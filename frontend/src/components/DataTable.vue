<template>
  <div class="bg-white rounded-lg shadow overflow-hidden">
    <LoadingSpinner v-if="loading" text="Cargando..." />
    <template v-else>
      <div v-if="items.length === 0" class="px-4 py-8 text-center text-gray-400 text-sm">
        No hay registros para mostrar
      </div>
      <table v-else class="w-full">
        <thead class="bg-gray-50">
          <tr>
            <th
              v-for="col in columns"
              :key="col.key"
              class="px-4 py-3 text-left text-sm font-medium text-gray-600 select-none"
              :class="col.sortable ? 'cursor-pointer hover:bg-gray-100' : ''"
              @click="col.sortable && handleSort(col.key)"
            >
              {{ col.label }}
              <span v-if="col.sortable" class="ml-1 text-xs">
                <template v-if="sortKey === col.key">
                  {{ sortDirection === 'asc' ? '↑' : '↓' }}
                </template>
                <template v-else>↕</template>
              </span>
            </th>
            <th v-if="$slots.actions" class="px-4 py-3 text-left text-sm font-medium text-gray-600">
              Acciones
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="item in items" :key="item.id" class="hover:bg-gray-50">
            <td
              v-for="col in columns"
              :key="col.key"
              class="px-4 py-3 text-sm"
            >
              <slot :name="`cell-${col.key}`" :item="item">{{ item[col.key] }}</slot>
            </td>
            <td v-if="$slots.actions" class="px-4 py-3 text-sm">
              <div class="flex gap-2">
                <slot name="actions" :item="item" />
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </template>
  </div>
</template>

<script setup>
import LoadingSpinner from './LoadingSpinner.vue'

defineProps({
  columns: { type: Array, required: true },
  items: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  sortKey: { type: String, default: '' },
  sortDirection: { type: String, default: 'asc' },
})

const emit = defineEmits(['sort'])

function handleSort(key) {
  emit('sort', key)
}
</script>
