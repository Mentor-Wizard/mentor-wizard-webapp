<script setup>
import {
  InformationCircleIcon,
  QuestionMarkCircleIcon,
} from '@heroicons/vue/20/solid';
import { usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import GuideCarouselModal from '@/Components/GuideCarouselModal.vue';
import PopUp from '@/Components/UI/Notifications/PopUp.vue';
import { useExternalCalendar } from '@/Composables/useExternalCalendar';

const page = usePage();
const providers = page.props.calendarIntegrations ?? [];

const {
  notification,
  availableCalendars,
  calendarProviderForSelection,
  credentialForms,
  authorize,
  authorizeCalDav,
  selectCalendar,
  disconnect,
  retrySync,
} = useExternalCalendar(providers);

const googleProvider = computed(() =>
  page.props.calendarIntegrations?.find((p) => p.key === 'google'),
);
const googlePersonalProvider = computed(() =>
  page.props.calendarIntegrations?.find((p) => p.key === 'google_personal_app'),
);
const appleProvider = computed(() =>
  page.props.calendarIntegrations?.find((p) => p.key === 'apple'),
);
const otherProviders = computed(
  () =>
    page.props.calendarIntegrations?.filter(
      (p) => !['google', 'google_personal_app', 'apple'].includes(p.key),
    ) ?? [],
);

const googleConnected = computed(
  () => googleProvider.value?.connected && !googleProvider.value?.needs_reauth,
);
const googlePersonalConnected = computed(
  () =>
    googlePersonalProvider.value?.connected
    && !googlePersonalProvider.value?.needs_reauth,
);

// Which Google integration is currently active (drives status badge / calendar name)
const activeGoogleIntegration = computed(() => {
  if (googleConnected.value) return googleProvider.value;
  if (googlePersonalConnected.value) return googlePersonalProvider.value;
  if (googleProvider.value?.needs_reauth) return googleProvider.value;
  if (googlePersonalProvider.value?.needs_reauth)
    return googlePersonalProvider.value;
  return googleProvider.value;
});

const anyGoogleConnected = computed(
  () => googleConnected.value || googlePersonalConnected.value,
);

// Toggle: show personal-credentials form instead of the one-click authorize button.
// Pre-selected when the personal variant is connected or needs re-auth.
const usePersonalGoogle = ref(
  googlePersonalProvider.value?.connected === true
    || googlePersonalProvider.value?.needs_reauth === true,
);

const googleGuideSteps = [
  {
    image: '/images/guides/google-calendar/step-1.png',
    caption:
      'Go to console.cloud.google.com. Create a new project or select an existing one.',
  },
  {
    image: '/images/guides/google-calendar/step-2.png',
    caption: 'Open the left menu → "APIs & Services".',
  },
  {
    image: '/images/guides/google-calendar/step-3.png',
    caption:
      'Click "Enable APIs and Services", search for "Google Calendar API" and enable it.',
  },
  {
    image: '/images/guides/google-calendar/step-4.png',
    caption: 'Go to "Credentials" → "Create Credentials" → "OAuth client ID".',
  },
  {
    image: '/images/guides/google-calendar/step-5.png',
    caption:
      'Choose "Web application". Under "Authorized JavaScript origins" add your site URL. Under "Authorized redirect URIs" add the callback URL shown in the form.',
  },
  {
    image: '/images/guides/google-calendar/step-6.png',
    caption:
      'After creation, copy the Client ID and Client Secret and paste them into the form.',
  },
];

const callbackUrl = `${window.location.origin}/settings/external-calendar/callback/google`;

const guideModal = ref(null);
const appleGuideModal = ref(null);

const appleGuideSteps = [
  {
    image: '/images/guides/apple-calendar/step-1.png',
    caption: 'Go to appleid.apple.com and sign in with your Apple Account.',
  },
  {
    image: '/images/guides/apple-calendar/step-2.png',
    caption:
      'Navigate to "Sign-In and Security" → "App-Specific Passwords" and click "Generate an app-specific password".',
  },
  {
    image: '/images/guides/apple-calendar/step-3.png',
    caption:
      'Enter any name — it\'s just for your reference (e.g. "MentorWizard"). Tap Create and copy the generated password.',
  },
  {
    image: '/images/guides/apple-calendar/step-4.png',
    caption:
      'Enter your Apple Account email and the app-specific password in the form below, then click "Connect Apple Calendar".',
  },
];

const statusLabels = {
  active: 'Connected',
  pending: 'Pending',
  error: 'Error',
  disconnected: 'Disconnected',
};
const statusClasses = {
  active: 'bg-green-100 text-green-800',
  pending: 'bg-yellow-100 text-yellow-800',
  error: 'bg-red-100 text-red-800',
  disconnected: 'bg-gray-100 text-gray-600',
};

const providerLabels = {
  outlook: 'Outlook Calendar',
  apple: 'Apple Calendar',
};
</script>

<template>
  <div class="mt-6">
    <h2 class="text-base font-semibold text-gray-900">Connected Calendars</h2>
    <p class="mt-1 text-sm text-gray-500">
      Confirmed sessions will be automatically synced to your connected
      calendars.
    </p>

    <ul class="mt-6 divide-y divide-gray-200">
      <!-- Google Calendar -->
      <li v-if="googleProvider || googlePersonalProvider" class="py-8">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-900">Google Calendar</p>
            <p
              v-if="googlePersonalConnected"
              class="mt-0.5 text-xs text-gray-400"
            >
              Connected via your personal Google Cloud app
            </p>
            <p v-else-if="googleConnected" class="mt-0.5 text-xs text-gray-400">
              Connected via shared app
            </p>
            <p
              v-if="activeGoogleIntegration?.calendar_name"
              class="mt-0.5 text-xs text-gray-500"
            >
              Calendar: {{ activeGoogleIntegration.calendar_name }}
            </p>
            <p
              v-if="activeGoogleIntegration?.last_synced_at"
              class="mt-0.5 text-xs text-gray-400"
            >
              Last synced:
              {{
                new Date(
                  activeGoogleIntegration.last_synced_at,
                ).toLocaleString()
              }}
            </p>
            <p
              v-if="activeGoogleIntegration?.last_error_message"
              class="mt-0.5 text-xs text-red-500"
            >
              {{ activeGoogleIntegration.last_error_message }}
            </p>
          </div>

          <div class="flex items-center gap-2">
            <span
              class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium"
              :class="
                statusClasses[activeGoogleIntegration?.sync_status]
                ?? statusClasses.disconnected
              "
            >
              {{
                statusLabels[activeGoogleIntegration?.sync_status]
                ?? activeGoogleIntegration?.sync_status
                ?? 'Disconnected'
              }}
            </span>
            <button
              v-if="
                anyGoogleConnected
                && activeGoogleIntegration?.sync_status === 'error'
              "
              type="button"
              class="rounded-md bg-yellow-50 px-3 py-1.5 text-sm font-semibold text-yellow-800 shadow-xs ring-1 ring-yellow-300 ring-inset hover:bg-yellow-100"
              @click="
                retrySync(
                  googlePersonalConnected ?
                    googlePersonalProvider.key
                  : googleProvider.key,
                )
              "
            >
              Retry Sync
            </button>
            <button
              v-if="anyGoogleConnected"
              type="button"
              class="rounded-md bg-white px-3 py-1.5 text-sm font-semibold text-gray-900 shadow-xs ring-1 ring-gray-300 ring-inset hover:bg-gray-50"
              @click="
                disconnect(
                  googlePersonalConnected ?
                    googlePersonalProvider.key
                  : googleProvider.key,
                )
              "
            >
              Disconnect
            </button>
          </div>
        </div>

        <!-- Not connected: authorize or credential form -->
        <template v-if="!anyGoogleConnected">
          <!-- Shared app one-click authorize -->
          <button
            v-if="!usePersonalGoogle"
            type="button"
            class="mt-3 w-full rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500"
            @click="authorize(googleProvider.key)"
          >
            Authorize with Google
          </button>

          <!-- Personal app credential form -->
          <div
            v-else
            class="mt-3 space-y-3 rounded-lg border border-gray-100 bg-gray-50 p-4"
          >
            <div class="flex items-center justify-between">
              <p class="text-xs font-medium text-gray-700">
                Enter your Google OAuth credentials
              </p>
              <button
                type="button"
                class="flex items-center gap-1 text-xs text-indigo-600 hover:text-indigo-800"
                @click="guideModal.open()"
              >
                <QuestionMarkCircleIcon class="size-3.5" />
                How to get these?
              </button>
            </div>

            <div
              class="rounded border border-gray-200 bg-white px-3 py-2 text-xs text-gray-500"
            >
              Add this URL to your Google OAuth app's
              <span class="font-medium">Authorized redirect URIs</span>:
              <p class="mt-1 font-mono break-all text-gray-800 select-all">
                {{ callbackUrl }}
              </p>
            </div>

            <input
              v-model="credentialForms[googlePersonalProvider.key].client_id"
              type="text"
              placeholder="Client ID"
              class="block w-full rounded-md border-0 py-1.5 text-sm text-gray-900 shadow-xs ring-1 ring-gray-300 ring-inset placeholder:text-gray-400 focus:ring-2 focus:ring-indigo-600 focus:ring-inset"
            />
            <p
              v-if="
                credentialForms[googlePersonalProvider.key].errors.client_id
              "
              class="text-xs text-red-500"
            >
              {{ credentialForms[googlePersonalProvider.key].errors.client_id }}
            </p>

            <input
              v-model="
                credentialForms[googlePersonalProvider.key].client_secret
              "
              type="password"
              placeholder="Client Secret"
              class="block w-full rounded-md border-0 py-1.5 text-sm text-gray-900 shadow-xs ring-1 ring-gray-300 ring-inset placeholder:text-gray-400 focus:ring-2 focus:ring-indigo-600 focus:ring-inset"
            />
            <p
              v-if="
                credentialForms[googlePersonalProvider.key].errors.client_secret
              "
              class="text-xs text-red-500"
            >
              {{
                credentialForms[googlePersonalProvider.key].errors.client_secret
              }}
            </p>

            <button
              type="button"
              class="w-full rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50"
              :disabled="
                !credentialForms[googlePersonalProvider.key].client_id
                || !credentialForms[googlePersonalProvider.key].client_secret
                || credentialForms[googlePersonalProvider.key].processing
              "
              @click="authorize(googlePersonalProvider.key)"
            >
              Authorize with Google
            </button>
          </div>

          <!-- Toggle -->
          <label
            class="mt-3 flex w-fit cursor-pointer items-center gap-2 select-none"
          >
            <button
              type="button"
              role="switch"
              :aria-checked="usePersonalGoogle"
              class="relative inline-flex h-5 w-9 shrink-0 rounded-full border-2 border-transparent transition-colors duration-200 focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2 focus:outline-none"
              :class="usePersonalGoogle ? 'bg-indigo-600' : 'bg-gray-200'"
              @click="usePersonalGoogle = !usePersonalGoogle"
            >
              <span
                class="pointer-events-none inline-block size-4 rounded-full bg-white shadow ring-0 transition-transform duration-200"
                :class="usePersonalGoogle ? 'translate-x-4' : 'translate-x-0'"
              />
            </button>
            <span class="text-xs text-gray-600"
              >Use my own Google Cloud credentials</span
            >
          </label>
        </template>

        <!-- Calendar picker (shared app) -->
        <div
          v-if="
            googleConnected
            && calendarProviderForSelection === googleProvider?.key
          "
          class="mt-3 rounded-lg border border-indigo-200 bg-indigo-50 p-4"
        >
          <p class="mb-2 text-xs font-medium text-indigo-800">
            Choose which calendar to sync sessions to:
          </p>
          <ul class="space-y-1">
            <li v-for="calendar in availableCalendars" :key="calendar.id">
              <button
                type="button"
                class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-left text-sm hover:bg-indigo-100"
                @click="selectCalendar(googleProvider.key, calendar)"
              >
                <span class="font-medium text-gray-900">{{
                  calendar.name
                }}</span>
                <span v-if="calendar.primary" class="text-xs text-indigo-600"
                  >(primary)</span
                >
              </button>
            </li>
          </ul>
        </div>

        <!-- Calendar picker (personal app) -->
        <div
          v-if="
            googlePersonalConnected
            && calendarProviderForSelection === googlePersonalProvider?.key
          "
          class="mt-3 rounded-lg border border-indigo-200 bg-indigo-50 p-4"
        >
          <p class="mb-2 text-xs font-medium text-indigo-800">
            Choose which calendar to sync sessions to:
          </p>
          <ul class="space-y-1">
            <li v-for="calendar in availableCalendars" :key="calendar.id">
              <button
                type="button"
                class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-left text-sm hover:bg-indigo-100"
                @click="selectCalendar(googlePersonalProvider.key, calendar)"
              >
                <span class="font-medium text-gray-900">{{
                  calendar.name
                }}</span>
                <span v-if="calendar.primary" class="text-xs text-indigo-600"
                  >(primary)</span
                >
              </button>
            </li>
          </ul>
        </div>
      </li>

      <!-- Apple Calendar (CalDAV) -->
      <li v-if="appleProvider" class="py-8">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-900">Apple Calendar</p>
            <p class="mt-0.5 text-xs text-gray-400">
              Connect using your Apple ID and an App-Specific Password
            </p>
            <p
              v-if="appleProvider.calendar_name"
              class="mt-0.5 text-xs text-gray-500"
            >
              Calendar: {{ appleProvider.calendar_name }}
            </p>
            <p
              v-if="appleProvider.last_synced_at"
              class="mt-0.5 text-xs text-gray-400"
            >
              Last synced:
              {{ new Date(appleProvider.last_synced_at).toLocaleString() }}
            </p>
            <p
              v-if="appleProvider.last_error_message"
              class="mt-0.5 text-xs text-red-500"
            >
              {{ appleProvider.last_error_message }}
            </p>
          </div>

          <div class="flex items-center gap-2">
            <span
              class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium"
              :class="
                statusClasses[appleProvider.sync_status]
                ?? statusClasses.disconnected
              "
            >
              {{
                statusLabels[appleProvider.sync_status]
                ?? appleProvider.sync_status
              }}
            </span>
            <button
              v-if="
                appleProvider.connected
                && !appleProvider.needs_reauth
                && appleProvider.sync_status === 'error'
              "
              type="button"
              class="rounded-md bg-yellow-50 px-3 py-1.5 text-sm font-semibold text-yellow-800 shadow-xs ring-1 ring-yellow-300 ring-inset hover:bg-yellow-100"
              @click="retrySync(appleProvider.key)"
            >
              Retry Sync
            </button>
            <button
              v-if="appleProvider.connected && !appleProvider.needs_reauth"
              type="button"
              class="rounded-md bg-white px-3 py-1.5 text-sm font-semibold text-gray-900 shadow-xs ring-1 ring-gray-300 ring-inset hover:bg-gray-50"
              @click="disconnect(appleProvider.key)"
            >
              Disconnect
            </button>
          </div>
        </div>

        <!-- Credentials form -->
        <div
          v-if="!appleProvider.connected || appleProvider.needs_reauth"
          class="mt-3 space-y-3 rounded-lg border border-gray-100 bg-gray-50 p-4"
        >
          <div class="flex items-center justify-between">
            <p class="text-xs font-medium text-gray-700">
              Enter your Apple ID and App-Specific Password
            </p>
            <button
              type="button"
              class="flex items-center gap-1 text-xs text-indigo-600 hover:text-indigo-800"
              @click="appleGuideModal.open()"
            >
              <InformationCircleIcon class="size-3.5" />
              How to get these?
            </button>
          </div>

          <input
            v-model="credentialForms[appleProvider.key].client_id"
            type="email"
            placeholder="Apple ID (email)"
            autocomplete="username"
            class="block w-full rounded-md border-0 py-1.5 text-sm text-gray-900 shadow-xs ring-1 ring-gray-300 ring-inset placeholder:text-gray-400 focus:ring-2 focus:ring-indigo-600 focus:ring-inset"
          />
          <p
            v-if="credentialForms[appleProvider.key].errors.client_id"
            class="text-xs text-red-500"
          >
            {{ credentialForms[appleProvider.key].errors.client_id }}
          </p>

          <input
            v-model="credentialForms[appleProvider.key].client_secret"
            type="password"
            placeholder="App-Specific Password (xxxx-xxxx-xxxx-xxxx)"
            autocomplete="current-password"
            class="block w-full rounded-md border-0 py-1.5 text-sm text-gray-900 shadow-xs ring-1 ring-gray-300 ring-inset placeholder:text-gray-400 focus:ring-2 focus:ring-indigo-600 focus:ring-inset"
          />
          <p
            v-if="credentialForms[appleProvider.key].errors.client_secret"
            class="text-xs text-red-500"
          >
            {{ credentialForms[appleProvider.key].errors.client_secret }}
          </p>

          <button
            type="button"
            class="w-full rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50"
            :disabled="
              !credentialForms[appleProvider.key].client_id
              || !credentialForms[appleProvider.key].client_secret
              || credentialForms[appleProvider.key].processing
            "
            @click="authorizeCalDav(appleProvider.key)"
          >
            Connect Apple Calendar
          </button>
        </div>

        <!-- Calendar picker -->
        <div
          v-if="
            appleProvider.connected
            && !appleProvider.needs_reauth
            && calendarProviderForSelection === appleProvider.key
          "
          class="mt-3 rounded-lg border border-indigo-200 bg-indigo-50 p-4"
        >
          <p class="mb-2 text-xs font-medium text-indigo-800">
            Choose which calendar to sync sessions to:
          </p>
          <ul class="space-y-1">
            <li v-for="calendar in availableCalendars" :key="calendar.id">
              <button
                type="button"
                class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-left text-sm hover:bg-indigo-100"
                @click="selectCalendar(appleProvider.key, calendar)"
              >
                <span class="font-medium text-gray-900">{{
                  calendar.name
                }}</span>
              </button>
            </li>
          </ul>
        </div>
      </li>

      <!-- Outlook and other OAuth providers -->
      <li v-for="provider in otherProviders" :key="provider.key" class="py-8">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-900">
              {{ providerLabels[provider.key] ?? provider.key }}
            </p>
            <p
              v-if="provider.uses_app_credentials"
              class="mt-0.5 text-xs text-gray-400"
            >
              Connect via the shared app — no credentials needed
            </p>
            <p
              v-if="provider.calendar_name"
              class="mt-0.5 text-xs text-gray-500"
            >
              Calendar: {{ provider.calendar_name }}
            </p>
            <p
              v-if="provider.last_synced_at"
              class="mt-0.5 text-xs text-gray-400"
            >
              Last synced:
              {{ new Date(provider.last_synced_at).toLocaleString() }}
            </p>
            <p
              v-if="provider.last_error_message"
              class="mt-0.5 text-xs text-red-500"
            >
              {{ provider.last_error_message }}
            </p>
          </div>

          <div class="flex items-center gap-2">
            <span
              class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium"
              :class="
                statusClasses[provider.sync_status]
                ?? statusClasses.disconnected
              "
            >
              {{ statusLabels[provider.sync_status] ?? provider.sync_status }}
            </span>
            <button
              v-if="
                provider.connected
                && !provider.needs_reauth
                && provider.sync_status === 'error'
              "
              type="button"
              class="rounded-md bg-yellow-50 px-3 py-1.5 text-sm font-semibold text-yellow-800 shadow-xs ring-1 ring-yellow-300 ring-inset hover:bg-yellow-100"
              @click="retrySync(provider.key)"
            >
              Retry Sync
            </button>
            <button
              v-if="provider.connected && !provider.needs_reauth"
              type="button"
              class="rounded-md bg-white px-3 py-1.5 text-sm font-semibold text-gray-900 shadow-xs ring-1 ring-gray-300 ring-inset hover:bg-gray-50"
              @click="disconnect(provider.key)"
            >
              Disconnect
            </button>
          </div>
        </div>

        <button
          v-if="
            provider.uses_app_credentials
            && (!provider.connected || provider.needs_reauth)
          "
          type="button"
          class="mt-3 w-full rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500"
          @click="authorize(provider.key)"
        >
          Authorize with {{ providerLabels[provider.key] ?? provider.key }}
        </button>

        <!-- Calendar picker -->
        <div
          v-if="
            provider.connected
            && !provider.needs_reauth
            && calendarProviderForSelection === provider.key
          "
          class="mt-3 rounded-lg border border-indigo-200 bg-indigo-50 p-4"
        >
          <p class="mb-2 text-xs font-medium text-indigo-800">
            Choose which calendar to sync sessions to:
          </p>
          <ul class="space-y-1">
            <li v-for="calendar in availableCalendars" :key="calendar.id">
              <button
                type="button"
                class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-left text-sm hover:bg-indigo-100"
                @click="selectCalendar(provider.key, calendar)"
              >
                <span class="font-medium text-gray-900">{{
                  calendar.name
                }}</span>
                <span v-if="calendar.primary" class="text-xs text-indigo-600"
                  >(primary)</span
                >
              </button>
            </li>
          </ul>
        </div>
      </li>
    </ul>

    <GuideCarouselModal
      ref="guideModal"
      title="How to get Google OAuth credentials"
      :steps="googleGuideSteps"
    />

    <GuideCarouselModal
      ref="appleGuideModal"
      title="How to connect Apple Calendar"
      :steps="appleGuideSteps"
    />

    <PopUp
      :show-status="notification.show"
      :success="notification.success"
      :message="notification.message"
    />
  </div>
</template>
