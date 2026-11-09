import { route } from 'quasar/wrappers';
import {
  createRouter,
  createMemoryHistory,
  createWebHistory,
  createWebHashHistory,
} from 'vue-router';

import routes from './routes';
import { restoreSession, isAdmin } from '../services/auth';

export default route(function () {
  const createHistory = process.env.SERVER
    ? createMemoryHistory
    : process.env.VUE_ROUTER_MODE === 'history'
      ? createWebHistory
      : createWebHashHistory;

  const router = createRouter({
    scrollBehavior: () => ({ left: 0, top: 0 }),
    routes,

    history: createHistory(process.env.VUE_ROUTER_BASE || '/'),
  });
  router.beforeEach(async (to) => {
    if (to.meta.public) return true;
    try {
      if (!(await restoreSession())) return { path: '/login' };
      if (to.meta.admin && !isAdmin.value) return { path: '/' };
      return true;
    } catch {
      return { path: '/login', query: { unavailable: '1' } };
    }
  });
  return router;
});
