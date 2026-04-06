import { shallowRef } from 'vue'
import axios from 'axios'
import { resolveBackendMediaUrl } from '../utils/resolveBackendMediaUrl'

export interface ProductRow {
  id: number
  name: string
  gender: string
  image?: string | null
}

const products = shallowRef<ProductRow[]>([])
let loadPromise: Promise<void> | null = null

export function useProductLinkResolver() {
  async function loadProducts(): Promise<void> {
    if (loadPromise) return loadPromise
    loadPromise = (async () => {
      try {
        const { data } = await axios.get('/api/index', {
          headers: { Accept: 'application/json' },
        })
        products.value = Array.isArray(data) ? data : []
      } catch {
        products.value = []
      }
    })()
    return loadPromise
  }

  /**
   * Подбор id товара по точному совпадению названия; при нескольких вариантах по полу — по child_gender заказа.
   */
  function resolveProductId(productName: string, orderGender?: string | null): number | null {
    const name = productName.trim()
    if (!name || products.value.length === 0) return null

    const candidates = products.value.filter((p) => String(p.name).trim() === name)
    if (candidates.length === 0) return null
    if (candidates.length === 1) return candidates[0].id

    const g = String(orderGender ?? '').toLowerCase()
    const genderKey =
      g === 'boy' || g === 'boys' ? 'boys' : g === 'girl' || g === 'girls' ? 'girls' : null
    if (genderKey) {
      const hit = candidates.find((p) => p.gender === genderKey)
      if (hit) return hit.id
    }
    return candidates[0].id
  }

  function resolveProductImage(productName: string, orderGender?: string | null): string | null {
    void products.value.length
    const id = resolveProductId(productName, orderGender)
    if (id == null) return null
    const p = products.value.find((row) => row.id === id)
    const img = p?.image
    if (typeof img !== 'string' || !img.trim()) return null
    return resolveBackendMediaUrl(img.trim())
  }

  return { products, loadProducts, resolveProductId, resolveProductImage }
}
