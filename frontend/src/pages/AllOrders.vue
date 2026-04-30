<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import * as XLSX from 'xlsx-js-style'
import Header from '../components/sections/Header.vue'
import Footer from '../components/sections/Footer.vue'
import Button from '../components/ui/Button.vue'
import Typography from '../components/ui/Typography.vue'
import { useToast } from '../composables/useToast'
import { ChevronDown, X } from 'lucide-vue-next'

interface AdminOrderItem {
  product_name: string
  quantity: number
  size_override?: string | null
}

interface AdminOrder {
  id: number
  created_at: string
  status: string
  order_type?: string | null
  total_amount?: string | number | null
  parent_full_name?: string
  parent_phone?: string
  parent_email?: string
  child_full_name?: string
  settlement?: string
  school?: string
  /** Размер по таблице (заказ); подставляется в позицию, если нет size_override */
  size_from_table?: string
  items?: AdminOrderItem[]
  user?: {
    id: number
    name: string
    email: string
  } | null
}

const router = useRouter()
const route = useRoute()
const { showToast } = useToast()

const loading = ref(false)
const orders = ref<AdminOrder[]>([])
const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 25,
  total: 0,
})

/** Общий поиск: номер заказа, ФИО, телефон, почта, ребёнок, школа */
const searchQuery = ref('')
const dateFrom = ref('')
const dateTo = ref('')
const sortByDate = ref<'new' | 'old'>('new')
const statusFilter = ref<'all' | 'pending' | 'pending_payment' | 'confirmed' | 'processing' | 'production' | 'partially_refunded' | 'refunded' | 'completed' | 'cancelled' | 'payment_cancelled'>('all')
const orderTypeFilter = ref<'all' | 'custom_tailoring' | 'ready_to_wear'>('all')
const showDateSortMenu = ref(false)
const showStatusMenu = ref(false)
const showOrderTypeMenu = ref(false)

function isAdminTabActive(path: '/admin' | '/admin/orders' | '/admin/products'): boolean {
  if (path === '/admin/orders') return route.path.startsWith('/admin/orders')
  return route.path === path
}

function getStoredToken(): string | null {
  return localStorage.getItem('auth_token') || localStorage.getItem('token')
}

let reloadTimer: ReturnType<typeof setTimeout> | null = null

function formatDate(date: string): string {
  const parsed = new Date(date)
  if (Number.isNaN(parsed.getTime())) return date
  return parsed.toLocaleDateString('ru-RU')
}

function formatOrderTotal(raw: string | number | null | undefined): string {
  if (raw === null || raw === undefined || raw === '') return '—'
  const n = typeof raw === 'number' ? raw : parseFloat(String(raw))
  if (!Number.isFinite(n)) return '—'
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  }).format(n)
}

function openOrder(orderId: number) {
  router.push(`/admin/orders/${orderId}`)
}

/** Подписи статусов для экспорта (как в кабинете / админке) */
const STATUS_LABEL_RU: Record<string, string> = {
  pending: 'Ожидает',
  pending_payment: 'Ожидает',
  confirmed: 'Подтверждён',
  processing: 'В работе',
  production: 'В производстве',
  partially_refunded: 'Частично возвращён',
  refunded: 'Возвращён',
  completed: 'Выполнен',
  cancelled: 'Отменён',
  payment_cancelled: 'Отменён',
}

function statusLabelRu(status: string): string {
  const k = String(status).toLowerCase().replace(/\s+/g, '_')
  return STATUS_LABEL_RU[k] ?? status
}

function statusFilterLabel(): string {
  if (statusFilter.value === 'all') return 'Все'
  return statusLabelRu(statusFilter.value)
}

function setDateSort(next: 'new' | 'old') {
  sortByDate.value = next
  showDateSortMenu.value = false
  pagination.value.current_page = 1
  void loadAllOrders()
}

function setStatusFilter(next: typeof statusFilter.value) {
  statusFilter.value = next
  showStatusMenu.value = false
  pagination.value.current_page = 1
  void loadAllOrders()
}

