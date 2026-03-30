<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import Header from '../components/sections/Header.vue'
import Footer from '../components/sections/Footer.vue'
import { useRoute, useRouter } from 'vue-router'
import Button from '../components/ui/Button.vue'
import Typography from '../components/ui/Typography.vue'
import axios from 'axios'

const route = useRoute()
const router = useRouter()

// Поля формы
const email = ref('')
const password = ref('')
const error = ref('')
const registeredNotice = ref(false)
const REGISTERED_NOTICE_MS = 5000

let registeredNoticeTimer: ReturnType<typeof setTimeout> | null = null

onMounted(() => {
  if (route.query.registered === '1') {
    registeredNotice.value = true
    router.replace({ path: '/login', query: {} })
    registeredNoticeTimer = setTimeout(() => {
      registeredNotice.value = false
      registeredNoticeTimer = null
    }, REGISTERED_NOTICE_MS)
  }
})

onUnmounted(() => {
  if (registeredNoticeTimer !== null) {
    clearTimeout(registeredNoticeTimer)
  }
})

const goToRegister = () => {
  router.push('/signUpForm')
}

// Отправка формы
const login = async () => {
  error.value = ''
  try {
    const response = await axios.post(
      '/api/login',
      {
        email: email.value,
        password: password.value,
      },
      { headers: { Accept: 'application/json' } },
    )

    localStorage.setItem('auth_token', response.data.token)
    localStorage.removeItem('token')

    const redirect = route.query.redirect
    const safe =
      typeof redirect === 'string' &&
      redirect.startsWith('/') &&
      !redirect.startsWith('//')
        ? redirect
        : '/'
    router.push(safe)
  } catch (err: any) {
    if (err.response && err.response.data.message) {
      error.value = err.response.data.message
    } else {
      error.value = 'Ошибка при входе'
    }
  }
}
</script>

<template>
  <Header />
  <div class="min-h-screen flex items-center justify-center bg-neutral-50 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full bg-white rounded-lg shadow-md p-8">
      <Typography as="h1" variant="h2" class="text-slate-900 mb-6 text-center">
        Вход
      </Typography>

      <div
        v-if="registeredNotice"
        class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-center text-sm text-emerald-800"
      >
        Вы зарегистрированы!
      </div>

      <div v-if="error" class="text-red-600 mb-4 text-sm text-center">
        {{ error }}
      </div>

      <form @submit.prevent="login" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1" for="email">Email</label>
          <input
              id="email"
              v-model="email"
              type="email"
              required
              class="w-full border border-neutral-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-transparent"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1" for="password">Пароль</label>
          <input
              id="password"
              v-model="password"
              type="password"
              required
              class="w-full border border-neutral-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-transparent"
          />
        </div>

        <Button type="submit" variant="primary" class="w-full mt-2">
          Войти
        </Button>
      </form>

      <div class="mt-4 text-center text-sm text-slate-600">
        Нет аккаунта?
        <span
            class="text-slate-900 font-medium hover:underline cursor-pointer"
            @click="goToRegister"
        >Зарегистрироваться</span>
      </div>
    </div>
  </div>
  <Footer />
</template>
