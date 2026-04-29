<script setup lang="ts">
import { ref, computed, watch, onMounted, nextTick } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'
import Typography from '../components/ui/Typography.vue'
import Button from '../components/ui/Button.vue'
import Header from '../components/sections/Header.vue'
import Footer from '../components/sections/Footer.vue'
import { resolveBackendMediaUrl } from '../utils/resolveBackendMediaUrl'

const AUTH_TOKEN_KEY = 'auth_token'

const inputClass =
  'w-full border border-neutral-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-slate-500'

/** Поля мерок: суффикс «см», цифровая клавиатура на телефоне */
const measureInputClass =
  `${inputClass} pr-10 tabular-nums [appearance:textfield]`

const route = useRoute()

const step = ref(1)
const totalSteps = 6
const submitLoading = ref(false)
const submitted = ref(false)
/** Возврат с страницы оплаты ЮKassa */
const returnedFromPayment = ref(false)

/** Есть токен — бэкенд подставит email учётной записи, если поле пустое */
const isAuthenticated = ref(false)
const accountEmail = ref('')

onMounted(() => {
  const q = route.query.fromPayment
  if (Array.isArray(q) ? q[0] === '1' : q === '1') {
    submitted.value = true
    returnedFromPayment.value = true
  }
  const t = localStorage.getItem(AUTH_TOKEN_KEY) || localStorage.getItem('token')
  isAuthenticated.value = Boolean(t && String(t).trim())
  if (isAuthenticated.value) {
    void loadAccountEmail()
  }
})

const stepLabels = [
  'Данные ребёнка',
  'Размер',
  'Комплект',
  'Данные родителя',
  'Получение',
  'Подтверждение',
]

/** Краткие пояснения под заголовком шага */
const stepSubtitles = [
  'Кто учится и в каком классе — для изготовления формы.',
  'Размер по таблице и мерки для посадки изделия.',
  'Выберите позиции комплекта и количество.',
  'Как с вами связаться по заказу.',
  'Кто заберёт готовый заказ.',
  'Проверьте данные и отправьте предзаказ.',
]

const stepCardClass =
  'rounded-xl border border-neutral-200/70 bg-neutral-50/50 p-4 sm:p-6 space-y-5'

// Шаг 1
const childFullName = ref('')
const childGender = ref<'boy' | 'girl' | ''>('')
const settlement = ref('')
const school = ref('')
const classNum = ref('')
const classLetter = ref('')
const schoolYear = ref('')

const classNumSelectOptions = Array.from({ length: 11 }, (_, i) => String(i + 1))
const classLetterSelectOptions = [
  'А',
  'Б',
  'В',
  'Г',
  'Д',
  'Е',
  'Ё',
  'Ж',
  'З',
  'И',
  'Й',
  'К',
  'Л',
  'М',
  'Н',
  'О',
  'П',
  'Р',
  'С',
  'Т',
  'У',
  'Ф',
  'Х',
  'Ц',
  'Ч',
  'Ш',
  'Щ',
  'Ъ',
  'Ы',
  'Ь',
  'Э',
  'Ю',
  'Я',
]
const schoolYearSelectOptions = ['26/27', '27/28', '28/29', '29/30']

// Шаг 2
const sizeFromTable = ref('')
const sizeFromTableSelectOptions = Array.from({ length: (50 - 28) / 2 + 1 }, (_, i) =>
  String(28 + i * 2),
)
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

function adjustKitLineQuantity(index: number, delta: number) {
  const line = kitLines.value[index]
  const current = Math.max(1, Math.floor(Number(line.quantity)) || 1)
  line.quantity = String(Math.max(1, current + delta))
}

function normalizeKitLineQuantity(index: number) {
  const line = kitLines.value[index]
  const n = Math.floor(Number(line.quantity))
  line.quantity = String(Number.isFinite(n) && n >= 1 ? n : 1)
}

/** +7 и до 10 цифр; пустой ввод даёт «+7» */
function normalizeRuPhoneInput(raw: string): string {
  const digits = raw.replace(/\D/g, '')
  if (digits.length === 0) return '+7'
  let core = digits
  if (core.startsWith('8')) core = `7${core.slice(1)}`
  if (!core.startsWith('7')) core = `7${core}`
  core = core.slice(0, 11)
  return `+${core}`
}

function isRuPhoneComplete(phone: string): boolean {
  const d = phone.replace(/\D/g, '')
  return d.length === 11 && d.startsWith('7')
}

/** 10 цифр после 7; незаполненные позиции — «_» (шаблон +7-___-___-__-__) */
function digitsAfterSeven(phone: string): string {
  const d = phone.replace(/\D/g, '')
  if (d.length <= 1) return ''
  return d.slice(1, 11)
}

