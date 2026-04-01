<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import axios from 'axios'
import Typography from '../components/ui/Typography.vue'
import Button from '../components/ui/Button.vue'
import Header from '../components/sections/Header.vue'
import Footer from '../components/sections/Footer.vue'

const AUTH_TOKEN_KEY = 'auth_token'

const inputClass =
  'w-full border border-neutral-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-slate-500'

const step = ref(1)
const totalSteps = 6
const submitLoading = ref(false)
const submitted = ref(false)

const stepLabels = [
  'Данные ребёнка',
  'Размер',
  'Комплект',
  'Данные родителя',
  'Получение',
  'Подтверждение',
]

// Шаг 1
const childFullName = ref('')
const childGender = ref<'boy' | 'girl' | ''>('')
const settlement = ref('')
const school = ref('')
const classNum = ref('')
const classLetter = ref('')
const schoolYear = ref('')

// Шаг 2
const sizeFromTable = ref('')
const heightCm = ref('')
const chestCm = ref('')
const waistCm = ref('')
const hipsCm = ref('')
const figureComment = ref('')

// Шаг 3
type KitLine = {
  productName: string
  quantity: string
  sizeOverride: string
  lineComment: string
}
type KitSuggestion = {
  id: number | null
  name: string
  image: string | null
}
const kitLines = ref<KitLine[]>([
  { productName: '', quantity: '1', sizeOverride: '', lineComment: '' },
])
const kitComment = ref('')
const selectedKitLineIndex = ref(0)
const availableKitSuggestions = ref<KitSuggestion[]>([])
const kitSuggestionsLoading = ref(false)
const kitSuggestionsError = ref('')

async function loadKitSuggestions() {
  if (childGender.value !== 'boy' && childGender.value !== 'girl') {
    availableKitSuggestions.value = []
    kitSuggestionsError.value = ''
    return
  }

  kitSuggestionsLoading.value = true
  kitSuggestionsError.value = ''

  try {
    const response = await axios.get('/api/index', {
      params: {
        gender: childGender.value === 'boy' ? 'boys' : 'girls',
        category: 'Комплекты',
      },
    })

    const products = Array.isArray(response.data) ? response.data : []
    const suggestions = products
      .map((item: Record<string, unknown>) => {
        const rawId = item.id
        let id: number | null = null
        if (typeof rawId === 'number' && Number.isFinite(rawId)) {
          id = rawId
        } else if (typeof rawId === 'string' && rawId.trim() !== '') {
          const n = Number(rawId)
          id = Number.isFinite(n) ? n : null
        }
        const name = item.name
        const image = item.image
        return {
          id,
          name: typeof name === 'string' ? name.trim() : '',
          image: typeof image === 'string' && image.trim() ? image : null,
        } satisfies KitSuggestion
      })
      .filter((item) => Boolean(item.name))

    const uniqueByKey = new Map<string, KitSuggestion>()
    for (const item of suggestions) {
      const key = item.id != null ? `id:${item.id}` : `name:${item.name}`
      if (!uniqueByKey.has(key)) {
        uniqueByKey.set(key, item)
      }
    }

    availableKitSuggestions.value = Array.from(uniqueByKey.values())
  } catch {
    availableKitSuggestions.value = []
    kitSuggestionsError.value = 'Не удалось загрузить комплекты. Можно ввести вручную.'
  } finally {
    kitSuggestionsLoading.value = false
  }
}

function ensureValidSelectedLineIndex() {
  if (!kitLines.value.length) {
    addKitLine()
  }
  if (selectedKitLineIndex.value < 0 || selectedKitLineIndex.value >= kitLines.value.length) {
    selectedKitLineIndex.value = 0
  }
}

function applySuggestedKitToSelectedLine(kitName: string) {
  ensureValidSelectedLineIndex()
  kitLines.value[selectedKitLineIndex.value].productName = kitName
}

function addSuggestedKitAsNewLine(kitName: string) {
  kitLines.value.push({
    productName: kitName,
    quantity: '1',
    sizeOverride: '',
    lineComment: '',
  })
  selectedKitLineIndex.value = kitLines.value.length - 1
}

function addKitLine() {
  kitLines.value.push({
    productName: '',
    quantity: '1',
    sizeOverride: '',
    lineComment: '',
  })
  selectedKitLineIndex.value = kitLines.value.length - 1
}

