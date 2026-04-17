<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import * as XLSX from 'xlsx-js-style'
import Header from '../components/sections/Header.vue'
import Footer from '../components/sections/Footer.vue'
import Button from '../components/ui/Button.vue'
import Typography from '../components/ui/Typography.vue'
import { useToast } from '../composables/useToast'

interface AdminOrderItem {
  product_name: string
  quantity: number
  size_override?: string | null
}

interface AdminOrder {
  id: number
  created_at: string
  status: string
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
const { showToast } = useToast()

const loading = ref(false)
const orders = ref<AdminOrder[]>([])

/** Общий поиск: номер заказа, дата (как в таблице), ФИО, телефон, почта, ребёнок, школа */
const searchQuery = ref('')
/** Фильтр по дате создания: локальная дата `YYYY-MM-DD` сравнивается с датой заказа */
const dateFrom = ref('')
const dateTo = ref('')
/** Сортировка по дате создания заказа */
const sortByDate = ref<'new' | 'old'>('new')

function getStoredToken(): string | null {
  return localStorage.getItem('auth_token') || localStorage.getItem('token')
}

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

/** Дата заказа в локальном календаре YYYY-MM-DD для сравнения с input type="date" */
function orderLocalDateKey(iso: string): string {
  const d = new Date(iso)
  if (Number.isNaN(d.getTime())) return ''
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${day}-${m}-${y}`
}

function normalizeSearchInput(raw: string): string {
  return raw.trim().toLowerCase().replace(/^#+\s*/, '')
}

function sortOrdersByDate(list: AdminOrder[]): AdminOrder[] {
  const out = [...list]
  out.sort((a, b) => {
    const ta = new Date(a.created_at).getTime()
    const tb = new Date(b.created_at).getTime()
    if (Number.isNaN(ta) || Number.isNaN(tb)) return 0
    return sortByDate.value === 'new' ? tb - ta : ta - tb
  })
  return out
}

const filteredOrders = computed(() => {
  let list = orders.value

  const from = dateFrom.value.trim()
  const to = dateTo.value.trim()
  if (from || to) {
    list = list.filter((o) => {
      const key = orderLocalDateKey(o.created_at)
      if (!key) return false
      if (from && key < from) return false
      if (to && key > to) return false
      return true
    })
  }

  const q = normalizeSearchInput(searchQuery.value)
  if (!q) {
    return sortOrdersByDate(list)
  }

  const filtered = list.filter((o) => {
    const idStr = String(o.id)
    const isoDate = orderLocalDateKey(o.created_at)
    const dateStr = formatDate(o.created_at).toLowerCase()
    const parentName = (o.parent_full_name || o.user?.name || '').toLowerCase()
    const phone = (o.parent_phone || '').replace(/\s/g, '')
    const email = (o.parent_email || o.user?.email || '').toLowerCase()
    const child = (o.child_full_name || '').toLowerCase()
    const school = (o.school || '').toLowerCase()
    const status = (o.status || '').toLowerCase()

    const haystack = [
      idStr,
      `#${idStr}`,
      isoDate,
      dateStr,
      parentName,
      phone,
      email,
      child,
      school,
      status,
    ].join(' ')

    if (haystack.includes(q)) return true

    const qDigits = q.replace(/\D/g, '')
    if (qDigits.length >= 3 && phone.includes(qDigits)) return true

    return false
  })

  return sortOrdersByDate(filtered)
})

function openOrder(orderId: number) {
  router.push(`/admin/orders/${orderId}`)
}

/** Подписи статусов для экспорта (как в кабинете / админке) */
const STATUS_LABEL_RU: Record<string, string> = {
  pending: 'В обработке',
  confirmed: 'Подтверждён',
  processing: 'В работе',
  production: 'В производстве',
  completed: 'Завершён',
  cancelled: 'Отменён',
}

