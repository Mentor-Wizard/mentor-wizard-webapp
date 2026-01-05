<script setup>
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue';
import { EllipsisVerticalIcon } from '@heroicons/vue/20/solid';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

import AppModal from '@/Components/AppModal.vue';
import DangerButton from '@/Components/UI/Button/DangerButton.vue';
import PrimaryButton from '@/Components/UI/Button/PrimaryButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const showDeleteModal = ref(false);
const programToDelete = ref(null);

defineProps({
  programs: {
    type: Object,
    default: null,
  },
});

const formatDate = (datetime) => {
  const date = new Date(datetime);
  return date.toLocaleString('uk-UA', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

const confirmDelete = (program) => {
  showDeleteModal.value = true;
  programToDelete.value = program;
};

const deleteProgram = () => {
  if (!programToDelete.value) return;
  router.delete(route('mentor-program.destroy', programToDelete.value.slug), {
    preserveScroll: true,
    only: ['programs'],
    onSuccess: () => {
      showDeleteModal.value = false;
      programToDelete.value = null;
    },
  });
};
</script>

<template>
  <AuthenticatedLayout>
    <template #header>
      <h2 class="text-xl leading-tight font-semibold text-gray-800">
        Mentor Programs
      </h2>
    </template>

    <div class="py-12">
      <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
          <div class="border-b border-gray-200 bg-white p-8">
            <template v-if="programs.length > 0">
              <ul role="list" class="divide-y divide-gray-100">
                <li
                  v-for="program in programs"
                  :key="program.id"
                  class="flex items-center justify-between gap-x-6 py-5"
                >
                  <div class="min-w-0">
                    <div class="flex items-start gap-x-3">
                      <p class="text-sm/6 font-semibold text-gray-900">
                        {{ program.name }}
                      </p>
                      <p
                        class="mt-0.5 rounded-md px-1.5 py-0.5 text-xs font-medium whitespace-nowrap ring-1 ring-inset"
                      >
                        {{ program.cost }} {{ program.currency.symbol }}
                      </p>
                    </div>
                    <div
                      class="mt-1 flex items-center gap-x-2 text-xs/5 text-gray-500"
                    >
                      <p class="truncate">
                        Created by {{ formatDate(program.created_at) }}
                      </p>
                    </div>
                  </div>
                  <div class="flex flex-none items-center gap-x-4">
                    <a
                      href="#"
                      class="hidden rounded-md bg-white px-2.5 py-1.5 text-sm font-semibold
                      text-gray-900 shadow-xs ring-1 ring-gray-300 ring-inset hover:bg-gray-50 sm:block"
                      >View program<span class="sr-only"
                        >, {{ program.name }}</span
                      ></a
                    >

                    <a
                      :href="route('pages.mentor.program.book', program.slug)"
                      class="hidden rounded-md bg-white px-2.5 py-1.5 text-sm font-semibold
                       text-gray-900 shadow-xs ring-1 ring-gray-300 ring-inset hover:bg-gray-50 sm:block"
                    >Book event<span class="sr-only"
                    >, {{ program.name }}</span
                    ></a
                    >

                    <a
                      :href="route('pages.mentor.program.book', program.slug)"
                      :class="[
                          'hidden rounded-md px-2.5 py-1.5 text-sm font-semibold' +
                           ' text-gray-900 shadow-xs ring-1 ring-inset sm:block',
                          program.pending_events_requests_number === 0
                            ? 'bg-green-50 ring-green-200 hover:bg-green-100'
                            : 'bg-yellow-50 ring-yellow-200 hover:bg-yellow-100',
                        ]"
                    >
                      Pending {{ program.pending_events_requests_number }}
                    </a>

                    <a
                      :href="route('pages.mentor.program.book', program.slug)"
                      class="hidden rounded-md bg-white px-2.5 py-1.5 text-sm font-semibold
                       text-gray-900 shadow-xs ring-1 ring-gray-300 ring-inset hover:bg-gray-50 sm:block"
                    >
                      Confirmed {{ program.confirmed_events_number }}
                    </a>

                    <Menu as="div" class="relative flex-none">
                      <MenuButton
                        class="-m-2.5 block p-2.5 text-gray-500 hover:text-gray-900"
                      >
                        <span class="sr-only">Open options</span>
                        <EllipsisVerticalIcon
                          class="size-5"
                          aria-hidden="true"
                        />
                      </MenuButton>
                      <transition
                        enter-active-class="transition ease-out duration-100"
                        enter-from-class="transform opacity-0 scale-95"
                        enter-to-class="transform opacity-100 scale-100"
                        leave-active-class="transition ease-in duration-75"
                        leave-from-class="transform opacity-100 scale-100"
                        leave-to-class="transform opacity-0 scale-95"
                      >
                        <MenuItems
                          class="absolute right-0 z-10 mt-2 w-32 origin-top-right rounded-md bg-white py-2 shadow-lg ring-1 ring-gray-900/5 focus:outline-hidden"
                        >
                          <MenuItem v-slot="{ active }">
                            <a
                              :href="route('mentor-program.edit', program.slug)"
                              :class="[
                                active ? 'bg-gray-50 outline-hidden' : '',
                                'block px-3 py-1 text-sm/6 text-gray-900',
                              ]"
                              >Edit<span class="sr-only"
                                >, {{ program.name }}</span
                              ></a
                            >
                          </MenuItem>
                          <MenuItem v-slot="{ active }">
                            <button
                              type="button"
                              :class="[
                                active ? 'bg-gray-50 outline-hidden' : '',
                                'block w-full px-3 py-1 text-left text-sm/6 text-gray-900',
                              ]"
                              @click="confirmDelete(program)"
                            >
                              Delete<span class="sr-only"
                                >, {{ program.name }}</span
                              >
                            </button>
                          </MenuItem>

                          <MenuItem v-slot="{ active }">
                            <button
                              type="button"
                              :class="[
                                active ? 'bg-gray-50 outline-hidden' : '',
                                'block w-full px-3 py-1 text-left text-sm/6 text-gray-900',
                              ]"
                              @click="route('pages.mentor.program.book', program.slug)"
                            >
                              Book Event<span class="sr-only"
                            >, {{ program.name }}</span
                            >
                            </button>

                            <button
                              type="button"
                              :class="[
                                program.pending_events_requests_number === 0 ?
                                 'bg-green-200 outline-hidden' : 'bg-red-200 outline-hidden',
                                'block w-full px-3 py-1 text-left text-sm/6 text-gray-900',
                              ]"
                              @click="route('pages.mentor.program.book', program.slug)"
                            >
                              Need confirmation {{ program.pending_events_requests_number }}
                              <span class="sr-only bg-red-300"
                            ></span
                            >
                            </button>
                          </MenuItem>
                        </MenuItems>
                      </transition>
                    </Menu>
                  </div>
                </li>
              </ul>
            </template>
            <template v-else>
              <div class="text-center text-sm text-gray-500">
                You don't have any programs yet.
              </div>
            </template>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <AppModal v-model="showDeleteModal">
      <div class="p-6">
        <h2 class="text-lg font-medium text-gray-900">
          Are you sure you want to delete this program?
        </h2>
        <p class="mt-1 text-sm text-gray-600">
          Once this program is deleted, all of its resources and data will be
          permanently deleted.
        </p>
        <div class="mt-6 flex justify-end space-x-3">
          <PrimaryButton @click="showDeleteModal = false">
            Cancel
          </PrimaryButton>
          <DangerButton @click="deleteProgram"> Delete Program </DangerButton>
        </div>
      </div>
    </AppModal>
  </AuthenticatedLayout>
</template>
