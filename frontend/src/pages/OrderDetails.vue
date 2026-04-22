<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import axios from 'axios'
import Header from '../components/sections/Header.vue'
import Footer from '../components/sections/Footer.vue'
import Card from '../components/ui/Card.vue'
import Typography from '../components/ui/Typography.vue'
import Button from '../components/ui/Button.vue'
import { useToast } from '../composables/useToast'
import { useProductLinkResolver } from '../composables/useProductLinkResolver'
import { Phone, MapPin, Package, Info } from 'lucide-vue-next'

interface OrderItem {
  id: number
  product_name: string
  quantity: number
  size_override?: string | null
  line_comment?: string | null
}

interface OrderDetails {
  id: number
  status: string
  order_type?: string
  created_at: string
  total_amount?: string | number | null
  child_full_name: string
  child_gender: string
  settlement: string
  school: string
  class_num: string
  class_letter?: string | null
  school_year: string
  size_from_table: string
  height_cm?: string | null
  chest_cm?: string | null
  waist_cm?: string | null
  hips_cm?: string | null
  figure_comment?: string | null
  kit_comment?: string | null
  parent_full_name: string
  parent_phone: string
  parent_email: string
  messenger_max?: string | null
  messenger_telegram?: string | null
  recipient_is_customer: boolean
  recipient_name?: string | null
  recipient_phone: string
  items: OrderItem[]
}

const route = useRoute()
const router = useRouter()
const { showToast } = useToast()
const { loadProducts, resolveProductId, resolveProductImage, products } = useProductLinkResolver()

function productHref(productName: string, orderGender?: string | null): string | null {
  void products.value.length
  const id = resolveProductId(productName, orderGender)
  return id != null ? `/product/${id}` : null
}

const itemImageByLineId = computed(() => {
  const o = order.value
  if (!o?.items?.length) return {} as Record<number, string | null>
  const out: Record<number, string | null> = {}
  for (const item of o.items) {
    out[item.id] = resolveProductImage(item.product_name, o.child_gender)
  }
  return out
})

const loading = ref(false)
const order = ref<OrderDetails | null>(null)
const isReadyToWearOrder = computed(
  () => String(order.value?.order_type ?? '').toLowerCase() === 'ready_to_wear',
)

const orderId = computed(() => Number(route.params.id))

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

function formatMoney(n: number): string {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  }).format(n)
}

function lineAmount(o: OrderDetails, quantity: number): string | null {
  const raw = o.total_amount
  if (raw === null || raw === undefined || raw === '') return null
  const total = typeof raw === 'number' ? raw : parseFloat(String(raw))
  if (!Number.isFinite(total) || total <= 0) return null
  const items = o.items ?? []
  const sumQty = items.reduce((s, i) => s + i.quantity, 0)
  if (sumQty <= 0) return null
  return formatMoney((total * quantity) / sumQty)
}

function formatPhoneDisplay(phone: string | undefined): string {
  if (!phone || !String(phone).trim()) return '—'
  return String(phone).trim()
}

function pickupLabel(o: OrderDetails): string {
  const parts = [o.settlement, o.school].filter(Boolean) as string[]
  if (parts.length === 0) return '—'
  return parts.join(', ')
}

