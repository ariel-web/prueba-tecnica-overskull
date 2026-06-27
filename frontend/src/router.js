import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from './stores/auth'

const routes = [
  { path: '/', redirect: '/dashboard' },
  { path: '/login', name: 'login', component: () => import('./views/Login.vue'), meta: { public: true } },
  { path: '/dashboard', name: 'dashboard', component: () => import('./views/Dashboard.vue') },
  { path: '/products', name: 'products', component: () => import('./views/Products.vue') },
  { path: '/products/new', name: 'product-create', component: () => import('./views/ProductForm.vue') },
  { path: '/products/:id/edit', name: 'product-edit', component: () => import('./views/ProductForm.vue') },
  { path: '/products/:id/stock', name: 'product-stock', component: () => import('./views/StockMovements.vue') },
  { path: '/categories', name: 'categories', component: () => import('./views/Categories.vue') },
  { path: '/:pathMatch(.*)*', redirect: '/dashboard' },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()

  if (auth.token && !auth.user) {
    await auth.fetchUser()
  }

  if (!to.meta.public && !auth.token) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  if (to.name === 'login' && auth.token) {
    return { name: 'dashboard' }
  }
})

export default router
