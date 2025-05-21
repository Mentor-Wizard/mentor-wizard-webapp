import {defineStore} from "pinia";
import {ref} from "vue";
import { computed } from 'vue';
import {usePage} from "@inertiajs/vue3";

export const useNavigation = defineStore('navigation', () => {
    const page = usePage();
    const userRoles = computed(() => page.props.auth?.roles ?? {});
    const isMentor = computed(() => userRoles.value.includes('mentor'));

    const landingNavigation = ref([
        {name: 'Product', href: '#'},
        {name: 'Features', href: '#'},
        {name: 'Marketplace', href: '#'},
        {name: 'Company', href: '#'},
    ]);

    const authenticatedNavigation = computed(() => {
        const base = [
            { name: 'Dashboard', href: route('pages.dashboard') },
            { name: 'Team', href: '#' },
            { name: 'Projects', href: '#' },
            { name: 'Calendar', href: '#' },
        ];
        if (isMentor.value) {
            base.push({ name: 'Mentor Programs', href: route('mentor-program.list') });
        }
        return base;
    });

    const userNavigation = [
        {name: 'Your Profile', href: route('profile.edit')},
        {name: 'Sign out', href: route('logout')},
    ]
    const authNavigation = [
        {name: 'Sign In', href: route('login')},
        {name: 'Register', href: route('register')},
    ]

    return {landingNavigation, authenticatedNavigation, userNavigation, authNavigation};
})
