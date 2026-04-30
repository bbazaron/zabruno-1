<script setup lang="ts">
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { useRoute, useRouter } from 'vue-router'

import Header from '../components/sections/Header.vue'
import Footer from '../components/sections/Footer.vue'
import Button from '../components/ui/Button.vue'
import Typography from '../components/ui/Typography.vue'
import { ShoppingCart, ChevronLeft, ChevronRight } from 'lucide-vue-next'
import { resolveBackendMediaUrl } from '../utils/resolveBackendMediaUrl'
import { useToast } from '../composables/useToast'

const route = useRoute()
const router = useRouter()
const productId = route.params.id
const { showToast } = useToast()

const product = ref<any>(null)
const selectedMedia = ref('') // ссылка на текущее главное изображение/видео
const isMediaViewerOpen = ref(false)
const activeMediaIndex = ref(0)
const selectedSize = ref<'XS' | 'S' | 'M' | 'L' | 'XL'>('M')
const cartLoading = ref(false)
const availableSizes: Array<'XS' | 'S' | 'M' | 'L' | 'XL'> = ['XS', 'S', 'M', 'L', 'XL']
const cartBySize = ref<Record<string, number>>({})

async function fetchProduct() {
  try {
    const response = await axios.get(`/api/product/${productId}`, {
      headers: { Accept: 'application/json' },
    })
    product.value = response.data
    if (typeof product.value?.name === 'string' && product.value.name.trim()) {
      document.title = product.value.name.trim()
    }
    selectedMedia.value = resolveBackendMediaUrl(product.value.image) // первое изображение по умолчанию
    await loadCartState()
  } catch (err) {
    console.error(err)
  }
}

onMounted(fetchProduct)

function selectMedia(url: string) {
  selectedMedia.value = resolveBackendMediaUrl(url)
  const mediaList = Array.isArray(product.value?.media) ? product.value.media : []
  const idx = mediaList.findIndex((item: string) => resolveBackendMediaUrl(item) === selectedMedia.value)
  activeMediaIndex.value = idx >= 0 ? idx : 0
}

function mediaListResolved(): string[] {
  const list = Array.isArray(product.value?.media) ? product.value.media : []
  return list.map((item: string) => resolveBackendMediaUrl(item)).filter(Boolean)
}

function openMediaViewer() {
  const list = mediaListResolved()
  if (list.length === 0) return
  const idx = list.findIndex((item) => item === selectedMedia.value)
  activeMediaIndex.value = idx >= 0 ? idx : 0
  isMediaViewerOpen.value = true
}

function closeMediaViewer() {
  isMediaViewerOpen.value = false
}

function goToMediaByStep(step: number) {
  const list = mediaListResolved()
  if (list.length === 0) return
  const next = (activeMediaIndex.value + step + list.length) % list.length
  activeMediaIndex.value = next
  selectedMedia.value = list[next]
}

function addToCart() {
  if (cartQuantityForSelectedSize() > 0) {
    void router.push('/cart')
    return
  }
  void addToCartAction()
}

function getStoredToken(): string | null {
  return localStorage.getItem('auth_token') || localStorage.getItem('token')
}

function cartQuantityForSelectedSize(): number {
  return cartBySize.value[selectedSize.value] ?? 0
}

async function loadCartState() {
  const token = getStoredToken()
  if (!token || !product.value?.id) {
    cartBySize.value = {}
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
    const nextState: Record<string, number> = {}

    for (const row of rows) {
      const rowProductId = Number(row?.product_id ?? row?.product?.id)
      if (rowProductId !== Number(product.value.id)) continue

      const size = String(row?.selected_size ?? '').trim().toUpperCase()
      const quantity = Number(row?.quantity ?? 0)
      if (!availableSizes.includes(size as (typeof availableSizes)[number])) continue

      nextState[size] = quantity > 0 ? quantity : 1
    }

    cartBySize.value = nextState
  } catch {
    // no-op
  }
}

async function addToCartAction() {
  const token = getStoredToken()
  if (!token) {
    router.push('/login')
    return
  }

  if (!product.value?.id) {
    showToast('Товар не найден', 'error')
    return
  }

  cartLoading.value = true
  try {
    await axios.post(
      '/api/cart/add',
      {
        product_id: Number(product.value.id),
        quantity: 1,
        selected_size: selectedSize.value,
      },
      {
        headers: {
          Accept: 'application/json',
          Authorization: `Bearer ${token}`,
        },
      },
    )
    await loadCartState()
    showToast('Товар добавлен в корзину', 'success')
  } catch (err: any) {
    const message = String(err?.response?.data?.message ?? '').trim()
    showToast(message || 'Не удалось добавить в корзину', 'error')
  } finally {
    cartLoading.value = false
  }
}
</script>

