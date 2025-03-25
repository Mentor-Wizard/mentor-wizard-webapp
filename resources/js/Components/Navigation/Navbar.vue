<template>
    <!-- When the mobile menu is open, add `overflow-hidden` to the `body` element to prevent double scrollbars -->
    <Popover as="template" v-slot="{ open }">
        <header
            :class="[
                open ? 'fixed inset-0 z-40 overflow-y-auto' : '',
                'bg-white dark:bg-gray-800 shadow-xs lg:static lg:overflow-y-visible'
            ]"
        >
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="relative flex justify-between lg:gap-8 ">
                    <!--                   -->
                    <div class="flex md:absolute md:inset-y-0 md:left-0  lg:static xl:col-span-2">
                        <ApplicationLogo></ApplicationLogo>
                    </div>

                    <div class="flex items-center px-6 py-4 md:mx-auto md:max-w-3xl lg:mx-0 lg:max-w-none xl:px-0">
                        <nav v-if="isAuthenticated"
                             class="hidden lg:flex lg:space-x-4 lg:py-2 md:flex md:space-x-2 md:ml-2 md:py-1"
                             aria-label="Global">
                            <a v-for="item in userNavigation" :key="item.name" @click="routerLink(item.name)"
                               :class="[item.current
                                ? 'bg-gray-100 text-gray-900 dark:bg-gray-700 dark:text-white'
                                : 'text-gray-900 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white',
                            'inline-flex items-center rounded-md px-3 py-2 text-sm font-medium'
                        ]"
                               :aria-current="item.current ? 'page' : undefined">{{ item.name }}</a>
                        </nav>
                        <div class="grid w-full grid-cols-1">
                            <input type="search" name="search"
                                   class="col-start-1 row-start-1 block w-full rounded-md bg-white dark:bg-gray-700 py-1.5 pr-3 pl-10 text-base text-gray-900 dark:text-white outline-1 -outline-offset-1 outline-gray-300 dark:outline-gray-600 placeholder:text-gray-400 dark:placeholder:text-gray-300"
                                   placeholder="Search"/>
                            <MagnifyingGlassIcon
                                class="pointer-events-none col-start-1 row-start-1 ml-3 size-5 self-center text-gray-400"
                                aria-hidden="true"/>
                        </div>
                    </div>
                    <div class="flex items-center md:absolute md:inset-y-0 md:right-0 lg:hidden md:hidden">
                        <!-- Mobile menu button -->
                        <PopoverButton
                            class="relative -mx-2 inline-flex items-center justify-center rounded-md p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-500 focus:ring-2 focus:ring-indigo-500 focus:outline-hidden focus:ring-inset">
                            <span class="absolute -inset-0.5"/>
                            <span class="sr-only">Open menu</span>
                            <Bars3Icon v-if="!open" class="block size-6" aria-hidden="true"/>
                            <XMarkIcon v-else class="block size-6" aria-hidden="true"/>
                        </PopoverButton>
                    </div>
                    <div
                        class="hidden lg:flex lg:items-center lg:justify-end xl:col-span-4 md:flex md:items-center md:justify-end">
                        <div v-if="!isAuthenticated">
                            <Link
                                :href="route('login')"
                                class="rounded-md px-3 py-2 text-black ring-1 ring-transparent transition hover:text-black/70 focus:outline-hidden focus-visible:ring-[#FF2D20] dark:text-white dark:hover:text-white/80 dark:focus-visible:ring-white"
                            >
                                Log in
                            </Link>

                            <Link
                                :href="route('register')"
                                class="rounded-md px-3 py-2 text-black ring-1 ring-transparent transition hover:text-black/70 focus:outline-hidden focus-visible:ring-[#FF2D20] dark:text-white dark:hover:text-white/80 dark:focus-visible:ring-white"
                            >
                                Register
                            </Link>
                        </div>
                        <button v-if="isAuthenticated" type="button"
                                class="relative ml-5 shrink-0 rounded-full   dark:bg-gray-700 dark:text-white' bg-white p-1 text-gray-400 hover:text-gray-500 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-hidden">
                            <span class="absolute -inset-1.5"/>
                            <span class="sr-only">View notifications</span>
                            <BellIcon class="size-6" aria-hidden="true"/>
                        </button>
                        <!-- Profile dropdown -->
                        <Menu v-if="isAuthenticated" as="div" class="relative ml-5 shrink-0">
                            <div>
                                <MenuButton
                                    class="relative flex rounded-full  dark:bg-gray-700 dark:text-white' bg-white focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-hidden">
                                    <span class="absolute -inset-1.5"/>
                                    <span class="sr-only">Open user menu</span>
                                    <img class="size-8 rounded-full" :src="user.imageUrl" alt=""/>
                                </MenuButton>
                            </div>
                            <transition enter-active-class="transition ease-out duration-100"
                                        enter-from-class="transform opacity-0 scale-95"
                                        enter-to-class="transform opacity-100 scale-100"
                                        leave-active-class="transition ease-in duration-75"
                                        leave-from-class="transform opacity-100 scale-100"
                                        leave-to-class="transform opacity-0 scale-95">
                                <MenuItems
                                    class="absolute right-0 z-10 mt-2 w-48 origin-top-right rounded-md bg-white py-1 ring-1 shadow-lg ring-black/5 focus:outline-hidden">
                                    <MenuItem v-for="item in page.props.userNavigation" :key="item.name"
                                              @click="routerLink(item.name)" v-slot="{ active }">
                                        <a
                                            :class="[active ? 'bg-gray-100 outline-hidden' : '', 'block px-4 py-2 text-sm text-gray-700']">{{
                                                item.name
                                            }}</a>
                                    </MenuItem>
                                </MenuItems>
                            </transition>
                        </Menu>
                    </div>
                </div>
            </div>
            <PopoverPanel v-if="isAuthenticated" as="nav" class="lg:hidden" aria-label="Global">
                <div class="mx-auto max-w-3xl space-y-1 px-2 pt-2 pb-3 sm:px-4">
                    <a v-for="item in userNavigation" :key="item.name" :href="item.href"
                       :aria-current="item.current ? 'page' : undefined"
                       :class="[item.current ? 'bg-gray-500 text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700' : 'hover:bg-gray-250 dark:bg-gray-800 dark:text-white'
 , 'block rounded-md px-3 py-2 text-base font-medium']">{{
                            item.name
                        }}</a>
                </div>
                <div class="border-t border-gray-200 pt-4 pb-3">
                    <div class="mx-auto flex max-w-3xl items-center px-1 sm:px-6">
                        <div class="shrink-0">
                            <img class="size-10 rounded-full" :src="user.imageUrl" alt=""/>
                        </div>
                        <div class="ml-3">
                            <div class="text-base font-medium text-gray-800">{{ user.name }}</div>
                            <div class="text-sm font-medium text-gray-500">{{ user.email }}</div>
                        </div>
                        <button type="button"
                                class="relative ml-auto shrink-0 rounded-full bg-white p-1 text-gray-400 dark:bg-gray-700 dark:text-white'

 hover:text-gray-500 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-hidden">
                            <span class="absolute -inset-1.5"/>
                            <span class="sr-only">View notifications</span>
                            <BellIcon class="size-6" aria-hidden="true"/>
                        </button>
                    </div>
                    <div class="mx-auto mt-3 max-w-3xl space-y-1 px-2 sm:px-4">
                        <a v-for="item in page.props.userNavigation" :key="item.name" @click="routerLink(item.name)"
                           class="block rounded-md px-3 py-2 text-base font-medium text-gray-500 hover:bg-gray-50 hover:text-gray-900">{{
                                item.name
                            }}</a>
                    </div>
                </div>
            </PopoverPanel>
        </header>
    </Popover>
</template>

<script setup>
import {Menu, MenuButton, MenuItem, MenuItems, Popover, PopoverButton, PopoverPanel} from '@headlessui/vue'
import {MagnifyingGlassIcon} from '@heroicons/vue/20/solid'
import {Bars3Icon, BellIcon, XMarkIcon} from '@heroicons/vue/24/outline'
import {Link} from "@inertiajs/vue3"
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import {usePage, router} from '@inertiajs/vue3';
import {reactive} from 'vue';
import { route } from 'ziggy-js';
const page = usePage();
defineProps({
    isAuthenticated: {
        type: Boolean,
    },
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
});
const routerLink = ((itemName) => {
    console.log(itemName);
    if (itemName === 'Sign out') {
        logout();
    }
})
function logout() {
    router.post('/logout', {}, {
        onSuccess: () => {
            console.log('Logout successful!');
        },
        onError: (errors) => {
            console.error('Error during logout:', errors);
        },
    });
}
const user = {
    name: page.props.auth?.user?.username,
    email: page.props.auth?.user?.email,
    imageUrl: page.props.auth?.user?.imageUrl,
}
const userNavigation = reactive(page.props.navigation);
</script>
