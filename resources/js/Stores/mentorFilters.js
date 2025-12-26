import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

export const useMentorFilters = defineStore('mentorFilters', () => {
  // ============ State ============
  const selectedStacks = ref([]);
  const selectedLanguages = ref([]);
  const selectedExperience = ref([]);
  const minRate = ref(null);
  const maxRate = ref(null);
  const selectedCurrency = ref('USD');
  const minRating = ref(null);

  // ============ Options (populated from backend) ============
  const stackOptions = ref([]);
  const languageOptions = ref([]);
  const currencyOptions = ref([]);

  // Static options (don't change)
  const experienceOptions = [
    { value: 'entry', label: 'Entry Level (1–3 years)' },
    { value: 'mid', label: 'Mid Level (4–7 years)' },
    { value: 'senior', label: 'Senior Level (8–12 years)' },
    { value: 'expert', label: 'Expert (12+ years)' },
  ];

  const ratingOptions = [
    { value: 5, label: '5.0' },
    { value: 4, label: '4.0 & up' },
    { value: 3, label: '3.0 & up' },
  ];

  // ============ Computed ============
  const activeFilterCount = computed(() => {
    let count = 0;
    if (selectedStacks.value.length > 0) count++;
    if (selectedLanguages.value.length > 0) count++;
    if (selectedExperience.value.length > 0) count++;
    if (minRate.value !== null || maxRate.value !== null) count++;
    if (minRating.value !== null) count++;
    return count;
  });

  // ============ Methods ============

  /**
   * Build query params in Spatie Query Builder format
   * Example: { 'filter[stacks]': 'Laravel,React', 'filter[experience]': 'senior' }
   */
  function buildQueryParams() {
    const params = {};

    if (selectedStacks.value.length > 0) {
      params['filter[stacks]'] = selectedStacks.value.join(',');
    }

    if (selectedLanguages.value.length > 0) {
      params['filter[languages]'] = selectedLanguages.value.join(',');
    }

    if (selectedExperience.value.length > 0) {
      params['filter[experience]'] = selectedExperience.value.join(',');
    }

    if (minRate.value !== null && minRate.value > 0) {
      params['filter[rate][min]'] = minRate.value;
    }

    if (maxRate.value !== null && maxRate.value < 200) {
      params['filter[rate][max]'] = maxRate.value;
    }

    if (minRating.value !== null) {
      params['filter[rating]'] = minRating.value;
    }

    return params;
  }

  /**
   * Parse query params from URL
   * Example: parseQueryParams({ 'filter[stacks]': 'Laravel,React' })
   */
  function parseQueryParams(query) {
    if (!query) return;

    // Parse filter[stacks]
    if (query['filter[stacks]']) {
      selectedStacks.value = query['filter[stacks]'].split(',');
    }

    // Parse filter[languages]
    if (query['filter[languages]']) {
      selectedLanguages.value = query['filter[languages]'].split(',');
    }

    // Parse filter[experience]
    if (query['filter[experience]']) {
      selectedExperience.value = query['filter[experience]'].split(',');
    }

    // Parse filter[rate][min] and filter[rate][max]
    if (query['filter[rate][min]']) {
      minRate.value = parseFloat(query['filter[rate][min]']);
    }

    if (query['filter[rate][max]']) {
      maxRate.value = parseFloat(query['filter[rate][max]']);
    }

    // Parse filter[rating]
    if (query['filter[rating]']) {
      minRating.value = parseFloat(query['filter[rating]']);
    }
  }

  function clearAllFilters() {
    selectedStacks.value = [];
    selectedLanguages.value = [];
    selectedExperience.value = [];
    minRate.value = null;
    maxRate.value = null;
    minRating.value = null;
  }

  function setOptions({ stacks, languages, currencies }) {
    stackOptions.value = stacks || [];
    languageOptions.value = languages || [];
    currencyOptions.value = currencies || [];
  }

  return {
    // State
    selectedStacks,
    selectedLanguages,
    selectedExperience,
    minRate,
    maxRate,
    selectedCurrency,
    minRating,

    // Options
    stackOptions,
    languageOptions,
    currencyOptions,
    experienceOptions,
    ratingOptions,

    // Computed
    activeFilterCount,

    // Methods
    buildQueryParams,
    parseQueryParams,
    clearAllFilters,
    setOptions,
  };
});
