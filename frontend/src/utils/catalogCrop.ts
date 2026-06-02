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

export interface CatalogCropPanLimits {
  minX: number
  maxX: number
  minY: number
  maxY: number
}

/** Допустимый сдвиг (%) при текущем масштабе — чтобы не уводить кадр в пустоту. */
export function catalogCropPanLimits(
  crop: CatalogCrop,
  viewportW: number,
  viewportH: number,
  imageW: number,
  imageH: number,
): CatalogCropPanLimits {
  const { catalog_zoom } = clampCatalogCrop(crop)
  if (viewportW <= 0 || viewportH <= 0 || imageW <= 0 || imageH <= 0) {
    return { minX: 0, maxX: 0, minY: 0, maxY: 0 }
  }

  const fitScale = Math.min(viewportW / imageW, viewportH / imageH)
  const scale = fitScale * (catalog_zoom / 100)
  const dispW = imageW * scale
  const dispH = imageH * scale
  const overflowX = Math.max(0, dispW - viewportW)
  const overflowY = Math.max(0, dispH - viewportH)

  const maxPanX = overflowX > 0 ? (overflowX / 2 / viewportW) * 100 : 0
  const maxPanY = overflowY > 0 ? (overflowY / 2 / viewportH) * 100 : 0

  return {
    minX: -maxPanX,
    maxX: maxPanX,
    minY: -maxPanY,
    maxY: maxPanY,
  }
}

export function clampCatalogPanToLimits(
  pan: number,
  min: number,
  max: number,
): number {
  return Math.min(max, Math.max(min, clampCatalogPan(pan)))
}

/**
 * Стили изображения в рамке каталога.
 * При 100% — всё фото целиком (contain); при >100% — увеличение с возможностью сдвига.
 */
export function computeCatalogCropImageStyle(
  crop: CatalogCrop | null | undefined,
  viewportW: number,
  viewportH: number,
  imageW: number,
  imageH: number,
): Record<string, string> {
  const { catalog_zoom, catalog_pan_x, catalog_pan_y } = clampCatalogCrop(crop)

  if (viewportW <= 0 || viewportH <= 0 || imageW <= 0 || imageH <= 0) {
    return {
      position: 'absolute',
      left: '50%',
      top: '50%',
      width: '100%',
      height: '100%',
      objectFit: 'contain',
      transform: 'translate(-50%, -50%)',
      userSelect: 'none',
      pointerEvents: 'none',
    }
  }

  const limits = catalogCropPanLimits(
    { catalog_zoom, catalog_pan_x, catalog_pan_y },
    viewportW,
    viewportH,
    imageW,
    imageH,
  )
  const panX = clampCatalogPanToLimits(catalog_pan_x, limits.minX, limits.maxX)
  const panY = clampCatalogPanToLimits(catalog_pan_y, limits.minY, limits.maxY)
  const panPxX = (panX / 100) * viewportW
  const panPxY = (panY / 100) * viewportH

  const fitScale = Math.min(viewportW / imageW, viewportH / imageH)
  const scale = fitScale * (catalog_zoom / 100)
  const dispW = imageW * scale
  const dispH = imageH * scale

  return {
    position: 'absolute',
    left: '50%',
    top: '50%',
    width: `${dispW}px`,
    height: `${dispH}px`,
    maxWidth: 'none',
    maxHeight: 'none',
    objectFit: 'fill',
    transform: `translate(calc(-50% + ${panPxX}px), calc(-50% + ${panPxY}px))`,
    userSelect: 'none',
    pointerEvents: 'none',
  }
}

/** @deprecated используйте computeCatalogCropImageStyle с размерами */
export function catalogCropImageStyle(crop: CatalogCrop | null | undefined): Record<string, string> {
  return computeCatalogCropImageStyle(crop, 1, 1, 1, 1)
}
