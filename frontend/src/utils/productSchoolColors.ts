/** One option per line — commas inside entries (e.g. "Школа №2, бордовый") stay intact. */
export const SCHOOL_COLOR_OPTIONS_DELIMITER = '\n'

/** Value for the "other" option in product page select (not stored in DB). */
export const SCHOOL_COLOR_OTHER_VALUE = '__other__'
export const SCHOOL_COLOR_OTHER_LABEL = 'Другое'

export function parseSchoolColorOptions(raw: unknown): string[] {
  if (Array.isArray(raw)) {
    return normalizeSchoolColorOptions(raw.map((item) => String(item ?? '')))
  }

  const value = String(raw ?? '').trim()
  if (!value) return []

  if (value.includes('\n') || value.includes('\r')) {
    return normalizeSchoolColorOptions(value.split(/\r?\n/))
  }

  try {
    const parsed = JSON.parse(value) as unknown
    if (Array.isArray(parsed)) {
      return normalizeSchoolColorOptions(parsed.map((item) => String(item ?? '')))
    }
  } catch {
    // one stored option (commas inside, e.g. "Школа №2, белый", must stay intact)
  }

  return normalizeSchoolColorOptions([value])
}

export function normalizeSchoolColorOptions(raw: string[]): string[] {
  const out: string[] = []
  for (const item of raw) {
    const trimmed = item.trim()
    if (!trimmed || out.includes(trimmed)) continue
    out.push(trimmed)
  }
  return out
}

/** Alphabetical order for display (Russian locale). */
export function sortSchoolColorOptions(options: string[]): string[] {
  return [...options].sort((a, b) => a.localeCompare(b, 'ru', { sensitivity: 'base' }))
}

export function serializeSchoolColorOptions(options: string[]): string | null {
  const normalized = normalizeSchoolColorOptions(options)
  if (normalized.length === 0) return null
  return normalized.join(SCHOOL_COLOR_OPTIONS_DELIMITER)
}

export function effectiveSchoolColors(
  defaults: string[],
  excluded: string[],
  extra: string[],
): string[] {
  const activeDefaults = defaults.filter((item) => !excluded.includes(item))
  return sortSchoolColorOptions(normalizeSchoolColorOptions([...activeDefaults, ...extra]))
}

export function productSchoolColorConfigFromLegacy(
  legacyColor: unknown,
  defaults: string[],
): { excluded: string[]; extra: string[] } {
  const effective = parseSchoolColorOptions(legacyColor)
  if (effective.length === 0) {
    return { excluded: [], extra: [] }
  }

  const excluded = defaults.filter((item) => !effective.includes(item))
  const extra = effective.filter((item) => !defaults.includes(item))

  return {
    excluded: normalizeSchoolColorOptions(excluded),
    extra: normalizeSchoolColorOptions(extra),
  }
}
