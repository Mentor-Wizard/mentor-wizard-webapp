import { useForm, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

export function useExternalCalendar(providers) {
  const page = usePage();

  const notification = ref({ show: false, success: false, message: '' });
  const availableCalendars = ref([]);
  const calendarProviderForSelection = ref('');

  function showNotification(success, message) {
    notification.value = { show: true, success, message };
    setTimeout(() => { notification.value.show = false; }, 5000);
  }

  watch(
    () => page.props.flash?.success,
    (value) => { if (value) showNotification(true, value); },
    { immediate: true },
  );

  watch(
    () => page.props.flash?.error,
    (value) => { if (value) showNotification(false, value); },
    { immediate: true },
  );

  watch(
    () => page.props.flash?.calendars,
    (value) => {
      if (value?.length) {
        availableCalendars.value = value;
      } else {
        availableCalendars.value = [];
        calendarProviderForSelection.value = '';
      }
    },
    { immediate: true },
  );

  watch(
    () => page.props.flash?.calendar_provider,
    (value) => { if (value) calendarProviderForSelection.value = value; },
    { immediate: true },
  );

  // Form for entering client_id + client_secret per provider
  const credentialForms = Object.fromEntries(
    providers.map((p) => [p.key, useForm({ client_id: '', client_secret: '' })]),
  );

  // Form for selecting a calendar
  const selectForms = Object.fromEntries(
    providers.map((p) => [p.key, useForm({ calendar_id: '', calendar_name: '' })]),
  );

  function authorize(providerKey) {
    credentialForms[providerKey].post(
      route('external-calendar.connect.redirect', { provider: providerKey }),
    );
  }

  function authorizeCalDav(providerKey) {
    credentialForms[providerKey].post(
      route('external-calendar.connect.direct', { provider: providerKey }),
    );
  }

  function selectCalendar(providerKey, calendar) {
    selectForms[providerKey].calendar_id = calendar.id;
    selectForms[providerKey].calendar_name = calendar.name;
    selectForms[providerKey].post(
      route('external-calendar.select', { provider: providerKey }),
      {
        onSuccess: () => {
          availableCalendars.value = [];
          calendarProviderForSelection.value = '';
        },
      },
    );
  }

  function disconnect(providerKey) {
    useForm({}).delete(route('external-calendar.disconnect', { provider: providerKey }));
  }

  return {
    notification,
    availableCalendars,
    calendarProviderForSelection,
    credentialForms,
    authorize,
    authorizeCalDav,
    selectCalendar,
    disconnect,
  };
}