function statusLabelRu(status: string): string {
  const k = String(status).toLowerCase().replace(/\s+/g, '_')
  return STATUS_LABEL_RU[k] ?? status
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
const PRODUCTION_EXCLUDED_STATUSES = new Set(['cancelled', 'completed'])

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
function exportProductionToXlsx() {
  const list = filteredOrders.value
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
function exportOrdersToXlsx() {
  const list = orders.value
  if (list.length === 0) {
    showToast('Нет заказов для экспорта', 'error')
    return
  }

  const sorted = sortOrdersByDate([...list])
  const rows = sorted.map((o) => ({
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

async function loadAllOrders() {
  const token = getStoredToken()
  if (!token) {
    router.push('/login')
    return
  }

  loading.value = true
  try {
    const response = await axios.get('/api/admin/orders', {
      headers: {
        Accept: 'application/json',
        Authorization: `Bearer ${token}`,
      },
    })

    orders.value = Array.isArray(response.data?.orders) ? response.data.orders : []
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

onMounted(() => {
  loadAllOrders()
})
</script>

<template>
  <Header />
  <section class="relative w-full min-h-screen font-sans text-slate-900">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <Typography as="h1" class="text-3xl md:text-4xl font-light">Все заказы</Typography>

      <div class="mt-2 mb-8 flex flex-wrap gap-3">
        <Button variant="secondary" size="sm" @click="router.push('/admin')">
          Управление администраторами
        </Button>
        <Button variant="secondary" size="sm" @click="router.push('/admin/orders')">
          Управление заказами
        </Button>
        <Button variant="secondary" size="sm" @click="router.push('/admin/products')">
          Управление товарами
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
                class="w-full border border-neutral-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900"
              />
            </div>
            <div>
              <label class="block text-xs font-medium text-slate-600 mb-1" for="sort-date">Порядок по дате</label>
              <select
                id="sort-date"
                v-model="sortByDate"
                class="w-full min-w-[180px] border border-neutral-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900 bg-white"
              >
                <option value="new">Сначала новые</option>
                <option value="old">Сначала старые</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-medium text-slate-600 mb-1" for="date-from">Дата с</label>
              <input
                id="date-from"
                v-model="dateFrom"
                type="date"
                class="w-full border border-neutral-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900"
              />
            </div>
            <div>
              <label class="block text-xs font-medium text-slate-600 mb-1" for="date-to">Дата по</label>
              <input
                id="date-to"
                v-model="dateTo"
                type="date"
                class="w-full border border-neutral-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900"
              />
            </div>
            <div class="flex flex-col sm:flex-row items-stretch sm:items-end gap-2">
              <Button
                variant="primary"
                size="sm"
                class="w-full sm:w-auto !border !border-[#185c37] !bg-[#217346] !text-white !shadow-sm hover:!bg-[#1a5c38] hover:!border-[#14532f] focus-visible:!ring-[#217346]/45"
                @click="exportOrdersToXlsx"
              >
                Экспорт всех заказов
              </Button>
              <Button
                variant="primary"
                size="sm"
                class="w-full sm:w-auto !border !border-[#185c37] !bg-[#217346] !text-white !shadow-sm hover:!bg-[#1a5c38] hover:!border-[#14532f] focus-visible:!ring-[#217346]/45"
                @click="exportProductionToXlsx"
              >
                Экспорт для производства
              </Button>
            </div>
          </div>

          <p v-if="filteredOrders.length === 0" class="text-slate-600 mb-4">Ничего не найдено по фильтрам</p>

        <table v-else class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-300">
              <th class="py-2 px-3">№</th>
              <th class="py-2 px-3">Дата</th>
              <th class="py-2 px-3">Статус</th>
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
              v-for="order in filteredOrders"
              :key="order.id"
              class="border-b border-slate-200 hover:bg-slate-50 cursor-pointer"
              @click="openOrder(order.id)"
            >
              <td class="py-2 px-3">#{{ order.id }}</td>
              <td class="py-2 px-3">{{ formatDate(order.created_at) }}</td>
              <td class="py-2 px-3">{{ order.status }}</td>
              <td class="py-2 px-3">{{ order.parent_full_name || order.user?.name || '-' }}</td>
              <td class="py-2 px-3">{{ order.parent_phone || '-' }}</td>
              <td class="py-2 px-3">{{ order.parent_email || order.user?.email || '-' }}</td>
              <td class="py-2 px-3">{{ order.settlement || '-' }}</td>
              <td class="py-2 px-3 whitespace-nowrap">{{ formatOrderTotal(order.total_amount) }}</td>
              <td class="py-2 px-3 flex items-center justify-between gap-2">
                <span>{{ order.school || '-' }}</span>
                <Button size="sm" variant="primary" @click.stop="openOrder(order.id)">Открыть</Button>
              </td>
            </tr>
          </tbody>
        </table>
        </template>
      </div>
    </div>
  </section>
  <Footer />
</template>
