import api from './api'

export const productService = {
  list(params = {}) {
    return api.get('/products', { params })
  },

  get(id) {
    return api.get(`/products/${id}`)
  },

  create(data) {
    return api.post('/products', data)
  },

  update(id, data) {
    return api.put(`/products/${id}`, data)
  },

  delete(id) {
    return api.delete(`/products/${id}`)
  },

  stockMovements(id, params = {}) {
    return api.get(`/products/${id}/stock-movements`, { params })
  },

  createStockMovement(id, data) {
    return api.post(`/products/${id}/stock-movements`, data)
  },
}
