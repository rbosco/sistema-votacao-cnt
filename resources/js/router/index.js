import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const routes = [
  {
    path: '/',
    redirect: '/vote',
  },
  {
    path: '/vote',
    name: 'Vote',
    component: () => import('@/views/Vote.vue'),
    meta: { public: true },
  },
  {
    path: '/login',
    name: 'Login',
    component: () => import('@/views/Login.vue'),
    meta: { public: true },
  },
  {
    path: '/admin',
    name: 'Admin',
    component: () => import('@/views/admin/Dashboard.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/admin/users',
    name: 'Users',
    component: () => import('@/views/admin/Users.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/admin/proposals',
    name: 'Proposals',
    component: () => import('@/views/admin/Proposals.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/admin/votes',
    name: 'Votes',
    component: () => import('@/views/admin/Votes.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/results/:encryptedId',
    name: 'Results',
    component: () => import('@/views/Results.vue'),
    meta: { public: true },
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

router.beforeEach((to, from, next) => {
  const authStore = useAuthStore();
  const requiresAuth = to.matched.some(record => record.meta.requiresAuth);
  const isPublic = to.matched.some(record => record.meta.public);

  if (requiresAuth && !authStore.isAuthenticated) {
    next('/login');
  } else if (to.path === '/login' && authStore.isAuthenticated) {
    next('/admin');
  } else {
    next();
  }
});

export default router;
