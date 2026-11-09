<template>
  <q-dialog
    :model-value="!!order"
    @update:model-value="
      (v) => {
        if (!v) $emit('close');
      }
    "
  >
    <q-card class="receipt-dialog"
      ><q-card-section class="row items-center justify-between no-print"
        ><h2 class="section-heading">Transaction receipt</h2>
        <q-btn flat round icon="close" aria-label="Close receipt" @click="$emit('close')"
      /></q-card-section>
      <q-card-section v-if="order" id="ahy-receipt">
        <div class="text-center">
          <q-icon name="water_drop" size="32px" color="primary" />
          <div class="text-h6 text-weight-bold q-mt-sm">AHY WATER STATION</div>
          <div class="text-caption">POINT OF SALE & MANAGEMENT</div>
          <q-badge class="q-mt-sm" :color="order.status === 'Voided' ? 'negative' : 'teal'">{{
            order.status
          }}</q-badge>
          <div class="receipt-number q-mt-md">
            {{ order.receipt_number || `Legacy order #${order.id}` }}
          </div>
          <div class="text-caption">{{ dateTime(order.created_at) }} · Asia/Manila</div>
        </div>
        <q-separator class="q-my-md" />
        <div class="receipt-meta">
          <div>
            <span>Customer</span><strong>{{ order.customer_name }}</strong>
          </div>
          <div v-if="order.cashier">
            <span>Served by</span><strong>{{ order.cashier.name }}</strong>
          </div>
          <div>
            <span>Payment</span><strong>{{ order.payment_method }}</strong>
          </div>
          <div v-if="order.payment_reference">
            <span>Reference</span
            ><strong class="wrap-reference">{{ order.payment_reference }}</strong>
          </div>
        </div>
        <q-markup-table flat dense class="q-mt-md"
          ><thead>
            <tr>
              <th class="text-left">Item</th>
              <th>Qty</th>
              <th class="text-right">Amount</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="i in order.items" :key="i.id">
              <td style="white-space: normal">
                {{ i.product_name || i.product?.name || 'Legacy product' }}
                <div class="text-caption text-grey-7">{{ money(i.price) }} each</div>
              </td>
              <td>{{ i.quantity }}</td>
              <td class="text-right">{{ money(i.subtotal) }}</td>
            </tr>
          </tbody></q-markup-table
        >
        <q-separator class="q-my-md" />
        <div class="receipt-meta">
          <div class="text-weight-bold text-h6">
            <span>Total</span><strong>{{ money(order.total_amount) }}</strong>
          </div>
          <div>
            <span>{{ order.payment_method === 'Cash' ? 'Cash received' : 'Confirmed amount' }}</span
            ><strong>{{ money(order.cash_received) }}</strong>
          </div>
          <div>
            <span>Change</span><strong>{{ money(order.change_amount) }}</strong>
          </div>
        </div>
        <q-banner v-if="order.status === 'Voided'" class="bg-red-1 text-negative q-mt-md"
          >VOIDED · {{ order.void_reason }}</q-banner
        >
        <p class="text-center text-caption q-mt-lg">
          Thank you for choosing AHY Water Station.<br />Project demonstration receipt.
        </p>
      </q-card-section>
      <q-card-section class="no-print q-pt-none" v-if="order?.status === 'Completed'"
        ><q-btn
          v-if="!order.delivery || order.delivery.status === 'Cancelled'"
          outline
          color="primary"
          icon="local_shipping"
          label="Schedule delivery"
          class="full-width"
          @click="schedule"
        /><q-banner v-else rounded class="bg-blue-1 text-primary"
          >Delivery: {{ order.delivery.status }}</q-banner
        ></q-card-section
      >
      <q-card-actions align="right" class="no-print q-pa-md"
        ><q-btn flat label="Close" @click="$emit('close')" /><q-btn
          outline
          color="primary"
          icon="download"
          label="Save text"
          @click="download" /><q-btn
          color="primary"
          icon="print"
          label="Print / Save PDF"
          @click="print"
      /></q-card-actions>
    </q-card>
  </q-dialog>
</template>
<script setup lang="ts">
import { nextTick } from 'vue';
import { Notify } from 'quasar';
import { printReceipt, saveTextFile } from '../services/files';
import { useRouter } from 'vue-router';
import { dateTime, money } from '../services/format';
const props = defineProps<{ order: any | null }>(),
  emit = defineEmits(['close']),
  router = useRouter();
async function print() {
  await nextTick();
  try { await printReceipt(); } catch { Notify.create({ type: 'negative', message: 'Could not open printing. Try saving the receipt instead.' }); }
}
async function schedule() {
  const id = props.order.id;
  emit('close');
  await router.push({ path: '/deliveries', query: { order: id } });
}
async function download() {
  const o = props.order;
  if (!o) return;
  const lines = [
    'AHY WATER STATION',
    'Project demonstration receipt',
    o.receipt_number || `Order #${o.id}`,
    dateTime(o.created_at) + ' (Asia/Manila)',
    `Status: ${o.status}`,
    `Customer: ${o.customer_name}`,
    `Served by: ${o.cashier?.name || '—'}`,
    `Payment: ${o.payment_method}`,
    o.payment_reference ? `Reference: ${o.payment_reference}` : '',
    '',
    ...o.items.map(
      (i: any) =>
        `${i.quantity} x ${i.product_name || i.product?.name || 'Legacy product'} @ ${money(i.price)} = ${money(i.subtotal)}`,
    ),
    '',
    `Total: ${money(o.total_amount)}`,
    `Received: ${money(o.cash_received)}`,
    `Change: ${money(o.change_amount)}`,
    o.status === 'Voided' ? `VOIDED: ${o.void_reason}` : '',
    'Thank you for choosing AHY Water Station.',
  ];
  try {
    await saveTextFile(`${o.receipt_number || 'AHY-' + o.id}.txt`, lines.join('\r\n'), 'text/plain;charset=utf-8');
  } catch { Notify.create({ type: 'negative', message: 'Could not save or share the receipt. Please try again.' }); }
}
</script>
<style>
.receipt-dialog {
  width: 510px;
  max-width: 95vw;
}
.receipt-number {
  font-family: monospace;
  font-size: 12px;
  overflow-wrap: anywhere;
}
.receipt-meta > div {
  display: flex;
  justify-content: space-between;
  gap: 20px;
  margin-bottom: 8px;
}
.receipt-meta strong {
  text-align: right;
  font-weight: 600;
  max-width: 65%;
  overflow-wrap: anywhere;
}
@media print {
  @page {
    margin: 12mm;
  }
  body {
    background: #fff !important;
  }
  body > * {
    visibility: hidden !important;
  }
  #ahy-receipt,
  #ahy-receipt * {
    visibility: visible !important;
  }
  #ahy-receipt {
    position: relative !important;
    margin: 0 auto;
    width: 100%;
    max-width: 90mm;
    color: #000;
    background: #fff;
  }
  .q-dialog__inner {
    display: block !important;
    padding: 0 !important;
    position: static !important;
    transform: none !important;
  }
  .q-dialog__inner > .q-card {
    max-height: none !important;
    overflow: visible !important;
    box-shadow: none !important;
    width: 100% !important;
    max-width: 100% !important;
  }
  .q-dialog {
    position: absolute !important;
    inset: 0 !important;
  }
  .no-print {
    display: none !important;
  }
  .q-dialog__backdrop {
    display: none !important;
  }
}
</style>
