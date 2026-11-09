<template>
  <q-page class="products-page q-pa-md q-pa-lg-lg">
    <!-- Page header -->
    <div class="row items-center justify-between q-col-gutter-md q-mb-lg">
      <div class="col-12 col-sm">
        <div class="row items-center q-gutter-sm">
          <q-avatar color="blue-1" text-color="primary" size="46px">
            <q-icon name="water_drop" size="28px" />
          </q-avatar>

          <div>
            <div class="text-h4 text-weight-bold text-grey-9">Products</div>
            <div class="text-body2 text-grey-7">Manage water products, prices, and stock.</div>
          </div>
        </div>
      </div>

      <div class="col-12 col-sm-auto">
        <q-btn
          color="primary"
          icon="add"
          label="Add Product"
          no-caps
          unelevated
          class="full-width add-button"
          @click="openAdd"
        />
      </div>
    </div>

    <!-- Error banner -->
    <q-banner v-if="errorMessage" rounded class="bg-red-1 text-negative q-mb-lg">
      <template #avatar>
        <q-icon name="error_outline" size="28px" />
      </template>

      <div class="text-weight-bold">Unable to load products</div>
      <div>{{ errorMessage }}</div>

      <template #action>
        <q-btn
          flat
          no-caps
          color="negative"
          label="Retry"
          :loading="loading"
          @click="loadProducts"
        />
      </template>
    </q-banner>

    <!-- Summary cards -->
    <div class="row q-col-gutter-md q-mb-lg">
      <div v-for="summary in summaries" :key="summary.label" class="col-12 col-sm-4">
        <q-card flat bordered class="summary-card">
          <q-card-section class="row items-center no-wrap">
            <q-avatar
              :color="summary.background"
              :text-color="summary.color"
              :icon="summary.icon"
              size="50px"
            />

            <div class="q-ml-md">
              <div class="text-caption text-grey-7">
                {{ summary.label }}
              </div>
              <div class="text-h5 text-weight-bold text-grey-9">
                {{ summary.value }}
              </div>
              <div class="text-caption text-grey-6">
                {{ summary.description }}
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <!-- Products table -->
    <q-card flat bordered class="inventory-card">
      <q-card-section>
        <div class="row items-center justify-between q-col-gutter-md">
          <div class="col-12 col-md">
            <div class="text-h6 text-weight-bold text-grey-9">Product Inventory</div>
            <div class="text-caption text-grey-7 q-mt-xs">
              {{ products.length }} product{{ products.length === 1 ? '' : 's' }}
              registered
            </div>
          </div>

          <div class="col-12 col-md-4">
            <q-input
              v-model="filter"
              outlined
              dense
              clearable
              debounce="200"
              placeholder="Search products..."
              aria-label="Search products"
            >
              <template #prepend>
                <q-icon name="search" color="primary" />
              </template>
            </q-input>
          </div>
        </div>
      </q-card-section>

      <q-separator />

      <q-table
        :rows="products"
        :columns="columns"
        row-key="id"
        :filter="filter"
        :loading="loading"
        v-model:pagination="pagination"
        flat
        separator="horizontal"
        :rows-per-page-options="[5, 10, 20, 50]"
        no-data-label="No products available"
        no-results-label="No matching products found"
      >
        <template #loading>
          <q-inner-loading showing color="primary">
            <q-spinner-dots size="42px" />
          </q-inner-loading>
        </template>

        <!-- Product name -->
        <template #body-cell-name="props">
          <q-td :props="props">
            <div class="row items-center no-wrap">
              <q-avatar color="blue-1" text-color="primary" size="42px">
                <q-icon name="water_drop" size="24px" />
              </q-avatar>

              <div class="q-ml-sm">
                <div class="text-weight-bold text-grey-9">
                  {{ props.row.name }}
                </div>
                <div
                  v-if="props.row.description"
                  class="text-caption text-grey-7 product-description"
                >
                  {{ props.row.description }}
                </div>
                <div class="text-caption text-grey-5">Product #{{ props.row.id }}</div>
              </div>
            </div>
          </q-td>
        </template>

        <!-- Price -->
        <template #body-cell-price="props">
          <q-td :props="props">
            <span class="text-weight-bold text-primary">
              {{ currency(props.row.price) }}
            </span>
          </q-td>
        </template>

        <!-- Stock -->
        <template #body-cell-stock="props">
          <q-td :props="props">
            <div class="text-weight-medium">{{ numberFormat(props.row.stock) }} units</div>
            <div class="text-caption text-grey-6">Alert level: {{ props.row.low_stock }}</div>
          </q-td>
        </template>

        <!-- Status -->
        <template #body-cell-status="props">
          <q-td :props="props">
            <q-badge
              :color="stockStatus(props.row).color"
              :text-color="stockStatus(props.row).textColor"
              rounded
              class="status-badge"
            >
              <q-icon :name="stockStatus(props.row).icon" size="14px" class="q-mr-xs" />
              {{ stockStatus(props.row).label }}
            </q-badge>
          </q-td>
        </template>

        <!-- Actions -->
        <template #body-cell-actions="props">
          <q-td :props="props" class="text-center">
            <q-btn
              flat
              round
              dense
              :color="props.row.is_active ? 'orange-9' : 'positive'"
              :icon="props.row.is_active ? 'pause_circle_outline' : 'play_circle_outline'"
              :aria-label="props.row.is_active ? 'Deactivate product' : 'Reactivate product'"
              :disable="saving || deletingId !== null || togglingId === props.row.id"
              :loading="togglingId === props.row.id"
              @click="toggleProductStatus(props.row)"
            >
              <q-tooltip>{{
                props.row.is_active ? 'Deactivate product' : 'Reactivate product'
              }}</q-tooltip>
            </q-btn>

            <q-btn
              flat
              round
              dense
              color="primary"
              icon="edit"
              aria-label="Edit product"
              :disable="saving || deletingId === props.row.id"
              @click="openEdit(props.row)"
            >
              <q-tooltip>Edit product</q-tooltip>
            </q-btn>

            <q-btn
              flat
              round
              dense
              color="negative"
              icon="delete_outline"
              aria-label="Delete product"
              :loading="deletingId === props.row.id"
              :disable="saving || deletingId !== null"
              @click="confirmDelete(props.row)"
            >
              <q-tooltip>Delete product</q-tooltip>
            </q-btn>
          </q-td>
        </template>

        <!-- Empty state -->
        <template #no-data="{ filter: activeFilter }">
          <div class="full-width column items-center q-pa-xl text-grey-7">
            <q-icon
              :name="activeFilter ? 'search_off' : 'inventory_2'"
              size="54px"
              color="grey-5"
            />

            <div class="text-subtitle1 text-weight-bold q-mt-sm">
              {{ activeFilter ? 'No matching products' : 'No products yet' }}
            </div>

            <div class="text-caption q-mt-xs">
              {{
                activeFilter
                  ? 'Try another product name.'
                  : 'Add your first water product to get started.'
              }}
            </div>

            <q-btn
              v-if="!activeFilter"
              unelevated
              no-caps
              color="primary"
              icon="add"
              label="Add First Product"
              class="q-mt-md"
              @click="openAdd"
            />
          </div>
        </template>
      </q-table>
    </q-card>

    <!-- Add / Edit dialog -->
    <q-dialog v-model="dialog" persistent>
      <q-card class="product-dialog">
        <q-form ref="formRef" @submit.prevent="saveProduct">
          <q-card-section class="row items-center no-wrap">
            <q-avatar color="blue-1" text-color="primary" size="48px">
              <q-icon :name="editing ? 'edit' : 'add_box'" size="26px" />
            </q-avatar>

            <div class="q-ml-md col">
              <div class="text-h6 text-weight-bold">
                {{ editing ? 'Edit Product' : 'Add Product' }}
              </div>
              <div class="text-caption text-grey-7">Complete the product details below.</div>
            </div>

            <q-btn
              flat
              round
              dense
              icon="close"
              aria-label="Close dialog"
              :disable="saving"
              @click="closeDialog"
            />
          </q-card-section>

          <q-separator />

          <q-card-section class="q-gutter-md">
            <q-input
              v-model="form.name"
              label="Product Name *"
              placeholder="e.g. Purified Water - 5 Gallons"
              outlined
              maxlength="255"
              counter
              lazy-rules
              :rules="[validateProductName]"
            >
              <template #prepend>
                <q-icon name="water_drop" color="primary" />
              </template>
            </q-input>

            <q-input
              v-model="form.description"
              label="Description"
              placeholder="Optional product details"
              type="textarea"
              autogrow
              outlined
              maxlength="1000"
            />

            <q-input
              v-model.number="form.price"
              label="Price (PHP) *"
              type="number"
              outlined
              min="0"
              step="0.01"
              lazy-rules
              :rules="[validatePrice]"
            >
              <template #prepend>
                <q-icon name="payments" color="primary" />
              </template>
            </q-input>

            <q-input
              v-model.number="form.stock"
              :disable="editing"
              :hint="
                editing
                  ? 'Use Inventory to adjust stock with a recorded reason.'
                  : 'Opening stock count'
              "
              label="Available Stock *"
              type="number"
              outlined
              min="0"
              step="1"
              lazy-rules
              :rules="[validateStock]"
            >
              <template #prepend>
                <q-icon name="inventory_2" color="primary" />
              </template>
            </q-input>

            <q-input
              v-model.number="form.low_stock"
              label="Low Stock Alert Level *"
              type="number"
              outlined
              min="0"
              step="1"
              hint="Alert when stock reaches this level or lower."
              lazy-rules
              :rules="[validateLowStock]"
            >
              <template #prepend>
                <q-icon name="notifications_active" color="orange" />
              </template>
            </q-input>
          </q-card-section>

          <q-separator />

          <q-card-actions align="right" class="q-pa-md">
            <q-btn flat no-caps label="Cancel" :disable="saving" @click="closeDialog" />

            <q-btn
              unelevated
              no-caps
              color="primary"
              :icon="editing ? 'save' : 'add'"
              :label="editing ? 'Save Changes' : 'Add Product'"
              type="submit"
              :loading="saving"
            />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue';
