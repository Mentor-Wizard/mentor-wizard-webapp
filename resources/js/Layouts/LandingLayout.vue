<template>
  <div class="bg-white">
    <!-- Header -->
    <header class="absolute inset-x-0 top-0 z-50">
      <Navbar :transparent=true />
      <Dialog class="lg:hidden" @close="mobileMenuOpen = false" :open="mobileMenuOpen">
        <div class="fixed inset-0 z-50"/>
        <DialogPanel
            class="fixed inset-y-0 right-0 z-50 w-full overflow-y-auto bg-white px-6 py-6 sm:max-w-sm sm:ring-1 sm:ring-gray-900/10">
          <div class="flex items-center justify-between">
            <Link :href="route('pages.welcome')" class="-m-1.5 p-1.5">
              <span class="sr-only">{{$page.props.project.name}}</span>
              <ApplicationLogo class="h-8 w-auto"/>
            </Link>
            <button type="button" class="-m-2.5 rounded-md p-2.5 text-gray-700" @click="mobileMenuOpen = false">
              <span class="sr-only">Close menu</span>
              <XMarkIcon class="size-6" aria-hidden="true"/>
            </button>
          </div>
          <div class="mt-6 flow-root">
            <div class="-my-6 divide-y divide-gray-500/10">
              <div class="space-y-2 py-6">
                <Link v-for="item in navigation" :key="item.name" :href="item.href"
                   class="-mx-3 block rounded-lg px-3 py-2 text-base/7 font-semibold text-gray-900 hover:bg-gray-50">{{
                    item.name
                  }}</Link>
              </div>
              <div class="py-6">
                <Link :href="route('login')"
                   class="-mx-3 block rounded-lg px-3 py-2.5 text-base/7 font-semibold text-gray-900 hover:bg-gray-50">Log
                  in</Link>
              </div>
            </div>
          </div>
        </DialogPanel>
      </Dialog>
    </header>

    <!-- Main content -->
    <main class="isolate pt-14">
      <slot/>
    </main>

    <!-- Footer -->
    <Footer/>
  </div>
</template>

<script setup>
import {ref} from 'vue'
import {Dialog, DialogPanel} from '@headlessui/vue'
import {XMarkIcon} from '@heroicons/vue/24/outline'
import ApplicationLogo from "@/Components/UI/Logo/ApplicationLogo.vue";
import {Link} from "@inertiajs/vue3";
import {useNavigation} from "@/Stores/navigation.js";
import Navbar from "@/Components/Navigation/Navbar/Navbar.vue";
import Footer from "@/Components/Navigation/Footer.vue";

const mobileMenuOpen = ref(false)
const {landingNavigation: navigation} = useNavigation()
</script>
