<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InfoList from '@/Pages/Chat/Blocks/InfoList.vue';
import ListUser from '@/Pages/Chat/Blocks/ListUser.vue';
import MainList from '@/Pages/Chat/Blocks/MainList.vue';
import { useCaseChat } from './useCaseChat.js';
import { onMounted, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
const { subscribeUser, unsubscribeUser, fetchUsers } = useCaseChat();
const user = usePage().props.auth.user;

onMounted(() => {
  fetchUsers(user.id);
});

onUnmounted(() => {
  unsubscribeUser();
});
</script>

<template>
  <AuthenticatedLayout>
    <div class="py-12">
      <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
          <div class="border-b border-gray-200 bg-white p-8">
            <div class="flex">
              <aside class="w-[25%] min-w-[160px] p-4">
                <ListUser />
              </aside>
              <main
                class="flex h-[150vh] w-[50%] flex-col border-l border-gray-200 bg-gray-50"
              >
                <MainList />
              </main>
              <aside class="w-[25%] min-w-[160px]">
                <InfoList />
              </aside>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<style scoped></style>
