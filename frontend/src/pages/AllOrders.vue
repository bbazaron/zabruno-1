<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import Header from '../components/sections/Header.vue'
import Footer from '../components/sections/Footer.vue'
import Button from '../components/ui/Button.vue'
import Typography from '../components/ui/Typography.vue'
import { useToast } from '../composables/useToast'

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
  return `${y}-${m}-${day}`
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

      <div class="mt-2 mb-8 flex gap-3">
        <Button variant="secondary" size="sm" @click="router.push('/admin')">
          Управление администраторами
        </Button>
        <Button variant="secondary" size="sm" @click="router.push('/admin/orders')">
          Управление заказами
        </Button>
      </div>

      <div class="rounded-lg border border-neutral-200 p-4">
        <div v-if="loading" class="text-slate-600">Загрузка...</div>
        <div v-else-if="orders.length === 0" class="text-slate-600">Заказов пока нет</div>

        <template v-else>
          <p class="text-xs text-slate-500 mb-3">
            Фильтры работают сразу при вводе — кнопку Enter нажимать не нужно. Поиск здесь только на этой странице со списком заказов (не внутри карточки одного заказа).
          </p>
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
