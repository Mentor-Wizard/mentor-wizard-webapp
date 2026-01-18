<script setup>
import {
  Disclosure,
  DisclosureButton,
  DisclosurePanel,
  Menu,
  MenuButton,
  MenuItem,
  MenuItems,
} from '@headlessui/vue';
import { MagnifyingGlassIcon } from '@heroicons/vue/20/solid';
import { Bars3Icon, BellIcon, XMarkIcon } from '@heroicons/vue/24/outline';
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

import NavbarLogo from '@/Components/Navigation/Navbar/NavbarLogo.vue';
import { useNavigation } from '@/Stores/navigation.js';
import { useAlerts } from '@/UseCases/useCaseAlert.js';
const { isRinging, notificationsCount } = useAlerts();

defineProps({
  transparent: {
    type: Boolean,
    default: false,
  },
});

const page = usePage();

const navigationStore = useNavigation();
const logout = () => {
  router.post(route('logout'));
};

const currentUser = computed(() => page.props.auth?.user ?? {});
const currentUserAvatar = ref(page.props.auth?.avatar ?? null);
const isLoggedIn = computed(() => !!currentUser.value?.email);

const { infoChatMessage } = useAlerts();
onMounted(() => {
  infoChatMessage(page.props.auth?.user.id);
});

const mainNavigations = computed(() =>
  isLoggedIn.value ?
    navigationStore.authenticatedNavigation
  : navigationStore.landingNavigation,
);
const userNavigations = computed(() => navigationStore.userNavigation);
const authNavigations = computed(() => navigationStore.authNavigation);

// FIXME: Add avatar url after #24 task implementation
const profileImageUrl = computed(
  () =>
    currentUserAvatar?.value
    || 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80',
);

const isActiveLink = (navItemHref) => page.props.ziggy.location === navItemHref;

const mainNavLinkClasses = (navItemHref) => {
  return {
    'border-b-2 border-indigo-500 text-gray-900': isActiveLink(navItemHref),
    'border-b-2 border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700':
      !isActiveLink(navItemHref),
    'inline-flex items-center px-1 pt-1 text-sm font-medium': true,
  };
};

const mobileNavLinkClasses = (navItemHref) => {
  return {
    'block border-l-4 py-2 pr-4 pl-3 text-base font-medium': true,
    'bg-indigo-50 border-indigo-500 text-indigo-700': isActiveLink(navItemHref),
    'border-transparent text-gray-600 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-800':
      !isActiveLink(navItemHref),
  };
};
</script>

