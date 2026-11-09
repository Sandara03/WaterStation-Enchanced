<template>
  <q-page class="q-pa-lg"
    ><div class="row justify-between items-center q-mb-lg">
      <div>
        <h1 class="page-title">Inventory control</h1>
        <p class="page-subtitle">
          Track stock, record restocking or damage, and review every movement.
        </p>
      </div>
      <q-btn color="primary" outline icon="refresh" label="Refresh" @click="load" />
    </div>
    <q-banner v-if="error" class="bg-red-1 text-negative q-mb-md" role="alert">{{
      error
    }}</q-banner>
    <q-card flat bordered class="q-mb-lg"
      ><q-card-section
        ><q-input v-model="search" outlined dense clearable label="Search products"
          ><template #prepend><q-icon name="search" /></template></q-input></q-card-section
      ><q-table
        :rows="products"
        :columns="columns"
        :filter="search || ''"
        row-key="id"
        :loading="loading"
        flat
        :pagination="{ rowsPerPage: 10 }"
        ><template #body-cell-stock="p"
          ><q-td :props="p"
            ><q-badge
              :color="
                !p.row.is_active
                  ? 'grey'
                  : p.row.stock === 0
                    ? 'negative'
                    : p.row.stock <= p.row.low_stock
                      ? 'orange-9'
                      : 'teal'
              "
              >{{ p.row.stock }} units</q-badge
            ></q-td
          ></template
        ><template #body-cell-actions="p"
          ><q-td :props="p"
            ><q-btn
              v-if="isAdmin"
              flat
              color="primary"
              icon="tune"
              label="Adjust"
              @click="open(p.row)" /><q-btn
              flat
              dense
              icon="history"
              label="History"
              @click="
                productId = p.row.id;
                page = 1;
                loadMovements();
              " /></q-td></template></q-table
    ></q-card>
    <q-card flat bordered
      ><q-card-section class="row justify-between items-center"
        ><div class="text-h6">Stock movement history</div>
        <q-select
          v-model="productId"
          clearable
          outlined
          dense
          emit-value
          map-options
          :options="products.map((p) => ({ label: p.name, value: p.id }))"
          label="Product"
          style="width: 280px; max-width: 100%"
          @update:model-value="
            page = 1;
            loadMovements();
          " /></q-card-section
      ><q-table
        :rows="movements"
        :columns="movementColumns"
        :loading="historyLoading"
        flat
        row-key="id"
        hide-pagination
        :pagination="{ rowsPerPage: 0 }"
        no-data-label="No stock movements yet." /><q-card-section class="row justify-end"
        ><q-pagination
          v-model="page"
          :max="last"
          :max-pages="5"
          direction-links
          @update:model-value="loadMovements" /></q-card-section
    ></q-card>
    <q-dialog v-model="dialog" persistent
      ><q-card style="width: 460px; max-width: 95vw"
        ><q-card-section class="text-h6">Adjust {{ selected?.name }}</q-card-section
        ><q-form @submit="save"
          ><q-card-section class="q-gutter-md"
            ><q-select
              v-model="form.type"
              outlined
              label="Adjustment type"
              emit-value
              map-options
              :options="types"
            /><q-input
              v-model.number="form.quantity"
              type="number"
              :min="form.type === 'correction' ? 0 : 1"
              step="1"
              outlined
              :label="form.type === 'correction' ? 'Physical count (sets stock)' : 'Quantity'"
              :rules="[
                (v) =>
                  (Number.isInteger(v) && v >= (form.type === 'correction' ? 0 : 1)) ||
                  'Enter a whole number',
              ]"
            /><q-input
              v-model.trim="form.reason"
              outlined
              label="Reason"
              maxlength="255"
              :rules="[(v) => v.length >= 5 || 'Use at least 5 characters']"
            /><q-banner class="bg-blue-1" rounded
              >Current: {{ selected?.stock }} · After adjustment: {{ preview }}</q-banner
            ></q-card-section
          ><q-card-actions align="right"
            ><q-btn flat label="Cancel" :disable="saving" v-close-popup /><q-btn
              color="primary"
              label="Save adjustment"
              type="submit"
              :loading="saving" /></q-card-actions></q-form></q-card
    ></q-dialog>
  </q-page>
