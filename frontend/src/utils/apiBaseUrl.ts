/**
 * База для axios: VITE_API_URL при сборке; если пусто — window.__VITE_API_URL__
 * (удобно поправить на сервере без пересборки).
 */
export function getApiBaseUrl(): string {
  const fromEnv = (import.meta.env.VITE_API_URL ?? '').trim()
  if (fromEnv) return stripTrailingSlash(fromEnv)

  if (typeof window !== 'undefined') {
    const w = (window as unknown as { __VITE_API_URL__?: string }).__VITE_API_URL__
    if (typeof w === 'string' && w.trim()) return stripTrailingSlash(w.trim())
  }

  return ''
}

function stripTrailingSlash(url: string): string {
  return url.replace(/\/+$/, '')
}
