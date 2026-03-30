<script setup lang="ts">
import Card from '../ui/Card.vue'
import Typography from '../ui/Typography.vue'
import { ArrowRight } from 'lucide-vue-next'

const cards = [
  {
    title: 'Наше производство в Забайкальском крае',
    description: 'Качественное производство с соблюдением всех стандартов',
    image: 'https://picsum.photos/400/300?random=2',
  },
  {
    title: 'Качество и комфорт каждого изделия',
    description: 'Натуральные материалы для максимального комфорта',
    image: 'https://picsum.photos/400/300?random=3',
  },
]
</script>

<template>
  <section class="py-8 md:py-12 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="grid md:grid-cols-3 gap-4">
        <!-- Product Cards -->
        <Card
          v-for="(card, idx) in cards"
          :key="idx"
          class="group overflow-hidden h-60 relative cursor-pointer"
        >
          <img
            :src="card.image"
            :alt="card.title"
            class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
          />
          <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent" />
          <div class="absolute inset-0 p-4 flex flex-col justify-between text-white">
            <div></div>
            <div class="flex items-end justify-between">
              <h3 class="text-lg md:text-xl font-semibold tracking-tight text-white flex-1">
                {{ card.title }}
              </h3>
              <ArrowRight :size="20" class="text-white flex-shrink-0 ml-3" />
            </div>
          </div>
        </Card>

        <!-- Order Process Card -->
        <Card class="p-4 bg-stone-100">
          <h3 class="text-lg md:text-xl font-semibold tracking-tight text-slate-900 mb-4">
            Как оформить заказ?
          </h3>
          <div class="grid grid-cols-4 gap-2">
            <div v-for="step in 4" :key="step" class="text-center">
              <div class="flex items-center justify-center w-12 h-12 mx-auto mb-2 rounded-full bg-white border-2 border-slate-900">
                <span class="text-lg font-bold text-slate-900">{{ step }}</span>
              </div>
              <Typography as="p" variant="small" class="text-slate-900 text-xs">
                {{ ['Ребёнок', 'Изделия', 'Размер', 'Выдача'][step - 1] }}
              </Typography>
            </div>
          </div>
        </Card>
      </div>
    </div>
  </section>
</template>
