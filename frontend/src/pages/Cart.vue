<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import axios from 'axios'
import { useRoute, useRouter } from 'vue-router'
import Header from '../components/sections/Header.vue'
import Footer from '../components/sections/Footer.vue'
import Button from '../components/ui/Button.vue'
import Typography from '../components/ui/Typography.vue'
import { useToast } from '../composables/useToast'
import { resolveBackendMediaUrl } from '../utils/resolveBackendMediaUrl'

interface CartItem {
  id: number
  quantity: number
  selected_size?: string | null
  product: {
    id: number
    name: string
    price: number | string
    image?: string | null
    in_stock?: boolean
  } | null
}

const router = useRouter()
const route = useRoute()
const { showToast } = useToast()
const loading = ref(false)
const items = ref<CartItem[]>([])
const updatingItemId = ref<number | null>(null)
const userName = ref('')
const userMail = ref('')
const userLoading = ref(false)

function isUserTabActive(path: '/orders' | '/cart'): boolean {
  return route.path === path
}

function getStoredToken(): string | null {
  return localStorage.getItem('auth_token') || localStorage.getItem('token')
}

const totalAmount = computed(() => {
  return items.value.reduce((sum, item) => {
    const unitPrice = Number(item.product?.price ?? 0)
    if (!Number.isFinite(unitPrice)) return sum
    return sum + unitPrice * item.quantity
  }, 0)
})

function formatCurrency(value: number): string {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    maximumFractionDigits: 2,
  }).format(value)
}

async function loadCart() {
  const token = getStoredToken()
  if (!token) {
    router.push('/login')
    return
  }

  loading.value = true
  try {
    const response = await axios.get('/api/cart', {
      headers: {
        Accept: 'application/json',
        Authorization: `Bearer ${token}`,
      },
    })
    items.value = Array.isArray(response.data?.items) ? response.data.items : []
  } catch (err: any) {
    if (err?.response?.status === 401) {
      router.push('/login')
      return
    }
    showToast('Не удалось загрузить корзину', 'error')
  } finally {
    loading.value = false
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
  } catch {
    showToast('Не удалось загрузить данные пользователя', 'error')
  } finally {
    userLoading.value = false
  }
}

async function updateQuantity(item: CartItem, nextQuantity: number) {
  if (nextQuantity < 1) return
  const token = getStoredToken()
  if (!token) {
    router.push('/login')
    return
  }

  updatingItemId.value = item.id
  try {
    const response = await axios.patch(
      `/api/cart/${item.id}`,
      { quantity: nextQuantity },
      {
        headers: {
          Accept: 'application/json',
          Authorization: `Bearer ${token}`,
        },
      },
    )
    const updated = response.data?.item
    if (updated) {
      items.value = items.value.map((row) => (row.id === item.id ? updated : row))
    }
  } catch {
    showToast('Не удалось обновить количество', 'error')
  } finally {
    updatingItemId.value = null
  }
}

async function removeItem(itemId: number) {
  const token = getStoredToken()
  if (!token) {
    router.push('/login')
    return
  }

  updatingItemId.value = itemId
  try {
    await axios.delete(`/api/cart/${itemId}`, {
      headers: {
        Accept: 'application/json',
        Authorization: `Bearer ${token}`,
      },
    })
    items.value = items.value.filter((item) => item.id !== itemId)
    showToast('Товар удалён из корзины', 'success')
  } catch {
    showToast('Не удалось удалить товар', 'error')
  } finally {
    updatingItemId.value = null
  }
}

onMounted(() => {
  void loadUser()
  void loadCart()
})
</script>