function setOrderTypeFilter(next: typeof orderTypeFilter.value) {
  orderTypeFilter.value = next
  showOrderTypeMenu.value = false
  pagination.value.current_page = 1
  void loadAllOrders()
}

function resetAllFilters() {
  searchQuery.value = ''
  dateFrom.value = ''
  dateTo.value = ''
  sortByDate.value = 'new'
  statusFilter.value = 'all'
  orderTypeFilter.value = 'all'
  showDateSortMenu.value = false
  showStatusMenu.value = false
  showOrderTypeMenu.value = false
  pagination.value.current_page = 1
  void loadAllOrders()
}

function handleGlobalClick(event: MouseEvent) {
  const target = event.target as HTMLElement | null
  if (!target) return
  if (
    target.closest('[data-admin-date-filter]') ||
    target.closest('[data-admin-status-filter]') ||
    target.closest('[data-admin-order-type-filter]')
  ) {
    return
  }
  showDateSortMenu.value = false
  showStatusMenu.value = false
  showOrderTypeMenu.value = false
}

function handleGlobalKeydown(event: KeyboardEvent) {
  if (event.key === 'Escape') {
    showDateSortMenu.value = false
    showStatusMenu.value = false
    showOrderTypeMenu.value = false
  }
}

function orderTypeLabel(orderType: string | null | undefined): string {
  const v = String(orderType ?? '').toLowerCase()
  if (v === 'ready_to_wear') return 'Готовая одежда'
  return 'Пошив'
}

function orderTypeFilterLabel(): string {
  if (orderTypeFilter.value === 'all') return 'Все'
  if (orderTypeFilter.value === 'ready_to_wear') return 'Готовая одежда'
  return 'Пошив'
}

function orderTypeFilterApiValue(): string | undefined {
  if (orderTypeFilter.value === 'all') return undefined
  return orderTypeFilter.value
}

const STATUS_BADGE_CLASS: Record<string, string> = {
  pending: 'bg-amber-50 text-amber-800 border-amber-200',
  pending_payment: 'bg-amber-50 text-amber-800 border-amber-200',
  confirmed: 'bg-sky-50 text-sky-800 border-sky-200',
  processing: 'bg-sky-50 text-sky-800 border-sky-200',
  production: 'bg-sky-50 text-sky-800 border-sky-200',
  partially_refunded: 'bg-amber-50 text-amber-800 border-amber-200',
  refunded: 'bg-neutral-100 text-neutral-700 border-neutral-200',
  completed: 'bg-violet-50 text-violet-800 border-violet-200',
  cancelled: 'bg-neutral-100 text-neutral-700 border-neutral-200',
  payment_cancelled: 'bg-neutral-100 text-neutral-700 border-neutral-200',
}

function statusBadgeClass(status: string): string {
  const key = String(status).toLowerCase().replace(/\s+/g, '_')
  return STATUS_BADGE_CLASS[key] ?? 'bg-neutral-100 text-neutral-800 border-neutral-200'
}

function orderTotalNumber(raw: string | number | null | undefined): number | '' {
  if (raw === null || raw === undefined || raw === '') return ''
  const n = typeof raw === 'number' ? raw : parseFloat(String(raw))
  return Number.isFinite(n) ? n : ''
}

function exportOrdersXlsxFileName(): string {
  const d = new Date()
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `zakazy_${day}-${m}-${y}.xlsx`
}

/** Заказы, которые не должны попадать в сводку «для производства» */
const PRODUCTION_EXCLUDED_STATUSES = new Set(['cancelled', 'completed', 'refunded'])

function effectiveLineSize(item: AdminOrderItem, order: AdminOrder): string {
  const o = item.size_override != null ? String(item.size_override).trim() : ''
  if (o) return o
  const t = (order.size_from_table || '').trim()
  return t || '—'
}