function removeKitLine(index: number) {
  if (kitLines.value.length > 1) {
    kitLines.value.splice(index, 1)
    if (selectedKitLineIndex.value >= kitLines.value.length) {
      selectedKitLineIndex.value = kitLines.value.length - 1
    }
  }
}

// Шаг 4
const parentFullName = ref('')
const parentPhone = ref('')
const parentEmail = ref('')
const messengerMax = ref('')
const messengerTelegram = ref('')

// Шаг 5
const recipientIsCustomer = ref(true)
const recipientName = ref('')
const recipientPhone = ref('')

watch(
  [recipientIsCustomer, parentPhone],
  () => {
    if (recipientIsCustomer.value) {
      recipientPhone.value = parentPhone.value
    }
  },
  { immediate: true },
)

watch(parentPhone, () => {
  if (recipientIsCustomer.value) {
    recipientPhone.value = parentPhone.value
  }
})

watch(childGender, () => {
  void loadKitSuggestions()
}, { immediate: true })

// Шаг 6
const termsAccepted = ref(false)

const stepError = ref('')
const submitError = ref('')

function getAuthHeaders(): Record<string, string> {
  const headers: Record<string, string> = { Accept: 'application/json' }
  const token = localStorage.getItem(AUTH_TOKEN_KEY) || localStorage.getItem('token')
  if (token) {
    headers.Authorization = `Bearer ${token}`
  }
  return headers
}

function buildOrderPayload() {
  return {
    childFullName: childFullName.value.trim(),
    childGender: childGender.value,
    settlement: settlement.value.trim(),
    school: school.value.trim(),
    classNum: classNum.value.trim(),
    classLetter: classLetter.value.trim() || null,
    schoolYear: schoolYear.value.trim(),
    sizeFromTable: sizeFromTable.value.trim(),
    heightCm: heightCm.value.trim() || null,
    chestCm: chestCm.value.trim() || null,
    waistCm: waistCm.value.trim() || null,
    hipsCm: hipsCm.value.trim() || null,
    figureComment: figureComment.value.trim() || null,
    kitLines: kitLines.value
      .filter((l) => l.productName.trim())
      .map((l) => ({
        productName: l.productName.trim(),
        quantity: Math.max(1, Math.floor(Number(l.quantity)) || 1),
        sizeOverride: l.sizeOverride.trim() || null,
        lineComment: l.lineComment.trim() || null,
      })),
    kitComment: kitComment.value.trim() || null,
    parentFullName: parentFullName.value.trim(),
    parentPhone: parentPhone.value.trim(),
    parentEmail: parentEmail.value.trim(),
    messengerMax: messengerMax.value.trim() || null,
    messengerTelegram: messengerTelegram.value.trim() || null,
    recipientIsCustomer: recipientIsCustomer.value,
    recipientName: recipientIsCustomer.value ? null : recipientName.value.trim(),
    recipientPhone: recipientPhone.value.trim(),
    termsAccepted: termsAccepted.value,
  }
}

function formatApiError(err: unknown): string {
  const e = err as {
    response?: { data?: { message?: string; errors?: Record<string, string[]> } }
  }
  const data = e.response?.data
  if (!data) return 'Не удалось отправить заказ. Проверьте соединение.'
  if (typeof data.message === 'string') return data.message
  if (data.errors) {
    return Object.values(data.errors).flat().join(' ')
  }
  return 'Ошибка при отправке заказа'
}

function validateStep(n: number): boolean {
  stepError.value = ''
  if (n === 1) {
    if (
      !childFullName.value.trim() ||
      !childGender.value ||
      !settlement.value.trim() ||
      !school.value.trim() ||
      !classNum.value.trim() ||
      !schoolYear.value.trim()
    ) {
      stepError.value = 'Заполните обязательные поля шага 1'
      return false
    }
  }
  if (n === 2) {
    if (!sizeFromTable.value.trim()) {
      stepError.value = 'Укажите размер по таблице'
      return false
    }
  }
  if (n === 3) {
    const ok = kitLines.value.some(
      (l) => l.productName.trim() && Number(l.quantity) > 0,
    )
    if (!ok) {
      stepError.value = 'Добавьте хотя бы одно изделие с названием и количеством'
      return false
    }
  }
  if (n === 4) {
    if (!parentFullName.value.trim() || !parentPhone.value.trim() || !parentEmail.value.trim()) {
      stepError.value = 'Заполните ФИО, телефон и email'
      return false
    }
  }
  if (n === 5) {
    if (!recipientIsCustomer.value && !recipientName.value.trim()) {
      stepError.value = 'Укажите ФИО получателя'
      return false
    }
    if (!recipientPhone.value.trim()) {
      stepError.value = 'Укажите телефон получателя'
      return false
    }
  }
  if (n === 6) {
    if (!termsAccepted.value) {
      stepError.value = 'Нужно согласие со сроками изготовления'
      return false
    }
  }
  return true
}

