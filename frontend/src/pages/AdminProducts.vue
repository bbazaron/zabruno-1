<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import Header from '../components/sections/Header.vue'
import Footer from '../components/sections/Footer.vue'
import Button from '../components/ui/Button.vue'
import Typography from '../components/ui/Typography.vue'
import { useToast } from '../composables/useToast'
import { resolveBackendMediaUrl } from '../utils/resolveBackendMediaUrl'

const AUTH_TOKEN_KEY = 'auth_token'
const MAX_MEDIA_FILES = 10
const ACCEPTED_IMAGE_TYPES = new Set(['image/png', 'image/jpeg', 'image/webp'])

interface AdminProduct {
  id: number
  name: string
  category: string
  gender: string
  season: string | null
  price: string | number
  original_price: string | number | null
  color: string | null
  image: string | null
  media?: Array<{
    id: number
    path: string
    sort_order: number
  }>
  description: string | null
  in_stock: boolean
}

const router = useRouter()
const route = useRoute()
const { showToast } = useToast()

const products = ref<AdminProduct[]>([])
const loading = ref(false)
const saving = ref(false)
const editingId = ref<number | null>(null)

const formName = ref('')
const formCategory = ref('')
const formGender = ref<'boys' | 'girls'>('boys')
const formSeason = ref('')
const formPrice = ref('')
const formOriginalPrice = ref('')
const formColor = ref('')
const formMediaFiles = ref<File[]>([])
const existingMedia = ref<Array<{ id: number; path: string; sort_order: number }>>([])
const removeMediaIds = ref<number[]>([])
const mediaInputKey = ref(0)
const formDescription = ref('')
const formInStock = ref(true)

const categories = [
  { id: 'cardigans', label: 'Кардиганы' },
  { id: 'vests', label: 'Жилеты' },
  { id: 'skirts', label: 'Юбки' },
  { id: 'pants', label: 'Брюки' },
  { id: 'sets', label: 'Комплекты' },
]

const categoryLabelById = Object.fromEntries(categories.map((category) => [category.id, category.label])) as Record<
  string,
  string
>

const inputClass =
  'w-full border border-neutral-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900'
const fileRowClass =
  'flex items-center justify-between gap-3 rounded-md border border-neutral-200 bg-neutral-50 px-3 py-2 text-sm'

function fileSignature(file: File): string {
  return `${file.name}|${file.size}|${file.lastModified}`
}

function formatFileSize(bytes: number): string {
  if (bytes < 1024) return `${bytes} Б`
  const kb = bytes / 1024
  if (kb < 1024) return `${kb.toFixed(1)} КБ`
  return `${(kb / 1024).toFixed(1)} МБ`
}

function getAuthHeaders(): Record<string, string> {
  const headers: Record<string, string> = { Accept: 'application/json' }
  const token = localStorage.getItem(AUTH_TOKEN_KEY) || localStorage.getItem('token')
  if (token) headers.Authorization = `Bearer ${token}`
  return headers
}

function resetForm() {
  editingId.value = null
  formName.value = ''
  formCategory.value = categories[0]?.id ?? ''
  formGender.value = 'boys'
  formSeason.value = ''
  formPrice.value = ''
  formOriginalPrice.value = ''
  formColor.value = ''
  formMediaFiles.value = []
  existingMedia.value = []
  removeMediaIds.value = []
  mediaInputKey.value += 1
  formDescription.value = ''
  formInStock.value = true
}

function fillForm(p: AdminProduct) {
  editingId.value = p.id
  formName.value = p.name
  formCategory.value = p.category
  formGender.value = p.gender === 'girls' ? 'girls' : 'boys'
  formSeason.value = p.season ?? ''
  formPrice.value = String(p.price ?? '')
  formOriginalPrice.value =
    p.original_price != null && p.original_price !== '' ? String(p.original_price) : ''
  formColor.value = p.color ?? ''
  formMediaFiles.value = []
  existingMedia.value = Array.isArray(p.media) ? [...p.media].sort((a, b) => a.sort_order - b.sort_order) : []
  removeMediaIds.value = []
  mediaInputKey.value += 1
  formDescription.value = p.description ?? ''
  formInStock.value = Boolean(p.in_stock)
}

