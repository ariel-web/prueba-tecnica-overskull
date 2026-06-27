import { defineStore } from 'pinia'
import { ref } from 'vue'
import { productService } from '../services/productService'

export const useProductStore = defineStore('product', () => {
  const products = ref([])
  const product = ref(null)
  const stockMovements = ref([])
  const meta = ref({ current_page: 1, last_page: 1, total: 0 })
  const movementsMeta = ref({ current_page: 1, last_page: 1, total: 0 })
  const loading = ref(false)
  const saving = ref(false)
  const error = ref(null)
  const success = ref(null)

  async function fetchProducts(params = {}) {
    loading.value = true
    error.value = null
    try {
      const res = await productService.list(params)
      products.value = res.data.data
      meta.value = res.data.meta
    } catch (err) {
      error.value = 'Error al cargar productos'
    } finally {
      loading.value = false
    }
  }

  async function fetchProduct(id) {
    loading.value = true
    error.value = null
    try {
      const res = await productService.get(id)
      product.value = res.data.data
      return res.data.data
    } catch (err) {
      error.value = 'Error al cargar el producto'
      return null
    } finally {
      loading.value = false
    }
  }

  async function createProduct(data) {
    saving.value = true
    success.value = null
    try {
      await productService.create(data)
      success.value = 'Producto creado correctamente'
      return true
    } catch (err) {
      throw err
    } finally {
      saving.value = false
    }
  }

  async function updateProduct(id, data) {
    saving.value = true
    success.value = null
    try {
      await productService.update(id, data)
      success.value = 'Producto actualizado correctamente'
      return true
    } catch (err) {
      throw err
    } finally {
      saving.value = false
    }
  }

  async function deleteProduct(id) {
    try {
      await productService.delete(id)
      success.value = 'Producto eliminado'
      return true
    } catch (err) {
      error.value = 'No se pudo eliminar el producto'
      return false
    }
  }

  async function fetchStockMovements(productId, params = {}) {
    loading.value = true
    error.value = null
    try {
      const res = await productService.stockMovements(productId, params)
      stockMovements.value = res.data.data
      movementsMeta.value = res.data.meta
    } catch (err) {
      error.value = 'Error al cargar movimientos de stock'
    } finally {
      loading.value = false
    }
  }

  async function createStockMovement(productId, data) {
    saving.value = true
    success.value = null
    try {
      await productService.createStockMovement(productId, data)
      success.value = 'Movimiento registrado correctamente'
      return true
    } catch (err) {
      throw err
    } finally {
      saving.value = false
    }
  }

  function reset() {
    products.value = []
    product.value = null
    stockMovements.value = []
    error.value = null
    success.value = null
  }

  return {
    products,
    product,
    stockMovements,
    meta,
    movementsMeta,
    loading,
    saving,
    error,
    success,
    fetchProducts,
    fetchProduct,
    createProduct,
    updateProduct,
    deleteProduct,
    fetchStockMovements,
    createStockMovement,
    reset,
  }
})
