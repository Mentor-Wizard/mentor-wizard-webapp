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
        { name: 'Dashboard', href: route('pages.welcome'), current: true },
        { name: 'Team', href: '#', current: false },
        { name: 'Projects', href: '#', current: false },
        { name: 'Calendar', href: '#', current: false },
    ]
    const userNavigation = [
        { name: 'Your Profile', href:route('profile.edit')  },
        { name: 'Sign out', href: route('logout'), method: 'post'},
    ]

    return {landingNavigation, authenticatedNavigation, userNavigation};
})
