<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import { ChevronDown, ChevronLeft, ChevronRight, ChevronUp } from 'lucide-vue-next'
import Header from '../components/sections/Header.vue'
import Footer from '../components/sections/Footer.vue'
import Button from '../components/ui/Button.vue'
import Typography from '../components/ui/Typography.vue'
import CatalogMediaCropEditor from '../components/admin/CatalogMediaCropEditor.vue'
import CatalogCropViewport from '../components/catalog/CatalogCropViewport.vue'
import { useToast } from '../composables/useToast'
import { resolveBackendMediaUrl } from '../utils/resolveBackendMediaUrl'
import { clampCatalogCrop, DEFAULT_CATALOG_CROP, type CatalogCrop } from '../utils/catalogCrop'

const AUTH_TOKEN_KEY = 'auth_token'
const ACCEPTED_IMAGE_TYPES = new Set(['image/png', 'image/jpeg', 'image/webp'])

interface CatalogSample {
  id: number
  name: string
  price: string | number
  gender: string
  description: string | null
  image_url?: string | null
  media?: Array<{
    id?: number | null
    url: string
    catalog_zoom?: number
    catalog_pan_x?: number
    catalog_pan_y?: number
  }>
}

interface ItemMedia {
  id: number
  path: string
  url?: string | null
  sort_order: number
  catalog_zoom?: number
  catalog_pan_x?: number
  catalog_pan_y?: number
}

interface CheckoutItem {
  id: number
  name: string
  price: string | number
  description: string | null
  image: string | null
  image_url?: string | null
  media?: ItemMedia[]
  gender: 'boys' | 'girls' | 'all' | string
  sort_order: number
  is_active: boolean
}

type MediaSlot =
  | { kind: 'existing'; id: number; url: string; crop: CatalogCrop; removed: boolean }
  | { kind: 'catalog'; key: string; sourceId: number | 'image'; url: string; crop: CatalogCrop; removed: boolean }
  | { kind: 'new'; key: string; file: File; preview: string; crop: CatalogCrop }

const router = useRouter()
const route = useRoute()
const { showToast } = useToast()

const items = ref<CheckoutItem[]>([])
const products = ref<CatalogSample[]>([])
const loading = ref(false)
const saving = ref(false)
const editingId = ref<number | null>(null)
const reordering = ref(false)
const previewGender = ref<'all' | 'boys' | 'girls'>('all')
const productsLoadError = ref(false)

const productSearch = ref('')
const copyFromProductId = ref<number | ''>('')
const formName = ref('')
const formPrice = ref('')
const formDescription = ref('')
const formGender = ref<'boys' | 'girls' | 'all'>('all')
const formIsActive = ref(true)
const mediaSlots = ref<MediaSlot[]>([])
const mediaInput = ref<HTMLInputElement | null>(null)
const photoCursor = ref(0)
const dragPhotoFrom = ref<number | null>(null)

const inputClass =
  'w-full border border-neutral-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900'

function isAdminTabActive(
  path: '/admin' | '/admin/orders' | '/admin/products' | '/admin/checkout-items',
): boolean {
  if (path === '/admin/orders') return route.path.startsWith('/admin/orders')
  return route.path === path
}

function getAuthHeaders(): Record<string, string> {
  const headers: Record<string, string> = { Accept: 'application/json' }
  const token = localStorage.getItem(AUTH_TOKEN_KEY) || localStorage.getItem('token')
  if (token) headers.Authorization = `Bearer ${token}`
  return headers
}

function formatGender(gender: string): string {
  if (gender === 'boys') return 'Мальчики'
  if (gender === 'girls') return 'Девочки'
  if (gender === 'all') return 'Все'
  return gender
}

function formatPrice(value: string | number): string {
  const n = typeof value === 'number' ? value : parseFloat(String(value).replace(',', '.'))
  if (!Number.isFinite(n)) return '—'
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  }).format(n)
}

function itemImageSrc(item: CheckoutItem): string {
  return resolveBackendMediaUrl(item.image_url || item.image)
}

function formatApiError(err: unknown): string {
  const data = (err as { response?: { data?: { message?: string; errors?: Record<string, string[]> } | string } })
    .response?.data
  if (data && typeof data === 'object') {
    if (data.errors) {
      const text = Object.values(data.errors).flat().filter(Boolean).join(' ')
      if (text) return text
    }
    if (typeof data.message === 'string' && data.message.trim() !== '') return data.message
  }
  return 'Не удалось сохранить карточку. Для фото используйте PNG, JPG или WEBP до 5 МБ, не больше 10 файлов.'
}

