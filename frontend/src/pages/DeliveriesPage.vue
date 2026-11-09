<template>
  <q-page class="q-pa-lg">
    <div class="screen-heading">
      <div>
        <h1 class="page-title">Deliveries</h1>
        <p class="page-subtitle">
          Plan the route, assign a rider, and follow each order through to delivery.
        </p>
      </div>
      <q-btn color="primary" icon="add" label="Schedule delivery" @click="open()" />
    </div>
    <div class="metric-grid q-mb-lg">
      <q-card v-for="tile in tiles" :key="tile.label" flat bordered class="metric-card q-pa-lg"
        ><div class="row justify-between">
          <span class="text-grey-7">{{ tile.label }}</span
          ><q-icon :name="tile.icon" :color="tile.color" size="25px" />
        </div>
        <div class="text-h4 text-weight-bold q-mt-md" :class="'text-' + tile.color">
          {{ tile.value }}
        </div></q-card
      >
    </div>
    <q-card flat bordered class="q-pa-md q-mb-lg"
      ><div class="screen-toolbar">
        <q-input
          v-model="search"
          outlined
          dense
          clearable
          label="Search recipient, phone, rider, or receipt"
          @keyup.enter="
            page = 1;
            load();
          "
          ><template #prepend><q-icon name="search" /></template></q-input
        ><q-select
          v-model="status"
          outlined
          clearable
          dense
          label="Status"
          :options="statuses"
          @update:model-value="
            page = 1;
            load();
          "
        /><q-select
          v-model="schedule"
          outlined
          clearable
          dense
          label="Schedule"
          :options="scheduleOptions"
          emit-value
          map-options
          @update:model-value="
            page = 1;
            load();
          "
        /><q-btn
          color="primary"
          icon="search"
          label="Search"
          @click="
            page = 1;
            load();
          "
        /><q-btn
          flat
          color="primary"
          icon="refresh"
          aria-label="Refresh deliveries"
          :loading="loading"
          @click="load"
        />
      </div>
      <p class="text-caption text-grey-7 q-mt-sm q-mb-none">
        Times use Asia/Manila. The summary above covers all deliveries.
      </p></q-card
    >
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
      no-data-label="No matching deliveries. Try clearing the filters or schedule a completed sale."
    >
      <template #body-cell-recipient_name="p"
        ><q-td :props="p"
          ><q-btn
            no-caps
            flat
            color="primary"
            class="q-px-none"
            :label="p.row.recipient_name"
            @click="show(p.row.id)"
          />
          <div class="text-caption text-grey-7">{{ p.row.phone || 'No phone recorded' }}</div></q-td
        ></template
      >
      <template #body-cell-scheduled_at="p"
        ><q-td :props="p"
          >{{ dateTime(p.row.scheduled_at) }}
          <div v-if="overdue(p.row)" class="text-caption text-negative">
            <q-icon name="schedule" /> Past scheduled time
          </div></q-td
        ></template
      >
      <template #body-cell-status="p"
        ><q-td :props="p"
          ><q-badge :color="statusColor(p.row.status)">{{ p.row.status }}</q-badge></q-td
        ></template
      >
      <template #body-cell-actions="p"
        ><q-td :props="p"
          ><q-btn
            flat
            round
            color="primary"
            icon="visibility"
            :aria-label="'View delivery for ' + p.row.recipient_name"
            @click="show(p.row.id)" /><q-btn
            v-if="editable(p.row)"
            flat
            round
            icon="edit"
            :aria-label="'Edit delivery for ' + p.row.recipient_name"
            @click="open(p.row)" /><q-btn
            v-if="nextStatus[p.row.status]"
            flat
            dense
            color="primary"
            :label="nextStatus[p.row.status]"
            :disable="updating === p.row.id"
            @click="transition(p.row, nextStatus[p.row.status]!)" /></q-td
      ></template>
    </q-table>
    <div class="row justify-between items-center q-mt-md q-gutter-sm">
      <span class="text-caption text-grey-7"
        >{{ total }} matching deliveries · Page {{ page }} of {{ last }}</span
      ><q-pagination
        v-model="page"
        :max="last"
        :max-pages="5"
        direction-links
        @update:model-value="load"
      />
    </div>

    <q-dialog v-model="dialog" persistent
      ><q-card class="form-dialog"
        ><q-card-section class="row items-center justify-between"
          ><h2 class="section-heading">{{ editing ? 'Edit delivery' : 'Schedule delivery' }}</h2>
          <q-btn
            flat
            round
            icon="close"
            aria-label="Close delivery form"
            :disable="saving"
            @click="dialog = false" /></q-card-section
        ><q-form @submit="save"
          ><q-card-section class="q-gutter-md q-pt-none">
            <q-banner v-if="formError" rounded class="bg-red-1 text-negative" role="alert">{{
              formError
            }}</q-banner>
            <div v-if="editing" class="text-caption text-grey-7 wrap-reference">
              {{ editing.order?.receipt_number || `Sale #${editing.order_id}` }} ·
              {{ editing.status }}
            </div>
            <q-select
              v-else
              v-model="order"
              outlined
              use-input
              input-debounce="300"
              label="Find completed sale by receipt / customer"
              :options="orders"
              :option-label="orderLabel"
              @filter="findOrders"
              @update:model-value="choose"
              :rules="[(v) => !!v || 'Select a sale']"
              ><template #no-option
                ><q-item
                  ><q-item-section>No matching completed sales.</q-item-section></q-item
                ></template
              ></q-select
            >
            <q-input
              v-model.trim="form.recipient_name"
              outlined
              label="Recipient"
              maxlength="255"
              :rules="[(v) => !!v?.trim() || 'Recipient is required']"
            />
            <q-input v-model.trim="form.phone" outlined label="Phone" type="tel" maxlength="40" />
            <q-input
              v-model.trim="form.address"
              outlined
              type="textarea"
              autogrow
              label="Delivery address"
              maxlength="2000"
              :rules="[(v) => !!v?.trim() || 'Address is required']"
            />
            <q-input
              v-model="form.scheduled_at"
              outlined
              type="datetime-local"
              label="Scheduled time (Asia/Manila)"
              stack-label
            />
            <q-input
              v-model.trim="form.rider_name"
              outlined
              label="Assigned rider (optional)"
              maxlength="120"
            />
            <q-input
              v-model.trim="form.notes"
              outlined
              type="textarea"
              autogrow
              label="Delivery instructions"
              maxlength="2000"
            /> </q-card-section
          ><q-card-actions align="right" class="q-pa-md"
            ><q-btn flat label="Cancel" :disable="saving" @click="dialog = false" /><q-btn
              color="primary"
              type="submit"
              :label="editing ? 'Save delivery' : 'Schedule delivery'"
              :loading="saving" /></q-card-actions></q-form></q-card
    ></q-dialog>

    <q-dialog v-model="detailsOpen"
      ><q-card class="details-dialog"
        ><q-card-section class="row items-center justify-between"
          ><div>
            <q-badge color="blue-1" text-color="primary" label="DELIVERY TRACKING" />
            <h2 class="section-heading q-mt-sm">
              {{ detail?.recipient_name || 'Delivery details' }}
            </h2>
          </div>
          <q-btn
            flat
            round
            icon="close"
            aria-label="Close delivery details"
            v-close-popup /></q-card-section
        ><q-linear-progress v-if="detailLoading" indeterminate /><q-banner
          v-if="detailError"
          class="bg-red-1 text-negative q-ma-md"
          role="alert"
          >{{ detailError }}</q-banner
        >
        <template v-if="detail"
          ><q-card-section class="q-pt-none"
            ><q-badge :color="statusColor(detail.status)">{{ detail.status }}</q-badge
            ><q-badge v-if="overdue(detail)" color="red-1" text-color="negative" class="q-ml-sm"
              >Past scheduled time</q-badge
            >
            <ol v-if="detail.status !== 'Cancelled'" class="delivery-progress">
              <li
                v-for="(step, index) in steps"
                :key="step"
                :class="{ done: steps.indexOf(detail.status) >= index }"
                :aria-current="detail.status === step ? 'step' : undefined"
              >
                <q-icon
                  :name="
                    steps.indexOf(detail.status) > index ? 'check_circle' : 'radio_button_checked'
                  "
                /><span>{{ step }}</span>
              </li>
            </ol>
            <dl class="delivery-fields">
              <dt>Sale receipt</dt>
              <dd class="wrap-reference">
                {{ detail.order?.receipt_number || `Sale #${detail.order_id}` }}
              </dd>
              <dt>Phone</dt>
              <dd>{{ detail.phone || 'Not recorded' }}</dd>
              <dt>Address</dt>
              <dd>{{ detail.address }}</dd>
              <dt>Scheduled</dt>
              <dd>{{ dateTime(detail.scheduled_at) }}</dd>
              <dt>Assigned rider</dt>
              <dd>{{ detail.rider_name || 'Not assigned' }}</dd>
              <dt>Instructions</dt>
              <dd class="delivery-notes">{{ detail.notes || 'No extra instructions' }}</dd>
              <template v-if="detail.delivered_at"
                ><dt>Delivered</dt>
                <dd>{{ dateTime(detail.delivered_at) }}</dd></template
              >
            </dl>
            <div class="quick-actions q-mt-md">
              <q-btn
                v-if="nextStatus[detail.status]"
                color="primary"
                :label="'Mark ' + nextStatus[detail.status]"
                :loading="updating === detail.id"
                @click="transition(detail, nextStatus[detail.status]!)"
              /><q-btn
                v-if="editable(detail)"
                outline
                color="primary"
                icon="edit"
                label="Edit / reschedule"
                @click="open(detail)"
              /><q-btn
                outline
                color="primary"
                icon="receipt_long"
                label="View receipt"
                @click="viewReceipt(detail.order_id)"
              /><q-btn
                v-if="editable(detail)"
                flat
                color="negative"
                label="Cancel delivery"
                :disable="updating === detail.id"
                @click="cancel(detail)"
              />
            </div>
            <p class="text-caption text-grey-7 q-mt-md">
              Recipient, address, schedule, and rider can be edited before dispatch.
            </p> </q-card-section
          ><q-separator /><q-card-section
            ><h3 class="section-heading">Delivery timeline</h3>
            <q-timeline color="primary" class="q-mb-none"
              ><q-timeline-entry
                v-for="event in detail.events"
                :key="event.id"
                :title="event.status"
                :subtitle="dateTime(event.created_at) + ' · ' + (event.actor?.name || 'System')"
                :icon="event.status === 'Delivered' ? 'check_circle' : 'local_shipping'"
                ><span class="wrap-reference">{{ event.message }}</span></q-timeline-entry
              ></q-timeline
            >
            <p v-if="!detail.events.length" class="text-grey-7 text-caption">
              This delivery predates timeline tracking. Future changes will appear here.
            </p></q-card-section
          ></template
        >
      </q-card></q-dialog
    >
    <ReceiptDialog :order="receipt" @close="receipt = null" />
  </q-page>
