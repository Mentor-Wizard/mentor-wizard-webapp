<script setup>
defineProps({
  isShow: {
    type: Boolean,
    required: true,
  },
  message: {
    type: String,
    required: true,
  },
});
</script>

<template>
  <Transition enter-active-class="transition ease-in-out" enter-from-class="opacity-0"
              leave-active-class="transition ease-in-out" leave-to-class="opacity-0">
    <p v-if="isShow" class="mt-2 text-sm text-green-600">
      {{ message }}
    </p>
  </Transition>
</template>
