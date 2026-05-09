<script setup>
import ApplicationLogo from '@/Components/UI/Logo/ApplicationLogo.vue';
import MentorsList from '@/Components/UI/Table/MentorsList.vue';
import { useFooter } from '@/Stores/footer.js';

const footer = useFooter();
</script>

<template>
  <footer class="relative mx-auto mt-32 max-w-7xl px-6 lg:px-8">
    <div class="border-t border-gray-900/10 py-16 sm:py-24 lg:py-32">
      <div class="xl:grid xl:grid-cols-3 xl:gap-8">
        <ApplicationLogo class="h-15 w-auto" />
        <div class="mt-16 grid grid-cols-2 gap-8 xl:col-span-2 xl:mt-0">
          <div class="md:grid md:grid-cols-2 md:gap-8">
            <div>
              <h3 class="text-sm/6 font-semibold text-gray-900">Solutions</h3>
              <ul role="list" class="mt-6 space-y-4">
                <li
                  v-for="item in footer.navigation.solutions"
                  :key="item.name"
                >
                  <a
                    :href="item.href"
                    class="text-sm/6 text-gray-600 hover:text-gray-900"
                    >{{ item.name }}</a
                  >
                </li>
              </ul>
            </div>
            <div class="mt-10 md:mt-0">
              <h3 class="text-sm/6 font-semibold text-gray-900">Support</h3>
              <ul role="list" class="mt-6 space-y-4">
                <li v-for="item in footer.navigation.support" :key="item.name">
                  <a
                    :href="item.href"
                    class="text-sm/6 text-gray-600 hover:text-gray-900"
                    >{{ item.name }}</a
                  >
                </li>
              </ul>
            </div>
          </div>
          <div class="md:grid md:grid-cols-2 md:gap-8">
            <div>
              <h3 class="text-sm/6 font-semibold text-gray-900">Company</h3>
              <ul role="list" class="mt-6 space-y-4">
                <li v-for="item in footer.navigation.company" :key="item.name">
                  <a
                    :href="item.href"
                    class="text-sm/6 text-gray-600 hover:text-gray-900"
                    >{{ item.name }}</a
                  >
                </li>
              </ul>
            </div>
            <div class="mt-10 md:mt-0">
              <h3 class="text-sm/6 font-semibold text-gray-900">Legal</h3>
              <ul role="list" class="mt-6 space-y-4">
                <li v-for="item in footer.navigation.legal" :key="item.name">
                  <a
                    :href="item.href"
                    class="text-sm/6 text-gray-600 hover:text-gray-900"
                    >{{ item.name }}</a
                  >
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </footer>
</template>
