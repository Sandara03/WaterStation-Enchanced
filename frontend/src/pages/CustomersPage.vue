<template>
  <q-page class="q-pa-lg">
    <div class="screen-heading">
      <div>
        <h1 class="page-title">Customers</h1>
        <p class="page-subtitle">
          Know your regulars, keep delivery details handy, and review every purchase.
        </p>
      </div>
      <q-btn color="primary" icon="person_add" label="Add customer" @click="edit(null)" />
    </div>
    <q-card flat bordered class="q-pa-md q-mb-lg"
      ><div class="screen-toolbar">
        <q-input
          v-model="search"
          outlined
          dense
          clearable
          label="Search name, phone, or email"
          @keyup.enter="
            page = 1;
            load();
          "
          ><template #prepend><q-icon name="search" /></template></q-input
        ><q-btn
          color="primary"
          label="Search"
          @click="
            page = 1;
            load();
          "
        /><q-btn
          flat
          color="primary"
          icon="refresh"
          label="Refresh"
          :loading="loading"
          @click="load"
        /></div
    ></q-card>
    <q-banner v-if="error" class="bg-red-1 text-negative q-mb-md" role="alert"
      >{{ error }}<template #action><q-btn flat label="Retry" @click="load" /></template
    ></q-banner>
    <q-table
      :rows="rows"
      :columns="columns"
      row-key="id"
      :loading="loading"
      flat
      bordered
      hide-pagination
      :pagination="{ rowsPerPage: 0 }"
      no-data-label="No customers found. Add a customer or try another search."
    >
      <template #body-cell-name="p"
        ><q-td :props="p"
          ><q-btn
            flat
            no-caps
            class="q-px-none"
            color="primary"
            :label="p.row.name"
            @click="openProfile(p.row.id)"
          />
          <div class="text-caption text-grey-7">{{ p.row.email || 'No email recorded' }}</div></q-td
        ></template
      >
      <template #body-cell-completed_spend="p"
        ><q-td :props="p" class="text-weight-bold">{{
          money(p.row.completed_spend)
        }}</q-td></template
      >
      <template #body-cell-last_purchase_at="p"
        ><q-td :props="p">{{ dateTime(p.row.last_purchase_at) }}</q-td></template
      >
      <template #body-cell-actions="p"
        ><q-td :props="p"
          ><q-btn
            flat
            round
            color="primary"
            icon="person_search"
            :aria-label="'View ' + p.row.name"
            @click="openProfile(p.row.id)"
            ><q-tooltip>Customer profile</q-tooltip></q-btn
          ><q-btn
            flat
            round
            icon="edit"
            :aria-label="'Edit ' + p.row.name"
            @click="edit(p.row)" /><q-btn
            flat
            round
            icon="add_shopping_cart"
            color="primary"
            :to="{ path: '/pos', query: { customer: p.row.id } }"
            :aria-label="'New sale for ' + p.row.name" /></q-td
      ></template>
    </q-table>
    <div class="row justify-between items-center q-mt-md q-gutter-sm">
      <span class="text-caption text-grey-7"
        >{{ total }} {{ search ? 'matching ' : '' }}customers · Spending includes completed sales
        only.</span
      ><q-pagination
        v-model="page"
        :max="last"
        :max-pages="5"
        direction-links
        @update:model-value="load"
      />
    </div>
    <CustomerFormDialog v-model="dialog" :customer="editing" @saved="saved" />
    <q-dialog v-model="profileOpen"
      ><q-card class="details-dialog">
        <q-card-section class="row items-center justify-between"
          ><div>
            <q-badge color="blue-1" text-color="primary" label="CUSTOMER PROFILE" />
            <h2 class="section-heading q-mt-sm">{{ profile?.customer?.name || 'Customer' }}</h2>
          </div>
          <q-btn flat round icon="close" aria-label="Close customer profile" v-close-popup
        /></q-card-section>
        <q-linear-progress v-if="profileLoading" indeterminate />
        <q-banner v-if="profileError" class="bg-red-1 text-negative q-ma-md" role="alert">{{
          profileError
        }}</q-banner>
        <template v-if="profile"
          ><q-card-section class="q-pt-none">
            <div class="profile-summary q-mb-md">
              <div>
                <strong>{{ money(profile.summary.completed_spend) }}</strong
                ><span>Total completed purchases</span>
              </div>
              <div>
                <strong>{{ profile.summary.completed_orders }}</strong
                ><span>Completed orders</span>
              </div>
            </div>
            <div class="text-body2 q-gutter-xs">
              <p>
                <q-icon name="phone" color="primary" class="q-mr-sm" />{{
                  profile.customer.phone || 'No phone recorded'
                }}
              </p>
              <p>
                <q-icon name="email" color="primary" class="q-mr-sm" />{{
                  profile.customer.email || 'No email recorded'
                }}
              </p>
              <p class="wrap-reference">
                <q-icon name="location_on" color="primary" class="q-mr-sm" />{{
                  profile.customer.address || 'No address recorded'
                }}
              </p>
              <p v-if="profile.customer.notes" class="text-grey-7 wrap-reference">
                {{ profile.customer.notes }}
              </p>
            </div>
            <div class="quick-actions q-mt-md">
              <q-btn
                color="primary"
                icon="add_shopping_cart"
                label="New sale"
                :to="{ path: '/pos', query: { customer: profile.customer.id } }"
              /><q-btn
                outline
                color="primary"
                icon="edit"
                label="Edit details"
                @click="edit(profile.customer)"
              /><q-btn
                v-if="isAdmin"
                flat
                color="negative"
                icon="delete_outline"
                label="Delete"
                @click="remove(profile.customer)"
              />
            </div> </q-card-section
          ><q-separator /><q-card-section
            ><h3 class="section-heading">Purchase history</h3>
            <p class="text-caption text-grey-7 q-mt-xs">
              Select a receipt to view or print it. Last purchase:
              {{ dateTime(profile.summary.last_purchase_at) }}
            </p></q-card-section
          >
          <q-list separator
            ><q-item
              v-for="o in profile.orders.data"
              :key="o.id"
              clickable
              @click="viewReceipt(o.id)"
              ><q-item-section avatar><q-icon name="receipt_long" color="primary" /></q-item-section
              ><q-item-section
                ><q-item-label class="wrap-reference">{{
                  o.receipt_number || `Legacy #${o.id}`
                }}</q-item-label
                ><q-item-label caption
                  >{{ dateTime(o.created_at) }} · {{ o.payment_method }}</q-item-label
                ></q-item-section
              ><q-item-section side
                ><strong>{{ money(o.total_amount) }}</strong
                ><q-badge :color="o.status === 'Completed' ? 'teal' : 'negative'">{{
                  o.status
                }}</q-badge></q-item-section
              ></q-item
            ></q-list
          >
          <q-card-section v-if="!profile.orders.data.length" class="text-grey-7"
            >No purchases recorded yet.</q-card-section
          ><q-card-section
            ><q-pagination
              v-model="historyPage"
              :max="profile.orders.last_page || 1"
              :max-pages="5"
              @update:model-value="loadProfile"
          /></q-card-section>
          <template v-if="profile.deliveries.length"
            ><q-separator /><q-card-section
              ><h3 class="section-heading">Latest deliveries</h3></q-card-section
            ><q-list separator
              ><q-item v-for="d in profile.deliveries" :key="d.id" to="/deliveries"
                ><q-item-section
                  ><q-item-label>{{ d.recipient_name }}</q-item-label
                  ><q-item-label caption
                    >{{ dateTime(d.scheduled_at) }} ·
                    {{ d.rider_name || 'Unassigned rider' }}</q-item-label
                  ></q-item-section
                ><q-item-section side
                  ><q-badge color="blue-1" text-color="primary">{{
                    d.status
                  }}</q-badge></q-item-section
                ></q-item
              ></q-list
            ></template
          >
        </template>
      </q-card></q-dialog
    >
    <ReceiptDialog :order="receipt" @close="receipt = null" />
  </q-page>
