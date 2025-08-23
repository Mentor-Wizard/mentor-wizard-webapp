<script setup>
defineProps({
  value: {
    type: String,
    required: true,
  },
});
</script>

<template>
  <label class="block text-sm font-medium text-gray-900">
    <span v-if="value">{{ value }}</span>
    <span v-else><slot /></span>
  </label>
</template>
