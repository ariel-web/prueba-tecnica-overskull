<template>
  <div class="h-screen flex flex-col bg-gray-100 overflow-hidden">
    <nav v-if="route.name !== 'login'" class="bg-gray-800 text-white px-6 py-3 flex items-center gap-6 shrink-0">
      <router-link to="/dashboard" class="hover:text-gray-300">Dashboard</router-link>
      <router-link to="/products" class="hover:text-gray-300">Productos</router-link>
      <router-link to="/categories" class="hover:text-gray-300">Categorías</router-link>
      <button @click="handleLogout" class="ml-auto bg-red-600 hover:bg-red-700 px-4 py-2 rounded">
        Salir
      </button>
    </nav>
    <main class="flex-1 overflow-y-auto">
      <router-view />
    </main>
    <AlertToast />
  </div>
</template>

<script setup>
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from './stores/auth'
import AlertToast from './components/AlertToast.vue'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

async function handleLogout() {
  await auth.logout()
  router.push('/login')
}
</script>
