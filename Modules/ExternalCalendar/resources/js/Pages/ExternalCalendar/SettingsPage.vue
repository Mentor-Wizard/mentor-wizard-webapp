<script setup>
import ExternalCalendarTab from '@modules/ExternalCalendar/resources/js/Components/ExternalCalendar/ExternalCalendarTab.vue';

import MainPageText from '@/Components/MainPageText.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
</script>

<template>
  <AuthenticatedLayout>
    <template #header>
      <MainPageText title="Calendar Settings" />
    </template>
    <div>
      <main>
        <div class="py-12">
          <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white p-8 shadow-xs sm:rounded-lg">
              <ExternalCalendarTab />
            </div>
          </div>
        </div>
      </main>
    </div>
  </AuthenticatedLayout>
</template>
