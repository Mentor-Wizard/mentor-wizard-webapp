<script setup>
import { Tab, TabGroup, TabList } from '@headlessui/vue';
import { MagnifyingGlassIcon } from '@heroicons/vue/20/solid/index.js';
import { ref } from 'vue';
import { useCaseChat } from '@/Pages/Chat/useCaseChat.js';

import SelectField from '@/Components/UI/Forms/SelectField.vue';
import MobileTabSelect from '@/Pages/Profile/Partials/Components/MobileTabSelect.vue';

const { sortedUsers, messageSortList, messageSortBy } = useCaseChat();

const selectedTab = ref(0);

function changeTab(index) {
  selectedTab.value = index;
}

const navigation = ref([{ name: 'All messages' }, { name: 'Unread' }]);
</script>

<template>
  <div class="grid w-full max-w-lg grid-cols-1 lg:max-w-xs">
    <label for="search" class="sr-only">Search conversation</label>
    <input
      id="search"
      type="search"
      name="search"
      class="col-start-1 row-start-1 block w-full rounded-md border-0 bg-white py-1.5 pr-3 pl-10 text-base text-gray-900 ring-1 ring-gray-300 ring-inset placeholder:text-gray-400 focus:ring-2 focus:ring-indigo-600 focus:ring-inset sm:text-sm/6"
      placeholder="Search conversation"
    />
    <MagnifyingGlassIcon
      class="pointer-events-none col-start-1 row-start-1 ml-3 size-5 self-center text-gray-400"
      aria-hidden="true"
    />
  </div>

  <TabGroup :selected-index="selectedTab" @change="changeTab">
    <h1 class="sr-only">Type messages</h1>
    <header class="border-b border-white/5">
      <MobileTabSelect v-model="selectedTab" :options="navigation" />
      <div class="hidden sm:block">
        <TabList class="border-b border-gray-200">
          <div class="-mb-px flex space-x-8" aria-label="Tabs">
            <Tab
              v-for="tab in navigation"
              :key="tab.name"
              v-slot="{ selected }"
              class="focus-visible:outline-none"
            >
              <div
                :class="[
                  selected ?
                    'border-indigo-500 text-indigo-600'
                  : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700',
                  'group inline-flex items-center border-b-2 px-1 py-4 text-sm font-medium',
                ]"
                :aria-current="selected ? 'page' : undefined"
              >
                <span>{{ tab.name }}</span>
              </div>
            </Tab>
          </div>
        </TabList>
      </div>
    </header>
  </TabGroup>

  <div class="mt-2 grid grid-cols-2 gap-4">
    <div class="self-end text-[0.75rem] text-gray-800">Sort by</div>
    <SelectField
      v-model="messageSortBy"
      :list="messageSortList"
      :placeholder="'Select sorting'"
      required
    />
  </div>

  <div
    v-for="user in sortedUsers"
    :key="user.id"
    class="mt-2 flex max-w-md items-start rounded-lg border border-gray-200 p-2 shadow-sm"
    :class="user.active ? 'bg-gray-100' : 'bg-white'"
  >
    <div class="relative">
      <img
        :src="user.avatar"
        alt="Sarah Johnson"
        class="h-12 w-12 rounded-full"
      />
      <!-- Status online -->
      <span
        class="absolute right-0 bottom-0 h-3 w-3 rounded-full border-2 border-white"
        :class="user.online ? 'bg-green-600' : 'bg-gray-300'"
      ></span>
    </div>

    <div class="ml-4 flex-1">
      <div class="flex items-center justify-between">
        <h4 class="text-[0.75rem] font-semibold text-gray-900">
          {{ user.name }}
        </h4>
        <span class="text-[0.75rem] text-gray-500">{{ user.last }}</span>
      </div>
      <p class="mt-1 line-clamp-2 text-[0.75rem] text-gray-800">
        {{ user.message }}
      </p>
    </div>
  </div>
</template>

<style scoped></style>
