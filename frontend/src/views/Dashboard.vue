<template>
  <div class="p-6">
    <h1 class="text-2xl font-bold mb-6 text-gray-800">Dashboard</h1>

    <LoadingSpinner v-if="loading" text="Cargando métricas..." />

    <p v-else-if="error" class="text-red-600 bg-red-50 p-4 rounded">{{ error }}</p>

    <template v-else>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white p-6 rounded-lg shadow">
          <p class="text-sm text-gray-500">Productos</p>
          <p class="text-3xl font-bold text-gray-800">{{ data.products }}</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow">
          <p class="text-sm text-gray-500">Categorías</p>
          <p class="text-3xl font-bold text-gray-800">{{ data.categories }}</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow">
          <p class="text-sm text-gray-500">Bajo stock</p>
          <p class="text-3xl font-bold text-red-600">{{ data.low_stock?.length || 0 }}</p>
        </div>
      </div>

      <div class="bg-white p-6 rounded-lg shadow">
        <h3 class="text-lg font-semibold mb-4 text-gray-800">Últimos movimientos</h3>
        <div v-if="data.last_movements?.length" class="space-y-2">
          <div
            v-for="m in data.last_movements"
            :key="m.id"
            class="flex items-center justify-between border-b pb-2"
          >
            <span
              :class="m.type === 'entrada' ? 'text-green-600' : 'text-red-600'"
              class="font-medium"
            >
              {{ m.type }}
            </span>
            <span class="text-gray-700">{{ m.quantity }} unidades</span>
            <span class="text-gray-500 text-sm">{{ m.reason }}</span>
          </div>
        </div>
        <p v-else class="text-gray-500">Sin movimientos</p>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../services/api'
import LoadingSpinner from '../components/LoadingSpinner.vue'

const loading = ref(false)
const error = ref(null)
const data = ref({})

onMounted(async () => {
  loading.value = true
  try {
    const res = await api.get('/dashboard')
    data.value = res.data
  } catch {
    error.value = 'No se pudo cargar el dashboard'
  } finally {
    loading.value = false
  }
})
</script>
