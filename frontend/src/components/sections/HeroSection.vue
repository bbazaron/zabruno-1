<script setup lang="ts">
import { useRouter } from 'vue-router'
import Card from '../ui/Card.vue'
import Button from '../ui/Button.vue'
import Typography from '../ui/Typography.vue'
import { Calendar, CheckCircle2, Leaf } from 'lucide-vue-next'

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
  <section class="relative w-full min-h-[100dvh] overflow-hidden">

    <!-- Фон: только object-contain + естественные пропорции; пустоты закрывает фоновый цвет (без растягивания) -->
    <div
      class="pointer-events-none absolute inset-0 z-0 min-h-[100dvh] overflow-hidden bg-[#e9e8e6]"
      aria-hidden="true"
    >
      <img
        src="/images/Home/background.png"
        alt=""
        decoding="async"
        fetchpriority="high"
        class="mx-auto block h-auto w-full max-w-[100rem] object-contain object-top"
      />
    </div>

    <!-- Content over image -->
    <div class="relative z-10 flex min-h-[100dvh] flex-col pt-12 md:min-h-screen md:pt-14 font-sans">

      <div class="flex flex-1 flex-col justify-between">
        <!-- Top Section with Title and CTAs -->
        <div class="max-w-7xl mx-auto w-full px-4 pt-2 sm:px-6 sm:pt-4 md:pt-6 lg:px-8">
          <div class="grid gap-6 md:grid-cols-2 md:gap-8 lg:gap-12">
            <!-- Left Content -->
            <div class="max-w-xl space-y-4 md:max-w-none">
              <div class="space-y-3 sm:space-y-4">
                <div class="space-y-1 sm:space-y-2">
                  <div
                    class="text-[1.85rem] leading-[1.08] font-light tracking-tight text-slate-900 min-[400px]:text-[2.05rem] sm:text-4xl md:text-5xl lg:text-6xl"
                    style="font-family: 'Playfair Display', serif;"
                  >
                    Школьная форма
                  </div>

                  <div
                    class="text-xl font-light uppercase leading-tight tracking-tight text-slate-900 sm:text-2xl md:text-3xl lg:text-4xl"
                  >
                    произведённая
                  </div>

                  <!-- Надпись-картинка: только max-width ИЛИ max-height через w-auto h-auto, иначе ломается соотношение сторон -->
                  <div class="mt-2 max-w-full sm:mt-3 md:mt-4 md:-ml-4 lg:-ml-6">
                    <img
                      src="/images/Home/v-zabaikalskom-krae.png"
                      alt="в Забайкальском крае"
                      decoding="async"
                      class="block h-auto w-auto max-w-[min(100%,17.5rem)] object-contain object-left sm:max-w-[min(100%,21rem)] md:max-w-[min(100%,26rem)] lg:max-w-[min(100%,30rem)] xl:max-w-[min(100%,34rem)]"
                    />
                  </div>
                </div>
                <Typography
                  as="p"
                  variant="body"
                  class="mt-3 max-w-md text-base leading-relaxed text-slate-600 sm:mt-4 sm:text-lg md:mt-5 lg:mt-6"
                >
                  Кардиганы, жилеты и юбки из натурального хлопка
                </Typography>
              </div>

              <!-- CTAs -->
              <div class="flex flex-col gap-3 pt-1 sm:flex-row sm:gap-4 sm:pt-2">
                <Button variant="primary" size="lg" type="button" class="w-full sm:w-auto" @click="goToOrderCheckout">
                  Оформить заказ →
                </Button>
                <Button variant="secondary" size="lg" as="link" href="/catalog" class="w-full sm:w-auto">
                  Смотреть коллекцию
                </Button>
              </div>
            </div>

            <!-- Spacer for right side -->
            <div></div>
          </div>
        </div>

        <!-- Feature Icons - Centered vertically between buttons and cards -->
        <div class="my-6 flex flex-wrap items-start gap-3 md:my-12">
          <div class="max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8">
            <div class="flex flex-wrap gap-x-4 gap-y-2 sm:gap-3">
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
                class=" overflow-hidden h-60 relative cursor-default"
            >
              <img
                  :src="card.image"
                  :alt="card.title"
                  class="absolute inset-0 w-full h-full object-cover"
              />
              <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent" />
              <div class="absolute inset-0 p-4 flex flex-col justify-between text-white">
                <div></div>
                <div class="flex items-end justify-between">
                  <h3 class="text-lg md:text-xl font-semibold tracking-tight text-white flex-1">
                    {{ card.title }}
                  </h3>
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
