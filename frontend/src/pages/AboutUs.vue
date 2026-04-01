<script setup lang="ts">
import Header from '../components/sections/Header.vue'
import Footer from '../components/sections/Footer.vue'
import Typography from '../components/ui/Typography.vue'
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
            <span class="text-slate-900 font-medium">О нас</span>
          </div>
          <Typography as="h1" variant="h2" class="text-slate-900 mb-2">
            О нас
          </Typography>
          <Typography as="p" variant="body" class="text-slate-600">
            Собственное производство школьной формы из качественных материалов.
          </Typography>
        </div>
      </section>

      <section class="bg-neutral-50 py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
          <Typography as="p" variant="body" class="text-slate-700">
            Мы шьём школьную форму и сопутствующие изделия, контролируя качество на каждом этапе — от
            подбора ткани до финальной примерки. Работаем с натуральными и износостойкими материалами,
            чтобы вещи служили долго и удобно сидели на ребёнке.
          </Typography>
          <Typography as="p" variant="body" class="text-slate-700">
            Помогаем подобрать размер по таблице и меркам, оформляем предзаказы и отвечаем на вопросы по
            составу комплектов. Наша цель — чтобы родители и школы были уверены в посадке и сроках
            изготовления.
          </Typography>
          <Typography as="p" variant="body" class="text-slate-700">
            Если хотите обсудить заказ или условия — напишите или позвоните, контакты указаны на странице
            <a href="/contacts" class="text-slate-900 font-medium underline underline-offset-2 hover:text-slate-700">
              Контакты
            </a>.
          </Typography>
        </div>
      </section>
    </main>

    <Footer />
  </div>
</template>