function compareSizeLabels(a: string, b: string): number {
  const da = /^\d+$/.test(a)
  const db = /^\d+$/.test(b)
  if (da && db) return parseInt(a, 10) - parseInt(b, 10)
  return a.localeCompare(b, 'ru', { numeric: true })
}

/**
 * Агрегация позиций order_items по (product_name + размер):
 * размер = size_override строки, иначе size_from_table заказа.
 */
function aggregateProductionForOrders(list: AdminOrder[]) {
  const map = new Map<string, { product_name: string; size: string; quantity: number }>()

  for (const order of list) {
    const st = String(order.status || '').toLowerCase()
    if (PRODUCTION_EXCLUDED_STATUSES.has(st)) continue

    for (const item of order.items ?? []) {
      const name = String(item.product_name || '').trim()
      if (!name) continue
      const qty = Math.max(0, Math.floor(Number(item.quantity)) || 0)
      if (qty <= 0) continue
      const size = effectiveLineSize(item, order)
      const key = `${name}\u0000${size}`
      const cur = map.get(key)
      if (cur) cur.quantity += qty
      else map.set(key, { product_name: name, size, quantity: qty })
    }
  }

  const rows = [...map.values()]
  rows.sort((x, y) => {
    const c = x.product_name.localeCompare(y.product_name, 'ru')
    if (c !== 0) return c
    return compareSizeLabels(x.size, y.size)
  })

  return rows.map((r) => ({
    ...r,
    summary_line: `${r.product_name}, размер ${r.size} — ${r.quantity} шт`,
  }))
}

function exportProductionXlsxFileName(): string {
  const d = new Date()
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `proizvodstvo_${day}-${m}-${y}.xlsx`
}

/** Сводка по товарам и размерам для производства (.xlsx). Учитываются заказы из текущей выборки (фильтры даты и поиска). */
function exportProductionToXlsx(list: AdminOrder[]) {
  if (list.length === 0) {
    showToast('Нет заказов в текущей выборке', 'error')
    return
  }

  const agg = aggregateProductionForOrders(list)
  if (agg.length === 0) {
    showToast('Нет позиций: проверьте состав заказов или исключённые статусы (отменён, завершён)', 'error')
    return
  }

  const rows = agg.map((r) => ({
    'Товар / модель': r.product_name,
    Размер: r.size,
    'Кол-во (шт.)': r.quantity,
    'Сводная строка': r.summary_line,
  }))

  const ws = XLSX.utils.json_to_sheet(rows)
  const wb = XLSX.utils.book_new()
  XLSX.utils.book_append_sheet(wb, ws, 'Производство')
  XLSX.writeFile(wb, exportProductionXlsxFileName())
  showToast('Сводка для производства скачана', 'success')
}

/** Жирные заголовки и выравнивание по левому краю для листа экспорта заказов. */
function applyOrdersExportSheetLayout(ws: XLSX.WorkSheet) {
  const ref = ws['!ref']
  if (!ref) return
  const range = XLSX.utils.decode_range(ref)
  const leftAlign = { horizontal: 'left' as const, vertical: 'center' as const, wrapText: true }
  for (let r = range.s.r; r <= range.e.r; r++) {
    for (let c = range.s.c; c <= range.e.c; c++) {
      const addr = XLSX.utils.encode_cell({ r, c })
      const cell = ws[addr]
      if (!cell) continue
      cell.s = {
        alignment: leftAlign,
        ...(r === 0 ? { font: { bold: true } } : {}),
      }
    }
  }
}

