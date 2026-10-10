<script setup lang="ts">
import type { TableColumn } from '~/types/api'

/**
 * A simple table. Cells render `row[column.key]`; override one with a
 * `#cell-<key>="{ row, value }"` slot. Shows a spinner while `loading` and an
 * empty state (overridable with the `#empty` slot) when there are no rows.
 */
const props = withDefaults(defineProps<{
  columns: TableColumn[]
  rows: Record<string, unknown>[]
  rowKey?: string
  loading?: boolean
  emptyTitle?: string
}>(), {
  rowKey: 'id',
  loading: false,
  emptyTitle: 'Nothing to show yet',
})

function keyFor(row: Record<string, unknown>, index: number): string | number {
  const value = row[props.rowKey]
  return typeof value === 'string' || typeof value === 'number' ? value : index
}
</script>

<template>
  <div class="app-data-table">
    <div v-if="loading" class="app-data-table__loading">
      <AppSpinner />
    </div>
    <template v-else-if="rows.length === 0">
      <slot name="empty">
        <AppEmptyState :title="emptyTitle" />
      </slot>
    </template>
    <table v-else>
      <thead>
        <tr>
          <th v-for="column in columns" :key="column.key" scope="col">
            {{ column.label }}
          </th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(row, index) in rows" :key="keyFor(row, index)">
          <td v-for="column in columns" :key="column.key">
            <slot :name="`cell-${column.key}`" :row="row" :value="row[column.key]">
              {{ row[column.key] }}
            </slot>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<style scoped>
.app-data-table {
  overflow-x: auto;
}

.app-data-table__loading {
  display: flex;
  justify-content: center;
  padding: 2rem;
}

table {
  width: 100%;
  border-collapse: collapse;
}

th,
td {
  text-align: left;
  padding: 0.6rem 0.75rem;
  border-bottom: 1px solid #eee;
}

th {
  font-size: 0.85rem;
  color: #555;
}
</style>
