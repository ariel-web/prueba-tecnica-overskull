import Vue from 'vue'
import Router from 'vue-router'

Vue.use(Router)

const router = new Router({
  mode: 'history',
  routes: [
    { path: '/', redirect: '/dashboard' },
    { path: '/login', name: 'login', component: () => import('./views/Login.vue'), meta: { public: true } },
    { path: '/dashboard', name: 'dashboard', component: () => import('./views/Dashboard.vue') },
    { path: '/products', name: 'products', component: () => import('./views/Products.vue') },
    { path: '/products/new', name: 'product-create', component: () => import('./views/ProductForm.vue') },
    { path: '/products/:id/edit', name: 'product-edit', component: () => import('./views/ProductForm.vue') },
    { path: '/products/:id/stock', name: 'product-stock', component: () => import('./views/StockMovements.vue') },
    { path: '/categories', name: 'categories', component: () => import('./views/Categories.vue') },
    { path: '*', redirect: '/dashboard' },
  ]
})

router.beforeEach((to, from, next) => {
  if (!to.meta.public && !localStorage.getItem('token')) {
    next({ name: 'login' })
  } else {
    next()
  }
})

export default router
