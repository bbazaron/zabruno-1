<script setup lang="ts">
import { useRouter } from 'vue-router'
import Header from '../components/sections/Header.vue'
import Footer from '../components/sections/Footer.vue'
import Card from '../components/ui/Card.vue'
import Typography from '../components/ui/Typography.vue'

const router = useRouter()

const genders = [
  {
    id: 'girls',
    name: 'Для девочек',
    image: '/images/SelectGender/girl.jpg',
  },
  {
    id: 'boys',
    name: 'Для мальчиков',
    image: '/images/SelectGender/boy.jpg',
  },
]

function goToCatalog(gender: 'boys' | 'girls') {
  router.push({ path: '/catalog', query: { gender } })
}
</script>

<template>
  <div class="w-full min-h-screen flex flex-col">
    <Header />

    <main class="flex-1 flex flex-col items-center justify-center bg-neutral-50 py-12">
      <Typography as="h1" variant="h2" class="mb-12 text-center">
        Выберите категорию
      </Typography>

      <div class="grid sm:grid-cols-2 gap-8 max-w-5xl w-full px-4">
        <Card
            v-for="gender in genders"
            :key="gender.id"
            class="relative cursor-pointer overflow-hidden group  flex flex-col justify-end h-full min-h-[600px]"
            @click="goToCatalog(gender.id)"
        >
          <!-- Фоновое изображение -->
          <img
              :src="gender.image"
              :alt="gender.name"
              class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
          />
          <!-- Градиент -->
          <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent" />
          <!-- Заголовок -->
          <div class="absolute bottom-4 left-4 text-white">
            <Typography as="h3" variant="h3" class="text-white font-bold text-2xl">
              {{ gender.name }}
            </Typography>
          </div>
        </Card>
      </div>
    </main>

    <Footer />
  </div>
</template>