/** Выгрузка всех загруженных заказов в Excel (.xlsx) через SheetJS. */
function exportOrdersToXlsx(list: AdminOrder[]) {
  if (list.length === 0) {
    showToast('Нет заказов для экспорта', 'error')
    return
  }

  const rows = list.map((o) => ({
    '№ заказа': o.id,
    Дата: formatDate(o.created_at),
    Статус: statusLabelRu(o.status || ''),
    'Сумма (руб.)': orderTotalNumber(o.total_amount),
    Заказчик: o.parent_full_name || o.user?.name || '',
    Ребёнок: o.child_full_name || '',
    Телефон: o.parent_phone || '',
    Email: o.parent_email || o.user?.email || '',
    'Населённый пункт': o.settlement || '',
    Школа: o.school || '',
  }))

  const ws = XLSX.utils.json_to_sheet(rows)
  applyOrdersExportSheetLayout(ws)
  const wb = XLSX.utils.book_new()
  XLSX.utils.book_append_sheet(wb, ws, 'Заказы')
  XLSX.writeFile(wb, exportOrdersXlsxFileName())
  showToast('Файл скачан', 'success')
}

async function fetchAllOrdersForExport(): Promise<AdminOrder[]> {
  const token = getStoredToken()
  if (!token) {
    return []
  }

  const all: AdminOrder[] = []
  let page = 1
  let lastPage = 1
  do {
    const response = await axios.get('/api/admin/orders', {
      params: {
        page,
        per_page: 100,
        search: searchQuery.value.trim() || undefined,
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
        sort: sortByDate.value,
        status: statusFilter.value,
        order_type: orderTypeFilterApiValue(),
      },
      headers: {
        Accept: 'application/json',
        Authorization: `Bearer ${token}`,
      },
    })

    const rows = Array.isArray(response.data?.orders) ? response.data.orders : []
    all.push(...rows)
    const p = response.data?.pagination
    lastPage = Number(p?.last_page ?? page)
    page += 1
  } while (page <= lastPage)

  return all
}

async function handleExportOrders() {
  try {
    const list = await fetchAllOrdersForExport()
    exportOrdersToXlsx(list)
  } catch {
    showToast('Не удалось выгрузить заказы', 'error')
  }
}

async function handleExportProduction() {
  try {
    const list = await fetchAllOrdersForExport()
    exportProductionToXlsx(list)
  } catch {
    showToast('Не удалось выгрузить сводку', 'error')
  }
}

async function loadAllOrders() {
  const token = getStoredToken()
  if (!token) {
    router.push('/login')
    return
  }

  loading.value = true
  try {
    const response = await axios.get('/api/admin/orders', {
      params: {
        page: pagination.value.current_page,
        per_page: pagination.value.per_page,
        search: searchQuery.value.trim() || undefined,
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
        sort: sortByDate.value,
        status: statusFilter.value,
        order_type: orderTypeFilterApiValue(),
      },
      headers: {
        Accept: 'application/json',
        Authorization: `Bearer ${token}`,
      },
    })

    orders.value = Array.isArray(response.data?.orders) ? response.data.orders : []
    const p = response.data?.pagination ?? {}
    pagination.value.current_page = Number(p.current_page ?? 1)
    pagination.value.last_page = Number(p.last_page ?? 1)
    pagination.value.per_page = Number(p.per_page ?? 25)
    pagination.value.total = Number(p.total ?? orders.value.length)
  } catch (err: any) {
    if (err?.response?.status === 401) {
      router.push('/login')
      return
    }
    if (err?.response?.status === 403) {
      router.push('/')
      return
    }
    showToast('Не удалось загрузить заказы', 'error')
  } finally {
    loading.value = false
  }
}

function goToPage(page: number) {
  if (page < 1 || page > pagination.value.last_page) return
  pagination.value.current_page = page
  void loadAllOrders()
}

watch([searchQuery, dateFrom, dateTo], () => {
  if (reloadTimer) clearTimeout(reloadTimer)
  reloadTimer = setTimeout(() => {
    pagination.value.current_page = 1
    void loadAllOrders()
  }, 350)
})

onMounted(() => {
  loadAllOrders()
  document.addEventListener('click', handleGlobalClick)
  document.addEventListener('keydown', handleGlobalKeydown)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleGlobalClick)
  document.removeEventListener('keydown', handleGlobalKeydown)
})
</script>

