<script setup>
import { usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const page = usePage();

import AboutMentor from '@modules/Marketplace/resources/js/Pages/Marketplace/Mentor/Blocks/AboutMentor.vue';
import AvailablePrograms from '@modules/Marketplace/resources/js/Pages/Marketplace/Mentor/Blocks/AvailablePrograms.vue';
import CalendarMentor from '@modules/Marketplace/resources/js/Pages/Marketplace/Mentor/Blocks/CalendarMentor.vue';
import ContactMentor from '@modules/Marketplace/resources/js/Pages/Marketplace/Mentor/Blocks/ContactMentor.vue';
import FooterMentor from '@modules/Marketplace/resources/js/Pages/Marketplace/Mentor/Blocks/FooterMentor.vue';
import ReviewsMentor from '@modules/Marketplace/resources/js/Pages/Marketplace/Mentor/Blocks/ReviewsMentor.vue';
import SimilarMentors from '@modules/Marketplace/resources/js/Pages/Marketplace/Mentor/Blocks/SimilarMentors.vue';
import TitleProfile from '@modules/Marketplace/resources/js/Pages/Marketplace/Mentor/Blocks/TitleProfile.vue';

import LandingLayout from '@/Layouts/LandingLayout.vue';

const mentor = page.props.mentor;
const reviewsArray = ref(Object.values(mentor.reviewBlock));
</script>

<template>
  <LandingLayout>
    <div class="bg-gray-100 py-12">
      <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        <div class="overflow-hidden bg-white shadow-xs sm:rounded-lg">
          <TitleProfile :data="mentor.titleBlock" />
        </div>
        <div class="grid grid-cols-3 gap-4 py-4">
          <div class="col-span-2">
            <div class="overflow-hidden bg-white shadow-xs sm:rounded-lg">
              <main
                class="mx-auto max-w-2xl px-4 py-6 sm:px-6 lg:max-w-7xl lg:px-8"
              >
                <AboutMentor
                  :title="mentor.titleBlock.title"
                  :description="mentor.titleBlock.description"
                />
              </main>
            </div>
            <div class="mt-4 overflow-hidden bg-white shadow-xs sm:rounded-lg">
              <main
                class="mx-auto max-w-2xl px-4 py-6 sm:px-6 lg:max-w-7xl lg:px-8"
              >
                <AvailablePrograms :data="mentor.programsBlock" />
              </main>
            </div>
            <div class="mt-4 overflow-hidden bg-white shadow-xs sm:rounded-lg">
              <main
                class="mx-auto max-w-2xl px-4 py-6 sm:px-6 lg:max-w-7xl lg:px-8"
              >
                <ReviewsMentor
                  v-model="reviewsArray"
                  :slug="mentor.slug"
                  :statistic="mentor.statisticBlock"
                />
              </main>
            </div>
          </div>
          <div>
            <div class="overflow-hidden bg-white shadow-xs sm:rounded-lg">
              <main
                class="mx-auto max-w-2xl px-4 py-6 sm:px-6 lg:max-w-7xl lg:px-8"
              >
                <CalendarMentor
                  :calendar-block="page.props.calendarBlock"
                  :main-program-slug="page.props.mainProgramSlug"
                  :main-program="page.props.mainProgram"
                  :week-days="page.props.weekDays"
                  :current-date="page.props.currentDate"
                />
              </main>
            </div>
            <div class="mt-4 overflow-hidden bg-white shadow-xs sm:rounded-lg">
              <main
                class="mx-auto max-w-2xl px-4 py-6 sm:px-6 lg:max-w-7xl lg:px-8"
              >
                <ContactMentor />
              </main>
            </div>
          </div>
        </div>
      </div>

      <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        <SimilarMentors :data="mentor.similarMentors" />
      </div>

      <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        <FooterMentor />
      </div>
    </div>
  </LandingLayout>
</template>
