import { defineStore } from 'pinia'
import { ref } from 'vue'
import { categoryService } from '../services/categoryService'

export const useCategoryStore = defineStore('category', () => {
  const categories = ref([])
  const meta = ref({ current_page: 1, last_page: 1, total: 0 })
  const loading = ref(false)
  const saving = ref(false)
  const error = ref(null)
  const success = ref(null)

  async function fetchCategories(params = {}) {
    loading.value = true
    error.value = null
    try {
      const res = await categoryService.list(params)
      categories.value = res.data.data
      meta.value = res.data.meta
    } catch (err) {
      error.value = 'Error al cargar categorías'
    } finally {
      loading.value = false
    }
  }

  async function createCategory(data) {
    saving.value = true
    success.value = null
    try {
      await categoryService.create(data)
      success.value = 'Categoría creada correctamente'
      return true
    } catch (err) {
      throw err
    } finally {
      saving.value = false
    }
  }

  async function updateCategory(id, data) {
    saving.value = true
    success.value = null
    try {
      await categoryService.update(id, data)
      success.value = 'Categoría actualizada correctamente'
      return true
    } catch (err) {
      throw err
    } finally {
      saving.value = false
    }
  }

  async function deleteCategory(id) {
    try {
      await categoryService.delete(id)
      success.value = 'Categoría eliminada'
      return true
    } catch (err) {
      error.value = 'No se pudo eliminar la categoría'
      return false
    }
  }

  function reset() {
    categories.value = []
    error.value = null
    success.value = null
  }

  return {
    categories,
    meta,
    loading,
    saving,
    error,
    success,
    fetchCategories,
    createCategory,
    updateCategory,
    deleteCategory,
    reset,
  }
})