<template>
  <div class="min-h-screen bg-neutral-50/80">
    <Header />

    <section class="bg-white border-b border-neutral-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6 md:pt-8 pb-8 md:pb-10">
        <div class="text-sm text-slate-600">
          <button
            type="button"
            class="hover:text-slate-900 transition-colors cursor-pointer"
            @click="router.push('/')"
          >
            Главная
          </button>
          <span class="mx-2">/</span>
          <span class="text-slate-900 font-medium">Корзина</span>
        </div>

        <div class="flex flex-col gap-6 sm:flex-row sm:items-start sm:justify-between sm:gap-8 mt-4 md:mt-5">
          <div class="min-w-0 flex-1">
            <h1 class="text-3xl md:text-4xl font-bold leading-tight text-slate-900">Профиль пользователя</h1>
            <Typography as="p" class="text-slate-600 mt-2 text-sm md:text-base leading-relaxed">
              Просмотр и управление вашими заказами
            </Typography>
            <Typography v-if="userLoading" as="p" class="text-slate-600 mt-4 text-sm">Загрузка профиля...</Typography>
            <Typography v-else-if="userName" as="p" class="text-slate-800 mt-4 text-sm md:text-base">
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
            <Button variant="secondary" size="sm" @click="router.push('/profile')">
              Редактировать профиль
            </Button>
          </div>
        </div>
      </div>
    </section>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 md:py-14">
      <h2 class="text-2xl md:text-3xl font-semibold tracking-tight text-slate-900 mb-6">Товары в корзине</h2>

      <div v-if="loading" class="text-slate-600 py-10 text-center">Загрузка корзины...</div>

      <div
        v-else-if="items.length === 0"
        class="rounded-xl border border-dashed border-neutral-300 bg-white py-14 text-center text-slate-600"
      >
        Ваша корзина пуста
      </div>

      <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <section class="lg:col-span-2 space-y-4">
          <article
            v-for="item in items"
            :key="item.id"
            class="rounded-xl border border-neutral-200 bg-white p-4 flex flex-col sm:flex-row gap-4"
          >
            <a
              v-if="item.product?.id"
              :href="`/product/${item.product.id}`"
              target="_blank"
              rel="noopener noreferrer"
              class="shrink-0 h-24 w-24 rounded-lg border border-neutral-200 bg-neutral-100 overflow-hidden"
            >
              <img
                v-if="item.product?.image"
                :src="resolveBackendMediaUrl(item.product?.image)"
                :alt="item.product?.name || 'product'"
                class="h-full w-full object-cover"
              />
            </a>
            <div
              v-else
              class="shrink-0 h-24 w-24 rounded-lg border border-neutral-200 bg-neutral-100 overflow-hidden"
            >
              <img
                v-if="item.product?.image"
                :src="resolveBackendMediaUrl(item.product?.image)"
                :alt="item.product?.name || 'product'"
                class="h-full w-full object-cover"
              />
            </div>

            <div class="flex-1 min-w-0">
              <a
                v-if="item.product?.id"
                :href="`/product/${item.product.id}`"
                target="_blank"
                rel="noopener noreferrer"
                class="font-semibold text-slate-900 hover:text-slate-700 hover:underline underline-offset-2"
              >
                {{ item.product?.name || 'Товар недоступен' }}
              </a>
              <p v-else class="font-semibold text-slate-900">
                {{ item.product?.name || 'Товар недоступен' }}
              </p>
              <p v-if="item.selected_size" class="text-xs text-slate-500 mt-1">
                Размер: {{ item.selected_size }}
              </p>
              <p class="text-slate-600 mt-1">
                {{ formatCurrency(Number(item.product?.price ?? 0)) }}
              </p>
            </div>

            <div class="flex items-center gap-2">
              <Button
                variant="outline"
                size="sm"
                :disabled="updatingItemId === item.id || item.quantity <= 1"
                @click="updateQuantity(item, item.quantity - 1)"
              >
                -
              </Button>
              <span class="min-w-8 text-center">{{ item.quantity }}</span>
              <Button
                variant="outline"
                size="sm"
                :disabled="updatingItemId === item.id"
                @click="updateQuantity(item, item.quantity + 1)"
              >
                +
              </Button>
            </div>

            <Button
              variant="secondary"
              size="sm"
              :disabled="updatingItemId === item.id"
              @click="removeItem(item.id)"
            >
              Удалить
            </Button>
          </article>
        </section>

        <aside class="rounded-xl border border-neutral-200 bg-white p-5 h-fit">
          <p class="text-slate-600">Итого</p>
          <p class="text-2xl font-bold text-slate-900 mt-1">
            {{ formatCurrency(totalAmount) }}
          </p>
          <Button class="w-full mt-4" @click="router.push('/checkout/cart')">
            Оформить заказ
          </Button>
        </aside>
      </div>
    </main>

    <Footer />
  </div>
</template>
