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

export function serializeSchoolColorOptions(options: string[]): string | null {
  const normalized = normalizeSchoolColorOptions(options)
  if (normalized.length === 0) return null
  return normalized.join(SCHOOL_COLOR_OPTIONS_DELIMITER)
}