import { useQuasar } from 'quasar';
import api from '../services/api';

interface Product {
  id: number;
  name: string;
  description: string | null;
  price: number | string;
  stock: number;
  low_stock: number;
  is_active: boolean;
}

interface ProductForm {
  name: string;
  description: string;
  price: number;
  stock: number;
  low_stock: number;
}

interface TableColumn {
  name: string;
  label: string;
  field: string | ((row: Product) => string);
  align: 'left' | 'right' | 'center';
  sortable?: boolean;
}

const $q = useQuasar();

const products = ref<Product[]>([]);
const loading = ref(false);
const saving = ref(false);
const deletingId = ref<number | null>(null);
const togglingId = ref<number | null>(null);
const dialog = ref(false);
const editing = ref(false);
const filter = ref('');
const productId = ref<number | null>(null);
const errorMessage = ref('');
const formRef = ref<{ validate: () => Promise<boolean> } | null>(null);

const pagination = ref({
  sortBy: 'name',
  descending: false,
  page: 1,
  rowsPerPage: 10,
});

function blankForm(): ProductForm {
  return {
    name: '',
    description: '',
    price: 0,
    stock: 0,
    low_stock: 5,
  };
}

const form = reactive<ProductForm>(blankForm());

type InputValue = string | number | null | undefined;

