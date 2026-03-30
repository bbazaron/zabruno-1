<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import Header from '../components/sections/Header.vue'
import Footer from '../components/sections/Footer.vue'
import Card from '../components/ui/Card.vue'
import Typography from '../components/ui/Typography.vue'
import Button from '../components/ui/Button.vue'
import { useToast } from '../composables/useToast'

interface OrderItem {
  id: number
  product_name: string
  quantity: number
  size_override?: string | null
  line_comment?: string | null
}

interface AdminOrderDetails {
  id: number
  status: string
  created_at: string
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
  recipient_name?: string | null
  recipient_phone: string
  user?: {
    id: number
    name: string
    email: string
  } | null
  items: OrderItem[]
}

const route = useRoute()
const router = useRouter()
const { showToast } = useToast()

const loading = ref(false)
const order = ref<AdminOrderDetails | null>(null)
const orderId = computed(() => Number(route.params.id))

/** Совпадает с валидацией backend и комментарием в миграции orders */
const ORDER_STATUSES = [
  { value: 'pending', label: 'Ожидает' },
  { value: 'confirmed', label: 'Подтверждён' },
  { value: 'completed', label: 'Выполнен' },
  { value: 'cancelled', label: 'Отменён' },
] as const

const selectedStatus = ref<string>('pending')
const savingStatus = ref(false)

const statusOptions = computed(() => {
  const s = order.value?.status
  const base = ORDER_STATUSES.map((o) => ({ value: o.value, label: `${o.label} (${o.value})` }))
  if (!s || base.some((o) => o.value === s)) {
    return base
  }
  return [{ value: s, label: `${s} (текущий в БД)` }, ...base]
})

function getStoredToken(): string | null {
  return localStorage.getItem('auth_token') || localStorage.getItem('token')
}

function formatDate(date: string): string {
  const parsed = new Date(date)
  if (Number.isNaN(parsed.getTime())) return date
  return parsed.toLocaleDateString('ru-RU')
}

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
    order.value = response.data?.order ?? null
    if (order.value?.status) {
      selectedStatus.value = order.value.status
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
    showToast('Статус сохранён', 'success')
  } catch {
    showToast('Не удалось сохранить статус', 'error')
  } finally {
    savingStatus.value = false
  }
}

onMounted(() => {
  loadOrder()
})
</script>

<template>
  <Header />
  <section class="relative w-full min-h-screen font-sans text-slate-900">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <div class="mb-6">
        <Button variant="secondary" size="sm" @click="router.push('/admin/orders')">Назад к заказам</Button>
      </div>

      <Card class="p-6">
        <div v-if="loading" class="text-slate-600">Загрузка заказа...</div>
        <div v-else-if="!order" class="text-slate-600">Заказ не найден</div>
        <div v-else class="space-y-6">
          <div>
            <Typography as="h1" class="text-3xl font-light">Заказ #{{ order.id }}</Typography>
            <Typography as="p" class="text-slate-600 mt-1">
              Дата: {{ formatDate(order.created_at) }}
            </Typography>
            <div class="mt-4 flex flex-col gap-2 sm:flex-row sm:items-end sm:flex-wrap">
              <div>
                <label class="block text-xs font-medium text-slate-600 mb-1" for="order-status">Статус заказа</label>
                <select
                  id="order-status"
                  v-model="selectedStatus"
                  class="min-w-[200px] border border-neutral-300 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-slate-900"
                >
                  <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">
                    {{ opt.label }}
                  </option>
                </select>
              </div>
              <Button type="button" variant="primary" size="sm" :disabled="savingStatus" @click="saveStatus">
                {{ savingStatus ? 'Сохранение...' : 'Сохранить статус' }}
              </Button>
            </div>
            <p class="text-xs text-slate-500 mt-2">
              Допустимые статусы заданы в миграции таблицы orders и на сервере: pending, confirmed, completed, cancelled.
            </p>
          </div>

          <div>
            <Typography as="h2" class="text-xl font-medium mb-2">Состав заказа</Typography>
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="border-b border-slate-300">
                  <th class="py-2 px-3">Изделие</th>
                  <th class="py-2 px-3">Количество</th>
                  <th class="py-2 px-3">Размер</th>
                  <th class="py-2 px-3">Комментарий</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in order.items" :key="item.id" class="border-b border-slate-200">
                  <td class="py-2 px-3">{{ item.product_name }}</td>
                  <td class="py-2 px-3">{{ item.quantity }}</td>
                  <td class="py-2 px-3">{{ item.size_override || '-' }}</td>
                  <td class="py-2 px-3">{{ item.line_comment || '-' }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="grid md:grid-cols-2 gap-6">
            <div>
              <Typography as="h3" class="text-lg font-medium mb-2">Данные ребенка</Typography>
              <p>ФИО: {{ order.child_full_name }}</p>
              <p>Пол: {{ order.child_gender }}</p>
              <p>Школа: {{ order.school }}</p>
              <p>Класс: {{ order.class_num }}{{ order.class_letter || '' }}</p>
              <p>Учебный год: {{ order.school_year }}</p>
              <p>Размер по таблице: {{ order.size_from_table }}</p>
            </div>
            <div>
              <Typography as="h3" class="text-lg font-medium mb-2">Данные заказчика</Typography>
              <p>ФИО: {{ order.parent_full_name }}</p>
              <p>Телефон: {{ order.parent_phone }}</p>
              <p>Email: {{ order.parent_email }}</p>
              <p>Населенный пункт: {{ order.settlement }}</p>
              <p>Получатель: {{ order.recipient_name || order.parent_full_name }}</p>
              <p>Телефон получателя: {{ order.recipient_phone }}</p>
            </div>
          </div>
        </div>
      </Card>
    </div>
  </section>
  <Footer />
</template>
