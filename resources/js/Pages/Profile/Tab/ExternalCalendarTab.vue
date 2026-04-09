<script setup>
import { usePage } from '@inertiajs/vue3';
import { QuestionMarkCircleIcon } from '@heroicons/vue/20/solid';
import { computed, ref } from 'vue';

import GuideCarouselModal from '@/Components/GuideCarouselModal.vue';
import PopUp from '@/Components/UI/Notifications/PopUp.vue';
import { useExternalCalendar } from '@/Composables/useExternalCalendar';
import { InformationCircleIcon } from '@heroicons/vue/20/solid';

const page = usePage();
const providers = page.props.calendarProviders ?? [];

const {
  notification,
  availableCalendars,
  needsCalendarSelection,
  credentialForms,
  authorize,
  authorizeCalDav,
  selectCalendar,
  disconnect,
} = useExternalCalendar(providers);

const googleProvider = computed(() => page.props.calendarProviders?.find((p) => p.key === 'google'));
const googlePersonalProvider = computed(() => page.props.calendarProviders?.find((p) => p.key === 'google_personal_app'));
const appleProvider = computed(() => page.props.calendarProviders?.find((p) => p.key === 'apple'));
const otherProviders = computed(() => page.props.calendarProviders?.filter((p) => !['google', 'google_personal_app', 'apple'].includes(p.key)) ?? []);

const googleConnected = computed(() => googleProvider.value?.connected && !googleProvider.value?.needs_reauth);
const googlePersonalConnected = computed(() => googlePersonalProvider.value?.connected && !googlePersonalProvider.value?.needs_reauth);

const googleGuideSteps = [
  { image: '/images/guides/google-calendar/step-1.png', caption: 'Go to Google Cloud Console → APIs & Services → Credentials. Click "Create Credentials" → "OAuth client ID".' },
  { image: '/images/guides/google-calendar/step-2.png', caption: 'Set application type to "Web application". Under "Authorized redirect URIs" add the callback URL shown below.' },
  { image: '/images/guides/google-calendar/step-3.png', caption: 'Copy the Client ID and Client Secret shown after creation.' },
  { image: '/images/guides/google-calendar/step-4.png', caption: 'Paste both values into the fields below and click "Authorize".' },
];

const callbackUrl = `${window.location.origin}/settings/external-calendar/callback/google`;

const guideModal = ref(null);
const appleGuideModal = ref(null);

const appleGuideSteps = [
  { image: '/images/guides/apple-calendar/step-1.png', caption: 'Go to appleid.apple.com and sign in. Navigate to "Sign-In and Security" → "App-Specific Passwords".' },
  { image: '/images/guides/apple-calendar/step-2.png', caption: 'Click "Generate an app-specific password". Enter a label like "MentorWizard" and click Create.' },
  { image: '/images/guides/apple-calendar/step-3.png', caption: 'Copy the generated password (format: xxxx-xxxx-xxxx-xxxx). You won\'t be able to see it again.' },
  { image: '/images/guides/apple-calendar/step-4.png', caption: 'Enter your Apple ID (email) and the app-specific password below, then click Connect.' },
];