</template>
<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import { useQuasar } from 'quasar';
import { useRoute, useRouter } from 'vue-router';
import api, { errorMessage } from '../services/api';
import { dateTime, localDateTimeInput } from '../services/format';
import ReceiptDialog from '../components/ReceiptDialog.vue';
const q = useQuasar(),
  route = useRoute(),
  router = useRouter(),
  rows = ref<any[]>([]),
  orders = ref<any[]>([]),
  order = ref<any>(null),
  editing = ref<any>(null),
  loading = ref(false),
  saving = ref(false),
  error = ref(''),
  formError = ref(''),
  status = ref<string | null>(null),
  schedule = ref<string | null>(null),
  search = ref(''),
  page = ref(1),
  last = ref(1),
  total = ref(0),
  dialog = ref(false),
  updating = ref<number | null>(null),
  detailsOpen = ref(false),
  detail = ref<any>(null),
  detailId = ref(0),
  detailLoading = ref(false),
  detailError = ref(''),
  receipt = ref<any>(null);
const blank = () => ({
    recipient_name: '',
    phone: '',
    address: '',
    scheduled_at: '',
    notes: '',
    rider_name: '',
  }),
  form = ref(blank()),
  summary = ref({ active: 0, today: 0, overdue: 0, delivered_today: 0 });
