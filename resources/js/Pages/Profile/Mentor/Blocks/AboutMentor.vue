<script setup>
import {IdentificationIcon} from "@heroicons/vue/24/solid/index.js";
const props = defineProps({
  title: {
    type: String,
    default: 'not defined'
  },
  description: {
    type: String,
    default: 'not defined'
  },
});
</script>

<template>
<div class="flex flex-col gap-4">
    <h3 class="text-lg font-semibold text-gray-900">About</h3>
    <span>
      {{ description }}
    </span>

    <h3 class="text-lg font-semibold text-gray-900 mt-2">Position</h3>
    <span class="flex items-center gap-2">
      <IdentificationIcon class="w-8 h-8 text-blue-600"/>
      {{ title }}
    </span>
</div>
</template>

<style scoped>

</style>