<template>
  <div class="w-full">
    <Header />

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
      <div v-if="product" class="grid lg:grid-cols-2 gap-8 lg:gap-12 items-start">
        <!-- Левый блок: галерея -->
        <div class="flex flex-col items-start gap-4">
          <div
            class="w-full max-w-[640px] h-[500px] bg-neutral-100 rounded-lg overflow-hidden cursor-zoom-in"
            @click="openMediaViewer"
          >
            <img :src="selectedMedia" alt="product" class="object-contain h-full w-full" />
          </div>
          <div class="flex flex-wrap gap-3">
            <button
              v-for="media in product.media"
              :key="media"
              @click="selectMedia(media)"
              class="border rounded overflow-hidden hover:border-slate-900"
            >
              <img :src="resolveBackendMediaUrl(media)" class="w-20 h-20 object-cover" />
            </button>
          </div>
        </div>

        <!-- Правый блок: информация о товаре -->
        <div class="flex flex-col gap-4 items-start text-left">
          <Typography as="h1" variant="h2" class="text-slate-900">
            {{ product.name }}
          </Typography>
          <Typography as="p" variant="body" class="text-slate-600">
            {{ product.description }}
          </Typography>
          <div>
            <p class="text-sm font-medium text-slate-700 mb-2">Размер</p>
            <div class="flex flex-wrap gap-2">
              <button
                v-for="size in availableSizes"
                :key="size"
                type="button"
                class="px-3 py-1.5 text-sm rounded-md border transition-colors"
                :class="
                  selectedSize === size
                    ? 'bg-slate-900 text-white border-slate-900'
                    : 'bg-white text-slate-700 border-slate-300 hover:bg-neutral-100'
                "
                @click="selectedSize = size"
              >
                {{ size }}
              </button>
            </div>
          </div>
          <div class="text-2xl font-bold text-slate-900 mt-4">
            {{ product.price }} ₽
            <span
                v-if="product.originalPrice > product.price"
                class="text-base line-through text-slate-500 ml-2"
            >
          {{ product.originalPrice }} ₽
        </span>
          </div>
          <Button
              @click="addToCart"
              :variant="cartQuantityForSelectedSize() > 0 ? 'outline' : 'primary'"
              :disabled="cartLoading"
              class="w-full flex items-center justify-center gap-2 mt-4"
              :class="
                cartQuantityForSelectedSize() > 0
                  ? '!bg-emerald-600 !text-white !border-emerald-600 hover:!bg-emerald-500'
                  : ''
              "
          >
            <ShoppingCart :size="16" />
            <span v-if="cartQuantityForSelectedSize() > 0">Перейти в корзину</span>
            <span v-else>В корзину</span>
          </Button>
        </div>
      </div>

      <!-- Загрузка или пустой продукт -->
      <div v-else class="text-center py-20 text-slate-600">
        Загрузка товара...
      </div>
    </main>

    <Footer />

    <div
      v-if="isMediaViewerOpen"
      class="fixed inset-0 z-50 bg-black/80 flex items-center justify-center p-4"
      @click.self="closeMediaViewer"
    >
      <button
        type="button"
        class="absolute top-4 right-4 text-white text-3xl leading-none hover:text-neutral-300"
        @click="closeMediaViewer"
        aria-label="Закрыть просмотр изображения"
      >
        ×
      </button>

      <div class="relative max-h-[90vh] max-w-[90vw] flex items-center justify-center">
        <button
          type="button"
          class="absolute -left-6 md:-left-8 z-10 flex h-10 w-10 cursor-pointer items-center justify-center rounded-full bg-black/45 text-white hover:bg-black/65"
          @click.stop="goToMediaByStep(-1)"
          aria-label="Предыдущее изображение"
        >
          <ChevronLeft :size="22" />
        </button>

        <img
          :src="mediaListResolved()[activeMediaIndex] || selectedMedia"
          alt="Просмотр изображения товара"
          class="max-h-[90vh] max-w-[90vw] object-contain"
        />

        <button
          type="button"
          class="absolute -right-6 md:-right-8 z-10 flex h-10 w-10 cursor-pointer items-center justify-center rounded-full bg-black/45 text-white hover:bg-black/65"
          @click.stop="goToMediaByStep(1)"
          aria-label="Следующее изображение"
        >
          <ChevronRight :size="22" />
        </button>
      </div>
    </div>
  </div>
</template>