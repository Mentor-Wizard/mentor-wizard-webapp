<script setup>
import { VideoCameraIcon } from '@heroicons/vue/20/solid';
import {PhoneIcon} from "@heroicons/vue/20/solid";
import {CalendarIcon} from "@heroicons/vue/20/solid";
import { EllipsisVerticalIcon } from '@heroicons/vue/24/outline'
import TiptapInput from "@/Pages/Chat/Blocks/TiptapInput.vue";
import ChatList from "@/Pages/Chat/Blocks/ChatList.vue";

</script>

<template>
  <header class="flex-shrink-0">
    <div class="flex justify-between">
        <div class="flex items-start p-2 max-w-md mt-2">
            <div class="relative">
                <img src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80" alt="Sarah Johnson" class="w-12 h-12 rounded-full" />
                <!-- Status online -->
                <span class="absolute bottom-0 right-0 w-3 h-3 bg-green-600 border-2 border-white rounded-full"></span>
            </div>

            <div class="ml-4 flex-1 mt-1">
                <h4 class="font-semibold text-[0.75rem] text-gray-900">Sarah Johnson</h4>
                <p class="mt-1 text-[0.75rem] text-green-600 line-clamp-2">
                    Online
                </p>
            </div>
        </div>
        <div class="flex items-start p-2 max-w-md mt-4">
            <VideoCameraIcon class="pointer-events-none col-start-1 row-start-1 ml-3 size-5 self-center text-gray-400" aria-hidden="true" />
            <PhoneIcon class="pointer-events-none col-start-1 row-start-1 ml-3 size-5 self-center text-gray-400" aria-hidden="true" />
            <CalendarIcon class="pointer-events-none col-start-1 row-start-1 ml-3 size-5 self-center text-gray-400" aria-hidden="true" />
            <EllipsisVerticalIcon class="pointer-events-none col-start-1 row-start-1 ml-3 size-5 self-center text-gray-400" aria-hidden="true" />
        </div>
    </div>
  </header>
  <section class="flex-1 bg-white overflow-auto">
    <ChatList />
  </section>
  <footer class="flex-shrink-0 bg-white border-t border-gray-200 p-4 shadow-sm">
      <div class="max-w-4xl mx-auto text-sm text-gray-600">
          <TiptapInput />
      </div>
  </footer>
</template>

<style scoped>

</style>