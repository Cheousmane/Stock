<template>
  <div class="pro-table-wrap">
    <div class="overflow-x-auto overscroll-x-contain">
      <table class="w-full min-w-[680px]">
        <thead>
          <tr class="pro-table-head">
            <th v-if="selectable" class="w-10 px-4 py-3">
              <input
                type="checkbox"
                class="size-4 rounded border-border text-primary-600 focus:ring-primary-500 cursor-pointer"
                :checked="allSelected"
                :indeterminate.prop="selectedCount > 0 && !allSelected"
                :aria-label="t('common.select')"
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
                <svg
                  v-if="col.sortable !== false && col.key && sortBy === col.key"
                  class="w-3 h-3 transition-transform duration-150"
                  :class="sortOrder === 'asc' ? '' : 'rotate-180'"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                  stroke-width="2.5"
                >
                  <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                </svg>
              </span>
            </th>
            <th v-if="$slots.actions" class="w-12 px-4 py-3" />
          </tr>
        </thead>
        <tbody class="divide-y divide-border/60">
          <tr
            v-for="(row, i) in rows"
            :key="row[rowKey] ?? i"
            @click="$emit('rowClick', row)"
            :class="[
              clickable ? 'cursor-pointer' : '',
              striped && i % 2 ? 'bg-surface-secondary/40' : 'bg-surface',
              hover && 'transition-colors duration-150 hover:bg-emerald-500/[0.04]',
              selectedIds && isRowSelected(row) ? 'bg-emerald-500/[0.08] hover:bg-emerald-500/[0.1]' : '',
            ]"
          >
            <td v-if="selectable" class="px-4 py-3">
              <input
                type="checkbox"
                class="size-4 rounded border-border text-primary-600 focus:ring-primary-500 cursor-pointer"
                :checked="isRowSelected(row)"
                :aria-label="t('common.select')"
                @click.stop="toggleRow(row)"
              />
            </td>
            <td
              v-for="col in columns"
              :key="col.key"
              :class="[
                'px-5 py-3.5 text-[13px] font-semibold text-text-primary',
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
          <p v-if="emptyDescription" class="mt-1 text-xs font-medium text-text-tertiary">{{ emptyDescription }}</p>
        </div>
      </slot>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
  columns: { type: Array, required: true },
  rows: { type: Array, default: () => [] },
  rowKey: { type: String, default: 'id' },
  sortBy: { type: String, default: '' },
  sortOrder: { type: String, default: 'asc' },
  striped: { type: Boolean, default: false },
  hover: { type: Boolean, default: true },
  clickable: { type: Boolean, default: false },
  selectable: { type: Boolean, default: false },
  selectedIds: { type: Array, default: () => [] },
  emptyText: { type: String, default: '' },
  emptyDescription: { type: String, default: '' },
})

const emit = defineEmits(['sort', 'rowClick', 'update:selectedIds'])

const selectedCount = computed(() => props.selectedIds.length)
const allSelected = computed(() => props.rows.length > 0 && selectedCount.value === props.rows.length)

function isRowSelected(row) {
  return props.selectedIds.includes(row[props.rowKey])
}

function toggleAll() {
  emit('update:selectedIds', allSelected.value ? [] : props.rows.map(r => r[props.rowKey]))
}

function toggleRow(row) {
  const id = row[props.rowKey]
  emit('update:selectedIds', isRowSelected(row) ? props.selectedIds.filter(x => x !== id) : [...props.selectedIds, id])
}
</script>
