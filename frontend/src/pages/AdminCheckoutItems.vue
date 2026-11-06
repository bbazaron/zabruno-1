<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import { ChevronDown, ChevronUp } from 'lucide-vue-next'
import Header from '../components/sections/Header.vue'
import Footer from '../components/sections/Footer.vue'
import Button from '../components/ui/Button.vue'
import Typography from '../components/ui/Typography.vue'
import { useToast } from '../composables/useToast'
import { resolveBackendMediaUrl } from '../utils/resolveBackendMediaUrl'

const AUTH_TOKEN_KEY = 'auth_token'

interface ProductCategory {
  id: string
  label: string
}

interface CatalogProduct {
  id: number
  name: string
  price: string | number
  gender: string
  category: string
  image: string | null
  image_url?: string | null
  in_use: boolean
}

interface CheckoutItem {
  id: number
  product_id: number | null
  name: string
  price: string | number
  image: string | null
  image_url?: string | null
  gender: 'boys' | 'girls' | 'all' | string
  sort_order: number
  is_active: boolean
  source?: 'catalog' | 'new'
}

const router = useRouter()
const route = useRoute()
const { showToast } = useToast()

const items = ref<CheckoutItem[]>([])
const products = ref<CatalogProduct[]>([])
const categories = ref<ProductCategory[]>([])
const loading = ref(false)
const saving = ref(false)
const editingId = ref<number | null>(null)

const formSource = ref<'catalog' | 'new'>('catalog')
const formProductId = ref<number | ''>('')
const productSearch = ref('')
const formName = ref('')
const formPrice = ref('')
const formGender = ref<'boys' | 'girls' | 'all'>('all')
const formCategory = ref('')
const formIsActive = ref(true)
const reordering = ref(false)
const previewGender = ref<'all' | 'boys' | 'girls'>('all')
const formImageFile = ref<File | null>(null)
const formImagePreview = ref('')
const existingImageUrl = ref<string | null>(null)

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

function productImageSrc(product: CatalogProduct | CheckoutItem): string {
  return resolveBackendMediaUrl(
    'image_url' in product ? product.image_url || product.image : product.image,
  )
}

function formatApiError(err: unknown): string {
  const e = err as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } }
  const data = e.response?.data
  if (!data) return 'Ошибка запроса'
  if (typeof data.message === 'string') return data.message
  if (data.errors) return Object.values(data.errors).flat().join(' ')
  return 'Ошибка запроса'
}

const selectableProducts = computed(() => {
  const q = productSearch.value.trim().toLowerCase()
  return products.value.filter((product) => {
    const takenByOther =
      product.in_use &&
      (editingId.value == null ||
        items.value.find((item) => item.id === editingId.value)?.product_id !== product.id)
    if (takenByOther) return false
    if (!q) return true
    return product.name.toLowerCase().includes(q) || String(product.id).includes(q)
  })
})

const selectedProduct = computed(() =>
  formProductId.value === ''
    ? null
    : (products.value.find((product) => product.id === formProductId.value) ?? null),
)

function applyProductToForm(product: CatalogProduct) {
  formProductId.value = product.id
  formName.value = product.name
  formPrice.value = String(product.price ?? '')
  formGender.value =
    product.gender === 'boys' || product.gender === 'girls' || product.gender === 'all'
      ? product.gender
      : 'all'
  formCategory.value = product.category || formCategory.value
  if (!formImagePreview.value) {
    existingImageUrl.value = productImageSrc(product) || null
  }
}

function onProductSelect(event: Event) {
  const raw = (event.target as HTMLSelectElement).value
  const id = raw === '' ? '' : Number(raw)
  formProductId.value = id === '' || !Number.isFinite(id) ? '' : id
  const product = selectedProduct.value
  if (product) applyProductToForm(product)
}

function resetForm() {
  if (formImagePreview.value) URL.revokeObjectURL(formImagePreview.value)
  editingId.value = null
  formSource.value = 'catalog'
  formProductId.value = ''
  productSearch.value = ''
  formName.value = ''
  formPrice.value = ''
  formGender.value = 'all'
  formCategory.value = categories.value[0]?.id ?? ''
  formIsActive.value = true
  formImageFile.value = null
  formImagePreview.value = ''
  existingImageUrl.value = null
}

function fillForm(item: CheckoutItem) {
  if (formImagePreview.value) URL.revokeObjectURL(formImagePreview.value)
  editingId.value = item.id
  formSource.value = item.product_id ? 'catalog' : 'new'
  formProductId.value = item.product_id ?? ''
  productSearch.value = ''
  formName.value = item.name
  formPrice.value = String(item.price ?? '')
  formGender.value =
    item.gender === 'boys' || item.gender === 'girls' || item.gender === 'all' ? item.gender : 'all'
  const linked = products.value.find((product) => product.id === item.product_id)
  formCategory.value = linked?.category || categories.value[0]?.id || ''
  formIsActive.value = Boolean(item.is_active)
  formImageFile.value = null
  formImagePreview.value = ''
  existingImageUrl.value = productImageSrc(item) || null
}

