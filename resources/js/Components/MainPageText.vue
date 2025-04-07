<template>
  <h1 class="text-3xl font-bold tracking-tight text-gray-900">{{title}}</h1>
</template>

<script setup>
defineProps({
  title: {
    type: String,
    required: true,
  },
});
</script>
