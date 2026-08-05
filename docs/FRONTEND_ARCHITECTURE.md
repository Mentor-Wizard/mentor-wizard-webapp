# Frontend архітектура

## Огляд

Проект використовує сучасну frontend архітектуру з **Vue.js 3** та
**Inertia.js** для створення SPA (Single Page Application) без необхідності
окремого API. Це забезпечує швидкість розробки Laravel з інтерактивністю
сучасних frontend фреймворків.

## Технологічний стек

### Основні технології

```json
{
  "vue": "^3.5.13", // Vue.js 3 з Composition API
  "@inertiajs/vue3": "^2.0.3", // Inertia.js для Vue 3
  "pinia": "^3.0.1", // State management
  "tailwindcss": "^4.1", // CSS framework
  "@headlessui/vue": "^1.7.23", // Headless UI компоненти
  "@heroicons/vue": "^2.2.0", // Іконки
  "vite": "^7.0.0", // Build tool
  "laravel-vite-plugin": "^1.2.0" // Laravel інтеграція
}
```

### Допоміжні бібліотеки

- **axios** - HTTP клієнт
- **laravel-echo** + **pusher-js** - WebSocket та real-time
- **vue-tel-input** - введення телефонних номерів
- **ziggy** - Laravel route helpers в JavaScript

## Архітектурний підхід

### Inertia.js - Hybrid SPA

Inertia.js створює гібрид між класичним server-rendered додатком та SPA:

```
Laravel Backend          Inertia.js          Vue.js Frontend
      ↓                      ↓                      ↓
   Action/Page  →  JSON Response  →  Vue Component
```

**Переваги:**

- Немає необхідності в API
- Швидка розробка
- SEO дружній
- Простіше тестування

## Структура проекту

### Директорії

```
resources/js/
├── Components/           # Повторно використовувані компоненти
│   ├── UI/              # Базові UI компоненти
│   │   ├── Button/      # Кнопки
│   │   ├── Forms/       # Форми
│   │   ├── Logo/        # Логотипи
│   │   └── Table/       # Таблиці
│   ├── Navigation/      # Навігаційні компоненти
│   │   ├── Navbar/      # Верхня панель
│   │   └── Footer.vue   # Підвал
│   └── Modal.vue        # Модальні вікна
├── Pages/               # Сторінки Inertia.js
│   ├── Auth/           # Автентифікація
│   ├── Profile/        # Профілі
│   ├── MentorProgram/  # Програми менторингу
│   └── Welcome.vue     # Головна сторінка
├── Layouts/            # Лейаути
│   ├── AuthenticatedLayout.vue  # Для авторизованих
│   ├── GuestLayout.vue          # Для гостей
│   └── LandingLayout.vue        # Для посадкових
├── app.js              # Головний файл додатка
└── bootstrap.js        # Bootstrap конфігурація
```

> ⚠ Ця структура — для доменів, що ще **не** винесені в `Modules/`. Домен, вже
> мігрований у модульний моноліт (наприклад `Chat`, `Calendar`), тримає власні
> Vue-сторінки та компоненти в
> `Modules/{Name}/resources/js/{Pages,Components}/`, а не тут — модуль DDD
> завжди full-stack (бекенд + Inertia-фронтенд домену). Резолвер сторінок
> (`resources/js/resolvePage.js`) шукає спершу в модулях, потім тут; повні
> конвенції — `docs/MODULAR_ARCHITECTURE.md`.

## Vue.js 3 - Composition API

### Налаштування додатка

```javascript
// resources/js/app.js
import { createInertiaApp } from '@inertiajs/vue3';
import { createApp, h } from 'vue';
import { createPinia } from 'pinia';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

createInertiaApp({
  resolve: (name) =>
    resolvePageComponent(
      `./Pages/${name}.vue`,
      import.meta.glob('./Pages/**/*.vue'),
    ),
  setup({ el, App, props, plugin }) {
    return createApp({ render: () => h(App, props) })
      .use(plugin) // Inertia.js plugin
      .use(createPinia()) // State management
      .use(ZiggyVue) // Laravel routes
      .mount(el);
  },
});
```

### Структура компонента

```vue
<script setup>
// Імпорти
import { ref, computed, onMounted } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

// Props
const props = defineProps({
  user: Object,
  mentorPrograms: Array,
});

// Reactive state
const isLoading = ref(false);
const selectedProgram = ref(null);

// Computed properties
const filteredPrograms = computed(() => {
  return props.mentorPrograms.filter((program) => program.is_active);
});

// Methods
const selectProgram = (program) => {
  selectedProgram.value = program;
};

// Lifecycle hooks
onMounted(() => {
  console.log('Component mounted');
});
</script>

<template>
  <Head title="Dashboard" />

  <AuthenticatedLayout>
    <template #header>
      <h2 class="text-xl font-semibold">Dashboard</h2>
    </template>

    <div class="py-12">
      <!-- Контент -->
    </div>
  </AuthenticatedLayout>
</template>
```