function formatParentPhoneMask(phone: string): string {
  const d7 = digitsAfterSeven(phone)
  const chars: string[] = []
  for (let i = 0; i < 10; i++) {
    chars[i] = i < d7.length ? d7[i]! : '_'
  }
  const g1 = chars.slice(0, 3).join('')
  const g2 = chars.slice(3, 6).join('')
  const g3 = chars.slice(6, 8).join('')
  const g4 = chars.slice(8, 10).join('')
  return `+7-${g1}-${g2}-${g3}-${g4}`
}

function setParentPhoneFromDigitsAfter7(after7: string) {
  const only = after7.replace(/\D/g, '').slice(0, 10)
  parentPhone.value = only.length === 0 ? '+7' : `+7${only}`
}

const PHONE_EDITABLE_POSITIONS = [3, 4, 5, 7, 8, 9, 11, 12, 14, 15] as const

function displayPosToDigitIndex(pos: number): number {
  return PHONE_EDITABLE_POSITIONS.filter((p) => p < pos).length
}

function digitIndexToDisplayPos(idx: number): number {
  if (idx <= 0) return PHONE_EDITABLE_POSITIONS[0]
  if (idx >= 10) return formatParentPhoneMask(parentPhone.value).length
  return PHONE_EDITABLE_POSITIONS[idx] ?? formatParentPhoneMask(parentPhone.value).length
}

function setPhoneCaretByDigitIndex(el: HTMLInputElement, idx: number) {
  nextTick(() => {
    const pos = digitIndexToDisplayPos(idx)
    el.setSelectionRange(pos, pos)
  })
}

/** Ввод только с начала номера; символы маски не попадают в буфер (не парсим value целиком) */
function onParentPhoneBeforeInput(e: Event) {
  const be = e as InputEvent
  if (be.isComposing) return
  const el = e.target as HTMLInputElement

  if (be.inputType === 'insertText' && be.data && /^\d+$/.test(be.data)) {
    be.preventDefault()
    const start = el.selectionStart ?? 0
    const end = el.selectionEnd ?? 0
    const d = digitsAfterSeven(parentPhone.value)
    const typed = be.data.replace(/\D/g, '')
    const from = displayPosToDigitIndex(start)
    const to = displayPosToDigitIndex(end)
    const nextDigits = (d.slice(0, from) + typed + d.slice(to)).slice(0, 10)
    setParentPhoneFromDigitsAfter7(nextDigits)
    setPhoneCaretByDigitIndex(el, Math.min(from + typed.length, 10))
    return
  }

  if (be.inputType === 'deleteContentBackward' || be.inputType === 'deleteContentForward') {
    be.preventDefault()
    const start = el.selectionStart ?? 0
    const end = el.selectionEnd ?? 0
    const d = digitsAfterSeven(parentPhone.value)
    let from = displayPosToDigitIndex(start)
    let to = displayPosToDigitIndex(end)

    if (start === end) {
      if (be.inputType === 'deleteContentBackward') {
        from = Math.max(0, from - 1)
      } else {
        to = Math.min(10, to + 1)
      }
    }

    if (from === to) {
      setPhoneCaretByDigitIndex(el, from)
    } else {
      const nextDigits = d.slice(0, from) + d.slice(to)
      setParentPhoneFromDigitsAfter7(nextDigits)
      setPhoneCaretByDigitIndex(el, from)
    }
  }
}

function onParentPhonePaste(e: ClipboardEvent) {
  e.preventDefault()
  const text = e.clipboardData?.getData('text/plain') ?? ''
  parentPhone.value = normalizeRuPhoneInput(text)
  const el = e.target as HTMLInputElement
  setPhoneCaretByDigitIndex(el, 10)
}

// Шаг 4
const parentFullName = ref('')
const parentPhone = ref('+7')
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
const orderTotalEstimate = ref<number | null>(null)
const orderTotalLoading = ref(false)
const orderTotalError = ref('')

type OrderEstimateLine = {
  product_name: string
  quantity: number
  unit_price: string | null
  line_total: string
  image: string | null
}

const orderEstimateLines = ref<OrderEstimateLine[]>([])

const stepError = ref('')
const submitError = ref('')

function formatOrderTotalRub(n: number): string {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  }).format(n)
}

function suggestionImageForProductName(name: string): string | null {
  const n = name.trim()
  const s = availableKitSuggestions.value.find((x) => x.name === n)
  return s?.image ?? null
}

