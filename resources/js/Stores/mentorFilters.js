import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useMentorFilters = defineStore('mentorFilters', () => {
  const selectedExpertise = ref([]);
  const selectedExperience = ref([]);
  const selectedRatings = ref([]);
  const selectedAvailability = ref([]);
  const priceMin = ref(1);
  // TODO: Replace static max with dynamic max from backend
  const priceMax = ref(200);
  const selectedCurrency = ref('USD');

  const experienceOptions = [
    { value: 'entry', label: 'Entry Level (1–3 years)' },
    { value: 'mid', label: 'Mid Level (4–7 years)' },
    { value: 'senior', label: 'Senior Level (8–12 years)' },
    { value: 'expert', label: 'Expert (12+ years)' },
  ];

  const ratings = [
    { value: 5, label: '5.0' },
    { value: 4, label: '4.0 & up' },
    { value: 3, label: '3.0 & up' },
  ];

  const availabilityOptions = [
    { value: 'today', label: 'Available today' },
    { value: 'booked', label: 'Booked' },
    { value: 'tomorrow', label: 'Available tomorrow' },
  ];

  const expertiseOptions = ref([]);
  const currencyOptions = ref([]);

  function setDynamicOptions({ expertise, currencies }) {
    expertiseOptions.value = expertise || [];
    currencyOptions.value = currencies || [];
  }

  function clearFilters() {
    selectedExpertise.value = [];
    selectedExperience.value = [];
    selectedRatings.value = [];
    selectedAvailability.value = [];
    priceMin.value = 0;
    priceMax.value = 200;
    selectedCurrency.value = 'USD';
  }

  function initializeFromQuery(query) {
    if (!query) return;

    if (query.expertise)
      selectedExpertise.value =
        Array.isArray(query.expertise) ? query.expertise : [query.expertise];
    if (query.experience)
      selectedExperience.value =
        Array.isArray(query.experience) ? query.experience : [query.experience];
    if (query.ratings)
      selectedRatings.value =
        Array.isArray(query.ratings) ?
          query.ratings.map(Number)
        : [Number(query.ratings)];
    if (query.availability)
      selectedAvailability.value =
        Array.isArray(query.availability) ?
          query.availability
        : [query.availability];
    if (query.priceMin) priceMin.value = Number(query.priceMin);
    if (query.priceMax) priceMax.value = Number(query.priceMax);
    if (query.currency) selectedCurrency.value = query.currency;
  }

  return {
    selectedExpertise,
    selectedExperience,
    selectedRatings,
    selectedAvailability,
    priceMin,
    priceMax,
    selectedCurrency,
    experienceOptions,
    ratings,
    availabilityOptions,
    expertiseOptions,
    currencyOptions,
    setDynamicOptions,
    clearFilters,
    initializeFromQuery,
  };
});