<template>
  <Header />
  <section class="relative w-full min-h-screen font-sans text-slate-900">
    <div class="max-w-[96rem] mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <Typography as="h1" class="text-3xl md:text-4xl font-light">Все заказы</Typography>

      <div class="mt-2 mb-8 flex flex-wrap gap-3">
        <Button
          :variant="isAdminTabActive('/admin/orders') ? 'primary' : 'secondary'"
          size="sm"
          @click="router.push('/admin/orders')"
        >
          Управление заказами
        </Button>
        <Button
          :variant="isAdminTabActive('/admin/products') ? 'primary' : 'secondary'"
          size="sm"
          @click="router.push('/admin/products')"
        >
          Управление товарами
        </Button>
        <Button :variant="isAdminTabActive('/admin') ? 'primary' : 'secondary'" size="sm" @click="router.push('/admin')">
          Управление администраторами
        </Button>
      </div>

      <div class="rounded-lg border border-neutral-200 p-4">
        <div v-if="loading" class="text-slate-600">Загрузка...</div>
        <div v-else-if="orders.length === 0" class="text-slate-600">Заказов пока нет</div>

        <template v-else>
          <div class="mb-4 flex flex-col gap-3 md:flex-row md:flex-wrap md:items-end">
            <div class="flex-1 min-w-[200px]">
              <label class="block text-xs font-medium text-slate-600 mb-1" for="all-orders-search">Поиск</label>
              <input
                id="all-orders-search"
                v-model="searchQuery"
                type="search"
                placeholder="Например: 12 или #12 — номер заказа; или фамилия, email, телефон"
                class="search-input w-full border border-neutral-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900"
              />
            </div>
            <div>
              <label class="block text-xs font-medium text-slate-600 mb-1" for="date-from">Дата с</label>
              <input
                id="date-from"
                v-model="dateFrom"
                type="date"
                class="w-full border border-neutral-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900 cursor-pointer"
              />
            </div>
            <div>
              <label class="block text-xs font-medium text-slate-600 mb-1" for="date-to">Дата по</label>
              <input
                id="date-to"
                v-model="dateTo"
                type="date"
                class="w-full border border-neutral-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900 cursor-pointer"
              />
            </div>
            <div class="flex items-end">
              <Button variant="outline" size="sm" class="w-full sm:w-auto" @click="resetAllFilters">
                <X :size="14" class="mr-1" />
                Сбросить фильтры
              </Button>
            </div>
            <div class="flex flex-col sm:flex-row items-stretch sm:items-end gap-2">
              <Button
                variant="primary"
                size="sm"
                class="w-full sm:w-auto !border !border-[#185c37] !bg-[#217346] !text-white !shadow-sm hover:!bg-[#1a5c38] hover:!border-[#14532f] focus-visible:!ring-[#217346]/45"
                @click="handleExportOrders"
              >
                Экспорт всех заказов
              </Button>
              <Button
                variant="primary"
                size="sm"
                class="w-full sm:w-auto !border !border-[#185c37] !bg-[#217346] !text-white !shadow-sm hover:!bg-[#1a5c38] hover:!border-[#14532f] focus-visible:!ring-[#217346]/45"
                @click="handleExportProduction"
              >
                Экспорт для производства
              </Button>
            </div>
          </div>

          <p v-if="orders.length === 0" class="text-slate-600 mb-4">Ничего не найдено по фильтрам</p>

          <div v-else class="overflow-x-auto overflow-y-visible">
            <table class="w-full min-w-[1100px] text-left border-collapse">
              <thead>
                <tr class="border-b border-slate-300">
                  <th class="py-2 px-3">№</th>
                  <th class="py-2 px-3 relative" data-admin-date-filter>
                    <button
                      type="button"
                      class="inline-flex items-center gap-1 font-semibold text-slate-900 hover:text-slate-700 cursor-pointer"
                      @click.stop="showDateSortMenu = !showDateSortMenu; showStatusMenu = false"
                    >
                      Дата
                      <ChevronDown :size="16" :class="showDateSortMenu ? 'rotate-180' : ''" class="transition-transform" />
                    </button>
                    <div
                      v-if="showDateSortMenu"
                      class="absolute left-3 top-full z-20 mt-2 w-44 rounded-md border border-neutral-200 bg-white p-1 shadow-lg"
                    >
                      <button
                        type="button"
                        class="block w-full rounded px-2 py-1.5 text-left text-sm hover:bg-neutral-100"
                        :class="sortByDate === 'new' ? 'bg-neutral-100 text-slate-900 font-medium' : 'text-slate-700'"
                        @click.stop="setDateSort('new')"
                      >
                        Сначала новые
                      </button>
                      <button
                        type="button"
                        class="block w-full rounded px-2 py-1.5 text-left text-sm hover:bg-neutral-100"
                        :class="sortByDate === 'old' ? 'bg-neutral-100 text-slate-900 font-medium' : 'text-slate-700'"
                        @click.stop="setDateSort('old')"
                      >
                        Сначала старые
                      </button>
                    </div>
                  </th>
                  <th class="py-2 px-3 relative" data-admin-order-type-filter>
                    <button
                      type="button"
                      class="inline-flex items-center gap-1 font-semibold text-slate-900 hover:text-slate-700 cursor-pointer"
                      @click.stop="showOrderTypeMenu = !showOrderTypeMenu; showDateSortMenu = false; showStatusMenu = false"
                    >
                      Тип
                      <ChevronDown :size="16" :class="showOrderTypeMenu ? 'rotate-180' : ''" class="transition-transform" />
                    </button>
                    <div
                      v-if="showOrderTypeMenu"
                      class="absolute left-3 top-full z-20 mt-2 w-52 rounded-md border border-neutral-200 bg-white p-1 shadow-lg"
                    >
                      <button
                        type="button"
                        class="block w-full rounded px-2 py-1.5 text-left text-sm hover:bg-neutral-100"
                        :class="orderTypeFilter === 'all' ? 'bg-neutral-100 text-slate-900 font-medium' : 'text-slate-700'"
                        @click.stop="setOrderTypeFilter('all')"
                      >
                        Все
                      </button>
                      <button
                        type="button"
                        class="block w-full rounded px-2 py-1.5 text-left text-sm hover:bg-neutral-100"
                        :class="orderTypeFilter === 'custom_tailoring' ? 'bg-neutral-100 text-slate-900 font-medium' : 'text-slate-700'"
                        @click.stop="setOrderTypeFilter('custom_tailoring')"
                      >
                        Пошив
                      </button>
                      <button
                        type="button"
                        class="block w-full rounded px-2 py-1.5 text-left text-sm hover:bg-neutral-100"
                        :class="orderTypeFilter === 'ready_to_wear' ? 'bg-neutral-100 text-slate-900 font-medium' : 'text-slate-700'"
                        @click.stop="setOrderTypeFilter('ready_to_wear')"
                      >
                        Готовая одежда
                      </button>
                    </div>
                    <span v-if="orderTypeFilter !== 'all'" class="ml-2 text-xs text-slate-500">({{ orderTypeFilterLabel() }})</span>
                  </th>
                  <th class="py-2 px-3 relative" data-admin-status-filter>
                    <button
                      type="button"
                      class="inline-flex items-center gap-1 font-semibold text-slate-900 hover:text-slate-700 cursor-pointer"
                      @click.stop="showStatusMenu = !showStatusMenu; showDateSortMenu = false; showOrderTypeMenu = false"
                    >
                      Статус
                      <ChevronDown :size="16" :class="showStatusMenu ? 'rotate-180' : ''" class="transition-transform" />
                    </button>
                    <div
                      v-if="showStatusMenu"
                      class="absolute left-3 top-full z-20 mt-2 w-52 rounded-md border border-neutral-200 bg-white p-1 shadow-lg"
                    >
                      <button
                        type="button"
                        class="block w-full rounded px-2 py-1.5 text-left text-sm hover:bg-neutral-100"
                        :class="statusFilter === 'all' ? 'bg-neutral-100 text-slate-900 font-medium' : 'text-slate-700'"
                        @click.stop="setStatusFilter('all')"
                      >
                        Все
                      </button>
                      <button
                        v-for="st in ['pending','pending_payment','confirmed','processing','production','partially_refunded','refunded','completed','cancelled','payment_cancelled']"
                        :key="st"
                        type="button"
                        class="block w-full rounded px-2 py-1.5 text-left text-sm hover:bg-neutral-100"
                        :class="statusFilter === st ? 'bg-neutral-100 text-slate-900 font-medium' : 'text-slate-700'"
                        @click.stop="setStatusFilter(st as any)"
                      >
                        {{ statusLabelRu(st) }}
                      </button>
                    </div>
                    <span v-if="statusFilter !== 'all'" class="ml-2 text-xs text-slate-500">({{ statusFilterLabel() }})</span>
                  </th>
                  <th class="py-2 px-3">Заказчик</th>
                  <th class="py-2 px-3">Телефон</th>
                  <th class="py-2 px-3">Email</th>
                  <th class="py-2 px-3">Город</th>
                  <th class="py-2 px-3">Сумма</th>
                  <th class="py-2 px-3">Школа</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="order in orders"
                  :key="order.id"
                  class="border-b border-slate-200 hover:bg-slate-50 cursor-pointer"
                  @click="openOrder(order.id)"
                >
                  <td class="py-2 px-3 whitespace-nowrap">#{{ order.id }}</td>
                  <td class="py-2 px-3 whitespace-nowrap">{{ formatDate(order.created_at) }}</td>
                  <td class="py-2 px-3 whitespace-nowrap">{{ orderTypeLabel(order.order_type) }}</td>
                  <td class="py-2 px-3 whitespace-nowrap">
                    <span
                      class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-medium"
                      :class="statusBadgeClass(order.status)"
                    >
                      {{ statusLabelRu(order.status) }}
                    </span>
                  </td>
                  <td class="py-2 px-3">{{ order.parent_full_name || order.user?.name || '-' }}</td>
                  <td class="py-2 px-3 whitespace-nowrap">{{ order.parent_phone || '-' }}</td>
                  <td class="py-2 px-3">{{ order.parent_email || order.user?.email || '-' }}</td>
                  <td class="py-2 px-3">{{ order.settlement || '-' }}</td>
                  <td class="py-2 px-3 whitespace-nowrap">{{ formatOrderTotal(order.total_amount) }}</td>
                  <td class="py-2 px-3">
                    <div class="flex items-center justify-between gap-2 min-w-[170px]">
                      <span>{{ order.school || '-' }}</span>
                      <Button size="sm" variant="primary" @click.stop="openOrder(order.id)">Открыть</Button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div
            v-if="pagination.last_page > 1"
            class="mt-4 flex flex-wrap items-center justify-between gap-3 text-sm text-slate-600"
          >
            <span>
              Страница {{ pagination.current_page }} из {{ pagination.last_page }} · Всего: {{ pagination.total }}
            </span>
            <div class="flex items-center gap-2">
              <Button
                variant="outline"
                size="sm"
                :disabled="pagination.current_page <= 1"
                @click="goToPage(pagination.current_page - 1)"
              >
                Назад
              </Button>
              <Button
                variant="outline"
                size="sm"
                :disabled="pagination.current_page >= pagination.last_page"
                @click="goToPage(pagination.current_page + 1)"
              >
                Вперёд
              </Button>
            </div>
          </div>
        </template>
      </div>
    </div>
  </section>
  <Footer />
</template>

<style scoped>
.search-input::-webkit-search-cancel-button {
  cursor: pointer;
}

#sort-date,
#sort-date option,
#date-from,
#date-to {
  cursor: pointer;
}

#date-from::-webkit-calendar-picker-indicator,
#date-to::-webkit-calendar-picker-indicator {
  cursor: pointer;
}
</style>