function buildPayload(): Record<string, string | number | boolean | null> {
  const price = parseFloat(String(formPrice.value).replace(',', '.'))
  const originalRaw = formOriginalPrice.value.trim()
  const original =
    originalRaw === '' ? null : parseFloat(String(originalRaw).replace(',', '.'))
  return {
    name: formName.value.trim(),
    category: formCategory.value,
    gender: formGender.value,
    season: formSeason.value.trim() || null,
    price: Number.isFinite(price) ? price : 0,
    original_price: original != null && Number.isFinite(original) ? original : null,
    color: formColor.value.trim() || null,
    description: formDescription.value.trim() || null,
    in_stock: formInStock.value,
  }
}

function onMediaFilesChange(event: Event) {
  const input = event.target as HTMLInputElement
  const files = Array.from(input.files ?? [])
  if (files.length === 0) return

  const next = [...formMediaFiles.value]
  const known = new Set(next.map(fileSignature))
  let skippedByType = 0
  let skippedByLimit = 0

  for (const file of files) {
    if (!ACCEPTED_IMAGE_TYPES.has(file.type)) {
      skippedByType += 1
      continue
    }
    const signature = fileSignature(file)
    if (known.has(signature)) {
      continue
    }
    if (next.length >= MAX_MEDIA_FILES) {
      skippedByLimit += 1
      continue
    }
    next.push(file)
    known.add(signature)
  }

  formMediaFiles.value = next
  input.value = ''

  if (skippedByType > 0) {
    showToast('Можно загружать только PNG, JPG, JPEG и WEBP', 'error')
  }
  if (skippedByLimit > 0) {
    showToast(`Можно выбрать не более ${MAX_MEDIA_FILES} файлов`, 'error')
  }
}

function removeSelectedMediaFile(index: number) {
  formMediaFiles.value = formMediaFiles.value.filter((_, idx) => idx !== index)
}

function markExistingMediaForRemoval(mediaId: number) {
  if (removeMediaIds.value.includes(mediaId)) return
  removeMediaIds.value = [...removeMediaIds.value, mediaId]
}

function restoreExistingMedia(mediaId: number) {
  removeMediaIds.value = removeMediaIds.value.filter((id) => id !== mediaId)
}

function existingMediaMarkedForRemoval(mediaId: number): boolean {
  return removeMediaIds.value.includes(mediaId)
}

function buildFormData(): FormData {
  const payload = buildPayload()
  const fd = new FormData()
  for (const [key, value] of Object.entries(payload)) {
    if (value === undefined) continue
    if (value === null) {
      fd.append(key, '')
      continue
    }
    if (typeof value === 'boolean') {
      fd.append(key, value ? '1' : '0')
      continue
    }
    fd.append(key, String(value))
  }
  for (const file of formMediaFiles.value) {
    fd.append('media[]', file)
  }
  for (const mediaId of removeMediaIds.value) {
    fd.append('remove_media_ids[]', String(mediaId))
  }
  return fd
}

function formatApiError(err: unknown): string {
  const e = err as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } }
  const data = e.response?.data
  if (!data) return 'Ошибка запроса'
  if (typeof data.message === 'string') return data.message
  if (data.errors) return Object.values(data.errors).flat().join(' ')
  return 'Ошибка запроса'
}

function getCategoryLabel(categoryId: string): string {
  return categoryLabelById[categoryId] ?? categoryId
}

function isAdminTabActive(path: '/admin' | '/admin/orders' | '/admin/products'): boolean {
  if (path === '/admin/orders') return route.path.startsWith('/admin/orders')
  return route.path === path
}

async function loadProducts() {
  loading.value = true
  try {
    const res = await axios.get<{ products?: AdminProduct[] }>('/api/admin/products', {
      headers: getAuthHeaders(),
    })
    products.value = Array.isArray(res.data?.products) ? res.data.products : []
  } catch {
    showToast('Не удалось загрузить товары', 'error')
    products.value = []
  } finally {
    loading.value = false
  }
}

