<script setup lang="ts">
import { computed } from 'vue'
import type { Component } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import Header from '../components/sections/Header.vue'
import Footer from '../components/sections/Footer.vue'
import Typography from '../components/ui/Typography.vue'
import Card from '../components/ui/Card.vue'
import Button from '../components/ui/Button.vue'
import Accordion from '../components/ui/Accordion.vue'
import type { AccordionItem } from '../components/ui/Accordion.vue'
import {
  LogIn,
  School,
  Ruler,
  ShoppingBag,
  Phone,
  ClipboardCheck,
  Headset,
  Factory,
  ShieldCheck,
} from 'lucide-vue-next'

const router = useRouter()

function getStoredToken(): string | null {
  return localStorage.getItem('auth_token') || localStorage.getItem('token')
}

function goToOrderCheckout() {
  if (getStoredToken()) {
    router.push({ name: 'OrderCheckout' })
  } else {
    router.push({ path: '/login', query: { redirect: '/orderCheckout' } })
  }
}

interface Step {
  icon: Component
  title: string
  text: string
}

const steps: Step[] = [
  {
    icon: LogIn,
    title: 'Вход или регистрация',
    text: 'Зайдите в личный кабинет или создайте аккаунт.',
  },
  {
    icon: School,
    title: 'Данные ребёнка',
    text: 'Укажите фамилию и имя, школу, класс, город и учебный год. Это поможет оформить заказ без путаницы.',
  },
  {
    icon: Ruler,
    title: 'Размер и мерки',
    text: 'Впишите рост и обхваты — грудь, талия, бёдра. Если сомневаетесь, откройте ',
  },
  {
    icon: ShoppingBag,
    title: 'Выбор комплекта',
    text: 'Выберите нужные вещи, размер и количество. В один заказ можно добавить несколько позиций.',
  },
  {
    icon: Phone,
    title: 'Данные родителя',
    text: 'Оставьте ваши фамилию, имя и телефон. По ним менеджер сможет уточнить детали, если что-то покажется неясным.',
  },
  {
    icon: ClipboardCheck,
    title: 'Проверка и оплата',
    text: 'Просмотрите сводку заказа, поставьте галочку согласия с условиями и перейдите к оплате. Готово — заказ принят.',
  },
]

const faqItems = computed<AccordionItem[]>(() => [
  {
    id: 'size-mistake',
    title: 'Что делать, если ошиблись в размере?',
    content:
      'Не переживайте: после оформления с вами свяжется менеджер. Если заказ ещё не ушёл в производство, мы поможем поправить мерки или размер. Чем раньше вы напишете или ответите на звонок, тем проще всё исправить.',
  },
  {
    id: 'change-order',
    title: 'Можно ли изменить заказ после отправки?',
    content:
      'Да, во многих случаях — пока заказ на проверке. Напишите нам или дождитесь звонка менеджера и скажите, что хотите поменять. Если изделие уже в работе, изменения могут быть ограничены — мы честно объясним варианты.',
  },
  {
    id: 'timing',
    title: 'Сколько ждать изготовление?',
    content:
      'Срок зависит от сезона и загрузки производства. Точные сроки вам назовёт менеджер после проверки заказа или уточните на странице контактов — мы подскажем актуальные ориентиры.',
  },
  {
    id: 'size-table',
    title: 'Как пользоваться таблицей размеров?',
    content:
      'Там собраны типичные мерки по размерам и короткая инструкция, как снять мерки дома. Перейдите в раздел ',
    link: { to: '/sizes', label: 'О размере' },
  },
])
</script>