const selectableProducts = computed(() => {
  const q = productSearch.value.trim().toLowerCase()
  return products.value.filter((product) => {
    if (!q) return true
    return product.name.toLowerCase().includes(q) || String(product.id).includes(q)
  })
})

const selectedSample = computed(() =>
  copyFromProductId.value === ''
    ? null
    : (products.value.find((product) => product.id === copyFromProductId.value) ?? null),
)

function clearMediaSlots() {
  for (const slot of mediaSlots.value) {
    if (slot.kind === 'new') URL.revokeObjectURL(slot.preview)
  }
  mediaSlots.value = []
}

function resetForm() {
  clearMediaSlots()
  editingId.value = null
  productSearch.value = ''
  copyFromProductId.value = ''
  formName.value = ''
  formPrice.value = ''
  formDescription.value = ''
  formGender.value = 'all'
  formIsActive.value = true
}

function catalogSlotsFromProduct(product: CatalogSample): MediaSlot[] {
  const rows = product.media ?? []
  const slots: MediaSlot[] = rows
    .filter((media) => Boolean(media.url))
    .map((media) => ({
      kind: 'catalog' as const,
      key: media.id ? `catalog-${product.id}-${media.id}` : `catalog-${product.id}-image`,
      sourceId: media.id ? media.id : 'image',
      url: resolveBackendMediaUrl(media.url),
      crop: clampCatalogCrop(media),
      removed: false,
    }))
  if (slots.length > 0) return slots
  const fallback = resolveBackendMediaUrl(product.image_url)
  if (!fallback) return []
  return [{
    kind: 'catalog',
    key: `catalog-${product.id}-image`,
    sourceId: 'image',
    url: fallback,
    crop: { ...DEFAULT_CATALOG_CROP },
    removed: false,
  }]
}

function applySample(product: CatalogSample) {
  const addedFiles = mediaSlots.value.filter((slot) => slot.kind === 'new')
  mediaSlots.value = [...catalogSlotsFromProduct(product), ...addedFiles]
  photoCursor.value = 0
  copyFromProductId.value = product.id
  formName.value = product.name
  formPrice.value = String(product.price ?? '')
  formDescription.value = product.description ?? ''
  formGender.value =
    product.gender === 'boys' || product.gender === 'girls' || product.gender === 'all'
      ? product.gender
      : 'all'
}

function clearCopy() {
  copyFromProductId.value = ''
  mediaSlots.value = mediaSlots.value.filter((slot) => slot.kind !== 'catalog')
  photoCursor.value = 0
}

function sampleImage(product: CatalogSample): string {
  return resolveBackendMediaUrl(product.media?.[0]?.url || product.image_url)
}

function fillForm(item: CheckoutItem) {
  clearMediaSlots()
  editingId.value = item.id
  productSearch.value = ''
  copyFromProductId.value = ''
  formName.value = item.name
  formPrice.value = String(item.price ?? '')
  formDescription.value = item.description ?? ''
  formGender.value =
    item.gender === 'boys' || item.gender === 'girls' || item.gender === 'all' ? item.gender : 'all'
  formIsActive.value = Boolean(item.is_active)
  photoCursor.value = 0
  mediaSlots.value = (item.media ?? []).map((media) => ({
    kind: 'existing' as const,
    id: media.id,
    url: resolveBackendMediaUrl(media.url || media.path),
    crop: clampCatalogCrop(media),
    removed: false,
  }))
}

function onMediaChange(event: Event) {
  const input = event.target as HTMLInputElement
  const files = Array.from(input.files ?? [])
  input.value = ''
  const next = [...mediaSlots.value]
  for (const file of files) {
    if (!ACCEPTED_IMAGE_TYPES.has(file.type)) {
      showToast('Можно загружать только PNG, JPG, JPEG и WEBP', 'error')
      continue
    }
    next.push({
      kind: 'new',
      key: `${file.name}-${file.size}-${crypto.randomUUID()}`,
      file,
      preview: URL.createObjectURL(file),
      crop: { ...DEFAULT_CATALOG_CROP },
    })
  }
  mediaSlots.value = next
  const visibleCount = next.filter((slot) => slot.kind === 'new' || !slot.removed).length
  photoCursor.value = Math.max(0, visibleCount - 1)
}

