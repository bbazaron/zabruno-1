<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  variant?: 'primary' | 'secondary' | 'outline'
  size?: 'sm' | 'md' | 'lg'
  disabled?: boolean
  type?: 'button' | 'submit' | 'reset'
  as?: string
  href?: string
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'primary',
  size: 'md',
  disabled: false,
  type: 'button',
  as: 'button',
})

const baseClasses =
  'inline-flex cursor-pointer items-center justify-center font-medium rounded-lg transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50'

const variants = {
  primary: 'bg-slate-900 text-white hover:bg-slate-800 focus-visible:ring-slate-900',
  secondary: 'border-2 border-slate-900 text-slate-900 hover:bg-slate-50 focus-visible:ring-slate-900',
  outline: 'border border-neutral-300 text-slate-900 hover:bg-neutral-100 focus-visible:ring-slate-900',
}

const sizes = {
  sm: 'px-3 py-1.5 text-sm',
  md: 'px-6 py-3 text-base',
  lg: 'px-8 py-4 text-lg',
}

const buttonClasses = computed(() => {
  return `${baseClasses} ${variants[props.variant]} ${sizes[props.size]}`
})
</script>

<template>
  <component
    :is="as === 'link' ? 'a' : props.as"
    :href="href"
    :type="as === 'button' ? type : undefined"
    :disabled="disabled"
    :class="buttonClasses"
  >
    <slot />
  </component>
</template>
