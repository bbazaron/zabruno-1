<script setup lang="ts">
import { onUnmounted, ref } from 'vue'
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
const personalDataConsent = ref(false)
const loading = ref(false)
const resending = ref(false)
const errorMessage = ref('')
const step = ref<'name' | 'email' | 'code' | 'password'>('name')
const code = ref('')
const registrationToken = ref('')
const resendIn = ref(0)
const RESEND_COOLDOWN_SECONDS = 60
let resendTimer: ReturnType<typeof setInterval> | null = null

const inputClass =
  'w-full border border-neutral-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-slate-500'

const goToLogin = () => {
  router.push('/login')
}

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

function formatRegisterError(err: unknown): string {
  const e = err as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } }
  const data = e.response?.data
  if (!data) return 'Ошибка регистрации'
  if (data.errors) {
    const first = Object.values(data.errors).flat()[0]
    if (first) return first
  }
  if (typeof data.message === 'string' && data.message.length > 0) return data.message
  return 'Ошибка регистрации'
}

function continueFromName() {
  errorMessage.value = ''
  firstName.value = firstName.value.trim()
  lastName.value = lastName.value.trim()
  if (!firstName.value || !lastName.value) {
    errorMessage.value = 'Введите имя и фамилию'
    return
  }
  step.value = 'email'
}

async function sendEmailCode() {
  errorMessage.value = ''
  const value = email.value.trim()
  if (!value || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
    errorMessage.value = 'Укажите корректный email'
    return
  }

  loading.value = true
  try {
    const response = await axios.post('/api/register', {
      firstName: firstName.value,
      lastName: lastName.value,
      email: value,
    }, {
      headers: { Accept: 'application/json' },
    })

    email.value = value
    step.value = 'code'
    code.value = ''
    registrationToken.value = ''
    startResendCooldown()
    showToast(
      typeof response.data?.message === 'string' ? response.data.message : 'Код отправлен на почту',
      'success',
    )
  } catch (err: unknown) {
    errorMessage.value = formatRegisterError(err)
    showToast(errorMessage.value, 'error')
  } finally {
    loading.value = false
  }
}

function onCodeInput(raw: string) {
  code.value = raw.replace(/\D/g, '').slice(0, 6)
  if (errorMessage.value) errorMessage.value = ''
}

async function confirmEmailCode() {
  errorMessage.value = ''
  if (code.value.length !== 6) {
    errorMessage.value = 'Введите шестизначный код из письма'
    return
  }

  loading.value = true
  try {
    const response = await axios.post('/api/register/confirm', {
      email: email.value.trim(),
      code: code.value,
    }, {
      headers: { Accept: 'application/json' },
    })

    const token = response.data?.registration_token
    if (typeof token !== 'string' || token.length === 0) {
      errorMessage.value = 'Не удалось подтвердить почту'
      showToast(errorMessage.value, 'error')
      return
    }

    registrationToken.value = token
    step.value = 'password'
    showToast('Почта подтверждена', 'success')
  } catch (err: unknown) {
    errorMessage.value = formatRegisterError(err)
    showToast(errorMessage.value, 'error')
  } finally {
    loading.value = false
  }
}

async function resendCode() {
  if (loading.value || resendIn.value > 0) return
  errorMessage.value = ''
  loading.value = true
  resending.value = true
  try {
    await axios.post('/api/register/resend', { email: email.value.trim() }, {
      headers: { Accept: 'application/json' },
    })
    code.value = ''
    registrationToken.value = ''
    startResendCooldown()
    showToast('Код отправлен на почту', 'success')
  } catch (err: unknown) {
    const retryAfter = (err as { response?: { data?: { retry_after?: number } } }).response?.data?.retry_after
    if (typeof retryAfter === 'number' && retryAfter > 0) {
      startResendCooldown(retryAfter)
    }
    errorMessage.value = formatRegisterError(err)
    showToast(errorMessage.value, 'error')
  } finally {
    loading.value = false
    resending.value = false
  }
}

