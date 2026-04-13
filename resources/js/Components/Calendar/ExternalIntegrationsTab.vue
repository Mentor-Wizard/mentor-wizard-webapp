<script setup>
import { ArrowPathIcon, CheckIcon, ExclamationCircleIcon, InformationCircleIcon, PlusIcon } from '@heroicons/vue/24/outline';
import { router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
  calendarEventId: {
    type: [Number, String],
    required: true,
  },
  integrations: {
    type: Array,
    default: () => [],
  },
});

const pendingId = ref(null);
const acknowledgingId = ref(null);

const typeConfig = {
  success: {
    icon: CheckIcon,
    class: 'text-green-600',
    bg: 'bg-green-50',
  },
  error: {
    icon: ExclamationCircleIcon,
    class: 'text-red-600',
    bg: 'bg-red-50',
  },
  error_status_viewed: {
    icon: ExclamationCircleIcon,
    class: 'text-gray-400',
    bg: 'bg-gray-50',
  },
  info: {
    icon: InformationCircleIcon,
    class: 'text-blue-500',
    bg: 'bg-blue-50',
  },
};

const syncStatusConfig = {
  synced: { label: 'Synced', class: 'bg-green-100 text-green-700' },
  error: { label: 'Error', class: 'bg-red-100 text-red-700' },
};

const syncKey = (integration) =>
  integration.integration_id;

const sync = (integration) => {
  pendingId.value = syncKey(integration);
  useForm({}).post(
    route('external-calendar.sync-integration', {
      calendarEvent: props.calendarEventId,
      integration: integration.integration_id,
    }),
    {
      preserveScroll: true,
      onSuccess: () => {
        router.reload({ only: ['externalIntegrations'] });
      },
      onFinish: () => {
        pendingId.value = null;
      },
    },
  );
};

const rerun = (integration) => {
  pendingId.value = syncKey(integration);
  useForm({}).post(
    route('external-calendar.rerun', {
      calendarEvent: props.calendarEventId,
      externalCalendarEvent: integration.external_event.id,
    }),
    {
      preserveScroll: true,
      onSuccess: () => {
        router.reload({ only: ['externalIntegrations'] });
      },
      onFinish: () => {
        pendingId.value = null;
      },
    },
  );
};

const acknowledge = (logId) => {
  acknowledgingId.value = logId;
  useForm({}).patch(
    route('external-calendar.log.acknowledge', { log: logId }),
    {
      preserveScroll: true,
      onSuccess: () => {
        router.reload({ only: ['externalIntegrations'] });
      },
      onFinish: () => {
        acknowledgingId.value = null;
      },
    },
  );
};

const expandedRows = ref(new Set());

const toggleLogs = (integrationId) => {
  if (expandedRows.value.has(integrationId)) {
    expandedRows.value.delete(integrationId);
  } else {
    expandedRows.value.add(integrationId);
  }
};

const formatDate = (iso) => {
  if (!iso) { return '—'; }
  return new Date(iso).toLocaleString();
};
</script>

<template>
  <div>
    <div
      v-if="integrations.length === 0"
      class="py-8 text-center text-sm text-gray-500"
    >
      No active calendar integrations found for participants of this event.
    </div>

    <div
      v-else
      class="divide-y divide-gray-100"
    >
      <div
        v-for="integration in integrations"
        :key="integration.integration_id"
        class="py-4"
      >
        <!-- Row header -->
        <div class="flex items-center justify-between gap-3">
          <div class="flex min-w-0 flex-1 items-center gap-3">
            <div class="min-w-0">
              <p class="truncate text-sm font-medium text-gray-900">
                {{ integration.provider_label }}
              </p>
              <p class="truncate text-xs text-gray-500">
                {{ integration.user_name }}
              </p>
            </div>

            <!-- Sync status badge -->
            <span
              v-if="integration.external_event?.sync_status"
              :class="[
                'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                syncStatusConfig[integration.external_event.sync_status]?.class ?? 'bg-gray-100 text-gray-600',
              ]"
            >
              {{ syncStatusConfig[integration.external_event.sync_status]?.label ?? integration.external_event.sync_status }}
            </span>

            <span
              v-else-if="integration.external_event"
              class="inline-flex items-center rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-medium text-yellow-700"
            >
              Pending
            </span>

            <span
              v-else
              class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500"
            >
              Not synced
            </span>
          </div>

          <div class="flex shrink-0 items-center gap-2">
            <!-- Logs toggle (only when external event exists and has logs) -->
            <button
              v-if="integration.external_event?.logs?.length > 0"
              type="button"
              class="text-xs text-indigo-600 hover:text-indigo-800"
              @click="toggleLogs(integration.integration_id)"
            >
              {{ expandedRows.has(integration.integration_id) ? 'Hide logs' : `Logs (${integration.external_event.logs.length})` }}
            </button>

            <!-- Sync (first time) -->
            <button
              v-if="!integration.external_event"
              type="button"
              :disabled="pendingId === syncKey(integration)"
              class="inline-flex items-center gap-1 rounded-md bg-white px-2.5 py-1.5 text-xs font-medium text-gray-700 shadow-xs ring-1 ring-gray-300 ring-inset hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
              @click="sync(integration)"
            >
              <PlusIcon
                class="h-3.5 w-3.5"
                :class="{ 'animate-spin': pendingId === syncKey(integration) }"
              />
              Sync
            </button>

            <!-- Re-sync (external event exists) -->
            <button
              v-else
              type="button"
              :disabled="pendingId === syncKey(integration)"
              class="inline-flex items-center gap-1 rounded-md bg-white px-2.5 py-1.5 text-xs font-medium text-gray-700 shadow-xs ring-1 ring-gray-300 ring-inset hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
              @click="rerun(integration)"
            >
              <ArrowPathIcon
                class="h-3.5 w-3.5"
                :class="{ 'animate-spin': pendingId === syncKey(integration) }"
              />
              Re-sync
            </button>
          </div>
        </div>

        <!-- Logs -->
        <div
          v-if="expandedRows.has(integration.integration_id) && integration.external_event?.logs?.length > 0"
          class="mt-3 space-y-1.5 rounded-md bg-gray-50 p-3"
        >
          <div
            v-for="log in integration.external_event.logs"
            :key="log.id"
            :class="['flex items-start gap-2 rounded p-2', typeConfig[log.type]?.bg ?? 'bg-white']"
          >
            <component
              :is="typeConfig[log.type]?.icon ?? InformationCircleIcon"
              :class="['mt-0.5 h-4 w-4 shrink-0', typeConfig[log.type]?.class ?? 'text-gray-400']"
            />

            <div class="min-w-0 flex-1">
              <p class="break-words text-xs text-gray-700">
                {{ log.message }}
              </p>
              <p class="mt-0.5 text-xs text-gray-400">
                {{ formatDate(log.created_at) }}
              </p>
            </div>

            <button
              v-if="log.type === 'error'"
              type="button"
              :disabled="acknowledgingId === log.id"
              class="shrink-0 text-xs text-gray-500 underline hover:text-gray-700 disabled:opacity-50"
              @click="acknowledge(log.id)"
            >
              Acknowledge
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
