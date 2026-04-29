<script setup lang="ts">
import { RouterLink } from 'vue-router'
import Header from '../components/sections/Header.vue'
import Footer from '../components/sections/Footer.vue'
import Typography from '../components/ui/Typography.vue'
import Card from '../components/ui/Card.vue'
import {
  Factory,
  Shirt,
  School,
  Users,
  HeartHandshake,
  CheckCircle2,
} from 'lucide-vue-next'

const reasons = [
  'забота о комфорте ребёнка',
  'понятный и удобный процесс заказа',
  'проверенное качество',
  'локальное производство',
  'надёжность в сроках',
]
</script>

<template>
  <div class="w-full text-slate-900">
    <Header />

    <main class="min-h-screen">
      <section class="bg-white border-b border-neutral-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 md:py-8">
          <div class="text-sm text-slate-600">
            <RouterLink to="/" class="hover:text-slate-900 transition-colors">Главная</RouterLink>
            <span class="mx-2">/</span>
            <span class="text-slate-900 font-medium">О нас</span>
          </div>
        </div>
      </section>

      <section class="bg-white border-b border-neutral-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14 md:py-20 lg:py-24">
          <div class="max-w-3xl mx-auto text-center space-y-6 md:space-y-8">
            <h1 class="text-3xl sm:text-4xl md:text-5xl font-bold tracking-tight text-slate-900 leading-[1.1]">
              О нас
            </h1>
            <Typography as="p" variant="body" class="text-slate-600 text-lg leading-relaxed">
              Мы создаём школьную форму там, где сами живём и растим детей — в посёлке Агинское, в Забайкальском крае.
              Для нас важно, чтобы ребёнку было удобно, а родителям — спокойно.
            </Typography>
          </div>
        </div>
      </section>

      <section class="bg-neutral-50 py-14 md:py-20 border-t border-neutral-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div class="text-center max-w-2xl mx-auto mb-12 md:mb-16 space-y-3">
            <h2 class="text-2xl md:text-3xl font-bold tracking-tight text-slate-900 leading-[1.1]">
              Что мы делаем
            </h2>
            <Typography as="p" variant="body" class="text-slate-600 text-lg leading-relaxed">
              Производим школьную форму с вниманием к качеству, удобству и единому стилю.
            </Typography>
          </div>

          <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 md:gap-6">
            <Card variant="outline" class="p-6 md:p-8 bg-white">
              <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl bg-neutral-100 text-slate-900 flex items-center justify-center">
                  <Shirt :size="20" :stroke-width="1.8" />
                </div>
                <h2 class="text-xl font-semibold text-slate-900">Что мы делаем</h2>
              </div>
              <Typography as="p" variant="body" class="text-slate-700 leading-relaxed">
                Мы вяжем школьные жилеты, кардиганы и юбки — удобные, аккуратные и практичные вещи, которые дети носят каждый день.
              </Typography>
            </Card>

            <Card variant="outline" class="p-6 md:p-8 bg-white">
              <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl bg-neutral-100 text-slate-900 flex items-center justify-center">
                  <Factory :size="20" :stroke-width="1.8" />
                </div>
                <h2 class="text-xl font-semibold text-slate-900">Где и как производится</h2>
              </div>
              <Typography as="p" variant="body" class="text-slate-700 leading-relaxed">
                Вся форма производится на нашем предприятии. Мы внимательно следим за качеством на каждом этапе.
                Состав изделий — 50% хлопка и 50% акрила. Изделия мягкие к телу, не вытягиваются, легко стираются
                и долго сохраняют аккуратный вид.
              </Typography>
            </Card>

            <Card variant="outline" class="p-6 md:p-8 bg-white">
              <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl bg-neutral-100 text-slate-900 flex items-center justify-center">
                  <School :size="20" :stroke-width="1.8" />
                </div>
                <h2 class="text-xl font-semibold text-slate-900">Работа со школами</h2>
              </div>
              <Typography as="p" variant="body" class="text-slate-700 leading-relaxed">
                Каждая школа для нас — отдельная история. Мы учитываем её требования, создаём форму в нужных цветах,
                добавляем логотип. В итоге получается единый стиль, которым можно гордиться.
              </Typography>
            </Card>

            <Card variant="outline" class="p-6 md:p-8 bg-white">
              <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl bg-neutral-100 text-slate-900 flex items-center justify-center">
                  <Users :size="20" :stroke-width="1.8" />
                </div>
                <h2 class="text-xl font-semibold text-slate-900">Удобство для родителей</h2>
              </div>
              <Typography as="p" variant="body" class="text-slate-700 leading-relaxed">
                Мы заранее готовим образцы и размеры. Можно прийти в магазин «Руно», примерить и без спешки оформить заказ.
                В августе форма уже готова — без стресса и лишних поисков перед школой.
              </Typography>
            </Card>
          </div>
        </div>
      </section>

      <section class="bg-white py-14 md:py-20 border-t border-neutral-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <Card class="p-8 md:p-10 lg:p-12 max-w-4xl mx-auto border border-neutral-100 shadow-sm bg-gradient-to-br from-neutral-50 to-white">
            <div class="flex items-center gap-3 mb-6">
              <div class="w-11 h-11 rounded-2xl bg-slate-900 text-white flex items-center justify-center">
                <HeartHandshake :size="22" :stroke-width="1.8" />
              </div>
              <h2 class="text-2xl md:text-3xl font-bold tracking-tight text-slate-900 leading-[1.1]">
                Почему выбирают нас
              </h2>
            </div>
            <ul class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-slate-700 mb-6">
              <li
                v-for="reason in reasons"
                :key="reason"
                class="rounded-xl bg-white border border-neutral-200 px-4 py-3 flex items-start gap-2"
              >
                <CheckCircle2 :size="18" :stroke-width="2" class="mt-0.5 shrink-0 text-emerald-600" />
                <span>{{ reason }}</span>
              </li>
            </ul>
            <Typography as="p" variant="body" class="text-slate-800 text-lg leading-relaxed font-medium">
              Мы производим форму, которая выглядит аккуратно, носится долго и не создаёт лишних забот.
            </Typography>
          </Card>
        </div>
      </section>
    </main>

    <Footer />
  </div>
</template>