## Компонентна система

### Принципи організації

1. **Атомарний дизайн** - від простих до складних компонентів
2. **Повторне використання** - DRY принцип
3. **Props-based** - передача даних через props
4. **Event-driven** - спілкування через events

### Базові UI компоненти

#### Кнопки

```vue
<!-- Components/UI/Button/PrimaryButton.vue -->
<script setup>
const props = defineProps({
  type: {
    type: String,
    default: 'button',
  },
  disabled: Boolean,
});

const emit = defineEmits(['click']);
</script>

<template>
  <button
    :type="type"
    :disabled="disabled"
    @click="emit('click', $event)"
    class="inline-flex items-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition duration-150 ease-in-out hover:bg-blue-700 focus:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none active:bg-blue-900"
    :class="{
      'opacity-25': disabled,
    }"
  >
    <slot />
  </button>
</template>
```

#### Поля форми

```vue
<!-- Components/UI/Forms/TextInput.vue -->
<script setup>
import { onMounted, ref } from 'vue';

const props = defineProps({
  modelValue: String,
  placeholder: String,
  required: Boolean,
  type: {
    type: String,
    default: 'text',
  },
});

const emit = defineEmits(['update:modelValue']);

const input = ref(null);

const focus = () => input.value.focus();

onMounted(() => {
  if (input.value.hasAttribute('autofocus')) {
    input.value.focus();
  }
});

defineExpose({ focus });
</script>

<template>
  <input
    ref="input"
    :type="type"
    :value="modelValue"
    :placeholder="placeholder"
    :required="required"
    @input="emit('update:modelValue', $event.target.value)"
    class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
  />
</template>
```

### Лейаути

#### Автентифікований лейаут

```vue
<!-- Layouts/AuthenticatedLayout.vue -->
<script setup>
import { ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';

const showingNavigationDropdown = ref(false);
const page = usePage();
</script>

<template>
  <div>
    <div class="min-h-screen bg-gray-100">
      <!-- Navigation -->
      <nav class="border-b border-gray-100 bg-white">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          <div class="flex h-16 justify-between">
            <!-- Logo -->
            <div class="flex shrink-0 items-center">
              <Link :href="route('pages.dashboard')">
                <ApplicationLogo class="block h-9 w-auto" />
              </Link>
            </div>

            <!-- Navigation Links -->
            <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
              <NavLink
                :href="route('pages.dashboard')"
                :active="route().current('pages.dashboard')"
              >
                Dashboard
              </NavLink>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:ms-6 sm:flex sm:items-center">
              <Dropdown align="right" width="48">
                <template #trigger>
                  <button class="inline-flex items-center">
                    {{ page.props.auth.user.username }}
                  </button>
                </template>

                <template #content>
                  <DropdownLink :href="route('profile.edit')">
                    Profile
                  </DropdownLink>
                  <DropdownLink
                    :href="route('logout')"
                    method="post"
                    as="button"
                  >
                    Log Out
                  </DropdownLink>
                </template>
              </Dropdown>
            </div>
          </div>
        </div>
      </nav>

      <!-- Page Heading -->
      <header class="bg-white shadow" v-if="$slots.header">
        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
          <slot name="header" />
        </div>
      </header>

      <!-- Page Content -->
      <main>
        <slot />
      </main>
    </div>
  </div>
</template>
```

## State Management з Pinia

### Налаштування store

```javascript
// stores/auth.js
import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

export const useAuthStore = defineStore('auth', () => {
  // State
  const user = ref(null);
  const isAuthenticated = ref(false);

  // Getters
  const userName = computed(() => user.value?.username ?? 'Guest');
  const hasRole = computed(() => (role) => {
    return user.value?.roles?.includes(role) ?? false;
  });

  // Actions
  function setUser(userData) {
    user.value = userData;
    isAuthenticated.value = !!userData;
  }

  function logout() {
    user.value = null;
    isAuthenticated.value = false;
  }

  return {
    user,
    isAuthenticated,
    userName,
    hasRole,
    setUser,
    logout,
  };
});
```

### Використання в компонентах

```vue
<script setup>
import { useAuthStore } from '@/stores/auth.js';

const authStore = useAuthStore();

// Реактивний доступ до state
console.log(authStore.userName);
console.log(authStore.hasRole('mentor'));

// Виклик actions
authStore.setUser(userData);
</script>
```

## Inertia.js інтеграція

### Основні методи

