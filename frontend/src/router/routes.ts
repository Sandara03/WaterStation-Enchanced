import type { RouteRecordRaw } from 'vue-router';
const routes: RouteRecordRaw[] = [
  { path: '/login', component: () => import('pages/LoginPage.vue'), meta: { public: true } },
  {
    path: '/',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      { path: '', component: () => import('pages/IndexPage.vue') },
      {
        path: 'products',
        component: () => import('pages/ProductsPage.vue'),
        meta: { admin: true },
      },
      { path: 'pos', component: () => import('pages/POSPage.vue') },
      { path: 'inventory', component: () => import('pages/InventoryPage.vue') },
      { path: 'sales', component: () => import('pages/SalesPage.vue') },
      { path: 'reports', component: () => import('pages/ReportsPage.vue'), meta: { admin: true } },
      { path: 'customers', component: () => import('pages/CustomersPage.vue') },
      { path: 'deliveries', component: () => import('pages/DeliveriesPage.vue') },
      {
        path: 'expenses',
        component: () => import('pages/ExpensesPage.vue'),
        meta: { admin: true },
      },
      { path: 'audit', component: () => import('pages/AuditPage.vue'), meta: { admin: true } },
      { path: 'account', component: () => import('pages/AccountPage.vue') },
      { path: ':catchAll(.*)*', component: () => import('pages/NotFoundPage.vue') },
    ],
  },
];
export default routes;
