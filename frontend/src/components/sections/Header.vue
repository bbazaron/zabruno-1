<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import axios from 'axios'
import Button from '../ui/Button.vue'
import { Menu, X, LogIn, LogOut, ShoppingBag } from 'lucide-vue-next'
import { useToast } from '../../composables/useToast'

const { showToast } = useToast()
const router = useRouter()
const mobileMenuOpen = ref(false)
const logoutLoading = ref(false)
const showLogoutConfirm = ref(false)
const currentUserRole = ref<'user' | 'admin' | 'manager' | 'super_admin'>('user')

function getStoredToken(): string | null {
  return localStorage.getItem('auth_token') || localStorage.getItem('token')
}

function clearStoredTokens() {
  localStorage.removeItem('auth_token')
  localStorage.removeItem('token')
}

function hasAuthToken(): boolean {
  return !!getStoredToken()?.length
}

const isLoggedIn = ref(false)
const adminOrderRoles = new Set(['admin', 'manager', 'super_admin'])

function canManageOrders(): boolean {
  return adminOrderRoles.has(currentUserRole.value)
}

async function syncCurrentUserRole() {
  const token = getStoredToken()
  if (!token) {
    currentUserRole.value = 'user'
    return
  }

  try {
    const response = await axios.get('/api/getUser', {
      headers: {
        Accept: 'application/json',
        Authorization: `Bearer ${token}`,
      },
    })
    const role = response.data?.role
    currentUserRole.value = adminOrderRoles.has(role) ? role : 'user'
  } catch {
    currentUserRole.value = 'user'
  }
}

function syncAuthFromStorage() {
  isLoggedIn.value = hasAuthToken()
  void syncCurrentUserRole()
}

onMounted(() => {
  syncAuthFromStorage()
  window.addEventListener('storage', syncAuthFromStorage)
})

onUnmounted(() => {
  window.removeEventListener('storage', syncAuthFromStorage)
})

watch(
  () => router.currentRoute.value.fullPath,
  () => {
    syncAuthFromStorage()
  },
)

const navItems = [
  { label: 'ШКОЛЬНАЯ ФОРМА', to: '/catalog' },
  { label: 'КАК ЗАКАЗАТЬ', to: '/how-to-order' },
  { label: 'О РАЗМЕРЕ', to: '/sizes' },
  { label: 'О НАС', to: '/about' },
  { label: 'КОНТАКТЫ', to: '/contacts' },
]

/** Закрыть мобильное меню только при обычном переходе в той же вкладке */
function onNavLinkClick(e: MouseEvent) {
  if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || e.button !== 0) return
  mobileMenuOpen.value = false
}

const goToLogin = () => {
  mobileMenuOpen.value = false
  router.push('/login')
}

function goToOrders() {

  if (!hasAuthToken()) {
    mobileMenuOpen.value = false
    router.push('/login')
    return
  }

  router.push(canManageOrders() ? '/admin/orders' : '/cart')
  mobileMenuOpen.value = false
}

function openLogoutConfirm() {
  showLogoutConfirm.value = true
}

function cancelLogout() {
  showLogoutConfirm.value = false
}

async function confirmLogout() {
  showLogoutConfirm.value = false
  await performLogout()
}

async function performLogout() {
  const token = getStoredToken()
  logoutLoading.value = true
  try {
    if (token) {
      await axios.post(
          '/api/logout',
          {},
          {
            headers: {
              Accept: 'application/json',
              Authorization: `Bearer ${token}`,
            },
          },
      )
    }
  } catch {
    // всё равно очищаем локальную сессию
  } finally {
    clearStoredTokens()
    syncAuthFromStorage()
    currentUserRole.value = 'user'
    mobileMenuOpen.value = false
    logoutLoading.value = false

    showToast('Вы вышли из аккаунта', 'success')

    if (router.currentRoute.value.path !== '/') {
      router.replace({ path: '/' })
    }
  }
}
</script>

