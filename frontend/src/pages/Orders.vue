<script setup lang="ts">
import { ref, onMounted } from 'vue'
import Card from '../components/ui/Card.vue'
import Button from '../components/ui/Button.vue'
import Typography from '../components/ui/Typography.vue'
import Header from '../components/sections/Header.vue'
import Footer from '../components/sections/Footer.vue'
import axios from 'axios'
import { useRouter } from 'vue-router'
import { useToast } from '../composables/useToast'

interface BackendOrder {
  id: number
  created_at: string
  status: string
  child_full_name?: string
  parent_full_name?: string
  parent_phone?: string
  school?: string
  items?: Array<{
    id: number
    product_name: string
    quantity: number
  }>
}

function getStoredToken(): string | null {
  return localStorage.getItem('auth_token') || localStorage.getItem('token')
}

const router = useRouter()
const { showToast } = useToast()

const userName = ref<string>('')
const userMail = ref<string>('')
const userLoading = ref(false)
const ordersLoading = ref(false)
const orders = ref<BackendOrder[]>([])

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
  } catch (err: any) {
    if (err?.response?.status === 401) {
      router.push('/login')
      return
    }

    showToast('Не удалось загрузить данные пользователя', 'error')
  } finally {
    userLoading.value = false
  }
}

async function loadOrders() {
  const token = getStoredToken()

  if (!token) {
    router.push('/login')
    return
  }

  ordersLoading.value = true
  try {
    const response = await axios.get('/api/getOrders', {
      headers: {
        Accept: 'application/json',
        Authorization: `Bearer ${token}`,
      },
    })

    orders.value = Array.isArray(response.data?.orders) ? response.data.orders : []
  } catch (err: any) {
    if (err?.response?.status === 401) {
      router.push('/login')
      return
    }

    showToast('Не удалось загрузить заказы', 'error')
  } finally {
    ordersLoading.value = false
  }
}

function formatDate(date: string): string {
  const parsed = new Date(date)
  if (Number.isNaN(parsed.getTime())) {
    return date
  }

  return parsed.toLocaleDateString('ru-RU')
}

function openOrder(orderId: number) {
  router.push(`/orders/${orderId}`)
}

function goToProfile() {
  router.push('/profile')
}

onMounted(() => {
  loadUser()
  loadOrders()
})
</script>

<template>
  <Header />

  <section class="relative w-full min-h-screen font-sans text-slate-900">

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <!-- Заголовок профиля -->
      <div class="flex justify-between items-center mb-8">
        <div>
          <Typography as="h1" class="text-4xl md:text-5xl font-light leading-tight">
            Профиль пользователя
          </Typography>
          <Typography as="p" class="text-slate-600 mt-1">
            Просмотр и управление вашими заказами
          </Typography>

          <Typography
            v-if="userLoading"
            as="p"
            class="text-slate-600 mt-2 text-sm"
          >
            Загрузка профиля...
          </Typography>

          <Typography
            v-else-if="userName"
            as="p"
            class="text-slate-700 mt-2 text-sm"
          >
            {{ userName }} <span v-if="userMail">{{ userMail }}</span>
          </Typography>
        </div>
        <Button variant="secondary" size="md" @click="goToProfile">Редактировать профиль</Button>
      </div>

      <Card class="p-4 mb-4">
        <div v-if="ordersLoading" class="py-6 text-slate-600">
          Загрузка заказов...
        </div>

        <div v-else-if="orders.length === 0" class="py-6 text-slate-600">
          У вас пока нет заказов
        </div>

        <table v-else class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-300">
              <th class="py-2 px-3">№</th>
              <th class="py-2 px-3">Дата</th>
              <th class="py-2 px-3">Статус</th>
              <th class="py-2 px-3">Заказчик</th>
              <th class="py-2 px-3">Действия</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="order in orders"
              :key="order.id"
              class="border-b border-slate-200 hover:bg-slate-50 cursor-pointer"
              @click="openOrder(order.id)"
            >
              <td class="py-2 px-3">#{{ order.id }}</td>
              <td class="py-2 px-3">{{ formatDate(order.created_at) }}</td>
              <td class="py-2 px-3">{{ order.status }}</td>
              <td class="py-2 px-3">{{ order.parent_full_name || '-' }}</td>
              <td class="py-2 px-3">
                <Button size="sm" variant="primary" @click.stop="openOrder(order.id)">Просмотр</Button>
              </td>
            </tr>
          </tbody>
        </table>
      </Card>
    </div>
  </section>
  <Footer />

</template>