<script setup>
    import { StarIcon } from "@heroicons/vue/20/solid/index.js";
    import { BriefcaseIcon } from '@heroicons/vue/16/solid'
    import { UserGroupIcon } from "@heroicons/vue/16/solid";
    import { GlobeAltIcon } from "@heroicons/vue/16/solid";
    import { ClockIcon } from "@heroicons/vue/16/solid";
    import {BookmarkIcon} from "@heroicons/vue/16/solid";

    const props = defineProps({
      data: {
        type: Object,
        required: true
      }
    });

</script>

<template>
    <main class="max-w-2xl sm:px-6 lg:max-w-7xl lg:px-8">
        <div class="py-6 grid grid-cols-3 gap-4">
            <div class="relative w-full">
                <img :src="data.avatar" alt="mentor.profile.name" class="aspect-square w-full object-cover rounded-2xl overflow-hidden" />
                <div class="absolute top-2 right-2 bg-green-200 text-green-800 text-sm font-medium px-3 py-1 rounded-full shadow flex items-center gap-2">
                    <span class="inline-block w-2 h-2 bg-green-500 rounded-full"></span>
                    Є місця сьогодні
                </div>
            </div>

            <div class="col-span-2 flex flex-col justify-between">
                <div>
                    <div class="flex justify-between items-center">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <h1 class="text-2xl font-bold text-gray-900">{{ data.name }}</h1>
                            </div>
                            <p class="text-gray-600 mb-3">{{ data.title }}</p>
                        </div>

                        <div class="flex items-center gap-2 mb-4">
                            <div class="flex items-center">
                                <StarIcon v-for="rating in [0, 1, 2, 3, 4]" :key="rating" :class="[data.rating > rating ? 'text-yellow-400' : 'text-gray-200', 'size-5 shrink-0']" aria-hidden="true" />
                                <span class="ms-2 font-semibold text-gray-900">{{ data.rating }}</span>
                                <span class="ms-2 text-gray-500">({{ data.reviews }} reviews)</span>
                            </div>
                        </div>
                    </div>
                  <!-- Теги технологій -->
                    <div class="flex flex-wrap gap-2 mb-4">
                        <span v-for="(stack, index) in data.stacks" :key="index"  class="bg-blue-100 text-blue-800 text-sm font-medium px-3 py-1 rounded-full">{{ stack }}</span>
                    </div>

                    <div class="grid grid-cols-4 gap-4 mb-6">
                        <div class="flex items-center gap-2 text-gray-600">
                            <BriefcaseIcon class="w-8 h-8 text-gray-500" />
                            <div>
                                <div class="text-sm text-gray-500">Досвід</div>
                                <div class="font-medium">{{ data.experience }}</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 text-gray-600">
                            <UserGroupIcon class="w-8 h-8 text-gray-500" />
                            <div>
                                <div class="text-sm text-gray-500">Кількість студентів</div>
                                <div class="font-medium">{{ data.mentiCount }}</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 text-gray-600">
                            <GlobeAltIcon class="w-8 h-8 text-gray-500" />
                            <div>
                                <div class="text-sm text-gray-500">Мови</div>
                                <div v-for="(language, index) in data.languages" :key="index" class="font-medium">{{ language }}</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 text-gray-600">
                            <ClockIcon class="w-8 h-8 text-gray-500" />
                            <div>
                                <div class="text-sm text-gray-500">Відповідає</div>
                                <div class="font-medium">
                                    < ніж за 2 години</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                        <div class="flex items-baseline gap-1">
                            <span class="text-2xl font-bold text-blue-600">від {{ data.rate }} {{ data.currency }}</span>
                            <span class="text-gray-500">/за годину</span>
                        </div>
                        <div class="flex gap-3">
                            <button class="flex items-center gap-2 px-4 py-2 border border-blue-400 text-blue-600 rounded-lg hover:bg-blue-50 transition-colors">
                              <BookmarkIcon class="w-5 h-5 stroke-blue-600 fill-none"/>
                                Save Profile
                            </button>
                            <button class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium">
                                Book Consultation
                            </button>
                        </div>
                </div>
            </div>
        </div>
    </main>
</template>

<style scoped>

</style>