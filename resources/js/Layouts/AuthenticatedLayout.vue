<script setup>
import AppFooter from '@/Components/Navigation/AppFooter.vue';
import AppNavbar from '@/Components/Navigation/Navbar/AppNavbar.vue';
</script>
<template>
  <div>
    <div class="min-h-screen bg-gray-100">
      <AppNavbar />
      <!-- Page Heading -->
      <header v-if="$slots.header" class="bg-white shadow">
        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
          <slot name="header" />
        </div>
      </header>
      <!-- Page Content -->
      <main>
        <slot />
      </main>
      <AppFooter />
    </div>
  </div>
</template>
