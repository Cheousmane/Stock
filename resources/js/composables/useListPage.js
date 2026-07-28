import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import { inject } from 'vue'
import axios from 'axios'

export function useListPage({
  fetchUrl,
  fetchFn = null,
  searchPlaceholder = '',
  statusFilterField = null,
  statusOptions = [],
  defaultSortBy = 'id',
  defaultSortOrder = 'desc',
  defaultPerPage = 25,
  pageTitle = '',
}) {
  const router = useRouter()
  const showToast = inject('showToast', () => {})

  const items = ref([])
  const loading = ref(true)
  const search = ref('')
  const statusFilter = ref('')
  const perPage = ref(defaultPerPage)
  const meta = ref(null)
  const sortBy = ref(defaultSortBy)
  const sortOrder = ref(defaultSortOrder)
  const selectedIds = ref([])
  const deleteTarget = ref(null)
  let debounceTimer = null

  const hasActiveFilters = computed(() => search.value || statusFilter.value)

  const allSelected = computed(() => items.value.length > 0 && selectedIds.value.length === items.value.length)

  function isSelected(id) { return selectedIds.value.includes(id) }

  function toggleSelect(id) {
    const idx = selectedIds.value.indexOf(id)
    if (idx > -1) selectedIds.value.splice(idx, 1)
    else selectedIds.value.push(id)
  }

  function toggleAll() {
    if (allSelected.value) { selectedIds.value = [] }
    else { selectedIds.value = items.value.map(i => i.id) }
  }

  const sortedItems = computed(() => {
    if (!sortBy.value) return items.value
    return [...items.value].sort((a, b) => {
      let aVal = a[sortBy.value] ?? ''
      let bVal = b[sortBy.value] ?? ''
      if (typeof aVal === 'string') aVal = aVal.toLowerCase()
      if (typeof bVal === 'string') bVal = bVal.toLowerCase()
      if (aVal < bVal) return sortOrder.value === 'asc' ? -1 : 1
      if (aVal > bVal) return sortOrder.value === 'asc' ? 1 : -1
      return 0
    })
  })

  const visiblePages = computed(() => {
    if (!meta.value) return []
    const { current_page: cur, last_page: last } = meta.value
    if (last <= 7) return Array.from({ length: last }, (_, i) => i + 1)
    const pages = [1]
    if (cur > 3) pages.push('...')
    for (let i = Math.max(2, cur - 1); i <= Math.min(last - 1, cur + 1); i++) pages.push(i)
    if (cur < last - 2) pages.push('...')
    pages.push(last)
    return pages
  })

  function toggleSort(field) {
    if (sortBy.value === field) {
      sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
    } else {
      sortBy.value = field
      sortOrder.value = 'asc'
    }
  }

  function onSearchInput() {
    clearTimeout(debounceTimer)
    debounceTimer = setTimeout(fetchData, 300)
  }

  function clearSearch() { search.value = ''; fetchData() }
  function resetFilters() { search.value = ''; statusFilter.value = ''; fetchData() }

  async function fetchData(page) {
    loading.value = true
    try {
      if (fetchFn) {
        const result = await fetchFn({ page: page || 1, per_page: perPage.value, search: search.value, status: statusFilter.value })
        items.value = result.data ?? result
        meta.value = result.meta ?? null
      } else {
        const params = { page: page || 1, per_page: perPage.value }
        if (search.value) params.search = search.value
        if (statusFilter.value && statusFilterField) params[statusFilterField] = statusFilter.value
        const { data } = await axios.get(fetchUrl, { params })
        items.value = data.data ?? data
        meta.value = data.meta ?? null
      }
      selectedIds.value = []
    } catch {} finally {
      loading.value = false
    }
  }

  function changePage(page) {
    if (page < 1 || (meta.value && page > meta.value.last_page)) return
    fetchData(page)
  }

  function confirmDelete(item) { deleteTarget.value = item }

  async function executeDelete(deleteUrl) {
    if (!deleteTarget.value) return
    const id = deleteTarget.value.id
    deleteTarget.value = null
    try {
      await axios.delete(deleteUrl ? deleteUrl(id) : `/${fetchUrl.split('/').pop()}/${id}`)
      showToast?.('Supprimé avec succès', 'success')
      fetchData(meta.value?.current_page || 1)
    } catch { showToast?.('Erreur lors de la suppression', 'error') }
  }

  async function bulkDelete(ids, bulkDeleteUrl) {
    try {
      await axios.post(bulkDeleteUrl || `/${fetchUrl.split('/').pop()}/bulk-delete`, { ids })
      showToast?.('Supprimés avec succès', 'success')
      selectedIds.value = []
      fetchData(meta.value?.current_page || 1)
    } catch { showToast?.('Erreur lors de la suppression', 'error') }
  }

  function exportFile(exportPath, filename) {
    return async () => {
      try {
        const res = await axios.get(exportPath, { responseType: 'blob' })
        const url = URL.createObjectURL(new Blob([res.data]))
        const a = document.createElement('a'); a.href = url; a.download = filename
        document.body.appendChild(a); a.click(); document.body.removeChild(a); URL.revokeObjectURL(url)
      } catch {}
    }
  }

  onMounted(() => fetchData())
  onBeforeUnmount(() => clearTimeout(debounceTimer))

  return {
    items, loading, search, statusFilter, perPage, meta,
    sortBy, sortOrder, selectedIds, deleteTarget, hasActiveFilters,
    allSelected, isSelected, toggleSelect, toggleAll, sortedItems,
    visiblePages, toggleSort, onSearchInput, clearSearch, resetFilters,
    fetchData, changePage, confirmDelete, executeDelete, bulkDelete, exportFile,
  }
}
