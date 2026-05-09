<script setup>
import { ChevronRightIcon } from '@heroicons/vue/20/solid';
import { router } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

const props = defineProps({
  categories: {
    type: Array,
    default: () => [],
  },
  selectedCategoryId: {
    type: Number,
    default: null,
  },
  routeName: {
    type: String,
    required: true,
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
    route(props.routeName),
    { category_id: id },
    { preserveState: true, preserveScroll: true },
  );
};

const clearCategory = () => {
  router.get(
    route(props.routeName),
    {},
    { preserveState: true, preserveScroll: true },
  );
};
</script>

<template>
  <div class="overflow-hidden rounded-lg bg-white shadow-sm">
    <div class="p-4">
      <h2 class="mb-3 text-sm font-semibold text-gray-900">Categories</h2>
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
      <ul v-if="flattenedCategories.length > 0" class="mt-2 space-y-0.5">
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
</template>