</template>
<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { useQuasar } from 'quasar';
import api, { errorMessage } from '../services/api';
import { isAdmin } from '../services/auth';
import { dateTime, money } from '../services/format';
import CustomerFormDialog from '../components/CustomerFormDialog.vue';
import ReceiptDialog from '../components/ReceiptDialog.vue';
const q = useQuasar(),
  rows = ref<any[]>([]),
  loading = ref(false),
  error = ref(''),
  search = ref(''),
  page = ref(1),
  last = ref(1),
  total = ref(0),
  dialog = ref(false),
  editing = ref<any>(null),
  profileOpen = ref(false),
  profile = ref<any>(null),
  profileId = ref(0),
  historyPage = ref(1),
  profileLoading = ref(false),
  profileError = ref(''),
  receipt = ref<any>(null);
const columns = [
  { name: 'name', label: 'Customer', field: 'name', align: 'left' as const },
  { name: 'phone', label: 'Phone', field: 'phone', align: 'left' as const },
  {
    name: 'completed_orders_count',
    label: 'Completed orders',
    field: 'completed_orders_count',
    align: 'right' as const,
  },
  {
    name: 'completed_spend',
    label: 'Total spending',
    field: 'completed_spend',
    align: 'right' as const,
  },
  {
    name: 'last_purchase_at',
    label: 'Last purchase',
    field: 'last_purchase_at',
    align: 'left' as const,
  },
  { name: 'actions', label: 'Actions', field: 'id', align: 'right' as const },
];
let listRequest = 0,
  profileRequest = 0;
