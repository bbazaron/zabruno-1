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
const confirmPassword = ref('')
const showPassword = ref(false)
const showConfirmPassword = ref(false)
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

  if (!firstName.value || !lastName.value || !email.value || !password.value || !confirmPassword.value) {
    errorMessage.value = 'Пожалуйста, заполните все поля'
    return
  }
  if (password.value !== confirmPassword.value) {
    errorMessage.value = 'Пароли не совпадают'
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
            <div class="relative">
              <input
                v-model="password"
                :type="showPassword ? 'text' : 'password'"
                autocomplete="new-password"
                class="w-full border border-neutral-300 rounded px-3 py-2 pr-10 focus:outline-none focus:ring-2 focus:ring-slate-500"
                placeholder="Введите пароль"
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

          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Повторите пароль</label>
            <div class="relative">
              <input
                v-model="confirmPassword"
                :type="showConfirmPassword ? 'text' : 'password'"
                autocomplete="new-password"
                class="w-full border border-neutral-300 rounded px-3 py-2 pr-10 focus:outline-none focus:ring-2 focus:ring-slate-500"
                placeholder="Повторите пароль"
              />
              <button
                type="button"
                class="absolute inset-y-0 right-0 flex items-center justify-center px-2.5 text-slate-500 hover:text-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-900 focus-visible:ring-offset-2 rounded-r-md"
                :aria-label="showConfirmPassword ? 'Скрыть пароль' : 'Показать пароль'"
                :aria-pressed="showConfirmPassword"
                @click="showConfirmPassword = !showConfirmPassword"
              >
                <svg
                  v-if="!showConfirmPassword"
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

          <div v-if="errorMessage" class="text-red-600 text-sm">
            {{ errorMessage }}
          </div>

          <Button type="submit" variant="primary" class="w-full gap-2" :disabled="loading">
            <svg
              v-if="loading"
              class="h-4 w-4 animate-spin"
              viewBox="0 0 24 24"
              fill="none"
              aria-hidden="true"
            >
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
            </svg>
            <span>{{ loading ? 'Регистрация...' : 'Зарегистрироваться' }}</span>
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
