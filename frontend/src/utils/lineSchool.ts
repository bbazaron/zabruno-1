export interface LineSchoolFields {
  school_city?: string | null
  school_name?: string | null
  selected_color?: string | null
}

export function formatLineSchoolMeta(item: LineSchoolFields): string {
  const city = String(item.school_city ?? '').trim()
  const school = String(item.school_name ?? '').trim()
  const color = String(item.selected_color ?? '').trim()
  if (!city && !school) return color
  return [city, school, color].filter(Boolean).join(' · ')
}
