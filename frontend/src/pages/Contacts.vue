<script setup lang="ts">
import Header from '../components/sections/Header.vue'
import Footer from '../components/sections/Footer.vue'
import Typography from '../components/ui/Typography.vue'

const phoneDisplay = '+7 (302) XXX-XX-XX'
const phoneTel = '+73020000000'
const email = 'info@shkola-forma.ru'
const address = 'Чита, Забайкальский край'

/** Карта OpenStreetMap*/
const mapEmbedUrl =
  'https://www.openstreetmap.org/export/embed.html?bbox=113.45%2C52.01%2C113.55%2C52.06&layer=mapnik&marker=52.034%2C113.499'
const mapOpenUrl = 'https://www.openstreetmap.org/?mlat=52.034&mlon=113.499#map=14/52.034/113.499'
</script>

<template>
  <div class="w-full">
    <Header />

    <main class="min-h-screen">
      <section class="bg-white border-b border-neutral-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
          <div class="mb-4 text-sm text-slate-600">
            <a href="/" class="hover:text-slate-900">Главная</a>
            <span class="mx-2">/</span>
            <span class="text-slate-900 font-medium">Контакты</span>
          </div>
          <Typography as="h1" variant="h2" class="text-slate-900 mb-2">
            Контактная информация
          </Typography>
          <Typography as="p" variant="body" class="text-slate-600">
            Свяжитесь с нами по телефону, почте или приезжайте по адресу
          </Typography>
        </div>
      </section>

      <section class="bg-neutral-50 py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-10 items-start">
            <!-- Левая колонка: контакты и реквизиты -->
            <div class="space-y-6">
              <div class="rounded-lg border border-neutral-200 bg-white p-6 md:p-8 space-y-6">
                <div>
                  <label class="block text-sm font-medium text-slate-700 mb-1">Телефон</label>
                  <a
                    :href="`tel:${phoneTel}`"
                    class="inline-block text-2xl md:text-3xl font-semibold tracking-tight text-slate-900 hover:text-slate-700 underline-offset-4 hover:underline"
                  >
                    {{ phoneDisplay }}
                  </a>
                </div>

                <div>
                  <label class="block text-sm font-medium text-slate-700 mb-1">Электронная почта</label>
                  <a
                    :href="`mailto:${email}`"
                    class="text-base md:text-lg leading-relaxed text-slate-900 break-all hover:text-slate-700 underline-offset-2 hover:underline"
                  >
                    {{ email }}
                  </a>
                </div>

                <div>
                  <label class="block text-sm font-medium text-slate-700 mb-1">Адрес</label>
                  <Typography as="p" variant="body" class="text-slate-900">
                    {{ address }}
                  </Typography>
                </div>
              </div>

              <div class="rounded-lg border border-neutral-200 bg-white p-6 md:p-8">
                <Typography as="h2" variant="h4" class="text-slate-900 mb-4">
                  Реквизиты
                </Typography>
                <div class="space-y-2 text-slate-700">
                  <Typography as="p" variant="body">
                    <span class="font-medium text-slate-900">ИНН:</span>
                  </Typography>
                  <Typography as="p" variant="body">
                    <span class="font-medium text-slate-900">ОГРНИП:</span>
                  </Typography>
                </div>
              </div>
            </div>

            <!-- Правая колонка: карта -->
            <div class="rounded-lg border border-neutral-200 bg-white overflow-hidden shadow-sm flex flex-col">
              <div
                class="relative w-full min-h-[280px] aspect-[4/3] lg:aspect-auto lg:min-h-[500px] lg:h-[500px]"
              >
                <iframe
                  title="Карта: адрес в Чите"
                  class="absolute inset-0 h-full w-full border-0"
                  loading="lazy"
                  referrerpolicy="no-referrer-when-downgrade"
                  :src="mapEmbedUrl"
                />
              </div>
              <div class="px-4 py-3 border-t border-neutral-200 bg-neutral-50">
                <a
                  :href="mapOpenUrl"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="text-sm text-slate-600 hover:text-slate-900 underline"
                >
                  Открыть карту на весь экран
                </a>
              </div>
            </div>
          </div>
        </div>
      </section>

    </main>

    <Footer />
  </div>
</template>
