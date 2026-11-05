<template>
  <q-dialog :model-value="!!order" @update:model-value="v=>{if(!v)$emit('close')}">
    <q-card style="width:460px;max-width:95vw"><q-card-section class="row items-center justify-between no-print"><div class="text-h6">Transaction receipt</div><q-btn flat round icon="close" aria-label="Close receipt" @click="$emit('close')"/></q-card-section>
      <q-card-section v-if="order" id="ahy-receipt">
        <div class="text-center"><div class="text-h6 text-weight-bold">AHY WATER STATION</div><div>Sales receipt</div><div class="text-caption">{{ order.receipt_number || `Legacy order #${order.id}` }}</div><div class="text-caption">{{ dateTime(order.created_at) }}</div></div>
        <q-separator class="q-my-md"/><div>Customer: {{ order.customer_name }}</div><div>Payment: {{ order.payment_method }}</div><div v-if="order.payment_reference">Reference: {{ order.payment_reference }}</div>
        <q-markup-table flat dense><thead><tr><th class="text-left">Item</th><th>Qty</th><th class="text-right">Amount</th></tr></thead><tbody><tr v-for="i in order.items" :key="i.id"><td style="white-space:normal">{{ i.product_name || i.product?.name || 'Legacy product' }}<div class="text-caption">{{ money(i.price) }} each</div></td><td>{{ i.quantity }}</td><td class="text-right">{{ money(i.subtotal) }}</td></tr></tbody></q-markup-table>
        <q-separator class="q-my-md"/><div class="row justify-between text-weight-bold text-h6"><span>Total</span><span>{{ money(order.total_amount) }}</span></div><div class="row justify-between"><span>Received</span><span>{{ money(order.cash_received) }}</span></div><div class="row justify-between"><span>Change</span><span>{{ money(order.change_amount) }}</span></div>
        <q-banner v-if="order.status==='Voided'" class="bg-red-1 text-negative q-mt-md">VOIDED · {{ order.void_reason }}</q-banner><p class="text-center text-caption q-mt-lg">Thank you for choosing AHY Water Station.<br>Project demonstration receipt.</p>
      </q-card-section>
      <q-card-actions align="right" class="no-print"><q-btn flat label="Close" @click="$emit('close')"/><q-btn color="primary" icon="print" label="Print / Save PDF" @click="print"/></q-card-actions>
    </q-card>
  </q-dialog>
</template>
<script setup lang="ts">
import { nextTick } from 'vue'; import { dateTime,money } from '../services/format'
defineProps<{ order: any | null }>();defineEmits(['close'])
async function print(){await nextTick();window.print()}
</script>
<style>
@media print {
  body { background: #fff !important; }
  body > * { visibility: hidden !important; }
  #ahy-receipt, #ahy-receipt * { visibility: visible !important; }
  #ahy-receipt { position: absolute; left: 0; top: 0; width: 100%; color: #000; background: #fff; }
  .q-dialog__inner { display: block !important; padding: 0 !important; }
  .q-dialog__inner > .q-card { max-height: none !important; overflow: visible !important; box-shadow: none !important; }
  .q-dialog { position: absolute !important; inset: 0 !important; }
  .no-print { display: none !important; }
}
</style>