function nextStep() {
  if (!validateStep(step.value)) return
  if (step.value < totalSteps) {
    step.value += 1
  }
}

function goToStep(targetStep: number) {
  if (targetStep < 1 || targetStep > totalSteps || targetStep === step.value) {
    return
  }

  if (targetStep < step.value) {
    stepError.value = ''
    step.value = targetStep
    return
  }

  for (let s = step.value; s < targetStep; s += 1) {
    if (!validateStep(s)) {
      return
    }
  }

  stepError.value = ''
  step.value = targetStep
}

function prevStep() {
  stepError.value = ''
  if (step.value > 1) {
    step.value -= 1
  }
}

async function submitOrder() {
  submitError.value = ''
  if (!validateStep(6)) return
  submitLoading.value = true
  try {
    await axios.post('/api/createOrder', buildOrderPayload(), {
      headers: getAuthHeaders(),
    })
    submitted.value = true
  } catch (err: unknown) {
    console.error(err)
    submitError.value = formatApiError(err)
  } finally {
    submitLoading.value = false
  }
}

const summaryLines = computed(() => {
  const lines: { label: string; value: string }[] = [
    { label: 'ФИО ребёнка', value: childFullName.value },
    { label: 'Пол', value: childGender.value === 'boy' ? 'Мальчик' : childGender.value === 'girl' ? 'Девочка' : '—' },
    { label: 'Населённый пункт', value: settlement.value },
    { label: 'Школа', value: school.value },
    { label: 'Класс', value: classNum.value },
    { label: 'Литера', value: classLetter.value || '—' },
    { label: 'Учебный год', value: schoolYear.value },
    { label: 'Размер по таблице', value: sizeFromTable.value },
    {
      label: 'Рост / грудь / талия / бёдра',
      value:
        [heightCm.value, chestCm.value, waistCm.value, hipsCm.value]
          .filter((v) => v !== '' && v != null)
          .join(' / ') || '—',
    },
    { label: 'Комментарий по фигуре', value: figureComment.value || '—' },
    { label: 'Комплект', value: kitLines.value.map((l) => `${l.productName} ×${l.quantity}${l.sizeOverride ? ` (${l.sizeOverride})` : ''}`).join('; ') },
    { label: 'Комментарий к комплекту', value: kitComment.value || '—' },
    { label: 'ФИО родителя', value: parentFullName.value },
    { label: 'Телефон', value: parentPhone.value },
    { label: 'Email', value: parentEmail.value },
    { label: 'Мессенджер MAX', value: messengerMax.value || '—' },
    { label: 'Telegram', value: messengerTelegram.value || '—' },
    { label: 'Получатель', value: recipientIsCustomer.value ? 'Заказчик' : recipientName.value },
    { label: 'Телефон получателя', value: recipientPhone.value },
  ]
  return lines
})
</script>

