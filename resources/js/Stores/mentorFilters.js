import { defineStore } from 'pinia';
import { computed, ref } from 'vue';

export const useMentorFilters = defineStore('mentorFilters', () => {
  // ============ State ============
  const selectedStacks = ref([]);
  const selectedLanguages = ref([]);
  const selectedExperience = ref([]);
  const minRate = ref(0);
  const maxRate = ref(200);
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

  const activeFilterCount = computed(() => {
    let count = 0;
    if (selectedStacks.value.length > 0) count++;
    if (selectedLanguages.value.length > 0) count++;
    if (selectedExperience.value.length > 0) count++;
    if (minRate.value > 0) count++;
    if (maxRate.value < 200) count++;
    if (minRating.value !== null) count++;
    return count;
  });

  const activeFilters = computed(() => {
    const filters = [];
    selectedStacks.value.forEach((s) =>
      filters.push({ type: 'stacks', label: s, value: s }),
    );
    selectedLanguages.value.forEach((l) =>
      filters.push({ type: 'languages', label: l, value: l }),
    );
    selectedExperience.value.forEach((e) => {
      const opt = experienceOptions.find((o) => o.value === e);
      filters.push({ type: 'experience', label: opt?.label || e, value: e });
    });
    if (minRate.value > 0) {
      filters.push({
        type: 'minRate',
        label: `Min ${selectedCurrency.value} ${minRate.value}/hr`,
        value: minRate.value,
      });
    }
    if (maxRate.value < 200) {
      filters.push({
        type: 'maxRate',
        label: `Max ${selectedCurrency.value} ${maxRate.value}/hr`,
        value: maxRate.value,
      });
    }
    if (minRating.value !== null) {
      filters.push({
        type: 'rating',
        label: `${minRating.value}+ stars`,
        value: minRating.value,
      });
    }
    return filters;
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

    if (minRate.value > 0) {
      params['filter[rate][min]'] = minRate.value;
    }

    if (maxRate.value < 200) {
      params['filter[rate][max]'] = maxRate.value;
    }

    if (minRating.value !== null) {
      params['filter[rating]'] = minRating.value;
    }

    if (selectedCurrency.value && selectedCurrency.value !== 'USD') {
      params['filter[currency]'] = selectedCurrency.value;
    }

    return params;
  }

  /**
   * Parse query params from URL
   * Laravel converts filter[stacks] to filter: { stacks: 'Laravel' }
   * Example: parseQueryParams({ filter: { stacks: 'Laravel,React' } })
   */
  function parseQueryParams(query) {
    if (!query) return;

    // Laravel passes filters as nested object
    const filter = query.filter || {};

    // Parse stacks
    if (filter.stacks) {
      selectedStacks.value = filter.stacks.split(',');
    }

    // Parse languages
    if (filter.languages) {
      selectedLanguages.value = filter.languages.split(',');
    }

    // Parse experience
    if (filter.experience) {
      selectedExperience.value = filter.experience.split(',');
    }

    if (filter.rate) {
      if (filter.rate.min) {
        minRate.value = Math.max(0, parseFloat(filter.rate.min));
      }
      if (filter.rate.max) {
        maxRate.value = Math.min(200, parseFloat(filter.rate.max));
      }
    }

    // Parse rating
    if (filter.rating) {
      minRating.value = parseFloat(filter.rating);
    }

    if (filter.currency) {
      selectedCurrency.value = filter.currency;
    }
  }

  function removeFilter(type, value) {
    switch (type) {
      case 'stacks':
        selectedStacks.value = selectedStacks.value.filter((s) => s !== value);
        break;
      case 'languages':
        selectedLanguages.value = selectedLanguages.value.filter(
          (l) => l !== value,
        );
        break;
      case 'experience':
        selectedExperience.value = selectedExperience.value.filter(
          (e) => e !== value,
        );
        break;
      case 'minRate':
        minRate.value = 0;
        break;
      case 'maxRate':
        maxRate.value = 200;
        break;
      case 'rating':
        minRating.value = null;
        break;
    }
  }

  function clearAllFilters() {
    selectedStacks.value = [];
    selectedLanguages.value = [];
    selectedExperience.value = [];
    minRate.value = 0;
    maxRate.value = 200;
    minRating.value = null;
    selectedCurrency.value = 'USD';
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
    activeFilters,

    // Methods
    buildQueryParams,
    parseQueryParams,
    removeFilter,
    clearAllFilters,
    setOptions,
  };
});