const statuses = ['Pending', 'Preparing', 'Out for Delivery', 'Delivered', 'Cancelled'],
  steps = statuses.slice(0, 4),
  nextStatus: Record<string, string> = {
    Pending: 'Preparing',
    Preparing: 'Out for Delivery',
    'Out for Delivery': 'Delivered',
  },
  scheduleOptions = [
    { label: 'Scheduled today', value: 'today' },
    { label: 'Past scheduled time', value: 'overdue' },
    { label: 'Upcoming', value: 'upcoming' },
  ];
const tiles = computed(() => [
  { label: 'In progress', value: summary.value.active, icon: 'local_shipping', color: 'primary' },
  { label: 'Scheduled today', value: summary.value.today, icon: 'event', color: 'primary' },
  {
    label: 'Past scheduled time',
    value: summary.value.overdue,
    icon: 'schedule',
    color: 'negative',
  },
  {
    label: 'Delivered today',
    value: summary.value.delivered_today,
    icon: 'task_alt',
    color: 'teal',
  },
]);
const editable = (d: any) => ['Pending', 'Preparing'].includes(d.status),
  overdue = (d: any) =>
    d.scheduled_at &&
    !['Delivered', 'Cancelled'].includes(d.status) &&
    new Date(d.scheduled_at).getTime() < Date.now(),
  statusColor = (s: string) =>
    s === 'Delivered'
      ? 'teal'
      : s === 'Cancelled'
        ? 'grey-7'
        : s === 'Out for Delivery'
          ? 'orange-9'
          : 'primary';
