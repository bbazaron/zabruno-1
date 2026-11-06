<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import Header from '../components/sections/Header.vue'
import Footer from '../components/sections/Footer.vue'
import Typography from '../components/ui/Typography.vue'

const route = useRoute()
const copied = ref(false)
const copyFailed = ref(false)

const code = computed(() => {
  const raw = route.query.code
  const value = Array.isArray(raw) ? raw[0] : raw
  return String(value ?? '').replace(/\D/g, '').slice(0, 6)
})

function copyWithSelection(text: string): boolean {
  const area = document.createElement('textarea')
  area.value = text
  area.setAttribute('readonly', '')
  area.style.position = 'fixed'
  area.style.top = '0'
  area.style.left = '0'
  area.style.opacity = '0'
  document.body.appendChild(area)
  area.focus()
  area.select()
  let ok = false
  try {
    ok = document.execCommand('copy')
  } catch {
    ok = false
  }
  document.body.removeChild(area)
  return ok
}

async function copyCode() {
  if (code.value.length !== 6) return
  try {
    await navigator.clipboard.writeText(code.value)
    copied.value = true
    copyFailed.value = false
  } catch {
    const ok = copyWithSelection(code.value)
    copied.value = ok
    copyFailed.value = !ok
  }
}

onMounted(() => {
  void copyCode()
})
</script>

<template>
  <div class="w-full min-h-screen flex flex-col">
    <Header />
    <main class="flex-1 flex items-center justify-center bg-neutral-50 px-4 py-16">
      <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md text-center">
        <Typography as="h1" variant="h2" class="mb-3 text-slate-900">
          Код из письма
        </Typography>
        <p v-if="code.length === 6" class="mb-6 text-sm text-slate-600">
          Нажмите на код, чтобы скопировать его целиком
        </p>
        <p v-else class="text-sm text-slate-600">
          В ссылке нет кода. Откройте письмо ещё раз и нажмите на код.
        </p>
        <button
          v-if="code.length === 6"
          type="button"
          class="w-full rounded-lg border border-neutral-200 bg-neutral-50 px-4 py-5 text-3xl font-semibold tracking-[0.35em] text-slate-900 hover:bg-neutral-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-900"
          @click="copyCode"
        >
          {{ code }}
        </button>
        <p v-if="copied" class="mt-4 text-sm text-emerald-700">Код скопирован</p>
        <p v-else-if="copyFailed" class="mt-4 text-sm text-slate-600">
          Браузер не разрешил копирование. Нажмите на код ещё раз.
        </p>
      </div>
    </main>
    <Footer />
  </div>
</template>
