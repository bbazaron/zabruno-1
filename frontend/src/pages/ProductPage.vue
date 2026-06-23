<script setup lang="ts">
import { computed, ref, onMounted } from 'vue'
import axios from 'axios'
import { useRoute, useRouter } from 'vue-router'

import Header from '../components/sections/Header.vue'
import Footer from '../components/sections/Footer.vue'
import Button from '../components/ui/Button.vue'
import Typography from '../components/ui/Typography.vue'
import { ShoppingCart, ChevronLeft, ChevronRight } from 'lucide-vue-next'
import { resolveBackendMediaUrl, resolveProductMediaUrl } from '../utils/resolveBackendMediaUrl'
import { useToast } from '../composables/useToast'
import {
  parseSchoolColorOptions,
  SCHOOL_COLOR_OTHER_LABEL,
  SCHOOL_COLOR_OTHER_VALUE,
} from '../utils/productSchoolColors'
import {
  defaultSelectedSize,
  isProductSizeAllowed,
  parseProductSizes,
} from '../utils/productSizes'
import {
  normalizeSelectedGender,
  productGenderRequiresChoice,
  selectedGenderFromProduct,
  selectedGenderLabel,
  type SelectedItemGender,
} from '../utils/productGender'

const route = useRoute()
const router = useRouter()
const productId = route.params.id
const { showToast } = useToast()

const product = ref<any>(null)
const selectedMedia = ref('') // ссылка на текущее главное изображение/видео
const isMediaViewerOpen = ref(false)
const activeMediaIndex = ref(0)
const selectedSize = ref('')
const schoolColorChoice = ref('')
const customSchoolColor = ref('')
const selectedClassNumber = ref('')
const selectedClassLetter = ref('')
const selectedItemGender = ref<SelectedItemGender>('boy')
const cartLoading = ref(false)
const availableSizes = ref<string[]>([])
const availableClassNumbers = Array.from({ length: 11 }, (_, i) => String(i + 1))
const availableClassLetters = ['А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ж', 'З', 'И', 'К', 'Л', 'М']
const cartByVariant = ref<Record<string, number>>({})

type ProductMediaEntry = string | { url?: string | null }

const selectClass =
  'w-full max-w-md px-3 py-2 text-sm rounded-md border border-slate-300 bg-white focus:outline-none focus:ring-2 focus:ring-slate-900'

const schoolColorOptions = computed(() => parseSchoolColorOptions(product.value?.color))

const productRequiresGenderChoice = computed(() =>
  productGenderRequiresChoice(product.value?.gender),
)

const fixedProductGender = computed(() => selectedGenderFromProduct(product.value?.gender))

const displayedProductGender = computed(() => {
  if (fixedProductGender.value) {
    return selectedGenderLabel(fixedProductGender.value)
  }
  return selectedGenderLabel(selectedItemGender.value)
})

const productInStock = computed(() => Boolean(product.value?.inStock))

function cartQuantityTotal(): number {
  return Object.values(cartByVariant.value).reduce((sum, qty) => sum + qty, 0)
}

function syncSelectedGenderFromProduct() {
  const fixed = selectedGenderFromProduct(product.value?.gender)
  if (fixed) {
    selectedItemGender.value = fixed
    return
  }
  if (!normalizeSelectedGender(selectedItemGender.value)) {
    selectedItemGender.value = 'boy'
  }
}

const effectiveSchoolColor = computed((): string => {
  if (schoolColorChoice.value === SCHOOL_COLOR_OTHER_VALUE) {
    return customSchoolColor.value.trim()
  }
  return schoolColorChoice.value.trim()
})

function isKnownSchoolColor(value: string): boolean {
  const normalized = value.trim()
  if (!normalized) return false
  return schoolColorOptions.value.some(
    (option) => option.localeCompare(normalized, undefined, { sensitivity: 'accent' }) === 0,
  )
}

function syncSchoolColorFromCartValue(value: string) {
  const normalized = value.trim()
  if (!normalized) {
    schoolColorChoice.value = schoolColorOptions.value[0] ?? ''
    customSchoolColor.value = ''
    return
  }
  if (isKnownSchoolColor(normalized)) {
    schoolColorChoice.value =
      schoolColorOptions.value.find(
        (option) => option.localeCompare(normalized, undefined, { sensitivity: 'accent' }) === 0,
      ) ?? normalized
    customSchoolColor.value = ''
    return
  }
  schoolColorChoice.value = SCHOOL_COLOR_OTHER_VALUE
  customSchoolColor.value = normalized
}