async function load() {
  const request = ++listRequest;
  loading.value = true;
  error.value = '';
  try {
    const { data } = await api.get('/customers', {
      params: { page: page.value, search: search.value },
    });
    if (request !== listRequest) return;
    rows.value = data.data;
    last.value = data.last_page;
    total.value = data.total;
    if (page.value > last.value) {
      page.value = last.value;
      await load();
    }
  } catch (e) {
    if (request === listRequest) error.value = errorMessage(e);
  } finally {
    if (request === listRequest) loading.value = false;
  }
}
function edit(row: any) {
  editing.value = row;
  dialog.value = true;
}
async function saved() {
  await load();
  if (profileOpen.value) await loadProfile();
  q.notify({ type: 'positive', message: 'Customer saved.' });
}
function openProfile(id: number) {
  profileId.value = id;
  historyPage.value = 1;
  profile.value = null;
  profileOpen.value = true;
  loadProfile();
}
async function loadProfile() {
  const request = ++profileRequest;
  profileLoading.value = true;
  profileError.value = '';
  try {
    const { data } = await api.get(`/customers/${profileId.value}`, {
      params: { page: historyPage.value },
    });
    if (request === profileRequest) profile.value = data;
  } catch (e) {
    if (request === profileRequest) profileError.value = errorMessage(e);
  } finally {
    if (request === profileRequest) profileLoading.value = false;
  }
}
async function viewReceipt(id: number) {
  try {
    receipt.value = (await api.get(`/orders/${id}`)).data;
  } catch (e) {
    q.notify({ type: 'negative', message: errorMessage(e) });
  }
}
function remove(row: any) {
  q.dialog({
    title: 'Delete customer?',
    message: `Delete ${row.name}? Customers with sales or delivery history are protected.`,
    cancel: true,
  }).onOk(async () => {
    try {
      await api.delete(`/customers/${row.id}`);
      profileOpen.value = false;
      await load();
    } catch (e) {
      q.notify({ type: 'negative', message: errorMessage(e) });
    }
  });
}
onMounted(load);
</script>
