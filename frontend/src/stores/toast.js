import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useToastStore = defineStore('toast', () => {
  const toasts = ref([])
  let nextId = 0

  function show(message, type = 'info', duration = 4000) {
    const id = ++nextId
    toasts.value.push({ id, message, type })

    if (duration > 0) {
      setTimeout(() => remove(id), duration)
    }
  }

  function remove(id) {
    toasts.value = toasts.value.filter((t) => t.id !== id)
  }

  function success(message) {
    show(message, 'success')
  }

  function error(message) {
    show(message, 'error')
  }

  function warning(message) {
    show(message, 'warning')
  }

  function info(message) {
    show(message, 'info')
  }

  return { toasts, show, remove, success, error, warning, info }
})
