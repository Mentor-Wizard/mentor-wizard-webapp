<template>
    <LandingLayout>
        <div class="py-12 bg-gray-100">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-xs sm:rounded-lg">
                    <main class="mx-auto max-w-2xl sm:px-6 lg:max-w-7xl lg:px-8">
                        <div class="px-4 py-6 sm:px-6 lg:grid lg:grid-cols-12 lg:gap-x-8 lg:p-8">
                            <div class="sm:flex lg:col-span-7">
                                <img :src="mentor.avatar" :alt="mentor.name"
                                     class="aspect-square w-full shrink-0 rounded-lg object-cover sm:size-40"/>
                                <div class="mt-6 sm:mt-0 sm:ml-6">
                                    <h3 class="text-base font-medium text-gray-900">
                                        <p class="mt-2 text-sm font-medium text-gray-900">{{ mentor.name }}
                                            {{ mentor.last_name }}</p>
                                    </h3>
                                    <p class="mt-3 text-sm text-gray-500">{{ mentor.description }}</p>
                                </div>
                            </div>

                            <div class="mt-6 lg:col-span-5 lg:mt-0">
                                <dl class="grid grid-cols-2 gap-x-6 text-sm">
                                    <div>
                                        <dt class="font-medium text-gray-900">Rating</dt>
                                        <div class="flex items-center xl:col-span-1">
                                            <div class="mt-4">
                                                <h2 class="sr-only">Reviews</h2>
                                                <div class="flex items-center">
                                                    <p class="text-sm text-gray-700">
                                                        {{ mentor.rating }}
                                                    </p>
                                                    <div class="ml-1 flex items-center">
                                                        <StarIcon v-for="rating in [0, 1, 2, 3, 4]" :key="rating"
                                                                  :class="[mentor.rating > rating? 'text-yellow-400' : 'text-gray-200', 'size-5 shrink-0']"
                                                                  aria-hidden="true"/>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <dt class="font-medium text-gray-900">Contact</dt>
                                        <dd class="mt-3 flex space-x-3 text-gray-500">
                                            <a v-if="mentor.linkedin" :href="mentor.linkedin" target="_blank"
                                               rel="noopener noreferrer">
                                                <button
                                                    class="cursor-pointer w-10 h-10 flex items-center justify-center rounded-lg bg-white shadow-md shadow-gray-200 group transition-all duration-300">
                                                    <svg
                                                        class="rounded-md transition-all duration-300 group-hover:scale-110"
                                                        xmlns="http://www.w3.org/2000/svg" width="28" height="28"
                                                        viewBox="0 0 72 72" fill="none">
                                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                                              d="M14.6975 11C12.6561 11 11 12.6057 11 14.5838V57.4474C11 59.4257 12.6563 61.03 14.6975 61.03H57.3325C59.3747 61.03 61.03 59.4255 61.03 57.4468V14.5838C61.03 12.6057 59.3747 11 57.3325 11H14.6975ZM26.2032 30.345V52.8686H18.7167V30.345H26.2032ZM26.6967 23.3793C26.6967 25.5407 25.0717 27.2703 22.4615 27.2703L22.4609 27.2701H22.4124C19.8998 27.2701 18.2754 25.5405 18.2754 23.3791C18.2754 21.1686 19.9489 19.4873 22.5111 19.4873C25.0717 19.4873 26.6478 21.1686 26.6967 23.3793ZM37.833 52.8686H30.3471L30.3469 52.8694C30.3469 52.8694 30.4452 32.4588 30.3475 30.3458H37.8336V33.5339C38.8288 31.9995 40.6098 29.8169 44.5808 29.8169C49.5062 29.8169 53.1991 33.0363 53.1991 39.9543V52.8686H45.7133V40.8204C45.7133 37.7922 44.6293 35.7269 41.921 35.7269C39.8524 35.7269 38.6206 37.1198 38.0796 38.4653C37.8819 38.9455 37.833 39.6195 37.833 40.2918V52.8686Z"
                                                              fill="#006699"></path>
                                                    </svg>
                                                </button>
                                            </a>
                                            <a v-if="mentor.telegram" :href="mentor.telegram" target="_blank"
                                               rel="noopener noreferrer">
                                                <button
                                                    class="cursor-pointer w-10 h-10 flex items-center justify-center rounded-lg bg-white shadow-md shadow-gray-200 group transition-all duration-300">
                                                    <svg
                                                        class="rounded-md transition-all duration-300 group-hover:scale-110"
                                                        xmlns="http://www.w3.org/2000/svg" width="28" height="28"
                                                        viewBox="0 0 72 72" fill="none">
                                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                                              d="M61.03 36.015C61.03 49.8304 49.8304 61.03 36.015 61.03C22.1996 61.03 11 49.8304 11 36.015C11 22.1996 22.1996 11 36.015 11C49.8304 11 61.03 22.1996 61.03 36.015ZM38.4121 28.3392C34.1147 30.1955 21.7235 35.4671 21.7235 35.4671C18.7869 36.6551 20.5058 37.7688 20.5058 37.7688C20.5058 37.7688 23.0127 38.6599 25.1615 39.328C27.3103 39.9963 28.4563 39.2538 28.4563 39.2538C28.4563 39.2538 33.47 35.8384 38.5554 32.2002C42.1366 29.6757 41.2772 31.7547 40.4176 32.6457C38.5554 34.5762 35.4755 37.6204 32.897 40.0706C31.751 41.1101 32.324 42.001 32.8254 42.4465C34.2836 43.7256 37.718 46.0518 39.2773 47.1079C39.7093 47.4005 39.9974 47.5956 40.0596 47.6439C40.4176 47.941 42.4232 49.2774 43.6408 48.9804C44.8584 48.6834 45.0017 46.9757 45.0017 46.9757C45.0017 46.9757 45.9328 40.8873 46.7923 35.3186C46.9515 34.2252 47.1107 33.1548 47.2592 32.1567C47.645 29.5623 47.9582 27.4565 48.0099 26.7058C48.2248 24.1814 45.6463 25.2208 45.6463 25.2208C45.6463 25.2208 40.0596 27.5968 38.4121 28.3392Z"
                                                              fill="#34AADF"></path>
                                                    </svg>
                                                </button>
                                            </a>
                                            <a v-if="mentor.whatsapp" :href="mentor.whatsapp" target="_blank"
                                               rel="noopener noreferrer">
                                                <button
                                                    class="cursor-pointer w-10 h-10 flex items-center justify-center rounded-lg bg-white shadow-md shadow-gray-200 group transition-all duration-300">
                                                    <svg class="transition-all duration-300 group-hover:scale-110"
                                                         xmlns="http://www.w3.org/2000/svg" width="28" height="28"
                                                         viewBox="0 0 71 72" fill="none">
                                                        <path
                                                            d="M12.5762 56.8405L15.8608 44.6381C13.2118 39.8847 12.3702 34.3378 13.4904 29.0154C14.6106 23.693 17.6176 18.952 21.9594 15.6624C26.3012 12.3729 31.6867 10.7554 37.1276 11.1068C42.5685 11.4582 47.6999 13.755 51.5802 17.5756C55.4604 21.3962 57.8292 26.4844 58.2519 31.9065C58.6746 37.3286 57.1228 42.7208 53.8813 47.0938C50.6399 51.4668 45.9261 54.5271 40.605 55.7133C35.284 56.8994 29.7125 56.1318 24.9131 53.5513L12.5762 56.8405ZM25.508 48.985L26.2709 49.4365C29.7473 51.4918 33.8076 52.3423 37.8191 51.8555C41.8306 51.3687 45.5681 49.5719 48.4489 46.7452C51.3298 43.9185 53.1923 40.2206 53.7463 36.2279C54.3002 32.2351 53.5143 28.1717 51.5113 24.6709C49.5082 21.1701 46.4003 18.4285 42.6721 16.8734C38.9438 15.3184 34.8045 15.0372 30.8993 16.0736C26.994 17.11 23.5422 19.4059 21.0817 22.6035C18.6212 25.801 17.2903 29.7206 17.2963 33.7514C17.293 37.0937 18.2197 40.3712 19.9732 43.2192L20.4516 44.0061L18.6153 50.8167L25.508 48.985Z"
                                                            fill="#00D95F"></path>
                                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                                              d="M44.0259 36.8847C43.5787 36.5249 43.0549 36.2716 42.4947 36.1442C41.9344 36.0168 41.3524 36.0186 40.793 36.1495C39.9524 36.4977 39.4093 37.8134 38.8661 38.4713C38.7516 38.629 38.5833 38.7396 38.3928 38.7823C38.2024 38.8251 38.0028 38.797 37.8316 38.7034C34.7543 37.5012 32.1748 35.2965 30.5122 32.4475C30.3704 32.2697 30.3033 32.044 30.325 31.8178C30.3467 31.5916 30.4555 31.3827 30.6286 31.235C31.2344 30.6368 31.6791 29.8959 31.9218 29.0809C31.9756 28.1818 31.7691 27.2863 31.3269 26.5011C30.985 25.4002 30.3344 24.42 29.4518 23.6762C28.9966 23.472 28.4919 23.4036 27.9985 23.4791C27.5052 23.5546 27.0443 23.7709 26.6715 24.1019C26.0242 24.6589 25.5104 25.3537 25.168 26.135C24.8256 26.9163 24.6632 27.7643 24.6929 28.6165C24.6949 29.0951 24.7557 29.5716 24.8739 30.0354C25.1742 31.1497 25.636 32.2144 26.2447 33.1956C26.6839 33.9473 27.163 34.6749 27.6801 35.3755C29.3607 37.6767 31.4732 39.6305 33.9003 41.1284C35.1183 41.8897 36.42 42.5086 37.7799 42.973C39.1924 43.6117 40.752 43.8568 42.2931 43.6824C43.1711 43.5499 44.003 43.2041 44.7156 42.6755C45.4281 42.1469 45.9995 41.4518 46.3795 40.6512C46.6028 40.1675 46.6705 39.6269 46.5735 39.1033C46.3407 38.0327 44.9053 37.4007 44.0259 36.8847Z"
                                                              fill="#00D95F"></path>
                                                    </svg>
                                                </button>
                                            </a>
                                        </dd>
                                        <dd class="mt-4 text-gray-500 flex items-center gap-2">
                                            <p>{{ mentor.phone }}</p>
                                            <button
                                                @click="copyPhone(mentor.phone)"
                                                class="cursor-pointer text-blue-500 hover:text-blue-700 transition"
                                                title="Copy phone"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                                                     viewBox="0 0 24 24" stroke="currentColor">
                                                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"
                                                          stroke-width="2" stroke="currentColor"/>
                                                    <rect x="3" y="3" width="13" height="13" rx="2" ry="2"
                                                          stroke-width="2" stroke="currentColor"/>
                                                </svg>
                                            </button>
                                        </dd>
                                    </div>
                                </dl>
                            </div>
                        </div>
                        <div class="px-4 py-6 sm:px-6 lg:grid lg:grid-cols-12 lg:gap-x-8 lg:p-8">
                            <div class="lg:col-span-12">
                                <h2 class="text-lg font-bold mb-4">Reviews</h2>
                                <div v-for="review in reviews.data" :key="review.id" class="w-full pb-5">
                                    <!-- Мобільна версія -->
                                    <div class="sm:hidden">
                                        <!-- Верхній блок: фото зліва, ім'я та рейтинг справа -->
                                        <div class="flex gap-3 mb-3">
                                            <!-- Фото зліва -->
                                            <img :src="review.menti.profile.avatar ?? defaultAvatar" :alt="review.menti.profile.name"
                                                 class="aspect-square w-12 h-12 rounded-lg object-cover shrink-0"/>

                                            <!-- Ім'я та рейтинг справа -->
                                            <div class="flex flex-col justify-center">
                                                <h3 class="text-sm font-medium text-gray-900 mb-1">
                                                    {{ review.menti.profile.name }} {{ review.menti.profile.last_name }}
                                                </h3>
                                                <div class="flex items-center">
                                                    <p class="text-sm text-gray-700 mr-2">
                                                        {{ review.rating }}
                                                    </p>
                                                    <div class="flex">
                                                        <StarIcon v-for="rating in [0, 1, 2, 3, 4]" :key="rating"
                                                                  :class="[review.rating > rating ? 'text-yellow-400' : 'text-gray-200', 'size-4 shrink-0']"
                                                                  aria-hidden="true"/>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Коментар під блоком -->
                                        <p class="text-sm text-gray-500">{{ review.comment }}</p>
                                    </div>

                                    <!-- Десктопна версія: горизонтальний flex -->
                                    <div class="hidden sm:flex gap-4 w-full">
                                        <!-- Фото зліва -->
                                        <div class="shrink-0">
                                            <img :src="review.menti.profile.avatar ?? defaultAvatar" :alt="review.menti.profile.name"
                                                 class="aspect-square w-20 h-20 rounded-lg object-cover"/>
                                        </div>

                                        <!-- Контент посередині -->
                                        <div class="flex-1 min-w-0">
                                            <h3 class="text-sm font-medium text-gray-900 mb-1">
                                                {{ review.menti.profile.name }} {{ review.menti.profile.last_name }}
                                            </h3>
                                            <p class="text-sm text-gray-500">{{ review.comment }}</p>
                                        </div>

                                        <!-- Рейтинг справа -->
                                        <div class="flex items-center shrink-0">
                                            <p class="text-sm text-gray-700 mr-2">
                                                {{ review.rating }}
                                            </p>
                                            <div class="flex">
                                                <StarIcon v-for="rating in [0, 1, 2, 3, 4]" :key="rating"
                                                          :class="[review.rating > rating ? 'text-yellow-400' : 'text-gray-200', 'size-5 shrink-0']"
                                                          aria-hidden="true"/>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <Pagination :data="reviews" />
                            </div>
                        </div>
                    </main>
                </div>
            </div>
        </div>
    </LandingLayout>
</template>

<script setup>

import LandingLayout from "@/Layouts/LandingLayout.vue";
import {usePage} from '@inertiajs/vue3';
import {StarIcon} from '@heroicons/vue/20/solid'
import Pagination from '@/Components/Pagination.vue'
import {computed} from "vue";

const mentor = usePage().props.mentor
const reviews = computed(() => usePage().props.reviews)
const defaultAvatar = usePage().props.defaultAvatar

function copyPhone(phone) {
    navigator.clipboard.writeText(phone)
        .then(() => {
            console.log('Copied:', phone)
        })
        .catch(err => {
            console.error('Error:', err)
        });
}
</script>
