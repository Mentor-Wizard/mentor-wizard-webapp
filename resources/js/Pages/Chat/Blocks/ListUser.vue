<script setup>

import { MagnifyingGlassIcon} from "@heroicons/vue/20/solid/index.js";
import MobileTabSelect from "@/Pages/Profile/Partials/Components/MobileTabSelect.vue";
import {ref} from "vue";

import SelectField from "@/Components/UI/Forms/SelectField.vue";
import {Tab, TabGroup, TabList} from "@headlessui/vue";

const selectedTab = ref(0);

function changeTab(index) {
  selectedTab.value = index;
}

const navigation = ref([
  { name: 'All messages' },
  { name: 'Unread' },
]);


const messageSortList = ['Resent', 'New', 'Name']
const messageSortBy = ref(1)
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
      <MobileTabSelect
        v-model="selectedTab"
        :options="navigation"
      />
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

 <div class="grid grid-cols-2 gap-4 mt-2">
    <div class="text-[0.75rem] text-gray-800 self-end">Sort by</div>
    <SelectField
      id="currency_id"
      v-model="messageSortBy"
      :list="messageSortList"
      :placeholder="'Select sorting'"
      required
    />
 </div>

  <div class="flex items-start p-2 bg-white rounded-lg shadow-sm border border-gray-200 max-w-md mt-2">
    <div class="relative">
        <img src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80" alt="Sarah Johnson" class="w-12 h-12 rounded-full" />
        <!-- Status online -->
        <span class="absolute bottom-0 right-0 w-3 h-3 bg-green-600 border-2 border-white rounded-full"></span>
    </div>

    <div class="ml-4 flex-1">
        <div class="flex items-center justify-between">
            <h4 class="font-semibold text-[0.75rem] text-gray-900">Sarah Johnson</h4>
            <span class="text-[0.75rem] text-gray-500">1h ago</span>
        </div>
        <p class="mt-1 text-[0.75rem] text-gray-800 line-clamp-2">
            I've completed the assignment you sent yesterday I've completed the assignment you sent yesterday
        </p>
    </div>
</div>

</template>

<style scoped>

</style>