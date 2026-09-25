<template>
  <div class="pro-table-wrap">
    <div class="overflow-x-auto">
      <table class="w-full">
        <thead>
            <tr class="pro-table-head">
            <th v-if="selectable" class="w-10 px-4 py-3">
              <input
                type="checkbox"
                class="size-4 rounded border-border text-primary-600 focus:ring-primary-500 cursor-pointer"
                :checked="selectedCount > 0"
                :indeterminate="selectedCount > 0 && selectedCount < rows.length"
                @click.stop="toggleAll"
              />
            </th>
            <th
              v-for="col in columns"
              :key="col.key"
              @click="col.sortable !== false && col.key && $emit('sort', col.key)"
              :class="[
                'px-5 py-4 text-[11px] font-extrabold tracking-[0.1em] text-text-tertiary uppercase whitespace-nowrap',
                col.align === 'right' ? 'text-right' : col.align === 'center' ? 'text-center' : 'text-left',
                { 'cursor-pointer select-none hover:text-text-primary transition-colors': col.sortable !== false && col.key },
              ]"
            >
              <span class="inline-flex items-center gap-1.5">
                {{ col.label }}
                <span v-if="col.sortable !== false && col.key && sortBy === col.key" class="inline-flex flex-col -space-y-1">
                  <svg class="w-2.5 h-2.5" :class="sortOrder === 'asc' ? 'text-text-primary' : 'text-border'" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" />
                  </svg>
                  <svg class="w-2.5 h-2.5" :class="sortOrder === 'desc' ? 'text-text-primary' : 'text-border'" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M14.77 12.79a.75.75 0 01-1.06-.02L10 8.832 6.29 12.77a.75.75 0 11-1.08-1.04l4.25-4.5a.75.75 0 011.08 0l4.25 4.5a.75.75 0 01-.02 1.06z" />
                  </svg>
                </span>
              </span>
            </th>
            <th v-if="$slots.actions" class="w-12 px-4 py-3" />
          </tr>
        </thead>
        <tbody class="divide-y divide-border/60">
          <tr
            v-for="(row, i) in rows"
            :key="row.id ?? i"
            @click="$emit('rowClick', row)"
            :class="[
              clickable ? 'cursor-pointer' : '',
              striped && i % 2 ? 'bg-surface-secondary/40' : 'bg-surface',
              hover && 'transition-colors duration-150 hover:bg-emerald-500/[0.05]',
              selectedIds && isRowSelected(row) ? 'bg-emerald-500/[0.07]' : '',
            ]"
          >
            <td v-if="selectable" class="px-4 py-3">
              <input
                type="checkbox"
                class="size-4 rounded border-border text-primary-600 focus:ring-primary-500 cursor-pointer"
                :checked="isRowSelected(row)"
                @click.stop="toggleRow(row)"
              />
            </td>
            <td
              v-for="col in columns"
              :key="col.key"
              :class="[
                'px-5 py-3.5 text-sm',
                col.align === 'right' ? 'text-right' : col.align === 'center' ? 'text-center' : 'text-left',
                col.class || '',
              ]"
            >
              <slot :name="`cell-${col.key}`" :row="row" :value="col.key ? row[col.key] : null">
                <span class="text-text-primary">{{ col.key ? row[col.key] : '' }}</span>
              </slot>
            </td>
            <td v-if="$slots.actions" class="px-4 py-3 text-right">
              <slot name="actions" :row="row" />
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <div v-if="!rows.length" class="py-12 px-6 text-center">
      <slot name="empty">
        <div class="flex flex-col items-center">
          <span class="pro-empty-icon">
            <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
            </svg>
          </span>
          <p class="text-sm font-extrabold text-text-primary">{{ emptyText || t('common.no_data') }}</p>
          <p class="mt-1 text-xs font-medium text-text-tertiary">Aucun élément à afficher pour le moment.</p>
        </div>
      </slot>
    </div>
  </div>
</template>

<style scoped>
.table-wrapper table:first-child thead tr:first-child th:first-child { border-radius: var(--radius-lg) 0 0 0; }
.table-wrapper table:first-child thead tr:first-child th:last-child { border-radius: 0 var(--radius-lg) 0 0; }
</style>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
  columns: { type: Array, required: true },
  rows: { type: Array, default: () => [] },
  sortBy: { type: String, default: '' },
  sortOrder: { type: String, default: 'asc' },
  striped: { type: Boolean, default: false },
  hover: { type: Boolean, default: true },
  clickable: { type: Boolean, default: false },
  selectable: { type: Boolean, default: false },
  selectedIds: { type: Array, default: () => [] },
  emptyText: { type: String, default: '' },
})

const emit = defineEmits(['sort', 'rowClick', 'update:selectedIds'])

const selectedCount = computed(() => props.selectedIds.length)

function isRowSelected(row) {
  return props.selectedIds.includes(row.id)
}

function toggleAll() {
  const all = props.rows.length > 0 && selectedCount.value === props.rows.length
  emit('update:selectedIds', all ? [] : props.rows.map(r => r.id))
}

function toggleRow(row) {
  const current = isRowSelected(row)
  emit('update:selectedIds', current ? props.selectedIds.filter(id => id !== row.id) : [...props.selectedIds, row.id])
}
</script>
