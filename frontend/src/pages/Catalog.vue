<script setup lang="ts">
import { ref, computed, watch, watchEffect } from 'vue'
import axios from 'axios'
import Header from '../components/sections/Header.vue'
import Footer from '../components/sections/Footer.vue'
import Card from '../components/ui/Card.vue'
import Button from '../components/ui/Button.vue'
import Typography from '../components/ui/Typography.vue'
import { ShoppingCart, Filter, ChevronLeft, ChevronRight } from 'lucide-vue-next'
import { useRoute, useRouter } from 'vue-router'
import { resolveBackendMediaUrl } from '../utils/resolveBackendMediaUrl'
import { useToast } from '../composables/useToast'

type GenderFilter = 'all' | 'boys' | 'girls'

interface CatalogProduct {
  id: number
  name: string
  description: string
  image: string
  media: string[]
  gender: string
  category: string
  price: number
  originalPrice?: number
  sizes: string[]
  inStock?: boolean
}

interface BackendCatalogProduct {
  id: number
  name: string
  description: string
  image: string
  media?: string[]
  gender: string
  category: string
  price: number
  original_price?: number | null
  in_stock?: boolean
}

interface CartStateItem {
  itemId: number
  quantity: number
}

function parseGenderQuery(q: unknown): GenderFilter {
  const s = String(q ?? '')
    .toLowerCase()
    .trim()
  if (s === 'boys' || s === 'boy') return 'boys'
  if (s === 'girls' || s === 'girl') return 'girls'
  return 'all'
}

const route = useRoute()
const router = useRouter()
const { showToast } = useToast()

const selectedCategory = ref('all')
const sortBy = ref('popular')
const selectedGender = ref<GenderFilter>(parseGenderQuery(route.query.gender))

const products = ref<CatalogProduct[]>([])
const addingProductId = ref<number | null>(null)
const cartByProductSizeKey = ref<Record<string, CartStateItem>>({})
const selectedSizeByProductId = ref<Record<number, string>>({})
const currentMediaIndexByProductId = ref<Record<number, number>>({})
const AVAILABLE_SIZES = ['XS', 'S', 'M', 'L', 'XL'] as const

function productSizeKey(productId: number, size: string): string {
  return `${productId}:${size}`
}

function getStoredToken(): string | null {
  return localStorage.getItem('auth_token') || localStorage.getItem('token')
}


async function fetchProducts() {
  try {
    const response = await axios.get('/api/index', {
      params: {
        gender: selectedGender.value,
        category: selectedCategory.value,
        sortBy: sortBy.value,
      }
    })
    const rows: BackendCatalogProduct[] = Array.isArray(response.data) ? response.data : []
    products.value = rows.map((row) => {
      const media = Array.isArray(row.media) ? row.media : []
      return {
        id: row.id,
        name: row.name,
        description: row.description,
        image: row.image,
        media: media.length > 0 ? media : row.image ? [row.image] : [],
        gender: row.gender,
        category: row.category,
        price: Number(row.price),
        originalPrice: row.original_price == null ? undefined : Number(row.original_price),
        sizes: [...AVAILABLE_SIZES],
        inStock: Boolean(row.in_stock),
      }
    })
    selectedSizeByProductId.value = products.value.reduce<Record<number, string>>((acc, p) => {
      acc[p.id] = selectedSizeByProductId.value[p.id] ?? 'M'
      return acc
    }, {})
    currentMediaIndexByProductId.value = products.value.reduce<Record<number, number>>((acc, p) => {
      const prev = currentMediaIndexByProductId.value[p.id] ?? 0
      const maxIndex = Math.max(0, p.media.length - 1)
      acc[p.id] = Math.min(prev, maxIndex)
      return acc
    }, {})
    void loadCartState()
  } catch (err) {
    console.error(err)
  }
}

async function loadCartState() {
  const token = getStoredToken()
  if (!token) {
    cartByProductSizeKey.value = {}
    return
  }

  try {
    const response = await axios.get('/api/cart', {
      headers: {
        Accept: 'application/json',
        Authorization: `Bearer ${token}`,
      },
    })

    const rows = Array.isArray(response.data?.items) ? response.data.items : []
    const nextState: Record<string, CartStateItem> = {}

    for (const row of rows) {
      const productId = Number(row?.product_id ?? row?.product?.id)
      const itemId = Number(row?.id)
      const quantity = Number(row?.quantity ?? 0)
      const size = String(row?.selected_size ?? '').trim().toUpperCase()

      if (productId > 0 && itemId > 0 && AVAILABLE_SIZES.includes(size as (typeof AVAILABLE_SIZES)[number])) {
        nextState[productSizeKey(productId, size)] = {
          itemId,
          quantity: quantity > 0 ? quantity : 1,
        }
      }
    }

    cartByProductSizeKey.value = nextState
  } catch {
    // no-op
  }
}