function reorderVisiblePhotos(fromVisible: number, toVisible: number) {
  if (fromVisible === toVisible) return
  const entries = visiblePhotos.value.map((entry) => entry.slot)
  const moved = entries[fromVisible]
  if (!moved) return
  entries.splice(fromVisible, 1)
  entries.splice(toVisible, 0, moved)
  let cursor = 0
  mediaSlots.value = mediaSlots.value.map((slot) => {
    if (slot.kind !== 'new' && slot.removed) return slot
    const next = entries[cursor]
    cursor += 1
    return next ?? slot
  })
  photoCursor.value = toVisible
}

function onPhotoDragStart(visibleIndex: number) {
  dragPhotoFrom.value = visibleIndex
}

function onPhotoDrop(visibleIndex: number) {
  if (dragPhotoFrom.value == null) return
  reorderVisiblePhotos(dragPhotoFrom.value, visibleIndex)
  dragPhotoFrom.value = null
}

function removeSlot(index: number) {
  const slot = mediaSlots.value[index]
  if (!slot) return
  if (slot.kind === 'existing' || slot.kind === 'catalog') {
    mediaSlots.value = mediaSlots.value.map((row, rowIndex) =>
      rowIndex === index && (row.kind === 'existing' || row.kind === 'catalog')
        ? { ...row, removed: true }
        : row,
    )
    return
  }
  URL.revokeObjectURL(slot.preview)
  mediaSlots.value = mediaSlots.value.filter((_, rowIndex) => rowIndex !== index)
}

function updateSlotCrop(index: number, crop: CatalogCrop) {
  const slot = mediaSlots.value[index]
  if (!slot || ('removed' in slot && slot.removed)) return
  const next = [...mediaSlots.value]
  next[index] = { ...slot, crop: clampCatalogCrop(crop) }
  mediaSlots.value = next
}

function restoreSlot(index: number) {
  const slot = mediaSlots.value[index]
  if (!slot || (slot.kind !== 'existing' && slot.kind !== 'catalog')) return
  mediaSlots.value = mediaSlots.value.map((row, rowIndex) =>
    rowIndex === index && (row.kind === 'existing' || row.kind === 'catalog')
      ? { ...row, removed: false }
      : row,
  )
}

const formTitle = computed(() =>
  editingId.value != null
    ? `Редактирование карточки №${editingId.value}`
    : 'Новая карточка',
)

function slotImage(slot: MediaSlot): string {
  if (slot.kind === 'new') return slot.preview
  return slot.url
}

function slotKey(slot: MediaSlot): string {
  if (slot.kind === 'existing') return `e-${slot.id}`
  return slot.key
}

const visiblePhotos = computed(() =>
  mediaSlots.value
    .map((slot, index) => ({ slot, index }))
    .filter(({ slot }) => slot.kind === 'new' || !slot.removed),
)

const removedPhotos = computed(() =>
  mediaSlots.value
    .map((slot, index) => ({ slot, index }))
    .filter(({ slot }) => slot.kind !== 'new' && slot.removed),
)

const activePhoto = computed(() => {
  const list = visiblePhotos.value
  if (list.length === 0) return null
  const cursor = Math.min(Math.max(photoCursor.value, 0), list.length - 1)
  return list[cursor] ?? null
})

const activePhotoVisibleIndex = computed(() => {
  const list = visiblePhotos.value
  if (list.length === 0) return 0
  return Math.min(Math.max(photoCursor.value, 0), list.length - 1)
})

function showPhoto(visibleIndex: number) {
  photoCursor.value = visibleIndex
}

function stepPhoto(delta: number) {
  const count = visiblePhotos.value.length
  if (count <= 1) return
  const current = activePhotoVisibleIndex.value
  photoCursor.value = (current + delta + count) % count
}

const draftPriceLabel = computed(() => {
  const raw = String(formPrice.value).trim().replace(',', '.')
  if (!raw) return 'Цена: —'
  const n = parseFloat(raw)
  if (!Number.isFinite(n)) return 'Цена: —'
  return `Цена: ${formatPrice(n)}`
})

const previewItems = computed(() =>
  items.value.filter((item) => {
    if (!item.is_active) return false
    if (previewGender.value === 'all') return true
    return item.gender === previewGender.value || item.gender === 'all'
  }),
)

