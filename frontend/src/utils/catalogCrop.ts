export interface CatalogCrop {
  catalog_zoom: number
  catalog_pan_x: number
  catalog_pan_y: number
}

export const DEFAULT_CATALOG_CROP: CatalogCrop = {
  catalog_zoom: 100,
  catalog_pan_x: 0,
  catalog_pan_y: 0,
}

export function clampCatalogZoom(value: number | null | undefined): number {
  const n = Number(value)
  if (!Number.isFinite(n)) return DEFAULT_CATALOG_CROP.catalog_zoom
  return Math.min(250, Math.max(100, Math.round(n)))
}

export function clampCatalogPan(value: number | null | undefined): number {
  const n = Number(value)
  if (!Number.isFinite(n)) return 0
  return Math.min(100, Math.max(-100, Math.round(n)))
}

export function clampCatalogCrop(crop: Partial<CatalogCrop> | null | undefined): CatalogCrop {
  return {
    catalog_zoom: clampCatalogZoom(crop?.catalog_zoom),
    catalog_pan_x: clampCatalogPan(crop?.catalog_pan_x),
    catalog_pan_y: clampCatalogPan(crop?.catalog_pan_y),
  }
}

/** Стили изображения внутри фиксированной рамки каталога (как после кадрирования). */
export function catalogCropImageStyle(crop: CatalogCrop | null | undefined): Record<string, string> {
  const { catalog_zoom, catalog_pan_x, catalog_pan_y } = clampCatalogCrop(crop)
  const scale = catalog_zoom / 100

  return {
    position: 'absolute',
    left: '50%',
    top: '50%',
    width: '100%',
    height: '100%',
    maxWidth: 'none',
    objectFit: 'cover',
    transform: `translate(calc(-50% + ${catalog_pan_x}%), calc(-50% + ${catalog_pan_y}%)) scale(${scale})`,
    transformOrigin: 'center center',
    userSelect: 'none',
    pointerEvents: 'none',
  }
}
