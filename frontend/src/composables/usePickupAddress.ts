import { ref } from 'vue'
import axios from 'axios'

export const DEFAULT_PICKUP_ADDRESS = 'пгт. Агинское, ул. Цыбикова 6в, магазин Руно'

const defaultPickupAddress = ref(DEFAULT_PICKUP_ADDRESS)
let loadPromise: Promise<string> | null = null

export function usePickupAddress() {
  async function loadDefaultPickupAddress(force = false): Promise<string> {
    if (!force && loadPromise) {
      return loadPromise
    }

    loadPromise = (async () => {
      try {
        const res = await axios.get<{ pickup_address?: string }>('/api/settings/pickup-address', {
          headers: { Accept: 'application/json' },
        })
        const value = String(res.data?.pickup_address ?? '').trim()
        defaultPickupAddress.value = value || DEFAULT_PICKUP_ADDRESS
      } catch {
        defaultPickupAddress.value = DEFAULT_PICKUP_ADDRESS
      }
      return defaultPickupAddress.value
    })()

    return loadPromise
  }

  function pickupLabelForOrder(order?: { pickup_address?: string | null } | null): string {
    const stored = String(order?.pickup_address ?? '').trim()
    if (stored) return stored
    return defaultPickupAddress.value
  }

  return {
    defaultPickupAddress,
    loadDefaultPickupAddress,
    pickupLabelForOrder,
  }
}
