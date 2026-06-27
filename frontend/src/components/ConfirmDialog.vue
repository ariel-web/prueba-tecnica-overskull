<template>
  <Teleport to="body">
    <Transition name="modal">
      <div
        v-if="visible"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
        @click.self="handleCancel"
      >
        <div class="bg-white rounded-lg p-6 max-w-sm w-full mx-4 shadow-xl">
          <h3 class="text-lg font-semibold mb-2 text-gray-800">{{ title }}</h3>
          <p class="text-gray-600 mb-6 text-sm">{{ message }}</p>
          <div class="flex justify-end gap-2">
            <button
              @click="handleCancel"
              class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded text-sm"
            >
              {{ cancelText }}
            </button>
            <button
              @click="handleConfirm"
              :class="confirmClass"
              class="px-4 py-2 text-white rounded text-sm"
            >
              {{ confirmText }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  visible: { type: Boolean, default: false },
  title: { type: String, default: 'Confirmar acción' },
  message: { type: String, default: '¿Estás seguro?' },
  confirmText: { type: String, default: 'Confirmar' },
  cancelText: { type: String, default: 'Cancelar' },
  variant: { type: String, default: 'danger' },
})

const emit = defineEmits(['confirm', 'cancel'])

const confirmClass = computed(() => {
  const variants = {
    danger: 'bg-red-600 hover:bg-red-700',
    primary: 'bg-blue-600 hover:bg-blue-700',
    success: 'bg-green-600 hover:bg-green-700',
  }
  return variants[props.variant] || variants.danger
})

function handleConfirm() {
  emit('confirm')
}

function handleCancel() {
  emit('cancel')
}
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.2s ease;
}
.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}
</style>
