<script setup>
import { onMounted, ref } from 'vue';

const model = defineModel({
  type: String,
  required: true,
});

const textarea = ref(null);

onMounted(() => {
  if (textarea.value?.hasAttribute('autofocus')) {
    textarea.value.focus();
  }
});

defineExpose({
  focus: () => textarea.value?.focus(),
  click: () => textarea.value?.click(),
});
</script>

<template>
  <textarea
    ref="textarea"
    v-model="model"
    rows="4"
    class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6"
  />
</template>