function childGenderLabel(g: string): string {
  const v = String(g).toLowerCase()
  if (v === 'boy' || v === 'boys') return 'мальчик'
  if (v === 'girl' || v === 'girls') return 'девочка'
  return g
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

async function loadOrderDetails() {
  const token = getStoredToken()
  if (!token) {
    router.push('/login')
    return
  }

  if (Number.isNaN(orderId.value)) {
    router.push('/orders')
    return
  }

  loading.value = true
  try {
    const response = await axios.get('/api/getOrders', {
      headers: {
        Accept: 'application/json',
        Authorization: `Bearer ${token}`,
      },
    })

    const orders = Array.isArray(response.data?.orders) ? response.data.orders : []
    const found = orders.find((item: { id: number }) => item.id === orderId.value)
    if (!found) {
      showToast('Заказ не найден', 'error')
      router.push('/orders')
      return
    }

    order.value = found as OrderDetails
  } catch (err: any) {
    if (err?.response?.status === 401) {
      router.push('/login')
      return
    }

    showToast('Не удалось загрузить заказ', 'error')
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  void loadProducts()
  loadOrderDetails()
})
</script>

<template>
  <Header />

  <section class="bg-white border-b border-neutral-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6 md:pt-8 pb-8 md:pb-10">
      <div class="text-sm text-slate-600">
        <RouterLink to="/" class="hover:text-slate-900 transition-colors">Главная</RouterLink>
        <span class="mx-2">/</span>
        <RouterLink to="/orders" class="hover:text-slate-900 transition-colors">Профиль пользователя</RouterLink>
        <span class="mx-2">/</span>
        <span class="text-slate-900 font-medium">Заказ №{{ orderId }}</span>
      </div>

      <div
        class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between sm:gap-8 mt-4 md:mt-5"
      >
        <div class="min-w-0 flex-1">
          <h1 class="text-3xl md:text-4xl font-bold leading-tight text-slate-900">
            Заказ №{{ orderId }}
          </h1>
          <Typography as="p" class="text-slate-600 mt-2 text-sm md:text-base leading-relaxed">
            Подробности заказа и данные для пошива
          </Typography>
        </div>
        <Button variant="secondary" size="md" class="shrink-0 self-start" @click="router.push('/orders')">
          К списку заказов
        </Button>
      </div>
    </div>
  </section>

  <section class="relative w-full min-h-screen font-sans text-slate-900 bg-neutral-50/80">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 md:py-14">
      <div v-if="loading" class="py-12 text-center text-slate-600 rounded-xl border border-dashed border-neutral-300 bg-white">
        Загрузка заказа...
      </div>
      <div v-else-if="!order" class="py-12 text-center text-slate-600 rounded-xl border border-dashed border-neutral-300 bg-white">
        Заказ не найден
      </div>

      <template v-else>
        <!-- Карточка заказа (как в списке «Мои заказы») -->
        <article
          class="rounded-xl border border-neutral-200 bg-white shadow-sm overflow-hidden mb-6 md:mb-8"
        >
          <div
            class="flex items-start justify-between gap-3 px-4 py-4 md:px-5 md:py-5 border-b border-neutral-100"
          >
            <div class="flex flex-wrap items-center gap-2 min-w-0">
              <p class="font-semibold text-slate-900 text-sm md:text-base leading-snug">
                Заказ №{{ order.id }} от {{ formatDate(order.created_at) }}
              </p>
              <span
                class="inline-flex items-center gap-1 rounded-full border px-2.5 py-0.5 text-xs font-medium"
                :class="statusBadge(order.status).class"
              >
                {{ statusBadge(order.status).label }}
                <Info :size="12" class="opacity-70 shrink-0" aria-hidden="true" />
              </span>
            </div>
          </div>

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
                    v-if="itemImageByLineId[item.id]"
                    :src="itemImageByLineId[item.id]!"
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
                    v-if="itemImageByLineId[item.id]"
                    :src="itemImageByLineId[item.id]!"
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
                <p v-if="item.size_override" class="text-xs text-slate-600 mt-1">
                  Размер: {{ item.size_override }}
                </p>
                <p v-if="item.line_comment" class="text-xs text-slate-600 mt-1">
                  <span class="text-slate-500">Комментарий: </span>{{ item.line_comment }}
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
              Позиции заказа не указаны
            </div>
          </div>

          <div
            class="flex flex-col sm:flex-row sm:items-center sm:justify-end gap-4 px-4 py-4 md:px-5 md:py-4 border-t border-neutral-100 bg-neutral-50/60"
          >
            <p class="text-base md:text-lg font-bold text-slate-900 tabular-nums text-right">
              Итого: {{ formatOrderTotal(order.total_amount) }}
            </p>
          </div>
        </article>

        <!-- Данные ребёнка и заказчика -->
        <div
          class="grid grid-cols-1 gap-5 md:gap-6"
          :class="isReadyToWearOrder ? 'lg:grid-cols-1' : 'lg:grid-cols-2'"
        >
          <Card v-if="!isReadyToWearOrder" variant="outline" class="p-5 md:p-6 bg-white border-neutral-200">
            <h2 class="text-lg font-semibold tracking-tight text-slate-900 mb-4">
              Данные ребёнка
            </h2>
            <dl class="space-y-2 text-sm text-slate-700">
              <div class="flex flex-col sm:flex-row sm:gap-2">
                <dt class="text-slate-500 shrink-0 sm:min-w-[140px]">ФИО</dt>
                <dd class="font-medium text-slate-900">{{ order.child_full_name }}</dd>
              </div>
              <div class="flex flex-col sm:flex-row sm:gap-2">
                <dt class="text-slate-500 shrink-0 sm:min-w-[140px]">Пол</dt>
                <dd>{{ childGenderLabel(order.child_gender) }}</dd>
              </div>
              <div class="flex flex-col sm:flex-row sm:gap-2">
                <dt class="text-slate-500 shrink-0 sm:min-w-[140px]">Школа</dt>
                <dd>{{ order.school }}</dd>
              </div>
              <div class="flex flex-col sm:flex-row sm:gap-2">
                <dt class="text-slate-500 shrink-0 sm:min-w-[140px]">Класс</dt>
                <dd>{{ order.class_num }}{{ order.class_letter || '' }}</dd>
              </div>
              <div class="flex flex-col sm:flex-row sm:gap-2">
                <dt class="text-slate-500 shrink-0 sm:min-w-[140px]">Учебный год</dt>
                <dd>{{ order.school_year }}</dd>
              </div>
              <div class="flex flex-col sm:flex-row sm:gap-2">
                <dt class="text-slate-500 shrink-0 sm:min-w-[140px]">Размер по таблице</dt>
                <dd>{{ order.size_from_table }}</dd>
              </div>
            </dl>

            <h3 class="text-base font-semibold text-slate-900 mt-6 mb-3">Мерки (см)</h3>
            <dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm text-slate-700">
              <div><dt class="text-slate-500">Рост</dt><dd class="font-medium text-slate-900">{{ order.height_cm ?? '—' }}</dd></div>
              <div><dt class="text-slate-500">Грудь</dt><dd class="font-medium text-slate-900">{{ order.chest_cm ?? '—' }}</dd></div>
              <div><dt class="text-slate-500">Талия</dt><dd class="font-medium text-slate-900">{{ order.waist_cm ?? '—' }}</dd></div>
              <div><dt class="text-slate-500">Бёдра</dt><dd class="font-medium text-slate-900">{{ order.hips_cm ?? '—' }}</dd></div>
            </dl>
            <p v-if="order.figure_comment" class="mt-4 text-sm text-slate-700">
              <span class="text-slate-500">Комментарий по фигуре:</span>
              {{ order.figure_comment }}
            </p>
            <p v-if="order.kit_comment" class="mt-2 text-sm text-slate-700">
              <span class="text-slate-500">Комментарий к комплекту:</span>
              {{ order.kit_comment }}
            </p>
          </Card>

          <Card variant="outline" class="p-5 md:p-6 bg-white border-neutral-200">
            <h2 class="text-lg font-semibold tracking-tight text-slate-900 mb-4">
              Заказчик и связь
            </h2>
            <dl class="space-y-2 text-sm text-slate-700">
              <div class="flex flex-col sm:flex-row sm:gap-2">
                <dt class="text-slate-500 shrink-0 sm:min-w-[140px]">ФИО</dt>
                <dd class="font-medium text-slate-900">{{ order.parent_full_name }}</dd>
              </div>
              <div class="flex flex-col sm:flex-row sm:gap-2">
                <dt class="text-slate-500 shrink-0 sm:min-w-[140px]">Телефон</dt>
                <dd>{{ order.parent_phone }}</dd>
              </div>
              <div class="flex flex-col sm:flex-row sm:gap-2">
                <dt class="text-slate-500 shrink-0 sm:min-w-[140px]">Email</dt>
                <dd class="break-all">{{ order.parent_email }}</dd>
              </div>
              <div class="flex flex-col sm:flex-row sm:gap-2">
                <dt class="text-slate-500 shrink-0 sm:min-w-[140px]">Населённый пункт</dt>
                <dd>{{ order.settlement }}</dd>
              </div>
              <div v-if="order.messenger_max" class="flex flex-col sm:flex-row sm:gap-2">
                <dt class="text-slate-500 shrink-0 sm:min-w-[140px]">MAX</dt>
                <dd>{{ order.messenger_max }}</dd>
              </div>
              <div v-if="order.messenger_telegram" class="flex flex-col sm:flex-row sm:gap-2">
                <dt class="text-slate-500 shrink-0 sm:min-w-[140px]">Telegram</dt>
                <dd>{{ order.messenger_telegram }}</dd>
              </div>
            </dl>

            <h3 class="text-base font-semibold text-slate-900 mt-6 mb-3">Получатель</h3>
            <dl class="space-y-2 text-sm text-slate-700">
              <div class="flex flex-col sm:flex-row sm:gap-2">
                <dt class="text-slate-500 shrink-0 sm:min-w-[140px]">ФИО</dt>
                <dd>{{ order.recipient_name || order.parent_full_name }}</dd>
              </div>
              <div class="flex flex-col sm:flex-row sm:gap-2">
                <dt class="text-slate-500 shrink-0 sm:min-w-[140px]">Телефон</dt>
                <dd>{{ order.recipient_phone }}</dd>
              </div>
            </dl>
          </Card>
        </div>
      </template>
    </div>
  </section>

  <Footer />
</template>
