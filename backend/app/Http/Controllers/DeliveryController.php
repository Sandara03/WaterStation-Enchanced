<?php
namespace App\Http\Controllers;
use App\Models\{Delivery,Order};
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
class DeliveryController extends Controller
{
    private const ACTIVE=['Pending','Preparing','Out for Delivery'];
    private const NEXT=['Pending'=>['Preparing','Cancelled'],'Preparing'=>['Out for Delivery','Cancelled'],'Out for Delivery'=>['Delivered'],'Delivered'=>[],'Cancelled'=>[]];
    public function index(Request $r)
    {
        $d=$r->validate(['status'=>'nullable|in:Pending,Preparing,Out for Delivery,Delivered,Cancelled','search'=>'nullable|string|max:255','schedule'=>'nullable|in:today,overdue,upcoming']);
        $active=Delivery::whereIn('status',self::ACTIVE);
        $result=Delivery::with(['customer','order'])
            ->when($d['status']??null,fn($q,$s)=>$q->where('status',$s))
            ->when($d['search']??null,fn($q,$s)=>$q->where(fn($q)=>$q->where('recipient_name','like','%'.$s.'%')->orWhere('phone','like','%'.$s.'%')->orWhere('rider_name','like','%'.$s.'%')->orWhereHas('order',fn($q)=>$q->where('receipt_number','like','%'.$s.'%'))));
        if(($d['schedule']??null)==='today')$result->whereDate('scheduled_at',today());
        if(($d['schedule']??null)==='overdue')$result->whereIn('status',self::ACTIVE)->where('scheduled_at','<',now());
        if(($d['schedule']??null)==='upcoming')$result->whereIn('status',self::ACTIVE)->where('scheduled_at','>=',now());
        return $result->latest('id')->paginate(20)->toArray()+['summary'=>[
            'active'=>(clone $active)->count(),'today'=>(clone $active)->whereDate('scheduled_at',today())->count(),
            'overdue'=>(clone $active)->where('scheduled_at','<',now())->count(),
            'delivered_today'=>Delivery::where('status','Delivered')->whereDate('delivered_at',today())->count(),
        ]];
    }
    public function show(Delivery $delivery){return $delivery->load(['customer','order','events.actor']);}
    private function rules():array
    {
        return ['recipient_name'=>'sometimes|required|string|max:255','phone'=>'nullable|string|max:40',
            'address'=>'sometimes|required|string|max:2000','scheduled_at'=>'nullable|date_format:Y-m-d\TH:i',
            'notes'=>'nullable|string|max:2000','rider_name'=>'nullable|string|max:120'];
    }
    public function store(Request $r)
    {
        $d=$r->validate(array_merge($this->rules(),['order_id'=>'required|integer|exists:orders,id','recipient_name'=>'required|string|max:255','address'=>'required|string|max:2000']));
        $delivery=DB::transaction(function()use($d,$r){
            $order=Order::lockForUpdate()->findOrFail($d['order_id']);
            abort_unless($order->status==='Completed',409,'Only completed sales can be delivered.');
            abort_if(Delivery::where('order_id',$order->id)->where('status','!=','Cancelled')->exists(),409,'This sale already has a delivery.');
            $delivery=Delivery::create($d+['customer_id'=>$order->customer_id,'status'=>'Pending']);
            $delivery->events()->create(['user_id'=>$r->user()->id,'status'=>'Pending','message'=>'Delivery scheduled.','created_at'=>now()]);
            Audit::record('delivery.created','Delivery',$delivery->id,'Delivery scheduled',['order_id'=>$order->id]);
            return $delivery->load('events.actor');
        },3);
        return response()->json($delivery,201);
    }
    public function update(Request $r,Delivery $delivery)
    {
        $d=$r->validate($this->rules()+['status'=>'sometimes|required|in:Pending,Preparing,Out for Delivery,Delivered,Cancelled','reason'=>'nullable|required_if:status,Cancelled|string|min:5|max:500']);
        if(!count(Arr::except($d,['reason'])))throw ValidationException::withMessages(['delivery'=>'Provide a status or delivery detail to update.']);
        return DB::transaction(function()use($delivery,$d,$r){
            // Sale voids also lock the order before its delivery.
            if($delivery->order_id)Order::whereKey($delivery->order_id)->lockForUpdate()->firstOrFail();
            $locked=Delivery::lockForUpdate()->findOrFail($delivery->id);
            $details=Arr::except($d,['status','reason']);
            if($details&&!in_array($locked->status,['Pending','Preparing']))throw ValidationException::withMessages(['delivery'=>'Delivery details can only be edited before dispatch.']);
            if(isset($d['status'])&&!in_array($d['status'],self::NEXT[$locked->status]??[]))throw ValidationException::withMessages(['status'=>'Invalid delivery transition. Follow the next step in the workflow.']);
            $locked->fill($details);
            $changed=array_keys($locked->getDirty());
            $message=$changed?'Updated '.implode(', ',array_map(fn($field)=>str_replace('_',' ',$field),$changed)).'.':'';
            if(isset($d['status'])){
                $locked->status=$d['status'];
                $locked->delivered_at=$d['status']==='Delivered'?now():null;
                $message=trim($message.' Delivery '.$d['status'].'. '.($d['reason']??''));
            }
            if(!$locked->isDirty())return $locked->load(['customer','order','events.actor']);
            $locked->save();
            $locked->events()->create(['user_id'=>$r->user()->id,'status'=>$locked->status,'message'=>$message,'created_at'=>now()]);
            Audit::record(isset($d['status'])?'delivery.status_changed':'delivery.updated','Delivery',$locked->id,$message);
            return $locked->fresh()->load(['customer','order','events.actor']);
        },3);
    }
}
