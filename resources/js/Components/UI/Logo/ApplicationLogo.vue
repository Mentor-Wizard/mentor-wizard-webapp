<template>
  <Link :href="route('pages.welcome')">
    <img class="h-9" src="https://tailwindcss.com/plus-assets/img/logos/mark.svg?color=indigo&shade=600" alt=""/>
  </Link>
</template>

<script setup>
import {Link} from "@inertiajs/vue3";
</script>
