<script setup lang="ts">
import { ref, computed, watch, watchEffect } from 'vue'
import axios from 'axios'
import Header from '../components/sections/Header.vue'
import Footer from '../components/sections/Footer.vue'
import Card from '../components/ui/Card.vue'
import Button from '../components/ui/Button.vue'
import Typography from '../components/ui/Typography.vue'
import { ShoppingCart, Filter } from 'lucide-vue-next'
import { useRoute, useRouter } from 'vue-router'
import { resolveBackendMediaUrl } from '../utils/resolveBackendMediaUrl'

type GenderFilter = 'all' | 'boys' | 'girls'

interface CatalogProduct {
  id: number
  name: string
  description: string
  image: string
  gender: string
  category: string
  price: number
  originalPrice?: number
  sizes: string[]
  inStock?: boolean
}

function parseGenderQuery(q: unknown): GenderFilter {
  const s = String(q ?? '')
    .toLowerCase()
    .trim()
  if (s === 'boys' || s === 'boy') return 'boys'
  if (s === 'girls' || s === 'girl') return 'girls'
  return 'all'
}

const API = import.meta.env.VITE_API_URL
const route = useRoute()
const router = useRouter()

const selectedCategory = ref('all')
const sortBy = ref('popular')
const selectedGender = ref<GenderFilter>(parseGenderQuery(route.query.gender))

const products = ref<CatalogProduct[]>([])


async function fetchProducts() {
  try {
    const response = await axios.get(`${API}/api/index`, {
      params: {
        gender: selectedGender.value,
        category: selectedCategory.value,
        sortBy: sortBy.value,
      }
    })
    products.value = response.data
  } catch (err) {
    console.error(err)
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
                  class="group overflow-hidden flex flex-col hover:shadow-lg transition-shadow"
                >
                  <!-- Product Image -->
                  <div class="relative overflow-hidden bg-neutral-100 h-64">
                    <img
                      :src="resolveBackendMediaUrl(product.image)"
                      :alt="product.name"
                      class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                    />
                  </div>

                  <!-- Product Info -->
                  <div class="p-4 flex flex-col flex-1">
                    <Typography as="h3" variant="h4" class="text-slate-900 mb-2 line-clamp-2">
                      {{ product.name }}
                    </Typography>
                    <Typography as="p" variant="small" class="text-slate-600 mb-3 flex-1">
                      {{ product.description }}
                    </Typography>

                    <!-- Sizes -->
                    <div class="mb-4">
                      <div class="flex flex-wrap gap-1">
                        <span
                          v-for="size in product.sizes"
                          :key="size"
                          class="px-2 py-1 text-xs border border-slate-300 rounded text-slate-700"
                        >
                          {{ size }}
                        </span>
                      </div>
                    </div>

                    <!-- Price & CTA -->
                    <div class="border-t border-neutral-200 pt-4">
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
                          @click.stop
                        variant="primary"
                        size="sm"
                        class="w-full gap-2"
                        :disabled="!product.inStock"
                      >
                        <ShoppingCart :size="16" />
                        <span>{{ product.inStock ? 'В корзину' : '' }}</span>
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
