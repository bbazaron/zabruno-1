<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import {
  catalogCropPanLimits,
  clampCatalogCrop,
  clampCatalogPanToLimits,
  clampCatalogZoom,
  computeCatalogCropImageStyle,
  type CatalogCrop,
} from '../../utils/catalogCrop'

const props = defineProps<{
  src: string
  crop: CatalogCrop
  interactive?: boolean
}>()

const emit = defineEmits<{
  'update:crop': [CatalogCrop]
}>()

const viewportRef = ref<HTMLElement | null>(null)
const naturalSize = ref({ w: 0, h: 0 })
const viewportSize = ref({ w: 0, h: 0 })
const dragging = ref(false)

type PointerPoint = { x: number; y: number }

const activePointers = new Map<number, PointerPoint>()
const dragOrigin = ref({ x: 0, y: 0, panX: 0, panY: 0 })
const pinchOrigin = ref<{
  distance: number
  zoom: number
  panX: number
  panY: number
  centerX: number
  centerY: number
} | null>(null)

let resizeObserver: ResizeObserver | null = null

const imageStyle = computed(() =>
  computeCatalogCropImageStyle(
    props.crop,
    viewportSize.value.w,
    viewportSize.value.h,
    naturalSize.value.w,
    naturalSize.value.h,
  ),
)

const isPinching = computed(() => activePointers.size >= 2)

function measureViewport() {
  const el = viewportRef.value
  if (!el) return
  viewportSize.value = { w: el.clientWidth, h: el.clientHeight }
}

function onImageLoad(event: Event) {
  const img = event.target as HTMLImageElement
  naturalSize.value = { w: img.naturalWidth, h: img.naturalHeight }
  measureViewport()
}

function pointerDistance(a: PointerPoint, b: PointerPoint): number {
  return Math.hypot(b.x - a.x, b.y - a.y)
}

function pointerCenter(a: PointerPoint, b: PointerPoint): PointerPoint {
  return { x: (a.x + b.x) / 2, y: (a.y + b.y) / 2 }
}

function publishCrop(next: CatalogCrop) {
  const clamped = clampCatalogCrop(next)
  const limits = catalogCropPanLimits(
    clamped,
    viewportSize.value.w,
    viewportSize.value.h,
    naturalSize.value.w,
    naturalSize.value.h,
  )
  emit('update:crop', {
    catalog_zoom: clamped.catalog_zoom,
    catalog_pan_x: clampCatalogPanToLimits(clamped.catalog_pan_x, limits.minX, limits.maxX),
    catalog_pan_y: clampCatalogPanToLimits(clamped.catalog_pan_y, limits.minY, limits.maxY),
  })
}

function beginSinglePointerDrag(point: PointerPoint) {
  dragging.value = true
  pinchOrigin.value = null
  dragOrigin.value = {
    x: point.x,
    y: point.y,
    panX: props.crop.catalog_pan_x,
    panY: props.crop.catalog_pan_y,
  }
}

function beginPinchGesture() {
  if (activePointers.size < 2) return
  const points = [...activePointers.values()]
  const first = points[0]
  const second = points[1]
  if (!first || !second) return

  dragging.value = false
  const center = pointerCenter(first, second)
  pinchOrigin.value = {
    distance: Math.max(pointerDistance(first, second), 1),
    zoom: props.crop.catalog_zoom,
    panX: props.crop.catalog_pan_x,
    panY: props.crop.catalog_pan_y,
    centerX: center.x,
    centerY: center.y,
  }
}

function onPointerDown(event: PointerEvent) {
  if (!props.interactive || !viewportRef.value) return
  event.preventDefault()

  activePointers.set(event.pointerId, { x: event.clientX, y: event.clientY })
  viewportRef.value.setPointerCapture(event.pointerId)

  if (activePointers.size >= 2) {
    beginPinchGesture()
    return
  }

  beginSinglePointerDrag({ x: event.clientX, y: event.clientY })
}

function onPointerMove(event: PointerEvent) {
  if (!props.interactive || !viewportRef.value) return
  if (!activePointers.has(event.pointerId)) return

  activePointers.set(event.pointerId, { x: event.clientX, y: event.clientY })
  const rect = viewportRef.value.getBoundingClientRect()
  if (rect.width <= 0 || rect.height <= 0) return

  if (activePointers.size >= 2) {
    if (!pinchOrigin.value) {
      beginPinchGesture()
    }
    const origin = pinchOrigin.value
    if (!origin) return

    const points = [...activePointers.values()]
    const first = points[0]
    const second = points[1]
    if (!first || !second) return

    const distance = Math.max(pointerDistance(first, second), 1)
    const center = pointerCenter(first, second)
    const zoomRatio = distance / origin.distance
    const deltaPanX = ((center.x - origin.centerX) / rect.width) * 100
    const deltaPanY = ((center.y - origin.centerY) / rect.height) * 100

    publishCrop({
      catalog_zoom: clampCatalogZoom(Math.round(origin.zoom * zoomRatio)),
      catalog_pan_x: origin.panX + deltaPanX,
      catalog_pan_y: origin.panY + deltaPanY,
    })
    return
  }

  if (!dragging.value) return

  const deltaPanX = ((event.clientX - dragOrigin.value.x) / rect.width) * 100
  const deltaPanY = ((event.clientY - dragOrigin.value.y) / rect.height) * 100

  publishCrop({
    ...props.crop,
    catalog_pan_x: dragOrigin.value.panX + deltaPanX,
    catalog_pan_y: dragOrigin.value.panY + deltaPanY,
  })
}

function onPointerUp(event: PointerEvent) {
  if (!activePointers.has(event.pointerId)) return

  activePointers.delete(event.pointerId)
  viewportRef.value?.releasePointerCapture(event.pointerId)

  if (activePointers.size >= 2) {
    beginPinchGesture()
    return
  }

  pinchOrigin.value = null

  if (activePointers.size === 1) {
    const point = [...activePointers.values()][0]
    if (point) {
      beginSinglePointerDrag(point)
    }
    return
  }

  dragging.value = false
}

function onWheel(event: WheelEvent) {
  if (!props.interactive) return
  event.preventDefault()
  const step = event.deltaY > 0 ? -4 : 4
  publishCrop({
    ...props.crop,
    catalog_zoom: clampCatalogZoom(props.crop.catalog_zoom + step),
  })
}

watch(
  () => props.src,
  () => {
    naturalSize.value = { w: 0, h: 0 }
  },
)

onMounted(() => {
  measureViewport()
  if (typeof ResizeObserver !== 'undefined' && viewportRef.value) {
    resizeObserver = new ResizeObserver(() => measureViewport())
    resizeObserver.observe(viewportRef.value)
  }
})

onBeforeUnmount(() => {
  resizeObserver?.disconnect()
  activePointers.clear()
})
</script>

<template>
  <div
    ref="viewportRef"
    class="relative overflow-hidden bg-neutral-100"
    :class="[
      interactive ? 'cursor-grab touch-none' : '',
      dragging || isPinching ? 'cursor-grabbing' : '',
    ]"
    @pointerdown.prevent="onPointerDown"
    @pointermove.prevent="onPointerMove"
    @pointerup="onPointerUp"
    @pointercancel="onPointerUp"
    @wheel.prevent="onWheel"
  >
    <img :src="src" alt="" draggable="false" :style="imageStyle" @load="onImageLoad" />
    <slot />
  </div>
</template>
