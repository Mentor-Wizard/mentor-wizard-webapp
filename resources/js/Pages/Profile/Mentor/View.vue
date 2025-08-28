<script setup>

import LandingLayout from "@/Layouts/LandingLayout.vue";
import {usePage} from '@inertiajs/vue3';
import {StarIcon} from '@heroicons/vue/20/solid'
import Pagination from '@/Components/Navigation/Pagination.vue'
import {computed} from "vue";
import LinkedinButton from "@/Components/UI/Button/LinkedinButton.vue";
import TelegramButton from "@/Components/UI/Button/TelegramButton.vue";
import WhatsappButton from "@/Components/UI/Button/WhatsappButton.vue";
import HeaderProfile from "@/Pages/Profile/Mentor/Blocks/HeaderProfile.vue";
import AboutMentor from "@/Pages/Profile/Mentor/Blocks/AboutMentor.vue";
import CalendarMentor from "@/Pages/Profile/Mentor/Blocks/CalendarMentor.vue";
import ContactMentor from "@/Pages/Profile/Mentor/Blocks/ContactMentor.vue";
import ReviewsMentor from "@/Pages/Profile/Mentor/Blocks/ReviewsMentor.vue";
import SimilarMentor from "@/Pages/Profile/Mentor/Blocks/SimilarMentors.vue";
import SimilarMentors from "@/Pages/Profile/Mentor/Blocks/SimilarMentors.vue";

const mentor = usePage().props.mentor
const reviews = computed(() => usePage().props.reviews)
const defaultAvatar = usePage().props.defaultAvatar

</script>

<template>
    <LandingLayout>
        <div class="py-12 bg-gray-100">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-xs sm:rounded-lg">
                    <HeaderProfile/>
                </div>
                <div class="py-4 grid grid-cols-3 gap-4">
                    <div class="col-span-2">
                      <div class="overflow-hidden bg-white shadow-xs sm:rounded-lg">
                        <main class="px-4 py-6 mx-auto max-w-2xl sm:px-6 lg:max-w-7xl lg:px-8">
                          <AboutMentor/>
                        </main>
                      </div>
                      <div class="mt-4 overflow-hidden bg-white shadow-xs sm:rounded-lg">
                        <main class="px-4 py-6 mx-auto max-w-2xl sm:px-6 lg:max-w-7xl lg:px-8">
                          <AboutMentor/>
                        </main>
                      </div>
                      <div class="mt-4 overflow-hidden bg-white shadow-xs sm:rounded-lg">
                        <main class="px-4 py-6 mx-auto max-w-2xl sm:px-6 lg:max-w-7xl lg:px-8">
                          <ReviewsMentor/>
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
               <SimilarMentors/>
          </div>
        </div>
    </LandingLayout>
</template>
