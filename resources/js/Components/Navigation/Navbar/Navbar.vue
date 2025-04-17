<script setup>
import {
    Disclosure,
    DisclosureButton, DisclosurePanel,
    Menu,
    MenuButton,
    MenuItem,
    MenuItems,
} from '@headlessui/vue'
import {MagnifyingGlassIcon} from '@heroicons/vue/20/solid'
import {Bars3Icon, BellIcon, XMarkIcon} from '@heroicons/vue/24/outline'
import ApplicationLogo from "@/Components/UI/Logo/ApplicationLogo.vue";
import {usePage, router, Link} from '@inertiajs/vue3';
import {useNavigation} from "@/Stores/navigation.js";
import {computed, reactive} from "vue";

defineProps({
    transparent: {
        type: Boolean,
        default: false,
    },
});

const page = usePage();
const user = reactive(page.props.auth?.user ?? {});
const mainNavigations = computed(() => {
    return page.props.auth?.user?.email ? useNavigation().authenticatedNavigation : useNavigation().landingNavigation;
});
const userNavigations = useNavigation().userNavigation;

console.log(user.username);

function logout() {
    router.post('/logout', {});
}
</script>

<template>
    <Disclosure as="nav" :class="{'bg-transparent': transparent, 'bg-white': !transparent}" class="shadow-sm"
                v-slot="{ open }">
        <div class="mx-auto max-w-7xl px-2 sm:px-4 lg:px-8">
            <div class="flex h-16 justify-between">
                <div class="flex px-2 lg:px-0">
                    <div class="flex shrink-0 items-center p-1">
                        <ApplicationLogo/>
                    </div>
                    <div class="hidden lg:ml-6 lg:flex lg:space-x-8">
                        <Link v-for="mainNavigation in mainNavigations" :href="mainNavigation.href"
                              :class="{
                            'border-b-2 border-indigo-500 text-gray-900': page.props.ziggy.location === mainNavigation.href,
                            'border-b-2 border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700': !page.props.ziggy.location === mainNavigation.href
                        }"
                              class="inline-flex items-center px-1 pt-1 text-sm font-medium"
                        >
                            {{ mainNavigation.name }}
                        </Link>
                    </div>
                </div>
                <!--  Search  -->
                <div class="flex flex-1 items-center justify-center px-2 lg:ml-6 lg:justify-end">
                    <div class="grid w-full max-w-lg grid-cols-1 lg:max-w-xs">
                        <input type="search" name="search"
                               class="col-start-1 row-start-1 block w-full rounded-md bg-white py-1.5 pr-3 pl-10 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6"
                               placeholder="Search"/>
                        <MagnifyingGlassIcon
                            class="pointer-events-none col-start-1 row-start-1 ml-3 size-5 self-center text-gray-400"
                            aria-hidden="true"/>
                    </div>
                </div>
                <div class="flex items-center lg:hidden">
                    <!-- Mobile menu button -->
                    <DisclosureButton
                        class="relative inline-flex items-center justify-center rounded-md p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-500 focus:ring-2 focus:ring-indigo-500 focus:outline-hidden focus:ring-inset">
                        <span class="absolute -inset-0.5"/>
                        <span class="sr-only">Open main menu</span>
                        <Bars3Icon v-if="!open" class="block size-6" aria-hidden="true"/>
                        <XMarkIcon v-else class="block size-6" aria-hidden="true"/>
                    </DisclosureButton>
                </div>
                <div class="hidden lg:ml-4 lg:flex lg:items-center" v-if="user.username !== undefined">
                    <button type="button"
                            class="relative shrink-0 rounded-full bg-white p-1 text-gray-400 hover:text-gray-500 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-hidden">
                        <span class="absolute -inset-1.5"/>
                        <span class="sr-only">View notifications</span>
                        <BellIcon class="size-6" aria-hidden="true"/>
                    </button>

                    <!-- Profile dropdown -->
                    <Menu as="div" class="relative ml-4 shrink-0" v-if="user.username !== undefined">
                        <div>
                            <MenuButton
                                class="relative flex rounded-full bg-white text-sm focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-hidden">
                                <span class="absolute -inset-1.5"/>
                                <span class="sr-only">Open user menu</span>
                                <img class="size-8 rounded-full"
                                     src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80"
                                     alt=""/>
                            </MenuButton>
                        </div>
                        <transition enter-active-class="transition ease-out duration-100"
                                    enter-from-class="transform opacity-0 scale-95"
                                    enter-to-class="transform opacity-100 scale-100"
                                    leave-active-class="transition ease-in duration-75"
                                    leave-from-class="transform opacity-100 scale-100"
                                    leave-to-class="transform opacity-0 scale-95">
                            <MenuItems
                                class="absolute right-0 z-10 mt-2 w-48 origin-top-right rounded-md bg-white py-1 shadow-lg ring-1 ring-black/5 focus:outline-hidden">
                                <MenuItem v-slot="{ active }" v-for="userNavigation in userNavigations">
                                    <DisclosureButton as="a" :href="userNavigation.href === route('logout') ? '#' : userNavigation.href"
                                          :class="[active ? 'bg-gray-100 outline-hidden' : '', 'block px-4 py-2 text-sm text-gray-700']"
                                          @click="userNavigation.href === route('logout') ? logout() : null"
                                    >
                                        {{ userNavigation.name }}
                                    </DisclosureButton>
                                </MenuItem>
                            </MenuItems>
                        </transition>
                    </Menu>
                </div>
            </div>
        </div>

        <DisclosurePanel class="lg:hidden" v-if="user.username !== undefined">
            <div class="bg-white space-y-1 pt-2 pb-3">
                <!-- Current: "bg-indigo-50 border-indigo-500 text-indigo-700", Default: "border-transparent text-gray-600 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-800" -->
                <DisclosureButton as="a" href="#"
                                  class="block border-l-4 border-indigo-500 bg-indigo-50 py-2 pr-4 pl-3 text-base font-medium text-indigo-700">
                    Dashboard
                </DisclosureButton>
                <DisclosureButton as="a" href="#"
                                  class="block border-l-4 border-transparent py-2 pr-4 pl-3 text-base font-medium text-gray-600 hover:border-gray-300 hover:bg-gray-50 hover:text-gray-800">
                    Team
                </DisclosureButton>
                <DisclosureButton as="a" href="#"
                                  class="block border-l-4 border-transparent py-2 pr-4 pl-3 text-base font-medium text-gray-600 hover:border-gray-300 hover:bg-gray-50 hover:text-gray-800">
                    Projects
                </DisclosureButton>
                <DisclosureButton as="a" href="#"
                                  class="block border-l-4 border-transparent py-2 pr-4 pl-3 text-base font-medium text-gray-600 hover:border-gray-300 hover:bg-gray-50 hover:text-gray-800">
                    Calendar
                </DisclosureButton>
            </div>
            <div class="bg-white border-t border-gray-200 pt-4 pb-3" v-if="user.username !== undefined">
                <div class="flex items-center px-4">
                    <div class="shrink-0">
                        <img class="size-10 rounded-full"
                             src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80"
                             alt=""/>
                    </div>
                    <div class="ml-3">
                        <div class="text-base font-medium text-gray-800">{{ user.username }}</div>
                        <div class="text-sm font-medium text-gray-500">{{ user.email }}</div>
                    </div>
                    <button type="button"
                            class="relative ml-auto shrink-0 rounded-full bg-white p-1 text-gray-400 hover:text-gray-500 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-hidden">
                        <span class="absolute -inset-1.5"/>
                        <span class="sr-only">View notifications</span>
                        <BellIcon class="size-6" aria-hidden="true"/>
                    </button>
                </div>
                <div class="mt-3 space-y-1">
                    <DisclosureButton as="a" :href="route('profile.edit')"
                                      class="block px-4 py-2 text-base font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-800">
                        Your Profile
                    </DisclosureButton>
                    <DisclosureButton as="a" href="#"
                                      class="block px-4 py-2 text-base font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-800">
                        Settings
                    </DisclosureButton>
                    <DisclosureButton @click="logout" as="a" href="#"
                                      class="block px-4 py-2 text-base font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-800">
                        Sign out
                    </DisclosureButton>
                </div>
            </div>
        </DisclosurePanel>
    </Disclosure>
</template>
