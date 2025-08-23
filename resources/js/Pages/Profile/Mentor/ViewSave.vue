<script setup>
import LandingLayout from '@/Layouts/LandingLayout.vue';
import { usePage } from '@inertiajs/vue3';
import { StarIcon } from '@heroicons/vue/20/solid';
import Pagination from '@/Components/Navigation/Pagination.vue';
import { computed } from 'vue';
import LinkedinButton from '@/Components/UI/Button/LinkedinButton.vue';
import TelegramButton from '@/Components/UI/Button/TelegramButton.vue';
import WhatsappButton from '@/Components/UI/Button/WhatsappButton.vue';

const mentor = usePage().props.mentor;
const reviews = computed(() => usePage().props.reviews);
const defaultAvatar = usePage().props.defaultAvatar;
</script>

<template>
  <LandingLayout>
    <div class="bg-gray-100 py-12">
      <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        <div class="overflow-hidden bg-white shadow-xs sm:rounded-lg">
          <main class="mx-auto max-w-2xl sm:px-6 lg:max-w-7xl lg:px-8">
            <div
              class="px-4 py-6 sm:px-6 lg:grid lg:grid-cols-12 lg:gap-x-8 lg:p-8"
            >
              <div class="sm:flex lg:col-span-7">
                <img
                  :src="mentor.profile.avatar"
                  :alt="mentor.profile.name"
                  class="aspect-square w-full shrink-0 rounded-lg object-cover pt-3 sm:size-40"
                />
                <div class="mt-6 sm:mt-0 sm:ml-6">
                  <h3 class="text-base font-medium text-gray-900">
                    <p class="mt-2 text-sm font-medium text-gray-900">
                      {{ mentor.profile.name }} {{ mentor.profile.last_name }}
                    </p>
                  </h3>
                  <p class="mt-3 text-sm text-gray-500">
                    {{ mentor.profile.description }}
                  </p>
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
                            <StarIcon
                              v-for="rating in [0, 1, 2, 3, 4]"
                              :key="rating"
                              :class="[
                                mentor.rating > rating ?
                                  'text-yellow-400'
                                : 'text-gray-200',
                                'size-5 shrink-0',
                              ]"
                              aria-hidden="true"
                            />
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div>
                    <dt class="font-medium text-gray-900">Contact</dt>
                    <dd class="mt-3 flex space-x-3 text-gray-500">
                      <LinkedinButton :url="mentor.profile.linkedin" />
                      <TelegramButton :url="mentor.profile.telegram" />
                      <WhatsappButton :url="mentor.profile.whatsapp" />
                    </dd>
                    <dd class="mt-4 flex items-center gap-2 text-gray-500">
                      <a :href="`tel:${mentor.profile.phone}`">{{
                        mentor.profile.phone
                      }}</a>
                    </dd>
                  </div>
                </dl>
              </div>
            </div>
            <div
              class="px-4 py-6 sm:px-6 lg:grid lg:grid-cols-12 lg:gap-x-8 lg:p-8"
            >
              <div v-if="reviews.data.length" class="lg:col-span-12">
                <h2 class="mb-4 text-lg font-bold">Reviews</h2>
                <div
                  v-for="review in reviews.data"
                  :key="review.id"
                  class="w-full pb-5"
                >
                  <div class="sm:hidden">
                    <div class="mb-3 flex gap-3">
                      <img
                        :src="review.menti.profile.avatar ?? defaultAvatar"
                        :alt="review.menti.profile.name"
                        class="aspect-square h-12 w-12 shrink-0 rounded-lg object-cover"
                      />

                      <div class="flex flex-col justify-center">
                        <h3 class="mb-1 text-sm font-medium text-gray-900">
                          {{ review.menti.profile.name }}
                          {{ review.menti.profile.last_name }}
                        </h3>
                        <div class="flex items-center">
                          <p class="mr-2 text-sm text-gray-700">
                            {{ review.rating }}
                          </p>
                          <div class="flex">
                            <StarIcon
                              v-for="rating in [0, 1, 2, 3, 4]"
                              :key="rating"
                              :class="[
                                review.rating > rating ?
                                  'text-yellow-400'
                                : 'text-gray-200',
                                'size-4 shrink-0',
                              ]"
                              aria-hidden="true"
                            />
                          </div>
                        </div>
                      </div>
                    </div>

                    <p class="text-sm text-gray-500">
                      {{ review.comment }}
                    </p>
                  </div>

                  <div class="hidden w-full gap-4 sm:flex">
                    <div class="shrink-0">
                      <img
                        :src="review.menti.profile.avatar ?? defaultAvatar"
                        :alt="review.menti.profile.name"
                        class="aspect-square h-20 w-20 rounded-lg object-cover"
                      />
                    </div>

                    <div class="min-w-0 flex-1">
                      <h3 class="mb-1 text-sm font-medium text-gray-900">
                        {{ review.menti.profile.name }}
                        {{ review.menti.profile.last_name }}
                      </h3>
                      <p class="text-sm text-gray-500">
                        {{ review.comment }}
                      </p>
                    </div>

                    <div class="flex shrink-0 items-center">
                      <p class="mr-2 text-sm text-gray-700">
                        {{ review.rating }}
                      </p>
                      <div class="flex">
                        <StarIcon
                          v-for="rating in [0, 1, 2, 3, 4]"
                          :key="rating"
                          :class="[
                            review.rating > rating ?
                              'text-yellow-400'
                            : 'text-gray-200',
                            'size-5 shrink-0',
                          ]"
                          aria-hidden="true"
                        />
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
