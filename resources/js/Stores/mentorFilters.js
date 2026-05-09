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
    const stacks =
      Array.isArray(selectedStacks.value) ? selectedStacks.value
      : selectedStacks.value ? [selectedStacks.value]
      : [];
    const languages =
      Array.isArray(selectedLanguages.value) ? selectedLanguages.value
      : selectedLanguages.value ? [selectedLanguages.value]
      : [];
    const experience =
      Array.isArray(selectedExperience.value) ? selectedExperience.value
      : selectedExperience.value ? [selectedExperience.value]
      : [];

    stacks.forEach((s) => filters.push({ type: 'stacks', label: s, value: s }));
    languages.forEach((l) =>
      filters.push({ type: 'languages', label: l, value: l }),
    );
    experience.forEach((e) => {
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
   * Build query params in nested object format
   * Example: { filter: { stacks: 'Laravel,React', experience: 'senior' } }
   */
  const queryParams = computed(() => {
    const params = {};
    const filter = {};

    const stacks =
      Array.isArray(selectedStacks.value) ? selectedStacks.value
      : selectedStacks.value ? [selectedStacks.value]
      : [];
    const languages =
      Array.isArray(selectedLanguages.value) ? selectedLanguages.value
      : selectedLanguages.value ? [selectedLanguages.value]
      : [];
    const experience =
      Array.isArray(selectedExperience.value) ? selectedExperience.value
      : selectedExperience.value ? [selectedExperience.value]
      : [];

    if (stacks.length > 0) {
      filter.stacks = stacks.join(',');
    }

    if (languages.length > 0) {
      filter.languages = languages.join(',');
    }

    if (experience.length > 0) {
      filter.experience = experience.join(',');
    }

    if (minRate.value > 0 || maxRate.value < 200) {
      filter.rate = {};
      if (minRate.value > 0) filter.rate.min = minRate.value;
      if (maxRate.value < 200) filter.rate.max = maxRate.value;
    }

    if (minRating.value !== null) {
      filter.rating = minRating.value;
    }

    if (selectedCurrency.value && selectedCurrency.value !== 'USD') {
      filter.currency = selectedCurrency.value;
    }

    if (Object.keys(filter).length > 0) {
      params.filter = filter;
    }

    return params;
  });

  function buildQueryParams() {
    return queryParams.value;
  }

  /**
   * Parse query params from URL
   * Laravel converts filter[stacks] to filter: { stacks: 'Laravel' }
   * Example: parseQueryParams({ filter: { stacks: 'Laravel,React' } })
   */
  function parseQueryParams(query) {
    if (!query) {
      clearAllFilters();
      return;
    }

    // Laravel passes filters as nested object
    const filter = query.filter || {};

    // Parse stacks
    const newStacks = filter.stacks ? filter.stacks.split(',') : [];
    if (JSON.stringify(selectedStacks.value) !== JSON.stringify(newStacks)) {
      selectedStacks.value = newStacks;
    }

    // Parse languages
    const newLanguages = filter.languages ? filter.languages.split(',') : [];
    if (
      JSON.stringify(selectedLanguages.value) !== JSON.stringify(newLanguages)
    ) {
      selectedLanguages.value = newLanguages;
    }

    // Parse experience
    const newExperience = filter.experience ? filter.experience.split(',') : [];
    if (
      JSON.stringify(selectedExperience.value) !== JSON.stringify(newExperience)
    ) {
      selectedExperience.value = newExperience;
    }

    if (filter.rate) {
      const newMin = Math.max(0, parseFloat(filter.rate.min) || 0);
      const newMax = Math.min(200, parseFloat(filter.rate.max) || 200);

      if (minRate.value !== newMin) minRate.value = newMin;
      if (maxRate.value !== newMax) maxRate.value = newMax;
    } else {
      if (minRate.value !== 0) minRate.value = 0;
      if (maxRate.value !== 200) maxRate.value = 200;
    }

    // Parse rating
    const newRating = filter.rating ? parseFloat(filter.rating) : null;
    if (minRating.value !== newRating) {
      minRating.value = newRating;
    }

    const newCurrency = filter.currency || 'USD';
    if (selectedCurrency.value !== newCurrency) {
      selectedCurrency.value = newCurrency;
    }
  }

  function removeFilter(type, value) {
    const stacks =
      Array.isArray(selectedStacks.value) ? selectedStacks.value
      : selectedStacks.value ? [selectedStacks.value]
      : [];
    const languages =
      Array.isArray(selectedLanguages.value) ? selectedLanguages.value
      : selectedLanguages.value ? [selectedLanguages.value]
      : [];
    const experience =
      Array.isArray(selectedExperience.value) ? selectedExperience.value
      : selectedExperience.value ? [selectedExperience.value]
      : [];

    switch (type) {
      case 'stacks':
        selectedStacks.value = stacks.filter((s) => s !== value);
        break;
      case 'languages':
        selectedLanguages.value = languages.filter((l) => l !== value);
        break;
      case 'experience':
        selectedExperience.value = experience.filter((e) => e !== value);
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
    queryParams,

    // Methods
    buildQueryParams,
    parseQueryParams,
    removeFilter,
    clearAllFilters,
    setOptions,
  };
});