const statusLabels = { active: 'Connected', pending: 'Pending', error: 'Error', disconnected: 'Disconnected' };
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
      Confirmed sessions will be automatically synced to your connected calendars.
    </p>

    <ul class="mt-6 divide-y divide-gray-100">

      <!-- Google group (both providers in one framed block) -->
      <li class="py-5">
        <div class="rounded-lg border border-gray-200 divide-y divide-gray-100">

          <!-- Google Calendar (multi-tenant) -->
          <div
            v-if="googleProvider"
            class="p-4"
          >
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-gray-900">Google Calendar</p>
                <p class="text-xs text-gray-400 mt-0.5">Connect via the shared app — no credentials needed</p>
                <p
                  v-if="googleProvider.calendar_name"
                  class="text-xs text-gray-500 mt-0.5"
                >
                  Calendar: {{ googleProvider.calendar_name }}
                </p>
                <p
                  v-if="googleProvider.last_synced_at"
                  class="text-xs text-gray-400 mt-0.5"
                >
                  Last synced: {{ new Date(googleProvider.last_synced_at).toLocaleString() }}
                </p>
                <p
                  v-if="googleProvider.last_error_message"
                  class="text-xs text-red-500 mt-0.5"
                >
                  {{ googleProvider.last_error_message }}
                </p>
              </div>

              <div class="flex items-center gap-2">
                <span
                  class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium"
                  :class="statusClasses[googleProvider.sync_status] ?? statusClasses.disconnected"
                >
                  {{ statusLabels[googleProvider.sync_status] ?? googleProvider.sync_status }}
                </span>
                <button
                  v-if="googleConnected"
                  type="button"
                  class="rounded-md bg-white px-3 py-1.5 text-sm font-semibold text-gray-900 shadow-xs ring-1 ring-gray-300 ring-inset hover:bg-gray-50"
                  @click="disconnect(googleProvider.key)"
                >
                  Disconnect
                </button>
              </div>
            </div>

            <!-- Blocked: personal app is active -->
            <p
              v-if="!googleConnected && googlePersonalConnected"
              class="mt-3 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-700"
            >
              Disconnect <span class="font-medium">Google Calendar (Personal App)</span> first to use the shared Google connection.
            </p>

            <!-- Authorize button -->
            <button
              v-else-if="!googleConnected"
              type="button"
              class="mt-3 w-full rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500"
              @click="authorize(googleProvider.key)"
            >
              Authorize with Google
            </button>

            <!-- Calendar picker -->
            <div
              v-if="googleConnected && needsCalendarSelection"
              class="mt-3 rounded-lg border border-indigo-200 bg-indigo-50 p-4"
            >
              <p class="text-xs font-medium text-indigo-800 mb-2">
                Choose which calendar to sync sessions to:
              </p>
              <ul class="space-y-1">
                <li
                  v-for="calendar in availableCalendars"
                  :key="calendar.id"
                >
                  <button
                    type="button"
                    class="w-full rounded-md px-3 py-2 text-left text-sm hover:bg-indigo-100 flex items-center gap-2"
                    @click="selectCalendar(googleProvider.key, calendar)"
                  >
                    <span class="font-medium text-gray-900">{{ calendar.name }}</span>
                    <span
                      v-if="calendar.primary"
                      class="text-xs text-indigo-600"
                    >(primary)</span>
                  </button>
                </li>
              </ul>
            </div>
          </div>

          <!-- Google Calendar (Personal App) -->
          <div
            v-if="googlePersonalProvider"
            class="p-4"
          >
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-gray-900">Google Calendar (Personal App)</p>
                <p class="text-xs text-gray-400 mt-0.5">Use your own Google Cloud OAuth app credentials</p>
                <p
                  v-if="googlePersonalProvider.calendar_name"
                  class="text-xs text-gray-500 mt-0.5"
                >
                  Calendar: {{ googlePersonalProvider.calendar_name }}
                </p>
                <p
                  v-if="googlePersonalProvider.last_synced_at"
                  class="text-xs text-gray-400 mt-0.5"
                >
                  Last synced: {{ new Date(googlePersonalProvider.last_synced_at).toLocaleString() }}
                </p>
                <p
                  v-if="googlePersonalProvider.last_error_message"
                  class="text-xs text-red-500 mt-0.5"
                >
                  {{ googlePersonalProvider.last_error_message }}
                </p>
              </div>

              <div class="flex items-center gap-2">
                <span
                  class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium"
                  :class="statusClasses[googlePersonalProvider.sync_status] ?? statusClasses.disconnected"
                >
                  {{ statusLabels[googlePersonalProvider.sync_status] ?? googlePersonalProvider.sync_status }}
                </span>
                <button
                  v-if="googlePersonalConnected"
                  type="button"
                  class="rounded-md bg-white px-3 py-1.5 text-sm font-semibold text-gray-900 shadow-xs ring-1 ring-gray-300 ring-inset hover:bg-gray-50"
                  @click="disconnect(googlePersonalProvider.key)"
                >
                  Disconnect
                </button>
              </div>
            </div>

            <!-- Blocked: shared app is active -->
            <p
              v-if="!googlePersonalConnected && googleConnected"
              class="mt-3 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-700"
            >
              Disconnect <span class="font-medium">Google Calendar</span> first to use your own Personal App credentials.
            </p>

            <!-- Credentials form -->
            <div
              v-else-if="!googlePersonalConnected"
              class="mt-3 rounded-lg border border-gray-100 bg-gray-50 p-4 space-y-3"
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

              <div class="rounded border border-gray-200 bg-white px-3 py-2 text-xs text-gray-500">
                Add this URL to your Google OAuth app's <span class="font-medium">Authorized redirect URIs</span>:
                <p class="mt-1 font-mono text-gray-800 break-all select-all">{{ callbackUrl }}</p>
              </div>

              <input
                v-model="credentialForms[googlePersonalProvider.key].client_id"
                type="text"
                placeholder="Client ID"
                class="block w-full rounded-md border-0 py-1.5 text-sm text-gray-900 shadow-xs ring-1 ring-gray-300 ring-inset placeholder:text-gray-400 focus:ring-2 focus:ring-indigo-600 focus:ring-inset"
              />
              <p
                v-if="credentialForms[googlePersonalProvider.key].errors.client_id"
                class="text-xs text-red-500"
              >
                {{ credentialForms[googlePersonalProvider.key].errors.client_id }}
              </p>

              <input
                v-model="credentialForms[googlePersonalProvider.key].client_secret"
                type="password"
                placeholder="Client Secret"
                class="block w-full rounded-md border-0 py-1.5 text-sm text-gray-900 shadow-xs ring-1 ring-gray-300 ring-inset placeholder:text-gray-400 focus:ring-2 focus:ring-indigo-600 focus:ring-inset"
              />
              <p
                v-if="credentialForms[googlePersonalProvider.key].errors.client_secret"
                class="text-xs text-red-500"
              >
                {{ credentialForms[googlePersonalProvider.key].errors.client_secret }}
              </p>

              <button
                type="button"
                class="w-full rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50"
                :disabled="!credentialForms[googlePersonalProvider.key].client_id || !credentialForms[googlePersonalProvider.key].client_secret || credentialForms[googlePersonalProvider.key].processing"
                @click="authorize(googlePersonalProvider.key)"
              >
                Authorize with Google
              </button>
            </div>

            <!-- Calendar picker -->
            <div
              v-if="googlePersonalConnected && needsCalendarSelection"
              class="mt-3 rounded-lg border border-indigo-200 bg-indigo-50 p-4"
            >
              <p class="text-xs font-medium text-indigo-800 mb-2">
                Choose which calendar to sync sessions to:
              </p>
              <ul class="space-y-1">
                <li
                  v-for="calendar in availableCalendars"
                  :key="calendar.id"
                >
                  <button
                    type="button"
                    class="w-full rounded-md px-3 py-2 text-left text-sm hover:bg-indigo-100 flex items-center gap-2"
                    @click="selectCalendar(googlePersonalProvider.key, calendar)"
                  >
                    <span class="font-medium text-gray-900">{{ calendar.name }}</span>
                    <span
                      v-if="calendar.primary"
                      class="text-xs text-indigo-600"
                    >(primary)</span>
                  </button>
                </li>
              </ul>
            </div>
          </div>

        </div>
      </li>

      <!-- Apple Calendar (CalDAV — dedicated section) -->
      <li
        v-if="appleProvider"
        class="py-5"
      >
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-900">Apple Calendar</p>
            <p class="text-xs text-gray-400 mt-0.5">Connect using your Apple ID and an App-Specific Password</p>
            <p
              v-if="appleProvider.calendar_name"
              class="text-xs text-gray-500 mt-0.5"
            >
              Calendar: {{ appleProvider.calendar_name }}
            </p>
            <p
              v-if="appleProvider.last_synced_at"
              class="text-xs text-gray-400 mt-0.5"
            >
              Last synced: {{ new Date(appleProvider.last_synced_at).toLocaleString() }}
            </p>
            <p
              v-if="appleProvider.last_error_message"
              class="text-xs text-red-500 mt-0.5"
            >
              {{ appleProvider.last_error_message }}
            </p>
          </div>

          <div class="flex items-center gap-2">
            <span
              class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium"
              :class="statusClasses[appleProvider.sync_status] ?? statusClasses.disconnected"
            >
              {{ statusLabels[appleProvider.sync_status] ?? appleProvider.sync_status }}
            </span>
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
          class="mt-3 rounded-lg border border-gray-100 bg-gray-50 p-4 space-y-3"
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
            :disabled="!credentialForms[appleProvider.key].client_id || !credentialForms[appleProvider.key].client_secret || credentialForms[appleProvider.key].processing"
            @click="authorizeCalDav(appleProvider.key)"
          >
            Connect Apple Calendar
          </button>
        </div>

        <!-- Calendar picker after connection -->
        <div
          v-if="appleProvider.connected && !appleProvider.needs_reauth && needsCalendarSelection"
          class="mt-3 rounded-lg border border-indigo-200 bg-indigo-50 p-4"
        >
          <p class="text-xs font-medium text-indigo-800 mb-2">
            Choose which calendar to sync sessions to:
          </p>
          <ul class="space-y-1">
            <li
              v-for="calendar in availableCalendars"
              :key="calendar.id"
            >
              <button
                type="button"
                class="w-full rounded-md px-3 py-2 text-left text-sm hover:bg-indigo-100 flex items-center gap-2"
                @click="selectCalendar(appleProvider.key, calendar)"
              >
                <span class="font-medium text-gray-900">{{ calendar.name }}</span>
              </button>
            </li>
          </ul>
        </div>
      </li>

      <!-- Outlook and any other providers -->
      <li
        v-for="provider in otherProviders"
        :key="provider.key"
        class="py-5"
      >
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-900">
              {{ providerLabels[provider.key] ?? provider.key }}
            </p>
            <p
              v-if="provider.uses_app_credentials"
              class="text-xs text-gray-400 mt-0.5"
            >
              Connect via the shared app — no credentials needed
            </p>
            <p
              v-if="provider.calendar_name"
              class="text-xs text-gray-500 mt-0.5"
            >
              Calendar: {{ provider.calendar_name }}
            </p>
            <p
              v-if="provider.last_synced_at"
              class="text-xs text-gray-400 mt-0.5"
            >
              Last synced: {{ new Date(provider.last_synced_at).toLocaleString() }}
            </p>
            <p
              v-if="provider.last_error_message"
              class="text-xs text-red-500 mt-0.5"
            >
              {{ provider.last_error_message }}
            </p>
          </div>

          <div class="flex items-center gap-2">
            <span
              class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium"
              :class="statusClasses[provider.sync_status] ?? statusClasses.disconnected"
            >
              {{ statusLabels[provider.sync_status] ?? provider.sync_status }}
            </span>
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

        <!-- OAuth redirect providers (e.g. Outlook): simple authorize button -->
        <button
          v-if="provider.uses_app_credentials && (!provider.connected || provider.needs_reauth)"
          type="button"
          class="mt-3 w-full rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500"
          @click="authorize(provider.key)"
        >
          Authorize with {{ providerLabels[provider.key] ?? provider.key }}
        </button>

        <!-- Calendar picker -->
        <div
          v-if="provider.connected && !provider.needs_reauth && needsCalendarSelection"
          class="mt-3 rounded-lg border border-indigo-200 bg-indigo-50 p-4"
        >
          <p class="text-xs font-medium text-indigo-800 mb-2">
            Choose which calendar to sync sessions to:
          </p>
          <ul class="space-y-1">
            <li
              v-for="calendar in availableCalendars"
              :key="calendar.id"
            >
              <button
                type="button"
                class="w-full rounded-md px-3 py-2 text-left text-sm hover:bg-indigo-100 flex items-center gap-2"
                @click="selectCalendar(provider.key, calendar)"
              >
                <span class="font-medium text-gray-900">{{ calendar.name }}</span>
                <span
                  v-if="calendar.primary"
                  class="text-xs text-indigo-600"
                >(primary)</span>
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