// Загружаем товары при изменении фильтров
watchEffect(() => {
  fetchProducts()
})

watch(
  () => route.query.gender,
  (q) => {
    const next = parseGenderQuery(q)
    if (next !== selectedGender.value) selectedGender.value = next
  },
)

watch(selectedGender, (g) => {
  const nextQuery = { ...route.query }
  if (g === 'all') delete nextQuery.gender
  else nextQuery.gender = g
  void router.replace({ path: route.path, query: nextQuery })
})

const genders: { id: GenderFilter; name: string }[] = [
  { id: 'all', name: 'Все' },
  { id: 'boys', name: 'Для мальчиков' },
  { id: 'girls', name: 'Для девочек' },
]

const categories = [
  { id: 'all', name: 'Все товары' },
  { id: 'cardigans', name: 'Кардиганы' },
  { id: 'vests', name: 'Жилеты' },
  { id: 'skirts', name: 'Юбки' },
  { id: 'pants', name: 'Брюки' },
  { id: 'Комплекты', name: 'Комплекты' },
]

const filteredProducts = computed(() => {
  let filtered = products.value

  if (selectedGender.value !== 'all') {
    filtered = filtered.filter((p) => p.gender === selectedGender.value)
  }
  if (selectedCategory.value !== 'all') {
    filtered = filtered.filter((p) => p.category === selectedCategory.value)
  }

  if (sortBy.value === 'price-asc') {
    filtered = [...filtered].sort((a, b) => a.price - b.price)
  } else if (sortBy.value === 'price-desc') {
    filtered = [...filtered].sort((a, b) => b.price - a.price)
  } else if (sortBy.value === 'new') {
    filtered = [...filtered].reverse()
  }

  return filtered
})

function resetFilters() {
  selectedCategory.value = 'all'
  selectedGender.value = 'all'
}

async function addToCart(product: CatalogProduct) {
  const token = getStoredToken()
  if (!token) {
    router.push('/login')
    return
  }

  const selectedSize = selectedSizeByProductId.value[product.id]
  if (!selectedSize) {
    showToast('Выберите размер', 'error')
    return
  }

  addingProductId.value = product.id
  try {
    const response = await axios.post(
      '/api/cart/add',
      {
        product_id: product.id,
        quantity: 1,
        selected_size: selectedSize,
      },
      {
        headers: {
          Accept: 'application/json',
          Authorization: `Bearer ${token}`,
        },
      },
    )

    const item = response.data?.item
    const itemId = Number(item?.id)
    const quantity = Number(item?.quantity ?? 1)
    if (itemId > 0) {
      cartByProductSizeKey.value = {
        ...cartByProductSizeKey.value,
        [productSizeKey(product.id, selectedSize)]: {
          itemId,
          quantity: quantity > 0 ? quantity : 1,
        },
      }
    }

    showToast('Товар добавлен в корзину', 'success')
  } catch (err: any) {
    const message = String(err?.response?.data?.message ?? '').trim()
    showToast(message || 'Не удалось добавить в корзину', 'error')
  } finally {
    addingProductId.value = null
  }
}

function handleCartButtonClick(product: CatalogProduct) {
  if (cartQuantity(product.id) > 0) {
    void router.push('/cart')
    return
  }
  void addToCart(product)
}

function cartQuantity(productId: number): number {
  const selectedSize = selectedSizeByProductId.value[productId]
  if (!selectedSize) return 0
  return cartByProductSizeKey.value[productSizeKey(productId, selectedSize)]?.quantity ?? 0
}

function resolvedMedia(product: CatalogProduct): string[] {
  return product.media.map((m) => resolveBackendMediaUrl(m)).filter(Boolean)
}

function currentMediaIndex(product: CatalogProduct): number {
  const maxIndex = Math.max(0, resolvedMedia(product).length - 1)
  return Math.min(currentMediaIndexByProductId.value[product.id] ?? 0, maxIndex)
}

function slideOffsetStyle(product: CatalogProduct): string {
  return `translateX(-${currentMediaIndex(product) * 100}%)`
}

function changeProductMedia(product: CatalogProduct, step: number) {
  const media = resolvedMedia(product)
  if (media.length <= 1) return
  const current = currentMediaIndex(product)
  const next = (current + step + media.length) % media.length
  currentMediaIndexByProductId.value = {
    ...currentMediaIndexByProductId.value,
    [product.id]: next,
  }
}

