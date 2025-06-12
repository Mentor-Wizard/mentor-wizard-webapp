<template>
    <LandingLayout>
        <div class="py-12 bg-gray-100">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-xs sm:rounded-lg">
                     <main class="mx-auto max-w-2xl pt-8 pb-24 sm:px-6 sm:pt-16 lg:max-w-7xl lg:px-8">
        <div class="space-y-2 px-4 sm:flex sm:items-baseline sm:justify-between sm:space-y-0 sm:px-0">
            <div class="flex sm:items-baseline sm:space-x-4">
                <h1 class="text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl">Order #54879</h1>
                <a href="#" class="hidden text-sm font-medium text-indigo-600 hover:text-indigo-500 sm:block">
                    View invoice
                    <span aria-hidden="true"> &rarr;</span>
                </a>
            </div>
            <p class="text-sm text-gray-600">Order placed <time datetime="2021-03-22" class="font-medium text-gray-900">March 22, 2021</time></p>
            <a href="#" class="text-sm font-medium text-indigo-600 hover:text-indigo-500 sm:hidden">
                View invoice
                <span aria-hidden="true"> &rarr;</span>
            </a>
        </div>

        <!-- Products -->
        <section aria-labelledby="products-heading" class="mt-6">
            <div class="space-y-8">
                <div class="px-4 py-6 sm:px-6 lg:grid lg:grid-cols-12 lg:gap-x-8 lg:p-8">
                    <div class="sm:flex lg:col-span-7">
                        <img :src="product.imageSrc" :alt="product.imageAlt" class="aspect-square w-full shrink-0 rounded-lg object-cover sm:size-40" />

                        <div class="mt-6 sm:mt-0 sm:ml-6">
                            <h3 class="text-base font-medium text-gray-900">
                                <a :href="product.href">{{ product.name }}</a>
                            </h3>
                            <p class="mt-2 text-sm font-medium text-gray-900">${{ product.price }}</p>
                            <p class="mt-3 text-sm text-gray-500">{{ product.description }}</p>
                        </div>
                    </div>

                    <div class="mt-6 lg:col-span-5 lg:mt-0">
                        <dl class="grid grid-cols-2 gap-x-6 text-sm">
                            <div>
                                <dt class="font-medium text-gray-900">Delivery address</dt>
                                <dd class="mt-3 text-gray-500">
                                    <span class="block">{{ product.address[0] }}</span>
                                    <span class="block">{{ product.address[1] }}</span>
                                    <span class="block">{{ product.address[2] }}</span>
                                </dd>
                            </div>
                            <div>
                                <dt class="font-medium text-gray-900">Shipping updates</dt>
                                <dd class="mt-3 space-y-3 text-gray-500">
                                    <p>{{ product.email }}</p>
                                    <p>{{ product.phone }}</p>
                                    <button type="button" class="font-medium text-indigo-600 hover:text-indigo-500">Edit</button>
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        </section>

        <!-- Billing -->

    </main>
                </div>
            </div>
        </div>
   </LandingLayout>
</template>

<script setup>
import { ref } from 'vue'
import LandingLayout from "@/Layouts/LandingLayout.vue";
const product = {
    id: 1,
    name: 'Nomad Tumbler',
    description:
        'This durable and portable insulated tumbler will keep your beverage at the perfect temperature during your next adventure.',
    href: '#',
    price: '35.00',
    status: 'Preparing to ship',
    step: 1,
    date: 'March 24, 2021',
    datetime: '2021-03-24',
    address: ['Floyd Miles', '7363 Cynthia Pass', 'Toronto, ON N3Y 4H8'],
    email: 'f•••@example.com',
    phone: '1•••••••••40',
    imageSrc: 'https://tailwindcss.com/plus-assets/img/ecommerce-images/confirmation-page-03-product-01.jpg',
    imageAlt: 'Insulated bottle with white base and black snap lid.',
}
</script>