async function fetchProduct() {
  try {
    const response = await axios.get(`/api/product/${productId}`, {
      headers: { Accept: 'application/json' },
    })
    product.value = response.data
    availableSizes.value = parseProductSizes(product.value?.sizes)
    selectedSize.value = defaultSelectedSize(availableSizes.value)
    if (typeof product.value?.name === 'string' && product.value.name.trim()) {
      document.title = product.value.name.trim()
    }
    schoolColorChoice.value = schoolColorOptions.value[0] ?? ''
    customSchoolColor.value = ''
    syncSelectedGenderFromProduct()
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
  const idx = mediaList.findIndex(
    (item: ProductMediaEntry) => resolveProductMediaUrl(item) === selectedMedia.value,
  )
  activeMediaIndex.value = idx >= 0 ? idx : 0
}

function mediaListResolved(): string[] {
  const list = Array.isArray(product.value?.media) ? product.value.media : []
  return list
    .map((item: ProductMediaEntry) => resolveProductMediaUrl(item))
    .filter(Boolean)
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
  if (cartQuantityTotal() > 0) {
    void router.push('/cart')
    return
  }
  if (!productInStock.value) return
  void addToCartAction()
}

function getStoredToken(): string | null {
  return localStorage.getItem('auth_token') || localStorage.getItem('token')
}

function cartVariantKey(
  size: string,
  color: string,
  classLabel: string,
  gender: SelectedItemGender,
): string {
  return `${size}|${color.trim().toLowerCase()}|${classLabel.trim().toUpperCase()}|${gender}`
}

function selectedClassLabel(): string {
  if (!selectedClassNumber.value || !selectedClassLetter.value) return ''
  return `${selectedClassNumber.value}${selectedClassLetter.value}`
}

function resetSelectedClass(): void {
  selectedClassNumber.value = ''
  selectedClassLetter.value = ''
}

async function loadCartState() {
  const token = getStoredToken()
  if (!token || !product.value?.id) {
    cartByVariant.value = {}
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

      const size = String(row?.selected_size ?? '').trim()
      const color = String(row?.selected_color ?? '').trim()
      const classLabel = String(row?.selected_class ?? '').trim().toUpperCase()
      const gender = normalizeSelectedGender(row?.selected_gender) ?? fixedProductGender.value ?? 'boy'
      const quantity = Number(row?.quantity ?? 0)
      if (!isProductSizeAllowed(size, availableSizes.value)) continue

      nextState[cartVariantKey(size, color, classLabel, gender)] = quantity > 0 ? quantity : 1

      if (quantity > 0 && color) {
        syncSchoolColorFromCartValue(color)
      }
      if (quantity > 0 && productRequiresGenderChoice.value) {
        selectedItemGender.value = gender
      }
    }

    cartByVariant.value = nextState
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
  if (!productInStock.value) {
    showToast('Товар отсутствует в наличии', 'error')
    return
  }
  const sizeToSend =
    availableSizes.value.length > 0
      ? defaultSelectedSize(availableSizes.value, selectedSize.value)
      : null

  if (schoolColorOptions.value.length > 0) {
    if (!schoolColorChoice.value) {
      showToast('Выберите школу и цвет', 'error')
      return
    }
    if (schoolColorChoice.value === SCHOOL_COLOR_OTHER_VALUE && !customSchoolColor.value.trim()) {
      showToast('Укажите школу и цвет в поле «Другое»', 'error')
      return
    }
  }

  const colorToSend = effectiveSchoolColor.value || schoolColorOptions.value[0] || null
  const genderToSend = fixedProductGender.value ?? selectedItemGender.value

  if (productRequiresGenderChoice.value && !genderToSend) {
    showToast('Выберите пол', 'error')
    return
  }

  cartLoading.value = true
  try {
    await axios.post(
      '/api/cart/add',
      {
        product_id: Number(product.value.id),
        quantity: 1,
        selected_size: sizeToSend,
        selected_color: colorToSend,
        selected_class: selectedClassLabel() || null,
        selected_gender: genderToSend,
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
              :key="resolveProductMediaUrl(media)"
              @click="selectMedia(resolveProductMediaUrl(media))"
              class="border rounded overflow-hidden hover:border-slate-900"
            >
              <img :src="resolveProductMediaUrl(media)" class="w-20 h-20 object-cover" />
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
          <p v-if="product.season" class="text-sm text-slate-600">
            Сезон: <span class="font-medium text-slate-900">{{ product.season }}</span>
          </p>
          <div class="w-full max-w-md space-y-2">
            <p class="text-sm font-medium text-slate-700">Пол</p>
            <p v-if="fixedProductGender" class="text-sm text-slate-900">
              {{ displayedProductGender }}
            </p>
            <div v-else class="flex flex-wrap gap-2">
              <button
                type="button"
                class="px-3 py-1.5 text-sm rounded-md border transition-colors"
                :class="
                  selectedItemGender === 'boy'
                    ? 'bg-slate-900 text-white border-slate-900'
                    : 'bg-white text-slate-700 border-slate-300 hover:bg-neutral-100'
                "
                @click="selectedItemGender = 'boy'"
              >
                Мальчик
              </button>
              <button
                type="button"
                class="px-3 py-1.5 text-sm rounded-md border transition-colors"
                :class="
                  selectedItemGender === 'girl'
                    ? 'bg-slate-900 text-white border-slate-900'
                    : 'bg-white text-slate-700 border-slate-300 hover:bg-neutral-100'
                "
                @click="selectedItemGender = 'girl'"
              >
                Девочка
              </button>
            </div>
          </div>
          <div v-if="schoolColorOptions.length > 0" class="w-full max-w-md space-y-2">
            <label for="product-school-color" class="block text-sm font-medium text-slate-700">
              Школа / цвет
            </label>
            <select id="product-school-color" v-model="schoolColorChoice" :class="selectClass">
              <option value="" disabled>Выберите школу и цвет</option>
              <option v-for="option in schoolColorOptions" :key="option" :value="option">
                {{ option }}
              </option>
              <option :value="SCHOOL_COLOR_OTHER_VALUE">{{ SCHOOL_COLOR_OTHER_LABEL }}</option>
            </select>
            <div v-if="schoolColorChoice === SCHOOL_COLOR_OTHER_VALUE">
              <label for="product-school-color-custom" class="sr-only">Укажите школу и цвет</label>
              <input
                id="product-school-color-custom"
                v-model="customSchoolColor"
                type="text"
                :class="selectClass"
                placeholder="Например: Школа №15, синий"
                maxlength="255"
              />
            </div>
          </div>
          <div v-if="availableSizes.length > 0">
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
          <div>
            <p class="text-sm font-medium text-slate-700 mb-2">Надпись (необязательно)</p>
            <div class="flex flex-wrap gap-2 items-center">
              <select
                v-model="selectedClassNumber"
                class="px-3 py-1.5 text-sm rounded-md border border-slate-300 bg-white"
              >
                <option value="">Класс</option>
                <option v-for="number in availableClassNumbers" :key="number" :value="number">
                  {{ number }}
                </option>
              </select>
              <select
                v-model="selectedClassLetter"
                class="px-3 py-1.5 text-sm rounded-md border border-slate-300 bg-white"
              >
                <option value="">Литера</option>
                <option v-for="letter in availableClassLetters" :key="letter" :value="letter">
                  {{ letter }}
                </option>
              </select>
              <div v-if="selectedClassLabel()" class="flex items-center gap-2">
                <span class="text-sm text-slate-600">
                  Будет напечатано: <span class="font-medium text-slate-900">{{ selectedClassLabel() }}</span>
                </span>
                <button
                  type="button"
                  class="px-3 py-1.5 text-sm rounded-md border border-red-200 bg-red-50 text-red-700 hover:bg-red-100"
                  @click="resetSelectedClass"
                >
                  Сброс
                </button>
              </div>
            </div>
          </div>
          <div class="text-2xl font-bold text-slate-900 mt-4">
            {{ product.price }} ₽
            <span
                v-if="product.originalPrice !== null && product.originalPrice !== undefined && product.originalPrice !== ''"
                class="text-base line-through text-slate-500 ml-2"
            >
          {{ product.originalPrice }} ₽
        </span>
          </div>
          <Button
              @click="addToCart"
              :variant="cartQuantityTotal() > 0 ? 'outline' : 'primary'"
              :disabled="(!productInStock && cartQuantityTotal() === 0) || cartLoading"
              class="w-full flex items-center justify-center gap-2 mt-4"
              :class="
                cartQuantityTotal() > 0
                  ? '!bg-emerald-600 !text-white !border-emerald-600 hover:!bg-emerald-500'
                  : ''
              "
          >
            <ShoppingCart :size="16" />
            <span v-if="!productInStock && cartQuantityTotal() === 0">Нет в наличии</span>
            <span v-else-if="cartQuantityTotal() > 0">Перейти в корзину</span>
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