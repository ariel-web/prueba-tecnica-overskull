import axios from 'axios'

// Legacy issue: API file exists but components still create duplicated axios calls.
const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api'
})

api.interceptors.request.use(config => {
  const token = localStorage.getItem('token')
  if (token) config.headers.Authorization = 'Bearer ' + token
  return config
})

// Legacy issue: global error interceptor does not normalize errors or redirect on 401.
api.interceptors.response.use(
  response => response,
  error => Promise.reject(error)
)

export default api