<template>
  <header class="sticky top-0 bg-white border-b border-neutral-200 z-50">
    <nav class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 py-3 sm:py-4 flex items-center justify-between gap-2">
      <!-- Logo -->
      <div class="flex min-w-0 items-center gap-2 cursor-pointer" @click="router.push('/')">
        <div class="h-9 w-9 sm:w-10 sm:h-10 shrink-0 bg-slate-900 rounded-lg flex items-center justify-center text-white font-bold text-xs sm:text-sm">
          ШФ
        </div>
        <div class="min-w-0 flex flex-col leading-tight">
          <span class="truncate text-xs sm:text-sm font-semibold text-slate-900">ШКОЛЬНАЯ ФОРМА</span>
          <span class="hidden min-[420px]:block truncate text-[10px] sm:text-xs text-slate-600">СОБСТВЕННОЕ ПРОИЗВОДСТВО</span>
        </div>
      </div>

      <!-- Desktop Navigation -->
      <div class="hidden md:flex items-center gap-8">
        <RouterLink
          v-for="item in navItems"
          :key="item.label"
          :to="item.to"
          class="text-sm font-medium transition-colors text-slate-600 hover:text-slate-900"
        >
          {{ item.label }}
        </RouterLink>
      </div>

      <!-- Right Actions -->
      <div class="flex shrink-0 items-center gap-2 sm:gap-4">
        <button
          v-if="!isLoggedIn"
          type="button"
          class="flex items-center gap-2 px-2 py-2 text-slate-600 hover:text-slate-900 cursor-pointer"
          aria-label="Войти"
          @click="goToLogin"
        >
          <span class="hidden sm:inline text-sm font-medium">Войти</span>
          <LogIn :size="20" class="shrink-0" aria-hidden="true" />
        </button>
        <button
          v-else
          type="button"
          class="flex items-center gap-2 px-2 py-2 text-slate-600 hover:text-slate-900 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
          :disabled="logoutLoading"
          aria-label="Выйти"
          @click="openLogoutConfirm"
        >
          <span class="hidden sm:inline text-sm font-medium">Выйти</span>
          <LogOut :size="20" class="shrink-0" aria-hidden="true" />
        </button>
        <Button
          variant="primary"
          size="sm"
          class="hidden sm:inline-flex gap-2 cursor-pointer"
          @click="goToOrders"
        >
          <ShoppingBag :size="18" />
          <span>{{ canManageOrders() ? 'Управление заказами' : 'Мои заказы' }}</span>
        </Button>
        <button
          type="button"
          class="md:hidden p-2 text-slate-600 cursor-pointer"
          @click="mobileMenuOpen = !mobileMenuOpen"
        >
          <Menu v-if="!mobileMenuOpen" :size="24" />
          <X v-else :size="24" />
        </button>
      </div>
    </nav>

    <!-- Mobile Menu -->
    <div
      v-if="mobileMenuOpen"
      class="md:hidden border-t border-neutral-200 bg-white"
    >
      <div class="px-4 py-4 space-y-4">
        <RouterLink
          v-for="item in navItems"
          :key="item.label"
          :to="item.to"
          class="block w-full text-left text-sm font-medium text-slate-600 hover:text-slate-900 py-2"
          @click="onNavLinkClick"
        >
          {{ item.label }}
        </RouterLink>
        <Button variant="primary" class="w-full" @click="goToOrders">
          {{ canManageOrders() ? 'Управление заказами' : 'Мои заказы' }}
        </Button>
        <button
          v-if="!isLoggedIn"
          type="button"
          class="block w-full text-left text-sm font-medium text-slate-600 hover:text-slate-900 py-2 cursor-pointer"
          @click="goToLogin"
        >
          Войти
        </button>
        <button
          v-else
          type="button"
          class="block w-full text-left text-sm font-medium text-slate-600 hover:text-slate-900 py-2 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
          :disabled="logoutLoading"
          @click="openLogoutConfirm"
        >
          Выйти
        </button>
      </div>
    </div>

    <Teleport to="body">
      <div
        v-if="showLogoutConfirm"
        class="fixed inset-0 z-[100] flex items-center justify-center p-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="logout-confirm-title"
      >
        <div
          class="absolute inset-0 bg-slate-900/40"
          @click="cancelLogout"
        />
        <div
          class="relative w-full max-w-sm rounded-lg border border-neutral-200 bg-white p-6 shadow-lg"
        >
          <p id="logout-confirm-title" class="text-center text-base font-medium text-slate-900">
            Выйти из кабинета
          </p>
          <div class="mt-6 flex gap-3 justify-center">
            <Button
              variant="primary"
              size="sm"
              class="min-w-[100px]"
              :disabled="logoutLoading"
              @click="confirmLogout"
            >
              Да
            </Button>
            <Button
              variant="secondary"
              size="sm"
              class="min-w-[100px]"
              :disabled="logoutLoading"
              @click="cancelLogout"
            >
              Нет
            </Button>
          </div>
        </div>
      </div>
    </Teleport>
  </header>
</template>
