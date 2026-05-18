export const DEFAULT_PRODUCT_SIZES = ['28', '30', '32', '34', '36', '38', '40', '42', '44'] as const

export function defaultProductSizes(): string[] {
  return [...DEFAULT_PRODUCT_SIZES]
}

export function parseProductSizes(raw: unknown): string[] {
  let parsed: string[] = []

  if (Array.isArray(raw)) {
    parsed = normalizeProductSizes(raw.map((item) => String(item ?? '')))
  } else if (typeof raw === 'string') {
    const trimmed = raw.trim()
    if (trimmed) {
      try {
        const json = JSON.parse(trimmed) as unknown
        if (Array.isArray(json)) {
          parsed = normalizeProductSizes(json.map((item) => String(item ?? '')))
        }
      } catch {
        // not JSON — split by delimiters below
      }
      if (parsed.length === 0) {
        parsed = normalizeProductSizes(trimmed.split(/[,;|\n]/g))
      }
    }
  }

  return parsed.length > 0 ? parsed : defaultProductSizes()
}

export function normalizeProductSizes(raw: string[]): string[] {
  const out: string[] = []
  for (const item of raw) {
    const normalized = normalizeProductSize(item)
    if (!normalized || out.includes(normalized)) continue
    out.push(normalized)
  }
  return out.sort(compareProductSizes)
}

export function normalizeProductSize(value: string): string | null {
  const raw = value.trim().replace(',', '.')
  if (!raw || !/^\d+(\.\d+)?$/.test(raw)) return null
  const number = Number(raw)
  if (!Number.isFinite(number) || number <= 0 || number > 999) return null
  if (Number.isInteger(number)) return String(number)
  return String(number).replace(/\.?0+$/, '')
}

export function compareProductSizes(a: string, b: string): number {
  return Number(a) - Number(b)
}

export function isProductSizeAllowed(size: string, allowed: string[]): boolean {
  const normalized = normalizeProductSize(size)
  if (!normalized) return false
  return allowed.includes(normalized)
}

export function defaultSelectedSize(sizes: string[], previous?: string): string {
  if (sizes.length === 0) return ''
  if (previous && isProductSizeAllowed(previous, sizes)) return normalizeProductSize(previous) ?? sizes[0]
  const middle = sizes[Math.floor(sizes.length / 2)]
  return middle ?? sizes[0]
}
