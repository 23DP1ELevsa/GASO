import { createRouter, createWebHistory } from 'vue-router';

import DashboardView from './views/DashboardView.vue';
import LoginView from './views/LoginView.vue';
import { getSession } from './lib/session';

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/login',
      name: 'login',
      component: LoginView,
      meta: { guestOnly: true },
    },
    {
      path: '/',
      name: 'dashboard',
      component: DashboardView,
      meta: { requiresAuth: true },
    },
  ],
});

router.beforeEach((to) => {
  const session = getSession();

  if (to.meta.requiresAuth && !session?.token) {
    return { name: 'login' };
  }

  if (to.meta.guestOnly && session?.token) {
    return { name: 'dashboard' };
  }

  return true;
});

export default router;