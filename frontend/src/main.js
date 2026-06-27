import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import { setUnauthorizedHandler, setNotificationHandler } from './services/api'
import { useAuthStore } from './stores/auth'
import { useToastStore } from './stores/toast'
import './styles.css'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)

setUnauthorizedHandler(() => {
  const auth = useAuthStore()
  auth.clearAuth()
  if (router.currentRoute.value.name !== 'login') {
    router.push({ name: 'login', query: { redirect: router.currentRoute.value.fullPath } })
  }
})

setNotificationHandler((message, type) => {
  const toast = useToastStore()
  toast.show(message, type)
})

app.mount('#app')
