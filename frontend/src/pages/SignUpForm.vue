<script setup lang="ts">
import { ref } from 'vue'
import Typography from '../components/ui/Typography.vue'
import Button from '../components/ui/Button.vue'
import Header from '../components/sections/Header.vue'
import Footer from '../components/sections/Footer.vue'
import axios from 'axios'
import { useRouter } from 'vue-router'
import { useToast } from '../composables/useToast'

const { showToast } = useToast()

const AUTH_TOKEN_KEY = 'auth_token'

const router = useRouter()

const firstName = ref('')
const lastName = ref('')
const email = ref('')
const password = ref('')
const loading = ref(false)
const errorMessage = ref('')

const goToLogin = () => {
  router.push('/login')
}

function formatRegisterError(err: unknown): string {
  const e = err as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } }
  const data = e.response?.data
  if (!data) return 'Ошибка регистрации'
  if (typeof data.message === 'string') return data.message
  if (data.errors) {
    return Object.values(data.errors).flat().join(' ')
  }
  return 'Ошибка регистрации'
}

async function registerUser() {
  errorMessage.value = ''

  if (!firstName.value || !lastName.value || !email.value || !password.value) {
    errorMessage.value = 'Пожалуйста, заполните все поля'
    return
  }

  loading.value = true
  try {
    const response = await axios.post('/api/register', {
      firstName: firstName.value,
      lastName: lastName.value,
      email: email.value,
      password: password.value,
    }, {
      headers: { Accept: 'application/json' },
    })

    const token = response.data?.token
    if (typeof token === 'string' && token.length > 0) {
      localStorage.setItem(AUTH_TOKEN_KEY, token)
    }

    // ✅ Показываем toast
    showToast('Вы успешно зарегистрировались! Теперь войдите в аккаунт', 'success')

    router.push('/login') // редирект на страницу логина
  } catch (err: unknown) {
    console.error(err)
    errorMessage.value = formatRegisterError(err)
    showToast(errorMessage.value, 'error') // тоже через toast
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="w-full min-h-screen flex flex-col">
    <Header />

    <main class="flex-1 flex items-center justify-center bg-neutral-50 py-16">
      <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
        <Typography as="h1" variant="h2" class="mb-6 text-slate-900">
          Регистрация
        </Typography>
        <Typography as="p" variant="body" class="mb-6 text-slate-600">
          Создайте аккаунт, чтобы получить доступ к личному кабинету и сделать заказ
        </Typography>

        <form @submit.prevent="registerUser" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Имя</label>
            <input
              v-model="firstName"
              type="text"
              class="w-full border border-neutral-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-slate-500"
              placeholder="Введите ваше имя"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Фамилия</label>
            <input
              v-model="lastName"
              type="text"
              class="w-full border border-neutral-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-slate-500"
              placeholder="Введите вашу фамилию"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Почта</label>
            <input
              v-model="email"
              type="email"
              class="w-full border border-neutral-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-slate-500"
              placeholder="Введите ваш email"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Пароль</label>
            <input
              v-model="password"
              type="password"
              class="w-full border border-neutral-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-slate-500"
              placeholder="Введите пароль"
            />
          </div>

          <div v-if="errorMessage" class="text-red-600 text-sm">
            {{ errorMessage }}
          </div>

          <Button type="submit" variant="primary" class="w-full" :disabled="loading">
            {{ loading ? 'Регистрация...' : 'Зарегистрироваться' }}
          </Button>
        </form>

        <div class="mt-4 text-sm text-slate-600 text-center">
          Уже есть аккаунт?
          <span
            class="text-slate-900 font-medium hover:underline cursor-pointer"
            @click="goToLogin"
          >Войти</span>
        </div>
      </div>
    </main>

    <Footer />
  </div>
</template>
