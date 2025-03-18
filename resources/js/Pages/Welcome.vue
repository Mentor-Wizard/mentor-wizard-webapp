<script setup>
import {Head} from "@inertiajs/vue3";
import LandingLayout from "@/Layouts/LandingLayout.vue";
import {ArrowPathIcon, CloudArrowUpIcon, FingerPrintIcon, LockClosedIcon,} from '@heroicons/vue/24/outline'
import {CheckIcon} from '@heroicons/vue/20/solid'

const features = [
  {
    name: 'Push to deploy',
    description:
        'Morbi viverra dui mi arcu sed. Tellus semper adipiscing suspendisse semper morbi. Odio urna massa nunc massa.',
    icon: CloudArrowUpIcon,
  },
  {
    name: 'SSL certificates',
    description:
        'Sit quis amet rutrum tellus ullamcorper ultricies libero dolor eget. Sem sodales gravida quam turpis enim lacus amet.',
    icon: LockClosedIcon,
  },
  {
    name: 'Simple queues',
    description:
        'Quisque est vel vulputate cursus. Risus proin diam nunc commodo. Lobortis auctor congue commodo diam neque.',
    icon: ArrowPathIcon,
  },
  {
    name: 'Advanced security',
    description:
        'Arcu egestas dolor vel iaculis in ipsum mauris. Tincidunt mattis aliquet hac quis. Id hac maecenas ac donec pharetra eget.',
    icon: FingerPrintIcon,
  },
]
const tiers = [
  {
    name: 'Freelancer',
    id: 'tier-freelancer',
    href: '#',
    priceMonthly: '$19',
    description: 'The essentials to provide your best work for clients.',
    features: ['5 products', 'Up to 1,000 subscribers', 'Basic analytics', '48-hour support response time'],
    mostPopular: false,
  },
  {
    name: 'Startup',
    id: 'tier-startup',
    href: '#',
    priceMonthly: '$49',
    description: 'A plan that scales with your rapidly growing business.',
    features: [
      '25 products',
      'Up to 10,000 subscribers',
      'Advanced analytics',
      '24-hour support response time',
      'Marketing automations',
    ],
    mostPopular: true,
  },
  {
    name: 'Enterprise',
    id: 'tier-enterprise',
    href: '#',
    priceMonthly: '$99',
    description: 'Dedicated support and infrastructure for your company.',
    features: [
      'Unlimited products',
      'Unlimited subscribers',
      'Advanced analytics',
      '1-hour, dedicated support response time',
      'Marketing automations',
    ],
    mostPopular: false,
  },
]
const faqs = [
  {
    id: 1,
    question: "What's the best thing about Switzerland?",
    answer:
        "I don't know, but the flag is a big plus. Lorem ipsum dolor sit amet consectetur adipisicing elit. Quas cupiditate laboriosam fugiat.",
  },
]
</script>

