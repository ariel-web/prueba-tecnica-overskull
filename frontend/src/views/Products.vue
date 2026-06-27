<template>
  <div class="p-6">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-gray-800">Productos</h1>
      <router-link to="/products/new" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
        Nuevo producto
      </router-link>
    </div>

    <div class="bg-white p-4 rounded-lg shadow mb-4 flex flex-wrap gap-4 items-end">
      <div>
        <label class="text-sm text-gray-600 block mb-1">Buscar</label>
        <input
          v-model="filters.q"
          placeholder="Nombre del producto"
          class="border border-gray-300 rounded px-3 py-2"
          @keyup.enter="applyFilters"
        />
      </div>
      <div>
        <label class="text-sm text-gray-600 block mb-1">Categoría</label>
        <select
          v-model="filters.category_id"
          class="border border-gray-300 rounded px-3 py-2"
          @change="applyFilters"
        >
          <option value="">Todas</option>
          <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
        </select>
      </div>
      <div>
        <label class="text-sm text-gray-600 block mb-1">Estado</label>
        <select
          v-model="filters.status"
          class="border border-gray-300 rounded px-3 py-2"
          @change="applyFilters"
        >
          <option value="">Todos</option>
          <option value="1">Activo</option>
          <option value="0">Inactivo</option>
        </select>
      </div>
      <button
        @click="applyFilters"
        class="bg-gray-700 hover:bg-gray-800 text-white px-4 py-2 rounded"
      >
        Buscar
      </button>
    </div>

    <DataTable
      :columns="columns"
      :items="productStore.products"
      :loading="productStore.loading"
      :sort-key="sortKey"
      :sort-direction="sortDirection"
      @sort="handleSort"
    >
      <template #cell-name="{ item }">{{ item.name }}</template>
      <template #cell-category="{ item }">{{ item.category?.name || '-' }}</template>
      <template #cell-price="{ item }">S/ {{ Number(item.price).toFixed(2) }}</template>
      <template #cell-stock="{ item }">
        <span :class="item.stock < 10 ? 'text-red-600 font-medium' : ''">{{ item.stock }}</span>
      </template>
      <template #actions="{ item }">
        <router-link :to="`/products/${item.id}/edit`" class="text-blue-600 hover:underline">Editar</router-link>
        <router-link :to="`/products/${item.id}/stock`" class="text-purple-600 hover:underline">Stock</router-link>
        <button @click="confirmDelete(item)" class="text-red-600 hover:underline">Eliminar</button>
      </template>
    </DataTable>

    <Pagination :meta="productStore.meta" @page-change="changePage" />
  </div>

  <ConfirmDialog
    :visible="confirmVisible"
    title="Eliminar producto"
    message="¿Estás seguro de que deseas eliminar este producto? Esta acción no se puede deshacer."
    confirm-text="Eliminar"
    @confirm="handleDelete"
    @cancel="confirmVisible = false"
  />
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useProductStore } from '../stores/product'
import { useCategoryStore } from '../stores/category'
import { useToastStore } from '../stores/toast'
import { categoryService } from '../services/categoryService'
import DataTable from '../components/DataTable.vue'
import Pagination from '../components/Pagination.vue'
import ConfirmDialog from '../components/ConfirmDialog.vue'

const productStore = useProductStore()
const categoryStore = useCategoryStore()
const toast = useToastStore()

const categories = ref([])
const sortKey = ref('created_at')
const sortDirection = ref('desc')
const confirmVisible = ref(false)
const itemToDelete = ref(null)

const filters = reactive({
  q: '',
  category_id: '',
  status: '',
})

const columns = [
  { key: 'id', label: 'ID', sortable: false },
  { key: 'name', label: 'Nombre', sortable: true },
  { key: 'category', label: 'Categoría', sortable: false },
  { key: 'price', label: 'Precio', sortable: true },
  { key: 'stock', label: 'Stock', sortable: true },
]

async function loadCategories() {
  try {
    const res = await categoryService.list({ per_page: 100 })
    categories.value = res.data.data
  } catch {
    // handled by interceptor
  }
}

function buildParams() {
  const params = { page: productStore.meta.current_page || 1 }
  if (filters.q) params.q = filters.q
  if (filters.category_id) params.category_id = filters.category_id
  if (filters.status !== '') params.status = filters.status
  params.sort_by = sortKey.value
  params.sort_direction = sortDirection.value
  return params
}

async function loadProducts() {
  await productStore.fetchProducts(buildParams())
}

function applyFilters() {
  productStore.meta.current_page = 1
  loadProducts()
}

function handleSort(key) {
  if (sortKey.value === key) {
    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortKey.value = key
    sortDirection.value = 'asc'
  }
  loadProducts()
}

function changePage(page) {
  productStore.meta.current_page = page
  loadProducts()
}

function confirmDelete(item) {
  itemToDelete.value = item
  confirmVisible.value = true
}

async function handleDelete() {
  confirmVisible.value = false
  if (!itemToDelete.value) return

  const ok = await productStore.deleteProduct(itemToDelete.value.id)
  if (ok) {
    toast.success('Producto eliminado correctamente')
    loadProducts()
  }
}

onMounted(() => {
  loadCategories()
  loadProducts()
})
</script>
