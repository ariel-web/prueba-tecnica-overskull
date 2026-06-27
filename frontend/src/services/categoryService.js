import api from './api'

export const categoryService = {
  list(params = {}) {
    return api.get('/categories', { params })
  },

  get(id) {
    return api.get(`/categories/${id}`)
  },

  create(data) {
    return api.post('/categories', data)
  },

  update(id, data) {
    return api.put(`/categories/${id}`, data)
  },

  delete(id) {
    return api.delete(`/categories/${id}`)
  },
}
