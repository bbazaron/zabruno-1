<script setup lang="ts">
import { computed, ref } from 'vue'
import Button from '../ui/Button.vue'
import {
  catalogCropImageStyle,
  clampCatalogCrop,
  DEFAULT_CATALOG_CROP,
  type CatalogCrop,
} from '../../utils/catalogCrop'

const props = defineProps<{
  imageUrl: string
  modelValue: CatalogCrop
}>()

const emit = defineEmits<{
  'update:modelValue': [CatalogCrop]
}>()

const viewportRef = ref<HTMLElement | null>(null)
const dragging = ref(false)
const dragOrigin = ref({ x: 0, y: 0, panX: 0, panY: 0 })

const crop = computed({
  get: () => clampCatalogCrop(props.modelValue),
  set: (value: CatalogCrop) => emit('update:modelValue', clampCatalogCrop(value)),
})

const imageStyle = computed(() => catalogCropImageStyle(crop.value))

function pointerSensitivity(): number {
  const zoom = crop.value.catalog_zoom / 100
  return 1 / Math.max(zoom, 1)
}

function onPointerDown(event: PointerEvent) {
  if (!viewportRef.value) return
  dragging.value = true
  dragOrigin.value = {
    x: event.clientX,
    y: event.clientY,
    panX: crop.value.catalog_pan_x,
    panY: crop.value.catalog_pan_y,
  }
  viewportRef.value.setPointerCapture(event.pointerId)
}

function onPointerMove(event: PointerEvent) {
  if (!dragging.value || !viewportRef.value) return
  const rect = viewportRef.value.getBoundingClientRect()
  if (rect.width <= 0 || rect.height <= 0) return

  const factor = pointerSensitivity()
  const deltaPanX = ((event.clientX - dragOrigin.value.x) / rect.width) * 100 * factor
  const deltaPanY = ((event.clientY - dragOrigin.value.y) / rect.height) * 100 * factor

  crop.value = {
    ...crop.value,
    catalog_pan_x: dragOrigin.value.panX + deltaPanX,
    catalog_pan_y: dragOrigin.value.panY + deltaPanY,
  }
}

function endDrag(event: PointerEvent) {
  if (!dragging.value) return
  dragging.value = false
  viewportRef.value?.releasePointerCapture(event.pointerId)
}

function onWheel(event: WheelEvent) {
  event.preventDefault()
  const step = event.deltaY > 0 ? -4 : 4
  crop.value = {
    ...crop.value,
    catalog_zoom: crop.value.catalog_zoom + step,
  }
}

function resetCrop() {
  crop.value = { ...DEFAULT_CATALOG_CROP }
}
</script>

<template>
  <div class="space-y-2">
    <p class="text-xs text-slate-500">
      Кадр как в каталоге: перетащите изображение, колёсико мыши — масштаб.
    </p>
    <div
      ref="viewportRef"
      class="relative h-64 w-full max-w-sm cursor-grab overflow-hidden rounded-md border-2 border-slate-300 bg-neutral-100 touch-none"
      :class="dragging ? 'cursor-grabbing ring-2 ring-slate-900' : ''"
      @pointerdown.prevent="onPointerDown"
      @pointermove="onPointerMove"
      @pointerup="endDrag"
      @pointercancel="endDrag"
      @pointerleave="endDrag"
      @wheel.prevent="onWheel"
    >
      <img :src="imageUrl" alt="" draggable="false" :style="imageStyle" />
      <div
        class="pointer-events-none absolute inset-0 border border-white/40 shadow-[inset_0_0_0_1px_rgba(0,0,0,0.08)]"
        aria-hidden="true"
      />
    </div>
    <div class="flex flex-wrap items-center gap-2 text-xs text-slate-600">
      <span>Масштаб: {{ crop.catalog_zoom }}%</span>
      <Button type="button" variant="outline" size="sm" @click="resetCrop">Сбросить кадр</Button>
    </div>
  </div>
</template>