async function finishRegistration() {
  errorMessage.value = ''
  if (password.value.length < 6) {
    errorMessage.value = 'Пароль должен быть минимум 6 символов'
    return
  }
  if (password.value !== confirmPassword.value) {
    errorMessage.value = 'Пароли не совпадают'
    showToast(errorMessage.value, 'error')
    return
  }
  if (!personalDataConsent.value) {
    errorMessage.value = 'Необходимо согласие на обработку персональных данных'
    return
  }

  loading.value = true
  try {
    const response = await axios.post('/api/register/complete', {
      email: email.value.trim(),
      registration_token: registrationToken.value,
      password: password.value,
    }, {
      headers: { Accept: 'application/json' },
    })

    const token = response.data?.token
    if (typeof token === 'string' && token.length > 0) {
      localStorage.setItem(AUTH_TOKEN_KEY, token)
    }

    showToast('Вы успешно зарегистрировались! Теперь войдите в аккаунт', 'success')
    router.push('/login')
  } catch (err: unknown) {
    errorMessage.value = formatRegisterError(err)
    showToast(errorMessage.value, 'error')
  } finally {
    loading.value = false
  }
}

function goBack(next: 'name' | 'email') {
  errorMessage.value = ''
  step.value = next
}
</script>

<template>
  <div class="w-full min-h-screen flex flex-col">
    <Header />

    <main class="flex-1 flex items-center justify-center bg-neutral-50 py-16">
      <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
        <Typography as="h1" variant="h2" class="mb-2 text-slate-900">
          Регистрация
        </Typography>
        <p class="mb-6 text-sm text-slate-500">
          Шаг {{ step === 'name' ? 1 : step === 'password' ? 3 : 2 }} из 3
        </p>
        <Typography as="p" variant="body" class="mb-6 text-slate-600">
          <template v-if="step === 'name'">Как к вам обращаться</template>
          <template v-else-if="step === 'email'">Укажите почту. Мы отправим код подтверждения.</template>
          <template v-else-if="step === 'code'">Код отправлен на {{ email }}. Он действует 10 минут.</template>
          <template v-else>Почта подтверждена. Придумайте пароль для входа.</template>
        </Typography>

        <form v-if="step === 'name'" class="space-y-4" @submit.prevent="continueFromName">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1" for="sign-up-first-name">Имя</label>
            <input id="sign-up-first-name" v-model="firstName" type="text" :class="inputClass" placeholder="Введите ваше имя" />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1" for="sign-up-last-name">Фамилия</label>
            <input id="sign-up-last-name" v-model="lastName" type="text" :class="inputClass" placeholder="Введите вашу фамилию" />
          </div>
          <div v-if="errorMessage" class="text-red-600 text-sm">{{ errorMessage }}</div>
          <Button type="submit" variant="primary" class="w-full">Далее</Button>
        </form>

        <form v-else-if="step === 'email'" class="space-y-4" @submit.prevent="sendEmailCode">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1" for="sign-up-email">Почта</label>
            <input id="sign-up-email" v-model="email" type="email" autocomplete="email" :class="inputClass" placeholder="Введите ваш email" />
          </div>
          <div v-if="errorMessage" class="text-red-600 text-sm">{{ errorMessage }}</div>
          <Button type="submit" variant="primary" class="w-full" :disabled="loading">
            {{ loading ? 'Отправка...' : 'Получить код' }}
          </Button>
          <button type="button" class="w-full text-sm text-slate-600 hover:text-slate-900 hover:underline" :disabled="loading" @click="goBack('name')">
            Назад
          </button>
        </form>

        <form v-else-if="step === 'code'" class="space-y-4" @submit.prevent="confirmEmailCode">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1" for="sign-up-code">Код из письма</label>
            <input
              id="sign-up-code"
              :value="code"
              type="text"
              inputmode="numeric"
              autocomplete="one-time-code"
              maxlength="6"
              class="w-full border border-neutral-300 rounded px-3 py-2 text-center text-lg tabular-nums tracking-[0.4em] focus:outline-none focus:ring-2 focus:ring-slate-500"
              placeholder="______"
              @input="onCodeInput(($event.target as HTMLInputElement).value)"
            />
          </div>
          <div v-if="errorMessage" class="text-red-600 text-sm">{{ errorMessage }}</div>
          <Button type="submit" variant="primary" class="w-full" :disabled="loading">
            {{ loading && !resending ? 'Проверка...' : 'Подтвердить почту' }}
          </Button>
          <Button type="button" variant="outline" class="w-full" :disabled="loading || resendIn > 0" @click="resendCode">
            {{ resending ? 'Отправка...' : resendIn > 0 ? `Отправить код заново через ${resendIn} с` : 'Отправить код заново' }}
          </Button>
          <button type="button" class="w-full text-sm text-slate-600 hover:text-slate-900 hover:underline" :disabled="loading" @click="goBack('email')">
            Указать другую почту
          </button>
        </form>

        <form v-else class="space-y-4" @submit.prevent="finishRegistration">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1" for="sign-up-password">Пароль</label>
            <div class="relative">
              <input
                id="sign-up-password"
                v-model="password"
                :type="showPassword ? 'text' : 'password'"
                autocomplete="new-password"
                minlength="6"
                placeholder="Введите пароль"
                :class="[inputClass, 'pr-10']"
              />
              <button
                type="button"
                class="absolute inset-y-0 right-0 flex items-center justify-center px-2.5 text-slate-500 hover:text-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-900 focus-visible:ring-offset-2 rounded-r-md"
                :aria-label="showPassword ? 'Скрыть пароль' : 'Показать пароль'"
                :aria-pressed="showPassword"
                @click="showPassword = !showPassword"
              >
                <svg v-if="!showPassword" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                <svg v-else class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                </svg>
              </button>
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1" for="sign-up-password-confirm">Повторите пароль</label>
            <div class="relative">
              <input
                id="sign-up-password-confirm"
                v-model="confirmPassword"
                :type="showConfirmPassword ? 'text' : 'password'"
                autocomplete="new-password"
                minlength="6"
                placeholder="Повторите пароль"
                :class="[inputClass, 'pr-10']"
              />
              <button
                type="button"
                class="absolute inset-y-0 right-0 flex items-center justify-center px-2.5 text-slate-500 hover:text-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-900 focus-visible:ring-offset-2 rounded-r-md"
                :aria-label="showConfirmPassword ? 'Скрыть пароль' : 'Показать пароль'"
                :aria-pressed="showConfirmPassword"
                @click="showConfirmPassword = !showConfirmPassword"
              >
                <svg v-if="!showConfirmPassword" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                <svg v-else class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                </svg>
              </button>
            </div>
          </div>
          <div class="flex items-start gap-2.5">
            <input
              id="sign-up-pd-consent"
              v-model="personalDataConsent"
              type="checkbox"
              class="mt-0.5 h-4 w-4 shrink-0 rounded border border-neutral-300 text-slate-900 focus:ring-2 focus:ring-slate-500"
            />
            <label for="sign-up-pd-consent" class="text-sm text-slate-700 leading-snug cursor-pointer">
              Я ознакомлен(а) с
              <RouterLink to="/personal-data-policy" class="text-slate-900 underline underline-offset-2 hover:text-slate-700" @click.stop>
                Политикой обработки персональных данных
              </RouterLink>
              и даю согласие на обработку персональных данных
            </label>
          </div>
          <div v-if="errorMessage" class="text-red-600 text-sm">{{ errorMessage }}</div>
          <Button type="submit" variant="primary" class="w-full" :disabled="loading">
            {{ loading ? 'Регистрация...' : 'Завершить регистрацию' }}
          </Button>
        </form>

        <div class="mt-4 text-sm text-slate-600 text-center">
          Уже есть аккаунт?
          <span class="text-slate-900 font-medium hover:underline cursor-pointer" @click="goToLogin">Войти</span>
        </div>
        <div class="mt-2 text-sm text-slate-600 text-center">
          <RouterLink to="/forgot-password" class="text-slate-900 font-medium hover:underline">
            Забыли пароль?
          </RouterLink>
        </div>
      </div>
    </main>

    <Footer />
  </div>
</template>
