import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useCalendar = defineStore('calendar', () => {
  const hours = ref([
    '12AM',
    '1AM',
    '2AM',
    '3AM',
    '4AM',
    '5AM',
    '6AM',
    '7AM',
    '8AM',
    '9AM',
    '10AM',
    '11AM',
    '12PM',
    '1PM',
    '2PM',
    '3PM',
    '4PM',
    '5PM',
    '6PM',
    '7PM',
    '8PM',
    '9PM',
    '10PM',
    '11PM',
  ]);
  const weekDays = ref(['M', 'T', 'W', 'T', 'F', 'S', 'S']);

  return { hours, weekDays };
});
