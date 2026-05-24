import { UserGroupIcon, UserIcon } from '@heroicons/vue/24/outline';
import { computed, ref } from 'vue';

/**
 * Adjusts the current date based on the specified direction and view type
 * @param {Date} currentDate - The current date
 * @param {string} currentTab - The current view tab ('Day view', 'Week view', 'Month view')
 * @param {string} direction - Direction to adjust ('previous', 'next', 'exact date')
 * @param {string|null} exactDate - Exact date string when direction is 'exact date'
 * @returns {Object} - Object containing the new date and potentially updated tab
 */
export const adjustDate = (
  currentDate,
  currentTab,
  direction,
  exactDate = null,
) => {
  const newDate = new Date(currentDate);
  let newTab = currentTab;

  console.log(currentTab);

  switch (currentTab) {
    case 'Day view':
      if (direction === 'previous') {
        newDate.setDate(currentDate.getDate() - 1);
      } else if (direction === 'next') {
        newDate.setDate(currentDate.getDate() + 1);
      } else if (direction === 'exact date') {
        const targetDate = new Date(exactDate);
        newDate.setDate(targetDate.getDate());
        newDate.setMonth(targetDate.getMonth());
        newDate.setFullYear(targetDate.getFullYear());
      }
      break;
    case 'Week view':
      if (direction === 'previous') {
        newDate.setDate(currentDate.getDate() - 7);
      } else if (direction === 'next') {
        newDate.setDate(currentDate.getDate() + 7);
      } else if (direction === 'exact date') {
        const targetDate = new Date(exactDate);
        newDate.setDate(targetDate.getDate());
        newDate.setMonth(targetDate.getMonth());
        newDate.setFullYear(targetDate.getFullYear());
        newTab = 'Day view';
      }
      break;
    case 'Month view':
      if (direction === 'previous') {
        newDate.setMonth(currentDate.getMonth() - 1);
      } else if (direction === 'next') {
        newDate.setMonth(currentDate.getMonth() + 1);
      } else if (direction === 'exact date') {
        const targetDate = new Date(exactDate);
        newDate.setDate(targetDate.getDate());
        newDate.setMonth(targetDate.getMonth());
        newDate.setFullYear(targetDate.getFullYear());
        newTab = 'Day view';
      }
      break;
    default:
      console.error('Invalid tab specified:', currentTab);
      return { date: currentDate, tab: currentTab };
  }

  return { date: newDate, tab: newTab };
};

/**
 * Formats the week range label for a given date, e.g. "02 серп - 08 серп"
 * Works in plain JavaScript; types are expressed via JSDoc.
 * @param {Date|string|number} currentDate
 * @param {string} [locale="uk-UA"]
 * @returns {string}
 */

export const formatWeekRange = (locale, currentDate) => {
  const currentDateObject = new Date(currentDate);
  const day = currentDateObject.getDay() === 0 ? 7 : currentDateObject.getDay();
  const start = new Date(currentDateObject);
  start.setHours(0, 0, 0, 0);
  start.setDate(currentDateObject.getDate() - (day - 1));

  const end = new Date(start);
  end.setDate(start.getDate() + 6);
  const localeValue =
    typeof locale === 'string' ? locale : String(locale || 'uk-UA');
  const monthFmt = new Intl.DateTimeFormat(localeValue, { month: 'short' });
  const pad2 = (number) => String(number).padStart(2, '0');
  const cleanMonth = (month) => month.replace(/\.$/, '');
  const startLabel = `${pad2(start.getDate())} ${cleanMonth(monthFmt.format(start))}`;
  const endLabel = `${pad2(end.getDate())} ${cleanMonth(monthFmt.format(end))}`;
  return `${startLabel} - ${endLabel}`;
};

export const validateForm = (form, errors) => {
  errors.value = {
    fromTime: null,
    fromDate: null,
    title: null,
    toDate: null,
    toTime: null,
    webLink: null,
    colour: null,
    description: null,
  };

  if (!form.title.trim()) {
    errors.value.title = 'Title is required';
  }

  if (!form.fromDate) {
    errors.value.fromDate = 'Start date is required';
  }

  if (!form.toDate) {
    errors.value.toDate = 'End date is required';
  }

  if (!form.fromTime) {
    errors.value.fromTime = 'Start time is required';
  }

  if (!form.toTime) {
    errors.value.toTime = 'End time is required';
  }

  if (form.description.length > 2000) {
    errors.value.description = 'Description is more than 2000 characters';
  }

  if (form.webLink === '') {
    form.webLink = null;
  }
  if (!isValidUrl(form.webLink)) {
    errors.value.webLink = 'Weblink format is wrong';
  }

  if (form.fromDate && form.toDate) {
    const fromDateTime = new Date(`${form.fromDate}T${form.fromTime}`);
    const toDateTime = new Date(`${form.toDate}T${form.toTime}`);
    const currentTime = new Date();
    if (fromDateTime >= toDateTime) {
      errors.value.toDate = 'End date/time must be after start date/time';
    }
    if (currentTime > fromDateTime) {
      errors.value.fromDate = 'Start date/time must be in the future';
    }
  }

  let errorStatus = false;

  Object.keys(errors.value).forEach((key) => {
    if (errors.value[key]) {
      errorStatus = true;
    }
  });
  return !errorStatus;
};

const isValidUrl = (urlString) => {
  try {
    if (urlString == null) {
      return true;
    }
    new URL(urlString);
    return true;
  } catch (error) {
    console.log(error);
    return false;
  }
};

export const capitalize = (symbol) =>
  symbol ? symbol.charAt(0).toUpperCase() + symbol.slice(1) : symbol;

export function selectedEventTypeHelper(form) {
  return computed(() => eventTypes.find((type) => type.value === form.type));
}

export function isFormValid(form) {
  return computed(() =>
    Boolean(
      form.title?.trim()
      && form.fromDate
      && form.toDate
      && form.fromTime
      && form.colour
      && form.toTime
      && form.session_type,
    ),
  );
}

export const sessionTypes = [
  { value: 'Video Session', label: 'Video Session' },
  { value: 'Voice Session', label: 'Voice Session' },
  { value: 'Code Review', label: 'Code Review' },
];

// CalendarEvent types
export const eventTypes = [
  { value: 'Individual', label: 'Individual', icon: UserIcon },
  { value: 'Group', label: 'Group', icon: UserGroupIcon },
];

export const errors = ref({
  title: null,
  fromDate: null,
  fromTime: null,
  toDate: null,
  toTime: null,
  type: null,
  webLink: null,
  colour: null,
  description: null,
});

export const timeZone = ref(
  Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC',
);

export const shownMonth = ref(
  new Date().toISOString().split('T')[0].slice(0, 7),
);
export const getTitleMonth = (filterDate) => {
  return new Date(filterDate).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
  });
};
export const getFormattedMonth = (dateString) => {
  const date = new Date(dateString);
  return date.toISOString().split('T')[0].slice(0, 7);
};
