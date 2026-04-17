<script setup lang="ts">
import { useRouter } from 'vue-router'
import Header from '../components/sections/Header.vue'
import Footer from '../components/sections/Footer.vue'
import Button from '../components/ui/Button.vue'
import Typography from '../components/ui/Typography.vue'
import { useToast } from '../composables/useToast'
import axios from 'axios'
import { ref } from 'vue'

const router = useRouter()
const { showToast } = useToast()

// Поля формы
const email = ref('')
const password = ref('')
const showPassword = ref(false)
const error = ref('')

const goToRegister = () => {
  router.push('/signUpForm')
}

// Отправка формы
const login = async () => {
  error.value = ''
  try {
    const response = await axios.post('/api/login', {
      email: email.value,
      password: password.value,
    })

    localStorage.setItem('auth_token', response.data.token)


    showToast('Вы вошли в аккаунт!', 'success')

    router.push('/')

  } catch (err: any) {
    showToast('Ошибка входа', 'error')
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
          <div class="relative">
            <input
              id="password"
              v-model="password"
              :type="showPassword ? 'text' : 'password'"
              required
              autocomplete="current-password"
              class="w-full border border-neutral-300 rounded-md px-3 py-2 pr-10 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-transparent"
            />
            <button
              type="button"
              class="absolute inset-y-0 right-0 flex items-center justify-center px-2.5 text-slate-500 hover:text-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-900 focus-visible:ring-offset-2 rounded-r-md"
              :aria-label="showPassword ? 'Скрыть пароль' : 'Показать пароль'"
              :aria-pressed="showPassword"
              @click="showPassword = !showPassword"
            >
              <svg
                v-if="!showPassword"
                class="h-5 w-5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2"
                aria-hidden="true"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                />
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                />
              </svg>
              <svg
                v-else
                class="h-5 w-5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2"
                aria-hidden="true"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"
                />
              </svg>
            </button>
          </div>
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