#### Навігація

```javascript
import { router } from '@inertiajs/vue3';

// GET запит
router.get('/users');

// POST з даними
router.post('/users', {
  name: 'John Doe',
  email: 'john@example.com',
});

// PUT/PATCH оновлення
router.put(`/users/${user.id}`, userData);

// DELETE
router.delete(`/users/${user.id}`);

// З колбеками
router.post('/users', userData, {
  onSuccess: (page) => {
    console.log('Success!');
  },
  onError: (errors) => {
    console.log('Validation errors:', errors);
  },
});
```

#### Форми

```vue
<script setup>
import { useForm } from '@inertiajs/vue3';

const form = useForm({
  name: '',
  email: '',
  password: '',
});

const submit = () => {
  form.post('/register', {
    onSuccess: () => {
      form.reset();
    },
  });
};
</script>

<template>
  <form @submit.prevent="submit">
    <input v-model="form.name" type="text" />
    <div v-if="form.errors.name" class="text-red-600">
      {{ form.errors.name }}
    </div>

    <input v-model="form.email" type="email" />
    <div v-if="form.errors.email" class="text-red-600">
      {{ form.errors.email }}
    </div>

    <button type="submit" :disabled="form.processing">
      <span v-if="form.processing">Loading...</span>
      <span v-else>Register</span>
    </button>
  </form>
</template>
```

#### Часткові оновлення

```javascript
// Оновити тільки певні дані на сторінці
router.reload({ only: ['users', 'posts'] });

// Виключити певні дані
router.reload({ except: ['largeDataSet'] });
```

## Tailwind CSS

### Структура стилів

```css
/* resources/css/app.css */
@tailwind base;
@tailwind components;
@tailwind utilities;

/* Custom компоненти */
@layer components {
  .btn-primary {
    @apply rounded bg-blue-600 px-4 py-2 font-bold text-white hover:bg-blue-700;
  }

  .form-input {
    @apply block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500;
  }
}
```

### Конфігурація

```javascript
// tailwind.config.js
import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

export default {
  content: [
    './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
    './storage/framework/views/*.php',
    './resources/views/**/*.blade.php',
    './resources/js/**/*.vue',
  ],

  theme: {
    extend: {
      fontFamily: {
        sans: ['Figtree', ...defaultTheme.fontFamily.sans],
      },
    },
  },

  plugins: [forms],
};
```

## Headless UI компоненти

### Dropdown приклад

```vue
<script setup>
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue';
</script>

<template>
  <Menu as="div" class="relative inline-block text-left">
    <MenuButton class="btn-primary"> Options </MenuButton>

    <transition
      enter-active-class="transition duration-100 ease-out"
      enter-from-class="transform scale-95 opacity-0"
      enter-to-class="transform scale-100 opacity-1"
      leave-active-class="transition duration-75 ease-in"
      leave-from-class="transform scale-100 opacity-100"
      leave-to-class="transform scale-95 opacity-0"
    >
      <MenuItems
        class="absolute right-0 mt-2 w-56 origin-top-right bg-white shadow-lg"
      >
        <MenuItem v-slot="{ active }">
          <button
            :class="[
              active ? 'bg-gray-100 text-gray-900' : 'text-gray-700',
              'group flex w-full items-center px-2 py-2 text-sm',
            ]"
          >
            Edit
          </button>
        </MenuItem>
      </MenuItems>
    </transition>
  </Menu>
</template>
```

## Heroicons інтеграція

```vue
<script setup>
import { UserIcon, CogIcon, ChevronDownIcon } from '@heroicons/vue/24/outline';
// або solid версії:
// import { UserIcon } from '@heroicons/vue/24/solid';
</script>

<template>
  <div class="flex items-center space-x-2">
    <UserIcon class="h-5 w-5" />
    <span>Profile</span>
    <ChevronDownIcon class="h-4 w-4" />
  </div>
</template>
```

## Real-time функціональність

### Laravel Echo налаштування

```javascript
// resources/js/bootstrap.js
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
  broadcaster: 'pusher',
  key: import.meta.env.VITE_PUSHER_APP_KEY,
  cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
  wsHost:
    import.meta.env.VITE_PUSHER_HOST ?
      import.meta.env.VITE_PUSHER_HOST
    : `ws-${import.meta.env.VITE_PUSHER_APP_CLUSTER}.pusher.io`,
  wsPort: import.meta.env.VITE_PUSHER_PORT ?? 80,
  wssPort: import.meta.env.VITE_PUSHER_PORT ?? 443,
  forceTLS: (import.meta.env.VITE_PUSHER_SCHEME ?? 'https') === 'https',
  enabledTransports: ['ws', 'wss'],
});
```

