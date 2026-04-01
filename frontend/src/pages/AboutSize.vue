<script setup lang="ts">
import Header from '../components/sections/Header.vue'
import Footer from '../components/sections/Footer.vue'
import Typography from '../components/ui/Typography.vue'
</script>

<template>
  <div class="w-full">
    <Header />

    <main class="min-h-screen">
      <!-- Заголовок -->
      <section class="bg-white border-b border-neutral-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
          <div class="mb-4 text-sm text-slate-600">
            <a href="/" class="hover:text-slate-900">Главная</a>
            <span class="mx-2">/</span>
            <span class="text-slate-900 font-medium">О размере</span>
          </div>
          <Typography as="h1" variant="h2" class="text-slate-900 mb-2 max-w-4xl">
            Как правильно снять мерки за 1 минуту.
          </Typography>
        </div>
      </section>

      <!-- Слева шаги, справа фигура -->
      <section class="bg-neutral-50 py-8 lg:py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12 items-start">
            <div class="space-y-8">
              <div>
                <Typography as="h2" variant="h3" class="text-slate-900 mb-3">
                  Как снять мерки
                </Typography>
                <Typography as="p" variant="body" class="text-slate-700">
                  <span class="font-medium text-slate-900">Понадобится:</span>
                  сантиметровая лента и 1 минута времени.
                </Typography>
              </div>

              <div class="space-y-6">
                <div>
                  <Typography as="h3" variant="h4" class="text-slate-900 mb-2">Рост</Typography>
                  <ul class="list-none space-y-1 text-base md:text-lg leading-relaxed text-slate-700">
                    <li>Встаньте босиком у стены, спиной прямо.</li>
                    <li>Пятки, спина и затылок касаются стены.</li>
                    <li>Отметьте верхнюю точку головы и измерьте расстояние до пола.</li>
                  </ul>
                </div>

                <div>
                  <Typography as="h3" variant="h4" class="text-slate-900 mb-2">Обхват груди</Typography>
                  <ul class="list-none space-y-1 text-base md:text-lg leading-relaxed text-slate-700">
                    <li>Измерьте по самой широкой части груди.</li>
                    <li>Лента проходит ровно, не стягивая тело.</li>
                  </ul>
                </div>

                <div>
                  <Typography as="h3" variant="h4" class="text-slate-900 mb-2">Талия</Typography>
                  <ul class="list-none space-y-1 text-base md:text-lg leading-relaxed text-slate-700">
                    <li>Измерьте самую узкую часть талии.</li>
                    <li>Не втягивайте живот, стойте расслабленно.</li>
                  </ul>
                </div>

                <div>
                  <Typography as="h3" variant="h4" class="text-slate-900 mb-2">Бедра</Typography>
                  <ul class="list-none space-y-1 text-base md:text-lg leading-relaxed text-slate-700">
                    <li>Измерьте по самой широкой части бёдер.</li>
                    <li>Лента должна быть параллельна полу.</li>
                  </ul>
                </div>
              </div>
            </div>

            <div class="lg:sticky lg:top-24">
              <div class="rounded-lg border border-neutral-200 bg-white p-4 shadow-sm overflow-hidden">
                <img
                  src="/images/AboutSize/SizeFigure.png"
                  alt="Схема: как снять мерки по фигуре"
                  class="w-full h-auto object-contain"
                  loading="lazy"
                />
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Таблица размеров -->
      <section class="bg-white border-t border-neutral-200 py-8 lg:py-12">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
          <Typography as="h2" variant="h3" class="text-slate-900">
            Таблица размеров
          </Typography>

          <div class="space-y-4 text-slate-700">
            <Typography as="p" variant="body">
              Наша таблица размеров поможет вам подобрать идеальную посадку по вашим меркам.
            </Typography>
            <ul class="list-disc pl-5 space-y-2 text-base md:text-lg leading-relaxed">
              <li>Сравните свои параметры с данными в таблице.</li>
              <li>Если ваши значения находятся между размерами — рекомендуем выбрать больший.</li>
              <li>При сомнениях ориентируйтесь на обхват груди как основной параметр.</li>
            </ul>
            <Typography as="p" variant="body" class="text-slate-700">
              <span aria-hidden="true">💡</span>
              Если вы не уверены в выборе —
              <a href="/contacts" class="text-slate-900 font-medium underline underline-offset-2 hover:text-slate-700">
                напишите нам
              </a>
              , и мы поможем подобрать размер.
            </Typography>
          </div>

          <div class="rounded-lg border border-neutral-200 bg-neutral-50 p-4 overflow-x-auto">
            <img
              src="/images/AboutSize/SizeTable.png"
              alt="Таблица размеров"
              class="w-full min-w-[280px] h-auto object-contain mx-auto"
              loading="lazy"
            />
          </div>
        </div>
      </section>
    </main>

    <Footer />
  </div>
</template>
