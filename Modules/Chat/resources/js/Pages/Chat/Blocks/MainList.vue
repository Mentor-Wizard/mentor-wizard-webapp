<script setup>
import {
  CalendarIcon,
  EllipsisVerticalIcon,
  PhoneIcon,
  VideoCameraIcon,
} from '@heroicons/vue/20/solid';
import ChatList from '@modules/Chat/resources/js/Pages/Chat/Blocks/ChatList.vue';
import TiptapInput from '@modules/Chat/resources/js/Pages/Chat/Blocks/TiptapInput.vue';
import { useCaseChat } from '@modules/Chat/resources/js/Pages/Chat/useCaseChat.js';

const { currentUser } = useCaseChat();
</script>

<template>
  <header v-if="currentUser" class="flex-shrink-0">
    <div class="flex justify-between">
      <div class="mt-2 flex max-w-md items-start p-2">
        <div class="relative">
          <img
            :src="currentUser?.avatar"
            alt="{{ currentUser?.name}}"
            class="h-12 w-12 rounded-full"
          />
          <!-- Status online -->
          <span
            class="absolute right-0 bottom-0 h-3 w-3 rounded-full border-2 border-white"
            :class="currentUser?.online ? 'bg-green-600' : 'bg-gray-300'"
          ></span>
        </div>

        <div class="mt-1 ml-4 flex-1">
          <h4 class="text-[0.75rem] font-semibold text-gray-900">
            {{ currentUser?.name }}
          </h4>
          <p
            class="mt-1 line-clamp-2 text-[0.75rem]"
            :class="currentUser?.online ? 'text-green-600' : 'text-gray-300'"
          >
            {{ currentUser?.online ? 'Online' : 'Offline' }}
          </p>
        </div>
      </div>
      <div class="mt-4 flex max-w-md items-start p-2">
        <VideoCameraIcon
          class="pointer-events-none col-start-1 row-start-1 ml-3 size-5 self-center text-gray-400"
          aria-hidden="true"
        />
        <PhoneIcon
          class="pointer-events-none col-start-1 row-start-1 ml-3 size-5 self-center text-gray-400"
          aria-hidden="true"
        />
        <CalendarIcon
          class="pointer-events-none col-start-1 row-start-1 ml-3 size-5 self-center text-gray-400"
          aria-hidden="true"
        />
        <EllipsisVerticalIcon
          class="pointer-events-none col-start-1 row-start-1 ml-3 size-5 self-center text-gray-400"
          aria-hidden="true"
        />
      </div>
    </div>
  </header>
  <section class="flex-1 overflow-auto bg-white">
    <ChatList />
  </section>
  <footer class="flex-shrink-0 border-t border-gray-200 bg-white p-4 shadow-sm">
    <div class="mx-auto max-w-4xl text-sm text-gray-600">
      <TiptapInput />
    </div>
  </footer>
</template>

<style scoped></style>
