<template>
  <div class="flex items-center justify-center gap-2 py-8">
    <div
      class="animate-spin rounded-full border-b-2"
      :class="sizeClasses"
      :style="{ borderColor: color }"
    ></div>
    <span v-if="text" class="text-gray-500 text-sm">{{ text }}</span>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  text: { type: String, default: '' },
  size: { type: String, default: 'md' },
  color: { type: String, default: '#2563eb' },
})

const sizeClasses = computed(() => {
  const sizes = {
    sm: 'h-4 w-4',
    md: 'h-6 w-6',
    lg: 'h-8 w-8',
  }
  return sizes[props.size] || sizes.md
})
</script>
