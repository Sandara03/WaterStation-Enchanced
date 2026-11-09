<template>
  <q-page class="q-pa-lg">
    <section class="overview-hero q-pa-lg q-mb-lg">
      <div class="row justify-between items-center q-col-gutter-md">
        <div class="col-12 col-md">
          <q-badge color="white" text-color="primary" label="AHY WATER STATION" />
          <h1 class="text-h4 text-weight-bold q-mb-sm">A clear view of your station.</h1>
          <p class="q-mb-none">Welcome, {{ staff?.name }}. {{ todayLabel }}</p>
        </div>
        <div class="col-auto">
          <q-btn
            unelevated
            color="white"
            text-color="primary"
            icon="add_shopping_cart"
            label="New sale"
            to="/pos"
          />
        </div>
      </div>
    </section>
    <div class="quick-actions q-mb-lg">
      <q-btn outline color="primary" icon="person_add" label="Customers" to="/customers" />
      <q-btn outline color="primary" icon="local_shipping" label="Deliveries" to="/deliveries" />
      <q-btn outline color="primary" icon="inventory_2" label="Inventory" to="/inventory" />
      <q-btn v-if="isAdmin" outline color="primary" icon="insights" label="Reports" to="/reports" />
      <q-space /><q-btn
        flat
        color="primary"
        icon="refresh"
        label="Refresh"
        :loading="loading"
        @click="load"
      />
    </div>
    <q-banner v-if="error" rounded class="bg-red-1 text-negative q-mb-md" role="alert"
      >{{ error }}<template #action><q-btn flat label="Retry" @click="load" /></template
    ></q-banner>
    <q-linear-progress v-if="loading" indeterminate class="q-mb-md" />
    <div class="metric-grid q-mb-lg">
      <q-card v-for="s in cards" :key="s.label" flat bordered class="metric-card q-pa-lg">
        <div class="row justify-between items-center">
          <span class="text-grey-7">{{ s.label }}</span
          ><q-avatar
            :color="s.color === 'primary' ? 'blue-1' : s.color + '-1'"
            :text-color="s.color"
            :icon="s.icon"
            size="42px"
          />
        </div>
        <div class="text-h4 text-weight-bold q-mt-md">{{ loading && !loaded ? '—' : s.value }}</div>
        <div class="text-caption text-grey-7 q-mt-sm">{{ s.caption }}</div>
      </q-card>
    </div>
    <div class="row q-col-gutter-lg q-mb-lg">
      <div class="col-12 col-md-8">
        <q-card flat bordered class="full-height"
          ><q-card-section class="row justify-between items-start"
            ><div>
              <h2 class="section-heading">Sales this week</h2>
              <p class="text-caption text-grey-7 q-mt-xs">
                Completed sales · last 7 days · Asia/Manila
              </p>
            </div>
            <q-badge color="blue-1" text-color="primary"
              >{{ stats.week_orders }} orders</q-badge
            ></q-card-section
          >
          <q-card-section class="q-pt-none"
            ><div class="row items-baseline q-gutter-sm">
              <strong class="text-h4 text-primary">{{ money(stats.week_sales) }}</strong
              ><span class="text-caption text-grey-7">{{ comparison }}</span>
            </div>
            <div
              class="sales-bars"
              role="img"
              :aria-label="
                'Daily sales: ' +
                stats.daily_sales.map((d) => d.date + ' ' + money(d.sales)).join('; ')
              "
            >
              <div v-for="day in stats.daily_sales" :key="day.date" class="sales-day">
                <span class="bar-value">{{ money(day.sales) }}</span>
                <div class="bar-track">
                  <div
                    class="bar-fill"
                    :style="{
                      height: Math.max(day.sales > 0 ? 3 : 0, (day.sales / maxSales) * 100) + '%',
                    }"
                  />
                </div>
                <span class="text-caption text-grey-7">{{ dayLabel(day.date) }}</span
                ><q-tooltip
                  >{{ day.date }} · {{ day.orders }} orders · {{ money(day.sales) }}</q-tooltip
                >
              </div>
            </div>
            <p v-if="loaded && !stats.week_orders" class="text-caption text-grey-7 q-mt-md">
              No completed sales this week yet. Make a new sale to get started.
            </p>
          </q-card-section></q-card
        >
      </div>
      <div class="col-12 col-md-4">
        <q-card flat bordered class="full-height q-pa-lg"
          ><h2 class="section-heading">Delivery desk</h2>
          <div class="text-h3 text-primary q-my-md">{{ stats.pending_deliveries }}</div>
          <p class="text-grey-7">Deliveries in progress</p>
          <q-separator class="q-my-md" />
          <div class="row justify-between q-mb-sm">
            <span>Scheduled today</span><strong>{{ stats.today_deliveries }}</strong>
          </div>
          <div class="row justify-between">
            <span>Past scheduled time</span
            ><strong :class="stats.overdue_deliveries ? 'text-negative' : ''">{{
              stats.overdue_deliveries
            }}</strong>
          </div>
          <q-btn
            unelevated
            color="primary"
            class="full-width q-mt-lg"
            icon="local_shipping"
            label="Manage deliveries"
            to="/deliveries"
        /></q-card>
      </div>
    </div>
    <div class="row q-col-gutter-lg">
      <div class="col-12 col-md-7">
        <q-card flat bordered
          ><q-card-section class="row items-center justify-between"
            ><h2 class="section-heading">Recent transactions</h2>
            <q-btn flat color="primary" label="View all" to="/sales"
          /></q-card-section>
          <q-list separator
            ><q-item
              v-for="o in stats.recent_orders"
              :key="o.id"
              clickable
              @click="openReceipt(o.id)"
              ><q-item-section avatar
                ><q-avatar
                  color="blue-1"
                  text-color="primary"
                  icon="receipt_long" /></q-item-section
              ><q-item-section
                ><q-item-label>{{ o.customer_name }}</q-item-label
                ><q-item-label caption class="receipt-reference"
                  >{{ o.receipt_number || `Legacy #${o.id}` }} ·
                  {{ dateTime(o.created_at) }}</q-item-label
                ></q-item-section
              ><q-item-section side
                ><div class="text-weight-bold">{{ money(o.total_amount) }}</div>
                <q-badge :color="o.status === 'Completed' ? 'teal' : 'negative'">{{
                  o.status
                }}</q-badge></q-item-section
              ></q-item
            ></q-list
          ><q-card-section v-if="loaded && !stats.recent_orders.length" class="text-grey-7"
            >Your first sale will appear here.</q-card-section
          ></q-card
        >
        <q-card flat bordered class="q-mt-lg"
          ><q-card-section class="row items-center justify-between"
            ><h2 class="section-heading">Upcoming deliveries</h2>
            <q-btn flat color="primary" label="View all" to="/deliveries" /></q-card-section
          ><q-list separator
            ><q-item v-for="d in stats.upcoming_deliveries" :key="d.id" to="/deliveries"
              ><q-item-section avatar
                ><q-icon name="local_shipping" color="primary" /></q-item-section
              ><q-item-section
                ><q-item-label>{{ d.recipient_name }}</q-item-label
                ><q-item-label caption
                  >{{ dateTime(d.scheduled_at) }} ·
                  {{ d.rider_name || 'Rider unassigned' }}</q-item-label
                ></q-item-section
              ><q-item-section side
                ><q-badge color="blue-1" text-color="primary">{{
                  d.status
                }}</q-badge></q-item-section
              ></q-item
            ></q-list
          ><q-card-section v-if="loaded && !stats.upcoming_deliveries.length" class="text-grey-7"
            >No deliveries waiting.</q-card-section
          ></q-card
        >
      </div>
      <div class="col-12 col-md-5">
        <q-card flat bordered
          ><q-card-section class="row justify-between items-center"
            ><h2 class="section-heading">Stock alerts</h2>
            <q-btn flat label="Inventory" color="primary" to="/inventory" /></q-card-section
          ><q-list separator
            ><q-item v-for="p in stats.stock_alerts" :key="p.id" to="/inventory"
              ><q-item-section>{{ p.name }}</q-item-section
              ><q-item-section side
                ><q-badge :color="p.stock === 0 ? 'negative' : 'orange-9'"
                  >{{ p.stock }} left</q-badge
                ></q-item-section
              ></q-item
            ></q-list
          ><q-card-section v-if="loaded && !stats.stock_alerts.length" class="text-grey-7"
            >Stock levels look good.</q-card-section
          ></q-card
        ><q-card flat bordered class="q-pa-lg q-mt-lg"
          ><h2 class="section-heading">All-time sales</h2>
          <div class="text-h4 text-primary q-mt-md">{{ money(stats.total_sales) }}</div>
          <p class="text-caption text-grey-7 q-mt-sm">
            {{ stats.total_orders }} recorded sales · revenue excludes voided sales.
          </p></q-card
        >
      </div>
    </div>
    <div class="text-caption text-grey-7 q-mt-lg">
      {{ stats.total_products }} active products ·
      {{ updated ? 'Updated ' + dateTime(updated) : 'Loading overview…' }} · Refreshes every minute.
    </div>
    <ReceiptDialog :order="receipt" @close="receipt = null" />
  </q-page>