/** Строки комплекта на шаге 6: позиции из формы + цены и фото из ответа /orderEstimateTotal */
const confirmKitRows = computed(() => {
  const kits = buildKitLinesForEstimate()
  const lines = orderEstimateLines.value
  return kits.map((k, i) => {
    const line = lines[i]
    const lineTotalNum =
      line?.line_total != null ? parseFloat(String(line.line_total)) : Number.NaN
    const unitNum =
      line?.unit_price != null ? parseFloat(String(line.unit_price)) : Number.NaN
    const fromApi = line?.image?.trim() ? line.image : null
    return {
      productName: k.productName,
      quantity: k.quantity,
      sizeOverride: k.sizeOverride,
      lineComment: k.lineComment,
      image: fromApi ?? suggestionImageForProductName(k.productName),
      unitPrice: Number.isFinite(unitNum) ? unitNum : null,
      lineTotal: Number.isFinite(lineTotalNum) ? lineTotalNum : null,
    }
  })
})

function buildKitLinesForEstimate() {
  return kitLines.value
    .filter((l) => l.productName.trim())
    .map((l) => ({
      productName: l.productName.trim(),
      quantity: Math.max(1, Math.floor(Number(l.quantity)) || 1),
      sizeOverride: l.sizeOverride.trim() || null,
      lineComment: l.lineComment.trim() || null,
    }))
}

async function fetchOrderTotalEstimate() {
  const lines = buildKitLinesForEstimate()
  if (lines.length === 0) {
    orderTotalEstimate.value = null
    orderEstimateLines.value = []
    orderTotalError.value = ''
    orderTotalLoading.value = false
    return
  }
  orderTotalLoading.value = true
  orderTotalError.value = ''
  try {
    const res = await axios.post(
      '/api/orderEstimateTotal',
      {
        kitLines: lines,
        childGender: childGender.value === 'boy' || childGender.value === 'girl' ? childGender.value : undefined,
      },
      { headers: getAuthHeaders() },
    )
    const raw = res.data?.total_amount
    const n =
      typeof raw === 'string' ? parseFloat(raw) : typeof raw === 'number' ? raw : Number.NaN
    orderTotalEstimate.value = Number.isFinite(n) ? n : null
    const rawLines = res.data?.lines
    orderEstimateLines.value = Array.isArray(rawLines)
      ? rawLines.filter((row: unknown): row is OrderEstimateLine => {
          if (row === null || typeof row !== 'object') return false
          const r = row as Record<string, unknown>
          return typeof r.product_name === 'string'
        })
      : []
  } catch {
    orderTotalError.value = 'Не удалось рассчитать сумму'
    orderTotalEstimate.value = null
    orderEstimateLines.value = []
  } finally {
    orderTotalLoading.value = false
  }
}

watch(
  [step, kitLines],
  () => {
    if (step.value === 6) {
      void fetchOrderTotalEstimate()
    }
  },
  { deep: true },
)

function getAuthHeaders(): Record<string, string> {
  const headers: Record<string, string> = { Accept: 'application/json' }
  const token = localStorage.getItem(AUTH_TOKEN_KEY) || localStorage.getItem('token')
  if (token) {
    headers.Authorization = `Bearer ${token}`
  }
  return headers
}

async function loadAccountEmail() {
  try {
    const { data } = await axios.get<{
      email?: string | null
      mail?: string | null
      user?: { email?: string | null; mail?: string | null } | null
    }>('/api/getUser', {
      headers: getAuthHeaders(),
    })
    const resolved =
      data?.email ??
      data?.mail ??
      data?.user?.email ??
      data?.user?.mail ??
      ''
    accountEmail.value = typeof resolved === 'string' ? resolved.trim() : ''
  } catch {
    accountEmail.value = ''
  }
}

