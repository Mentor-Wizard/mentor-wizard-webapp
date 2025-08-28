<script setup>

</script>

<template>
  <div class="py-4 grid grid-cols-4 gap-4">
    <div v-for="i in [1,2,3,4]" class="rounded-2xl bg-white overflow-hidden">
      <img
        src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80"
        alt="mentor.profile.name"
        class="w-full h-48 object-cover"
      />
      <div class="p-4">
      Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries
      </div>
      <div class="p-4 flex justify-center">
        <button type="button" class="flex items-center gap-2 px-4 py-2 border border-blue-200 text-blue-600 rounded-lg hover:bg-blue-50 transition-colors">
            View Profile
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>

</style>