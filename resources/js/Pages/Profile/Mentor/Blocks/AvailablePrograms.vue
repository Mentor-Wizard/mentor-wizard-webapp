<script setup>
const props = defineProps({
  data: {
    type: Object,
    required: true
  }
});

</script>

<template>
  <div class="mt-2">
     <h3 class="text-lg font-semibold text-gray-900">Available Programs</h3>
  </div>

  <div class="grid grid-cols-2 gap-4">
    <div v-for="program in data" :key="program.id" class="bg-white border border-gray-300 rounded-lg p-6 max-w-sm shadow-sm flex flex-col h-full">
        <div class="flex justify-between items-start mb-3">
            <h3 class="text-lg font-semibold text-gray-900">{{ program.name }}</h3>
            <span class="text-lg font-semibold text-blue-600">{{ program.currency }}{{ program.cost }}</span>
        </div>

        <p class="text-gray-600 text-sm mb-4 leading-relaxed">
            {{ program.description }}
        </p>

        <div class="flex items-center mb-4 text-gray-600 text-sm">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="10"></circle>
                <polyline points="12,6 12,12 16,14"></polyline>
            </svg>
            60 minutes
        </div>

        <div class="space-y-3 mb-6">
            <div v-for="block in program.blocks" key="block.id" class="flex items-start">
                <svg class="w-5 h-5 text-green-500 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                </svg>
                <span class="text-gray-700 text-sm">{{ block.name }}</span>
            </div>
        </div>

        <button class="w-full py-3 px-4 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200 font-medium mt-auto">
            Book Session
        </button>
    </div>
  </div>

</template>

<style scoped>

</style>