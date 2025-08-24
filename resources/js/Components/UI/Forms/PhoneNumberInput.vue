<script setup>
import 'vue-tel-input/vue-tel-input.css';

import { ref, watch } from 'vue';
import { VueTelInput } from 'vue-tel-input';

const emit = defineEmits(['update:modelValue', 'update:country']);

const phone = ref(null);

watch(phone, (value) => {
  emit('update:modelValue', value);
});
</script>

<template>
  <vue-tel-input v-model="phone" />
</template>