function onImageChange(event: Event) {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0] ?? null
  if (formImagePreview.value) URL.revokeObjectURL(formImagePreview.value)
  formImageFile.value = file
  formImagePreview.value = file ? URL.createObjectURL(file) : ''
  input.value = ''
}

const formTitle = computed(() =>
  editingId.value != null ? `Редактирование карточки №${editingId.value}` : 'Добавить карточку на шаг 3',
)

const previewSrc = computed(() => {
  if (formImagePreview.value) return formImagePreview.value
  return existingImageUrl.value ?? ''
})

watch(formSource, (source) => {
  if (source === 'catalog' && formProductId.value !== '' && selectedProduct.value) {
    applyProductToForm(selectedProduct.value)
  }
})

async function loadItems() {
  loading.value = true
  try {
    const res = await axios.get<{
      items?: CheckoutItem[]
      products?: CatalogProduct[]
      categories?: ProductCategory[]
    }>('/api/admin/checkout-items', {
      headers: getAuthHeaders(),
    })
    items.value = Array.isArray(res.data?.items) ? res.data.items : []
    products.value = Array.isArray(res.data?.products) ? res.data.products : []
    categories.value = Array.isArray(res.data?.categories) ? res.data.categories : []
    if (!formCategory.value && categories.value[0]) {
      formCategory.value = categories.value[0].id
    }
  } catch {
    showToast('Не удалось загрузить карточки изделий', 'error')
    items.value = []
  } finally {
    loading.value = false
  }
}

async function submitForm() {
  if (formSource.value === 'catalog' && formProductId.value === '') {
    showToast('Выберите товар из каталога', 'error')
    return
  }
  if (!formName.value.trim()) {
    showToast('Укажите название', 'error')
    return
  }
  const priceNum = parseFloat(String(formPrice.value).replace(',', '.'))
  if (!Number.isFinite(priceNum) || priceNum < 0) {
    showToast('Укажите корректную цену', 'error')
    return
  }
  if (formSource.value === 'new' && !formCategory.value) {
    showToast('Выберите категорию — товар будет добавлен в каталог', 'error')
    return
  }

  saving.value = true
  try {
    const payload = new FormData()
    payload.append('source', formSource.value)
    if (formSource.value === 'catalog') {
      payload.append('product_id', String(formProductId.value))
    } else {
      payload.append('category', formCategory.value)
    }
    payload.append('name', formName.value.trim())
    payload.append('price', String(priceNum))
    payload.append('gender', formGender.value)
    payload.append('is_active', formIsActive.value ? '1' : '0')
    if (formImageFile.value) payload.append('image_file', formImageFile.value)

    if (editingId.value != null) {
      payload.append('_method', 'PATCH')
      await axios.post(`/api/admin/checkout-items/${editingId.value}`, payload, {
        headers: getAuthHeaders(),
      })
      showToast('Карточка обновлена', 'success')
    } else {
      await axios.post('/api/admin/checkout-items', payload, {
        headers: getAuthHeaders(),
      })
      showToast(
        formSource.value === 'new' ? 'Товар создан в каталоге и добавлен на шаг 3' : 'Карточка добавлена',
        'success',
      )
    }
    resetForm()
    await loadItems()
  } catch (err) {
    showToast(formatApiError(err), 'error')
  } finally {
    saving.value = false
  }
}

