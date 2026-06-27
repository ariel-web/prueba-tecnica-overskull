import { ref } from 'vue'

export function useValidation() {
  const errors = ref({})

  function validate(data, rules) {
    errors.value = {}

    for (const [field, rule] of Object.entries(rules)) {
      const value = data[field]
      const isEmpty = value === null || value === undefined || value === ''

      if (rule.required && isEmpty) {
        errors.value[field] = [`${rule.label} es obligatorio`]
        continue
      }

      if (isEmpty) continue

      if (rule.email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
        if (!emailRegex.test(value)) {
          errors.value[field] = [`El formato de ${rule.label} no es válido`]
          continue
        }
      }

      if (rule.numeric || rule.min !== undefined || rule.max !== undefined) {
        const num = Number(value)
        if (isNaN(num)) {
          errors.value[field] = [`${rule.label} debe ser un número`]
          continue
        }
        if (rule.min !== undefined && num < rule.min) {
          errors.value[field] = [`${rule.label} debe ser mayor o igual a ${rule.min}`]
          continue
        }
        if (rule.max !== undefined && num > rule.max) {
          errors.value[field] = [`${rule.label} debe ser menor o igual a ${rule.max}`]
          continue
        }
      }

      if (rule.minLength && String(value).length < rule.minLength) {
        errors.value[field] = [`${rule.label} debe tener al menos ${rule.minLength} caracteres`]
        continue
      }
    }

    return Object.keys(errors.value).length === 0
  }

  function setBackendErrors(backendErrors) {
    errors.value = { ...backendErrors }
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

  return { errors, validate, setBackendErrors, clear, has, get }
}
