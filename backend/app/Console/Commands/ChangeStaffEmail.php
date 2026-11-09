<?php
namespace App\Console\Commands;

use App\Models\{AccessToken, User};
use App\Support\Audit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\{DB, Validator};
use Illuminate\Validation\ValidationException;

class ChangeStaffEmail extends Command
{
    protected $signature = 'staff:email {current_email} {new_email}';
    protected $description = 'Change a staff login email, keeping its password, role, and transaction history';

    public function handle(): int
    {
        $data = [
            'current_email'=>strtolower(trim($this->argument('current_email'))),
            'new_email'=>strtolower(trim($this->argument('new_email'))),
        ];
        $validator = Validator::make($data,['current_email'=>'required|email|max:255','new_email'=>'required|email|max:255']);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) $this->error($error);
            return self::FAILURE;
        }
        try {
            DB::transaction(function () use ($data) {
                $user = User::whereRaw('LOWER(email) = ?',[$data['current_email']])->lockForUpdate()->first();
                if (!$user) throw ValidationException::withMessages(['current_email'=>'No staff account has that current email.']);
                if (User::whereRaw('LOWER(email) = ?',[$data['new_email']])->where('id','!=',$user->id)->exists()) {
                    throw ValidationException::withMessages(['new_email'=>'That email belongs to another staff account.']);
                }
                $user->update(['email'=>$data['new_email']]);
                AccessToken::where('user_id',$user->id)->delete();
                Audit::record('staff.email_changed','User',$user->id,'Staff login email changed from console');
            },3);
        } catch (ValidationException $e) {
            foreach ($e->errors() as $messages) foreach ($messages as $message) $this->error($message);
            return self::FAILURE;
        }
        $this->info('Login email updated. Password and role were preserved. Sign in again with '.$data['new_email'].'.');
        return self::SUCCESS;
    }
}
