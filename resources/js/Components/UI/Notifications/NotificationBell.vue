<script setup>
import { BellIcon } from '@heroicons/vue/24/outline';
import { router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';

const page = usePage();
const open = ref(false);
const notifications = ref([]);
const unreadCount = ref(page.props.notifications?.unreadCount ?? 0);

const userId = computed(() => page.props.auth?.user?.id);

const fetchNotifications = async () => {
  try {
    const res = await fetch(route('notifications.index'), {
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    });

    if (!res.ok) {
      throw new Error(`Failed to fetch notifications: ${res.status}`);
    }

    const data = await res.json();
    notifications.value = data;
    unreadCount.value = data.filter((n) => !n.read_at).length;
  } catch (error) {
    console.error(error);
  }
};

const markAsRead = async (id) => {
  try {
    const res = await fetch(route('notifications.read', { id }), {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN':
          document.querySelector('meta[name="csrf-token"]')?.content ?? '',
        'X-Requested-With': 'XMLHttpRequest',
      },
    });

    if (!res.ok) {
      throw new Error(`Failed to mark notification as read: ${res.status}`);
    }

    const n = notifications.value.find((x) => x.id === id);
    if (n) {
      n.read_at = new Date().toISOString();
    }
    unreadCount.value = notifications.value.filter((x) => !x.read_at).length;
  } catch (error) {
    console.error(error);
  }
};

const markAllRead = async () => {
  try {
    const res = await fetch(route('notifications.read-all'), {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN':
          document.querySelector('meta[name="csrf-token"]')?.content ?? '',
        'X-Requested-With': 'XMLHttpRequest',
      },
    });

    if (!res.ok) {
      throw new Error(
        `Failed to mark all notifications as read: ${res.status}`,
      );
    }

    notifications.value.forEach((n) => {
      if (!n.read_at) {
        n.read_at = new Date().toISOString();
      }
    });
    unreadCount.value = 0;
  } catch (error) {
    console.error(error);
  }
};

const toggle = () => {
  open.value = !open.value;
  if (open.value) {
    fetchNotifications();
  }
};

const closeOnOutsideClick = (e) => {
  if (!e.target.closest('[data-notification-bell]')) {
    open.value = false;
  }
};

const formatDate = (iso) => {
  if (!iso) {
    return '';
  }
  return new Date(iso).toLocaleString('en-GB', {
    day: 'numeric',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit',
  });
};

const navigateToEvent = async (notification) => {
  if (!notification.read_at) {
    await markAsRead(notification.id);
  }
  open.value = false;
  if (notification.data?.calendar_event_id) {
    router.visit(
      route('pages.calendar.show', { id: notification.data.calendar_event_id }),
    );
  }
};

let echoChannel = null;

onMounted(() => {
  document.addEventListener('click', closeOnOutsideClick);

  if (window.Echo && userId.value) {
    echoChannel = window.Echo.private(`App.Models.User.${userId.value}`);
    echoChannel.notification((notification) => {
      unreadCount.value += 1;
      notifications.value.unshift({
        id: notification.id ?? crypto.randomUUID(),
        read_at: null,
        created_at: new Date().toISOString(),
        data: notification,
      });
    });
  }
});

onUnmounted(() => {
  document.removeEventListener('click', closeOnOutsideClick);
  if (echoChannel) {
    echoChannel.stopListening(
      '.Illuminate\\Notifications\\Events\\BroadcastNotificationCreated',
    );
  }
});
</script>

<template>
  <div class="relative" data-notification-bell>
    <button
      type="button"
      class="relative shrink-0 rounded-full bg-white p-1 text-gray-400 hover:text-gray-500 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none"
      @click.stop="toggle"
    >
      <span class="absolute -inset-1.5" />
      <span class="sr-only">View notifications</span>
      <BellIcon class="size-6" aria-hidden="true" />
      <span
        v-if="unreadCount > 0"
        class="absolute -top-1 -right-1 flex size-4 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white"
      >
        {{ unreadCount > 9 ? '9+' : unreadCount }}
      </span>
    </button>

    <transition
      enter-active-class="transition ease-out duration-100"
      enter-from-class="transform opacity-0 scale-95"
      enter-to-class="transform opacity-100 scale-100"
      leave-active-class="transition ease-in duration-75"
      leave-from-class="transform opacity-100 scale-100"
      leave-to-class="transform opacity-0 scale-95"
    >
      <div
        v-if="open"
        class="absolute right-0 z-20 mt-2 w-80 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black/5 focus:outline-none"
      >
        <div
          class="flex items-center justify-between border-b border-gray-100 px-4 py-3"
        >
          <span class="text-sm font-semibold text-gray-900">Notifications</span>
          <button
            v-if="unreadCount > 0"
            type="button"
            class="text-xs text-indigo-600 hover:text-indigo-800"
            @click="markAllRead"
          >
            Mark all read
          </button>
        </div>

        <div class="max-h-96 overflow-y-auto">
          <div
            v-if="notifications.length === 0"
            class="px-4 py-6 text-center text-sm text-gray-500"
          >
            No notifications yet.
          </div>

          <button
            v-for="notification in notifications"
            :key="notification.id"
            type="button"
            class="flex w-full items-start gap-3 px-4 py-3 text-left hover:bg-gray-50"
            :class="{ 'bg-indigo-50': !notification.read_at }"
            @click="navigateToEvent(notification)"
          >
            <span
              class="mt-1.5 size-2 shrink-0 rounded-full"
              :class="notification.read_at ? 'bg-gray-300' : 'bg-indigo-500'"
            />
            <div class="min-w-0 flex-1">
              <p class="truncate text-sm font-medium text-gray-900">
                {{ notification.data?.title ?? 'Notification' }}
              </p>
              <p class="mt-0.5 text-xs text-gray-500">
                {{ notification.data?.message ?? '' }}
              </p>
              <p class="mt-1 text-xs text-gray-400">
                {{ formatDate(notification.created_at) }}
              </p>
            </div>
          </button>
        </div>
      </div>
    </transition>
  </div>
</template>
