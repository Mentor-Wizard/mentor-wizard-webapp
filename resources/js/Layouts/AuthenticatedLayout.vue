<script setup>
import Navbar from '@/Components/Navigation/Navbar/Navbar.vue';
import Footer from '@/Components/Navigation/Footer.vue';
</script>
<template>
  <div>
    <div class="min-h-screen bg-gray-100">
      <Navbar />
      <!-- Page Heading -->
      <header class="bg-white shadow" v-if="$slots.header">
        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
          <slot name="header" />
        </div>
      </header>
      <!-- Page Content -->
      <main>
        <slot />
      </main>
      <Footer></Footer>
    </div>
  </div>
</template>
