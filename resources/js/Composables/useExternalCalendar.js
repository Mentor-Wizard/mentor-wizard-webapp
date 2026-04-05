import { useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

export function useExternalCalendar(providers) {
  const page = usePage();

  const notification = ref({ show: false, success: false, message: '' });
  const availableCalendars = ref([]);

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
    (value) => { if (value?.length) availableCalendars.value = value; },
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
    // POST saves credentials and backend returns redirect to Google OAuth
    credentialForms[providerKey].post(
      route('external-calendar.connect.redirect', { provider: providerKey }),
    );
  }

  function selectCalendar(providerKey, calendar) {
    selectForms[providerKey].calendar_id = calendar.id;
    selectForms[providerKey].calendar_name = calendar.name;
    selectForms[providerKey].post(
      route('external-calendar.select', { provider: providerKey }),
      { onSuccess: () => { availableCalendars.value = []; } },
    );
  }

  function disconnect(providerKey) {
    useForm({}).delete(route('external-calendar.disconnect', { provider: providerKey }));
  }

  const needsCalendarSelection = computed(() => availableCalendars.value.length > 0);

  return {
    notification,
    availableCalendars,
    needsCalendarSelection,
    credentialForms,
    authorize,
    selectCalendar,
    disconnect,
  };
}
