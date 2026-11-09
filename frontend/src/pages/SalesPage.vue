<template>
  <q-page class="q-pa-lg"
    ><div class="row justify-between items-center q-mb-lg">
      <div>
        <h1 class="page-title">Sales & receipts</h1>
        <p class="page-subtitle">Find a transaction, reprint its receipt, or review its status.</p>
      </div>
      <q-btn outline icon="refresh" label="Refresh" color="primary" @click="load" />
    </div>
    <q-card flat bordered class="q-pa-md q-mb-md"
      ><div class="row q-col-gutter-md">
        <div class="col-12 col-sm-7">
          <q-input
            v-model="search"
            outlined
            dense
            clearable
            label="Search receipt or customer"
            @keyup.enter="
              page = 1;
              load();
            "
          />
        </div>
        <div class="col-12 col-sm-3">
          <q-select
            v-model="status"
            outlined
            dense
            clearable
            label="Status"
            :options="['Completed', 'Voided']"
            @update:model-value="
              page = 1;
              load();
            "
          />
        </div>
        <div class="col-12 col-sm-2">
          <q-btn
            color="primary"
            label="Search"
            @click="
              page = 1;
              load();
            "
          />
        </div></div
    ></q-card>
    <q-banner v-if="error" class="bg-red-1 text-negative q-mb-md" role="alert">{{
      error
    }}</q-banner>
    <q-table
      :rows="rows"
      :columns="columns"
      :loading="loading"
      row-key="id"
      flat
      bordered
      hide-pagination
      :pagination="{ rowsPerPage: 0 }"
      no-data-label="No matching transactions."
      ><template #body-cell-total_amount="p"
        ><q-td :props="p">{{ money(p.row.total_amount) }}</q-td></template
      ><template #body-cell-status="p"
        ><q-td :props="p"
          ><q-badge :color="p.row.status === 'Completed' ? 'teal' : 'negative'">{{
            p.row.status
          }}</q-badge></q-td
        ></template
      ><template #body-cell-actions="p"
        ><q-td :props="p"
          ><q-btn
            flat
            dense
            icon="receipt_long"
            label="Receipt"
            color="primary"
            @click="receipt = p.row" /><q-btn
            v-if="isAdmin && p.row.status === 'Completed'"
            flat
            dense
            label="Void"
            color="negative"
            @click="voidSale(p.row)" /></q-td></template
    ></q-table>
    <div class="row justify-between items-center q-mt-md">
      <span class="text-caption text-grey-7"
        >{{ total }} transactions · Page {{ page }} of {{ last }}</span
      ><q-pagination
        v-model="page"
        :max="last"
        :max-pages="5"
        direction-links
        @update:model-value="load"
      />
    </div>
    <ReceiptDialog :order="receipt" @close="receipt = null" />
  </q-page>
</template>
<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { useQuasar } from 'quasar';
import api, { errorMessage } from '../services/api';
import { money, dateTime } from '../services/format';
import { isAdmin } from '../services/auth';
import ReceiptDialog from '../components/ReceiptDialog.vue';
const q = useQuasar(),
  rows = ref<any[]>([]),
  loading = ref(false),
  error = ref(''),
  search = ref(''),
  status = ref<string | null>(null),
  page = ref(1),
  last = ref(1),
  total = ref(0),
  receipt = ref<any>(null);
const columns = [
  {
    name: 'receipt_number',
    label: 'Receipt',
    field: (r: any) => r.receipt_number || `Legacy #${r.id}`,
    align: 'left' as const,
  },
  { name: 'customer_name', label: 'Customer', field: 'customer_name', align: 'left' as const },
  { name: 'payment_method', label: 'Payment', field: 'payment_method', align: 'left' as const },
  { name: 'total_amount', label: 'Total', field: 'total_amount', align: 'right' as const },
  { name: 'status', label: 'Status', field: 'status', align: 'left' as const },
  {
    name: 'created_at',
    label: 'Date',
    field: (r: any) => dateTime(r.created_at),
    align: 'left' as const,
  },
  { name: 'actions', label: 'Actions', field: 'id', align: 'right' as const },
];
async function load() {
  loading.value = true;
  error.value = '';
  try {
    const { data } = await api.get('/orders', {
      params: { page: page.value, search: search.value, status: status.value },
    });
    rows.value = data.data;
    last.value = data.last_page;
    total.value = data.total;
  } catch (e) {
    error.value = errorMessage(e);
  } finally {
    loading.value = false;
  }
}
function voidSale(order: any) {
  q.dialog({
    title: 'Void this sale?',
    message:
      'Use only when all items can be returned to stock. Stock will be restored once. Dispatched or delivered orders cannot be voided. Arrange any cash or digital refund separately. Enter a reason:',
    prompt: {
      model: '',
      type: 'text',
      isValid: (v: string) => v.trim().length >= 5 && v.length <= 500,
    },
    cancel: true,
    persistent: true,
  }).onOk(async (reason: string) => {
    try {
      await api.post(`/orders/${order.id}/void`, { reason });
      q.notify({ type: 'positive', message: 'Sale voided and stock restored.' });
      await load();
    } catch (e) {
      q.notify({ type: 'negative', message: errorMessage(e) });
    }
  });
}
onMounted(load);
</script>
