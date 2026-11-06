<script setup lang="ts">
import { onUnmounted, ref } from 'vue'
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
const showPassword = ref(false)
const showPasswordConfirmation = ref(false)
const error = ref('')
const loading = ref(false)
const resending = ref(false)
const resendIn = ref(0)
const RESEND_COOLDOWN_SECONDS = 60
let resendTimer: ReturnType<typeof setInterval> | null = null

function startResendCooldown(seconds = RESEND_COOLDOWN_SECONDS) {
  resendIn.value = seconds
  if (resendTimer) clearInterval(resendTimer)
  resendTimer = setInterval(() => {
    resendIn.value -= 1
    if (resendIn.value <= 0) {
      resendIn.value = 0
      if (resendTimer) clearInterval(resendTimer)
      resendTimer = null
    }
  }, 1000)
}

onUnmounted(() => {
  if (resendTimer) clearInterval(resendTimer)
})

const inputClass =
  'w-full border border-neutral-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-transparent'

function normalizeEmail(value: string): string {
  return value.trim()
}

function screenMessage(message: string, fallback: string): string {
  const text = message.trim()
  if (!text) return fallback
  if (/553|5\.1\.10|free_tier|no valid recipients|expected response code/i.test(text)) {
    return 'Не удалось отправить код на эту почту. Попробуйте другой адрес.'
  }
  return text
}

function formatApiError(err: unknown, fallback: string): string {
  const data = (err as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } })
    .response?.data
  if (data?.errors) {
    const first = Object.values(data.errors).flat()[0]
    if (first) return screenMessage(first, fallback)
  }
  if (typeof data?.message === 'string' && data.message.length > 0) {
    return screenMessage(data.message, fallback)
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
    startResendCooldown()
    showToast('Код отправлен на почту', 'success')
  } catch (err: unknown) {
    error.value = formatApiError(err, 'Не удалось отправить код. Попробуйте снова.')
    showToast(error.value, 'error')
  } finally {
    loading.value = false
  }
}

async function resendCode() {
  if (loading.value || resendIn.value > 0) return
  error.value = ''
  loading.value = true
  resending.value = true
  try {
    await axios.post('/api/password/forgot', { email: email.value })
    code.value = ''
    startResendCooldown()
    showToast('Код отправлен на почту', 'success')
  } catch (err: unknown) {
    const retryAfter = (err as { response?: { data?: { retry_after?: number } } }).response?.data?.retry_after
    if (typeof retryAfter === 'number' && retryAfter > 0) {
      startResendCooldown(retryAfter)
    }
    error.value = formatApiError(err, 'Не удалось отправить код. Попробуйте снова.')
    showToast(error.value, 'error')
  } finally {
    loading.value = false
    resending.value = false
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
    showPassword.value = false
    showPasswordConfirmation.value = false
    showToast('Код подтверждён. Введите новый пароль', 'success')
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
    showToast(error.value, 'error')
    return
  }

  loading.value = true
  try {
    await axios.post('/api/password/reset', {
      email: email.value,
      code: code.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
    })
    showToast('Пароль обновлён. Войдите с новым паролем', 'success')
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
  password: 'Введите новый пароль и повторите его.',
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
          Код отправлен на {{ email }}. Он действует 10 минут.
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
          {{ loading && !resending ? 'Проверка…' : 'Подтвердить код' }}
        </Button>
        <Button
          type="button"
          variant="outline"
          class="w-full"
          :disabled="loading || resendIn > 0"
          @click="resendCode"
        >
          {{ resending ? 'Отправка…' : resendIn > 0 ? `Отправить код заново через ${resendIn} с` : 'Отправить код заново' }}
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
          <label class="block text-sm font-medium text-slate-700 mb-1" for="reset-password">Введите новый пароль</label>
          <div class="relative">
            <input
              id="reset-password"
              v-model="password"
              :type="showPassword ? 'text' : 'password'"
              required
              autocomplete="new-password"
              minlength="6"
              placeholder="Введите новый пароль"
              :class="[inputClass, 'pr-10']"
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
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
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
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
              </svg>
            </button>
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1" for="reset-password-confirm">
            Повторите пароль
          </label>
          <div class="relative">
            <input
              id="reset-password-confirm"
              v-model="passwordConfirmation"
              :type="showPasswordConfirmation ? 'text' : 'password'"
              required
              autocomplete="new-password"
              minlength="6"
              placeholder="Повторите пароль"
              :class="[inputClass, 'pr-10']"
            />
            <button
              type="button"
              class="absolute inset-y-0 right-0 flex items-center justify-center px-2.5 text-slate-500 hover:text-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-900 focus-visible:ring-offset-2 rounded-r-md"
              :aria-label="showPasswordConfirmation ? 'Скрыть пароль' : 'Показать пароль'"
              :aria-pressed="showPasswordConfirmation"
              @click="showPasswordConfirmation = !showPasswordConfirmation"
            >
              <svg
                v-if="!showPasswordConfirmation"
                class="h-5 w-5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2"
                aria-hidden="true"
              >
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
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
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
              </svg>
            </button>
          </div>
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