<template>
  <Head title="Welcome"/>
  <LandingLayout>
    <!-- Hero section -->
    <div class="relative">
      <div class="absolute inset-x-0 -top-40 -z-10 transform-gpu overflow-hidden blur-3xl sm:-top-80"
           aria-hidden="true">
        <div
            class="relative left-[calc(50%-11rem)] aspect-1155/678 w-[36.125rem] -translate-x-1/2 rotate-[30deg] bg-linear-to-tr from-[#ff80b5] to-[#9089fc] opacity-30 sm:left-[calc(50%-30rem)] sm:w-[72.1875rem]"
            style="clip-path: polygon(74.1% 44.1%, 100% 61.6%, 97.5% 26.9%, 85.5% 0.1%, 80.7% 2%, 72.5% 32.5%, 60.2% 62.4%, 52.4% 68.1%, 47.5% 58.3%, 45.2% 34.5%, 27.5% 76.7%, 0.1% 64.9%, 17.9% 100%, 27.6% 76.8%, 76.1% 97.7%, 74.1% 44.1%)"/>
      </div>
      <div class="py-24 sm:py-32 lg:pb-40">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
          <div class="mx-auto max-w-2xl text-center">
            <h1 class="text-5xl font-semibold tracking-tight text-balance text-gray-900 sm:text-7xl">Deploy to the cloud
              with confidence</h1>
            <p class="mt-8 text-lg font-medium text-pretty text-gray-500 sm:text-xl/8">Anim aute id magna aliqua ad ad
              non deserunt sunt. Qui irure qui lorem cupidatat commodo. Elit sunt amet fugiat veniam occaecat.</p>
            <div class="mt-10 flex items-center justify-center gap-x-6">
              <a href="#"
                 class="rounded-md bg-indigo-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Get
                started</a>
              <a href="#" class="text-sm/6 font-semibold text-gray-900">Learn more <span aria-hidden="true">→</span></a>
            </div>
          </div>
          <div class="mt-16 flow-root sm:mt-24">
            <div
                class="-m-2 rounded-xl bg-gray-900/5 p-2 ring-1 ring-gray-900/10 ring-inset lg:-m-4 lg:rounded-2xl lg:p-4">
              <img src="https://tailwindcss.com/plus-assets/img/component-images/project-app-screenshot.png"
                   alt="App screenshot" width="2432" height="1442"
                   class="rounded-md ring-1 shadow-2xl ring-gray-900/10"/>
            </div>
          </div>
        </div>
      </div>
      <div
          class="absolute inset-x-0 top-[calc(100%-13rem)] -z-10 transform-gpu overflow-hidden blur-3xl sm:top-[calc(100%-30rem)]"
          aria-hidden="true">
        <div
            class="relative left-[calc(50%+3rem)] aspect-1155/678 w-[36.125rem] -translate-x-1/2 bg-linear-to-tr from-[#ff80b5] to-[#9089fc] opacity-30 sm:left-[calc(50%+36rem)] sm:w-[72.1875rem]"
            style="clip-path: polygon(74.1% 44.1%, 100% 61.6%, 97.5% 26.9%, 85.5% 0.1%, 80.7% 2%, 72.5% 32.5%, 60.2% 62.4%, 52.4% 68.1%, 47.5% 58.3%, 45.2% 34.5%, 27.5% 76.7%, 0.1% 64.9%, 17.9% 100%, 27.6% 76.8%, 76.1% 97.7%, 74.1% 44.1%)"/>
      </div>
    </div>

    <!-- Logo cloud -->
    <div class="mx-auto max-w-7xl px-6 lg:px-8">
      <div
          class="mx-auto grid max-w-lg grid-cols-4 items-center gap-x-8 gap-y-12 sm:max-w-xl sm:grid-cols-6 sm:gap-x-10 sm:gap-y-14 lg:mx-0 lg:max-w-none lg:grid-cols-5">
        <img class="col-span-2 max-h-12 w-full object-contain lg:col-span-1"
             src="https://tailwindcss.com/plus-assets/img/logos/158x48/transistor-logo-gray-900.svg" alt="Transistor"
             width="158" height="48"/>
        <img class="col-span-2 max-h-12 w-full object-contain lg:col-span-1"
             src="https://tailwindcss.com/plus-assets/img/logos/158x48/reform-logo-gray-900.svg" alt="Reform"
             width="158" height="48"/>
        <img class="col-span-2 max-h-12 w-full object-contain lg:col-span-1"
             src="https://tailwindcss.com/plus-assets/img/logos/158x48/tuple-logo-gray-900.svg" alt="Tuple" width="158"
             height="48"/>
        <img class="col-span-2 max-h-12 w-full object-contain sm:col-start-2 lg:col-span-1"
             src="https://tailwindcss.com/plus-assets/img/logos/158x48/savvycal-logo-gray-900.svg" alt="SavvyCal"
             width="158" height="48"/>
        <img class="col-span-2 col-start-2 max-h-12 w-full object-contain sm:col-start-auto lg:col-span-1"
             src="https://tailwindcss.com/plus-assets/img/logos/158x48/statamic-logo-gray-900.svg" alt="Statamic"
             width="158" height="48"/>
      </div>
      <div class="mt-16 flex justify-center">
        <p class="relative rounded-full px-4 py-1.5 text-sm/6 text-gray-600 ring-1 ring-gray-900/10 ring-inset hover:ring-gray-900/20">
          <span
              class="hidden md:inline">Transistor saves up to $40,000 per year, per employee by working with us.</span>
          <a href="#" class="font-semibold text-indigo-600"><span class="absolute inset-0" aria-hidden="true"/> Read our
            case study <span aria-hidden="true">&rarr;</span></a>
        </p>
      </div>
    </div>

    <!-- Feature section -->
    <div class="mx-auto mt-32 max-w-7xl px-6 sm:mt-56 lg:px-8">
      <div class="mx-auto max-w-2xl lg:text-center">
        <h2 class="text-base/7 font-semibold text-indigo-600">Deploy faster</h2>
        <p class="mt-2 text-4xl font-semibold tracking-tight text-pretty text-gray-900 sm:text-5xl lg:text-balance">
          Everything you need to deploy your app</p>
        <p class="mt-6 text-lg/8 text-pretty text-gray-600">Quis tellus eget adipiscing convallis sit sit eget aliquet
          quis. Suspendisse eget egestas a elementum pulvinar et feugiat blandit at. In mi viverra elit nunc.</p>
      </div>
      <div class="mx-auto mt-16 max-w-2xl sm:mt-20 lg:mt-24 lg:max-w-4xl">
        <dl class="grid max-w-xl grid-cols-1 gap-x-8 gap-y-10 lg:max-w-none lg:grid-cols-2 lg:gap-y-16">
          <div v-for="feature in features" :key="feature.name" class="relative pl-16">
            <dt class="text-base/7 font-semibold text-gray-900">
              <div class="absolute top-0 left-0 flex size-10 items-center justify-center rounded-lg bg-indigo-600">
                <component :is="feature.icon" class="size-6 text-white" aria-hidden="true"/>
              </div>
              {{ feature.name }}
            </dt>
            <dd class="mt-2 text-base/7 text-gray-600">{{ feature.description }}</dd>
          </div>
        </dl>
      </div>
    </div>

    <!-- Testimonial section -->
    <div class="mx-auto mt-32 max-w-7xl sm:mt-56 sm:px-6 lg:px-8">
      <div
          class="relative overflow-hidden bg-gray-900 px-6 py-20 shadow-xl sm:rounded-3xl sm:px-10 sm:py-24 md:px-12 lg:px-20">
        <img class="absolute inset-0 size-full object-cover brightness-150 saturate-0"
             src="https://images.unsplash.com/photo-1601381718415-a05fb0a261f3?ixid=MXwxMjA3fDB8MHxwcm9maWxlLXBhZ2V8ODl8fHxlbnwwfHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=1216&q=80"
             alt=""/>
        <div class="absolute inset-0 bg-gray-900/90 mix-blend-multiply"/>
        <div class="absolute -top-56 -left-80 transform-gpu blur-3xl" aria-hidden="true">
          <div class="aspect-1097/845 w-[68.5625rem] bg-linear-to-r from-[#ff4694] to-[#776fff] opacity-[0.45]"
               style="clip-path: polygon(74.1% 44.1%, 100% 61.6%, 97.5% 26.9%, 85.5% 0.1%, 80.7% 2%, 72.5% 32.5%, 60.2% 62.4%, 52.4% 68.1%, 47.5% 58.3%, 45.2% 34.5%, 27.5% 76.7%, 0.1% 64.9%, 17.9% 100%, 27.6% 76.8%, 76.1% 97.7%, 74.1% 44.1%)"/>
        </div>
        <div class="hidden md:absolute md:bottom-16 md:left-[50rem] md:block md:transform-gpu md:blur-3xl"
             aria-hidden="true">
          <div class="aspect-1097/845 w-[68.5625rem] bg-linear-to-r from-[#ff4694] to-[#776fff] opacity-25"
               style="clip-path: polygon(74.1% 44.1%, 100% 61.6%, 97.5% 26.9%, 85.5% 0.1%, 80.7% 2%, 72.5% 32.5%, 60.2% 62.4%, 52.4% 68.1%, 47.5% 58.3%, 45.2% 34.5%, 27.5% 76.7%, 0.1% 64.9%, 17.9% 100%, 27.6% 76.8%, 76.1% 97.7%, 74.1% 44.1%)"/>
        </div>
        <div class="relative mx-auto max-w-2xl lg:mx-0">
          <img class="h-12 w-auto" src="https://tailwindcss.com/plus-assets/img/logos/workcation-logo-white.svg"
               alt=""/>
          <figure>
            <blockquote class="mt-6 text-lg font-semibold text-white sm:text-xl/8">
              <p>“Amet amet eget scelerisque tellus sit neque faucibus non eleifend. Integer eu praesent at a. Ornare
                arcu gravida natoque erat et cursus tortor consequat at. Vulputate gravida sociis enim nullam ultricies
                habitant malesuada lorem ac.”</p>
            </blockquote>
            <figcaption class="mt-6 text-base text-white">
              <div class="font-semibold">Judith Black</div>
              <div class="mt-1">CEO of Tuple</div>
            </figcaption>
          </figure>
        </div>
      </div>
    </div>

    <!-- Pricing section -->
    <div class="py-24 sm:pt-48">
      <div class="mx-auto max-w-7xl px-6 lg:px-8">
        <div class="mx-auto max-w-2xl lg:text-center">
          <h2 class="text-base/7 font-semibold text-indigo-600">Pricing</h2>
          <p class="mt-2 text-4xl font-semibold tracking-tight text-pretty text-gray-900 sm:text-5xl lg:text-balance">
            Pricing that grows with you</p>
          <p class="mt-6 text-lg/8 text-pretty text-gray-600">Quis tellus eget adipiscing convallis sit sit eget aliquet
            quis. Suspendisse eget egestas a elementum pulvinar et feugiat blandit at. In mi viverra elit nunc.</p>
        </div>
        <div
            class="isolate mx-auto mt-16 grid max-w-md grid-cols-1 gap-y-8 sm:mt-20 lg:mx-0 lg:max-w-none lg:grid-cols-3">
          <div v-for="(tier, tierIdx) in tiers" :key="tier.id"
               :class="[tier.mostPopular ? 'lg:z-10 lg:rounded-b-none' : 'lg:mt-8', tierIdx === 0 ? 'lg:rounded-r-none' : '', tierIdx === tiers.length - 1 ? 'lg:rounded-l-none' : '', 'flex flex-col justify-between rounded-3xl bg-white p-8 ring-1 ring-gray-200 xl:p-10']">
            <div>
              <div class="flex items-center justify-between gap-x-4">
                <h3 :id="tier.id"
                    :class="[tier.mostPopular ? 'text-indigo-600' : 'text-gray-900', 'text-lg/8 font-semibold']">
                  {{ tier.name }}</h3>
                <p v-if="tier.mostPopular"
                   class="rounded-full bg-indigo-600/10 px-2.5 py-1 text-xs/5 font-semibold text-indigo-600">Most
                  popular</p>
              </div>
              <p class="mt-4 text-sm/6 text-gray-600">{{ tier.description }}</p>
              <p class="mt-6 flex items-baseline gap-x-1">
                <span class="text-4xl font-semibold tracking-tight text-gray-900">{{ tier.priceMonthly }}</span>
                <span class="text-sm/6 font-semibold text-gray-600">/month</span>
              </p>
              <ul role="list" class="mt-8 space-y-3 text-sm/6 text-gray-600">
                <li v-for="feature in tier.features" :key="feature" class="flex gap-x-3">
                  <CheckIcon class="h-6 w-5 flex-none text-indigo-600" aria-hidden="true"/>
                  {{ feature }}
                </li>
              </ul>
            </div>
            <a :href="tier.href" :aria-describedby="tier.id"
               :class="[tier.mostPopular ? 'bg-indigo-600 text-white shadow-xs hover:bg-indigo-500' : 'text-indigo-600 ring-1 ring-indigo-200 ring-inset hover:ring-indigo-300', 'mt-8 block rounded-md px-3 py-2 text-center text-sm/6 font-semibold focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600']">Buy
              plan</a>
          </div>
        </div>
      </div>
    </div>

    <!-- FAQs -->
    <div class="mx-auto max-w-2xl px-6 pb-8 sm:pt-12 sm:pb-24 lg:max-w-7xl lg:px-8 lg:pb-32">
      <h2 class="text-4xl font-semibold tracking-tight text-gray-900 sm:text-5xl">Frequently asked questions</h2>
      <dl class="mt-20 divide-y divide-gray-900/10">
        <div v-for="faq in faqs" :key="faq.id" class="py-8 first:pt-0 last:pb-0 lg:grid lg:grid-cols-12 lg:gap-8">
          <dt class="text-base/7 font-semibold text-gray-900 lg:col-span-5">{{ faq.question }}</dt>
          <dd class="mt-4 lg:col-span-7 lg:mt-0">
            <p class="text-base/7 text-gray-600">{{ faq.answer }}</p>
          </dd>
        </div>
      </dl>
    </div>

    <!-- CTA section -->
    <div class="relative -z-10 mt-32 px-6 lg:px-8">
      <div
          class="absolute inset-x-0 top-1/2 -z-10 flex -translate-y-1/2 transform-gpu justify-center overflow-hidden blur-3xl sm:top-auto sm:right-[calc(50%-6rem)] sm:bottom-0 sm:translate-y-0 sm:transform-gpu sm:justify-end"
          aria-hidden="true">
        <div class="aspect-1108/632 w-[69.25rem] flex-none bg-linear-to-r from-[#ff80b5] to-[#9089fc] opacity-25"
             style="clip-path: polygon(73.6% 48.6%, 91.7% 88.5%, 100% 53.9%, 97.4% 18.1%, 92.5% 15.4%, 75.7% 36.3%, 55.3% 52.8%, 46.5% 50.9%, 45% 37.4%, 50.3% 13.1%, 21.3% 36.2%, 0.1% 0.1%, 5.4% 49.1%, 21.4% 36.4%, 58.9% 100%, 73.6% 48.6%)"/>
      </div>
      <div class="mx-auto max-w-2xl text-center">
        <h2 class="text-4xl font-semibold tracking-tight text-balance text-gray-900 sm:text-5xl">Boost your
          productivity. Start using our app today.</h2>
        <p class="mx-auto mt-6 max-w-xl text-lg/8 text-pretty text-gray-600">Incididunt sint fugiat pariatur cupidatat
          consectetur sit cillum anim id veniam aliqua proident excepteur commodo do ea.</p>
        <div class="mt-10 flex items-center justify-center gap-x-6">
          <a href="#"
             class="rounded-md bg-indigo-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Get
            started</a>
          <a href="#" class="text-sm/6 font-semibold text-gray-900">Learn more <span aria-hidden="true">→</span></a>
        </div>
      </div>
      <div
          class="absolute top-full right-0 left-1/2 -z-10 hidden -translate-y-1/2 transform-gpu overflow-hidden blur-3xl sm:block"
          aria-hidden="true">
        <div class="aspect-1155/678 w-[72.1875rem] bg-linear-to-tr from-[#ff80b5] to-[#9089fc] opacity-30"
             style="clip-path: polygon(74.1% 44.1%, 100% 61.6%, 97.5% 26.9%, 85.5% 0.1%, 80.7% 2%, 72.5% 32.5%, 60.2% 62.4%, 52.4% 68.1%, 47.5% 58.3%, 45.2% 34.5%, 27.5% 76.7%, 0.1% 64.9%, 17.9% 100%, 27.6% 76.8%, 76.1% 97.7%, 74.1% 44.1%)"/>
      </div>
    </div>
  </LandingLayout>
</template>