</script>




<template>
  <div class="w-full">
    <!-- Header -->
    <Header />

    <!-- Main Content -->
    <main class="min-h-screen">
      <!-- Breadcrumb & Title Section -->
      <section class="bg-white border-b border-neutral-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
          <div class="mb-4 text-sm text-slate-600">
            <a href="/" class="hover:text-slate-900">Главная</a>
            <span class="mx-2">/</span>
            <span class="text-slate-900 font-medium">Каталог</span>
          </div>
          <div>
            <Typography as="h1" variant="h2" class="text-slate-900 mb-2">
              Каталог школьной формы
            </Typography>
            <Typography as="p" variant="body" class="text-slate-600">
              Выберите понравившийся товар из нашей коллекции натуральной школьной формы
            </Typography>
          </div>
        </div>
      </section>

      <!-- Filters & Products Section -->
      <section class="bg-neutral-50 py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <!-- Filters Row -->
          <div class="grid md:grid-cols-4 gap-6 mb-8">
            <!-- Category Filter -->
            <div class="md:col-span-1">
              <div class="bg-white rounded-lg p-6 border border-neutral-200">
                <h3 class="font-semibold text-slate-900 mb-4">Для кого</h3>
                <div class="space-y-3">
                  <button
                    v-for="g in genders"
                    :key="g.id"
                    type="button"
                    @click="selectedGender = g.id"
                    :class="[
                      'w-full text-left px-3 py-2 rounded-md text-sm transition-colors',
                      selectedGender === g.id
                        ? 'bg-slate-900 text-white font-medium'
                        : 'text-slate-700 hover:bg-neutral-100',
                    ]"
                  >
                    {{ g.name }}
                  </button>
                </div>
              </div>

              <div class="bg-white rounded-lg p-6 border border-neutral-200 mt-4">
                <div class="flex items-center gap-2 mb-4">
                  <Filter :size="18" class="text-slate-900" />
                  <h3 class="font-semibold text-slate-900">Категории</h3>
                </div>
                <div class="space-y-3">
                  <button
                    v-for="category in categories"
                    :key="category.id"
                    @click="selectedCategory = category.id"
                    :class="[
                      'w-full text-left px-3 py-2 rounded-md text-sm transition-colors',
                      selectedCategory === category.id
                        ? 'bg-slate-900 text-white font-medium'
                        : 'text-slate-700 hover:bg-neutral-100'
                    ]"
                  >
                    {{ category.name }}
                  </button>
                </div>
              </div>

              <!-- Sort Filter -->
              <div class="bg-white rounded-lg p-6 border border-neutral-200 mt-4">
                <h3 class="font-semibold text-slate-900 mb-4">Сортировка</h3>
                <div class="space-y-3">
                  <label class="flex items-center gap-3 cursor-pointer">
                    <input
                      type="radio"
                      v-model="sortBy"
                      value="popular"
                      class="w-4 h-4"
                    />
                    <span class="text-sm text-slate-700">По популярности</span>
                  </label>
                  <label class="flex items-center gap-3 cursor-pointer">
                    <input
                      type="radio"
                      v-model="sortBy"
                      value="price-asc"
                      class="w-4 h-4"
                    />
                    <span class="text-sm text-slate-700">Цена: низкая</span>
                  </label>
                  <label class="flex items-center gap-3 cursor-pointer">
                    <input
                      type="radio"
                      v-model="sortBy"
                      value="price-desc"
                      class="w-4 h-4"
                    />
                    <span class="text-sm text-slate-700">Цена: высокая</span>
                  </label>
                  <label class="flex items-center gap-3 cursor-pointer">
                    <input
                      type="radio"
                      v-model="sortBy"
                      value="new"
                      class="w-4 h-4"
                    />
                    <span class="text-sm text-slate-700">Новые</span>
                  </label>
                </div>
              </div>
            </div>

            <!-- Products Grid -->
            <div class="md:col-span-3">
              <div class="flex items-center justify-between mb-4">
                <span class="text-sm text-slate-600">
                  Найдено товаров: <span class="font-semibold text-slate-900">{{ filteredProducts.length }}</span>
                </span>
              </div>


              <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <router-link
                    v-for="product in filteredProducts"
                    :key="product.id"
                    :to="`/product/${product.id}`"
                    class="block"
                >
                <Card
                  :key="product.id"
                  class="group overflow-hidden flex flex-col h-[560px] hover:shadow-lg transition-shadow"
                >
                  <!-- Product Image -->
                  <div class="relative overflow-hidden bg-neutral-100 h-64">
                    <div
                      class="flex h-full transition-transform duration-500 ease-out"
                      :style="{ transform: slideOffsetStyle(product) }"
                    >
                      <div
                        v-for="(mediaUrl, mediaIdx) in resolvedMedia(product)"
                        :key="`${product.id}-${mediaIdx}-${mediaUrl}`"
                        class="h-full w-full min-w-full overflow-hidden"
                      >
                        <img
                          :src="mediaUrl"
                          :alt="product.name"
                          class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                        />
                      </div>
                    </div>
                    <button
                      v-if="resolvedMedia(product).length > 1"
                      type="button"
                      class="absolute left-2 top-1/2 -translate-y-1/2 z-10 flex h-8 w-8 cursor-pointer items-center justify-center rounded-full bg-black/45 text-white text-xl leading-none hover:bg-black/65"
                      @click.stop.prevent="changeProductMedia(product, -1)"
                      aria-label="Предыдущее изображение"
                    >
                      <ChevronLeft :size="18" />
                    </button>
                    <button
                      v-if="resolvedMedia(product).length > 1"
                      type="button"
                      class="absolute right-2 top-1/2 -translate-y-1/2 z-10 flex h-8 w-8 cursor-pointer items-center justify-center rounded-full bg-black/45 text-white text-xl leading-none hover:bg-black/65"
                      @click.stop.prevent="changeProductMedia(product, 1)"
                      aria-label="Следующее изображение"
                    >
                      <ChevronRight :size="18" />
                    </button>
                  </div>

                  <!-- Product Info -->
                  <div class="p-4 flex flex-col flex-1">
                    <Typography as="h3" variant="h4" class="text-slate-900 mb-2 line-clamp-2">
                      {{ product.name }}
                    </Typography>
                    <Typography as="p" variant="small" class="text-slate-600 mb-3 line-clamp-3 min-h-[60px]">
                      {{ product.description }}
                    </Typography>

                    <!-- Sizes -->
                    <div class="mb-4">
                      <div class="flex flex-wrap gap-1">
                        <button
                          v-for="size in product.sizes"
                          :key="size"
                          type="button"
                          class="px-2 py-1 text-xs border rounded transition-colors"
                          :class="
                            selectedSizeByProductId[product.id] === size
                              ? 'border-slate-900 bg-slate-900 text-white'
                              : 'border-slate-300 text-slate-700 hover:bg-neutral-100'
                          "
                          @click.stop.prevent="selectedSizeByProductId[product.id] = size"
                        >
                          {{ size }}
                        </button>
                      </div>
                    </div>

                    <!-- Price & CTA -->
                    <div class="border-t border-neutral-200 pt-4 mt-auto">
                      <div class="flex items-baseline gap-2 mb-4">
                        <span class="text-lg font-bold text-slate-900">{{ product.price }} ₽</span>
                        <span
                          v-if="product.originalPrice != null && product.originalPrice > product.price"
                          class="text-sm text-slate-500 line-through"
                        >
                          {{ product.originalPrice }} ₽
                        </span>
                      </div>
                      <Button
                        @click.stop.prevent="handleCartButtonClick(product)"
                        :variant="cartQuantity(product.id) > 0 ? 'outline' : 'primary'"
                        size="sm"
                        class="w-full gap-2"
                        :class="
                          cartQuantity(product.id) > 0
                            ? '!bg-emerald-600 !text-white !border-emerald-600 hover:!bg-emerald-500'
                            : ''
                        "
                        :disabled="(!product.inStock && cartQuantity(product.id) === 0) || (addingProductId === product.id && cartQuantity(product.id) === 0)"
                      >
                        <ShoppingCart :size="16" />
                        <span v-if="!product.inStock">Нет в наличии</span>
                        <span v-else-if="cartQuantity(product.id) > 0">Перейти в корзину</span>
                        <span v-else>В корзину</span>
                      </Button>
                    </div>
                  </div>
                </Card>
                </router-link>
              </div>

              <!-- Empty State -->
              <div
                v-if="filteredProducts.length === 0"
                class="col-span-full text-center py-12"
              >
                <Typography as="p" variant="body" class="text-slate-600 mb-4">
                  По выбранным фильтрам товаров нет
                </Typography>
                <Button variant="secondary" @click="resetFilters">
                  Сбросить фильтры
                </Button>
              </div>
            </div>
          </div>
        </div>
      </section>
    </main>

    <!-- Footer -->
    <Footer />
  </div>
</template>
