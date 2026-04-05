<script setup>
import { QuestionMarkCircleIcon } from '@heroicons/vue/20/solid';
import { ref } from 'vue';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import MainPageText from '@/Components/MainPageText.vue';
import GuideCarouselModal from '@/Components/GuideCarouselModal.vue';
import PopUp from '@/Components/UI/Notifications/PopUp.vue';
import { useExternalCalendar } from '@/Composables/useExternalCalendar';

const props = defineProps({
  providers: { type: Array, required: true },
});

const {
  notification,
  availableCalendars,
  needsCalendarSelection,
  credentialForms,
  authorize,
  selectCalendar,
  disconnect,
} = useExternalCalendar(props.providers);

const googleGuideSteps = [
  { image: '/images/guides/google-calendar/step-1.png', caption: 'Go to Google Cloud Console → APIs & Services → Credentials. Click "Create Credentials" → "OAuth client ID".' },
  { image: '/images/guides/google-calendar/step-2.png', caption: 'Set application type to "Web application". Under "Authorized redirect URIs" add the callback URL shown below.' },
  { image: '/images/guides/google-calendar/step-3.png', caption: 'Copy the Client ID and Client Secret shown after creation.' },
  { image: '/images/guides/google-calendar/step-4.png', caption: 'Paste both values into the fields below and click "Authorize".' },
];

const callbackUrl = `${window.location.origin}/settings/external-calendar/callback/google`;

const guideModal = ref(null);

const providerLabels = { google: 'Google Calendar', outlook: 'Outlook Calendar', apple: 'Apple Calendar' };
const statusLabels = { active: 'Connected', pending: 'Pending', error: 'Error', disconnected: 'Disconnected' };
const statusClasses = {
  active: 'bg-green-100 text-green-800',
  pending: 'bg-yellow-100 text-yellow-800',
  error: 'bg-red-100 text-red-800',
  disconnected: 'bg-gray-100 text-gray-600',
};
</script>

<template>
  <AuthenticatedLayout>
    <template #header>
      <MainPageText title="Calendar Integrations" />
    </template>

    <div class="py-12">
      <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
        <div class="overflow-hidden bg-white shadow-xs sm:rounded-lg">
          <div class="p-6">
            <h2 class="text-base font-semibold text-gray-900">Connected Calendars</h2>
            <p class="mt-1 text-sm text-gray-500">
              Confirmed sessions will be automatically synced to your connected calendars.
            </p>

            <ul class="mt-6 divide-y divide-gray-100">
              <li
                v-for="provider in providers"
                :key="provider.key"
                class="py-5"
              >
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-sm font-medium text-gray-900">
                      {{ providerLabels[provider.key] ?? provider.key }}
                    </p>
                    <p
                      v-if="provider.calendar_name"
                      class="text-xs text-gray-500"
                    >
                      Calendar: {{ provider.calendar_name }}
                    </p>
                    <p
                      v-if="provider.last_synced_at"
                      class="text-xs text-gray-400"
                    >
                      Last synced: {{ new Date(provider.last_synced_at).toLocaleString() }}
                    </p>
                    <p
                      v-if="provider.last_error_message"
                      class="text-xs text-red-500"
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

                <!-- Credentials form -->
                <div
                  v-if="provider.key === 'google' && (!provider.connected || provider.needs_reauth)"
                  class="mt-3 rounded-lg border border-gray-200 bg-gray-50 p-4 space-y-3"
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
                    v-model="credentialForms[provider.key].client_id"
                    type="text"
                    placeholder="Client ID"
                    class="block w-full rounded-md border-0 py-1.5 text-sm text-gray-900 shadow-xs ring-1 ring-gray-300 ring-inset placeholder:text-gray-400 focus:ring-2 focus:ring-indigo-600 focus:ring-inset"
                  />
                  <p
                    v-if="credentialForms[provider.key].errors.client_id"
                    class="text-xs text-red-500"
                  >
                    {{ credentialForms[provider.key].errors.client_id }}
                  </p>

                  <input
                    v-model="credentialForms[provider.key].client_secret"
                    type="password"
                    placeholder="Client Secret"
                    class="block w-full rounded-md border-0 py-1.5 text-sm text-gray-900 shadow-xs ring-1 ring-gray-300 ring-inset placeholder:text-gray-400 focus:ring-2 focus:ring-indigo-600 focus:ring-inset"
                  />
                  <p
                    v-if="credentialForms[provider.key].errors.client_secret"
                    class="text-xs text-red-500"
                  >
                    {{ credentialForms[provider.key].errors.client_secret }}
                  </p>

                  <button
                    type="button"
                    class="w-full rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50"
                    :disabled="!credentialForms[provider.key].client_id || !credentialForms[provider.key].client_secret || credentialForms[provider.key].processing"
                    @click="authorize(provider.key)"
                  >
                    Authorize with Google
                  </button>
                </div>

                <!-- Calendar picker -->
                <div
                  v-if="provider.key === 'google' && needsCalendarSelection"
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
          </div>
        </div>
      </div>
    </div>

    <GuideCarouselModal
      ref="guideModal"
      title="How to get Google OAuth credentials"
      :steps="googleGuideSteps"
    />

    <PopUp
      :show-status="notification.show"
      :success="notification.success"
      :message="notification.message"
    />
  </AuthenticatedLayout>
</template>
