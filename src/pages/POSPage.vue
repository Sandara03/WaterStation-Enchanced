<template>
  <q-page class="q-pa-lg"><div class="row items-center justify-between q-mb-lg"><div><h1 class="page-title">Point of Sale</h1><p class="page-subtitle">Choose products, confirm payment, and issue a receipt.</p></div><q-btn outline color="primary" icon="refresh" label="Refresh stock" :loading="loading" :disable="!!pending" @click="load"/></div>
    <q-banner v-if="error" class="bg-red-1 text-negative q-mb-md" rounded role="alert">{{ error }}</q-banner>
    <q-banner v-if="pending" class="bg-orange-1 text-orange-10 q-mb-md" rounded>The last checkout has not been confirmed. Retry it with the button below. The same checkout key prevents a second sale. Review Sales & Receipts if the server remains unavailable.</q-banner>
    <div class="row q-col-gutter-lg">
      <div class="col-12 col-lg-7"><q-input v-model="search" outlined clearable label="Search water products" class="q-mb-md"><template #prepend><q-icon name="search"/></template></q-input>
        <q-linear-progress v-if="loading" indeterminate class="q-mb-md"/>
        <div class="row q-col-gutter-md"><div v-for="p in filtered" :key="p.id" class="col-12 col-sm-6"><q-card flat bordered class="product-card q-pa-md"><div class="row justify-between items-start"><q-avatar color="blue-1" text-color="primary" icon="water_drop" size="52px"/><q-badge :color="p.stock===0?'negative':p.stock<=p.low_stock?'orange-9':'teal'">{{ p.stock }} in stock</q-badge></div><div class="text-subtitle1 text-weight-bold q-mt-md">{{ p.name }}</div><div class="text-caption text-grey-7" style="min-height:32px">{{ p.description || 'Drinking water' }}</div><div class="row justify-between items-center q-mt-md"><div class="text-h6 text-primary">{{ money(p.price) }}</div><q-btn unelevated color="primary" icon="add" label="Add" :disable="!!pending||saving||quantity(p.id)>=p.stock" @click="add(p)"/></div></q-card></div></div><p v-if="!loading&&!filtered.length" class="text-grey-7 q-pa-lg text-center">No products found. Check your search or ask an administrator to add products.</p>
      </div>
      <div class="col-12 col-lg-5"><q-card flat bordered class="q-pa-lg"><div class="text-h6 text-weight-bold">Current order <q-badge color="blue-1" text-color="primary">{{ itemCount }} items</q-badge></div>
        <div v-if="!cart.length" class="text-center q-py-xl text-grey-6"><q-icon name="shopping_basket" size="52px"/><p class="q-mt-md">Add a product to begin.</p></div>
        <q-list separator><q-item v-for="item in cart" :key="item.product.id" class="q-px-none"><q-item-section><q-item-label>{{ item.product.name }}</q-item-label><q-item-label caption>{{ money(item.product.price) }} each</q-item-label><q-item-label class="text-weight-bold">{{ money(Number(item.product.price)*item.quantity) }}</q-item-label></q-item-section><q-item-section side><div class="row items-center"><q-btn flat round dense icon="remove" aria-label="Decrease quantity" :disable="saving||!!pending" @click="change(item,-1)"/><span class="q-px-sm">{{ item.quantity }}</span><q-btn flat round dense icon="add" aria-label="Increase quantity" :disable="saving||!!pending||item.quantity>=item.product.stock" @click="change(item,1)"/></div><q-btn flat dense color="negative" label="Remove" :disable="saving||!!pending" @click="cart=cart.filter(x=>x.product.id!==item.product.id)"/></q-item-section></q-item></q-list>
        <template v-if="cart.length||pending"><q-separator class="q-my-md"/>
          <q-select v-model="customer" :options="customers" option-label="name" outlined clearable use-input input-debounce="300" label="Customer (optional)" :disable="saving||!!pending" @filter="filterCustomers" class="q-mb-md"><template #no-option><q-item><q-item-section>No matching customers. Add one on Customers.</q-item-section></q-item></template></q-select>
          <q-input v-if="!customer" v-model.trim="customerName" outlined label="Walk-in name (optional)" :disable="saving||!!pending" class="q-mb-md" maxlength="255"/>
          <q-select v-model="paymentMethod" :options="['Cash','GCash','Bank Transfer']" outlined label="Payment method" :disable="saving||!!pending" class="q-mb-md"/>
          <q-input v-if="paymentMethod!=='Cash'" v-model.trim="reference" outlined label="Payment reference" hint="Confirm payment manually before completing the sale." :disable="saving||!!pending" class="q-mb-md" maxlength="100"/>
          <q-input v-model.number="received" type="number" min="0" step="0.01" outlined :label="paymentMethod==='Cash'?'Cash received (PHP)':'Confirmed amount (PHP)'" :disable="saving||!!pending" class="q-mb-sm"/>
          <q-btn flat dense label="Use exact amount" color="primary" :disable="saving||!!pending" @click="received=total"/>
          <div class="row justify-between text-h5 text-weight-bold q-mt-md"><span>Total</span><span>{{ money(total) }}</span></div><div class="row justify-between q-mt-sm"><span>Change</span><span>{{ money(Math.max(0,Number(received||0)-total)) }}</span></div>
          <q-btn class="full-width q-mt-lg" size="lg" unelevated color="primary" :label="pending?'Retry checkout':'Complete sale'" icon="point_of_sale" :loading="saving" :disable="loading||!online" @click="place"/>
          <q-btn flat class="full-width q-mt-sm" label="Clear cart" color="negative" :disable="saving||!!pending" @click="clear"/>
        </template>
      </q-card></div>
    </div><ReceiptDialog :order="receipt" @close="receipt=null"/>
  </q-page>