async function submitForm() {
  if (!formName.value.trim() || !formCategory.value) {
    showToast('Укажите название и категорию', 'error')
    return
  }
  const priceNum = parseFloat(String(formPrice.value).replace(',', '.'))
  if (!Number.isFinite(priceNum) || priceNum < 0) {
    showToast('Укажите корректную цену', 'error')
    return
  }

  saving.value = true
  try {
    const payload = buildFormData()
    if (editingId.value != null) {
      payload.append('_method', 'PATCH')
      await axios.post(`/api/admin/products/${editingId.value}`, payload, {
        headers: getAuthHeaders(),
      })
      showToast('Товар обновлён', 'success')
    } else {
      await axios.post('/api/admin/products', payload, {
        headers: getAuthHeaders(),
      })
      showToast('Товар создан', 'success')
    }
    resetForm()
    await loadProducts()
  } catch (err) {
    showToast(formatApiError(err), 'error')
  } finally {
    saving.value = false
  }
}

function startEdit(p: AdminProduct) {
  fillForm(p)
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

function cancelEdit() {
  resetForm()
}

async function removeProduct(p: AdminProduct) {
  if (!confirm(`Удалить товар «${p.name}» (id ${p.id})?`)) return
  try {
    await axios.delete(`/api/admin/products/${p.id}`, { headers: getAuthHeaders() })
    showToast('Товар удалён', 'success')
    if (editingId.value === p.id) resetForm()
    await loadProducts()
  } catch (err) {
    showToast(formatApiError(err), 'error')
  }
}

const formTitle = computed(() =>
  editingId.value != null ? `Редактирование товара №${editingId.value}` : 'Новый товар',
)

onMounted(() => {
  resetForm()
  void loadProducts()
})
</script>

<template>
  <Header />
  <section class="relative w-full min-h-screen font-sans text-slate-900 bg-neutral-50/50">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <Typography as="h1" class="text-3xl md:text-4xl font-light">Управление товарами</Typography>

      <div class="mt-2 mb-8 flex flex-wrap gap-3">
        <Button
          :variant="isAdminTabActive('/admin/orders') ? 'primary' : 'secondary'"
          size="sm"
          @click="router.push('/admin/orders')"
        >
          Управление заказами
        </Button>
        <Button
          :variant="isAdminTabActive('/admin/products') ? 'primary' : 'secondary'"
          size="sm"
          @click="router.push('/admin/products')"
        >
          Управление товарами
        </Button>
        <Button :variant="isAdminTabActive('/admin') ? 'primary' : 'secondary'" size="sm" @click="router.push('/admin')">
          Управление администраторами
        </Button>
      </div>

      <div class="rounded-lg border border-neutral-200 bg-white p-4 sm:p-6 mb-8 shadow-sm">
        <Typography as="h2" class="text-lg font-medium mb-4">{{ formTitle }}</Typography>
        <form class="grid grid-cols-1 md:grid-cols-2 gap-4" @submit.prevent="submitForm">
          <div class="md:col-span-2">
            <label class="block text-xs font-medium text-slate-600 mb-1" for="ap-name">Название</label>
            <input id="ap-name" v-model="formName" type="text" required :class="inputClass" />
          </div>
          <div>
            <label class="block text-xs font-medium text-slate-600 mb-1" for="ap-cat">Категория</label>
            <select id="ap-cat" v-model="formCategory" required :class="inputClass">
              <option value="" disabled>Выберите</option>
              <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.label }}</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-medium text-slate-600 mb-1" for="ap-gender">Пол</label>
            <select id="ap-gender" v-model="formGender" :class="inputClass">
              <option value="boys">Мальчики</option>
              <option value="girls">Девочки</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-medium text-slate-600 mb-1" for="ap-price">Цена (₽)</label>
            <input
              id="ap-price"
              v-model="formPrice"
              type="text"
              inputmode="decimal"
              required
              :class="inputClass"
              placeholder="0"
            />
          </div>
          <div>
            <label class="block text-xs font-medium text-slate-600 mb-1" for="ap-original"
              >Старая цена (₽)</label
            >
            <input
              id="ap-original"
              v-model="formOriginalPrice"
              type="text"
              inputmode="decimal"
              :class="inputClass"
              placeholder="Необязательно"
            />
          </div>
          <div>
            <label class="block text-xs font-medium text-slate-600 mb-1" for="ap-season">Сезон</label>
            <input id="ap-season" v-model="formSeason" type="text" :class="inputClass" placeholder="Необязательно" />
          </div>
          <div>
            <label class="block text-xs font-medium text-slate-600 mb-1" for="ap-color">Цвет</label>
            <input id="ap-color" v-model="formColor" type="text" :class="inputClass" placeholder="Необязательно" />
          </div>
          <div class="md:col-span-2">
            <label class="block text-xs font-medium text-slate-600 mb-2">Изображения товара</label>
            <div v-if="editingId != null && existingMedia.length > 0" class="mb-3 space-y-2">
              <p class="text-xs text-slate-500">Текущие изображения товара</p>
              <div
                v-for="media in existingMedia"
                :key="media.id"
                class="flex items-center justify-between gap-3 rounded-md border border-neutral-200 bg-white px-3 py-2"
              >
                <div class="flex min-w-0 items-center gap-3">
                  <img
                    :src="resolveBackendMediaUrl(media.path)"
                    alt=""
                    class="h-12 w-12 rounded object-cover border border-neutral-200"
                  />
                  <div class="min-w-0">
                    <p class="truncate text-sm text-slate-800">{{ media.path.split('/').pop() }}</p>
                    <p class="text-xs text-slate-500">
                      {{ media.sort_order === 0 ? 'Главное изображение' : `Позиция ${media.sort_order + 1}` }}
                    </p>
                  </div>
                </div>
                <button
                  v-if="!existingMediaMarkedForRemoval(media.id)"
                  type="button"
                  class="shrink-0 text-xs text-red-600 underline hover:text-red-800"
                  @click="markExistingMediaForRemoval(media.id)"
                >
                  Удалить
                </button>
                <button
                  v-else
                  type="button"
                  class="shrink-0 text-xs text-slate-700 underline hover:text-slate-900"
                  @click="restoreExistingMedia(media.id)"
                >
                  Отменить удаление
                </button>
              </div>
              <p v-if="removeMediaIds.length > 0" class="text-xs text-amber-700">
                Выбрано к удалению: {{ removeMediaIds.length }}. Удаление произойдет после сохранения.
              </p>
            </div>
            <input
              :key="mediaInputKey"
              id="ap-image"
              type="file"
              accept="image/png,image/jpeg,image/jpg,image/webp"
              multiple
              class="hidden"
              @change="onMediaFilesChange"
            />
            <div class="flex flex-wrap items-center gap-3">
              <label
                for="ap-image"
                class="inline-flex cursor-pointer items-center rounded-md border border-neutral-300 px-3 py-2 text-sm text-slate-700 hover:bg-neutral-100"
              >
                Добавить файлы
              </label>
              <span class="text-xs text-slate-500">Выбрано {{ formMediaFiles.length }} из {{ MAX_MEDIA_FILES }}</span>
            </div>
            <p class="mt-1 text-xs text-slate-500">Можно выбрать несколько файлов и добавлять их поэтапно.</p>
            <div v-if="formMediaFiles.length > 0" class="mt-3 space-y-2">
              <div v-for="(file, idx) in formMediaFiles" :key="fileSignature(file)" :class="fileRowClass">
                <div class="min-w-0">
                  <p class="truncate text-sm text-slate-800">{{ file.name }}</p>
                  <p class="text-xs text-slate-500">{{ formatFileSize(file.size) }}</p>
                </div>
                <button
                  type="button"
                  class="shrink-0 text-xs text-red-600 underline hover:text-red-800"
                  @click="removeSelectedMediaFile(idx)"
                >
                  Убрать
                </button>
              </div>
            </div>
          </div>
          <div class="md:col-span-2">
            <label class="block text-xs font-medium text-slate-600 mb-1" for="ap-desc">Описание</label>
            <textarea
              id="ap-desc"
              v-model="formDescription"
              rows="3"
              :class="inputClass"
              placeholder="Описание товара"
            />
          </div>
          <div class="md:col-span-2 flex items-center gap-2">
            <input id="ap-stock" v-model="formInStock" type="checkbox" class="rounded border-neutral-300" />
            <label for="ap-stock" class="text-sm text-slate-700">В наличии</label>
          </div>
          <div class="md:col-span-2 flex flex-wrap gap-3">
            <Button type="submit" variant="primary" :disabled="saving">
              {{ saving ? 'Сохранение…' : editingId != null ? 'Сохранить изменения' : 'Создать товар' }}
            </Button>
            <Button v-if="editingId != null" type="button" variant="outline" @click="cancelEdit">
              Отмена
            </Button>
          </div>
        </form>
      </div>

      <div class="rounded-lg border border-neutral-200 bg-white p-4 shadow-sm overflow-hidden">
        <Typography as="h2" class="text-lg font-medium mb-4">Каталог (админ)</Typography>
        <div v-if="loading" class="text-slate-600 py-8 text-center">Загрузка…</div>
        <div v-else-if="products.length === 0" class="text-slate-600 py-8 text-center">Товаров пока нет</div>
        <div v-else class="overflow-x-auto">
          <table class="w-full text-left text-sm border-collapse min-w-[640px]">
            <thead>
              <tr class="border-b border-slate-200 bg-slate-50/80">
                <th class="py-2 px-3 font-medium text-slate-700">ID</th>
                <th class="py-2 px-3 font-medium text-slate-700">Фото</th>
                <th class="py-2 px-3 font-medium text-slate-700">Название</th>
                <th class="py-2 px-3 font-medium text-slate-700">Категория</th>
                <th class="py-2 px-3 font-medium text-slate-700">Пол</th>
                <th class="py-2 px-3 font-medium text-slate-700">Цена</th>
                <th class="py-2 px-3 font-medium text-slate-700">Наличие</th>
                <th class="py-2 px-3 font-medium text-slate-700 w-[1%] whitespace-nowrap">Действия</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="p in products" :key="p.id" class="border-b border-slate-100">
                <td class="py-2 px-3 align-middle tabular-nums">{{ p.id }}</td>
                <td class="py-2 px-3 align-middle">
                  <router-link v-if="p.image" :to="`/product/${p.id}`" class="inline-block">
                    <img
                      :src="resolveBackendMediaUrl(p.image)"
                      alt=""
                      class="h-10 w-10 rounded object-cover border border-neutral-200"
                    />
                  </router-link>
                  <span v-else class="text-slate-400 text-xs">—</span>
                </td>
                <td class="py-2 px-3 align-middle max-w-[200px]">
                  <router-link :to="`/product/${p.id}`" class="line-clamp-2 text-slate-900 hover:underline">
                    {{ p.name }}
                  </router-link>
                </td>
                <td class="py-2 px-3 align-middle">{{ getCategoryLabel(p.category) }}</td>
                <td class="py-2 px-3 align-middle">{{ p.gender === 'boys' ? 'Мальчики' : 'Девочки' }}</td>
                <td class="py-2 px-3 align-middle tabular-nums">{{ p.price }} ₽</td>
                <td class="py-2 px-3 align-middle">{{ p.in_stock ? 'Да' : 'Нет' }}</td>
                <td class="py-2 px-3 align-middle">
                  <div class="flex flex-wrap gap-2">
                    <button
                      type="button"
                      class="text-slate-700 underline text-xs hover:text-slate-900"
                      @click="startEdit(p)"
                    >
                      Изменить
                    </button>
                    <button
                      type="button"
                      class="text-red-600 underline text-xs hover:text-red-800"
                      @click="removeProduct(p)"
                    >
                      Удалить
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </section>
  <Footer />
</template>
