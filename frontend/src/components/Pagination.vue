<template>
  <div v-if="meta.last_page > 1" class="flex items-center justify-between mt-4">
    <span class="text-sm text-gray-600">
      Página {{ meta.current_page }} de {{ meta.last_page }}
      <span v-if="meta.total" class="text-gray-400">({{ meta.total }} registros)</span>
    </span>
    <div class="flex items-center gap-2">
      <button
        :disabled="meta.current_page === 1"
        @click="$emit('page-change', meta.current_page - 1)"
        class="px-3 py-1.5 bg-gray-200 rounded text-sm hover:bg-gray-300 disabled:opacity-40 disabled:cursor-not-allowed"
      >
        Anterior
      </button>
      <button
        :disabled="meta.current_page === meta.last_page"
        @click="$emit('page-change', meta.current_page + 1)"
        class="px-3 py-1.5 bg-gray-200 rounded text-sm hover:bg-gray-300 disabled:opacity-40 disabled:cursor-not-allowed"
      >
        Siguiente
      </button>
    </div>
  </div>
</template>

<script setup>
defineProps({
  meta: {
    type: Object,
    required: true,
    validator: (val) => 'current_page' in val && 'last_page' in val,
  },
})

defineEmits(['page-change'])
</script>
