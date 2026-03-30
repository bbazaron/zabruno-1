<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  variant?: 'default' | 'outline'
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'default',
})

const cardClasses = computed(() => {
  const base = 'rounded-lg overflow-hidden'
  const variants = {
    default: 'bg-white shadow-md',
    outline: 'border border-neutral-200',
  }
  return `${base} ${variants[props.variant]}`
})
</script>

<template>
  <div :class="cardClasses">
    <slot />
  </div>
</template>