async function loadItems() {
  loading.value = true
  try {
    const res = await axios.get<{
      items?: CheckoutItem[]
      products?: CatalogSample[]
    }>('/api/admin/checkout-items', {
      headers: getAuthHeaders(),
    })
    items.value = Array.isArray(res.data?.items) ? res.data.items : []
    products.value = Array.isArray(res.data?.products) ? res.data.products : []
    productsLoadError.value = false
  } catch {
    showToast('Не удалось загрузить карточки пошива', 'error')
    items.value = []
    products.value = []
    productsLoadError.value = true
  } finally {
    loading.value = false
  }
}

async function submitForm() {
  if (!formName.value.trim()) {
    showToast('Укажите название', 'error')
    return
  }
  const priceNum = parseFloat(String(formPrice.value).replace(',', '.'))
  if (!Number.isFinite(priceNum) || priceNum < 0) {
    showToast('Укажите корректную цену', 'error')
    return
  }

  const kept = mediaSlots.value.filter((slot) => slot.kind === 'new' || !slot.removed)
  if (kept.length > 10) {
    showToast('Можно сохранить не больше 10 фотографий', 'error')
    return
  }

  saving.value = true
  try {
    const payload = new FormData()
    payload.append('name', formName.value.trim())
    payload.append('price', String(priceNum))
    payload.append('description', formDescription.value.trim())
    payload.append('gender', formGender.value)
    payload.append('is_active', formIsActive.value ? '1' : '0')
    const keptCatalog = mediaSlots.value.some((slot) => slot.kind === 'catalog' && !slot.removed)
    if (editingId.value == null && copyFromProductId.value !== '' && keptCatalog) {
      payload.append('copy_from_product_id', String(copyFromProductId.value))
      payload.append('copy_media', '0')
    }

    const newFiles: File[] = []
    let fileIndex = 0
    for (const slot of mediaSlots.value) {
      if (slot.kind === 'existing' || slot.kind === 'catalog') {
        if (slot.removed) {
          if (slot.kind === 'existing') payload.append('remove_media_ids[]', String(slot.id))
          continue
        }
        const sequenceKey = slot.kind === 'existing'
          ? `id:${slot.id}`
          : `source:${slot.sourceId}`
        payload.append('media_sequence[]', sequenceKey)
        const cropKey = slot.kind === 'existing' ? String(slot.id) : `source:${slot.sourceId}`
        payload.append(`media_catalog_zoom[${cropKey}]`, String(slot.crop.catalog_zoom))
        payload.append(`media_catalog_pan_x[${cropKey}]`, String(slot.crop.catalog_pan_x))
        payload.append(`media_catalog_pan_y[${cropKey}]`, String(slot.crop.catalog_pan_y))
        continue
      }
      payload.append('media_sequence[]', `file:${fileIndex}`)
      payload.append(`media_catalog_zoom[new:${fileIndex}]`, String(slot.crop.catalog_zoom))
      payload.append(`media_catalog_pan_x[new:${fileIndex}]`, String(slot.crop.catalog_pan_x))
      payload.append(`media_catalog_pan_y[new:${fileIndex}]`, String(slot.crop.catalog_pan_y))
      newFiles.push(slot.file)
      fileIndex += 1
    }
    for (const file of newFiles) {
      payload.append('media[]', file)
    }

    if (editingId.value != null) {
      payload.append('_method', 'PATCH')
      await axios.post(`/api/admin/checkout-items/${editingId.value}`, payload, {
        headers: getAuthHeaders(),
      })
      showToast('Карточка пошива обновлена', 'success')
    } else {
      await axios.post('/api/admin/checkout-items', payload, {
        headers: getAuthHeaders(),
      })
      showToast('Карточка пошива добавлена', 'success')
    }
    resetForm()
    await loadItems()
  } catch (err) {
    showToast(formatApiError(err), 'error')
  } finally {
    saving.value = false
  }
}

async function persistOrder(nextItems: CheckoutItem[]) {
  reordering.value = true
  const previous = items.value
  items.value = nextItems
  try {
    const res = await axios.patch<{ items?: CheckoutItem[] }>(
      '/api/admin/checkout-items/reorder',
      { ids: nextItems.map((item) => item.id) },
      { headers: getAuthHeaders() },
    )
    if (Array.isArray(res.data?.items)) {
      items.value = res.data.items
    }
  } catch (err) {
    items.value = previous
    showToast(formatApiError(err), 'error')
  } finally {
    reordering.value = false
  }
}

