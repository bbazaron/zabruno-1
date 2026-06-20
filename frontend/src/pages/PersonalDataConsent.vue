<script setup lang="ts">
import { RouterLink } from 'vue-router'
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
            <RouterLink to="/" class="hover:text-slate-900">Главная</RouterLink>
            <span class="mx-2">/</span>
            <span class="text-slate-900 font-medium">Согласие на обработку персональных данных</span>
          </div>
          <Typography as="h1" variant="h2" class="text-slate-900">
            Согласие на обработку персональных данных
          </Typography>
        </div>
      </section>

      <section class="bg-white py-8 lg:py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8 text-slate-700 text-left">
          <div class="space-y-1">
            <Typography as="h2" variant="h3" class="text-slate-900 tracking-wide uppercase">
              Согласие
            </Typography>
            <Typography as="p" variant="body" class="text-slate-900 font-medium">
              на обработку персональных данных
            </Typography>
          </div>

          <Typography as="p" variant="body" class="leading-relaxed">
            Я, являясь пользователем сайта FORMA75.RU, действуя свободно, своей волей и в своем интересе, а также
            подтверждая свою дееспособность, предоставляю Индивидуальному предпринимателю Хан Эржэне Бадмажаповне
            (ИНН 800101362802), адрес: 687000, Забайкальский край, пгт. Агинское, ул. Юннатская, д. 8а (далее —
            Оператор), согласие на обработку моих персональных данных на следующих условиях.
          </Typography>

          <div class="space-y-6">
            <div class="space-y-3">
              <Typography as="p" variant="body" class="leading-relaxed">
                <span class="font-semibold text-slate-900">1.</span>
                Согласие предоставляется на обработку следующих персональных данных:
              </Typography>
              <ul class="list-disc pl-6 space-y-1 text-sm sm:text-base leading-relaxed">
                <li>фамилия, имя, отчество;</li>
                <li>номер телефона;</li>
                <li>адрес электронной почты;</li>
                <li>адрес доставки;</li>
                <li>сведения о заказах;</li>
                <li>сведения, указываемые при регистрации личного кабинета;</li>
                <li>сведения, содержащиеся в обращениях, направляемых через формы сайта;</li>
                <li>иные данные, добровольно предоставляемые мной Оператору.</li>
              </ul>
            </div>

            <div class="space-y-3">
              <Typography as="p" variant="body" class="leading-relaxed">
                <span class="font-semibold text-slate-900">2.</span>
                При оформлении заказа школьной формы дополнительно могут обрабатываться сведения о ребенке:
              </Typography>
              <ul class="list-disc pl-6 space-y-1 text-sm sm:text-base leading-relaxed">
                <li>фамилия и имя ребенка;</li>
                <li>сведения об образовательной организации;</li>
                <li>класс;</li>
                <li>размеры одежды;</li>
                <li>иные сведения, необходимые для изготовления и передачи товара.</li>
              </ul>
            </div>

            <Typography as="p" variant="body" class="leading-relaxed">
              <span class="font-semibold text-slate-900">3.</span>
              Я подтверждаю, что являюсь законным представителем ребенка либо действую с согласия законного
              представителя и вправе предоставлять указанные сведения Оператору.
            </Typography>

            <div class="space-y-3">
              <Typography as="p" variant="body" class="leading-relaxed">
                <span class="font-semibold text-slate-900">4.</span>
                Персональные данные обрабатываются в целях:
              </Typography>
              <ul class="list-disc pl-6 space-y-1 text-sm sm:text-base leading-relaxed">
                <li>регистрации и обслуживания личного кабинета;</li>
                <li>оформления, обработки и исполнения заказов;</li>
                <li>изготовления товаров по индивидуальным параметрам;</li>
                <li>нанесения эмблем образовательных организаций;</li>
                <li>организации выдачи и доставки товаров;</li>
                <li>приема и обработки платежей;</li>
                <li>направления уведомлений о статусе заказа;</li>
                <li>обработки обращений и претензий;</li>
                <li>исполнения требований законодательства Российской Федерации;</li>
                <li>ведения бухгалтерского и налогового учета;</li>
                <li>обеспечения работы сайта и его сервисов.</li>
              </ul>
            </div>

            <Typography as="p" variant="body" class="leading-relaxed">
              <span class="font-semibold text-slate-900">5.</span>
              Я предоставляю согласие на совершение следующих действий с персональными данными: сбор, запись,
              систематизация, накопление, хранение, уточнение (обновление, изменение), извлечение, использование,
              передача (предоставление, доступ), обезличивание, блокирование, удаление и уничтожение персональных
              данных.
            </Typography>

            <Typography as="p" variant="body" class="leading-relaxed">
              <span class="font-semibold text-slate-900">6.</span>
              Обработка персональных данных может осуществляться как с использованием средств автоматизации, так и
              без использования таких средств.
            </Typography>

            <div class="space-y-3">
              <Typography as="p" variant="body" class="leading-relaxed">
                <span class="font-semibold text-slate-900">7.</span>
                Я уведомлен(а) и соглашаюсь с тем, что Оператор вправе передавать персональные данные:
              </Typography>
              <ul class="list-disc pl-6 space-y-1 text-sm sm:text-base leading-relaxed">
                <li>ООО НКО «ЮМани» (ЮKassa) для обработки платежей;</li>
                <li>транспортным компаниям, включая СДЭК, для организации доставки товаров;</li>
                <li>организациям, обеспечивающим техническое функционирование сайта;</li>
                <li>хостинг-провайдерам;</li>
                <li>подрядчикам по сопровождению сайта;</li>
                <li>государственным органам в случаях, предусмотренных законодательством Российской Федерации.</li>
              </ul>
              <Typography as="p" variant="body" class="leading-relaxed">
                Оператор вправе поручать обработку персональных данных третьим лицам, привлекаемым для обеспечения
                работы сайта, исполнения заказов, обработки платежей, доставки товаров и выполнения иных
                обязательств перед пользователем, при условии соблюдения такими лицами требований законодательства
                Российской Федерации о персональных данных и обеспечения конфиденциальности персональных данных.
              </Typography>
            </div>

            <Typography as="p" variant="body" class="leading-relaxed">
              <span class="font-semibold text-slate-900">8.</span>
              Согласие действует с момента его предоставления до достижения целей обработки персональных данных
              либо до момента его отзыва, если иной срок хранения не предусмотрен законодательством Российской
              Федерации.
            </Typography>

            <Typography as="p" variant="body" class="leading-relaxed">
              <span class="font-semibold text-slate-900">9.</span>
              Я уведомлен(а) о праве отозвать настоящее согласие путем направления обращения на адрес электронной
              почты:
              <a href="mailto:erjena.erjena@mail.ru" class="text-slate-900 underline underline-offset-2 hover:text-slate-700">
                erjena.erjena@mail.ru
              </a>
            </Typography>

            <Typography as="p" variant="body" class="leading-relaxed">
              <span class="font-semibold text-slate-900">10.</span>
              Отзыв согласия не влияет на законность обработки персональных данных, осуществленной до момента
              получения Оператором соответствующего обращения.
            </Typography>

            <Typography as="p" variant="body" class="leading-relaxed">
              <span class="font-semibold text-slate-900">11.</span>
              Я подтверждаю, что ознакомлен(а) с
              <RouterLink
                to="/personal-data-policy"
                class="text-slate-900 underline underline-offset-2 hover:text-slate-700"
              >
                Политикой обработки персональных данных
              </RouterLink>,
              размещенной на сайте FORMA75.RU, и принимаю ее условия.
            </Typography>

            <Typography as="p" variant="body" class="leading-relaxed">
              <span class="font-semibold text-slate-900">12.</span>
              Предоставляя настоящее согласие, я подтверждаю достоверность предоставляемых сведений и осознаю
              последствия предоставления недостоверной информации.
            </Typography>
          </div>
        </div>
      </section>
    </main>

    <Footer />
  </div>
</template>
