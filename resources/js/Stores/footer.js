import { defineStore } from 'pinia';

export const useFooter = defineStore('footer', () => {
  const navigation = {
    solutions: [
      { name: 'Hosting', href: '#' },
      { name: 'Data services', href: '#' },
      { name: 'Uptime monitoring', href: '#' },
      { name: 'Enterprise services', href: '#' },
      { name: 'Analytics', href: '#' },
    ],
    support: [
      { name: 'Submit ticket', href: '#' },
      { name: 'Documentation', href: '#' },
      { name: 'Guides', href: '#' },
    ],
    company: [
      { name: 'About', href: '#' },
      { name: 'Blog', href: '#' },
      { name: 'Jobs', href: '#' },
      { name: 'Press', href: '#' },
    ],
    legal: [
      { name: 'Terms of service', href: '#' },
      { name: 'Privacy policy', href: '#' },
      { name: 'License', href: '#' },
    ],
  };

  return { navigation };
});
