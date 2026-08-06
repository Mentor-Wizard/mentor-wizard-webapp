<script setup>
import {
  Listbox,
  ListboxButton,
  ListboxOption,
  ListboxOptions,
  Tab,
  TabList,
} from '@headlessui/vue';
import { ChevronUpDownIcon } from '@heroicons/vue/20/solid/index.js';

const selected = defineModel({
  type: Number,
  required: true,
});

defineProps({
  options: {
    type: Array,
    required: true,
  },
});
</script>

<template>
  <TabList class="grid grid-cols-1 sm:hidden">
    <Listbox v-model="selected" as="div">
      <div class="relative mt-2">
        <ListboxButton
          class="grid w-full cursor-default grid-cols-1 rounded-md bg-white py-1.5 pr-2 pl-3 text-left text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6"
        >
          <span class="col-start-1 row-start-1 truncate pr-6">{{
            options[selected]?.name ?? ''
          }}</span>
          <ChevronUpDownIcon
            class="col-start-1 row-start-1 size-5 self-center justify-self-end text-gray-500 sm:size-4"
            aria-hidden="true"
          />
        </ListboxButton>

        <transition
          leave-active-class="transition ease-in duration-100"
          leave-from-class="opacity-100"
          leave-to-class="opacity-0"
        >
          <TabList>
            <ListboxOptions
              class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black/5 focus:outline-hidden sm:text-sm"
            >
              <Tab
                v-for="(item, key) in options"
                :key="key"
                v-slot="{ itemSelected }"
                :as="ListboxOption"
              >
                <div
                  :class="[
                    itemSelected ?
                      'bg-indigo-600 text-white outline-hidden'
                    : 'text-gray-900',
                    'relative cursor-default py-2 pr-9 pl-3 select-none',
                  ]"
                >
                  <span class="block truncate font-normal">{{
                    item.name
                  }}</span>
                </div>
              </Tab>
            </ListboxOptions>
          </TabList>
        </transition>
      </div>
    </Listbox>
  </TabList>
</template>
