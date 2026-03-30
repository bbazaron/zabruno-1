<script setup lang="ts">
import { onMounted, ref } from 'vue'
import axios from 'axios'
import { useRouter } from 'vue-router'
import Header from '../components/sections/Header.vue'
import Footer from '../components/sections/Footer.vue'
import Button from '../components/ui/Button.vue'
import Typography from '../components/ui/Typography.vue'
import { useToast } from '../composables/useToast'

interface AdminUser {
  id: number
  name: string
  email: string
  role: 'admin' | 'super_admin'
  created_at: string
}

const { showToast } = useToast()

const admins = ref<AdminUser[]>([])
const loading = ref(false)
const creating = ref(false)

const formName = ref('')
const formEmail = ref('')
const formPassword = ref('')
const formRole = ref<'admin' | 'super_admin'>('admin')
const currentRole = ref<string>('user')
const router = useRouter()

function getStoredToken(): string | null {
  return localStorage.getItem('auth_token') || localStorage.getItem('token')
}

async function loadCurrentUserRole(token: string) {
  const response = await axios.get('/api/getUser', {
    headers: {
      Accept: 'application/json',
      Authorization: `Bearer ${token}`,
    },
  })

  currentRole.value = response.data?.role ?? 'user'
}

async function loadAdmins() {
  const token = getStoredToken()
  if (!token) return

  loading.value = true
  try {
    await loadCurrentUserRole(token)

    const response = await axios.get('/api/admin/admins', {
      headers: {
        Accept: 'application/json',
        Authorization: `Bearer ${token}`,
      },
    })

    admins.value = Array.isArray(response.data?.admins) ? response.data.admins : []
  } catch {
    showToast('Не удалось загрузить админов', 'error')
  } finally {
    loading.value = false
  }
}

async function createAdmin() {
  const token = getStoredToken()
  if (!token) return

  creating.value = true
  try {
    await axios.post(
      '/api/admin/admins',
      {
        name: formName.value.trim(),
        email: formEmail.value.trim(),
        password: formPassword.value,
        role: formRole.value,
      },
      {
        headers: {
          Accept: 'application/json',
          Authorization: `Bearer ${token}`,
        },
      },
    )

    showToast('Админ создан', 'success')
    formName.value = ''
    formEmail.value = ''
    formPassword.value = ''
    formRole.value = 'admin'
    await loadAdmins()
  } catch {
    showToast('Не удалось создать админа', 'error')
  } finally {
    creating.value = false
  }
}

onMounted(() => {
  loadAdmins()
})
</script>

<template>
  <Header />
  <section class="relative w-full min-h-screen font-sans text-slate-900">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <Typography as="h1" class="text-3xl md:text-4xl font-light">Админ панель</Typography>
      <div class="mt-2 mb-8 flex gap-3">
        <Button variant="secondary" size="sm" @click="router.push('/admin')">
          Управление администраторами
        </Button>
        <Button variant="secondary" size="sm" @click="router.push('/admin/orders')">
          Управление заказами
        </Button>
      </div>

      <div v-if="currentRole === 'super_admin'" class="mb-8 rounded-lg border border-neutral-200 p-4">
        <Typography as="h2" class="text-lg font-medium mb-4">Создать администратора</Typography>
        <form class="grid md:grid-cols-2 gap-4" @submit.prevent="createAdmin">
          <input v-model="formName" type="text" placeholder="Имя" class="border border-neutral-300 rounded px-3 py-2" />
          <input v-model="formEmail" type="email" placeholder="Email" class="border border-neutral-300 rounded px-3 py-2" />
          <input
            v-model="formPassword"
            type="password"
            placeholder="Пароль (мин. 8 символов)"
            class="border border-neutral-300 rounded px-3 py-2"
          />
          <select v-model="formRole" class="border border-neutral-300 rounded px-3 py-2">
            <option value="admin">admin</option>
            <option value="super_admin">super_admin</option>
          </select>
          <div class="md:col-span-2">
            <Button type="submit" variant="primary" :disabled="creating">
              {{ creating ? 'Создание...' : 'Создать админа' }}
            </Button>
          </div>
        </form>
      </div>

      <div class="rounded-lg border border-neutral-200 p-4">
        <Typography as="h2" class="text-lg font-medium mb-4">Список админов</Typography>

        <div v-if="loading" class="text-slate-600">Загрузка...</div>
        <div v-else-if="admins.length === 0" class="text-slate-600">Админы не найдены</div>

        <table v-else class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-300">
              <th class="py-2 px-3">ID</th>
              <th class="py-2 px-3">Имя</th>
              <th class="py-2 px-3">Email</th>
              <th class="py-2 px-3">Роль</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="admin in admins" :key="admin.id" class="border-b border-slate-200">
              <td class="py-2 px-3">{{ admin.id }}</td>
              <td class="py-2 px-3">{{ admin.name }}</td>
              <td class="py-2 px-3">{{ admin.email }}</td>
              <td class="py-2 px-3">{{ admin.role }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </section>
  <Footer />
</template>
