<template>
  <q-page class="q-pa-lg"
    ><div class="row justify-between items-center q-mb-lg">
      <div>
        <h1 class="page-title">Operating expenses</h1>
        <p class="page-subtitle">
          Record utilities, supplies, maintenance, and other station costs.
        </p>
      </div>
      <q-btn
        color="primary"
        icon="add"
        label="Record expense"
        @click="
          form = blank();
          dialog = true;
        "
      />
    </div>
    <q-banner v-if="error" class="bg-red-1 text-negative q-mb-md" role="alert">{{ error }}</q-banner
    ><q-table
      :rows="rows"
      :columns="columns"
      :loading="loading"
      row-key="id"
      flat
      bordered
      hide-pagination
      :pagination="{ rowsPerPage: 0 }"
      no-data-label="No expenses recorded."
      ><template #body-cell-actions="p"
        ><q-td :props="p"
          ><q-btn
            flat
            round
            icon="delete_outline"
            color="negative"
            aria-label="Remove expense entry"
            @click="remove(p.row)" /></q-td></template
    ></q-table>
    <div class="row justify-between items-center q-mt-md">
      <q-btn
        flat
        label="View sales and expense report"
        color="primary"
        to="/reports"
      /><q-pagination
        v-model="page"
        :max="last"
        :max-pages="5"
        direction-links
        @update:model-value="load"
      />
    </div>
    <q-dialog v-model="dialog" persistent
      ><q-card style="width: 460px; max-width: 95vw"
        ><q-card-section class="text-h6">Record expense</q-card-section
        ><q-form @submit="save"
          ><q-card-section class="q-gutter-md"
            ><q-input
              v-model="form.expense_date"
              type="date"
              outlined
              label="Expense date"
              :max="localDate()"
              :rules="[(v) => !!v || 'Required']" /><q-select
              v-model="form.category"
              outlined
              label="Category"
              :options="categories" /><q-input
              v-model="form.description"
              outlined
              label="Description"
              maxlength="255"
              :rules="[(v) => !!v.trim() || 'Required']" /><q-input
              v-model.number="form.amount"
              type="number"
              min="0.01"
              step="0.01"
              outlined
              label="Amount (PHP)"
              :rules="[
                (v) => Number(v) > 0 || 'Enter an amount greater than zero',
              ]" /></q-card-section
          ><q-card-actions align="right"
            ><q-btn flat label="Cancel" :disable="saving" v-close-popup /><q-btn
              type="submit"
              label="Save expense"
              color="primary"
              :loading="saving" /></q-card-actions></q-form></q-card
    ></q-dialog>
  </q-page>
</template>
<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { useQuasar } from 'quasar';
import api, { errorMessage } from '../services/api';
import { localDate, money } from '../services/format';
const q = useQuasar(),
  rows = ref<any[]>([]),
  loading = ref(false),
  saving = ref(false),
  dialog = ref(false),
  error = ref(''),
  page = ref(1),
  last = ref(1),
  categories = ['Utilities', 'Supplies', 'Maintenance', 'Transport', 'Salary', 'Rent', 'Other'],
  blank = () => ({
    expense_date: localDate(),
    category: 'Utilities',
    description: '',
    amount: null as number | null,
  }),
  form = ref(blank());
const columns = [
  { name: 'expense_date', label: 'Date', field: 'expense_date', align: 'left' as const },
  { name: 'category', label: 'Category', field: 'category', align: 'left' as const },
  { name: 'description', label: 'Description', field: 'description', align: 'left' as const },
  { name: 'amount', label: 'Amount', field: (r: any) => money(r.amount), align: 'right' as const },
  { name: 'actions', label: 'Actions', field: 'id', align: 'right' as const },
];
async function load() {
  loading.value = true;
  error.value = '';
  try {
    const { data } = await api.get('/expenses', { params: { page: page.value } });
    rows.value = data.data;
    last.value = data.last_page;
    if (page.value > last.value) {
      page.value = last.value;
      await load();
    }
  } catch (e) {
    error.value = errorMessage(e);
  } finally {
    loading.value = false;
  }
}
async function save() {
  if (saving.value) return;
  saving.value = true;
  try {
    await api.post('/expenses', form.value);
    dialog.value = false;
    page.value = 1;
    await load();
    q.notify({ type: 'positive', message: 'Expense recorded.' });
  } catch (e) {
    q.notify({ type: 'negative', message: errorMessage(e) });
  } finally {
    saving.value = false;
  }
}
function remove(row: any) {
  q.dialog({
    title: 'Remove incorrect expense entry?',
    message: `Remove ${money(row.amount)} for ${row.description}? The removal is retained in the activity log.`,
    cancel: true,
  }).onOk(async () => {
    try {
      await api.delete(`/expenses/${row.id}`);
      await load();
    } catch (e) {
      q.notify({ type: 'negative', message: errorMessage(e) });
    }
  });
}
onMounted(load);
</script>
