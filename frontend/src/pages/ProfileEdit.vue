<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import Header from '../components/sections/Header.vue'
import Footer from '../components/sections/Footer.vue'
import Button from '../components/ui/Button.vue'
import Typography from '../components/ui/Typography.vue'
import { useToast } from '../composables/useToast'

const router = useRouter()
const { showToast } = useToast()

const name = ref('')
const email = ref('')
const password = ref('')
const loading = ref(false)
const saving = ref(false)

function getStoredToken(): string | null {
  return localStorage.getItem('auth_token') || localStorage.getItem('token')
}

async function loadProfile() {
  const token = getStoredToken()
  if (!token) {
    router.push('/login')
    return
  }

  loading.value = true
  try {
    const response = await axios.get('/api/getUser', {
      headers: {
        Accept: 'application/json',
        Authorization: `Bearer ${token}`,
      },
    })

    name.value = typeof response.data?.name === 'string' ? response.data.name : ''
    email.value = typeof response.data?.mail === 'string' ? response.data.mail : ''
  } catch (err: any) {
    if (err?.response?.status === 401) {
      router.push('/login')
      return
    }
    showToast('Не удалось загрузить профиль', 'error')
  } finally {
    loading.value = false
  }
}

async function saveProfile() {
  const token = getStoredToken()
  if (!token) {
    router.push('/login')
    return
  }

  if (!name.value.trim() || !email.value.trim()) {
    showToast('Имя и почта обязательны', 'error')
    return
  }

  saving.value = true
  try {
    const response = await axios.put('/api/updateProfile', {
      name: name.value.trim(),
      email: email.value.trim(),
      password: password.value.trim() || undefined,
    }, {
      headers: {
        Accept: 'application/json',
        Authorization: `Bearer ${token}`,
      },
    })

    name.value = response.data?.user?.name ?? name.value
    email.value = response.data?.user?.mail ?? email.value
    password.value = ''
    showToast('Профиль сохранен', 'success')
  } catch (err: any) {
    if (err?.response?.status === 401) {
      router.push('/login')
      return
    }
    showToast('Не удалось сохранить профиль', 'error')
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  loadProfile()
})
</script>

<template>
  <Header />
  <section class="relative w-full min-h-screen font-sans text-slate-900">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <Typography as="h1" class="text-3xl md:text-4xl font-light">Редактирование профиля</Typography>
      <Typography as="p" class="text-slate-600 mt-2 mb-8">Измените имя, почту или пароль</Typography>

      <div v-if="loading" class="text-slate-600">Загрузка профиля...</div>

      <form v-else class="space-y-4" @submit.prevent="saveProfile">
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1" for="profile-name">Имя</label>
          <input
            id="profile-name"
            v-model="name"
            type="text"
            class="w-full border border-neutral-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-slate-900"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1" for="profile-email">Почта</label>
          <input
            id="profile-email"
            v-model="email"
            type="email"
            class="w-full border border-neutral-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-slate-900"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1" for="profile-password">Новый пароль</label>
          <input
            id="profile-password"
            v-model="password"
            type="password"
            class="w-full border border-neutral-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-slate-900"
            placeholder="Оставьте пустым, если менять не нужно"
          />
        </div>

        <div class="pt-2 flex gap-3">
          <Button type="submit" variant="primary" :disabled="saving">
            {{ saving ? 'Сохранение...' : 'Сохранить' }}
          </Button>
          <Button type="button" variant="secondary" @click="router.push('/orders')">
            Назад
          </Button>
        </div>
      </form>
    </div>
  </section>
  <Footer />
</template>
