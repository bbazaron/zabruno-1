<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import Header from '../components/sections/Header.vue'
import Footer from '../components/sections/Footer.vue'
import Button from '../components/ui/Button.vue'
import Typography from '../components/ui/Typography.vue'
import { useToast } from '../composables/useToast'
import { resolveBackendMediaUrl } from '../utils/resolveBackendMediaUrl'

const AUTH_TOKEN_KEY = 'auth_token'

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
  description: string | null
  in_stock: boolean
}

const router = useRouter()
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
const formImage = ref('')
const formDescription = ref('')
const formInStock = ref(true)

const categories = [
  { id: 'cardigans', label: 'Кардиганы' },
  { id: 'vests', label: 'Жилеты' },
  { id: 'skirts', label: 'Юбки' },
  { id: 'pants', label: 'Брюки' },
  { id: 'Комплекты', label: 'Комплекты' },
]

const inputClass =
  'w-full border border-neutral-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900'

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
  formImage.value = ''
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
  formImage.value = p.image ?? ''
  formDescription.value = p.description ?? ''
  formInStock.value = Boolean(p.in_stock)
}

function buildPayload(): Record<string, unknown> {
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
    image: formImage.value.trim() || null,
    description: formDescription.value.trim() || null,
    in_stock: formInStock.value,
  }
}

function formatApiError(err: unknown): string {
  const e = err as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } }
  const data = e.response?.data
  if (!data) return 'Ошибка запроса'
  if (typeof data.message === 'string') return data.message
  if (data.errors) return Object.values(data.errors).flat().join(' ')
  return 'Ошибка запроса'
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
    const payload = buildPayload()
    if (editingId.value != null) {
      await axios.patch(`/api/admin/products/${editingId.value}`, payload, {
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
        <Button variant="secondary" size="sm" @click="router.push('/admin')">
          Управление администраторами
        </Button>
        <Button variant="secondary" size="sm" @click="router.push('/admin/orders')">
          Управление заказами
        </Button>
        <Button variant="secondary" size="sm" @click="router.push('/admin/products')">
          Управление товарами
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
            <label class="block text-xs font-medium text-slate-600 mb-1" for="ap-image"
              >Путь к изображению</label
            >
            <input
              id="ap-image"
              v-model="formImage"
              type="text"
              :class="inputClass"
              placeholder="Например: /storage/products/photo.jpg"
            />
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
                  <img
                    v-if="p.image"
                    :src="resolveBackendMediaUrl(p.image)"
                    alt=""
                    class="h-10 w-10 rounded object-cover border border-neutral-200"
                  />
                  <span v-else class="text-slate-400 text-xs">—</span>
                </td>
                <td class="py-2 px-3 align-middle max-w-[200px]">
                  <span class="line-clamp-2">{{ p.name }}</span>
                </td>
                <td class="py-2 px-3 align-middle">{{ p.category }}</td>
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
