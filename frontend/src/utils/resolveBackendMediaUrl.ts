import axios from 'axios'

/**
 * Значение `image` из API часто хранится как путь (`/storage/...` или `storage/...`).
 * В каталоге страница открыта с origin фронта (например http://localhost:5173), и браузер
 * подставляет его к относительному пути — запрос уходит не на Laravel, картинка не находится,
 * показывается только alt. Нормализуем к базе бэкенда (axios.defaults.baseURL).
 */
export function resolveBackendMediaUrl(raw: string | null | undefined): string {
  const s = String(raw ?? '').trim()
  if (!s) return ''
  if (/^https?:\/\//i.test(s)) return s
  if (s.startsWith('//')) {
    if (typeof window !== 'undefined') return `${window.location.protocol}${s}`
    return `https:${s}`
  }
  const apiBase = String(axios.defaults.baseURL ?? '').replace(/\/$/, '')
  const originBase = apiBase.replace(/\/api$/i, '')
  const normalized = s.startsWith('/storage/') || s.startsWith('storage/')
    ? s
    : `storage/${s.replace(/^\/+/, '')}`
  const path = normalized.startsWith('/') ? normalized : `/${normalized}`
  if (!originBase) return path
  return `${originBase}${path}`
}