function buildOrderPayload() {
  return {
    childFullName: childFullName.value.trim(),
    childGender: childGender.value,
    settlement: settlement.value.trim(),
    school: school.value.trim(),
    classNum: classNum.value.trim(),
    classLetter: classLetter.value.trim(),
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
    termsAccepted: true,
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
      !classLetter.value.trim() ||
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
    if (
      !heightCm.value.trim() ||
      !chestCm.value.trim() ||
      !waistCm.value.trim() ||
      !hipsCm.value.trim()
    ) {
      stepError.value = 'Укажите рост и обхваты (грудь, талия, бёдра)'
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
    if (!parentFullName.value.trim()) {
      stepError.value = 'Заполните ФИО и телефон'
      return false
    }
    if (!isRuPhoneComplete(parentPhone.value)) {
      stepError.value = 'Введите полный номер: +7 и 10 цифр'
      return false
    }
    const em = parentEmail.value.trim()
    if (em && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(em)) {
      stepError.value = 'Укажите корректный email или оставьте поле пустым'
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
    const { data } = await axios.post<{ confirmation_url?: string | null }>(
      '/api/createOrder',
      buildOrderPayload(),
      {
        headers: getAuthHeaders(),
      },
    )
    const payUrl = data?.confirmation_url
    if (typeof payUrl === 'string' && payUrl.length > 0) {
      window.location.assign(payUrl)
      return
    }
    returnedFromPayment.value = false
    submitted.value = true
  } catch (err: unknown) {
    console.error(err)
    submitError.value = formatApiError(err)
  } finally {
    submitLoading.value = false
  }
}

function emailForSummary(): string {
  const em = parentEmail.value.trim()
  if (em) return em
  if (isAuthenticated.value && accountEmail.value) return accountEmail.value
  if (isAuthenticated.value) return 'как в учётной записи (подставится при отправке заказа)'
  return '—'
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
    { label: 'Комментарий к комплекту', value: kitComment.value || '—' },
    { label: 'ФИО родителя', value: parentFullName.value },
    { label: 'Телефон', value: parentPhone.value },
    { label: 'Email', value: emailForSummary() },
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

    <main class="flex-1 bg-neutral-50 py-2 md:py-6 px-4 sm:px-6">
      <div class="max-w-4xl mx-auto w-full">
        <div v-if="submitted" class="bg-white p-8 rounded-lg shadow-md text-center">
          <Typography as="h1" variant="h2" class="mb-4 text-slate-900">
            {{ returnedFromPayment ? 'Возврат из оплаты' : 'Заказ оформлен' }}
          </Typography>
          <Typography as="p" variant="body" class="text-slate-600 mb-8">
            <template v-if="returnedFromPayment">
              Если оплата в ЮKassa прошла успешно, заказ поступит в обработку. Статус можно посмотреть в разделе «Мои заказы».
            </template>
            <template v-else>
              Сумма заказа нулевая — оплата не требуется. Мы свяжемся с вами для уточнения деталей. Спасибо за заказ!
            </template>
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

          <!-- Step indicator: одна строка; при узком экране — горизонтальный свайп без лишней высоты -->
          <div class="mb-6">
            <div
              class="overflow-x-auto overflow-y-hidden py-1 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
            >
              <div
                class="flex min-w-min flex-nowrap items-center justify-center gap-x-0 px-1 sm:px-2"
              >
                <template v-for="(label, i) in stepLabels" :key="label">
                  <div class="flex shrink-0 items-center">
                    <button
                      type="button"
                      class="group flex w-[5.25rem] flex-col items-center gap-1 sm:w-[6rem] md:w-[6.75rem] lg:w-[7.25rem] cursor-pointer text-center touch-manipulation"
                      @click="goToStep(i + 1)"
                    >
                      <div
                        :class="[
                          'relative grid shrink-0 place-items-center rounded-full font-semibold tabular-nums transition-all duration-200',
                          step === i + 1
                            ? 'h-9 w-9 -translate-y-0.5 text-[0.9375rem] shadow-md ring-2 ring-slate-900/15 ring-offset-2 ring-offset-white'
                            : 'h-8 w-8 text-sm',
                          step > i + 1
                            ? 'bg-emerald-600 text-white'
                            : step === i + 1
                              ? 'bg-slate-900 text-white'
                              : 'bg-neutral-200 text-slate-600',
                        ]"
                      >
                        <span class="block leading-none select-none [font-feature-settings:'tnum']">
                          {{ i + 1 }}
                        </span>
                      </div>
                      <span
                        class="w-full text-[10px] font-medium leading-tight sm:text-[11px] md:text-xs line-clamp-2 transition-colors"
                        :class="
                          step === i + 1
                            ? 'text-slate-900 font-semibold'
                            : 'text-slate-500'
                        "
                      >
                        {{ label }}
                      </span>
                    </button>
                    <span
                      v-if="i < stepLabels.length - 1"
                      class="flex h-8 shrink-0 items-center px-0.5 text-neutral-300 sm:px-1"
                      aria-hidden="true"
                    >
                      <svg
                        class="h-3.5 w-3.5 sm:h-4 sm:w-4"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                      >
                        <path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          d="M9 5l7 7-7 7"
                        />
                      </svg>
                    </span>
                  </div>
                </template>
              </div>
            </div>
          </div>

          <div
            v-if="stepError"
            class="mb-6 flex gap-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900"
            role="alert"
          >
            <svg
              class="h-5 w-5 shrink-0 text-red-600 mt-0.5"
              aria-hidden="true"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
              />
            </svg>
            <p class="min-w-0 leading-relaxed">{{ stepError }}</p>
          </div>

          <!-- Шаг 1 -->
          <div v-show="step === 1" class="space-y-5">
            <div class="mb-4">
              <Typography as="h2" variant="h4" class="text-slate-900">
                Шаг 1. Данные ребёнка
              </Typography>
              <p class="mt-1.5 text-sm text-slate-600 leading-relaxed">
                {{ stepSubtitles[0] }}
              </p>
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">
                ФИО ребёнка <span class="text-red-600" aria-hidden="true">*</span>
              </label>
              <input v-model="childFullName" type="text" :class="inputClass" placeholder="Фамилия Имя Отчество" />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">
                Пол <span class="text-red-600" aria-hidden="true">*</span>
              </label>
              <select v-model="childGender" :class="inputClass">
                <option value="" disabled>Выберите</option>
                <option value="boy">Мальчик</option>
                <option value="girl">Девочка</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">
                Населённый пункт <span class="text-red-600" aria-hidden="true">*</span>
              </label>
              <input v-model="settlement" type="text" :class="inputClass" />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">
                Школа <span class="text-red-600" aria-hidden="true">*</span>
              </label>
              <input v-model="school" type="text" :class="inputClass" />
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">
                  Класс <span class="text-red-600" aria-hidden="true">*</span>
                </label>
                <select v-model="classNum" :class="inputClass">
                  <option value="" disabled>Выберите</option>
                  <option v-for="n in classNumSelectOptions" :key="n" :value="n">{{ n }}</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">
                  Литера <span class="text-red-600" aria-hidden="true">*</span>
                </label>
                <select v-model="classLetter" :class="inputClass">
                  <option value="" disabled>Выберите</option>
                  <option v-for="letter in classLetterSelectOptions" :key="letter" :value="letter">
                    {{ letter }}
                  </option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">
                  Учебный год <span class="text-red-600" aria-hidden="true">*</span>
                </label>
                <select v-model="schoolYear" :class="inputClass">
                  <option value="" disabled>Выберите</option>
                  <option v-for="y in schoolYearSelectOptions" :key="y" :value="y">{{ y }}</option>
                </select>
              </div>
            </div>
            <p class="text-xs text-slate-500 pt-1">
              <span class="text-red-600" aria-hidden="true">*</span>
              обязательные поля для заполнения
            </p>
          </div>

          <!-- Шаг 2 -->
          <div v-show="step === 2" class="space-y-5">
            <div class="mb-4">
              <Typography as="h2" variant="h4" class="text-slate-900">
                Шаг 2. Размер
              </Typography>
              <p class="mt-1.5 text-sm text-slate-600 leading-relaxed">
                {{ stepSubtitles[1] }}
              </p>
            </div>
            <div>
              <label for="order-size-from-table" class="block text-sm font-medium text-slate-700 mb-1">
                Размер по таблице <span class="text-red-600" aria-hidden="true">*</span>
              </label>
              <a
                href="/sizes"
                target="_blank"
                rel="noopener noreferrer"
                class="mb-2 inline-block text-sm font-medium text-slate-600 hover:text-slate-900 underline underline-offset-2"
              >
                Смотреть таблицу размеров
              </a>
              <select
                id="order-size-from-table"
                v-model="sizeFromTable"
                :class="inputClass"
              >
                <option value="" disabled>Выберите</option>
                <option v-for="s in sizeFromTableSelectOptions" :key="s" :value="s">{{ s }}</option>
              </select>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">
                  Рост <span class="text-red-600" aria-hidden="true">*</span>
                </label>
                <div class="relative">
                  <input
                    v-model="heightCm"
                    type="text"
                    inputmode="decimal"
                    autocomplete="off"
                    enterkeyhint="next"
                    :class="measureInputClass"
                    placeholder="например, 152"
                  />
                  <span
                    class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-400 select-none"
                    aria-hidden="true"
                  >
                    см
                  </span>
                </div>
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">
                  Грудь <span class="text-red-600" aria-hidden="true">*</span>
                </label>
                <div class="relative">
                  <input
                    v-model="chestCm"
                    type="text"
                    inputmode="decimal"
                    autocomplete="off"
                    enterkeyhint="next"
                    :class="measureInputClass"
                    placeholder="например, 72"
                  />
                  <span
                    class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-400 select-none"
                    aria-hidden="true"
                  >
                    см
                  </span>
                </div>
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">
                  Талия <span class="text-red-600" aria-hidden="true">*</span>
                </label>
                <div class="relative">
                  <input
                    v-model="waistCm"
                    type="text"
                    inputmode="decimal"
                    autocomplete="off"
                    enterkeyhint="next"
                    :class="measureInputClass"
                    placeholder="например, 64"
                  />
                  <span
                    class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-400 select-none"
                    aria-hidden="true"
                  >
                    см
                  </span>
                </div>
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">
                  Бёдра <span class="text-red-600" aria-hidden="true">*</span>
                </label>
                <div class="relative">
                  <input
                    v-model="hipsCm"
                    type="text"
                    inputmode="decimal"
                    autocomplete="off"
                    enterkeyhint="done"
                    :class="measureInputClass"
                    placeholder="например, 78"
                  />
                  <span
                    class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-400 select-none"
                    aria-hidden="true"
                  >
                    см
                  </span>
                </div>
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
            <p class="text-xs text-slate-500 pt-1">
              <span class="text-red-600" aria-hidden="true">*</span>
              обязательные поля для заполнения
            </p>
          </div>

          <!-- Шаг 3 -->
          <div v-show="step === 3" class="space-y-5">
            <div class="mb-4">
              <Typography as="h2" variant="h4" class="text-slate-900">
                Шаг 3. Комплект
              </Typography>
              <p class="mt-1.5 text-sm text-slate-600 leading-relaxed">
                {{ stepSubtitles[2] }}
              </p>
            </div>
            <div :class="stepCardClass">
            <div class="rounded-lg border border-neutral-200 bg-white p-4">
              <p class="text-sm font-medium text-slate-800">
                Доступные комплекты
                {{ childGender === 'boy' ? 'для мальчиков' : childGender === 'girl' ? 'для девочек' : '' }}
              </p>
              <p v-if="!childGender" class="mt-1 text-xs text-slate-500">
                Выберите пол ребёнка на шаге 1, чтобы увидеть рекомендации.
              </p>
              <template v-else>
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
                          :src="resolveBackendMediaUrl(kit.image)"
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
                          :src="resolveBackendMediaUrl(kit.image)"
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
                        Добавить +
                      </button>
                      <button
                        type="button"
                        class="rounded border border-slate-300 px-2 py-1 text-xs text-slate-700 hover:bg-slate-100"
                        @click="addSuggestedKitAsNewLine(kit.name)"
                      >
                        В новую позицию +
                      </button>
                    </div>
                  </div>
                </div>
              </template>
            </div>
            <datalist v-if="availableKitSuggestions.length" id="kit-name-suggestions">
              <option v-for="kit in availableKitSuggestions" :key="kit.name" :value="kit.name" />
            </datalist>
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
                <label class="block text-sm font-medium text-slate-700 mb-1">
                  Наименование изделия <span class="text-red-600" aria-hidden="true">*</span>
                </label>
                <input
                  v-model="line.productName"
                  type="text"
                  :class="inputClass"
                  :list="availableKitSuggestions.length ? 'kit-name-suggestions' : undefined"
                  @focus="selectedKitLineIndex = idx"
                />
              </div>
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                  <label class="block text-sm font-medium text-slate-700 mb-1">
                    Количество <span class="text-red-600" aria-hidden="true">*</span>
                  </label>
                  <div
                    class="flex h-8 w-full max-w-[9rem] items-stretch overflow-hidden rounded border border-neutral-200 bg-white shadow-sm focus-within:ring-2 focus-within:ring-slate-400"
                  >
                    <button
                      type="button"
                      class="flex min-w-0 flex-1 basis-0 items-center justify-center bg-neutral-50 text-lg font-bold leading-none text-slate-700 transition hover:bg-neutral-100 disabled:cursor-not-allowed disabled:bg-neutral-50 disabled:text-slate-400 disabled:hover:bg-neutral-50"
                      :disabled="Math.max(1, Math.floor(Number(line.quantity)) || 1) <= 1"
                      aria-label="Уменьшить количество"
                      @click="adjustKitLineQuantity(idx, -1)"
                    >
                      −
                    </button>
                    <input
                      v-model="line.quantity"
                      type="number"
                      min="1"
                      class="min-w-0 flex-1 basis-0 border-0 bg-white px-0.5 py-0 text-center text-base font-bold tabular-nums leading-none text-slate-900 focus:outline-none [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
                      @blur="normalizeKitLineQuantity(idx)"
                    />
                    <button
                      type="button"
                      class="flex min-w-0 flex-1 basis-0 items-center justify-center bg-neutral-50 text-lg font-bold leading-none text-slate-700 transition hover:bg-neutral-100"
                      aria-label="Увеличить количество"
                      @click="adjustKitLineQuantity(idx, 1)"
                    >
                      +
                    </button>
                  </div>
                </div>
                <div class="sm:col-span-2">
                  <label class="block text-sm font-medium text-slate-700 mb-1">
                    Размер по позиции, если отличается от общего
                  </label>
                  <input v-model="line.sizeOverride" type="text" :class="inputClass" />
                </div>
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Комментарий</label>
                <input v-model="line.lineComment" type="text" :class="inputClass" />
              </div>
            </div>
            <Button type="button" variant="outline" size="sm" @click="addKitLine">
              + Добавить позицию
            </Button>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Комментарий к комплекту</label>
              <textarea v-model="kitComment" rows="3" :class="inputClass" />
            </div>
            <p class="text-xs text-slate-500 pt-1">
              <span class="text-red-600" aria-hidden="true">*</span>
              обязательные поля для заполнения
            </p>
            </div>
          </div>

          <!-- Шаг 4 -->
          <div v-show="step === 4" class="space-y-5">
            <div class="mb-4">
              <Typography as="h2" variant="h4" class="text-slate-900">
                Шаг 4. Данные родителя
              </Typography>
              <p class="mt-1.5 text-sm text-slate-600 leading-relaxed">
                {{ stepSubtitles[3] }}
              </p>
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">
                ФИО родителя <span class="text-red-600" aria-hidden="true">*</span>
              </label>
              <input v-model="parentFullName" type="text" :class="inputClass" />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">
                Телефон <span class="text-red-600" aria-hidden="true">*</span>
              </label>
              <input
                :value="formatParentPhoneMask(parentPhone)"
                type="tel"
                inputmode="tel"
                autocomplete="tel"
                :class="[inputClass, 'tabular-nums']"
                @beforeinput="onParentPhoneBeforeInput"
                @paste="onParentPhonePaste"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">
                Email
              </label>
              <p
                v-if="isAuthenticated"
                class="mb-1.5 text-xs text-slate-500 leading-relaxed"
              >
                По умолчанию будет использован email вашей учетной записи
              </p>
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
            <p class="text-xs text-slate-500 pt-1">
              <span class="text-red-600" aria-hidden="true">*</span>
              обязательные поля для заполнения
            </p>
          </div>

          <!-- Шаг 5 -->
          <div v-show="step === 5" class="space-y-5">
            <div class="mb-4">
              <Typography as="h2" variant="h4" class="text-slate-900">
                Шаг 5. Получение
              </Typography>
              <p class="mt-1.5 text-sm text-slate-600 leading-relaxed">
                {{ stepSubtitles[4] }}
              </p>
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-2">
                Кто будет получать заказ <span class="text-red-600" aria-hidden="true">*</span>
              </label>
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
              <label class="block text-sm font-medium text-slate-700 mb-1">
                ФИО получателя <span class="text-red-600" aria-hidden="true">*</span>
              </label>
              <input v-model="recipientName" type="text" :class="inputClass" />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">
                Телефон получателя <span class="text-red-600" aria-hidden="true">*</span>
              </label>
              <input
                v-model="recipientPhone"
                type="tel"
                :class="inputClass"
                :readonly="recipientIsCustomer"
                :placeholder="recipientIsCustomer ? 'Совпадает с телефоном заказчика' : '+7 …'"
              />
            </div>
            <p class="text-xs text-slate-500 pt-1">
              <span class="text-red-600" aria-hidden="true">*</span>
              обязательные поля для заполнения
            </p>
          </div>

          <!-- Шаг 6 -->
          <div v-show="step === 6" class="space-y-5">
            <div class="mb-4">
              <Typography as="h2" variant="h4" class="text-slate-900">
                Шаг 6. Подтверждение
              </Typography>
              <p class="mt-1.5 text-sm text-slate-600 leading-relaxed">
                {{ stepSubtitles[5] }}
              </p>
            </div>
            <div :class="stepCardClass">
            <div
              class="rounded-lg border border-emerald-200 bg-emerald-50/80 px-4 py-3"
            >
              <Typography v-if="orderTotalLoading" as="p" variant="body" class="text-slate-700">
                Подсчёт суммы…
              </Typography>
              <Typography
                v-else-if="orderTotalError"
                as="p"
                variant="body"
                class="text-amber-800"
              >
                {{ orderTotalError }}
              </Typography>
              <Typography v-else as="p" variant="body" class="text-slate-900 font-medium">
                Сумма заказа:
                {{
                  orderTotalEstimate != null
                    ? formatOrderTotalRub(orderTotalEstimate)
                    : '—'
                }}
              </Typography>
            </div>

            <div class="mb-4">
              <Typography as="h3" variant="body" class="text-sm font-medium text-slate-500 mb-3">
                Комплект
              </Typography>
              <div class="space-y-3">
                <div
                  v-for="(row, idx) in confirmKitRows"
                  :key="`${row.productName}-${idx}`"
                  class="flex gap-4 rounded-xl border border-neutral-200 bg-white p-3 shadow-sm"
                >
                  <div
                    class="shrink-0 w-24 h-24 sm:w-28 sm:h-28 rounded-lg bg-neutral-100 overflow-hidden border border-neutral-100"
                  >
                    <img
                      v-if="row.image"
                      :src="resolveBackendMediaUrl(row.image)"
                      :alt="row.productName"
                      class="w-full h-full object-cover"
                    />
                    <div
                      v-else
                      class="w-full h-full flex items-center justify-center text-xs text-neutral-400 text-center px-1"
                    >
                      Нет фото
                    </div>
                  </div>
                  <div class="min-w-0 flex-1 flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2">
                    <div class="min-w-0 space-y-1">
                      <p class="text-sm font-semibold text-slate-900 leading-snug">
                        {{ row.productName }}
                      </p>
                      <p class="text-sm text-slate-600">
                        Количество: {{ row.quantity }}
                        <span
                          v-if="row.unitPrice != null"
                          class="text-slate-500"
                        >
                          · {{ formatOrderTotalRub(row.unitPrice) }} за шт.
                        </span>
                      </p>
                      <p v-if="row.sizeOverride" class="text-xs text-slate-600">
                        Размер: {{ row.sizeOverride }}
                      </p>
                      <p v-if="row.lineComment" class="text-xs text-slate-600">
                        {{ row.lineComment }}
                      </p>
                    </div>
                    <div class="shrink-0 text-left sm:text-right">
                      <p
                        v-if="orderTotalLoading"
                        class="text-sm text-slate-500"
                      >
                        …
                      </p>
                      <p
                        v-else
                        class="text-base font-semibold text-slate-900 tabular-nums"
                      >
                        {{
                          row.lineTotal != null
                            ? formatOrderTotalRub(row.lineTotal)
                            : '—'
                        }}
                      </p>
                      <p
                        v-if="!orderTotalLoading && row.lineTotal === 0 && row.unitPrice == null"
                        class="text-xs text-amber-800 mt-1 max-w-[12rem] sm:max-w-none sm:ml-auto"
                      >
                        Не найдено в каталоге — сумма по строке 0 ₽
                      </p>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <dl class="rounded-lg border border-neutral-200 overflow-hidden divide-y divide-neutral-200">
              <div
                v-for="(row, idx) in summaryLines"
                :key="row.label"
                :class="[
                  'grid grid-cols-1 sm:grid-cols-3 gap-1 px-4 py-3 sm:gap-4',
                  idx % 2 === 1 ? 'bg-neutral-100/70' : 'bg-white',
                ]"
              >
                <dt class="text-sm font-medium text-slate-500 sm:col-span-1">{{ row.label }}</dt>
                <dd class="text-sm text-slate-900 sm:col-span-2 whitespace-pre-wrap">{{ row.value }}</dd>
              </div>
            </dl>
            <Typography
              as="p"
              variant="body"
              class="mt-6 rounded-lg border border-slate-200 bg-slate-50/90 px-4 py-3 text-slate-700 leading-relaxed"
            >
              После оформления заказа менеджер свяжется с вами для уточнения деталей.
            </Typography>
            <Typography
              as="p"
              variant="body"
              class="rounded-lg border border-neutral-200 bg-white px-4 py-3 text-slate-700 leading-relaxed"
            >
              Получение заказа по адресу: пгт. Агинское, с Хусатуй, ул. Хусатуй, д.16

            </Typography>
            <div
              v-if="submitError"
              class="flex gap-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900"
              role="alert"
            >
              <svg
                class="h-5 w-5 shrink-0 text-red-600 mt-0.5"
                aria-hidden="true"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                />
              </svg>
              <p class="min-w-0 leading-relaxed">{{ submitError }}</p>
            </div>
            </div>
          </div>

          <!-- Nav: на мобиле сначала «Далее», ниже отделённо «Назад» -->
          <div class="mt-10 border-t border-neutral-200 pt-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between sm:gap-4">
              <div class="order-2 flex w-full min-h-[2.75rem] items-stretch sm:order-1 sm:flex-1 sm:justify-start">
                <Button
                  v-if="step > 1"
                  type="button"
                  variant="outline"
                  class="w-full sm:w-auto border-neutral-300 bg-neutral-50 text-slate-800 hover:bg-neutral-100"
                  @click="prevStep"
                >
                  Назад
                </Button>
              </div>
              <div class="order-1 flex w-full justify-stretch sm:order-2 sm:w-auto sm:justify-end">
                <Button
                  v-if="step < totalSteps"
                  type="button"
                  variant="primary"
                  class="w-full sm:w-auto min-w-[10rem]"
                  @click="nextStep"
                >
                  Далее
                </Button>
                <Button
                  v-else
                  type="button"
                  variant="primary"
                  class="w-full sm:w-auto min-w-[10rem]"
                  :disabled="submitLoading"
                  @click="submitOrder"
                >
                  {{ submitLoading ? 'Переход…' : 'Перейти к оплате' }}
                </Button>
              </div>
            </div>
            <p class="mt-1.5 text-center text-xs text-slate-500 sm:hidden">
              «Назад» — вернуться и изменить данные
            </p>
          </div>
        </div>
      </div>
    </main>

    <Footer />
  </div>
</template>