<template>
  <div class="w-full text-slate-900">
    <Header />

    <main class="min-h-screen">
      <!-- Breadcrumb -->
      <section class="bg-white border-b border-neutral-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 md:py-8">
          <div class="text-sm text-slate-600">
            <RouterLink to="/" class="hover:text-slate-900 transition-colors">Главная</RouterLink>
            <span class="mx-2">/</span>
            <span class="text-slate-900 font-medium">Как заказать</span>
          </div>
        </div>
      </section>

      <!-- Hero -->
      <section class="relative bg-gradient-to-b from-neutral-50 to-white border-b border-neutral-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14 md:py-20 lg:py-24">
          <div class="max-w-3xl mx-auto text-center space-y-6 md:space-y-8">
            <h1
              class="text-3xl sm:text-4xl md:text-5xl font-bold tracking-tight text-slate-900 leading-[1.1]"
            >
              Как заказать школьную форму по индивидуальным меркам
            </h1>
            <Typography
              as="p"
              variant="body"
              class="text-slate-600 text-lg leading-relaxed max-w-2xl mx-auto"
            >
              Всего 5–10 минут — и форма будет идеально подходить вашему ребёнку
            </Typography>
            <div class="pt-2">
              <Button variant="primary" size="lg" class="min-w-[200px]" @click="goToOrderCheckout">
                Оформить заказ
              </Button>
            </div>
          </div>
        </div>
      </section>

      <!-- Как это работает -->
      <section class="bg-white py-14 md:py-20 lg:py-24">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div class="text-center max-w-2xl mx-auto mb-12 md:mb-16 space-y-3">
            <h2
              class="text-2xl md:text-3xl font-bold tracking-tight text-slate-900 leading-[1.1]"
            >
              Как это работает
            </h2>
            <Typography as="p" variant="body" class="text-slate-600 text-lg leading-relaxed">
              Шесть простых шагов — без сложных терминов. Можно идти по порядку прямо в форме заказа.
            </Typography>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 md:gap-8">
            <Card
              v-for="(step, index) in steps"
              :key="step.title"
              variant="outline"
              class="p-6 md:p-8 h-full flex flex-col gap-4 bg-white"
            >
              <div class="flex items-start gap-4">
                <div
                  class="shrink-0 w-12 h-12 rounded-2xl bg-neutral-100 text-slate-900 flex items-center justify-center"
                  aria-hidden="true"
                >
                  <component :is="step.icon" :size="22" :stroke-width="1.75" />
                </div>
                <span class="text-sm font-medium text-slate-400 pt-1 tabular-nums">{{ index + 1 }}</span>
              </div>
              <h3 class="text-lg md:text-xl font-medium tracking-tight text-slate-900">
                {{ step.title }}
              </h3>
              <Typography
                v-if="index === 2"
                as="p"
                variant="body"
                class="text-slate-700 flex-1 !text-base leading-relaxed"
              >
                {{ step.text }}
                <RouterLink
                  to="/sizes"
                  class="text-slate-900 font-medium underline underline-offset-2 hover:text-slate-700"
                >
                  таблицу размеров
                </RouterLink>.
              </Typography>
              <Typography
                v-else
                as="p"
                variant="body"
                class="text-slate-700 flex-1 !text-base leading-relaxed"
              >
                {{ step.text }}
              </Typography>
            </Card>
          </div>
        </div>
      </section>

      <!-- Что дальше -->
      <section class="bg-neutral-50 py-14 md:py-20 border-t border-neutral-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div class="max-w-2xl mx-auto text-center mb-10 md:mb-12 space-y-3">
            <h2
              class="text-2xl md:text-3xl font-bold tracking-tight text-slate-900 leading-[1.1]"
            >
              Что происходит дальше
            </h2>
            <Typography as="p" variant="body" class="text-slate-600 text-lg leading-relaxed">
              После оплаты заказ не «теряется» в системе — его проверяют люди, а не только программа.
            </Typography>
          </div>

          <div class="max-w-3xl mx-auto space-y-4 md:space-y-6">
            <Card variant="outline" class="p-6 md:p-7 flex gap-4 md:gap-5 items-start bg-white">
              <div class="shrink-0 w-11 h-11 rounded-xl bg-white border border-neutral-200 text-slate-900 flex items-center justify-center shadow-sm">
                <Headset :size="20" :stroke-width="1.75" aria-hidden="true" />
              </div>
              <div class="space-y-1">
                <h3 class="text-lg md:text-xl font-medium tracking-tight text-slate-900">
                  Менеджер проверяет заказ
                </h3>
                <Typography as="p" variant="body" class="text-slate-700 !text-base leading-relaxed">
                  Сверяет мерки, состав комплекта и контакты. Так мы заранее отлавливаем опечатки и несостыковки.
                </Typography>
              </div>
            </Card>
            <Card variant="outline" class="p-6 md:p-7 flex gap-4 md:gap-5 items-start bg-white">
              <div class="shrink-0 w-11 h-11 rounded-xl bg-white border border-neutral-200 text-slate-900 flex items-center justify-center shadow-sm">
                <Phone :size="20" :stroke-width="1.75" aria-hidden="true" />
              </div>
              <div class="space-y-1">
                <h3 class="text-lg md:text-xl font-medium tracking-tight text-slate-900">
                  При необходимости связываемся с вами
                </h3>
                <Typography as="p" variant="body" class="text-slate-700 !text-base leading-relaxed">
                  Звонок или сообщение — если нужно уточнить размер, количество или детали по школе.
                </Typography>
              </div>
            </Card>
            <Card variant="outline" class="p-6 md:p-7 flex gap-4 md:gap-5 items-start bg-white">
              <div class="shrink-0 w-11 h-11 rounded-xl bg-white border border-neutral-200 text-slate-900 flex items-center justify-center shadow-sm">
                <Factory :size="20" :stroke-width="1.75" aria-hidden="true" />
              </div>
              <div class="space-y-1">
                <h3 class="text-lg md:text-xl font-medium tracking-tight text-slate-900">
                  Передаём в производство
                </h3>
                <Typography as="p" variant="body" class="text-slate-700 !text-base leading-relaxed">
                  Когда всё согласовано, заказ уходит в работу. О сроках вы можете спросить у менеджера или в разделе контактов.
                </Typography>
              </div>
            </Card>
          </div>
        </div>
      </section>

      <!-- Доверие -->
      <section class="bg-white py-14 md:py-20 border-t border-neutral-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <Card class="p-8 md:p-10 lg:p-12 max-w-4xl mx-auto border border-neutral-100 shadow-sm bg-gradient-to-br from-neutral-50 to-white">
            <div class="flex flex-col md:flex-row md:items-start gap-6 md:gap-8">
              <div
                class="shrink-0 w-14 h-14 rounded-2xl bg-slate-900 text-white flex items-center justify-center mx-auto md:mx-0"
                aria-hidden="true"
              >
                <ShieldCheck :size="28" :stroke-width="1.5" />
              </div>
              <div class="space-y-4 text-center md:text-left">
                <h2
                  class="text-2xl md:text-3xl font-bold tracking-tight text-slate-900 leading-[1.1]"
                >
                  Мы проверяем каждый заказ вручную
                </h2>
                <Typography as="p" variant="body" class="text-slate-700 leading-relaxed">
                  Для нас важно, чтобы форма подошла ребёнку и чтобы вы чувствовали себя уверенно при заказе. Поэтому заявки не уходят «в пустоту»: их смотрит менеджер, при сомнениях — на связи. Вы можете задать вопрос в любой момент на странице
                  <RouterLink
                    to="/contacts"
                    class="text-slate-900 font-medium underline underline-offset-2 hover:text-slate-700"
                  >
                    Контакты
                  </RouterLink>
                  .
                </Typography>
              </div>
            </div>
          </Card>
        </div>
      </section>

      <!-- FAQ -->
      <section class="bg-neutral-50 py-14 md:py-20 border-t border-neutral-200">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
          <div class="text-center mb-10 md:mb-12 space-y-3">
            <h2
              class="text-2xl md:text-3xl font-bold tracking-tight text-slate-900 leading-[1.1]"
            >
              Частые вопросы
            </h2>
            <Typography as="p" variant="body" class="text-slate-600 text-lg leading-relaxed">
              Коротко отвечаем на то, что чаще всего спрашивают родители.
            </Typography>
          </div>
          <Accordion :items="faqItems" />
        </div>
      </section>

      <!-- Финальный CTA -->
      <section class="bg-white py-14 md:py-20 border-t border-neutral-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
          <div class="max-w-xl mx-auto space-y-6 md:space-y-8">
            <h2
              class="text-2xl md:text-3xl font-bold tracking-tight text-slate-900 leading-[1.1]"
            >
              Готовы оформить заказ?
            </h2>
            <Typography as="p" variant="body" class="text-slate-600 text-lg leading-relaxed">
              Перейдите к форме — шаги те же, что описаны выше. Если что-то смущает, сначала загляните в раздел с размерами или напишите нам.
            </Typography>
            <Button variant="primary" size="lg" class="min-w-[220px]" @click="goToOrderCheckout">
              Оформить заказ
            </Button>
          </div>
        </div>
      </section>
    </main>

    <Footer />
  </div>
</template>
