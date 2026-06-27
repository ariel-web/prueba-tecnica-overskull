import { ref } from 'vue'

export function useFormErrors() {
  const errors = ref({})

  function setErrors(err) {
    errors.value = {}
    if (err.response?.status === 422 && err.response?.data?.errors) {
      errors.value = err.response.data.errors
    }
  }

  function clear() {
    errors.value = {}
  }

  function has(field) {
    return !!errors.value[field]
  }

  function get(field) {
    return errors.value[field]?.[0] || null
  }

  return { errors, setErrors, clear, has, get }
}