</template>
<script setup lang="ts">
import { computed,onMounted,onUnmounted,ref,watch } from 'vue';import { useQuasar } from 'quasar';import api,{errorMessage} from '../services/api';import {money} from '../services/format';import ReceiptDialog from '../components/ReceiptDialog.vue'
interface Product{id:number;name:string;description?:string;price:string|number;stock:number;low_stock:number;is_active:boolean}
interface Item{product:Product;quantity:number}
const q=useQuasar(),products=ref<Product[]>([]),cart=ref<Item[]>([]),search=ref(''),loading=ref(false),saving=ref(false),error=ref(''),receipt=ref<any>(null),customers=ref<any[]>([]),customer=ref<any>(null),customerName=ref(''),paymentMethod=ref('Cash'),received=ref<number|null>(null),reference=ref(''),pending=ref<any>(null)
const online=ref(navigator.onLine),filtered=computed(()=>products.value.filter(p=>p.is_active&&p.name.toLowerCase().includes((search.value||'').toLowerCase())))
const connectivity=()=>{online.value=navigator.onLine}
onMounted(()=>{window.addEventListener('online',connectivity);window.addEventListener('offline',connectivity)})
onUnmounted(()=>{window.removeEventListener('online',connectivity);window.removeEventListener('offline',connectivity)})
const total=computed(()=>cart.value.reduce((sum,i)=>sum+Math.round(Number(i.product.price)*100)*i.quantity,0)/100),itemCount=computed(()=>cart.value.reduce((sum,i)=>sum+i.quantity,0))
function quantity(id:number){return cart.value.find(i=>i.product.id===id)?.quantity||0}
function add(p:Product){if(saving.value||pending.value||quantity(p.id)>=p.stock)return;const i=cart.value.find(i=>i.product.id===p.id);if(i)i.quantity++;else cart.value.push({product:p,quantity:1})}
function change(i:Item,d:number){if(saving.value||pending.value)return;if(i.quantity+d<=0)cart.value=cart.value.filter(x=>x!==i);else if(i.quantity+d<=i.product.stock)i.quantity+=d}
function store(key:string,value:any){try{sessionStorage.setItem(key,JSON.stringify(value))}catch{error.value='Browser storage is unavailable. Keep this page open during checkout.'}}
watch(cart,v=>store('ahy_cart',v),{deep:true})
async function filterCustomers(value:string,update:(fn:()=>void)=>void){try{const {data}=await api.get('/customers',{params:{search:value}});update(()=>{customers.value=data.data})}catch(e){q.notify({type:'negative',message:errorMessage(e)});update(()=>{customers.value=[]})}}
async function load(){loading.value=true;error.value='';try{products.value=(await api.get('/products')).data;let adjusted=false;cart.value=cart.value.flatMap(i=>{const p=products.value.find(p=>p.id===i.product.id&&p.is_active&&p.stock>0);if(!p){adjusted=true;return []}const n=Math.min(i.quantity,p.stock);if(n!==i.quantity||Number(p.price)!==Number(i.product.price))adjusted=true;return [{product:p,quantity:n}]});if(adjusted)q.notify({type:'warning',message:'Cart updated for current prices and available stock. Review before paying.'})}catch(e){error.value=errorMessage(e)}finally{loading.value=false}}
function clear(){q.dialog({title:'Clear current order?',message:'Remove all items from the cart?',cancel:true}).onOk(()=>{cart.value=[];received.value=null})}
async function place(){
  if(saving.value)return
  if(!pending.value){
    if(!cart.value.length)return
    const paid=Number(received.value),cents=Math.round(paid*100),due=Math.round(total.value*100)
    if(!Number.isFinite(paid)||received.value===null||cents<due||(paymentMethod.value!=='Cash'&&cents!==due)){error.value='Enter sufficient cash, or the exact confirmed digital payment amount.';return}
    if(paymentMethod.value!=='Cash'&&!reference.value){error.value='Enter the confirmed digital payment reference.';return}
    pending.value={checkout_key:crypto.randomUUID(),customer_id:customer.value?.id||null,customer_name:customer.value?.name||customerName.value||'Walk-in Customer',payment_method:paymentMethod.value,cash_received:paid,payment_reference:paymentMethod.value==='Cash'?null:reference.value,items:cart.value.map(i=>({product_id:i.product.id,quantity:i.quantity}))}
    store('ahy_checkout',pending.value)
  }
  saving.value=true;error.value=''
  try{const {data}=await api.post('/orders',pending.value);receipt.value=data;pending.value=null;sessionStorage.removeItem('ahy_checkout');cart.value=[];customer.value=null;customerName.value='';received.value=null;reference.value='';q.notify({type:'positive',message:'Sale completed. Receipt is ready.'});await load()}
  catch(e:any){error.value=errorMessage(e);if([403,409,422].includes(e.response?.status)){pending.value=null;sessionStorage.removeItem('ahy_checkout')}}
  finally{saving.value=false}
}
onMounted(async()=>{
  try{const saved=JSON.parse(sessionStorage.getItem('ahy_cart')||'[]');cart.value=Array.isArray(saved)?saved.filter((i:any)=>i?.product?.id&&Number.isInteger(i.quantity)&&i.quantity>0):[];pending.value=JSON.parse(sessionStorage.getItem('ahy_checkout')||'null')}catch{cart.value=[]}
  if(pending.value){const p=pending.value;customerName.value=p.customer_name;paymentMethod.value=p.payment_method;received.value=p.cash_received;reference.value=p.payment_reference||'';try{products.value=(await api.get('/products')).data}catch(e){error.value=errorMessage(e)}}else await load()
})
</script>
<style scoped>.product-card{height:100%;transition:box-shadow .2s}.product-card:hover{box-shadow:0 8px 25px #14567512}</style>