<template>
  <Disclosure
    v-slot="{ open }"
    as="nav"
    :class="{ 'bg-transparent': transparent, 'bg-white': !transparent }"
    class="shadow-sm"
  >
    <div class="mx-auto max-w-7xl px-2 sm:px-4 lg:px-8">
      <div class="flex h-16 justify-between">
        <div class="flex px-2 lg:px-0">
          <div class="flex shrink-0 items-center p-1">
            <NavbarLogo />
          </div>
          <div class="hidden lg:ml-6 lg:flex lg:space-x-8">
            <Link
              v-for="mainNavigation in mainNavigations"
              :key="mainNavigation.name"
              :href="mainNavigation.href"
              :class="mainNavLinkClasses(mainNavigation.href)"
            >
              {{ mainNavigation.name }}
            </Link>
          </div>
        </div>
        <div
          class="flex flex-1 items-center justify-center px-2 lg:ml-6 lg:justify-end"
        >
          <div class="grid w-full max-w-lg grid-cols-1 lg:max-w-xs">
            <label for="search" class="sr-only">Search</label>
            <input
              id="search"
              type="search"
              name="search"
              class="col-start-1 row-start-1 block w-full rounded-md border-0 bg-white py-1.5 pr-3 pl-10 text-base text-gray-900 ring-1 ring-gray-300 ring-inset placeholder:text-gray-400 focus:ring-2 focus:ring-indigo-600 focus:ring-inset sm:text-sm/6"
              placeholder="Search"
            />
            <MagnifyingGlassIcon
              class="pointer-events-none col-start-1 row-start-1 ml-3 size-5 self-center text-gray-400"
              aria-hidden="true"
            />
          </div>
        </div>
        <div class="flex items-center lg:hidden">
          <DisclosureButton
            class="relative inline-flex items-center justify-center rounded-md p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none focus:ring-inset"
          >
            <span class="absolute -inset-0.5" />
            <span class="sr-only">Open main menu</span>
            <Bars3Icon v-if="!open" class="block size-6" aria-hidden="true" />
            <XMarkIcon v-else class="block size-6" aria-hidden="true" />
          </DisclosureButton>
        </div>

        <div class="hidden lg:ml-4 lg:flex lg:items-center">
          <div v-if="isLoggedIn" class="flex items-center">
            <button
              type="button"
              class="relative shrink-0 rounded-full bg-white p-1 text-gray-400 hover:text-gray-500 focus:ring-0 focus:ring-indigo-500 focus:ring-offset-0 focus:outline-none"
            >
              <span class="absolute -inset-1.5" />
              <span class="sr-only">View notifications</span>
              <BellIcon
                :class="[
                  'bell size-6 transition-colors',
                  isRinging ? 'animate-ring text-red-500' : 'text-gray-400',
                ]"
                aria-hidden="true"
              />
              <Transition
                enter-active-class="transition ease-out duration-[5000ms]"
                enter-from-class="opacity-0 scale-75"
                enter-to-class="opacity-100 scale-100"
                leave-active-class="transition ease-in duration-[1000ms]"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 scale-75"
              >
                <span
                  v-if="notificationsCount > 0"
                  class="absolute -top-0.5 -right-0.5 flex h-[18px] min-w-[18px] items-center justify-center rounded-full bg-red-600 px-1 text-[11px] font-semibold text-white ring-2 ring-white"
                >
                  {{ notificationsCount > 99 ? '99+' : notificationsCount }}
                </span>
              </Transition>
            </button>

            <Menu as="div" class="relative ml-4 shrink-0">
              <div>
                <MenuButton
                  class="relative flex rounded-full bg-white text-sm focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none"
                >
                  <span class="absolute -inset-1.5" />
                  <span class="sr-only">Open user menu</span>
                  <img
                    class="size-8 rounded-full"
                    :src="profileImageUrl"
                    alt="User avatar"
                  />
                </MenuButton>
              </div>
              <transition
                enter-active-class="transition ease-out duration-100"
                enter-from-class="transform opacity-0 scale-95"
                enter-to-class="transform opacity-100 scale-100"
                leave-active-class="transition ease-in duration-75"
                leave-from-class="transform opacity-100 scale-100"
                leave-to-class="transform opacity-0 scale-95"
              >
                <MenuItems
                  class="absolute right-0 z-10 mt-2 w-48 origin-top-right rounded-md bg-white py-1 shadow-lg ring-1 ring-black/5 focus:outline-none"
                >
                  <MenuItem
                    v-for="userNavigation in userNavigations"
                    :key="userNavigation.name"
                    v-slot="{ active }"
                  >
                    <component
                      :is="
                        userNavigation.href === route('logout') ?
                          'button'
                        : Link
                      "
                      :href="
                        userNavigation.href !== route('logout') ?
                          userNavigation.href
                        : undefined
                      "
                      :class="[
                        active ? 'bg-gray-100' : '',
                        'block w-full px-4 py-2 text-left text-sm text-gray-700',
                      ]"
                      @click="logout"
                    >
                      {{ userNavigation.name }}
                    </component>
                  </MenuItem>
                </MenuItems>
              </transition>
            </Menu>
          </div>
          <div v-else class="flex items-center space-x-8">
            <Link
              v-for="authNavigation in authNavigations"
              :key="authNavigation.name"
              :href="authNavigation.href"
              class="inline-flex items-center px-1 pt-1 text-sm font-medium text-gray-500"
            >
              {{ authNavigation.name }}
            </Link>
          </div>
        </div>
      </div>
    </div>

    <DisclosurePanel class="lg:hidden">
      <div class="space-y-1 bg-white pt-2 pb-3">
        <DisclosureButton
          v-for="mainNavigation in mainNavigations"
          :key="mainNavigation.name"
          as="a"
          :href="mainNavigation.href"
          :class="mobileNavLinkClasses(mainNavigation.href)"
        >
          {{ mainNavigation.name }}
        </DisclosureButton>
        <template v-if="!isLoggedIn">
          <DisclosureButton
            v-for="authNavigation in authNavigations"
            :key="authNavigation.name"
            as="a"
            :href="authNavigation.href"
            :class="mainNavLinkClasses(authNavigation.href)"
          >
            {{ authNavigation.name }}
          </DisclosureButton>
        </template>
      </div>

      <div
        v-if="isLoggedIn"
        class="border-t border-gray-200 bg-white pt-4 pb-3"
      >
        <div class="flex items-center px-4">
          <div class="shrink-0">
            <img
              class="size-10 rounded-full"
              :src="profileImageUrl"
              alt="User avatar"
            />
          </div>
          <div class="ml-3">
            <div class="text-base font-medium text-gray-800">
              {{ currentUser.username }}
            </div>
            <div class="text-sm font-medium text-gray-500">
              {{ currentUser.email }}
            </div>
          </div>
          <button
            type="button"
            class="relative ml-auto shrink-0 rounded-full bg-white p-1 text-gray-400 hover:text-gray-500 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none"
          >
            <span class="absolute -inset-1.5" />
            <span class="sr-only">View notifications</span>
            <BellIcon
              :class="[
                'bell size-6 transition-colors',
                isRinging ? 'animate-ring text-red-500' : 'text-gray-400',
              ]"
              aria-hidden="true"
            />
            <span
              v-if="notificationsCount > 0"
              class="absolute -top-0.5 -right-0.5 flex h-[18px] min-w-[18px] items-center justify-center rounded-full bg-red-600 px-1 text-[11px] font-semibold text-white ring-2 ring-white"
            >
              {{ notificationsCount > 99 ? '99+' : notificationsCount }}
            </span>
          </button>
        </div>
        <div class="mt-3 space-y-1">
          <DisclosureButton
            v-for="userNavigation in userNavigations"
            :key="userNavigation.name"
            :as="userNavigation.href === route('logout') ? 'button' : 'a'"
            :href="
              userNavigation.href !== route('logout') ?
                userNavigation.href
              : undefined
            "
            class="block px-4 py-2 text-base font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-800"
            @click="logout"
          >
            {{ userNavigation.name }}
          </DisclosureButton>
        </div>
      </div>
    </DisclosurePanel>
  </Disclosure>
</template>

<style scoped>
.bell {
  transform-origin: top center;
}

@keyframes animate-ring {
  0% {
    transform: rotate(0deg);
  }
  10% {
    transform: rotate(15deg);
  }
  20% {
    transform: rotate(-15deg);
  }
  30% {
    transform: rotate(10deg);
  }
  40% {
    transform: rotate(-10deg);
  }
  50% {
    transform: rotate(6deg);
  }
  60% {
    transform: rotate(-6deg);
  }
  70% {
    transform: rotate(0deg);
  }
}

.animate-ring {
  animation: animate-ring 2s ease-in-out;
}
</style>