</template>
<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { useQuasar } from 'quasar';
import api, { errorMessage } from '../services/api';
import { money, dateTime } from '../services/format';
import { staff, isAdmin } from '../services/auth';
import ReceiptDialog from '../components/ReceiptDialog.vue';
type Day = { date: string; sales: number; orders: number };
const q = useQuasar(),
  stats = ref({
    total_products: 0,
    total_orders: 0,
    total_sales: 0,
    today_sales: 0,
    today_orders: 0,
    week_sales: 0,
    week_orders: 0,
    previous_week_sales: 0,
    low_stock: 0,
    out_of_stock: 0,
    pending_deliveries: 0,
    today_deliveries: 0,
    overdue_deliveries: 0,
    daily_sales: [] as Day[],
    recent_orders: [] as any[],
    stock_alerts: [] as any[],
    upcoming_deliveries: [] as any[],
  }),
  loading = ref(false),
  loaded = ref(false),
  error = ref(''),
  updated = ref(''),
  receipt = ref<any>(null);
const todayLabel = new Date().toLocaleDateString('en-PH', {
  timeZone: 'Asia/Manila',
  weekday: 'long',
  month: 'long',
  day: 'numeric',
  year: 'numeric',
});
const cards = computed(() => [
  {
    label: "Today's sales",
    value: money(stats.value.today_sales),
    caption: `${stats.value.today_orders} completed orders today`,
    icon: 'payments',
    color: 'primary',
  },
  {
    label: 'Orders this week',
    value: stats.value.week_orders,
    caption: 'Completed sales in the last 7 days',
    icon: 'receipt_long',
    color: 'teal',
  },
  {
    label: 'Low stock',
    value: stats.value.low_stock,
    caption: 'Products at the reorder level',
    icon: 'inventory_2',
    color: 'orange',
  },
  {
    label: 'Out of stock',
    value: stats.value.out_of_stock,
    caption: 'Active products needing a refill',
    icon: 'warning_amber',
    color: 'red',
  },
]);
const maxSales = computed(() =>
  Math.max(1, ...stats.value.daily_sales.map((x) => Number(x.sales))),
);
const comparison = computed(() =>
  stats.value.previous_week_sales
    ? `${((stats.value.week_sales / stats.value.previous_week_sales - 1) * 100).toFixed(1)}% vs. previous 7 days`
    : 'No sales in the previous 7 days',
);
const dayLabel = (d: string) =>
  new Date(d + 'T12:00:00+08:00').toLocaleDateString('en-PH', {
    weekday: 'short',
    timeZone: 'Asia/Manila',
  });
