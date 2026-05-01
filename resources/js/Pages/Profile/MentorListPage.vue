<script setup>
import { ChevronRightIcon } from '@heroicons/vue/20/solid';
import { Link, router } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

import AppPagination from '@/Components/Navigation/AppPagination.vue';
import LandingLayout from '@/Layouts/LandingLayout.vue';

const props = defineProps({
  mentors: {
    type: Object,
    required: true,
  },
  categories: {
    type: Array,
    default: () => [],
  },
  selectedCategoryId: {
    type: Number,
    default: null,
  },
});

const expandedIds = ref(new Set());

const flattenedCategories = computed(() => {
  const out = [];
  const walk = (nodes, depth, ancestors) => {
    for (const node of nodes) {
      out.push({
        id: node.id,
        name: node.name,
        depth,
        ancestors,
        hasChildren: node.children?.length > 0,
      });
      if (node.children?.length > 0)
        walk(node.children, depth + 1, [...ancestors, node.id]);
    }
  };
  walk(props.categories, 0, []);
  return out;
});

const findAncestorIds = (nodes, targetId, trail = []) => {
  for (const node of nodes) {
    if (node.id === targetId) return trail;
    if (node.children?.length > 0) {
      const found = findAncestorIds(node.children, targetId, [
        ...trail,
        node.id,
      ]);
      if (found !== null) return found;
    }
  }
  return null;
};

onMounted(() => {
  if (props.selectedCategoryId !== null) {
    const ancestors = findAncestorIds(
      props.categories,
      props.selectedCategoryId,
    );
    ancestors?.forEach((id) => expandedIds.value.add(id));
  }
});

const toggleExpand = (id) => {
  if (expandedIds.value.has(id)) {
    expandedIds.value.delete(id);
  } else {
    expandedIds.value.add(id);
  }
  expandedIds.value = new Set(expandedIds.value);
};

const selectCategory = (id) => {
  router.get(
    route('page.profile-programs'),
    { category_id: id },
    { preserveState: true, preserveScroll: true },
  );
};

const clearCategory = () => {
  router.get(
    route('page.profile-programs'),
    {},
    { preserveState: true, preserveScroll: true },
  );
};
</script>

<template>
  <LandingLayout>
    <div class="bg-gray-100 py-16">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <h1 class="mb-8 text-3xl font-bold text-gray-900">Find a Mentor</h1>

        <div class="flex flex-col gap-6 lg:flex-row">
          <aside class="w-full shrink-0 lg:w-64">
            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
              <div class="p-4">
                <h2 class="mb-3 text-sm font-semibold text-gray-900">
                  Categories
                </h2>
                <button
                  type="button"
                  :class="[
                    'block w-full rounded-md px-2 py-1.5 text-left text-sm',
                    selectedCategoryId === null ?
                      'bg-indigo-50 font-semibold text-indigo-700'
                    : 'text-gray-700 hover:bg-gray-50',
                  ]"
                  @click="clearCategory"
                >
                  All mentors
                </button>
                <ul
                  v-if="flattenedCategories.length > 0"
                  class="mt-2 space-y-0.5"
                >
                  <template v-for="node in flattenedCategories" :key="node.id">
                    <li
                      v-show="
                        node.depth === 0
                        || node.ancestors.every((id) => expandedIds.has(id))
                      "
                      class="flex items-center"
                      :style="{ paddingLeft: node.depth * 16 + 'px' }"
                    >
                      <button
                        v-if="node.hasChildren"
                        type="button"
                        class="mr-1 inline-flex size-5 shrink-0 items-center justify-center rounded text-gray-400 hover:text-gray-700"
                        @click="toggleExpand(node.id)"
                      >
                        <ChevronRightIcon
                          :class="[
                            'size-4 transition-transform duration-150',
                            expandedIds.has(node.id) ? 'rotate-90' : '',
                          ]"
                        />
                      </button>
                      <span v-else class="mr-1 inline-block size-5 shrink-0" />
                      <button
                        type="button"
                        :class="[
                          'flex-1 truncate rounded-md px-2 py-1 text-left text-sm',
                          selectedCategoryId === node.id ?
                            'bg-indigo-50 font-semibold text-indigo-700'
                          : 'text-gray-700 hover:bg-gray-50',
                        ]"
                        @click="selectCategory(node.id)"
                      >
                        {{ node.name }}
                      </button>
                    </li>
                  </template>
                </ul>
              </div>
            </div>
          </aside>

          <div class="min-w-0 flex-1">
            <div class="space-y-6">
              <div
                v-for="mentor in mentors.data"
                :key="mentor.userSlug"
                class="overflow-hidden rounded-lg bg-white shadow-sm"
              >
                <div class="flex items-start gap-6 p-6">
                  <Link :href="route('page.mentor', mentor.userSlug)">
                    <img
                      :src="mentor.userAvatar"
                      :alt="mentor.userName"
                      class="h-20 w-20 flex-shrink-0 rounded-full object-cover"
                    />
                  </Link>

                  <div class="min-w-0 flex-1">
                    <div class="flex items-start justify-between">
                      <div>
                        <Link
                          :href="route('page.mentor', mentor.userSlug)"
                          class="text-lg font-semibold text-gray-900 hover:text-indigo-600"
                        >
                          {{ mentor.userName }}
                        </Link>
                        <p class="mt-0.5 text-sm text-gray-600">
                          {{ mentor.title }}
                        </p>
                      </div>

                      <div class="ml-4 flex-shrink-0 text-right">
                        <p class="text-lg font-semibold text-indigo-600">
                          {{ mentor.rate }}
                          <span v-if="mentor.currency">{{
                            mentor.currency.symbol
                          }}</span>
                          <span class="text-sm font-normal text-gray-500"
                            >/hour</span
                          >
                        </p>
                      </div>
                    </div>

                    <p class="mt-2 line-clamp-2 text-sm text-gray-500">
                      {{ mentor.description }}
                    </p>

                    <div class="mt-4 flex items-center justify-end gap-3">
                      <Link
                        :href="route('page.mentor', mentor.userSlug)"
                        class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                      >
                        View Profile
                      </Link>
                      <Link
                        v-if="mentor.mainProgramSlug"
                        :href="
                          route(
                            'pages.mentor.program.book',
                            mentor.mainProgramSlug,
                          )
                        "
                        class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500"
                      >
                        Book Consultation
                      </Link>
                    </div>
                  </div>
                </div>
              </div>

              <div
                v-if="mentors.data.length === 0"
                class="py-12 text-center text-gray-500"
              >
                No mentors found.
              </div>
            </div>

            <AppPagination :data="mentors" />
          </div>
        </div>
      </div>
    </div>
  </LandingLayout>
</template>
