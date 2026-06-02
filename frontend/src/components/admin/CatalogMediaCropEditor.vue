<script setup lang="ts">
import { computed } from 'vue'
import Button from '../ui/Button.vue'
import CatalogCropViewport from '../catalog/CatalogCropViewport.vue'
import {
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

const crop = computed({
  get: () => clampCatalogCrop(props.modelValue),
  set: (value: CatalogCrop) => emit('update:modelValue', clampCatalogCrop(value)),
})

function resetCrop() {
  crop.value = { ...DEFAULT_CATALOG_CROP }
}
</script>

<template>
  <div class="space-y-2">
    <p class="text-xs text-slate-500">
      Вся фотография видна при 100%. Увеличьте масштаб и сдвиньте кадр, если нужна обрезка.
      Перетаскивание — сдвиг; на телефоне — щипок для масштаба; на компьютере — колёсико мыши.
    </p>
    <CatalogCropViewport
      :src="imageUrl"
      :crop="crop"
      interactive
      class="h-64 w-full max-w-sm rounded-md border-2 border-slate-300"
      :class="crop.catalog_zoom > 100 ? 'ring-1 ring-slate-400' : ''"
      @update:crop="(value) => (crop = value)"
    >
      <div
        class="pointer-events-none absolute inset-0 border border-white/40 shadow-[inset_0_0_0_1px_rgba(0,0,0,0.08)]"
        aria-hidden="true"
      />
    </CatalogCropViewport>
    <div class="flex flex-wrap items-center gap-2 text-xs text-slate-600">
      <span>Масштаб: {{ crop.catalog_zoom }}%</span>
      <Button type="button" variant="outline" size="sm" @click="resetCrop">Сбросить кадр</Button>
    </div>
  </div>
</template>
