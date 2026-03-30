<script setup lang="ts">
import { useToast } from '../../composables/useToast'

const { toasts } = useToast()
</script>

<template>
  <div class="fixed top-5 left-1/2 transform -translate-x-1/2 z-50 flex flex-col gap-2">
    <transition-group name="toast" tag="div">
      <div
          v-for="toast in toasts"
          :key="toast.id"
          class="px-4 py-3 rounded-md shadow text-sm text-white min-w-[200px] text-center"
          :class="{
          'bg-emerald-500': toast.type === 'success',
          'bg-red-500': toast.type === 'error',
          'bg-slate-800': toast.type === 'info',
        }"
      >
        {{ toast.message }}
      </div>
    </transition-group>
  </div>
</template>

<style>
/* Анимация появления и исчезновения */
.toast-enter-from {
  opacity: 0;
  transform: translateY(-10px);
}
.toast-enter-to {
  opacity: 1;
  transform: translateY(0);
}
.toast-leave-from {
  opacity: 1;
  transform: translateY(0);
}
.toast-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}
.toast-enter-active,
.toast-leave-active {
  transition: all 0.3s ease;
}
</style>