<template>
  <div class="w-full min-h-screen flex flex-col">
    <Header />

    <main class="flex-1 bg-neutral-50 py-2 md:py-6 px-4">
      <div class="max-w-3xl mx-auto w-full">
        <div v-if="submitted" class="bg-white p-8 rounded-lg shadow-md text-center">
          <Typography as="h1" variant="h2" class="mb-4 text-slate-900">
            Заказ отправлен
          </Typography>
          <Typography as="p" variant="body" class="text-slate-600 mb-8">
            Мы свяжемся с вами для уточнения деталей. Спасибо за заказ!
          </Typography>
          <Button variant="primary" class="inline-flex" @click="$router.push('/')">
            На главную
          </Button>
        </div>

        <div v-else class="bg-white p-6 md:p-8 rounded-lg shadow-md w-full">
          <Typography as="h1" variant="h2" class="mb-2 text-slate-900">
            Оформление заказа
          </Typography>
          <Typography as="p" variant="body" class="mb-8 text-slate-600">
            Заполните данные по шагам — это займёт несколько минут.
          </Typography>

          <!-- Step indicator -->
          <div class="mb-8 overflow-x-auto pb-2">
            <div class="flex min-w-max gap-2 md:gap-3">
              <div
                v-for="(label, i) in stepLabels"
                :key="label"
                class="flex items-center gap-2"
              >
                <button
                  type="button"
                  class="flex items-center gap-2 cursor-pointer"
                  @click="goToStep(i + 1)"
                >
                  <div
                  :class="[
                    'flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-medium',
                    step > i + 1
                      ? 'bg-emerald-600 text-white'
                      : step === i + 1
                        ? 'bg-slate-900 text-white'
                        : 'bg-neutral-200 text-slate-600',
                  ]"
                  >
                    {{ i + 1 }}
                  </div>
                  <span
                    class="hidden sm:inline text-xs font-medium max-w-[100px] leading-tight"
                    :class="step === i + 1 ? 'text-slate-900' : 'text-slate-500'"
                  >
                    {{ label }}
                  </span>
                </button>
              </div>
            </div>
          </div>

          <div v-if="stepError" class="mb-4 text-sm text-red-600">
            {{ stepError }}
          </div>

          <!-- Шаг 1 -->
          <div v-show="step === 1" class="space-y-4">
            <Typography as="h2" variant="h4" class="text-slate-900 mb-4">
              Шаг 1. Данные ребёнка
            </Typography>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">ФИО ребёнка</label>
              <input v-model="childFullName" type="text" :class="inputClass" placeholder="Фамилия Имя Отчество" />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Пол</label>
              <select v-model="childGender" :class="inputClass">
                <option value="" disabled>Выберите</option>
                <option value="boy">Мальчик</option>
                <option value="girl">Девочка</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Населённый пункт</label>
              <input v-model="settlement" type="text" :class="inputClass" />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Школа</label>
              <input v-model="school" type="text" :class="inputClass" />
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Класс</label>
                <input v-model="classNum" type="text" :class="inputClass" placeholder="например, 5" />
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Литера</label>
                <input v-model="classLetter" type="text" :class="inputClass" placeholder="А, Б…" />
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Учебный год</label>
                <input v-model="schoolYear" type="text" :class="inputClass" placeholder="2025/2026" />
              </div>
            </div>
          </div>

          <!-- Шаг 2 -->
          <div v-show="step === 2" class="space-y-4">
            <Typography as="h2" variant="h4" class="text-slate-900 mb-4">
              Шаг 2. Размер
            </Typography>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Размер по таблице</label>
              <input v-model="sizeFromTable" type="text" :class="inputClass" />
            </div>
            <Typography as="p" variant="small" class="text-slate-500">
              При необходимости укажите мерки (см):
            </Typography>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Рост</label>
                <input v-model="heightCm" type="text" :class="inputClass" />
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Грудь</label>
                <input v-model="chestCm" type="text" :class="inputClass" />
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Талия</label>
                <input v-model="waistCm" type="text" :class="inputClass" />
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Бёдра</label>
                <input v-model="hipsCm" type="text" :class="inputClass" />
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Комментарий по фигуре</label>
              <textarea
                v-model="figureComment"
                rows="3"
                :class="inputClass"
                placeholder="Особенности посадки, пожелания"
              />
            </div>
          </div>

          <!-- Шаг 3 -->
          <div v-show="step === 3" class="space-y-4">
            <Typography as="h2" variant="h4" class="text-slate-900 mb-4">
              Шаг 3. Комплект
            </Typography>
            <div class="rounded-lg border border-neutral-200 bg-neutral-50 p-4">
              <p class="text-sm font-medium text-slate-800">
                Доступные комплекты
                {{ childGender === 'boy' ? 'для мальчиков' : childGender === 'girl' ? 'для девочек' : '' }}
              </p>
              <p v-if="!childGender" class="mt-1 text-xs text-slate-500">
                Выберите пол ребёнка на шаге 1, чтобы увидеть рекомендации.
              </p>
              <template v-else>
                <p class="mt-1 text-xs text-slate-500">
                  Сейчас выбрано для заполнения: Позиция {{ selectedKitLineIndex + 1 }}.
                  Клик по фото открывает карточку товара в новой вкладке.
                </p>
                <p v-if="kitSuggestionsLoading" class="mt-1 text-xs text-slate-500">
                  Загружаем доступные комплекты...
                </p>
                <p v-else-if="kitSuggestionsError" class="mt-1 text-xs text-red-600">
                  {{ kitSuggestionsError }}
                </p>
                <p v-else-if="availableKitSuggestions.length === 0" class="mt-1 text-xs text-slate-500">
                  Для выбранного пола пока нет готовых комплектов.
                </p>
                <div v-else class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <div
                    v-for="kit in availableKitSuggestions"
                    :key="kit.id != null ? kit.id : kit.name"
                    class="rounded-md border border-slate-300 bg-white p-2"
                  >
                    <div class="flex items-center gap-3">
                      <a
                        v-if="kit.id != null"
                        :href="`/product/${kit.id}`"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="group shrink-0 rounded focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-500"
                        :title="'Открыть страницу товара в новой вкладке'"
                        @click.stop
                      >
                        <img
                          v-if="kit.image"
                          :src="kit.image"
                          :alt="kit.name"
                          class="h-14 w-14 rounded object-cover border border-neutral-200 transition group-hover:ring-2 group-hover:ring-slate-400"
                        />
                        <div
                          v-else
                          class="flex h-14 w-14 items-center justify-center rounded border border-dashed border-neutral-300 bg-neutral-100 text-[10px] text-slate-500 transition group-hover:ring-2 group-hover:ring-slate-400"
                        >
                          фото
                        </div>
                      </a>
                      <template v-else>
                        <img
                          v-if="kit.image"
                          :src="kit.image"
                          :alt="kit.name"
                          class="h-14 w-14 rounded object-cover border border-neutral-200 shrink-0"
                        />
                        <div
                          v-else
                          class="h-14 w-14 rounded border border-dashed border-neutral-300 bg-neutral-100 shrink-0"
                          aria-hidden="true"
                        />
                      </template>
                      <span class="text-sm text-slate-700">{{ kit.name }}</span>
                    </div>
                    <div class="mt-2 flex gap-2">
                      <button
                        type="button"
                        class="rounded border border-slate-300 px-2 py-1 text-xs text-slate-700 hover:bg-slate-100"
                        @click="applySuggestedKitToSelectedLine(kit.name)"
                      >
                        В текущую позицию
                      </button>
                      <button
                        type="button"
                        class="rounded border border-slate-300 px-2 py-1 text-xs text-slate-700 hover:bg-slate-100"
                        @click="addSuggestedKitAsNewLine(kit.name)"
                      >
                        В новую позицию
                      </button>
                    </div>
                  </div>
                </div>
              </template>
            </div>
            <div
              v-for="(line, idx) in kitLines"
              :key="idx"
              class="rounded-lg border border-neutral-200 p-4 space-y-3"
            >
              <div class="flex justify-between items-center">
                <span class="text-sm font-medium text-slate-700">Позиция {{ idx + 1 }}</span>
                <button
                  v-if="kitLines.length > 1"
                  type="button"
                  class="text-sm text-red-600 hover:underline"
                  @click="removeKitLine(idx)"
                >
                  Удалить
                </button>
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Наименование изделия</label>
                <input
                  v-model="line.productName"
                  type="text"
                  :class="inputClass"
                  :list="availableKitSuggestions.length ? 'kit-name-suggestions' : undefined"
                  @focus="selectedKitLineIndex = idx"
                />
              </div>
              <datalist v-if="availableKitSuggestions.length" id="kit-name-suggestions">
                <option v-for="kit in availableKitSuggestions" :key="kit.name" :value="kit.name" />
              </datalist>
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                  <label class="block text-sm font-medium text-slate-700 mb-1">Количество</label>
                  <input v-model="line.quantity" type="number" min="1" :class="inputClass" />
                </div>
                <div class="sm:col-span-2">
                  <label class="block text-sm font-medium text-slate-700 mb-1">
                    Размер по позиции, если отличается от общего
                  </label>
                  <input v-model="line.sizeOverride" type="text" :class="inputClass" placeholder="Необязательно" />
                </div>
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Комментарий</label>
                <input v-model="line.lineComment" type="text" :class="inputClass" placeholder="Необязательно" />
              </div>
            </div>
            <Button type="button" variant="outline" size="sm" @click="addKitLine">
              + Добавить позицию
            </Button>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Комментарий к комплекту</label>
              <textarea v-model="kitComment" rows="3" :class="inputClass" />
            </div>
          </div>

          <!-- Шаг 4 -->
          <div v-show="step === 4" class="space-y-4">
            <Typography as="h2" variant="h4" class="text-slate-900 mb-4">
              Шаг 4. Данные родителя
            </Typography>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">ФИО родителя</label>
              <input v-model="parentFullName" type="text" :class="inputClass" />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Телефон</label>
              <input v-model="parentPhone" type="tel" :class="inputClass" placeholder="+7 …" />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Email (для отправки чека)</label>
              <input v-model="parentEmail" type="email" :class="inputClass" />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Мессенджер MAX</label>
              <input v-model="messengerMax" type="text" :class="inputClass" placeholder="Ник или номер" />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Telegram</label>
              <input v-model="messengerTelegram" type="text" :class="inputClass" placeholder="@username" />
            </div>
          </div>

          <!-- Шаг 5 -->
          <div v-show="step === 5" class="space-y-4">
            <Typography as="h2" variant="h4" class="text-slate-900 mb-4">
              Шаг 5. Получение
            </Typography>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-2">Кто будет получать заказ</label>
              <div class="space-y-2">
                <label class="flex items-center gap-2 cursor-pointer">
                  <input v-model="recipientIsCustomer" type="radio" :value="true" class="w-4 h-4" />
                  <span class="text-sm text-slate-700">Заказчик (я)</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                  <input v-model="recipientIsCustomer" type="radio" :value="false" class="w-4 h-4" />
                  <span class="text-sm text-slate-700">Другой человек</span>
                </label>
              </div>
            </div>
            <div v-if="!recipientIsCustomer">
              <label class="block text-sm font-medium text-slate-700 mb-1">ФИО получателя</label>
              <input v-model="recipientName" type="text" :class="inputClass" />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Телефон получателя</label>
              <input
                v-model="recipientPhone"
                type="tel"
                :class="inputClass"
                :readonly="recipientIsCustomer"
                :placeholder="recipientIsCustomer ? 'Совпадает с телефоном заказчика' : '+7 …'"
              />
            </div>
          </div>

          <!-- Шаг 6 -->
          <div v-show="step === 6" class="space-y-4">
            <Typography as="h2" variant="h4" class="text-slate-900 mb-4">
              Шаг 6. Подтверждение
            </Typography>
            <Typography as="p" variant="body" class="text-slate-600 mb-4">
              Проверьте данные перед отправкой.
            </Typography>
            <dl class="rounded-lg border border-neutral-200 divide-y divide-neutral-200">
              <div
                v-for="row in summaryLines"
                :key="row.label"
                class="grid grid-cols-1 sm:grid-cols-3 gap-1 px-4 py-3 sm:gap-4"
              >
                <dt class="text-sm font-medium text-slate-500 sm:col-span-1">{{ row.label }}</dt>
                <dd class="text-sm text-slate-900 sm:col-span-2 whitespace-pre-wrap">{{ row.value }}</dd>
              </div>
            </dl>
            <label class="flex items-start gap-3 cursor-pointer pt-2">
              <input v-model="termsAccepted" type="checkbox" class="mt-1 w-4 h-4 rounded border-neutral-300" />
              <span class="text-sm text-slate-700">
                Согласен(на) со сроками изготовления и условиями предзаказа
              </span>
            </label>
            <div v-if="submitError" class="text-sm text-red-600">
              {{ submitError }}
            </div>
          </div>

          <!-- Nav -->
          <div class="mt-10 flex flex-col-reverse sm:flex-row gap-3 sm:justify-between">
            <Button
              v-if="step > 1"
              type="button"
              variant="outline"
              @click="prevStep"
            >
              Назад
            </Button>
            <div v-else class="hidden sm:block" />

            <div class="flex gap-3 sm:ml-auto">
              <Button
                v-if="step < totalSteps"
                type="button"
                variant="primary"
                class="w-full sm:w-auto"
                @click="nextStep"
              >
                Далее
              </Button>
              <Button
                v-else
                type="button"
                variant="primary"
                class="w-full sm:w-auto"
                :disabled="submitLoading"
                @click="submitOrder"
              >
                {{ submitLoading ? 'Отправка…' : 'Отправить заказ' }}
              </Button>
            </div>
          </div>
        </div>
      </div>
    </main>

    <Footer />
  </div>
</template>