### Використання в компонентах

```vue
<script setup>
import { onMounted, onUnmounted, ref } from 'vue';

const messages = ref([]);

onMounted(() => {
  // Слухання приватного каналу
  window.Echo.private(`chat.${props.chatId}`).listen('MessageSent', (e) => {
    messages.value.push(e.message);
  });

  // Слухання присутності
  window.Echo.join(`chat.${props.chatId}`)
    .here((users) => {
      console.log('Users currently in chat:', users);
    })
    .joining((user) => {
      console.log('User joined:', user);
    })
    .leaving((user) => {
      console.log('User left:', user);
    });
});

onUnmounted(() => {
  window.Echo.leaveChannel(`chat.${props.chatId}`);
});
</script>
```

## Тестування frontend коду

### Vitest налаштування

```javascript
// vitest.config.js
import { defineConfig } from 'vitest/config';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
  plugins: [vue()],
  test: {
    globals: true,
    environment: 'jsdom',
  },
  resolve: {
    alias: {
      '@': '/resources/js',
    },
  },
});
```

### Приклад тесту компонента

```javascript
// tests/js/Components/PrimaryButton.test.js
import { mount } from '@vue/test-utils';
import { describe, it, expect } from 'vitest';
import PrimaryButton from '@/Components/UI/Button/PrimaryButton.vue';

describe('PrimaryButton', () => {
  it('renders button with correct text', () => {
    const wrapper = mount(PrimaryButton, {
      slots: {
        default: 'Click me',
      },
    });

    expect(wrapper.text()).toBe('Click me');
    expect(wrapper.find('button').exists()).toBe(true);
  });

  it('emits click event when clicked', async () => {
    const wrapper = mount(PrimaryButton);

    await wrapper.find('button').trigger('click');

    expect(wrapper.emitted()).toHaveProperty('click');
  });

  it('is disabled when disabled prop is true', () => {
    const wrapper = mount(PrimaryButton, {
      props: {
        disabled: true,
      },
    });

    expect(wrapper.find('button').attributes('disabled')).toBeDefined();
  });
});
```

## Build та розгортання

### Vite конфігурація

```javascript
// vite.config.js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
  plugins: [
    laravel({
      input: 'resources/js/app.js',
      ssr: 'resources/js/ssr.js',
      refresh: true,
    }),
    vue({
      template: {
        transformAssetUrls: {
          base: null,
          includeAbsolute: false,
        },
      },
    }),
  ],
  resolve: {
    alias: {
      '@': '/resources/js',
    },
  },
});
```

### Команди збирання

```bash
# Розробка
yarn dev

# Production збірка
yarn build

# SSR збірка (опціонально)
yarn build --ssr
```

## Best Practices

### Компонентна архітектура

1. **Single File Components** - один файл на компонент
2. **Composition API** - для логіки
3. **Props validation** - завжди валідуйте props
4. **Event naming** - використовуйте kebab-case для custom events

### Продуктивність

```vue
<script setup>
// ✅ Добре - lazy loading компонентів
const LazyComponent = defineAsyncComponent(() => import('./LazyComponent.vue'));

// ✅ Добре - мемоізація обчислень
const expensiveValue = computed(() => {
  return heavyCalculation(props.data);
});

// ✅ Добре - оптимізація v-for
</script>

<template>
  <!-- ✅ Добре - key для списків -->
  <div v-for="item in items" :key="item.id">
    {{ item.name }}
  </div>

  <!-- ✅ Добре - v-show для часто перемикаємих елементів -->
  <div v-show="isVisible">Content</div>

  <!-- ✅ Добре - v-if для рідко змінюваних умов -->
  <div v-if="hasPermission">Admin panel</div>
</template>
```

### Налагодження

```vue
<script setup>
// Vue DevTools підтримка
import { onMounted } from 'vue';

onMounted(() => {
  if (process.env.NODE_ENV === 'development') {
    console.log('Component data:', props, state);
  }
});
</script>
```

### SEO оптимізація

```vue
<script setup>
import { Head } from '@inertiajs/vue3';

// SEO метадані
const pageTitle = computed(() => `${props.mentor.name} - Mentor Profile`);
const pageDescription = computed(
  () => `Learn from ${props.mentor.name}. ${props.mentor.bio}`,
);
</script>

<template>
  <Head :title="pageTitle">
    <meta name="description" :content="pageDescription" />
    <meta property="og:title" :content="pageTitle" />
    <meta property="og:description" :content="pageDescription" />
    <meta property="og:image" :content="props.mentor.avatar_url" />
  </Head>
</template>
```

Ця архітектура забезпечує масштабований, підтримуваний та продуктивний frontend
для Mentor Wizard платформи.
