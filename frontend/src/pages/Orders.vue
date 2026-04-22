<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import Button from '../components/ui/Button.vue'
import Typography from '../components/ui/Typography.vue'
import Header from '../components/sections/Header.vue'
import Footer from '../components/sections/Footer.vue'
import axios from 'axios'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import { useToast } from '../composables/useToast'
import { useProductLinkResolver } from '../composables/useProductLinkResolver'
import {
  Phone,
  MapPin,
  ChevronDown,
  ChevronUp,
  Package,
  Info,
  Search,
} from 'lucide-vue-next'

interface BackendOrder {
  id: number
  created_at: string
  status: string
  total_amount?: string | number | null
  child_full_name?: string
  parent_full_name?: string
  parent_phone?: string
  school?: string
  settlement?: string
  child_gender?: string
  items?: Array<{
    id: number
    product_name: string
    quantity: number
  }>
}

function getStoredToken(): string | null {
  return localStorage.getItem('auth_token') || localStorage.getItem('token')
}

const router = useRouter()
const route = useRoute()
const { showToast } = useToast()
const { loadProducts, resolveProductId, resolveProductImage, products } = useProductLinkResolver()

function isUserTabActive(path: '/orders' | '/cart'): boolean {
  return route.path === path
}

function productHref(productName: string, orderGender?: string | null): string | null {
  void products.value.length
  const id = resolveProductId(productName, orderGender)
  return id != null ? `/product/${id}` : null
}

function itemThumbnail(
  item: { product_name: string },
  orderGender: string | undefined,
): string | null {
  return resolveProductImage(item.product_name, orderGender)
}

const userName = ref<string>('')
const userMail = ref<string>('')
const userLoading = ref(false)
const ordersLoading = ref(false)
const orders = ref<BackendOrder[]>([])
const expandedByOrderId = ref<Record<number, boolean>>({})

/** Фильтры списка заказов */
const filterOrderOrPhone = ref('')
const filterDate = ref('')

function digitsOnly(s: string): string {
  return s.replace(/\D/g, '')
}

