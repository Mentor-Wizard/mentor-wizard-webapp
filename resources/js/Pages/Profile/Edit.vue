<template>
  <AuthenticatedLayout>
    <template #header>
      <MainPageText title="Profile" />
    </template>
    <div>
      <main>
        <div class="py-12">
          <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white p-8 shadow-xs sm:rounded-lg">
              <TabGroup :selectedIndex="selectedTab" @change="changeTab">
                <h1 class="sr-only">Account Settings</h1>
                <header class="border-b border-white/5">
                  <MobileTabSelect
                    :options="navigation"
                    v-model="selectedTab"
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
                <TabPanels>
                  <TabPanel v-for="tab in navigation">
                    <component :is="tab.component" />
                  </TabPanel>
                </TabPanels>
              </TabGroup>
            </div>
          </div>
        </div>
      </main>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import MainPageText from '@/Components/MainPageText.vue';
import {
  BellIcon,
  CreditCardIcon,
  UserIcon,
  CalendarIcon,
} from '@heroicons/vue/20/solid';
import { ref, shallowRef } from 'vue';
import MyAccountTab from '@/Pages/Profile/Tab/MyAccountTab.vue';
import NotificationTab from '@/Pages/Profile/Tab/NotificationTab.vue';
import BillingTab from '@/Pages/Profile/Tab/BillingTab.vue';
import { Tab, TabGroup, TabList, TabPanel, TabPanels } from '@headlessui/vue';
import MobileTabSelect from '@/Pages/Profile/Partials/Components/MobileTabSelect.vue';
import CalendarTab from '@/Pages/Profile/Tab/CalendarTab.vue';

const selectedTab = ref(0);

function changeTab(index) {
  selectedTab.value = index;
}

const navigation = ref([
  { name: 'My Account', icon: UserIcon, component: shallowRef(MyAccountTab) },
  {
    name: 'Notification',
    icon: BellIcon,
    component: shallowRef(NotificationTab),
  },
  { name: 'Billing', icon: CreditCardIcon, component: shallowRef(BillingTab) },
  { name: 'Calendar', icon: CalendarIcon, component: shallowRef(CalendarTab) },
]);
</script>
