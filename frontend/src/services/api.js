import axios from 'axios'

let onUnauthorized = null
let onNotification = null

export function setUnauthorizedHandler(handler) {
  onUnauthorized = handler
}

export function setNotificationHandler(handler) {
  onNotification = handler
}

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8080/api',
  timeout: 15000,
})

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

api.interceptors.response.use(
  (response) => response,
  (error) => {
    const status = error.response?.status
    const serverMessage = error.response?.data?.message

    switch (status) {
      case 401:
        onUnauthorized?.()
        onNotification?.('Sesión expirada. Inicia sesión nuevamente.', 'error')
        break
      case 403:
        onNotification?.(serverMessage || 'No tienes permisos para esta acción.', 'error')
        break
      case 422:
        onNotification?.(serverMessage || 'Los datos enviados no son válidos.', 'warning')
        break
      case 500:
        onNotification?.(serverMessage || 'Error interno del servidor. Intenta nuevamente.', 'error')
        break
      case undefined:
        onNotification?.('Error de conexión. Verifica tu red o que el servidor esté activo.', 'error')
        break
    }

    return Promise.reject(error)
  }
)

export default api