async function load() {
  if (loading.value) return;
  loading.value = true;
  error.value = '';
  try {
    stats.value = (await api.get('/dashboard')).data;
    loaded.value = true;
    updated.value = new Date().toISOString();
  } catch (e) {
    error.value = errorMessage(e);
  } finally {
    loading.value = false;
  }
}
async function openReceipt(id: number) {
  try {
    receipt.value = (await api.get(`/orders/${id}`)).data;
  } catch (e) {
    q.notify({ type: 'negative', message: errorMessage(e) });
  }
}
let timer: ReturnType<typeof setInterval>;
onMounted(() => {
  load();
  timer = setInterval(() => {
    if (!document.hidden) load();
  }, 60000);
});
onUnmounted(() => clearInterval(timer));
</script>
<style scoped>
.overview-hero {
  border-radius: 20px;
  background: linear-gradient(120deg, #07516d, #087ca6);
  color: white;
}
.sales-bars {
  display: grid;
  grid-template-columns: repeat(7, minmax(0, 1fr));
  gap: 12px;
  margin-top: 24px;
}
.sales-day {
  min-width: 0;
  text-align: center;
}
.bar-value {
  font-size: 10px;
  color: #607d93;
  display: block;
  overflow: hidden;
  text-overflow: ellipsis;
}
.bar-track {
  height: 130px;
  background: #f0f8fd;
  border-radius: 7px;
  display: flex;
  align-items: flex-end;
  margin: 9px 0;
}
.bar-fill {
  width: 100%;
  background: linear-gradient(#28a9de, #116eaa);
  border-radius: 7px;
  min-height: 2px;
}
.receipt-reference {
  overflow-wrap: anywhere;
}
@media (max-width: 600px) {
  .sales-bars {
    gap: 6px;
  }
  .bar-value {
    font-size: 9px;
  }
  .overview-hero h1 {
    font-size: 27px;
  }
  .bar-track {
    height: 105px;
  }
}
</style>
