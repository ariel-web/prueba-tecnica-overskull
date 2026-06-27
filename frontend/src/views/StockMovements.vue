<template>
  <div class="p-6">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">Movimientos de Stock</h1>
    <router-link to="/products" class="text-blue-600 hover:underline mb-4 inline-block">← Volver</router-link>

    <div v-if="Object.keys(validation.errors.value).length" class="bg-red-50 border border-red-200 text-red-700 p-4 rounded mb-4">
      <ul class="list-disc list-inside text-sm">
        <li v-for="(msgs, field) in validation.errors.value" :key="field">
          <span v-for="msg in msgs" :key="msg">{{ msg }}</span>
        </li>
      </ul>
    </div>

    <p v-if="success" class="text-green-600 bg-green-50 p-3 rounded mb-4">{{ success }}</p>

    <div class="bg-white p-4 rounded-lg shadow mb-6 grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
      <FormField label="Tipo" :error="validation.get('type')" required>
        <select v-model="form.type" class="border border-gray-300 rounded px-3 py-2 w-full">
          <option value="entrada">Entrada</option>
          <option value="salida">Salida</option>
        </select>
      </FormField>
      <FormField label="Cantidad" :error="validation.get('quantity')" required>
        <input
          v-model="form.quantity"
          type="number"
          min="1"
          placeholder="Cantidad"
          class="border border-gray-300 rounded px-3 py-2 w-full"
        />
      </FormField>
      <FormField label="Motivo" :error="validation.get('reason')">
        <input
          v-model="form.reason"
          placeholder="Motivo"
          class="border border-gray-300 rounded px-3 py-2 w-full"
        />
      </FormField>
      <button
        @click="save"
        :disabled="productStore.saving"
        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded disabled:opacity-50"
      >
        <span v-if="productStore.saving" class="inline-flex items-center gap-2">
          <span class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></span>
          Guardando...
        </span>
        <span v-else>Registrar</span>
      </button>
    </div>

    <DataTable
      :columns="columns"
      :items="productStore.stockMovements"
      :loading="productStore.loading"
    >
      <template #cell-type="{ item }">
        <span
          :class="item.type === 'entrada' ? 'text-green-600' : 'text-red-600'"
          class="font-medium"
        >
          {{ item.type }}
        </span>
      </template>
      <template #cell-reason="{ item }">{{ item.reason || '-' }}</template>
      <template #cell-user="{ item }">{{ item.user?.name || '-' }}</template>
    </DataTable>

    <Pagination :meta="productStore.movementsMeta" @page-change="changePage" />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useProductStore } from '../stores/product'
import { useToastStore } from '../stores/toast'
import { useValidation } from '../composables/useValidation'
import DataTable from '../components/DataTable.vue'
import Pagination from '../components/Pagination.vue'
import FormField from '../components/FormField.vue'

const route = useRoute()
const productId = route.params.id

const productStore = useProductStore()
const toast = useToastStore()
const validation = useValidation()

const success = ref(null)
const page = ref(1)

const form = reactive({
  type: 'entrada',
  quantity: '',
  reason: '',
})

const columns = [
  { key: 'id', label: 'ID', sortable: false },
  { key: 'type', label: 'Tipo', sortable: false },
  { key: 'quantity', label: 'Cantidad', sortable: false },
  { key: 'reason', label: 'Motivo', sortable: false },
  { key: 'user', label: 'Usuario', sortable: false },
]

function validateForm() {
  return validation.validate(form, {
    type: { required: true, label: 'Tipo' },
    quantity: { required: true, numeric: true, min: 1, label: 'Cantidad' },
  })
}

async function load() {
  await productStore.fetchStockMovements(productId, { page: page.value })
}

async function save() {
  validation.clear()
  success.value = null

  if (!validateForm()) return

  try {
    await productStore.createStockMovement(productId, form)
    toast.success('Movimiento registrado correctamente')
    success.value = productStore.success
    Object.assign(form, { type: 'entrada', quantity: '', reason: '' })
    load()
  } catch (err) {
    if (err.response?.status === 422 && err.response?.data?.errors) {
      validation.setBackendErrors(err.response.data.errors)
    }
  }
}

function changePage(newPage) {
  page.value = newPage
  load()
}

onMounted(load)
</script>
