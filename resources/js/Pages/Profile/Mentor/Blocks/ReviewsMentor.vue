<script setup>
    import { StarIcon  } from "@heroicons/vue/20/solid/index.js";
    import {ref} from "vue";

    const model = defineModel({
      type: [Array, null],
      required: true
    })

    const props = defineProps({
      slug: {
        type: String,
        required: true
      },
      statistic: {
        type: Object,
        required: true
      },
    });
    const totalCount = props.statistic.star_5 + props.statistic.star_4 + props.statistic.star_3 + props.statistic.star_2 + props.statistic.star_1
    const ratingTotal = (
      (5 * props.statistic.star_5 +
        4 * props.statistic.star_4 +
        3 * props.statistic.star_3 +
        2 * props.statistic.star_2 +
        props.statistic.star_1) / totalCount
    ).toFixed(1);

    const counts = [
      { rating: 5, count: props.statistic.star_5 },
      { rating: 4, count: props.statistic.star_4 },
      { rating: 3, count: props.statistic.star_3 },
      { rating: 2, count: props.statistic.star_2 },
      { rating: 1, count: props.statistic.star_1 },
    ]

    const page = ref(0)
    const fetchData = async (query) => {
      const { data } = await axios.get(route('page.mentor-review', {
        mentor: props.slug,
      }), {
        params: { page: page.value }
      });
      model.value.push(...data.items)
      page.value = data.next_page
    };
</script>

<template>
    <div class="mt-6">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-lg font-bold mb-4">Reviews & Testimonials</h2>
            </div>
            <div class="flex">
                <StarIcon v-for="rating in [0, 1, 2, 3, 4]" :key="rating" :class="[ratingTotal > rating ? 'text-yellow-400' : 'text-gray-200', 'size-5 shrink-0']" aria-hidden="true" />
                <p class="text-sm text-gray-700 ml-2"> {{ ratingTotal }} ({{ totalCount }}) </p>
            </div>
        </div>

        <dl class="space-y-3">
            <div v-for="count in counts" :key="count.rating" class="flex items-center text-sm">
                <dt class="flex flex-1 items-center">
                    <p class="w-3 font-medium text-gray-900">{{ count.rating }}<span class="sr-only"> star reviews</span></p>
                    <div aria-hidden="true" class="ml-1 flex flex-1 items-center">
                        <StarIcon :class="[count.count > 0 ? 'text-yellow-400' : 'text-gray-300', 'size-5 shrink-0']" aria-hidden="true" />

                        <div class="relative ml-3 flex-1">
                            <div class="h-3 rounded-full border border-gray-200 bg-gray-100" />
                            <div v-if="count.count > 0" class="absolute inset-y-0 rounded-full border border-yellow-400 bg-yellow-400"
                                 :style="{ width: `calc(${count.count} / ${totalCount} * 100%)` }" />
                        </div>
                    </div>
                </dt>
                <dd class="ml-3 w-10 text-right text-sm text-gray-900 tabular-nums">{{ Math.round((count.count / totalCount) * 100 ) }}%</dd>
            </div>
        </dl>
    </div>
    <div class="border-t border-gray-200 my-6"></div>
    <div v-if="model.length" class="lg:col-span-12 mt-4">
        <div v-for="review in model" :key="review.id" class="w-full pb-6">
            <div class="flex gap-3">
                <img :src="review.menti.avatar" :alt="review.menti.username" class="aspect-square w-10 h-10 rounded-full object-cover shrink-0" />

                <div class="flex-1 min-w-0">
                    <div class="flex justify-between items-center mb-2">
                        <h3 class="text-sm font-medium text-gray-900">
                            {{ review.menti.username }}
                        </h3>
                        <span class="text-xs text-gray-400">2 weeks ago</span>
                    </div>

                    <div class="flex mb-3">
                        <StarIcon v-for="rating in [0, 1, 2, 3, 4]" :key="rating" :class="[review.rating > rating ? 'text-yellow-400' : 'text-gray-200', 'size-4 shrink-0']" aria-hidden="true" />
                    </div>

                    <p class="text-sm text-gray-600 leading-relaxed mb-2">
                        {{ review.comment }}
                    </p>
                    <p v-if="review.program" class="text-xs text-gray-500">
                        Program: {{ review.program }}
                    </p>
                </div>
            </div>
        </div>
        <div class="flex items-center justify-center">
            <button @click="fetchData" class="py-2 px-4 border border-gray-600 text-gray-600 rounded-md hover:bg-gray-50 transition-colors duration-200">
                Load More Reviews
            </button>
        </div>
    </div>
</template>

<style scoped>

</style>