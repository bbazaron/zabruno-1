<script setup lang="ts">
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { useRoute } from 'vue-router'

import Header from '../components/sections/Header.vue'
import Footer from '../components/sections/Footer.vue'
import Button from '../components/ui/Button.vue'
import Typography from '../components/ui/Typography.vue'
import { ShoppingCart } from 'lucide-vue-next'

const route = useRoute()
const productId = route.params.id

const product = ref<any>(null)
const selectedMedia = ref('') // ссылка на текущее главное изображение/видео

async function fetchProduct() {
  try {
    const response = await axios.get(`/api/product/${productId}`, {
      headers: { Accept: 'application/json' },
    })
    product.value = response.data
    selectedMedia.value = product.value.image // первое изображение по умолчанию
  } catch (err) {
    console.error(err)
  }
}

onMounted(fetchProduct)

function selectMedia(url: string) {
  selectedMedia.value = url
}

function addToCart() {
  console.log('Добавлено в корзину', product.value)
}
</script>

<template>
  <div class="w-full">
    <Header />

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
      <div v-if="product" class="grid md:grid-cols-5 gap-8">
        <!-- Левый блок: миниатюры -->
        <div class="flex flex-col gap-4 col-span-1">
          <button
              v-for="media in product.media"
              :key="media"
              @click="selectMedia(media)"
              class="border rounded overflow-hidden hover:border-slate-900"
          >
            <img :src="media" class="w-20 h-20 object-cover" />
          </button>
        </div>

        <!-- Центр: основное изображение -->
        <div class="col-span-3">
          <div class="w-full h-[500px] bg-neutral-100 flex items-center justify-center rounded-lg overflow-hidden">
            <img :src="selectedMedia" alt="product" class="object-contain h-full w-full" />
          </div>
        </div>

        <!-- Правый блок: информация о товаре -->
        <div class="col-span-1 flex flex-col gap-4">
          <Typography as="h1" variant="h2" class="text-slate-900">
            {{ product.name }}
          </Typography>
          <Typography as="p" variant="body" class="text-slate-600">
            {{ product.description }}
          </Typography>
          <div class="text-2xl font-bold text-slate-900 mt-4">
            {{ product.price }} ₽
            <span
                v-if="product.originalPrice > product.price"
                class="text-base line-through text-slate-500 ml-2"
            >
          {{ product.originalPrice }} ₽
        </span>
          </div>
          <Button
              @click="addToCart"
              variant="primary"
              class="w-full flex items-center justify-center gap-2 mt-4"
          >
            <ShoppingCart size="16" />
            Добавить в корзину
          </Button>
        </div>
      </div>

      <!-- Загрузка или пустой продукт -->
      <div v-else class="text-center py-20 text-slate-600">
        Загрузка товара...
      </div>
    </main>

    <Footer />
  </div>
</template>