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
            <span class="text-slate-900 font-medium">Публичная оферта</span>
          </div>
          <Typography as="h1" variant="h2" class="text-slate-900">
            Публичная оферта
          </Typography>
        </div>
      </section>

      <section class="bg-white py-8 lg:py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8 text-slate-700 text-left">
          <div class="space-y-1">
            <Typography as="h2" variant="h3" class="text-slate-900">
              Публичная оферта розничной купли-продажи для интернет-магазина FORMA75.RU
            </Typography>
            <Typography as="p" variant="body" class="text-slate-900 font-medium">
              пгт. Агинское
            </Typography>
            <Typography as="p" variant="body" class="text-slate-900 font-medium">
              01.04.2026 г.
            </Typography>
          </div>

          <div class="space-y-6">
            <div class="space-y-3">
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">1. Общие положения</span></Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">1.1.</span> Продавец: Индивидуальный предприниматель Хан Эржэна Бадмажаповна, свидетельство о государственной регистрации №316753600058958 от 28 марта 2016 г., выданного Управлением ФНС России по Забайкальскому краю.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">1.2.</span> Оферта — публичное предложение Продавца любому лицу заключить на условиях, изложенных в Оферте, договор розничной купли-продажи Товара (далее — Договор). Оферта является публичной (п. 2 ст. 437 ГК РФ). Оферта размещается на сайте по адресу: forma75.ru. Оферта действует до ее отзыва Продавцом. Продавец вправе изменить или отозвать Оферту в одностороннем порядке, без какого-либо специального уведомления. Договор заключается на условиях, предусмотренных в редакции Оферты, действующей в момент акцепта ее Покупателем. Новая редакция Оферты не распространяется на правоотношения между Покупателем и Продавцом, возникшие до вступления новой редакции Оферты в силу. Заказы, которые уже оформлены к моменту изменения или отзыва Оферты, исполняются на условиях Оферты, которая действовала на момент их оформления. Действующая редакция Оферты всегда находится в Интернет по адресу: forma75.ru</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">1.3.</span> Товар — любой товар, который Продавец предлагает к продаже на сайте forma75.ru, сопровождая его описанием, указанием цены, сведений об изготовителе и иной информацией.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">1.4.</span> Покупатель — любое физическое или юридическое лицо, которое оформило заказ на приобретение товара у Продавца в установленном настоящей Офертой порядке. Покупатель — физическое лицо, которое приобретает товар для личных, семейных, домашних и иных нужд, которые не связаны с его предпринимательской деятельностью. К отношениям между потребителем и Продавцом по Договору применяются Закон Российской Федерации от 07.02.1992 N 2300-1 «О защите прав потребителей» и иные правовые акты, принятые в соответствии с ним.</Typography>
            </div>

            <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">2. Предмет Договора</span><br>Продавец обязуется передать в собственность Покупателю, а Покупатель обязуется оплатить и принять Товар, заказанный на условиях Оферты.</Typography>

            <div class="space-y-3">
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">3. Порядок заключения Договора</span></Typography>
              <Typography as="p" variant="body" class="leading-relaxed">Акцептом Оферты является совершение Покупателем совокупности действий:</Typography>
              <ul class="list-disc pl-6 space-y-1 text-sm sm:text-base leading-relaxed">
                <li>проставление чек-бокса «Принимаю условия Публичной оферты»;</li>
                <li>оформление заказа в сервисе на основании Оферты;</li>
                <li>внесение полной оплаты Заказа.</li>
              </ul>
              <Typography as="p" variant="body" class="leading-relaxed">Лицо, которое произвело акцепт настоящей Оферты, становится Покупателем, а Продавец и Покупатель совместно — Сторонами Договора. Покупатель — физическое лицо, принимая условия настоящей Оферты, подтверждает, что он правоспособен, полностью дееспособен (достиг совершеннолетия) и вправе заключать Договор, что в соответствии с законами его государства не имеет запретов и ограничений (в том числе возрастных) для заказа Товаров по Договору.</Typography>
            </div>

            <div class="space-y-3">
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">4. Порядок оформления заказа</span></Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">4.1.</span> Продажа Товара осуществляется в реальном времени и подразумевает изменение цены и наличия Товара без предварительного уведомления об этом.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">4.2.</span> Заказ оформляется Покупателем самостоятельно на сайте по адресу: forma75.ru. Продавец в этом случае вправе связаться с Покупателем, чтобы дополнительно подтвердить заказ.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">4.3.</span> Оформление Заказа возможно, как с регистрацией личного кабинета, так и без регистрации.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">4.4.</span> Обязательства Продавца по изготовлению, комплектованию и передаче Товара возникают с момента получения Продавцом полной оплаты Заказа и подтверждения оформления Заказа Покупателем. После получения оплаты Продавец приступает к выполнению Заказа, включая индивидуальное изготовление Товара, нанесение эмблем, подбор размеров, комплектование и подготовку Товара к выдаче либо отправке Покупателю.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed">Договор розничной купли-продажи считается заключённым с момента:</Typography>
              <ul class="list-disc pl-6 space-y-1 text-sm sm:text-base leading-relaxed">
                <li>оформления Покупателем Заказа на сайте FORMA75.RU;</li>
                <li>подтверждения Заказа Продавцом;</li>
                <li>и получения Продавцом полной оплаты Заказа.</li>
              </ul>
              <Typography as="p" variant="body" class="leading-relaxed">Подтверждением заключения договора и оплаты Товара является кассовый чек, электронный чек, уведомление платёжной системы либо иной документ, подтверждающий оплату Заказа. После получения полной оплаты Продавец приступает к изготовлению, комплектованию и подготовке Товара к выдаче либо отправке Покупателю. После оформления Заказа на сайте FORMA75.RU и получения полной оплаты Заказа Продавец предоставляет Покупателю подтверждение заключения договора розничной купли-продажи на условиях настоящей Оферты.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed">Подтверждение Заказа направляется Покупателю:</Typography>
              <ul class="list-disc pl-6 space-y-1 text-sm sm:text-base leading-relaxed">
                <li>по электронной почте;</li>
                <li>посредством SMS-сообщения;</li>
                <li>через личный кабинет на Сайте;</li>
                <li>либо иным способом, указанным Покупателем при оформлении Заказа.</li>
              </ul>
              <Typography as="p" variant="body" class="leading-relaxed">Подтверждение Заказа содержит:</Typography>
              <ul class="list-disc pl-6 space-y-1 text-sm sm:text-base leading-relaxed">
                <li>номер Заказа либо иной идентификатор Заказа;</li>
                <li>сведения о Продавце;</li>
                <li>сведения о Покупателе;</li>
                <li>наименование Товара;</li>
                <li>количество Товара;</li>
                <li>размеры и иные параметры Заказа;</li>
                <li>сведения о школе и/или эмблеме (при наличии);</li>
                <li>стоимость Заказа;</li>
                <li>информацию об оплате;</li>
                <li>способ получения Товара;</li>
                <li>ориентировочные сроки изготовления и выдачи Товара;</li>
                <li>адрес пункта выдачи заказов либо сведения об отправке Товара.</li>
              </ul>
              <Typography as="p" variant="body" class="leading-relaxed">Номер Заказа и информация, направленные Покупателю, позволяют идентифицировать Заказ, получить сведения о заключенном договоре розничной купли-продажи, составе Заказа и его статусе. Информация о статусе Заказа также может отображаться в личном кабинете Покупателя на Сайте.</Typography>
            </div>

            <div class="space-y-3">
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">5. Персональные данные покупателя</span></Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">5.1.</span> Покупатель обязуется предоставлять достоверную, актуальную и достаточную информацию при регистрации на сайте FORMA75.RU, оформлении Заказа, использовании личного кабинета и взаимодействии с Продавцом.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">5.2.</span> При регистрации на Сайте, оформлении Заказа либо использовании сервисов Сайта Покупатель предоставляет Продавцу согласие на обработку персональных данных в целях:</Typography>
              <ul class="list-disc pl-6 space-y-1 text-sm sm:text-base leading-relaxed">
                <li>регистрации и обслуживания личного кабинета;</li>
                <li>оформления, оплаты, изготовления и выдачи Заказа;</li>
                <li>индивидуального изготовления Товара;</li>
                <li>нанесения эмблемы школы;</li>
                <li>идентификации Покупателя;</li>
                <li>обратной связи с Покупателем;</li>
                <li>направления уведомлений о статусе Заказа;</li>
                <li>обработки обращений, претензий и возвратов;</li>
                <li>улучшения работы Сайта и сервисов;</li>
                <li>проведения аналитических, статистических и маркетинговых исследований;</li>
                <li>использования сервисов аналитики, включая Яндекс.Метрику.</li>
              </ul>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">5.3.</span> Продавец не вправе отказывать Покупателю в заключении, исполнении либо расторжении договора в связи с отказом Покупателя предоставить персональные данные, если предоставление таких данных не является обязательным в соответствии с законодательством Российской Федерации либо не требуется для исполнения Заказа.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">5.4.</span> При оформлении Заказа Покупатель предоставляет:</Typography>
              <ul class="list-disc pl-6 space-y-1 text-sm sm:text-base leading-relaxed">
                <li>ФИО;</li>
                <li>номер телефона;</li>
                <li>адрес электронной почты;</li>
                <li>сведения о ребёнке;</li>
                <li>сведения о школе;</li>
                <li>класс;</li>
                <li>размеры и иные параметры, необходимые для изготовления и выдачи Товара;</li>
                <li>адрес доставки (при необходимости отправки через транспортную компанию).</li>
              </ul>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">5.5.</span> При регистрации на Сайте Покупатель предоставляет:</Typography>
              <ul class="list-disc pl-6 space-y-1 text-sm sm:text-base leading-relaxed">
                <li>имя;</li>
                <li>номер телефона;</li>
                <li>адрес электронной почты;</li>
                <li>пароль для доступа к личному кабинету.</li>
              </ul>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">5.6.</span> Покупатель подтверждает законность предоставления персональных данных ребёнка и наличие необходимых прав и согласий для передачи указанных данных Продавцу.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">5.6.1.</span> Покупатель подтверждает, что является законным представителем ребёнка либо действует с согласия законного представителя ребёнка и вправе предоставлять сведения о ребёнке Продавцу для целей исполнения Заказа.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">5.7.</span> Продавец обрабатывает персональные данные Покупателя в соответствии с Федеральным законом №152-ФЗ «О персональных данных», <RouterLink to="/personal-data-policy" class="text-slate-900 underline underline-offset-2 hover:text-slate-700">Политикой обработки персональных данных</RouterLink> и иными документами, размещенными на Сайте. Покупатель подтверждает ознакомление с Политикой обработки персональных данных, размещенной на Сайте.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">5.8.</span> Продавец принимает необходимые организационные и технические меры для защиты персональных данных Покупателей от неправомерного доступа, изменения, раскрытия, уничтожения и иных неправомерных действий.</Typography>
            </div>

            <div class="space-y-3">
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">6. Изменение заказа после его оформления</span></Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">6.1.</span> Заказ может быть изменен или отменен по инициативе Продавца с немедленным уведомлением об этом Покупателя по телефону в следующих исключительных случаях:</Typography>
              <ul class="list-disc pl-6 space-y-1 text-sm sm:text-base leading-relaxed">
                <li>если Продавец выявит отсутствие заказанного товара или его нужного количества на складе;</li>
                <li>если Продавец выявит бракованный Товар, который не может быть заменен качественным в разумные сроки;</li>
                <li>если поставщик Товара Партнер находится в процессе ликвидации или в процедуре банкротства, или возникли другие риски ненадлежащего исполнения поставки;</li>
                <li>если Товар предварительного заказа не поступил в продажу, и выход Товара в реализацию задерживается на неопределенный срок либо отменен изготовителем;</li>
                <li>если введены законодательные ограничения на свободную продажу заказанного Товара либо есть иные установленные законом ограничения на исполнение заказа.</li>
              </ul>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">6.2.</span> Покупатель может внести изменения в заказ только на этапе его комплектации (до передачи в доставку). Изменение и подтверждение измененного заказа осуществляется теми же способами, что и оформление.</Typography>
            </div>

            <div class="space-y-3">
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">7. Отказ от товара до его передачи</span></Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">7.1.</span> Покупатель вправе отказаться от Заказа в любое время до момента фактической передачи Товара.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">7.2.</span> Отказ от Товара осуществляется путем:</Typography>
              <ul class="list-disc pl-6 space-y-1 text-sm sm:text-base leading-relaxed">
                <li>обращения к Продавцу по телефону;</li>
                <li>направления уведомления на электронную почту Продавца;</li>
                <li>через личный кабинет на Сайте;</li>
                <li>иным способом, позволяющим идентифицировать Покупателя и Заказ.</li>
              </ul>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">7.3.</span> В случае отказа Покупателя от Товара до начала индивидуального изготовления Товара Продавец осуществляет возврат уплаченных денежных средств в порядке, предусмотренном настоящей Офертой и законодательством Российской Федерации.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">7.4.</span> В случае отказа Покупателя от Товара после начала индивидуального изготовления Товара Продавец вправе удержать фактически понесенные расходы, связанные с изготовлением Товара, закупкой материалов, нанесением эмблем, выполнением индивидуальных работ, подготовкой Заказа к выдаче или отправке, если это допускается законодательством Российской Федерации.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">7.5.</span> Товары, изготовленные по индивидуальным параметрам Покупателя, включая Товары с нанесением эмблемы школы, индивидуальными размерами либо иными персонализированными характеристиками, после запуска в производство могут не подлежать отмене и возврату денежных средств в части фактически выполненных работ и понесенных расходов Продавца.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">7.6.</span> Если Товар был отправлен Покупателю через транспортную компанию по просьбе Покупателя, при отказе от Заказа Покупатель обязан возместить Продавцу фактически понесенные расходы на доставку, упаковку и отправку Товара.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">7.7.</span> При самовывозе Товара через ПВЗ Продавца дополнительные расходы за выдачу Товара с Покупателя не взимаются.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">7.8.</span> Возврат денежных средств осуществляется в сроки и порядке, предусмотренные настоящей Офертой и законодательством Российской Федерации.</Typography>
            </div>

            <div class="space-y-3">
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">8. Информирование Покупателя</span></Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">8.1.</span> Продавец обязуется предоставить Покупателю необходимую и достоверную информацию о Товаре.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">8.1.1.</span> Покупатель вправе требовать предоставления ему необходимой и достоверной информации о Товаре, соответствующей установленным законом, иными правовыми актами и обычно предъявляемым в розничной торговле требованиям к содержанию и способам предоставления такой информации.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">8.2.</span> Информация о Товаре.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">8.2.1.</span> Продавец уведомляет Покупателя, что на сайте предоставлена информация об основных потребительских свойствах Товара, об адресе (месте нахождения) продавца, о месте изготовления товара, о полном фирменном наименовании (наименовании) продавца (изготовителя), о цене и об условиях приобретения товара, о его доставке, о порядке оплаты товара, а также о сроке, в течение которого действует предложение о заключении Договора. Вся информация о Товаре, представленная на сайте, носит информационный характер, не является рекламой и не может в полной мере передавать всю информацию о свойствах и характеристиках Товара. Покупатель имеет право обратиться в службу поддержки за дополнительной информацией о заинтересовавшем его Товаре. По просьбе Покупателя менеджер интернет-магазина обязан предоставить (по телефону или посредством электронной почты) прочую информацию, необходимую и достаточную, с точки зрения Покупателя, для принятия им решения о покупке Товара. Любые характеристики Товаров могут быть изменены изготовителем Товара без предварительного уведомления.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">8.3.</span> До момента получения Товара Покупателю предоставляется необходимая и достоверная информация о Товаре, обеспечивающая возможность правильного выбора Товара, в том числе:</Typography>
              <ul class="list-disc pl-6 space-y-1 text-sm sm:text-base leading-relaxed">
                <li>наименование Товара;</li>
                <li>сведения об основных потребительских свойствах Товара;</li>
                <li>информация о размерах, материалах, составе ткани и особенностях ухода за изделием;</li>
                <li>сведения о наличии индивидуальных характеристик Товара, включая нанесение эмблемы школы, индивидуальный пошив либо изготовление под заказ;</li>
                <li>цена Товара в рублях Российской Федерации;</li>
                <li>условия оплаты и получения Товара;</li>
                <li>информация о порядке и сроках изготовления, выдачи и возврата Товара;</li>
                <li>сведения о Продавце и адресе пункта выдачи заказов;</li>
                <li>информация о правилах эксплуатации и рекомендациях по уходу за изделием;</li>
                <li>сведения о подтверждении соответствия Товара обязательным требованиям законодательства Российской Федерации, если такое подтверждение является обязательным;</li>
                <li>информация о том, что Товар с индивидуально-определенными свойствами надлежащего качества обмену и возврату не подлежит.</li>
              </ul>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">8.4.</span> Информация о Товаре предоставляется:</Typography>
              <ul class="list-disc pl-6 space-y-1 text-sm sm:text-base leading-relaxed">
                <li>на Сайте;</li>
                <li>в карточке Товара;</li>
                <li>в личном кабинете Покупателя;</li>
                <li>при получении Товара в ПВЗ;</li>
                <li>посредством маркировки, ярлыков, упаковки либо сопроводительной документации.</li>
              </ul>
              <Typography as="p" variant="body" class="leading-relaxed">Если Товар имеет недостатки, был восстановлен, уценен либо имеет особенности, влияющие на его потребительские свойства, Покупателю предоставляется соответствующая информация до момента передачи Товара.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">8.4.1.</span> Покупатель уведомлен и соглашается, что оттенки цвета Товара могут незначительно отличаться от изображений, представленных на Сайте, в зависимости от настроек экрана устройства, особенностей фотосъемки, условий освещения, а также партии материалов и сырья.</Typography>
            </div>

            <div class="space-y-3">
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">9. Порядок передачи Товара</span></Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">9.1.</span> Передача Товара Покупателю осуществляется исключительно через собственный пункт выдачи заказов Продавца (ПВЗ), расположенный по адресу: Забайкальский край, пгт. Агинское, ул. Цыбикова, д. 6В.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">9.2.</span> Основным способом получения Товара является самовывоз через ПВЗ Продавца. По отдельному согласованию с Покупателем Продавец вправе организовать передачу Товара через транспортные, курьерские либо почтовые организации.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">9.3.</span> После оформления и оплаты Заказа Товар передается Покупателю после его изготовления, комплектования и изменения статуса Заказа на «Готов к выдаче».</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">9.4.</span> Выдача Заказов начинается с 1 августа соответствующего сезона, если иной срок не указан Продавцом дополнительно.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">9.4.1.</span> Срок изготовления Товара указывается Продавцом при оформлении Заказа и является ориентировочным, поскольку зависит от сезонной производственной нагрузки, объема Заказов, наличия материалов и комплектующих, необходимости индивидуального изготовления Товара, согласования параметров Заказа и иных обстоятельств, влияющих на производственный процесс.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">9.4.2.</span> Продавец вправе изменить ориентировочный срок изготовления Товара при возникновении обстоятельств, объективно влияющих на сроки исполнения Заказа, уведомив Покупателя одним из способов, предусмотренных настоящей Офертой.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">9.4.3.</span> Незначительное изменение сроков изготовления и передачи Товара, вызванное производственной необходимостью, сезонной нагрузкой либо иными объективными обстоятельствами, не считается нарушением обязательств Продавца при условии уведомления Покупателя.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">9.4.4.</span> В случае существенного увеличения срока изготовления Товара Покупатель вправе отказаться от Заказа и потребовать возврата денежных средств за неисполненную часть Заказа в порядке, предусмотренном законодательством Российской Федерации.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">9.5.</span> О готовности Товара к выдаче Покупатель уведомляется одним или несколькими способами: по телефону, посредством SMS-сообщения, по электронной почте, через личный кабинет на Сайте, через мессенджеры, указанные Покупателем.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">9.6.</span> Покупатель обязан самостоятельно получить Товар в ПВЗ в часы работы пункта выдачи.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">9.7.</span> При получении Товара Покупатель обязан сообщить номер Заказа, предъявить документ, удостоверяющий личность, по требованию Продавца, проверить внешний вид Товара, комплектность и соответствие Заказу.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">9.8.</span> Подписание документа о получении Товара либо фактическое получение Товара Покупателем подтверждает передачу Товара Покупателю, отсутствие претензий к внешнему виду, комплектности и количеству Товара.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">9.9.</span> Право собственности на Товар, а также риск случайной гибели или повреждения Товара переходят к Покупателю с момента фактической передачи Товара в ПВЗ.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">9.10.</span> В случае неполучения Товара Покупателем в разумный срок Продавец вправе связаться с Покупателем для согласования новой даты выдачи, поместить Товар на ответственное хранение, отменить Заказ в случаях, предусмотренных законодательством РФ и настоящей Офертой.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">9.11.</span> В случае изготовления Товара по индивидуальным параметрам Покупателя Продавец вправе удерживать Товар на хранении до его получения Покупателем в сроки, установленные внутренними правилами Продавца.</Typography>
            </div>

            <div class="space-y-3">
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">10. Цена Товара и порядок расчетов</span></Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">10.1.</span> Стоимость Заказа определяется Продавцом в одностороннем порядке на момент оформления Заказа и состоит из стоимости Товара и стоимости доставки (при необходимости).</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">10.2.</span> Цены на Товары указываются на сайте FORMA75.RU в рублях Российской Федерации.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">10.3.</span> Основным способом получения Товара является самовывоз через пункт выдачи заказов (ПВЗ) Продавца по адресу: Забайкальский край, пгт. Агинское, ул. Цыбикова, д. 6В.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">10.4.</span> Для Покупателей, находящихся вне населённого пункта расположения ПВЗ, по согласованию с Продавцом может быть организована отправка Товара через транспортные, курьерские либо почтовые организации.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">10.5.</span> Стоимость доставки не входит в стоимость Товара и оплачивается Покупателем отдельно в соответствии с тарифами выбранной службы доставки.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">10.6.</span> В случае привлечения транспортной компании для доставки Товара Продавец несет ответственность перед Покупателем в пределах, предусмотренных законодательством Российской Федерации. Продавец не отвечает за нарушение сроков доставки, вызванное действиями перевозчика, если им были приняты все разумные меры для своевременной передачи Товара.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">10.7.</span> Обязанность Продавца по передаче Товара считается исполненной при самовывозе — с момента фактической передачи Товара Покупателю в ПВЗ; при отправке через транспортную компанию — с момента передачи Товара перевозчику.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">10.8.</span> Оплата Заказа осуществляется банковской картой, через СБП, QR-оплатой и иными способами, указанными на сайте.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">10.9.</span> Прием платежей осуществляется через сервис ЮKassa.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">10.10.</span> Продавец применяет онлайн-кассу в соответствии с законодательством Российской Федерации и направляет Покупателю электронный кассовый чек.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">10.11.</span> Обязанность Покупателя по оплате считается исполненной с момента подтверждения успешного платежа.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">10.12.</span> В случае невозможности исполнения Заказа по причинам отсутствия Товара, материалов либо иным обстоятельствам, Продавец осуществляет возврат денежных средств Покупателю способом, которым была произведена оплата, если иное не согласовано сторонами.</Typography>
            </div>

            <div class="space-y-3">
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">11. Возврат Товара после его передачи</span></Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">11.1.</span> В случае обнаружения недостатков Товара, если они не были заранее оговорены Продавцом, Покупатель вправе предъявить требования, предусмотренные законодательством Российской Федерации о защите прав потребителей.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">11.2.</span> Браком признаются пятна, дырки, непрошитые элементы, повреждения ткани и иные производственные дефекты, возникшие по вине Продавца.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">11.3.</span> Претензии по качеству Товара направляются Покупателем путем обращения в пункт выдачи заказов Продавца либо посредством направления письменного обращения на электронную почту Продавца.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">11.4.</span> В претензии Покупатель указывает ФИО, номер Заказа, дату оформления и получения Заказа, описание выявленного недостатка, обстоятельства обнаружения недостатка.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">11.5.</span> К претензии могут быть приложены фотографии Товара.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">11.6.</span> Возврат Товара осуществляется через пункт выдачи заказов Продавца по адресу: Забайкальский край, пгт. Агинское, ул. Цыбикова, д. 6В.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">11.7.</span> Покупатель вправе вернуть Товар надлежащего качества в течение 14 (четырнадцати) календарных дней с момента получения Товара при соблюдении условий сохранения товарного вида, ярлыков и упаковки, отсутствия следов эксплуатации и сохранения потребительских свойств Товара. Отсутствие кассового чека либо иного документа, подтверждающего оплату Товара, не лишает Покупателя права ссылаться на иные доказательства приобретения Товара у Продавца.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">11.8.</span> Не подлежат возврату и обмену Товары надлежащего качества, имеющие индивидуально-определенные свойства, в том числе изготовленные по индивидуальным размерам и по персональным параметрам Покупателя.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">11.9.</span> Для оформления возврата Покупатель заполняет заявление на возврат Товара.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">11.10.</span> Форма заявления может выдаваться в ПВЗ либо направляться Покупателю электронно.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">11.11.</span> Возврат денежных средств осуществляется после проверки Товара Продавцом способом, которым была произведена оплата, если иное не согласовано сторонами.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">11.12.</span> Срок рассмотрения претензии и возврата денежных средств определяется в соответствии с законодательством Российской Федерации.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">11.13.</span> В случае возврата Товара, отправленного Покупателю через транспортную компанию, расходы по обратной пересылке Товара надлежащего качества несет Покупатель, если иное не предусмотрено законодательством РФ.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">11.14.</span> Продавец вправе отказать в возврате Товара при наличии следов эксплуатации, повреждения Товара по вине Покупателя, отсутствии сохранности товарного вида, если Товар относится к категории индивидуально изготовленных изделий.</Typography>
            </div>

            <div class="space-y-3">
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">12. Срок и порядок возврата денежных средств при отказе от Товара или возврате Товара</span></Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">12.1.</span> В случае отказа Покупателя от Товара либо возврата Товара Продавец осуществляет возврат денежных средств в порядке и сроки, предусмотренные законодательством Российской Федерации.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">12.2.</span> Возврат денежных средств за Товар надлежащего качества осуществляется после получения Товара Продавцом, проверки сохранности товарного вида и подтверждения соблюдения условий возврата.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">12.3.</span> Возврат денежных средств производится не позднее 10 (десяти) календарных дней с момента получения Продавцом письменного требования Покупателя и возвращаемого Товара, если иной срок не установлен законодательством Российской Федерации.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">12.4.</span> Возврат денежных средств осуществляется тем же способом, которым была произведена оплата, если иное не согласовано сторонами.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">12.5.</span> При оплате Заказа банковской картой, через СБП, QR-оплату либо иным электронным способом возврат денежных средств осуществляется на банковский счет или платежный инструмент, с которого была произведена оплата.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">12.6.</span> В случае невозможности возврата денежных средств первоначальным способом по причинам, не зависящим от Продавца, Покупатель обязан предоставить актуальные банковские реквизиты для осуществления возврата.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">12.7.</span> Расходы, связанные с комиссией банков, платежных систем либо организаций, осуществляющих перевод денежных средств, возникшие не по вине Продавца, возлагаются на Покупателя в случаях, допустимых законодательством Российской Федерации.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">12.8.</span> При возврате Товара надлежащего качества Продавец возвращает стоимость Товара за вычетом расходов Продавца на доставку Товара Покупателю и/или возврат Товара от Покупателя, если такие расходы фактически были понесены Продавцом.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">12.9.</span> Стоимость услуг транспортных компаний, курьерских служб, почтовых расходов и иных услуг третьих лиц, связанных с доставкой Товара, возврату не подлежит, за исключением случаев передачи Товара ненадлежащего качества либо иных случаев, прямо предусмотренных законодательством Российской Федерации.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">12.10.</span> Возврат денежных средств за Товары с индивидуально-определенными свойствами надлежащего качества не осуществляется.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">12.11.</span> В случае отказа Покупателя от индивидуально изготовленного Товара после запуска Товара в производство Продавец вправе отказать в возврате денежных средств в части стоимости выполненных работ, использованных материалов и понесенных расходов, если это допускается законодательством Российской Федерации.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">12.12.</span> Возврат денежных средств осуществляется только лицу, осуществившему оплату Заказа, если иное не предусмотрено законодательством РФ либо письменным соглашением сторон.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">12.13.</span> Продавец вправе запросить у Покупателя документы и сведения, необходимые для идентификации плательщика и предотвращения мошеннических действий при возврате денежных средств.</Typography>
            </div>

            <div class="space-y-3">
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">13. Ответственность сторон</span></Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">13.1.</span> Стороны несут ответственность за неисполнение или ненадлежащее исполнение своих обязательств в соответствии с законодательством Российской Федерации и условиями настоящей Оферты.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">13.2.</span> Продавец несет ответственность за нарушение сроков передачи предварительно оплаченного Товара Покупателю в случаях и пределах, предусмотренных законодательством Российской Федерации. При этом Покупатель понимает и соглашается, что сроки изготовления и передачи Товара могут изменяться в связи с сезонной производственной нагрузкой, индивидуальным изготовлением Товара, необходимостью согласования параметров Заказа, отсутствием материалов, тканей или фурнитуры, действиями третьих лиц и обстоятельствами, не зависящими от Продавца.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">13.3.</span> Продавец не несет ответственности за нарушение сроков передачи Товара в случае предоставления Покупателем неверных или неполных данных, невозможности связаться с Покупателем, несвоевременного получения Товара Покупателем, задержек, вызванных действиями транспортных компаний, обстоятельств непреодолимой силы и технических сбоев, не зависящих от Продавца.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">13.4.</span> В случае если Товар изготовлен индивидуально под параметры Покупателя, сроки изготовления являются ориентировочными и могут корректироваться Продавцом с учетом производственного процесса.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">13.5.</span> Продавец не несет ответственности за неверно выбранный Покупателем размер, ошибки в указании школы, класса, иных параметров Заказа и последствия предоставления Покупателем недостоверных данных.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">13.6.</span> В случае неполучения Товара Покупателем в согласованные сроки Продавец вправе переместить Товар на хранение, потребовать компенсации фактически понесенных расходов на хранение и повторную отправку Товара, отменить Заказ в случаях, предусмотренных законодательством РФ.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">13.7.</span> Продавец не несет ответственности за действия банков, платежных систем, операторов связи, хостинг-провайдеров, транспортных компаний и иных третьих лиц.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">13.8.</span> Сайт и отдельные сервисы могут быть временно полностью или частично недоступны в связи с проведением технических, профилактических или иных работ.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">13.9.</span> Продавец несет ответственность в пределах, предусмотренных законодательством Российской Федерации. Если иное не установлено императивными нормами законодательства Российской Федерации, совокупный размер ответственности Продавца по требованиям, связанным с исполнением настоящего Договора, ограничивается стоимостью соответствующего Товара, в отношении которого возникли требования Покупателя. Продавец не несет ответственности за косвенные убытки, упущенную выгоду, потерю данных, репутационные потери и иные неблагоприятные последствия, не связанные непосредственно с недостатками Товара либо нарушением обязательств Продавца.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">13.10.</span> Признание судом недействительности отдельного положения настоящей Оферты не влечет недействительности остальных положений.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">13.11.</span> Не допускаются действия Покупателя, направленные на злоупотребление правами, получение необоснованной выгоды, создание искусственных оснований для предъявления претензий, взыскания неустоек, штрафов либо иных компенсаций. При выявлении признаков злоупотребления правом Продавец вправе использовать предусмотренные законодательством способы защиты своих прав и законных интересов.</Typography>
            </div>

            <div class="space-y-3">
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">14. Разрешение споров из договора</span></Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">14.1.</span> Все вопросы, претензии и обращения, возникающие у Покупателя в связи с исполнением настоящей Оферты, направляются Продавцу по телефону, указанному на сайте, по электронной почте, через форму обратной связи на Сайте, при обращении в ПВЗ Продавца.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">14.1.1.</span> Стороны признают юридическую силу документов, уведомлений, сообщений и согласований, направленных посредством электронной почты, SMS, мессенджеров, личного кабинета на Сайте и иных электронных каналов связи, указанных Покупателем при оформлении Заказа.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">14.2.</span> Стороны обязуются стремиться к разрешению всех споров и разногласий путем переговоров и добровольного урегулирования.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">14.3.</span> До обращения в суд Покупатель вправе направить Продавцу письменную претензию.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">14.4.</span> Продавец рассматривает претензию в сроки, предусмотренные законодательством Российской Федерации.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">14.5.</span> При недостижении соглашения спор подлежит рассмотрению в судебном порядке в соответствии с законодательством Российской Федерации.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">14.6.</span> Покупатель, являющийся потребителем, вправе предъявить иск по месту нахождения Продавца, по месту заключения или исполнения договора и в ином порядке, предусмотренном законодательством РФ о защите прав потребителей.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">14.7.</span> Споры с лицами, не являющимися потребителями, рассматриваются в соответствии с правилами подсудности, установленными законодательством Российской Федерации.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">14.8.</span> К отношениям сторон применяется законодательство Российской Федерации.</Typography>
            </div>

            <div class="space-y-3">
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">15. Форс-мажор</span></Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">15.1.</span> Стороны освобождаются от ответственности за полное или частичное неисполнение обязательств по Договору, если такое неисполнение явилось следствием обстоятельств непреодолимой силы, возникших после заключения Договора и находящихся вне разумного контроля Сторон.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">15.2.</span> К обстоятельствам непреодолимой силы относятся, включая, но не ограничиваясь: стихийные бедствия, пожары, наводнения, эпидемии, перебои электроснабжения, сбои в работе связи и интернет-инфраструктуры, действия органов государственной власти, ограничения перевозок, военные действия, мобилизация, массовые перебои поставок сырья и материалов и иные чрезвычайные обстоятельства.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">15.3.</span> Сторона, для которой возникли обстоятельства непреодолимой силы, обязана уведомить другую Сторону в разумный срок.</Typography>
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">15.4.</span> Срок исполнения обязательств продлевается соразмерно времени действия обстоятельств непреодолимой силы и их последствий.</Typography>
            </div>

            <div class="space-y-2">
              <Typography as="p" variant="body" class="leading-relaxed"><span class="font-semibold text-slate-900">16. Реквизиты Продавца и контактная информация</span></Typography>
              <Typography as="p" variant="body" class="leading-relaxed">Индивидуальный предприниматель Хан Эржэна Бадмажаповна</Typography>
              <Typography as="p" variant="body" class="leading-relaxed">Юр. адрес: 687000, Забайкальский край, пгт. Агинское, ул. Юннатская д.8а</Typography>
              <Typography as="p" variant="body" class="leading-relaxed">Почтовый адрес: 687000, Забайкальский край, пгт. Агинское, ул. Юннатская, д.8а</Typography>
              <Typography as="p" variant="body" class="leading-relaxed">ИНН: 800101362802</Typography>
              <Typography as="p" variant="body" class="leading-relaxed">КПП:</Typography>
              <Typography as="p" variant="body" class="leading-relaxed">БИК: 044525411</Typography>
              <Typography as="p" variant="body" class="leading-relaxed">Банк: ФИЛИАЛ «ЦЕНТРАЛЬНЫЙ» БАНКА ВТБ (ПАО)</Typography>
              <Typography as="p" variant="body" class="leading-relaxed">Корреспондентский счёт: 30101810145250000411</Typography>
              <Typography as="p" variant="body" class="leading-relaxed">Расчётный счёт: 40802810914160001821</Typography>
            </div>
          </div>
        </div>
      </section>
    </main>

    <Footer />
  </div>
</template>