const orderLabel = (o: any) =>
  o ? `${o.receipt_number || `Legacy #${o.id}`} · ${o.customer_name}` : '';
const columns = [
  { name: 'recipient_name', label: 'Recipient', field: 'recipient_name', align: 'left' as const },
  {
    name: 'receipt',
    label: 'Sale',
    field: (r: any) => r.order?.receipt_number || `Sale #${r.order_id}`,
    align: 'left' as const,
  },
  { name: 'scheduled_at', label: 'Scheduled', field: 'scheduled_at', align: 'left' as const },
  {
    name: 'rider_name',
    label: 'Rider',
    field: (r: any) => r.rider_name || 'Unassigned',
    align: 'left' as const,
  },
  { name: 'status', label: 'Status', field: 'status', align: 'left' as const },
  { name: 'actions', label: 'Actions', field: 'id', align: 'right' as const },
];
let listRequest = 0,
  detailsRequest = 0,
  orderSearch = 0;
async function load() {
  const request = ++listRequest;
  loading.value = true;
  error.value = '';
  try {
    const { data } = await api.get('/deliveries', {
      params: {
        page: page.value,
        status: status.value,
        schedule: schedule.value,
        search: search.value,
      },
    });
    if (request !== listRequest) return;
    rows.value = data.data;
    last.value = data.last_page;
    total.value = data.total;
    summary.value = data.summary;
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
async function findOrders(v: string, update: (f: () => void) => void) {
  const request = ++orderSearch;
  try {
    const { data } = await api.get('/orders', { params: { search: v, status: 'Completed' } });
    if (request === orderSearch)
      update(() => {
        orders.value = data.data.filter(
          (o: any) => !o.delivery || o.delivery.status === 'Cancelled',
        );
      });
  } catch (e) {
    update(() => {
      orders.value = [];
    });
    q.notify({ type: 'negative', message: errorMessage(e) });
  }
}
function choose(o: any) {
  form.value.recipient_name = o?.customer_name || '';
  form.value.phone = o?.customer?.phone || '';
  form.value.address = o?.customer?.address || '';
}
function open(row?: any) {
  editing.value = row || null;
  order.value = null;
  formError.value = '';
  form.value = row
    ? {
        recipient_name: row.recipient_name,
        phone: row.phone || '',
        address: row.address,
        scheduled_at: localDateTimeInput(row.scheduled_at),
        notes: row.notes || '',
        rider_name: row.rider_name || '',
      }
    : blank();
  dialog.value = true;
}
async function save() {
  if (saving.value || (!editing.value && !order.value)) return;
  saving.value = true;
  formError.value = '';
  try {
    const payload = { ...form.value, scheduled_at: form.value.scheduled_at || null };
    if (editing.value) await api.patch(`/deliveries/${editing.value.id}`, payload);
    else await api.post('/deliveries', { ...payload, order_id: order.value.id });
    dialog.value = false;
    page.value = 1;
    await load();
    if (detailsOpen.value) await loadDetail();
    q.notify({
      type: 'positive',
      message: editing.value ? 'Delivery updated.' : 'Delivery scheduled.',
    });
  } catch (e) {
    formError.value = errorMessage(e);
  } finally {
    saving.value = false;
  }
}
function show(id: number) {
  detailId.value = id;
  detail.value = null;
  detailsOpen.value = true;
  loadDetail();
}
async function loadDetail() {
  const request = ++detailsRequest;
  detailLoading.value = true;
  detailError.value = '';
  try {
    const { data } = await api.get(`/deliveries/${detailId.value}`);
    if (request === detailsRequest) detail.value = data;
  } catch (e) {
    if (request === detailsRequest) detailError.value = errorMessage(e);
  } finally {
    if (request === detailsRequest) detailLoading.value = false;
  }
}
async function commit(row: any, s: string, reason?: string) {
  updating.value = row.id;
  try {
    await api.patch(`/deliveries/${row.id}`, { status: s, reason });
    await load();
    if (detailsOpen.value) await loadDetail();
    q.notify({ type: 'positive', message: 'Delivery ' + s.toLowerCase() + '.' });
  } catch (e) {
    q.notify({ type: 'negative', message: errorMessage(e) });
    await load();
    if (detailsOpen.value) await loadDetail();
  } finally {
    updating.value = null;
  }
}
function transition(row: any, s: string) {
  q.dialog({
    title: `Mark ${s}?`,
    message: `Update delivery for ${row.recipient_name}?`,
    cancel: true,
  }).onOk(() => commit(row, s));
}
function cancel(row: any) {
  q.dialog({
    title: 'Cancel delivery?',
    message: 'Record a reason. Cancelling delivery does not void or refund the sale.',
    prompt: {
      model: '',
      type: 'text',
      isValid: (v: string) => v.trim().length >= 5 && v.length <= 500,
    },
    cancel: true,
    persistent: true,
  }).onOk((reason: string) => commit(row, 'Cancelled', reason));
}
async function viewReceipt(id: number) {
  try {
    receipt.value = (await api.get(`/orders/${id}`)).data;
  } catch (e) {
    q.notify({ type: 'negative', message: errorMessage(e) });
  }
}
onMounted(load);
watch(
  () => route.query.order,
  async (value) => {
    if (!value) return;
    const id = Number(value);
    try {
      const { data } = await api.get(`/orders/${id}`);
      if (Number(route.query.order) !== id) return;
      if (data.status !== 'Completed' || (data.delivery && data.delivery.status !== 'Cancelled'))
        throw new Error('This sale cannot be scheduled for delivery.');
      open();
      orders.value = [data];
      order.value = data;
      choose(data);
    } catch (e) {
      q.notify({ type: 'negative', message: errorMessage(e) });
    }
    if (Number(route.query.order) === id) {
      const query = { ...route.query };
      delete query.order;
      router.replace({ path: '/deliveries', query });
    }
  },
  { immediate: true },
);
</script>
<style scoped>
.delivery-progress {
  list-style: none;
  padding: 0;
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 10px;
  margin: 25px 0;
}
.delivery-progress li {
  color: #8495a4;
  display: flex;
  flex-direction: column;
  gap: 8px;
  font-size: 11px;
  text-align: center;
  align-items: center;
}
.delivery-progress li.done {
  color: #116eaa;
  font-weight: 700;
}
.delivery-progress .q-icon {
  font-size: 25px;
}
.delivery-fields {
  display: grid;
  grid-template-columns: 120px minmax(0, 1fr);
  gap: 14px;
  margin: 20px 0;
}
.delivery-fields dt {
  color: #71869a;
  font-size: 13px;
}
.delivery-fields dd {
  margin: 0;
  overflow-wrap: anywhere;
}
.delivery-notes {
  white-space: pre-wrap;
}
@media (max-width: 500px) {
  .delivery-fields {
    grid-template-columns: 1fr;
    gap: 5px;
  }
  .delivery-fields dd {
    margin-bottom: 10px;
  }
  .delivery-progress {
    gap: 6px;
  }
  .delivery-progress li {
    font-size: 10px;
  }
}
</style>
