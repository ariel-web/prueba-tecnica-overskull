<template>
  <div class="p-6">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">{{ isEdit ? 'Editar' : 'Crear' }} Producto</h1>

    <LoadingSpinner v-if="loadingData" text="Cargando datos..." />

    <template v-else>
      <div v-if="Object.keys(validation.errors.value).length" class="bg-red-50 border border-red-200 text-red-700 p-4 rounded mb-4">
        <ul class="list-disc list-inside text-sm">
          <li v-for="(msgs, field) in validation.errors.value" :key="field">
            <span v-for="msg in msgs" :key="msg">{{ msg }}</span>
          </li>
        </ul>
      </div>

      <p v-if="success" class="text-green-600 bg-green-50 p-3 rounded mb-4">{{ success }}</p>

      <div class="bg-white p-6 rounded-lg shadow max-w-2xl space-y-4">
        <FormField label="Nombre" :error="validation.get('name')" required>
          <input
            v-model="form.name"
            placeholder="Nombre del producto"
            class="border border-gray-300 rounded px-3 py-2 w-full"
          />
        </FormField>

        <FormField label="Descripción" :error="validation.get('description')">
          <textarea
            v-model="form.description"
            placeholder="Descripción"
            class="border border-gray-300 rounded px-3 py-2 w-full"
          ></textarea>
        </FormField>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <FormField label="Precio" :error="validation.get('price')" required>
            <input
              v-model="form.price"
              type="number"
              step="0.01"
              min="0"
              placeholder="Precio"
              class="border border-gray-300 rounded px-3 py-2 w-full"
            />
          </FormField>

          <FormField label="Stock" :error="validation.get('stock')" required>
            <input
              v-model="form.stock"
              type="number"
              min="0"
              placeholder="Stock"
              class="border border-gray-300 rounded px-3 py-2 w-full"
            />
          </FormField>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <FormField label="Categoría" :error="validation.get('category_id')" required>
            <select
              v-model="form.category_id"
              class="border border-gray-300 rounded px-3 py-2 w-full"
            >
              <option value="">Seleccione categoría</option>
              <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
          </FormField>

          <FormField label="Estado">
            <select v-model="form.status" class="border border-gray-300 rounded px-3 py-2 w-full">
              <option :value="1">Activo</option>
              <option :value="0">Inactivo</option>
            </select>
          </FormField>
        </div>

        <div class="flex gap-2">
          <button
            @click="save"
            :disabled="productStore.saving"
            class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded disabled:opacity-50"
          >
            <span v-if="productStore.saving" class="inline-flex items-center gap-2">
              <span class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></span>
              Guardando...
            </span>
            <span v-else>Guardar</span>
          </button>
          <router-link
            to="/products"
            class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-2 rounded"
          >
            Cancelar
          </router-link>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useProductStore } from '../stores/product'
import { useToastStore } from '../stores/toast'
import { categoryService } from '../services/categoryService'
import { useValidation } from '../composables/useValidation'
import FormField from '../components/FormField.vue'
import LoadingSpinner from '../components/LoadingSpinner.vue'

const route = useRoute()
const router = useRouter()
const productStore = useProductStore()
const toast = useToastStore()
const validation = useValidation()

const isEdit = computed(() => !!route.params.id)
const categories = ref([])
const loadingData = ref(false)
const success = ref(null)

const form = reactive({
  name: '',
  description: '',
  price: '',
  stock: '',
  category_id: '',
  status: 1,
})

function validateForm() {
  return validation.validate(form, {
    name: { required: true, minLength: 3, label: 'Nombre' },
    price: { required: true, numeric: true, min: 0, label: 'Precio' },
    stock: { required: true, numeric: true, min: 0, label: 'Stock' },
    category_id: { required: true, label: 'Categoría' },
  })
}

async function loadCategories() {
  try {
    const res = await categoryService.list({ per_page: 100 })
    categories.value = res.data.data
  } catch {
    // handled by interceptor
  }
}

async function loadProduct() {
  loadingData.value = true
  try {
    const data = await productStore.fetchProduct(route.params.id)
    if (data) {
      Object.assign(form, {
        name: data.name,
        description: data.description || '',
        price: data.price,
        stock: data.stock,
        category_id: data.category_id ?? '',
        status: data.status ? 1 : 0,
      })
    }
  } finally {
    loadingData.value = false
  }
}

async function save() {
  validation.clear()
  success.value = null

  if (!validateForm()) return

  try {
    if (isEdit.value) {
      await productStore.updateProduct(route.params.id, form)
    } else {
      await productStore.createProduct(form)
    }
    toast.success(`Producto ${isEdit.value ? 'actualizado' : 'creado'} correctamente`)
    setTimeout(() => router.push('/products'), 800)
  } catch (err) {
    if (err.response?.status === 422 && err.response?.data?.errors) {
      validation.setBackendErrors(err.response.data.errors)
    }
  }
}

onMounted(() => {
  loadCategories()
  if (isEdit.value) loadProduct()
})
</script>