const validateProductName = (value: InputValue): true | string =>
  String(value ?? '').trim().length > 0 || 'Product name is required.';

const validatePrice = (value: InputValue): true | string => {
  if (value === null || value === undefined || value === '' || !Number.isFinite(Number(value))) {
    return 'Enter a valid price.';
  }
  return Number(value) >= 0 || 'Price cannot be negative.';
};

const validateStock = (value: InputValue): true | string => {
  if (value === null || value === undefined || value === '' || !Number.isInteger(Number(value))) {
    return 'Stock must be a whole number.';
  }
  return Number(value) >= 0 || 'Stock cannot be negative.';
};

const validateLowStock = (value: InputValue): true | string => {
  if (value === null || value === undefined || value === '' || !Number.isInteger(Number(value))) {
    return 'Alert level must be a whole number.';
  }
  return Number(value) >= 0 || 'Alert level cannot be negative.';
};

const columns: TableColumn[] = [
  {
    name: 'name',
    label: 'PRODUCT',
    field: 'name',
    align: 'left',
    sortable: true,
  },
  {
    name: 'price',
    label: 'PRICE',
    field: 'price',
    align: 'right',
    sortable: true,
  },
  {
    name: 'stock',
    label: 'STOCK',
    field: 'stock',
    align: 'right',
    sortable: true,
  },
  {
    name: 'status',
    label: 'STATUS',
    field: (row: Product) => stockStatus(row).label,
    align: 'center',
    sortable: true,
  },
  {
    name: 'actions',
    label: 'ACTIONS',
    field: 'id',
    align: 'center',
  },
];

