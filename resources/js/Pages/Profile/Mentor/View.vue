<script setup>

import LandingLayout from "@/Layouts/LandingLayout.vue";
import {usePage} from '@inertiajs/vue3';
import AboutMentor from "@/Pages/Profile/Mentor/Blocks/AboutMentor.vue";
import CalendarMentor from "@/Pages/Profile/Mentor/Blocks/CalendarMentor.vue";
import ContactMentor from "@/Pages/Profile/Mentor/Blocks/ContactMentor.vue";
import ReviewsMentor from "@/Pages/Profile/Mentor/Blocks/ReviewsMentor.vue";
import SimilarMentors from "@/Pages/Profile/Mentor/Blocks/SimilarMentors.vue";
import FooterMentor from "@/Pages/Profile/Mentor/Blocks/FooterMentor.vue";
import AvailablePrograms from "@/Pages/Profile/Mentor/Blocks/AvailablePrograms.vue";
import TitleProfile from "@/Pages/Profile/Mentor/Blocks/TitleProfile.vue";

const mentor = usePage().props.mentor
const defaultAvatar = usePage().props.defaultAvatar

</script>

<template>
    <LandingLayout>
        <div class="py-12 bg-gray-100">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-xs sm:rounded-lg">
                    <TitleProfile
                      :data="mentor.titleBlock"
                    />
                </div>
                <div class="py-4 grid grid-cols-3 gap-4">
                    <div class="col-span-2">
                      <div class="overflow-hidden bg-white shadow-xs sm:rounded-lg">
                        <main class="px-4 py-6 mx-auto max-w-2xl sm:px-6 lg:max-w-7xl lg:px-8">
                          <AboutMentor
                            :title="mentor.titleBlock.title"
                            :description="mentor.titleBlock.description"
                          />
                        </main>
                      </div>
                      <div class="mt-4 overflow-hidden bg-white shadow-xs sm:rounded-lg">
                        <main class="px-4 py-6 mx-auto max-w-2xl sm:px-6 lg:max-w-7xl lg:px-8">
                          <AvailablePrograms
                            :data="mentor.programsBlock"
                          />
                        </main>
                      </div>
                      <div class="mt-4 overflow-hidden bg-white shadow-xs sm:rounded-lg">
                        <main class="px-4 py-6 mx-auto max-w-2xl sm:px-6 lg:max-w-7xl lg:px-8">
                          <ReviewsMentor
                            v-model="mentor.reviewBlock"
                            :slug="mentor.slug"
                            :statistic="mentor.statisticBlock"
                          />
                        </main>
                      </div>
                    </div>
                  <div>
                    <div class="overflow-hidden bg-white shadow-xs sm:rounded-lg">
                      <main class="px-4 py-6 mx-auto max-w-2xl sm:px-6 lg:max-w-7xl lg:px-8">
                        <CalendarMentor/>
                      </main>
                    </div>
                    <div class="mt-4 overflow-hidden bg-white shadow-xs sm:rounded-lg">
                    <main class="px-4 py-6 mx-auto max-w-2xl sm:px-6 lg:max-w-7xl lg:px-8">
                      <ContactMentor/>
                    </main>
                  </div>
                    </div>
                </div>
            </div>

          <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
               <SimilarMentors
                 :data="mentor.similarMentors"
               />
          </div>

          <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
               <FooterMentor/>
          </div>
        </div>
    </LandingLayout>
</template>
