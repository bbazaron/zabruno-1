<script setup lang="ts">
import { useRouter } from 'vue-router'
import Card from '../ui/Card.vue'
import Button from '../ui/Button.vue'
import Typography from '../ui/Typography.vue'
import { Calendar, CheckCircle2, Leaf, ArrowRight } from 'lucide-vue-next'

const router = useRouter()

function hasAuthToken(): boolean {
  return !!(localStorage.getItem('auth_token') || localStorage.getItem('token'))
}

function goToOrderCheckout() {
  if (hasAuthToken()) {
    router.push({ name: 'OrderCheckout' })
  } else {
    router.push({ path: '/login', query: { redirect: '/orderCheckout' } })
  }
}

const features = [
  { icon: Calendar, label: 'Выдача в августе' },
  { icon: CheckCircle2, label: 'Удобный предзаказ' },
  { icon: Leaf, label: 'Натуральный хлопок' },
]

const cards = [
  {
    title: 'Наше производство в Забайкальском крае',
    description: 'Качественное производство с соблюдением всех стандартов',
    image: "/images/Home/photo2.png",
  },
  {
    title: 'Качество и комфорт каждого изделия',
    description: 'Натуральные материалы для максимального комфорта',
    image: "/images/Home/photo1.png",
  },
]
</script>

<template>
  <section class="relative w-full">
    <!-- Background Image -->
    <div class="absolute inset-0 w-full h-full overflow-hidden">
      <img
          src="/images/Home/background.png"
          alt="School uniform showcase"
          class="w-full h-full object-contain object-top  scale-90"
          style="transform: translateZ(-50px) scale(1.15);"
      />
    </div>

    <!-- Content over image -->
    <div class="relative z-10 min-h-screen flex flex-col pt-12 md:pt-14 font-sans">

      <div class="flex-1 flex flex-col justify-between">
        <!-- Top Section with Title and CTAs -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full pt-4 md:pt-6">
          <div class="grid md:grid-cols-2 gap-8 lg:gap-12">
            <!-- Left Content -->
            <div class="space-y-4">
              <div class="space-y-4">
                <div class="space-y-2">
                  <div class="text-5xl md:text-6xl font-light tracking-tight text-slate-900 leading-[1.1]"
                       style="font-family: 'Playfair Display', serif;">
                    Школьная форма
                  </div>

                  <div class="text-3xl md:text-4xl font-light tracking-tight text-slate-900 uppercase leading-[1.1]">
                    произведённая
                  </div>

                  <div>
                    <img
                        src="/images/Home/v-zabaikalskom-krae.png"
                        alt="в Забайкальском крае"
                        class="w-auto h-70 md:h-74 object-contain mt-4 md:-mt-22 -ml-6 md:-ml-6"
                    />
                  </div>
                </div>
                <Typography as="p" variant="body" class="text-slate-600 text-lg leading-relaxed max-w-md -mt-10 md:-mt-22">
                  Кардиганы, жилеты и юбки из натурального хлопка
                </Typography>
              </div>

              <!-- CTAs -->
              <div class="flex flex-col sm:flex-row gap-4 mt-4">
                <Button variant="primary" size="lg" type="button" @click="goToOrderCheckout">
                  Оформить заказ →
                </Button>
                <Button variant="secondary" size="lg" as="link" href="/genderSelect">
                  Смотреть коллекцию
                </Button>
              </div>
            </div>

            <!-- Spacer for right side -->
            <div></div>
          </div>
        </div>

        <!-- Feature Icons - Centered vertically between buttons and cards -->
        <div class="flex gap-3 flex-wrap items-start my-12">
          <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full">
            <div class="flex gap-3 flex-wrap">
              <div
                  v-for="(feature, idx) in features"
                  :key="idx"
                  class="flex items-center gap-3 text-sm text-slate-700"
              >
                <component :is="feature.icon" :size="20" class="text-slate-900" />
                <span>{{ feature.label }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Cards Section Over Image -->
      <div class="relative z-20 py-4 md:py-2">
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
            <Card class="p-4 bg-stone-100 flex flex-col justify-center">
              <h3 class="text-lg md:text-xl font-medium tracking-tight text-slate-900 mb-4">
                Как оформить заказ?
              </h3>
              <div class="flex items-center justify-between relative">
                <div
                    v-for="(stepLabel, idx) in ['Ребёнок', 'Изделия', 'Размер', 'Выдача']"
                    :key="idx"
                    class="relative flex-1 text-center"
                >
                  <!-- Кружок с иконкой -->
                  <div class="flex items-center justify-center w-14 h-14 mx-auto mb-2 rounded-full bg-white border-2 border-slate-900 relative z-10">
                    <svg v-if="idx === 0" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" class="text-slate-900" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                      <circle cx="12" cy="7" r="3"/>
                      <path d="M6.5 19v-1.5A3.5 3.5 0 0 1 10 14h4a3.5 3.5 0 0 1 3.5 3.5V19"/>
                    </svg>
                    <svg v-else-if="idx === 1" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" class="text-slate-900" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M8 7l1.8-2h4.4L16 7"/>
                      <path d="M5 9h14l-1 10H6L5 9Z"/>
                      <path d="M9 11v-1a3 3 0 0 1 6 0v1"/>
                    </svg>
                    <svg v-else-if="idx === 2" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" class="text-slate-900" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M4.5 8h11l-1 10h-9l-1-10Z"/>
                      <path d="M6.8 8l1.5-2h3.6l1.5 2"/>
                      <path d="M17 5v14"/>
                      <path d="M17 8h2M17 11h1.3M17 14h2M17 17h1.3"/>
                    </svg>
                    <svg v-else width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" class="text-slate-900" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M4.5 9h6l-.8 9h-4.4L4.5 9Z"/>
                      <path d="M5.8 9V7.8A1.8 1.8 0 0 1 7.6 6h.8a1.8 1.8 0 0 1 1.8 1.8V9"/>
                      <path d="M13.5 8h6l-.8 10h-4.4l-.8-10Z"/>
                      <path d="M14.8 8V6.8A1.8 1.8 0 0 1 16.6 5h.8a1.8 1.8 0 0 1 1.8 1.8V8"/>
                    </svg>
                  </div>
                  <Typography as="p" variant="small" class="text-slate-900 text-sm font-semibold leading-none mb-1">
                    {{ idx + 1 }}
                  </Typography>
                  <Typography as="p" variant="small" class="text-slate-900 text-sm">
                    {{ stepLabel }}
                  </Typography>

                  <!-- Стрелка вправо (кроме последнего кружка) -->
                  <div
                      v-if="idx !== 3"
                      class="absolute top-7 right-0 translate-x-1/2 -translate-y-1/2 w-6 h-6 rounded-full bg-white border border-slate-300 shadow-sm flex items-center justify-center z-20"
                  >
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" class="text-slate-700" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M9 6l6 6-6 6"/>
                    </svg>
                  </div>
                </div>
              </div>
            </Card>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
