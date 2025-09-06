<script setup>
import {IdentificationIcon} from "@heroicons/vue/24/solid/index.js";
const props = defineProps({
  title: {
    type: String,
    default: 'не вказано'
  },
  description: {
    type: String,
    default: 'не вказано'
  },
});
</script>

<template>
<div class="flex flex-col gap-4">
  <div>
    <h3 class="text-lg font-semibold text-gray-900">Про себе</h3>
    <span>
      {{ description }}
    </span>
  </div>

  <div>
    <h3 class="text-lg font-semibold text-gray-900 mt-2">Посада</h3>
    <span class="flex items-center gap-2">
      <IdentificationIcon class="w-8 h-8 text-blue-600"/>
      {{ title }}
    </span>
  </div>
</div>
</template>

<style scoped>

</style>