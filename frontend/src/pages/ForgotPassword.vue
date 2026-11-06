<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import Header from '../components/sections/Header.vue'
import Footer from '../components/sections/Footer.vue'
import Button from '../components/ui/Button.vue'
import Typography from '../components/ui/Typography.vue'
import { useToast } from '../composables/useToast'

const router = useRouter()
const { showToast } = useToast()

const step = ref<'email' | 'code' | 'password'>('email')
const email = ref('')
const code = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const error = ref('')
const loading = ref(false)

const inputClass =
  'w-full border border-neutral-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-transparent'

function normalizeEmail(value: string): string {
  return value.trim()
}

function formatApiError(err: unknown, fallback: string): string {
  const data = (err as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } })
    .response?.data
  if (data?.errors) {
    const first = Object.values(data.errors).flat()[0]
    if (first) return first
  }
  if (typeof data?.message === 'string' && data.message.length > 0) {
    return data.message
  }
  return fallback
}

async function submitEmail() {
  error.value = ''
  const value = normalizeEmail(email.value)
  if (!value || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
    error.value = 'Укажите корректный email'
    return
  }

  loading.value = true
  try {
    await axios.post('/api/password/forgot', { email: value })
    email.value = value
    step.value = 'code'
    code.value = ''
    showToast('Если такой аккаунт есть, мы отправим код на почту', 'success')
  } catch (err: unknown) {
    error.value = formatApiError(err, 'Не удалось отправить код. Попробуйте снова.')
    showToast(error.value, 'error')
  } finally {
    loading.value = false
  }
}

function onCodeInput(raw: string) {
  code.value = raw.replace(/\D/g, '').slice(0, 6)
  if (error.value) error.value = ''
}

async function submitCode() {
  error.value = ''
  if (code.value.length !== 6) {
    error.value = 'Введите шестизначный код из письма'
    return
  }

  loading.value = true
  try {
    await axios.post('/api/password/verify', {
      email: email.value,
      code: code.value,
    })
    step.value = 'password'
    password.value = ''
    passwordConfirmation.value = ''
    showToast('Код подтверждён. Придумайте новый пароль', 'success')
  } catch (err: unknown) {
    error.value = formatApiError(err, 'Неверный или просроченный код')
    showToast(error.value, 'error')
  } finally {
    loading.value = false
  }
}

async function submitPassword() {
  error.value = ''
  if (password.value.length < 6) {
    error.value = 'Пароль должен быть минимум 6 символов'
    return
  }
  if (password.value !== passwordConfirmation.value) {
    error.value = 'Пароли не совпадают'
    return
  }

  loading.value = true
  try {
    const response = await axios.post('/api/password/reset', {
      email: email.value,
      code: code.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
    })
    const message =
      typeof response.data?.message === 'string'
        ? response.data.message
        : 'Пароль обновлён. Войдите с новым паролем'
    showToast(message, 'success')
    await router.push('/login')
  } catch (err: unknown) {
    error.value = formatApiError(err, 'Не удалось обновить пароль')
    showToast(error.value, 'error')
  } finally {
    loading.value = false
  }
}

function backToEmail() {
  step.value = 'email'
  code.value = ''
  password.value = ''
  passwordConfirmation.value = ''
  error.value = ''
}

const subtitle = {
  email: 'Укажите почту аккаунта. Мы отправим шестизначный код.',
  code: '',
  password: 'Придумайте новый пароль для входа.',
}
</script>

<template>
  <Header />
  <div class="min-h-screen flex items-center justify-center bg-neutral-50 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full bg-white rounded-lg shadow-md p-8">
      <Typography as="h1" variant="h2" class="text-slate-900 mb-2 text-center">
        Восстановление пароля
      </Typography>
      <p class="text-sm text-slate-600 text-center mb-6">
        <template v-if="step === 'email'">
          {{ subtitle.email }}
        </template>
        <template v-else-if="step === 'code'">
          Код отправлен на {{ email }}. Введите его ниже.
        </template>
        <template v-else>
          {{ subtitle.password }}
        </template>
      </p>

      <div v-if="error" class="text-red-600 mb-4 text-sm text-center">
        {{ error }}
      </div>

      <form v-if="step === 'email'" class="space-y-4" @submit.prevent="submitEmail">
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1" for="reset-email">Email</label>
          <input
            id="reset-email"
            v-model="email"
            type="email"
            required
            autocomplete="email"
            :class="inputClass"
            placeholder="name@example.com"
          />
        </div>
        <Button type="submit" variant="primary" class="w-full mt-2" :disabled="loading">
          {{ loading ? 'Отправка…' : 'Отправить код' }}
        </Button>
      </form>

      <form v-else-if="step === 'code'" class="space-y-4" @submit.prevent="submitCode">
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1" for="reset-code">Код из письма</label>
          <input
            id="reset-code"
            :value="code"
            type="text"
            inputmode="numeric"
            autocomplete="one-time-code"
            maxlength="6"
            :class="[inputClass, 'tabular-nums tracking-[0.4em] text-center text-lg']"
            placeholder="______"
            @input="onCodeInput(($event.target as HTMLInputElement).value)"
          />
        </div>
        <Button type="submit" variant="primary" class="w-full mt-2" :disabled="loading">
          {{ loading ? 'Проверка…' : 'Подтвердить код' }}
        </Button>
        <button
          type="button"
          class="w-full text-sm text-slate-600 hover:text-slate-900 hover:underline"
          :disabled="loading"
          @click="backToEmail"
        >
          Указать другую почту
        </button>
      </form>

      <form v-else class="space-y-4" @submit.prevent="submitPassword">
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1" for="reset-password">Новый пароль</label>
          <input
            id="reset-password"
            v-model="password"
            type="password"
            required
            autocomplete="new-password"
            minlength="6"
            :class="inputClass"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1" for="reset-password-confirm">
            Повторите пароль
          </label>
          <input
            id="reset-password-confirm"
            v-model="passwordConfirmation"
            type="password"
            required
            autocomplete="new-password"
            minlength="6"
            :class="inputClass"
          />
        </div>
        <Button type="submit" variant="primary" class="w-full mt-2" :disabled="loading">
          {{ loading ? 'Сохранение…' : 'Сохранить пароль' }}
        </Button>
        <button
          type="button"
          class="w-full text-sm text-slate-600 hover:text-slate-900 hover:underline"
          :disabled="loading"
          @click="backToEmail"
        >
          Указать другую почту
        </button>
      </form>

      <div class="mt-4 text-center text-sm text-slate-600">
        Вспомнили пароль?
        <span class="text-slate-900 font-medium hover:underline cursor-pointer" @click="router.push('/login')">
          Войти
        </span>
      </div>
    </div>
  </div>
  <Footer />
</template>
