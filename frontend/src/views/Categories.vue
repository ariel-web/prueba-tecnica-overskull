<template>
  <div class="p-6">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">Categorías</h1>

    <div v-if="Object.keys(validation.errors.value).length" class="bg-red-50 border border-red-200 text-red-700 p-4 rounded mb-4">
      <ul class="list-disc list-inside text-sm">
        <li v-for="(msgs, field) in validation.errors.value" :key="field">
          <span v-for="msg in msgs" :key="msg">{{ msg }}</span>
        </li>
      </ul>
    </div>

    <p v-if="success" class="text-green-600 bg-green-50 p-3 rounded mb-4">{{ success }}</p>

    <div class="bg-white p-4 rounded-lg shadow mb-4 grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
      <FormField label="Nombre" :error="validation.get('name')" required>
        <input
          v-model="form.name"
          placeholder="Nombre categoría"
          class="border border-gray-300 rounded px-3 py-2 w-full"
          @keyup.enter="save"
        />
      </FormField>
      <FormField label="Descripción" :error="validation.get('description')">
        <input
          v-model="form.description"
          placeholder="Descripción"
          class="border border-gray-300 rounded px-3 py-2 w-full"
        />
      </FormField>
      <FormField label="Estado">
        <select v-model="form.status" class="border border-gray-300 rounded px-3 py-2 w-full">
          <option :value="1">Activo</option>
          <option :value="0">Inactivo</option>
        </select>
      </FormField>
      <button
        @click="save"
        :disabled="categoryStore.saving"
        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded disabled:opacity-50"
      >
        {{ form.id ? 'Actualizar' : 'Guardar' }}
      </button>
    </div>

    <DataTable
      :columns="columns"
      :items="categoryStore.categories"
      :loading="categoryStore.loading"
    >
      <template #cell-name="{ item }">{{ item.name }}</template>
      <template #cell-status="{ item }">
        <span
          :class="item.status ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
          class="px-2 py-1 rounded text-xs"
        >
          {{ item.status ? 'Activo' : 'Inactivo' }}
        </span>
      </template>
      <template #actions="{ item }">
        <button @click="edit(item)" class="text-blue-600 hover:underline">Editar</button>
        <button @click="confirmDelete(item)" class="text-red-600 hover:underline">Eliminar</button>
      </template>
    </DataTable>

    <Pagination :meta="categoryStore.meta" @page-change="changePage" />
  </div>

  <ConfirmDialog
    :visible="confirmVisible"
    title="Eliminar categoría"
    message="¿Estás seguro de que deseas eliminar esta categoría?"
    confirm-text="Eliminar"
    @confirm="handleDelete"
    @cancel="confirmVisible = false"
  />
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useCategoryStore } from '../stores/category'
import { useToastStore } from '../stores/toast'
import { useValidation } from '../composables/useValidation'
import DataTable from '../components/DataTable.vue'
import Pagination from '../components/Pagination.vue'
import ConfirmDialog from '../components/ConfirmDialog.vue'
import FormField from '../components/FormField.vue'

const categoryStore = useCategoryStore()
const toast = useToastStore()
const validation = useValidation()

const success = ref(null)
const page = ref(1)
const confirmVisible = ref(false)
const itemToDelete = ref(null)

const form = reactive({
  id: null,
  name: '',
  description: '',
  status: 1,
})

const columns = [
  { key: 'id', label: 'ID', sortable: false },
  { key: 'name', label: 'Nombre', sortable: false },
  { key: 'status', label: 'Estado', sortable: false },
]

async function load() {
  await categoryStore.fetchCategories({ per_page: 15, page: page.value })
}

function edit(c) {
  form.id = c.id
  form.name = c.name
  form.description = c.description || ''
  form.status = c.status ? 1 : 0
  validation.clear()
}

function resetForm() {
  Object.assign(form, { id: null, name: '', description: '', status: 1 })
}

function validateForm() {
  return validation.validate(form, {
    name: { required: true, minLength: 2, label: 'Nombre' },
  })
}

async function save() {
  validation.clear()
  success.value = null

  if (!validateForm()) return

  try {
    if (form.id) {
      await categoryStore.updateCategory(form.id, form)
    } else {
      await categoryStore.createCategory(form)
    }
    toast.success(`Categoría ${form.id ? 'actualizada' : 'creada'} correctamente`)
    success.value = categoryStore.success
    resetForm()
    load()
  } catch (err) {
    if (err.response?.status === 422 && err.response?.data?.errors) {
      validation.setBackendErrors(err.response.data.errors)
    }
  }
}

function confirmDelete(item) {
  itemToDelete.value = item
  confirmVisible.value = true
}

async function handleDelete() {
  confirmVisible.value = false
  if (!itemToDelete.value) return

  const ok = await categoryStore.deleteCategory(itemToDelete.value.id)
  if (ok) {
    toast.success('Categoría eliminada correctamente')
    load()
  }
}

function changePage(newPage) {
  page.value = newPage
  load()
}

onMounted(load)
</script>
