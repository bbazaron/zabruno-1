<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  as?: 'h1' | 'h2' | 'h3' | 'h4' | 'h5' | 'h6' | 'p' | 'span'
  variant?: 'h1' | 'h2' | 'h3' | 'h4' | 'body' | 'small'
}

const props = withDefaults(defineProps<Props>(), {
  as: 'p',
  variant: 'body',
})

const typographyClasses = computed(() => {
  const variants = {
    h1: 'text-5xl md:text-6xl font-bold tracking-tight',
    h2: 'text-3xl md:text-4xl font-bold tracking-tight',
    h3: 'text-2xl md:text-3xl font-semibold tracking-tight',
    h4: 'text-xl md:text-2xl font-semibold',
    body: 'text-base md:text-lg leading-relaxed',
    small: 'text-sm text-neutral-600',
  }
  return variants[props.variant]
})
</script>

<template>
  <component
    :is="props.as"
    :class="typographyClasses"
  >
    <slot />
  </component>
</template>
