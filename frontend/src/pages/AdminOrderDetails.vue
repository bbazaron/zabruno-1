<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import axios from 'axios'
import Header from '../components/sections/Header.vue'
import Footer from '../components/sections/Footer.vue'
import Card from '../components/ui/Card.vue'
import Button from '../components/ui/Button.vue'
import { useToast } from '../composables/useToast'
import { useProductLinkResolver } from '../composables/useProductLinkResolver'
import { Phone, MapPin, Package, Info, Trash2, Plus } from 'lucide-vue-next'

interface OrderItem {
  id: number
  product_name: string
  quantity: number
  size_override?: string | null
  line_comment?: string | null
  unit_price?: string | number | null
  line_total?: string | number | null
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

interface AdminOrder extends OrderDetails {
  user?: {
    id: number
    name: string
    email: string
  } | null
}

interface ItemDraft {
  product_name: string
  quantity: number
  size_override: string
  line_comment: string
}

interface OrderDraft {
  child_full_name: string
  child_gender: 'boy' | 'girl'
  settlement: string
  school: string
  class_num: string
  class_letter: string
  school_year: string
  size_from_table: string
  height_cm: string
  chest_cm: string
  waist_cm: string
  hips_cm: string
  figure_comment: string
  kit_comment: string
  parent_full_name: string
  parent_phone: string
  parent_email: string
  messenger_max: string
  messenger_telegram: string
  recipient_is_customer: boolean
  recipient_name: string
  recipient_phone: string
  items: ItemDraft[]
}

const route = useRoute()
const router = useRouter()
const { showToast } = useToast()
const { loadProducts, resolveProductId, resolveProductImage, products } = useProductLinkResolver()

const loading = ref(false)
const order = ref<AdminOrder | null>(null)
const orderId = computed(() => Number(route.params.id))
const isReadyToWearOrder = computed(
  () => String(order.value?.order_type ?? '').toLowerCase() === 'ready_to_wear',
)

const editMode = ref(false)
const draft = ref<OrderDraft | null>(null)
const savingOrder = ref(false)

const ORDER_STATUSES = [
  { value: 'pending', label: 'Ожидает' },
  { value: 'confirmed', label: 'Подтверждён' },
  { value: 'processing', label: 'В работе' },
  { value: 'production', label: 'В производстве' },
  { value: 'completed', label: 'Выполнен' },
  { value: 'cancelled', label: 'Отменён' },
] as const

const selectedStatus = ref<string>('pending')
const savingStatus = ref(false)

function normalizeStatusForSelect(status: string | null | undefined): string {
  const key = String(status ?? '').toLowerCase().replace(/\s+/g, '_')
  if (key === 'pending_payment') return 'pending'
  if (key === 'payment_cancelled') return 'cancelled'
  return key
}

const statusOptions = computed(() => {
  const s = normalizeStatusForSelect(order.value?.status)
  const base = ORDER_STATUSES.map((o) => ({ value: o.value, label: o.label }))
  if (!s || base.some((o) => o.value === s)) {
    return base
  }
  return [{ value: s, label: statusBadge(s).label }, ...base]
})

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

const draftItemImageByIndex = computed(() => {
  const d = draft.value
  if (!d?.items.length) return {} as Record<number, string | null>
  const out: Record<number, string | null> = {}
  d.items.forEach((it, i) => {
    out[i] = resolveProductImage(it.product_name, d.child_gender)
  })
  return out
})

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

function lineAmount(lineTotal: string | number | null | undefined): string | null {
  if (lineTotal === null || lineTotal === undefined || lineTotal === '') return null
  const n = typeof lineTotal === 'number' ? lineTotal : parseFloat(String(lineTotal))
  if (!Number.isFinite(n)) return null
  return formatMoney(n)
}

function formatPhoneDisplay(phone: string | undefined): string {
  if (!phone || !String(phone).trim()) return '—'
  return String(phone).trim()
}

const PICKUP_ADDRESS = 'пгт. Агинское, с Хусатуй, ул. Хусатуй, д.16'

function pickupLabel(_o: OrderDetails): string {
  return PICKUP_ADDRESS
}

function childGenderLabel(g: string): string {
  const v = String(g).toLowerCase()
  if (v === 'boy' || v === 'boys') return 'мальчик'
  if (v === 'girl' || v === 'girls') return 'девочка'
  return g
}

function orderTypeLabel(orderType: string | null | undefined): string {
  const v = String(orderType ?? '').toLowerCase()
  if (v === 'ready_to_wear') return 'Готовая одежда'
  return 'Пошив'
}

const STATUS_BADGE: Record<string, { label: string; class: string }> = {
  pending: { label: 'В обработке', class: 'bg-amber-50 text-amber-800 border-amber-200' },
  pending_payment: { label: 'Ожидает', class: 'bg-amber-50 text-amber-800 border-amber-200' },
  confirmed: { label: 'Подтверждён', class: 'bg-sky-50 text-sky-800 border-sky-200' },
  processing: { label: 'В работе', class: 'bg-sky-50 text-sky-800 border-sky-200' },
  production: { label: 'В производстве', class: 'bg-sky-50 text-sky-800 border-sky-200' },
  completed: { label: 'Завершён', class: 'bg-violet-50 text-violet-800 border-violet-200' },
  cancelled: { label: 'Отменён', class: 'bg-neutral-100 text-neutral-700 border-neutral-200' },
  payment_cancelled: { label: 'Отменён', class: 'bg-neutral-100 text-neutral-700 border-neutral-200' },
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

function normalizeRecipientIsCustomer(v: unknown): boolean {
  return v === true || v === 1 || v === '1'
}

function orderToDraft(o: AdminOrder): OrderDraft {
  return {
    child_full_name: o.child_full_name ?? '',
    child_gender: o.child_gender === 'girl' ? 'girl' : 'boy',
    settlement: o.settlement ?? '',
    school: o.school ?? '',
    class_num: o.class_num ?? '',
    class_letter: o.class_letter ?? '',
    school_year: o.school_year ?? '',
    size_from_table: o.size_from_table ?? '',
    height_cm: o.height_cm ?? '',
    chest_cm: o.chest_cm ?? '',
    waist_cm: o.waist_cm ?? '',
    hips_cm: o.hips_cm ?? '',
    figure_comment: o.figure_comment ?? '',
    kit_comment: o.kit_comment ?? '',
    parent_full_name: o.parent_full_name ?? '',
    parent_phone: o.parent_phone ?? '',
    parent_email: o.parent_email ?? '',
    messenger_max: o.messenger_max ?? '',
    messenger_telegram: o.messenger_telegram ?? '',
    recipient_is_customer: normalizeRecipientIsCustomer(o.recipient_is_customer),
    recipient_name: o.recipient_name ?? '',
    recipient_phone: o.recipient_phone ?? '',
    items:
      o.items?.length > 0
        ? o.items.map((i) => ({
            product_name: i.product_name,
            quantity: i.quantity,
            size_override: i.size_override ?? '',
            line_comment: i.line_comment ?? '',
          }))
        : [
            {
              product_name: '',
              quantity: 1,
              size_override: '',
              line_comment: '',
            },
          ],
  }
}

function buildPayload(d: OrderDraft) {
  const items = d.items
    .map((i) => ({
      product_name: i.product_name.trim(),
      quantity: Math.max(1, Math.floor(Number(i.quantity)) || 1),
      size_override: i.size_override.trim() || null,
      line_comment: i.line_comment.trim() || null,
    }))
    .filter((i) => i.product_name.length > 0)

  return {
    child_full_name: d.child_full_name.trim(),
    child_gender: d.child_gender,
    settlement: d.settlement.trim(),
    school: d.school.trim(),
    class_num: d.class_num.trim(),
    class_letter: d.class_letter.trim() || null,
    school_year: d.school_year.trim(),
    size_from_table: d.size_from_table.trim(),
    height_cm: d.height_cm.trim() || null,
    chest_cm: d.chest_cm.trim() || null,
    waist_cm: d.waist_cm.trim() || null,
    hips_cm: d.hips_cm.trim() || null,
    figure_comment: d.figure_comment.trim() || null,
    kit_comment: d.kit_comment.trim() || null,
    parent_full_name: d.parent_full_name.trim(),
    parent_phone: d.parent_phone.trim(),
    parent_email: d.parent_email.trim() || '',
    messenger_max: d.messenger_max.trim() || null,
    messenger_telegram: d.messenger_telegram.trim() || null,
    recipient_is_customer: d.recipient_is_customer,
    recipient_name: d.recipient_is_customer ? null : d.recipient_name.trim() || null,
    recipient_phone: d.recipient_phone.trim(),
    items,
  }
}

function beginEdit() {
  if (!order.value) return
  draft.value = orderToDraft(order.value)
  editMode.value = true
}

function cancelEdit() {
  editMode.value = false
  draft.value = null
}

function addDraftItem() {
  draft.value?.items.push({
    product_name: '',
    quantity: 1,
    size_override: '',
    line_comment: '',
  })
}

function removeDraftItem(index: number) {
  if (!draft.value || draft.value.items.length <= 1) return
  draft.value.items.splice(index, 1)
}

const inputClass =
  'w-full border border-neutral-300 rounded-lg px-3 py-2 text-sm text-slate-900 bg-white focus:outline-none focus:ring-2 focus:ring-slate-900/20'

async function loadOrder() {
  const token = getStoredToken()
  if (!token) {
    router.push('/login')
    return
  }

  if (Number.isNaN(orderId.value)) {
    router.push('/admin/orders')
    return
  }

  loading.value = true
  try {
    const response = await axios.get(`/api/admin/orders/${orderId.value}`, {
      headers: {
        Accept: 'application/json',
        Authorization: `Bearer ${token}`,
      },
    })
    const o = response.data?.order ?? null
    order.value = o
    if (o?.status) {
      selectedStatus.value = normalizeStatusForSelect(o.status)
    }
  } catch (err: any) {
    if (err?.response?.status === 401) {
      router.push('/login')
      return
    }
    if (err?.response?.status === 403) {
      router.push('/')
      return
    }
    if (err?.response?.status === 404) {
      showToast('Заказ не найден', 'error')
      router.push('/admin/orders')
      return
    }
    showToast('Не удалось загрузить заказ', 'error')
  } finally {
    loading.value = false
  }
}

async function saveStatus() {
  const token = getStoredToken()
  if (!token || !order.value) return

  savingStatus.value = true
  try {
    const response = await axios.patch(
      `/api/admin/orders/${orderId.value}/status`,
      { status: selectedStatus.value },
      {
        headers: {
          Accept: 'application/json',
          Authorization: `Bearer ${token}`,
        },
      },
    )
    order.value = response.data?.order ?? order.value
    if (order.value?.status) {
      selectedStatus.value = normalizeStatusForSelect(order.value.status)
    }
    showToast('Статус сохранён', 'success')
  } catch {
    showToast('Не удалось сохранить статус', 'error')
  } finally {
    savingStatus.value = false
  }
}

async function saveOrderDraft() {
  const token = getStoredToken()
  if (!token || !draft.value) return

  const payload = buildPayload(draft.value)
  if (payload.items.length === 0) {
    showToast('Добавьте хотя бы одну позицию с названием товара', 'error')
    return
  }

  savingOrder.value = true
  try {
    const response = await axios.patch(`/api/admin/orders/${orderId.value}`, payload, {
      headers: {
        Accept: 'application/json',
        Authorization: `Bearer ${token}`,
      },
    })
    order.value = response.data?.order ?? order.value
    editMode.value = false
    draft.value = null
    if (order.value?.status) {
      selectedStatus.value = normalizeStatusForSelect(order.value.status)
    }
    showToast('Данные заказа сохранены', 'success')
  } catch (err: any) {
    const data = err?.response?.data
    const errors = data?.errors
    if (errors && typeof errors === 'object') {
      const first = Object.values(errors).flat()[0]
      if (typeof first === 'string') {
        showToast(first, 'error')
        return
      }
    }
    const msg = data?.message
    showToast(typeof msg === 'string' ? msg : 'Не удалось сохранить заказ', 'error')
  } finally {
    savingOrder.value = false
  }
}

onMounted(() => {
  void loadProducts()
  loadOrder()
})
</script>

<template>
  <Header />

  <section class="bg-white border-b border-neutral-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6 md:pt-8 pb-8 md:pb-10">
      <div class="text-sm text-slate-600">
        <RouterLink to="/" class="hover:text-slate-900 transition-colors">Главная</RouterLink>
        <span class="mx-2">/</span>
        <RouterLink to="/admin/orders" class="hover:text-slate-900 transition-colors">Заказы (админ)</RouterLink>
        <span class="mx-2">/</span>
        <span class="text-slate-900 font-medium">Заказ №{{ orderId }}</span>
      </div>

      <div
        class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-start sm:gap-8 mt-4 md:mt-5"
      >
        <Button variant="secondary" size="md" class="shrink-0 self-start" @click="router.push('/admin/orders')">
          К списку заказов
        </Button>
        <div class="min-w-0 flex-1">
          <h1 class="text-3xl md:text-4xl font-bold leading-tight text-slate-900">
            Заказ №{{ orderId }}
          </h1>
        </div>
      </div>
    </div>
  </section>

  <section class="relative w-full min-h-screen font-sans text-slate-900 bg-neutral-50/80">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 md:py-14">
      <div
        v-if="loading"
        class="py-12 text-center text-slate-600 rounded-xl border border-dashed border-neutral-300 bg-white"
      >
        Загрузка заказа...
      </div>
      <div
        v-else-if="!order"
        class="py-12 text-center text-slate-600 rounded-xl border border-dashed border-neutral-300 bg-white"
      >
        Заказ не найден
      </div>

      <template v-else>
        <Card variant="outline" class="p-4 md:p-5 mb-6 md:mb-8 bg-white border-neutral-200">
          <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div class="space-y-2 text-sm text-slate-700 min-w-0">
              <p v-if="order.user" class="break-words">
                <span class="text-slate-500">Аккаунт:</span>
                <span class="font-medium text-slate-900">{{ order.user.name }}</span>
                <span class="text-slate-500"> · </span>
                <span class="break-all">{{ order.user.email }}</span>
              </p>
              <p v-else class="text-slate-500">Заказ без привязки к аккаунту</p>
            </div>
            <div class="flex flex-col sm:flex-row flex-wrap items-stretch sm:items-end gap-3 shrink-0">
              <div>
                <label class="block text-xs font-medium text-slate-600 mb-1" for="admin-order-status"
                  >Статус заказа</label
                >
                <select
                  id="admin-order-status"
                  v-model="selectedStatus"
                  class="min-w-[200px] w-full sm:w-auto border border-neutral-300 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-slate-900/20"
                >
                  <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">
                    {{ opt.label }}
                  </option>
                </select>
              </div>
              <Button type="button" variant="primary" size="md" :disabled="savingStatus" @click="saveStatus">
                {{ savingStatus ? 'Сохранение...' : 'Сохранить статус' }}
              </Button>
              <template v-if="!editMode">
                <Button type="button" variant="outline" size="md" @click="beginEdit">Редактировать данные</Button>
              </template>
              <template v-else>
                <Button
                  type="button"
                  variant="primary"
                  size="md"
                  class="!border-0 !bg-emerald-100 !text-emerald-900 !shadow-sm hover:!bg-emerald-200/90 focus-visible:!ring-emerald-400/50"
                  :disabled="savingOrder"
                  @click="saveOrderDraft"
                >
                  {{ savingOrder ? 'Сохранение...' : 'Сохранить данные' }}
                </Button>
                <Button type="button" variant="secondary" size="md" :disabled="savingOrder" @click="cancelEdit">
                  Отмена
                </Button>
              </template>
            </div>
          </div>
        </Card>

        <!-- Карточка заказа (как у пользователя) -->
        <article class="rounded-xl border border-neutral-200 bg-white shadow-sm overflow-hidden mb-6 md:mb-8">
          <div
            class="flex items-start justify-between gap-3 px-4 py-4 md:px-5 md:py-5 border-b border-neutral-100"
          >
            <div class="flex flex-wrap items-center gap-2 min-w-0">
              <p class="font-semibold text-slate-900 text-sm md:text-base leading-snug">
                Заказ №{{ order.id }} от {{ formatDate(order.created_at) }}
              </p>
              <span
                class="inline-flex items-center gap-1 rounded-full border px-2.5 py-0.5 text-xs font-medium bg-emerald-50 text-emerald-700 border-emerald-200"
              >
                {{ orderTypeLabel(order.order_type) }}
              </span>
              <span
                class="inline-flex items-center gap-1 rounded-full border px-2.5 py-0.5 text-xs font-medium"
                :class="statusBadge(order.status).class"
              >
                {{ statusBadge(order.status).label }}
                <Info :size="12" class="opacity-70 shrink-0" aria-hidden="true" />
              </span>
            </div>
          </div>

          <template v-if="!editMode">
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
                    <Package v-else :size="32" stroke-width="1.25" aria-hidden="true" />
                  </RouterLink>
                  <template v-else>
                    <img
                      v-if="itemImageByLineId[item.id]"
                      :src="itemImageByLineId[item.id]!"
                      :alt="item.product_name"
                      class="h-full w-full object-cover"
                    />
                    <Package v-else :size="32" stroke-width="1.25" aria-hidden="true" />
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
                  <p v-else class="font-medium text-slate-900 text-sm md:text-base leading-snug">
                    {{ item.product_name }}
                  </p>
                  <p v-if="item.size_override" class="text-xs text-slate-600 mt-1">
                    Размер: {{ item.size_override }}
                  </p>
                  <p v-if="item.line_comment" class="text-xs text-slate-600 mt-1">
                    {{ item.line_comment }}
                  </p>
                </div>
                <div class="text-left sm:text-right shrink-0 sm:pl-4">
                  <p v-if="lineAmount(item.line_total)" class="text-sm text-slate-700">
                    <span class="text-slate-500">Сумма:</span>
                    <span class="font-semibold text-slate-900 tabular-nums">
                      {{ lineAmount(item.line_total) }}
                    </span>
                  </p>
                  <p class="text-xs md:text-sm text-slate-500 mt-1 tabular-nums">{{ item.quantity }} шт.</p>
                </div>
              </div>
              <div
                v-if="!(order.items && order.items.length)"
                class="px-4 py-6 md:px-5 text-sm text-slate-500 text-center"
              >
                Позиции заказа не указаны
              </div>
            </div>
          </template>

          <template v-else-if="draft">
            <div class="px-4 py-3 md:px-5 md:py-4 border-b border-neutral-100 text-xs text-slate-500">
              Контакты и получение редактируются в блоке «Заказчик» ниже.
            </div>
            <div class="divide-y divide-neutral-100 px-4 py-4 md:px-5 md:py-5 space-y-4">
              <div class="flex items-center justify-between gap-2">
                <span class="text-sm font-medium text-slate-900">Позиции комплекта</span>
                <Button type="button" variant="outline" size="sm" class="gap-1" @click="addDraftItem">
                  <Plus :size="16" aria-hidden="true" />
                  Добавить
                </Button>
              </div>
              <div
                v-for="(row, idx) in draft.items"
                :key="idx"
                class="flex flex-col sm:flex-row gap-3 pt-4 first:pt-0 border-t border-neutral-100 first:border-t-0"
              >
                <div
                  class="shrink-0 w-20 h-20 rounded-lg bg-neutral-100 border border-neutral-200 overflow-hidden flex items-center justify-center text-slate-400 mx-auto sm:mx-0"
                >
                  <img
                    v-if="draftItemImageByIndex[idx]"
                    :src="draftItemImageByIndex[idx]!"
                    :alt="row.product_name || 'Товар'"
                    class="w-full h-full object-cover"
                  />
                  <Package v-else :size="32" stroke-width="1.25" aria-hidden="true" />
                </div>
                <div class="flex-1 min-w-0 grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <div class="sm:col-span-2">
                    <label class="block text-xs text-slate-500 mb-1">Название товара</label>
                    <input v-model="row.product_name" type="text" :class="inputClass" />
                  </div>
                  <div>
                    <label class="block text-xs text-slate-500 mb-1">Количество</label>
                    <input v-model.number="row.quantity" type="number" min="1" :class="inputClass" />
                  </div>
                  <div>
                    <label class="block text-xs text-slate-500 mb-1">Размер (позиция)</label>
                    <input v-model="row.size_override" type="text" :class="inputClass" />
                  </div>
                  <div class="sm:col-span-2">
                    <label class="block text-xs text-slate-500 mb-1">Комментарий к позиции</label>
                    <input v-model="row.line_comment" type="text" :class="inputClass" />
                  </div>
                </div>
                <div class="flex sm:flex-col justify-end shrink-0">
                  <button
                    type="button"
                    class="p-2 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 transition-colors disabled:opacity-40"
                    :disabled="draft.items.length <= 1"
                    aria-label="Удалить позицию"
                    @click="removeDraftItem(idx)"
                  >
                    <Trash2 :size="20" />
                  </button>
                </div>
              </div>
            </div>
          </template>

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
          v-if="!editMode"
          class="grid grid-cols-1 gap-5 md:gap-6"
          :class="isReadyToWearOrder ? 'lg:grid-cols-1' : 'lg:grid-cols-2'"
        >
          <Card v-if="!isReadyToWearOrder" variant="outline" class="p-5 md:p-6 bg-white border-neutral-200">
            <h2 class="text-lg font-semibold tracking-tight text-slate-900 mb-4">Данные ребёнка</h2>
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
              <div>
                <dt class="text-slate-500">Рост</dt>
                <dd class="font-medium text-slate-900">{{ order.height_cm ?? '—' }}</dd>
              </div>
              <div>
                <dt class="text-slate-500">Грудь</dt>
                <dd class="font-medium text-slate-900">{{ order.chest_cm ?? '—' }}</dd>
              </div>
              <div>
                <dt class="text-slate-500">Талия</dt>
                <dd class="font-medium text-slate-900">{{ order.waist_cm ?? '—' }}</dd>
              </div>
              <div>
                <dt class="text-slate-500">Бёдра</dt>
                <dd class="font-medium text-slate-900">{{ order.hips_cm ?? '—' }}</dd>
              </div>
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
            <h2 class="text-lg font-semibold tracking-tight text-slate-900 mb-4">Заказчик и связь</h2>
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

        <div
          v-else-if="draft"
          class="grid grid-cols-1 gap-5 md:gap-6"
          :class="isReadyToWearOrder ? 'lg:grid-cols-1' : 'lg:grid-cols-2'"
        >
          <Card v-if="!isReadyToWearOrder" variant="outline" class="p-5 md:p-6 bg-white border-neutral-200 space-y-4">
            <h2 class="text-lg font-semibold tracking-tight text-slate-900">Данные ребёнка</h2>
            <div>
              <label class="block text-xs text-slate-500 mb-1">ФИО</label>
              <input v-model="draft.child_full_name" type="text" :class="inputClass" />
            </div>
            <div>
              <label class="block text-xs text-slate-500 mb-1">Пол</label>
              <select v-model="draft.child_gender" :class="inputClass">
                <option value="boy">Мальчик</option>
                <option value="girl">Девочка</option>
              </select>
            </div>
            <div>
              <label class="block text-xs text-slate-500 mb-1">Школа</label>
              <input v-model="draft.school" type="text" :class="inputClass" />
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs text-slate-500 mb-1">Класс (номер)</label>
                <input v-model="draft.class_num" type="text" :class="inputClass" />
              </div>
              <div>
                <label class="block text-xs text-slate-500 mb-1">Буква</label>
                <input v-model="draft.class_letter" type="text" :class="inputClass" />
              </div>
            </div>
            <div>
              <label class="block text-xs text-slate-500 mb-1">Учебный год</label>
              <input v-model="draft.school_year" type="text" :class="inputClass" />
            </div>
            <div>
              <label class="block text-xs text-slate-500 mb-1">Размер по таблице</label>
              <input v-model="draft.size_from_table" type="text" :class="inputClass" />
            </div>
            <h3 class="text-base font-semibold text-slate-900 pt-2">Мерки (см)</h3>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs text-slate-500 mb-1">Рост</label>
                <input v-model="draft.height_cm" type="text" :class="inputClass" />
              </div>
              <div>
                <label class="block text-xs text-slate-500 mb-1">Грудь</label>
                <input v-model="draft.chest_cm" type="text" :class="inputClass" />
              </div>
              <div>
                <label class="block text-xs text-slate-500 mb-1">Талия</label>
                <input v-model="draft.waist_cm" type="text" :class="inputClass" />
              </div>
              <div>
                <label class="block text-xs text-slate-500 mb-1">Бёдра</label>
                <input v-model="draft.hips_cm" type="text" :class="inputClass" />
              </div>
            </div>
            <div>
              <label class="block text-xs text-slate-500 mb-1">Комментарий по фигуре</label>
              <textarea v-model="draft.figure_comment" rows="3" :class="inputClass" />
            </div>
            <div>
              <label class="block text-xs text-slate-500 mb-1">Комментарий к комплекту</label>
              <textarea v-model="draft.kit_comment" rows="3" :class="inputClass" />
            </div>
          </Card>

          <Card variant="outline" class="p-5 md:p-6 bg-white border-neutral-200 space-y-4">
            <h2 class="text-lg font-semibold tracking-tight text-slate-900">Заказчик и связь</h2>
            <div>
              <label class="block text-xs text-slate-500 mb-1">ФИО</label>
              <input v-model="draft.parent_full_name" type="text" :class="inputClass" />
            </div>
            <div>
              <label class="block text-xs text-slate-500 mb-1">Телефон</label>
              <input v-model="draft.parent_phone" type="text" :class="inputClass" />
            </div>
            <div>
              <label class="block text-xs text-slate-500 mb-1">Email</label>
              <input v-model="draft.parent_email" type="email" :class="inputClass" />
            </div>
            <div>
              <label class="block text-xs text-slate-500 mb-1">Населённый пункт</label>
              <input v-model="draft.settlement" type="text" :class="inputClass" />
            </div>
            <div>
              <label class="block text-xs text-slate-500 mb-1">MAX</label>
              <input v-model="draft.messenger_max" type="text" :class="inputClass" />
            </div>
            <div>
              <label class="block text-xs text-slate-500 mb-1">Telegram</label>
              <input v-model="draft.messenger_telegram" type="text" :class="inputClass" />
            </div>
            <h3 class="text-base font-semibold text-slate-900 pt-2">Получатель</h3>
            <label class="flex items-center gap-2 text-sm text-slate-700">
              <input v-model="draft.recipient_is_customer" type="checkbox" class="rounded border-neutral-300" />
              Получатель совпадает с заказчиком
            </label>
            <div v-if="!draft.recipient_is_customer">
              <label class="block text-xs text-slate-500 mb-1">ФИО получателя</label>
              <input v-model="draft.recipient_name" type="text" :class="inputClass" />
            </div>
            <div>
              <label class="block text-xs text-slate-500 mb-1">Телефон получателя</label>
              <input v-model="draft.recipient_phone" type="text" :class="inputClass" />
            </div>
          </Card>
        </div>
      </template>
    </div>
  </section>

  <Footer />
</template>
