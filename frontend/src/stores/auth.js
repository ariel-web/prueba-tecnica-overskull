import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '../services/api'

export const useAuthStore = defineStore('auth', () => {
  const token = ref(localStorage.getItem('token') || null)
  const user = ref(null)
  const loading = ref(false)
  const error = ref(null)

  const isAuthenticated = computed(() => !!token.value)

  function clearAuth() {
    token.value = null
    user.value = null
    localStorage.removeItem('token')
  }

  async function login(email, password) {
    loading.value = true
    error.value = null

    try {
      const { data } = await api.post('/login', { email, password })
      token.value = data.token
      user.value = data.user
      localStorage.setItem('token', data.token)
      return true
    } catch (err) {
      error.value = err.response?.data?.message || 'Credenciales inválidas'
      return false
    } finally {
      loading.value = false
    }
  }

  async function fetchUser() {
    if (!token.value) return

    try {
      const { data } = await api.get('/me')
      user.value = data
    } catch (err) {
      if (err.response?.status === 401) {
        clearAuth()
      }
    }
  }

  async function logout() {
    try {
      await api.post('/logout')
    } catch {
      // Token may be expired — clear local state regardless
    }
    clearAuth()
  }

  return { token, user, loading, error, isAuthenticated, login, fetchUser, logout, clearAuth }
})