function orderDateLocalYmd(createdAt: string): string {
  const d = new Date(createdAt)
  if (Number.isNaN(d.getTime())) return ''
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

const filteredOrders = computed(() => {
  let list = orders.value

  const textQ = filterOrderOrPhone.value.trim()
  if (textQ) {
    const phoneDigits = digitsOnly(textQ)
    list = list.filter((o) => {
      const idMatch = String(o.id).includes(textQ)
      const phoneMatch =
        phoneDigits.length > 0 &&
        digitsOnly(String(o.parent_phone ?? '')).includes(phoneDigits)
      return idMatch || phoneMatch
    })
  }

  const dateQ = filterDate.value.trim()
  if (dateQ) {
    list = list.filter((o) => orderDateLocalYmd(o.created_at) === dateQ)
  }

  return list
})

const hasActiveFilters = computed(
  () =>
    Boolean(filterOrderOrPhone.value.trim()) ||
    Boolean(filterDate.value.trim()),
)

function clearOrderFilters() {
  filterOrderOrPhone.value = ''
  filterDate.value = ''
}

function isExpanded(orderId: number): boolean {
  return expandedByOrderId.value[orderId] !== false
}

function toggleOrderCard(orderId: number) {
  expandedByOrderId.value = {
    ...expandedByOrderId.value,
    [orderId]: !isExpanded(orderId),
  }
}

async function loadUser() {
  const token = getStoredToken()

  if (!token) {
    router.push('/login')
    return
  }

  userLoading.value = true

  try {
    const response = await axios.get('/api/getUser', {
      headers: {
        Accept: 'application/json',
        Authorization: `Bearer ${token}`,
      },
    })

    userName.value = typeof response.data?.name === 'string' ? response.data.name : ''
    userMail.value = typeof response.data?.mail === 'string' ? response.data.mail : ''
  } catch (err: any) {
    if (err?.response?.status === 401) {
      router.push('/login')
      return
    }

    showToast('Не удалось загрузить данные пользователя', 'error')
  } finally {
    userLoading.value = false
  }
}

async function loadOrders() {
  const token = getStoredToken()

  if (!token) {
    router.push('/login')
    return
  }

  ordersLoading.value = true
  try {
    const response = await axios.get('/api/getOrders', {
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

    showToast('Не удалось загрузить заказы', 'error')
  } finally {
    ordersLoading.value = false
  }
}

function formatDate(date: string): string {
  const parsed = new Date(date)
  if (Number.isNaN(parsed.getTime())) {
    return date
  }

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

function formatMoney(n: number): string {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  }).format(n)
}

/** Доля суммы заказа по позиции (пропорционально количеству), если общая сумма известна */
function lineAmount(order: BackendOrder, quantity: number): string | null {
  const raw = order.total_amount
  if (raw === null || raw === undefined || raw === '') return null
  const total = typeof raw === 'number' ? raw : parseFloat(String(raw))
  if (!Number.isFinite(total) || total <= 0) return null
  const items = order.items ?? []
  const sumQty = items.reduce((s, i) => s + i.quantity, 0)
  if (sumQty <= 0) return null
  return formatMoney((total * quantity) / sumQty)
}

function formatPhoneDisplay(phone: string | undefined): string {
  if (!phone || !String(phone).trim()) return '—'
  return String(phone).trim()
}

function pickupLabel(order: BackendOrder): string {
  const parts = [order.settlement, order.school].filter(Boolean) as string[]
  if (parts.length === 0) return '—'
  return parts.join(', ')
}

const STATUS_BADGE: Record<string, { label: string; class: string }> = {
  pending: { label: 'В обработке', class: 'bg-amber-50 text-amber-800 border-amber-200' },
  confirmed: { label: 'Подтверждён', class: 'bg-sky-50 text-sky-800 border-sky-200' },
  processing: { label: 'В работе', class: 'bg-sky-50 text-sky-800 border-sky-200' },
  production: { label: 'В производстве', class: 'bg-sky-50 text-sky-800 border-sky-200' },
  completed: { label: 'Завершён', class: 'bg-violet-50 text-violet-800 border-violet-200' },
  cancelled: { label: 'Отменён', class: 'bg-neutral-100 text-neutral-700 border-neutral-200' },
}

function statusBadge(status: string): { label: string; class: string } {
  const key = String(status).toLowerCase().replace(/\s+/g, '_')
  return (
    STATUS_BADGE[key] ?? {
      label: status,
      class: 'bg-neutral-100 text-neutral-800 border-neutral-200',
    }
  )
}

function openOrder(orderId: number) {
  router.push(`/orders/${orderId}`)
}

function goToProfile() {
  router.push('/profile')
}

onMounted(() => {
  loadUser()
  loadOrders()
  void loadProducts()
})
</script>

<template>
  <Header />

  <section class="bg-white border-b border-neutral-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6 md:pt-8 pb-8 md:pb-10">
      <div class="text-sm text-slate-600">
        <RouterLink to="/" class="hover:text-slate-900 transition-colors">Главная</RouterLink>
        <span class="mx-2">/</span>
        <span class="text-slate-900 font-medium">Мои заказы</span>
      </div>

      <div
        class="flex flex-col gap-6 sm:flex-row sm:items-start sm:justify-between sm:gap-8 mt-4 md:mt-5"
      >
        <div class="min-w-0 flex-1">
          <h1 class="text-3xl md:text-4xl font-bold leading-tight text-slate-900">
            Профиль пользователя
          </h1>
          <Typography as="p" class="text-slate-600 mt-2 text-sm md:text-base leading-relaxed">
            Просмотр и управление вашими заказами
          </Typography>

          <Typography
            v-if="userLoading"
            as="p"
            class="text-slate-600 mt-4 text-sm"
          >
            Загрузка профиля...
          </Typography>

          <Typography
            v-else-if="userName"
            as="p"
            class="text-slate-800 mt-4 text-sm md:text-base"
          >
            {{ userName }}<span v-if="userMail" class="text-slate-600"> · {{ userMail }}</span>
          </Typography>
        </div>
        <div class="flex flex-wrap gap-3 self-start">
          <Button
            :variant="isUserTabActive('/cart') ? 'primary' : 'secondary'"
            size="sm"
            @click="router.push('/cart')"
          >
            Корзина
          </Button>
          <Button
            :variant="isUserTabActive('/orders') ? 'primary' : 'secondary'"
            size="sm"
            @click="router.push('/orders')"
          >
            Мои заказы
          </Button>
          <Button variant="secondary" size="sm" @click="goToProfile">
            Редактировать профиль
          </Button>
        </div>
      </div>
    </div>
  </section>

  <section class="relative w-full min-h-screen font-sans text-slate-900 bg-neutral-50/80">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 md:py-14">
      <!-- Секция заказов -->
      <h2
        class="text-2xl md:text-3xl font-semibold tracking-tight text-slate-900 mb-5 md:mb-6"
      >
        Заказы
      </h2>

      <!-- Поиск по заказам -->
      <div
        v-if="!ordersLoading && orders.length > 0"
        class="mb-6 rounded-xl border border-neutral-200 bg-white p-4 md:p-5 shadow-sm"
      >
        <div class="flex flex-wrap items-center gap-2 mb-3 md:mb-4">
          <Search :size="18" class="text-slate-500 shrink-0" aria-hidden="true" />
          <span class="text-sm font-medium text-slate-800">Поиск заказов</span>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 md:gap-4">
          <label class="block min-w-0 sm:col-span-2 lg:col-span-1">
            <span class="block text-xs font-medium text-slate-600 mb-1.5">Номер заказа или телефон</span>
            <input
              v-model="filterOrderOrPhone"
              type="text"
              inputmode="text"
              autocomplete="off"
              placeholder="Номер заказа или номер телефона"
              class="w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900"
            />
          </label>
          <label class="block min-w-0">
            <span class="block text-xs font-medium text-slate-600 mb-1.5">Дата заказа</span>
            <input
              v-model="filterDate"
              type="date"
              class="w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900 [color-scheme:light]"
            />
          </label>
          <div class="flex items-end min-w-0">
            <Button
              v-if="hasActiveFilters"
              type="button"
              variant="outline"
              size="sm"
              class="w-full sm:w-auto"
              @click="clearOrderFilters"
            >
              Сбросить
            </Button>
          </div>
        </div>
      </div>

      <div v-if="ordersLoading" class="py-12 text-center text-slate-600">
        Загрузка заказов...
      </div>

      <div v-else-if="orders.length === 0" class="py-12 text-center text-slate-600 rounded-xl border border-dashed border-neutral-300 bg-white">
        У вас пока нет заказов
      </div>

      <div
        v-else-if="filteredOrders.length === 0"
        class="py-12 text-center text-slate-600 rounded-xl border border-dashed border-neutral-300 bg-white"
      >
        По заданным условиям заказов не найдено. Попробуйте изменить фильтры.
      </div>

      <div v-else class="space-y-5">
        <article
          v-for="order in filteredOrders"
          :key="order.id"
          class="rounded-xl border border-neutral-200 bg-white shadow-sm overflow-hidden"
        >
          <!-- Шапка карточки -->
          <div
            class="flex items-start justify-between gap-3 px-4 py-4 md:px-5 md:py-5 border-b border-neutral-100"
          >
            <div class="flex flex-wrap items-center gap-2 min-w-0">
              <button
                type="button"
                class="font-semibold text-slate-900 text-sm md:text-base leading-snug hover:text-slate-700 hover:underline underline-offset-2 cursor-pointer"
                @click="openOrder(order.id)"
              >
                Заказ №{{ order.id }} от {{ formatDate(order.created_at) }}
              </button>
              <span
                class="inline-flex items-center gap-1 rounded-full border px-2.5 py-0.5 text-xs font-medium"
                :class="statusBadge(order.status).class"
              >
                {{ statusBadge(order.status).label }}
                <Info :size="12" class="opacity-70 shrink-0" aria-hidden="true" />
              </span>
            </div>
            <button
              type="button"
              class="shrink-0 p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-neutral-100 transition-colors"
              :aria-expanded="isExpanded(order.id)"
              :aria-label="isExpanded(order.id) ? 'Свернуть' : 'Развернуть'"
              @click="toggleOrderCard(order.id)"
            >
              <ChevronUp v-if="isExpanded(order.id)" :size="22" />
              <ChevronDown v-else :size="22" />
            </button>
          </div>

          <template v-if="isExpanded(order.id)">
            <!-- Контакты и получение -->
            <div
              class="px-4 py-3 md:px-5 md:py-4 grid grid-cols-1 sm:grid-cols-2 gap-3 md:gap-6 border-b border-neutral-100 text-sm"
            >
              <div class="flex items-start gap-2 text-slate-600">
                <Phone :size="18" class="text-slate-400 shrink-0 mt-0.5" aria-hidden="true" />
                <span>
                  <span class="text-slate-500">Телефон:</span>
                  {{ formatPhoneDisplay(order.parent_phone) }}
                </span>
              </div>
              <div class="flex items-start gap-2 text-slate-600">
                <MapPin :size="18" class="text-slate-400 shrink-0 mt-0.5" aria-hidden="true" />
                <span>
                  <span class="text-slate-500">Получение:</span>
                  <span class="text-sky-700 font-medium">{{ pickupLabel(order) }}</span>
                </span>
              </div>
            </div>

            <!-- Позиции -->
            <div class="divide-y divide-neutral-100">
              <div
                v-for="item in order.items ?? []"
                :key="item.id"
                class="flex flex-col sm:flex-row sm:items-start gap-4 px-4 py-4 md:px-5 md:py-5"
              >
                <div
                  class="shrink-0 w-20 h-20 rounded-lg bg-neutral-100 border border-neutral-200 overflow-hidden flex items-center justify-center text-slate-400"
                >
                  <RouterLink
                    v-if="productHref(item.product_name, order.child_gender)"
                    :to="productHref(item.product_name, order.child_gender)!"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="flex h-full w-full items-center justify-center transition-opacity hover:opacity-90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-400 focus-visible:ring-offset-2 rounded-lg"
                  >
                    <img
                      v-if="itemThumbnail(item, order.child_gender)"
                      :src="itemThumbnail(item, order.child_gender)!"
                      :alt="item.product_name"
                      class="h-full w-full object-cover"
                    />
                    <Package
                      v-else
                      :size="32"
                      stroke-width="1.25"
                      aria-hidden="true"
                    />
                  </RouterLink>
                  <template v-else>
                    <img
                      v-if="itemThumbnail(item, order.child_gender)"
                      :src="itemThumbnail(item, order.child_gender)!"
                      :alt="item.product_name"
                      class="h-full w-full object-cover"
                    />
                    <Package
                      v-else
                      :size="32"
                      stroke-width="1.25"
                      aria-hidden="true"
                    />
                  </template>
                </div>
                <div class="flex-1 min-w-0">
                  <RouterLink
                    v-if="productHref(item.product_name, order.child_gender)"
                    :to="productHref(item.product_name, order.child_gender)!"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="font-medium text-slate-900 text-sm md:text-base leading-snug hover:text-slate-700 underline-offset-2 hover:underline inline-block"
                  >
                    {{ item.product_name }}
                  </RouterLink>
                  <p
                    v-else
                    class="font-medium text-slate-900 text-sm md:text-base leading-snug"
                  >
                    {{ item.product_name }}
                  </p>
                </div>
                <div class="text-left sm:text-right shrink-0 sm:pl-4">
                  <p v-if="lineAmount(order, item.quantity)" class="text-sm text-slate-700">
                    <span class="text-slate-500">Сумма:</span>
                    <span class="font-semibold text-slate-900 tabular-nums">
                      {{ lineAmount(order, item.quantity) }}
                    </span>
                  </p>
                  <p class="text-xs md:text-sm text-slate-500 mt-1 tabular-nums">
                    {{ item.quantity }} шт.
                  </p>
                </div>
              </div>
              <div
                v-if="!(order.items && order.items.length)"
                class="px-4 py-6 md:px-5 text-sm text-slate-500 text-center"
              >
                Состав заказа уточняется
              </div>
            </div>
          </template>

          <!-- Подвал карточки -->
          <div
            class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 px-4 py-4 md:px-5 md:py-4 border-t border-neutral-100 bg-neutral-50/60"
          >
            <div class="flex gap-2 shrink-0">
              <Button variant="primary" size="sm" @click.stop="openOrder(order.id)">
                Подробнее
              </Button>
            </div>
            <p class="text-base md:text-lg font-bold text-slate-900 tabular-nums text-right">
              Итого: {{ formatOrderTotal(order.total_amount) }}
            </p>
          </div>
        </article>
      </div>
    </div>
  </section>
  <Footer />
</template>
