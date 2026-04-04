<script setup lang="ts">
import { ref } from 'vue'
import { RouterLink } from 'vue-router'
import { ChevronDown } from 'lucide-vue-next'

export interface AccordionItem {
  id: string
  title: string
  content: string
  link?: { to: string; label: string }
}

defineProps<{
  items: AccordionItem[]
}>()

const openId = ref<string | null>(null)

function toggle(id: string) {
  openId.value = openId.value === id ? null : id
}
</script>

<template>
  <div class="rounded-lg border border-neutral-200 bg-white divide-y divide-neutral-200 overflow-hidden">
    <div v-for="item in items" :key="item.id">
      <button
        type="button"
        class="w-full flex items-start justify-between gap-4 px-5 py-4 md:py-5 text-left text-lg md:text-xl font-medium tracking-tight text-slate-900 hover:bg-neutral-50 transition-colors"
        :aria-expanded="openId === item.id"
        :aria-controls="`accordion-panel-${item.id}`"
        :id="`accordion-trigger-${item.id}`"
        @click="toggle(item.id)"
      >
        <span class="flex-1">{{ item.title }}</span>
        <ChevronDown
          class="shrink-0 text-slate-500 transition-transform duration-200 mt-0.5"
          :class="openId === item.id ? 'rotate-180' : ''"
          :size="22"
          aria-hidden="true"
        />
      </button>
      <div
        v-show="openId === item.id"
        :id="`accordion-panel-${item.id}`"
        role="region"
        :aria-labelledby="`accordion-trigger-${item.id}`"
        class="px-5 pb-5 pt-0 text-slate-700 text-base leading-relaxed"
      >
        <p>
          {{ item.content }}
          <template v-if="item.link">
            <RouterLink
              :to="item.link.to"
              class="text-slate-900 font-medium underline underline-offset-2 hover:text-slate-700"
            >
              {{ item.link.label }}
            </RouterLink>
            .
          </template>
        </p>
      </div>
    </div>
  </div>
</template>
