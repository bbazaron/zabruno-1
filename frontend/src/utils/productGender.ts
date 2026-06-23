export type ProductCatalogGender = 'boys' | 'girls' | 'all'
export type SelectedItemGender = 'boy' | 'girl'

export function productGenderRequiresChoice(gender: string | null | undefined): boolean {
  return String(gender ?? '').toLowerCase() === 'all'
}

export function selectedGenderFromProduct(gender: string | null | undefined): SelectedItemGender | null {
  const value = String(gender ?? '').toLowerCase()
  if (value === 'boys') return 'boy'
  if (value === 'girls') return 'girl'
  return null
}

export function normalizeSelectedGender(value: string | null | undefined): SelectedItemGender | null {
  const g = String(value ?? '').toLowerCase()
  if (g === 'boy' || g === 'boys') return 'boy'
  if (g === 'girl' || g === 'girls') return 'girl'
  return null
}

export function selectedGenderLabel(value: string | null | undefined): string {
  const g = String(value ?? '').toLowerCase()
  if (g === 'boy' || g === 'boys') return 'Мальчик'
  if (g === 'girl' || g === 'girls') return 'Девочка'
  return ''
}

export function itemGenderLabel(
  itemGender: string | null | undefined,
  fallbackGender?: string | null,
): string {
  return selectedGenderLabel(itemGender) || selectedGenderLabel(fallbackGender)
}

export function resolveCartItemGenderLabel(
  selectedGender: string | null | undefined,
  productGender: string | null | undefined,
): string {
  return (
    selectedGenderLabel(selectedGender) ||
    selectedGenderLabel(selectedGenderFromProduct(productGender))
  )
}
