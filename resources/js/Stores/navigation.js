import {defineStore} from "pinia";
import {ref} from "vue";

export const useNavigation = defineStore('navigation', () => {
    const landingNavigation = ref([
        {name: 'Product', href: '#'},
        {name: 'Features', href: '#'},
        {name: 'Marketplace', href: '#'},
        {name: 'Company', href: '#'},
    ]);

    const authenticatedNavigation = [
        {name: 'Home', href: route('pages.welcome')},
        {name: 'Team', href: '#'},
        {name: 'Projects', href: '#'},
        {name: 'Calendar', href: '#'},
    ]
    const userNavigation = [
        {name: 'Your Profile', href: route('profile.edit')},
        {name: 'Sign out', href: route('logout')},
    ]

    return {landingNavigation, authenticatedNavigation, userNavigation};
})