const lowStockCount = computed(
  () =>
    products.value.filter((product) => {
      const stock = Number(product.stock);
      const threshold = Number(product.low_stock);
      return stock > 0 && stock <= threshold;
    }).length,
);

const outOfStockCount = computed(
  () => products.value.filter((product) => Number(product.stock) <= 0).length,
);

const summaries = computed(() => [
  {
    label: 'Total Products',
    value: numberFormat(products.value.length),
    description: 'Registered products',
    icon: 'inventory_2',
    color: 'primary',
    background: 'blue-1',
  },
  {
    label: 'Low Stock',
    value: numberFormat(lowStockCount.value),
    description: 'Products needing attention',
    icon: 'warning',
    color: 'orange-10',
    background: 'orange-1',
  },
  {
    label: 'Out of Stock',
    value: numberFormat(outOfStockCount.value),
    description: 'Products with zero stock',
    icon: 'remove_shopping_cart',
    color: 'negative',
    background: 'red-1',
  },
]);

function numberFormat(value: number): string {
  return new Intl.NumberFormat('en-PH').format(value);
}

function currency(value: number | string): string {
  const amount = Number(value);

  if (!Number.isFinite(amount)) return '₱0.00';

  return amount.toLocaleString('en-PH', {
    style: 'currency',
    currency: 'PHP',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
}

function stockStatus(product: Product) {
  const stock = Number(product.stock);
  const threshold = Number(product.low_stock);

  if (!product.is_active) {
    return {
      label: 'Inactive',
      color: 'grey-3',
      textColor: 'grey-8',
      icon: 'pause_circle',
    };
  }

  if (stock <= 0) {
    return {
      label: 'Out of Stock',
      color: 'red-1',
      textColor: 'negative',
      icon: 'remove_shopping_cart',
    };
  }

  if (stock <= threshold) {
    return {
      label: 'Low Stock',
      color: 'orange-1',
      textColor: 'orange-10',
      icon: 'warning',
    };
  }

  return {
    label: 'In Stock',
    color: 'green-1',
    textColor: 'green-9',
    icon: 'check_circle',
  };
}

function parseProducts(data: unknown): Product[] {
  let rows: unknown = data;

  if (data && typeof data === 'object' && !Array.isArray(data)) {
    const response = data as Record<string, unknown>;

    if (Array.isArray(response.data)) {
      rows = response.data;
    }
  }

  if (!Array.isArray(rows)) {
    throw new Error('The products API returned an unexpected response.');
  }

  return rows.map((item: unknown) => {
    if (!item || typeof item !== 'object') {
      throw new Error('The products API returned invalid product data.');
    }

    const row = item as Record<string, unknown>;
    const price = Number(row.price);
    const stock = Number(row.stock);
    const threshold = Number(row.low_stock ?? 5);
    const id = Number(row.id);

    if (
      !Number.isFinite(id) ||
      !Number.isFinite(price) ||
      !Number.isFinite(stock) ||
      !Number.isFinite(threshold)
    ) {
      throw new Error('A product has missing or invalid numeric fields.');
    }

    return {
      id,
      name: String(row.name ?? ''),
      description:
        row.description === null || row.description === undefined ? null : String(row.description),
      price,
      stock,
      low_stock: threshold,
      is_active: !(row.is_active === false || row.is_active === 0 || row.is_active === '0'),
    };
  });
}

async function loadProducts(): Promise<void> {
  if (loading.value) return;

  loading.value = true;
  errorMessage.value = '';

  try {
    const response = await api.get('/products');
    products.value = parseProducts(response.data);
  } catch (error: unknown) {
    console.error('Products API error:', error);

    const err = error as {
      response?: {
        status?: number;
        data?: { message?: string };
      };
      message?: string;
    };

    errorMessage.value =
      err.response?.data?.message || err.message || 'Could not retrieve products from the server.';

    $q.notify({
      type: 'negative',
      message: 'Could not load products.',
      caption: errorMessage.value,
      position: 'top',
      timeout: 5000,
    });
  } finally {
    loading.value = false;
  }
}

function openAdd(): void {
  Object.assign(form, blankForm());
  editing.value = false;
  productId.value = null;
  dialog.value = true;
}

function openEdit(product: Product): void {
  Object.assign(form, {
    name: product.name,
    description: product.description ?? '',
    price: Number(product.price),
    stock: Number(product.stock),
    low_stock: Number(product.low_stock),
  });

  editing.value = true;
  productId.value = product.id;
  dialog.value = true;
}

function closeDialog(): void {
  if (saving.value) return;
  dialog.value = false;
}

async function saveProduct(): Promise<void> {
  const valid = await formRef.value?.validate();

  if (!valid) return;

  const name = form.name.trim();
  const description = form.description.trim();
  const price = Number(form.price);
  const stock = Number(form.stock);
  const lowStock = Number(form.low_stock);

  if (!name) {
    $q.notify({
      type: 'warning',
      message: 'Product name is required.',
    });
    return;
  }

  if (!Number.isFinite(price) || price < 0) {
    $q.notify({
      type: 'warning',
      message: 'Enter a valid non-negative price.',
    });
    return;
  }

  if (!Number.isInteger(stock) || stock < 0) {
    $q.notify({
      type: 'warning',
      message: 'Stock must be a non-negative whole number.',
    });
    return;
  }

  if (!Number.isInteger(lowStock) || lowStock < 0) {
    $q.notify({
      type: 'warning',
      message: 'The alert level must be a non-negative whole number.',
    });
    return;
  }

  const payload: ProductForm = {
    name,
    description,
    price,
    stock,
    low_stock: lowStock,
  };

  saving.value = true;

  try {
    if (editing.value && productId.value !== null) {
      const { stock: _stock, ...details } = payload;
      await api.put(`/products/${productId.value}`, details);
    } else {
      await api.post('/products', payload);
    }

    dialog.value = false;

    $q.notify({
      type: 'positive',
      message: editing.value ? 'Product updated successfully.' : 'Product added successfully.',
      position: 'top',
    });

    await loadProducts();
  } catch (error: unknown) {
    console.error('Save product error:', error);

    const err = error as {
      response?: {
        data?: {
          message?: string;
          errors?: Record<string, string[]>;
        };
      };
      message?: string;
    };

    const validationErrors = err.response?.data?.errors;
    const firstValidationError = validationErrors
      ? Object.values(validationErrors).flat()[0]
      : undefined;

    $q.notify({
      type: 'negative',
      message:
        firstValidationError ||
        err.response?.data?.message ||
        err.message ||
        'Could not save the product.',
      position: 'top',
      timeout: 5000,
    });
  } finally {
    saving.value = false;
  }
}

async function toggleProductStatus(product: Product): Promise<void> {
  if (togglingId.value !== null || deletingId.value !== null || saving.value) return;

  const nextStatus = !product.is_active;
  const action = nextStatus ? 'reactivate' : 'deactivate';

  $q.dialog({
    title: `${nextStatus ? 'Reactivate' : 'Deactivate'} Product?`,
    message: nextStatus
      ? `Make "${product.name}" available for sale again?`
      : `Deactivate "${product.name}"? It will remain in your records but cannot be sold.`,
    cancel: { label: 'Cancel', flat: true, noCaps: true },
    ok: {
      label: nextStatus ? 'Reactivate' : 'Deactivate',
      color: nextStatus ? 'positive' : 'warning',
      unelevated: true,
      noCaps: true,
    },
    persistent: true,
  }).onOk(() => {
    void (async () => {
      togglingId.value = product.id;
      try {
        await api.patch(`/products/${product.id}`, { is_active: nextStatus });
        $q.notify({
          type: 'positive',
          message: `Product ${action}d successfully.`,
          position: 'top',
        });
        await loadProducts();
      } catch (error: unknown) {
        const err = error as {
          response?: { data?: { message?: string } };
          message?: string;
        };
        $q.notify({
          type: 'negative',
          message: err.response?.data?.message || err.message || 'Could not update product status.',
          position: 'top',
          timeout: 5000,
        });
      } finally {
        togglingId.value = null;
      }
    })();
  });
}

function confirmDelete(product: Product): void {
  if (deletingId.value !== null || saving.value) return;

  $q.dialog({
    title: 'Delete Product?',
    message:
      `Are you sure you want to delete "${product.name}"? ` + 'This action cannot be undone.',
    cancel: {
      label: 'Cancel',
      flat: true,
      noCaps: true,
    },
    ok: {
      label: 'Delete Product',
      color: 'negative',
      unelevated: true,
      noCaps: true,
    },
    persistent: true,
  }).onOk(() => {
    void deleteProduct(product);
  });
}

async function deleteProduct(product: Product): Promise<void> {
  deletingId.value = product.id;

  try {
    await api.delete(`/products/${product.id}`);

    $q.notify({
      type: 'positive',
      message: 'Product deleted successfully.',
      position: 'top',
    });

    await loadProducts();
  } catch (error: unknown) {
    console.error('Delete product error:', error);

    const err = error as {
      response?: {
        data?: { message?: string };
      };
      message?: string;
    };

    $q.notify({
      type: 'negative',
      message:
        err.response?.data?.message ||
        err.message ||
        'Could not delete this product. It may be linked to an order.',
      position: 'top',
      timeout: 5000,
    });
  } finally {
    deletingId.value = null;
  }
}

onMounted(() => {
  void loadProducts();
});
</script>

<style scoped>
.products-page {
  min-height: 100%;
  background: #f3f8fc;
}

.summary-card,
.inventory-card {
  background: #ffffff;
  border-color: #e1eaf2;
  border-radius: 16px;
}

.summary-card {
  height: 100%;
  transition:
    box-shadow 0.2s ease,
    transform 0.2s ease;
}

.summary-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 22px rgba(15, 76, 117, 0.08);
}

.inventory-card {
  overflow: hidden;
}

.add-button {
  min-height: 42px;
  border-radius: 9px;
}

.product-dialog {
  width: 500px;
  max-width: 95vw;
  border-radius: 18px;
}

.product-description {
  max-width: 280px;
  overflow-wrap: anywhere;
}

.status-badge {
  padding: 6px 10px;
  font-weight: 600;
}

@media (max-width: 599px) {
  .products-page {
    padding: 12px;
  }

  .product-description {
    max-width: 180px;
  }
}

@media (prefers-reduced-motion: reduce) {
  .summary-card {
    transition: none;
  }
}
</style>