function moveItem(index: number, delta: number) {
  const nextIndex = index + delta
  if (nextIndex < 0 || nextIndex >= items.value.length || reordering.value) return
  const next = [...items.value]
  const [row] = next.splice(index, 1)
  if (!row) return
  next.splice(nextIndex, 0, row)
  void persistOrder(next)
}

async function removeItem(item: CheckoutItem) {
  if (!confirm(`Удалить карточку «${item.name}» из индивидуального пошива? Каталог не изменится.`)) return
  try {
    await axios.delete(`/api/admin/checkout-items/${item.id}`, { headers: getAuthHeaders() })
    showToast('Карточка удалена', 'success')
    if (editingId.value === item.id) resetForm()
    await loadItems()
  } catch (err) {
    showToast(formatApiError(err), 'error')
  }
}

onMounted(() => {
  void loadItems()
})
</script>

<template>
  <Header />
  <section class="relative w-full min-h-screen font-sans text-slate-900 bg-neutral-50/50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <Typography as="h1" class="text-3xl md:text-4xl font-light">Индивидуальный пошив</Typography>
      <p class="mt-2 max-w-3xl text-sm text-slate-600">
        Карточки этой страницы видны только в форме заказа. У каждой своя цена, фото и описание.
        Каталог отсюда не меняется.
      </p>

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
          Каталог
        </Button>
        <Button
          :variant="isAdminTabActive('/admin/checkout-items') ? 'primary' : 'secondary'"
          size="sm"
          @click="router.push('/admin/checkout-items')"
        >
          Индивидуальный пошив
        </Button>
        <Button :variant="isAdminTabActive('/admin') ? 'primary' : 'secondary'" size="sm" @click="router.push('/admin')">
          Управление администраторами
        </Button>
      </div>

      <div class="rounded-lg border border-neutral-200 bg-white p-4 sm:p-6 mb-6 shadow-sm">
        <Typography as="h2" class="text-lg font-medium mb-1">{{ formTitle }}</Typography>
        <p class="mb-4 text-sm text-slate-600">
          Заполните название и цену пошива. Товар из каталога можно скопировать кнопкой — каталог при этом не меняется.
        </p>
        <form class="grid gap-4 md:grid-cols-2" @submit.prevent="submitForm">
          <div v-if="editingId == null" class="md:col-span-2 rounded-lg border border-neutral-200 bg-neutral-50 p-3 sm:p-4">
            <p class="text-sm font-medium text-slate-900">Скопировать товар из каталога</p>
            <p class="mt-1 text-sm text-slate-600">
              Необязательно. Кнопка подставляет название, описание и цену в поля ниже. Их можно сразу изменить.
            </p>
            <input
              v-model="productSearch"
              type="search"
              :class="[inputClass, 'mt-3 bg-white']"
              placeholder="Найти товар по названию"
            />
            <p v-if="productsLoadError" class="mt-3 text-sm text-red-600">
              Каталог не загрузился. Обновите страницу.
            </p>
            <p v-else-if="products.length === 0" class="mt-3 text-sm text-slate-600">
              В каталоге пока нет товаров.
              <button type="button" class="ml-1 font-medium text-slate-900 underline" @click="router.push('/admin/products')">
                Открыть каталог
              </button>
            </p>
            <p v-else-if="selectableProducts.length === 0" class="mt-3 text-sm text-slate-600">
              Такого товара в каталоге нет.
            </p>
            <ul v-else class="mt-3 max-h-72 space-y-2 overflow-y-auto">
              <li
                v-for="product in selectableProducts"
                :key="product.id"
                class="flex items-center gap-3 rounded-md border bg-white p-2"
                :class="copyFromProductId === product.id ? 'border-slate-900' : 'border-neutral-200'"
              >
                <img
                  v-if="sampleImage(product)"
                  :src="sampleImage(product)"
                  alt=""
                  class="h-12 w-12 shrink-0 rounded object-cover"
                />
                <div
                  v-else
                  class="flex h-12 w-12 shrink-0 items-center justify-center rounded bg-neutral-100 text-[10px] text-slate-400"
                >
                  нет фото
                </div>
                <div class="min-w-0 flex-1">
                  <p class="truncate text-sm font-medium text-slate-900">{{ product.name }}</p>
                  <p class="text-xs text-slate-600">
                    В каталоге {{ formatPrice(product.price) }} · {{ formatGender(product.gender) }}
                  </p>
                </div>
                <Button
                  type="button"
                  size="sm"
                  :variant="copyFromProductId === product.id ? 'primary' : 'outline'"
                  @click="applySample(product)"
                >
                  {{ copyFromProductId === product.id ? 'Подставлено' : 'Подставить' }}
                </Button>
              </li>
            </ul>
            <div v-if="selectedSample" class="mt-3">
              <Button type="button" variant="outline" size="sm" @click="clearCopy">Убрать фото этого товара</Button>
            </div>
          </div>

          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-slate-700 mb-1">Название</label>
            <input v-model="formName" type="text" :class="inputClass" placeholder="Например: Жилет" />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Цена пошива, ₽</label>
            <input v-model="formPrice" type="text" inputmode="decimal" :class="inputClass" placeholder="3500" />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1" for="sewing-gender">Кому показывать</label>
            <select id="sewing-gender" v-model="formGender" :class="inputClass">
              <option value="all">Всем</option>
              <option value="boys">Только мальчикам</option>
              <option value="girls">Только девочкам</option>
            </select>
            <p class="mt-1 text-xs text-slate-500">Зависит от пола ребёнка на первом шаге заказа.</p>
          </div>
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-slate-700 mb-1">Описание</label>
            <textarea v-model="formDescription" rows="4" :class="inputClass" placeholder="Состав, посадка, чем пошив отличается" />
          </div>
          <div class="flex items-end pb-2">
            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
              <input v-model="formIsActive" type="checkbox" class="rounded border-neutral-300" />
              Показывать родителям в форме заказа
            </label>
          </div>

          <div class="md:col-span-2 space-y-3">
            <p class="text-sm font-medium text-slate-700">Предпросмотр карточки</p>
            <p class="text-xs text-slate-500">
              Стрелки листают фото. Перетащите миниатюры, чтобы сменить порядок. Первая — обложка.
            </p>
            <input
              id="sewing-photos"
              ref="mediaInput"
              type="file"
              accept="image/png,image/jpeg,image/webp"
              multiple
              class="sr-only"
              @change="onMediaChange"
            />
            <div class="w-full max-w-sm overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-sm">
              <div class="relative h-44 w-full overflow-hidden bg-neutral-100">
                <CatalogCropViewport
                  v-if="activePhoto"
                  :key="slotKey(activePhoto.slot)"
                  :src="slotImage(activePhoto.slot)"
                  :crop="activePhoto.slot.crop"
                  class="block h-44 w-full"
                />
                <div
                  v-else
                  class="flex h-full w-full items-center justify-center text-sm text-slate-500"
                >
                  фото
                </div>
                <button
                  v-if="visiblePhotos.length > 1"
                  type="button"
                  class="absolute left-2 top-1/2 z-10 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full bg-black/45 text-white hover:bg-black/65"
                  aria-label="Предыдущее изображение"
                  @click="stepPhoto(-1)"
                >
                  <ChevronLeft :size="18" />
                </button>
                <button
                  v-if="visiblePhotos.length > 1"
                  type="button"
                  class="absolute right-2 top-1/2 z-10 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full bg-black/45 text-white hover:bg-black/65"
                  aria-label="Следующее изображение"
                  @click="stepPhoto(1)"
                >
                  <ChevronRight :size="18" />
                </button>
              </div>
              <div v-if="visiblePhotos.length > 1" class="flex justify-center gap-1.5 bg-white py-2">
                <button
                  v-for="(entry, visibleIndex) in visiblePhotos"
                  :key="`dot-${slotKey(entry.slot)}`"
                  type="button"
                  class="h-2 w-2 rounded-full"
                  :class="visibleIndex === activePhotoVisibleIndex ? 'bg-slate-900' : 'bg-neutral-300'"
                  :aria-label="`Фото ${visibleIndex + 1}`"
                  @click="showPhoto(visibleIndex)"
                />
              </div>
              <div class="space-y-2 p-3">
                <p class="text-sm font-semibold leading-snug text-slate-900">
                  {{ formName.trim() || 'Название' }}
                </p>
                <p
                  v-if="formDescription.trim()"
                  class="line-clamp-3 whitespace-pre-line text-sm leading-snug text-slate-600"
                >
                  {{ formDescription.trim() }}
                </p>
                <p class="text-sm font-medium tabular-nums text-slate-800">{{ draftPriceLabel }}</p>
              </div>
            </div>

            <div class="flex max-w-sm gap-2 overflow-x-auto pb-1">
              <button
                v-for="(entry, visibleIndex) in visiblePhotos"
                :key="`thumb-${slotKey(entry.slot)}`"
                type="button"
                draggable="true"
                class="h-16 w-16 shrink-0 overflow-hidden rounded-md border-2 bg-neutral-100"
                :class="visibleIndex === activePhotoVisibleIndex ? 'border-slate-900' : 'border-transparent'"
                :aria-label="visibleIndex === 0 ? 'Обложка' : `Фото ${visibleIndex + 1}`"
                @click="showPhoto(visibleIndex)"
                @dragstart="onPhotoDragStart(visibleIndex)"
                @dragover.prevent
                @drop.prevent="onPhotoDrop(visibleIndex)"
              >
                <img :src="slotImage(entry.slot)" alt="" draggable="false" class="h-full w-full object-cover" />
              </button>
              <button
                type="button"
                class="flex h-16 w-16 shrink-0 items-center justify-center rounded-md border border-dashed border-neutral-300 bg-white text-center text-[11px] leading-tight text-slate-700 hover:bg-neutral-50"
                @click="mediaInput?.click()"
              >
                Добавить фото
              </button>
            </div>

            <div v-if="activePhoto" class="max-w-sm space-y-2">
              <div class="flex flex-wrap items-center justify-between gap-2">
                <p class="text-sm text-slate-800">
                  {{ activePhotoVisibleIndex === 0 ? 'Обложка' : `Фото ${activePhotoVisibleIndex + 1}` }}
                </p>
                <Button type="button" variant="outline" size="sm" @click="removeSlot(activePhoto.index)">
                  Убрать фото
                </Button>
              </div>
              <CatalogMediaCropEditor
                :image-url="slotImage(activePhoto.slot)"
                :model-value="activePhoto.slot.crop"
                @update:model-value="updateSlotCrop(activePhoto.index, $event)"
              />
            </div>

            <div v-if="removedPhotos.length" class="flex max-w-sm flex-wrap gap-2">
              <Button
                v-for="entry in removedPhotos"
                :key="`restore-${slotKey(entry.slot)}`"
                type="button"
                variant="secondary"
                size="sm"
                @click="restoreSlot(entry.index)"
              >
                Вернуть фото
              </Button>
            </div>
            <p class="text-xs text-slate-500">PNG, JPG или WEBP, до 10 фото.</p>
          </div>

          <div class="md:col-span-2 flex flex-wrap gap-2">
            <Button type="submit" variant="primary" :disabled="saving">
              {{ saving ? 'Сохранение…' : editingId != null ? 'Сохранить' : 'Добавить карточку' }}
            </Button>
            <Button v-if="editingId != null" type="button" variant="outline" @click="resetForm">Отмена</Button>
          </div>
        </form>
      </div>

      <div class="grid gap-6 lg:grid-cols-[minmax(0,1.15fr)_minmax(20rem,0.85fr)] items-start">
        <div class="rounded-lg border border-neutral-200 bg-white p-4 sm:p-6 shadow-sm min-w-0">
          <Typography as="h2" class="text-lg font-medium mb-1">Уже добавленные карточки</Typography>
          <p class="text-sm text-slate-600 mb-4">Стрелки меняют порядок в форме заказа. Справа — как это видит родитель.</p>
          <div v-if="loading" class="text-slate-600 py-8 text-center">Загрузка…</div>
          <div v-else-if="items.length === 0" class="text-slate-600 py-8 text-center">
            Карточек пока нет. Заполните форму выше и нажмите «Добавить карточку».
          </div>
          <div v-else class="overflow-x-auto">
            <table class="w-full text-left text-sm border-collapse min-w-[520px]">
              <thead>
                <tr class="border-b border-slate-200 bg-slate-50/80">
                  <th class="py-2 px-3 font-medium text-slate-700 w-[1%] whitespace-nowrap">Порядок</th>
                  <th class="py-2 px-3 font-medium text-slate-700">Фото</th>
                  <th class="py-2 px-3 font-medium text-slate-700">Название</th>
                  <th class="py-2 px-3 font-medium text-slate-700">Цена</th>
                  <th class="py-2 px-3 font-medium text-slate-700">Для кого</th>
                  <th class="py-2 px-3 font-medium text-slate-700">Видна в заказе</th>
                  <th class="py-2 px-3 font-medium text-slate-700 w-[1%] whitespace-nowrap">Действия</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(item, index) in items" :key="item.id" class="border-b border-slate-100">
                  <td class="py-2 px-3 align-middle">
                    <div class="flex flex-col">
                      <button
                        type="button"
                        class="rounded p-0.5 text-slate-600 hover:bg-slate-100 disabled:text-slate-300 disabled:hover:bg-transparent"
                        :disabled="index === 0 || reordering"
                        aria-label="Выше"
                        @click="moveItem(index, -1)"
                      >
                        <ChevronUp :size="18" />
                      </button>
                      <button
                        type="button"
                        class="rounded p-0.5 text-slate-600 hover:bg-slate-100 disabled:text-slate-300 disabled:hover:bg-transparent"
                        :disabled="index === items.length - 1 || reordering"
                        aria-label="Ниже"
                        @click="moveItem(index, 1)"
                      >
                        <ChevronDown :size="18" />
                      </button>
                    </div>
                  </td>
                  <td class="py-2 px-3 align-middle">
                    <img
                      v-if="itemImageSrc(item)"
                      :src="itemImageSrc(item)"
                      alt=""
                      class="h-12 w-12 rounded object-cover border border-neutral-200"
                    />
                    <span v-else class="text-slate-400 text-xs">—</span>
                  </td>
                  <td class="py-2 px-3 align-middle">{{ item.name }}</td>
                  <td class="py-2 px-3 align-middle tabular-nums">{{ formatPrice(item.price) }}</td>
                  <td class="py-2 px-3 align-middle">{{ formatGender(item.gender) }}</td>
                  <td class="py-2 px-3 align-middle">{{ item.is_active ? 'Да' : 'Нет' }}</td>
                  <td class="py-2 px-3 align-middle">
                    <div class="flex flex-wrap gap-2">
                      <Button type="button" variant="outline" size="sm" @click="fillForm(item)">Изменить</Button>
                      <Button type="button" variant="outline" size="sm" @click="removeItem(item)">Удалить</Button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <aside class="rounded-lg border border-neutral-200 bg-neutral-50 p-4 sm:p-5 shadow-sm lg:sticky lg:top-6">
          <Typography as="h2" class="text-lg font-medium text-slate-900">Предпросмотр карточек у клиента</Typography>
          <p class="mt-1 text-sm text-slate-600">Только просмотр. Кнопки ниже фильтруют карточки по полу ребёнка.</p>
          <div class="mt-3 flex flex-wrap gap-2">
            <button
              type="button"
              class="rounded-full border px-3 py-1 text-xs"
              :class="previewGender === 'all' ? 'border-slate-900 bg-slate-900 text-white' : 'border-neutral-300 bg-white text-slate-700'"
              @click="previewGender = 'all'"
            >
              Все
            </button>
            <button
              type="button"
              class="rounded-full border px-3 py-1 text-xs"
              :class="previewGender === 'boys' ? 'border-slate-900 bg-slate-900 text-white' : 'border-neutral-300 bg-white text-slate-700'"
              @click="previewGender = 'boys'"
            >
              Мальчики
            </button>
            <button
              type="button"
              class="rounded-full border px-3 py-1 text-xs"
              :class="previewGender === 'girls' ? 'border-slate-900 bg-slate-900 text-white' : 'border-neutral-300 bg-white text-slate-700'"
              @click="previewGender = 'girls'"
            >
              Девочки
            </button>
          </div>
          <div v-if="previewItems.length === 0" class="mt-4 text-sm text-slate-500">
            Нет активных карточек для этого фильтра.
          </div>
          <div v-else class="mt-4 grid grid-cols-1 gap-3">
            <div
              v-for="item in previewItems"
              :key="`preview-${item.id}`"
              class="overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-sm"
            >
              <img
                v-if="itemImageSrc(item)"
                :src="itemImageSrc(item)"
                :alt="item.name"
                class="h-36 w-full object-cover bg-neutral-100"
              />
              <div
                v-else
                class="flex h-36 w-full items-center justify-center border-b border-dashed border-neutral-200 bg-neutral-100 text-sm text-slate-500"
              >
                фото
              </div>
              <div class="p-3 space-y-2">
                <p class="text-sm font-semibold text-slate-900 leading-snug">{{ item.name }}</p>
                <p v-if="item.description" class="text-xs text-slate-600 line-clamp-3 whitespace-pre-line">
                  {{ item.description }}
                </p>
                <p class="text-sm font-medium text-slate-800 tabular-nums">Цена: {{ formatPrice(item.price) }}</p>
              </div>
            </div>
          </div>
        </aside>
      </div>
    </div>
  </section>
  <Footer />
</template>
