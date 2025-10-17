<script setup>

import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import ListUser from "@/Pages/Chat/Blocks/ListUser.vue";
import MainList from "@/Pages/Chat/Blocks/MainList.vue";
import InfoList from "@/Pages/Chat/Blocks/InfoList.vue";
</script>

<template>
  <AuthenticatedLayout>
    <div class="py-12">
      <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
          <div class="border-b border-gray-200 bg-white p-8">
            <div class="flex">
              <aside class="w-[25%] min-w-[160px] p-4">
                <ListUser/>
              </aside>
              <main class="h-[150vh]  w-[50%] flex flex-col bg-gray-50 border-l border-gray-200">
                <MainList/>
              </main>
              <aside class="w-[25%] min-w-[160px]">
                <InfoList/>
              </aside>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<style scoped>

</style>