const previewItems = computed(() =>
  items.value.filter((item) => {
    if (!item.is_active) return false
    if (previewGender.value === 'all') return true
    return item.gender === previewGender.value || item.gender === 'all'
  }),
)

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
  if (!confirm(`Убрать карточку «${item.name}» с шага 3? Товар в каталоге останется.`)) return
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
      <Typography as="h1" class="text-3xl md:text-4xl font-light">Изделия на шаге заказа</Typography>
      <p class="mt-2 text-sm text-slate-600">
        Карточки шага 3 берутся из каталога. Выберите уже добавленный товар или создайте новый, если его ещё нет.
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
          Управление товарами
        </Button>
        <Button
          :variant="isAdminTabActive('/admin/checkout-items') ? 'primary' : 'secondary'"
          size="sm"
          @click="router.push('/admin/checkout-items')"
        >
          Изделия
        </Button>
        <Button :variant="isAdminTabActive('/admin') ? 'primary' : 'secondary'" size="sm" @click="router.push('/admin')">
          Управление администраторами
        </Button>
      </div>

      <div class="rounded-lg border border-neutral-200 bg-white p-4 sm:p-6 mb-6 shadow-sm">
        <Typography as="h2" class="text-lg font-medium mb-4">{{ formTitle }}</Typography>
        <form class="grid gap-4 md:grid-cols-2" @submit.prevent="submitForm">
          <fieldset class="md:col-span-2 space-y-2">
            <legend class="text-sm font-medium text-slate-700 mb-1">Откуда взять карточку</legend>
            <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
              <input v-model="formSource" type="radio" value="catalog" class="accent-slate-900" />
              Товар из каталога
            </label>
            <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
              <input v-model="formSource" type="radio" value="new" class="accent-slate-900" />
              Новая карточка — товара нет в каталоге
            </label>
          </fieldset>

          <template v-if="formSource === 'catalog'">
            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-slate-700 mb-1">Поиск по каталогу</label>
              <input
                v-model="productSearch"
                type="search"
                :class="inputClass"
                placeholder="Название или id товара"
              />
            </div>
            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-slate-700 mb-1">Товар</label>
              <select :class="inputClass" :value="formProductId === '' ? '' : String(formProductId)" @change="onProductSelect">
                <option value="">Выберите товар</option>
                <option v-for="product in selectableProducts" :key="product.id" :value="String(product.id)">
                  {{ product.name }} — {{ formatPrice(product.price) }} ({{ formatGender(product.gender) }})
                </option>
              </select>
              <p v-if="selectableProducts.length === 0" class="mt-1 text-xs text-slate-500">
                Нет свободных товаров. Создайте новый ниже или добавьте товар в разделе «Управление товарами».
              </p>
            </div>
          </template>

          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-slate-700 mb-1">Название</label>
            <input v-model="formName" type="text" :class="inputClass" placeholder="Например: Жилет" />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Цена, ₽</label>
            <input v-model="formPrice" type="text" inputmode="decimal" :class="inputClass" placeholder="3500" />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Для кого</label>
            <select v-model="formGender" :class="inputClass">
              <option value="all">Все</option>
              <option value="boys">Мальчики</option>
              <option value="girls">Девочки</option>
            </select>
          </div>
          <div v-if="formSource === 'new'">
            <label class="block text-sm font-medium text-slate-700 mb-1">Категория в каталоге</label>
            <select v-model="formCategory" :class="inputClass">
              <option v-for="category in categories" :key="category.id" :value="category.id">
                {{ category.label }}
              </option>
            </select>
            <p class="mt-1 text-xs text-slate-500">Товар появится и в каталоге, и на шаге 3.</p>
          </div>
          <div class="flex items-end pb-2">
            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
              <input v-model="formIsActive" type="checkbox" class="rounded border-neutral-300" />
              Показывать в форме заказа
            </label>
          </div>
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-slate-700 mb-1">Фото</label>
            <input type="file" accept="image/png,image/jpeg,image/webp" @change="onImageChange" />
            <div v-if="previewSrc" class="mt-3 flex items-center gap-3">
              <img :src="previewSrc" alt="" class="h-20 w-20 rounded object-cover border border-neutral-200" />
            </div>
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
          <Typography as="h2" class="text-lg font-medium mb-1">Карточки на шаге 3</Typography>
          <p class="text-sm text-slate-600 mb-4">Передвигайте стрелками — справа сразу видно, как это увидит родитель.</p>
          <div v-if="loading" class="text-slate-600 py-8 text-center">Загрузка…</div>
          <div v-else-if="items.length === 0" class="text-slate-600 py-8 text-center">
            Карточек пока нет — выберите товар из каталога или создайте новый.
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
                  <th class="py-2 px-3 font-medium text-slate-700">Показывать в форме</th>
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
                      v-if="productImageSrc(item)"
                      :src="productImageSrc(item)"
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
                      <button
                        type="button"
                        class="text-slate-700 underline text-xs hover:text-slate-900"
                        @click="fillForm(item)"
                      >
                        Изменить
                      </button>
                      <button
                        type="button"
                        class="text-red-600 underline text-xs hover:text-red-800"
                        @click="removeItem(item)"
                      >
                        Удалить
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <aside class="rounded-lg border border-neutral-200 bg-neutral-50 p-4 sm:p-5 shadow-sm lg:sticky lg:top-6">
          <Typography as="h2" class="text-lg font-medium text-slate-900">Предпросмотр формы</Typography>
          <p class="mt-1 text-sm text-slate-600">
            Отметьте, что необходимо сшить для ребенка
          </p>
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
                v-if="productImageSrc(item)"
                :src="productImageSrc(item)"
                :alt="item.name"
                class="h-36 w-full object-cover bg-neutral-100"
              />
              <div
                v-else
                class="flex h-36 w-full items-center justify-center border-b border-dashed border-neutral-200 bg-neutral-100 text-sm text-slate-500"
              >
                фото
              </div>
              <div class="p-3 space-y-3">
                <div>
                  <p class="text-sm font-semibold text-slate-900 leading-snug">{{ item.name }}</p>
                  <p class="mt-1 text-sm font-medium text-slate-800 tabular-nums">
                    Цена: {{ formatPrice(item.price) }}
                  </p>
                </div>
                <div>
                  <span class="block text-xs font-medium text-slate-600 mb-1.5">Количество</span>
                  <div
                    class="flex h-8 w-full max-w-[10.5rem] items-stretch overflow-hidden rounded border border-neutral-200 bg-white"
                  >
                    <span class="flex min-w-0 flex-1 items-center justify-center bg-neutral-50 text-lg font-bold text-slate-400">
                      −
                    </span>
                    <span class="flex min-w-0 flex-1 items-center justify-center text-base font-bold tabular-nums text-slate-900">
                      0
                    </span>
                    <span class="flex min-w-0 flex-1 items-center justify-center bg-neutral-50 text-lg font-bold text-slate-400">
                      +
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </aside>
      </div>
    </div>
  </section>
  <Footer />
</template>
