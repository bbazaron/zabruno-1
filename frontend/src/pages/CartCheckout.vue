<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import Header from '../components/sections/Header.vue'
import Footer from '../components/sections/Footer.vue'
import Button from '../components/ui/Button.vue'
import { useToast } from '../composables/useToast'

interface CartItem {
  id: number
  quantity: number
  selected_size?: string | null
  product: {
    id: number
    name: string
    price: number | string
  } | null
}

const router = useRouter()
const { showToast } = useToast()
const loading = ref(false)
const submitting = ref(false)
const items = ref<CartItem[]>([])

const form = ref({
  parent_full_name: '',
  parent_phone: '',
  parent_email: '',
  comment: '',
})

function getStoredToken(): string | null {
  return localStorage.getItem('auth_token') || localStorage.getItem('token')
}

const totalAmount = computed(() =>
  items.value.reduce((sum, item) => {
    const price = Number(item.product?.price ?? 0)
    if (!Number.isFinite(price)) return sum
    return sum + price * item.quantity
  }, 0),
)

function formatCurrency(value: number): string {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    maximumFractionDigits: 2,
  }).format(value)
}

async function loadData() {
  const token = getStoredToken()
  if (!token) {
    router.push('/login')
    return
  }

  loading.value = true
  try {
    const [userResponse, cartResponse] = await Promise.all([
      axios.get('/api/getUser', {
        headers: { Accept: 'application/json', Authorization: `Bearer ${token}` },
      }),
      axios.get('/api/cart', {
        headers: { Accept: 'application/json', Authorization: `Bearer ${token}` },
      }),
    ])

    form.value.parent_full_name = String(userResponse.data?.name ?? '').trim()
    form.value.parent_email = String(userResponse.data?.mail ?? '').trim()
    items.value = Array.isArray(cartResponse.data?.items) ? cartResponse.data.items : []
  } catch {
    showToast('Не удалось загрузить данные для оформления', 'error')
  } finally {
    loading.value = false
  }
}

async function submitOrder() {
  const token = getStoredToken()
  if (!token) {
    router.push('/login')
    return
  }
  if (!items.value.length) {
    showToast('Корзина пуста', 'error')
    return
  }

  submitting.value = true
  try {
    const response = await axios.post('/api/createCartOrder', form.value, {
      headers: { Accept: 'application/json', Authorization: `Bearer ${token}` },
    })

    const confirmationUrl = String(response.data?.confirmation_url ?? '').trim()
    if (confirmationUrl) {
      window.location.href = confirmationUrl
      return
    }

    showToast('Заказ оформлен', 'success')
    router.push('/orders')
  } catch (err: any) {
    const message = String(err?.response?.data?.message ?? '').trim()
    showToast(message || 'Не удалось оформить заказ', 'error')
  } finally {
    submitting.value = false
  }
}

onMounted(() => {
  void loadData()
})
</script>

<template>
  <div class="min-h-screen bg-neutral-50/80">
    <Header />
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 md:py-14">
      <h1 class="text-3xl md:text-4xl font-bold text-slate-900">Оформление заказа</h1>
      <p class="text-slate-600 mt-2">Готовая одежда по общим размерам</p>

      <div v-if="loading" class="mt-8 text-slate-600">Загрузка...</div>
      <div v-else class="mt-8 grid grid-cols-1 lg:grid-cols-3 gap-6">
        <section class="lg:col-span-2 rounded-xl border border-neutral-200 bg-white p-5 space-y-4">
          <div>
            <label class="block text-sm text-slate-600 mb-1">Имя</label>
            <input
              v-model="form.parent_full_name"
              type="text"
              class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm"
            />
          </div>
          <div>
            <label class="block text-sm text-slate-600 mb-1">Телефон</label>
            <input
              v-model="form.parent_phone"
              type="text"
              class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm"
            />
          </div>
          <div>
            <label class="block text-sm text-slate-600 mb-1">Почта</label>
            <input
              v-model="form.parent_email"
              type="email"
              class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm"
            />
          </div>
          <div>
            <label class="block text-sm text-slate-600 mb-1">Комментарий</label>
            <textarea
              v-model="form.comment"
              rows="4"
              class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm"
            />
          </div>
        </section>

        <aside class="rounded-xl border border-neutral-200 bg-white p-5 h-fit">
          <h2 class="text-lg font-semibold text-slate-900 mb-3">Ваш заказ</h2>
          <div class="space-y-2 text-sm">
            <div v-for="item in items" :key="item.id" class="flex justify-between gap-3">
              <span class="text-slate-700">
                {{ item.product?.name }} x{{ item.quantity }}
                <span v-if="item.selected_size" class="text-slate-500">({{ item.selected_size }})</span>
              </span>
              <span class="text-slate-900">
                {{ formatCurrency(Number(item.product?.price ?? 0) * item.quantity) }}
              </span>
            </div>
          </div>
          <div class="mt-4 border-t pt-4">
            <p class="text-slate-600">Итого</p>
            <p class="text-2xl font-bold text-slate-900">{{ formatCurrency(totalAmount) }}</p>
          </div>
          <Button class="w-full mt-4" :disabled="submitting || items.length === 0" @click="submitOrder">
            {{ submitting ? 'Оформление...' : 'Оплатить и оформить' }}
          </Button>
        </aside>
      </div>
    </main>
    <Footer />
  </div>
</template>