</template>
<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useQuasar } from 'quasar';
import api, { errorMessage } from '../services/api';
import { isAdmin } from '../services/auth';
import { dateTime } from '../services/format';
const q = useQuasar(),
  products = ref<any[]>([]),
  movements = ref<any[]>([]),
  search = ref(''),
  loading = ref(false),
  historyLoading = ref(false),
  saving = ref(false),
  error = ref(''),
  dialog = ref(false),
  selected = ref<any>(null),
  productId = ref<number | null>(null),
  page = ref(1),
  last = ref(1),
  form = ref({ type: 'restock', quantity: 1, reason: '' });
const types = [
  { label: 'Restock (+)', value: 'restock' },
  { label: 'Damaged / lost (-)', value: 'damage' },
  { label: 'Correct to physical count', value: 'correction' },
];
const preview = computed(() =>
  form.value.type === 'correction'
    ? form.value.quantity
    : Number(selected.value?.stock || 0) +
      (form.value.type === 'restock' ? 1 : -1) * Number(form.value.quantity),
);
const columns = [
  { name: 'name', label: 'Product', field: 'name', align: 'left' as const, sortable: true },
  { name: 'stock', label: 'Stock', field: 'stock', align: 'right' as const, sortable: true },
  { name: 'low_stock', label: 'Alert level', field: 'low_stock', align: 'right' as const },
  {
    name: 'active',
    label: 'Status',
    field: (r: any) => (r.is_active ? 'Active' : 'Inactive'),
    align: 'left' as const,
  },
  { name: 'actions', label: 'Actions', field: 'id', align: 'right' as const },
];
const movementColumns = [
  {
    name: 'created_at',
    label: 'When',
    field: (r: any) => dateTime(r.created_at),
    align: 'left' as const,
  },
  { name: 'product', label: 'Product', field: (r: any) => r.product?.name, align: 'left' as const },
  { name: 'type', label: 'Type', field: 'type', align: 'left' as const },
  {
    name: 'change',
    label: 'Change',
    field: (r: any) =>
      `${r.after_stock - r.before_stock > 0 ? '+' : ''}${r.after_stock - r.before_stock}`,
    align: 'right' as const,
  },
  { name: 'before_stock', label: 'Before', field: 'before_stock', align: 'right' as const },
  { name: 'after_stock', label: 'After', field: 'after_stock', align: 'right' as const },
  { name: 'reason', label: 'Reason', field: 'reason', align: 'left' as const },
];
async function load() {
  loading.value = true;
  error.value = '';
  try {
    products.value = (await api.get('/products')).data;
    await loadMovements();
  } catch (e) {
    error.value = errorMessage(e);
  } finally {
    loading.value = false;
  }
}
async function loadMovements() {
  historyLoading.value = true;
  try {
    const { data } = await api.get('/inventory/movements', {
      params: { page: page.value, product_id: productId.value },
    });
    movements.value = data.data;
    last.value = data.last_page;
  } catch (e) {
    error.value = errorMessage(e);
  } finally {
    historyLoading.value = false;
  }
}
function open(p: any) {
  selected.value = p;
  form.value = { type: 'restock', quantity: 1, reason: '' };
  dialog.value = true;
}
async function save() {
  if (saving.value) return;
  saving.value = true;
  try {
    await api.post(`/inventory/${selected.value.id}/adjust`, form.value);
    dialog.value = false;
    q.notify({ type: 'positive', message: 'Stock adjusted and recorded.' });
    await load();
  } catch (e) {
    q.notify({ type: 'negative', message: errorMessage(e) });
  } finally {
    saving.value = false;
  }
}
onMounted(load);
</script>
