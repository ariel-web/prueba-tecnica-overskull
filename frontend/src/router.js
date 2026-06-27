import Vue from 'vue'
import Router from 'vue-router'
import Login from './views/Login.vue'
import Dashboard from './views/Dashboard.vue'
import Products from './views/Products.vue'
import ProductForm from './views/ProductForm.vue'
import Categories from './views/Categories.vue'
import StockMovements from './views/StockMovements.vue'

Vue.use(Router)

const router = new Router({
  mode: 'history',
  routes: [
    { path: '/', redirect: '/dashboard' },
    { path: '/login', component: Login },
    { path: '/dashboard', component: Dashboard },
    { path: '/products', component: Products },
    { path: '/products/new', component: ProductForm },
    { path: '/products/:id/edit', component: ProductForm },
    { path: '/products/:id/stock', component: StockMovements },
    { path: '/categories', component: Categories },
  ]
})

router.beforeEach((to, from, next) => {
  // Legacy issue: simplistic route guard.
  if (to.path !== '/login' && !localStorage.getItem('token')) {
    next('/login')
  } else {
    next()
  }
})

export default router
