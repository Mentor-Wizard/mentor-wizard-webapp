<script setup>
import { ref, onMounted } from 'vue'
import { ChevronDownIcon, ChevronLeftIcon, ChevronRightIcon, EllipsisHorizontalIcon } from '@heroicons/vue/20/solid'
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue'

const days = [
    { date: '2021-12-27' },
    { date: '2021-12-28' },
    { date: '2021-12-29' },
    { date: '2021-12-30' },
    { date: '2021-12-31' },
    { date: '2022-01-01', isCurrentMonth: true },
    { date: '2022-01-02', isCurrentMonth: true },
    { date: '2022-01-03', isCurrentMonth: true },
    { date: '2022-01-04', isCurrentMonth: true },
    { date: '2022-01-05', isCurrentMonth: true },
    { date: '2022-01-06', isCurrentMonth: true },
    { date: '2022-01-07', isCurrentMonth: true },
    { date: '2022-01-08', isCurrentMonth: true },
    { date: '2022-01-09', isCurrentMonth: true },
    { date: '2022-01-10', isCurrentMonth: true },
    { date: '2022-01-11', isCurrentMonth: true },
    { date: '2022-01-12', isCurrentMonth: true },
    { date: '2022-01-13', isCurrentMonth: true },
    { date: '2022-01-14', isCurrentMonth: true },
    { date: '2022-01-15', isCurrentMonth: true },
    { date: '2022-01-16', isCurrentMonth: true },
    { date: '2022-01-17', isCurrentMonth: true },
    { date: '2022-01-18', isCurrentMonth: true },
    { date: '2022-01-19', isCurrentMonth: true },
    { date: '2022-01-20', isCurrentMonth: true, isToday: true },
    { date: '2022-01-21', isCurrentMonth: true },
    { date: '2022-01-22', isCurrentMonth: true, isSelected: true },
    { date: '2022-01-23', isCurrentMonth: true },
    { date: '2022-01-24', isCurrentMonth: true },
    { date: '2022-01-25', isCurrentMonth: true },
    { date: '2022-01-26', isCurrentMonth: true },
    { date: '2022-01-27', isCurrentMonth: true },
    { date: '2022-01-28', isCurrentMonth: true },
    { date: '2022-01-29', isCurrentMonth: true },
    { date: '2022-01-30', isCurrentMonth: true },
    { date: '2022-01-31', isCurrentMonth: true },
    { date: '2022-02-01' },
    { date: '2022-02-02' },
    { date: '2022-02-03' },
    { date: '2022-02-04' },
    { date: '2022-02-05' },
    { date: '2022-02-06' },
]

const container = ref(null)
const containerNav = ref(null)
const containerOffset = ref(null)

onMounted(() => {
    // Set the container scroll position based on the current time.
    const currentMinute = new Date().getHours() * 60
    container.value.scrollTop =
        ((container.value.scrollHeight - containerNav.value.offsetHeight - containerOffset.value.offsetHeight) *
            currentMinute) /
        1440
})
</script>

<template>
    <div class="flex h-full flex-col">
        <div class="isolate flex flex-auto overflow-hidden bg-white">
            <div ref="container" class="flex flex-auto flex-col overflow-auto">
                <div ref="containerNav" class="sticky top-0 z-10 grid flex-none grid-cols-7 bg-white text-xs text-gray-500 shadow-sm ring-1 ring-black/5 md:hidden">
                    <button type="button" class="flex flex-col items-center pt-3 pb-1.5">
                        <span>W</span>
                        <!-- Default: "text-gray-900", Selected: "bg-gray-900 text-white", Today (Not Selected): "text-indigo-600", Today (Selected): "bg-indigo-600 text-white" -->
                        <span class="mt-3 flex size-8 items-center justify-center rounded-full text-base font-semibold text-gray-900">19</span>
                    </button>
                    <button type="button" class="flex flex-col items-center pt-3 pb-1.5">
                        <span>T</span>
                        <span class="mt-3 flex size-8 items-center justify-center rounded-full text-base font-semibold text-indigo-600">20</span>
                    </button>
                    <button type="button" class="flex flex-col items-center pt-3 pb-1.5">
                        <span>F</span>
                        <span class="mt-3 flex size-8 items-center justify-center rounded-full text-base font-semibold text-gray-900">21</span>
                    </button>
                    <button type="button" class="flex flex-col items-center pt-3 pb-1.5">
                        <span>S</span>
                        <span class="mt-3 flex size-8 items-center justify-center rounded-full bg-gray-900 text-base font-semibold text-white">22</span>
                    </button>
                    <button type="button" class="flex flex-col items-center pt-3 pb-1.5">
                        <span>S</span>
                        <span class="mt-3 flex size-8 items-center justify-center rounded-full text-base font-semibold text-gray-900">23</span>
                    </button>
                    <button type="button" class="flex flex-col items-center pt-3 pb-1.5">
                        <span>M</span>
                        <span class="mt-3 flex size-8 items-center justify-center rounded-full text-base font-semibold text-gray-900">24</span>
                    </button>
                    <button type="button" class="flex flex-col items-center pt-3 pb-1.5">
                        <span>T</span>
                        <span class="mt-3 flex size-8 items-center justify-center rounded-full text-base font-semibold text-gray-900">25</span>
                    </button>
                </div>
                <div class="flex w-full flex-auto">
                    <div class="w-14 flex-none bg-white ring-1 ring-gray-100" />
                    <div class="grid flex-auto grid-cols-1 grid-rows-1">
                        <!-- Horizontal lines -->
                        <div class="col-start-1 col-end-2 row-start-1 grid divide-y divide-gray-100" style="grid-template-rows: repeat(48, minmax(3.5rem, 1fr))">
                            <div ref="containerOffset" class="row-end-1 h-7"></div>
                            <div>
                                <div class="sticky left-0 -mt-2.5 -ml-14 w-14 pr-2 text-right text-xs/5 text-gray-400">12AM</div>
                            </div>
                            <div />
                            <div>
                                <div class="sticky left-0 -mt-2.5 -ml-14 w-14 pr-2 text-right text-xs/5 text-gray-400">1AM</div>
                            </div>
                            <div />
                            <div>
                                <div class="sticky left-0 -mt-2.5 -ml-14 w-14 pr-2 text-right text-xs/5 text-gray-400">2AM</div>
                            </div>
                            <div />
                            <div>
                                <div class="sticky left-0 -mt-2.5 -ml-14 w-14 pr-2 text-right text-xs/5 text-gray-400">3AM</div>
                            </div>
                            <div />
                            <div>
                                <div class="sticky left-0 -mt-2.5 -ml-14 w-14 pr-2 text-right text-xs/5 text-gray-400">4AM</div>
                            </div>
                            <div />
                            <div>
                                <div class="sticky left-0 -mt-2.5 -ml-14 w-14 pr-2 text-right text-xs/5 text-gray-400">5AM</div>
                            </div>
                            <div />
                            <div>
                                <div class="sticky left-0 -mt-2.5 -ml-14 w-14 pr-2 text-right text-xs/5 text-gray-400">6AM</div>
                            </div>
                            <div />
                            <div>
                                <div class="sticky left-0 -mt-2.5 -ml-14 w-14 pr-2 text-right text-xs/5 text-gray-400">7AM</div>
                            </div>
                            <div />
                            <div>
                                <div class="sticky left-0 -mt-2.5 -ml-14 w-14 pr-2 text-right text-xs/5 text-gray-400">8AM</div>
                            </div>
                            <div />
                            <div>
                                <div class="sticky left-0 -mt-2.5 -ml-14 w-14 pr-2 text-right text-xs/5 text-gray-400">9AM</div>
                            </div>
                            <div />
                            <div>
                                <div class="sticky left-0 -mt-2.5 -ml-14 w-14 pr-2 text-right text-xs/5 text-gray-400">10AM</div>
                            </div>
                            <div />
                            <div>
                                <div class="sticky left-0 -mt-2.5 -ml-14 w-14 pr-2 text-right text-xs/5 text-gray-400">11AM</div>
                            </div>
                            <div />
                            <div>
                                <div class="sticky left-0 -mt-2.5 -ml-14 w-14 pr-2 text-right text-xs/5 text-gray-400">12PM</div>
                            </div>
                            <div />
                            <div>
                                <div class="sticky left-0 -mt-2.5 -ml-14 w-14 pr-2 text-right text-xs/5 text-gray-400">1PM</div>
                            </div>
                            <div />
                            <div>
                                <div class="sticky left-0 -mt-2.5 -ml-14 w-14 pr-2 text-right text-xs/5 text-gray-400">2PM</div>
                            </div>
                            <div />
                            <div>
                                <div class="sticky left-0 -mt-2.5 -ml-14 w-14 pr-2 text-right text-xs/5 text-gray-400">3PM</div>
                            </div>
                            <div />
                            <div>
                                <div class="sticky left-0 -mt-2.5 -ml-14 w-14 pr-2 text-right text-xs/5 text-gray-400">4PM</div>
                            </div>
                            <div />
                            <div>
                                <div class="sticky left-0 -mt-2.5 -ml-14 w-14 pr-2 text-right text-xs/5 text-gray-400">5PM</div>
                            </div>
                            <div />
                            <div>
                                <div class="sticky left-0 -mt-2.5 -ml-14 w-14 pr-2 text-right text-xs/5 text-gray-400">6PM</div>
                            </div>
                            <div />
                            <div>
                                <div class="sticky left-0 -mt-2.5 -ml-14 w-14 pr-2 text-right text-xs/5 text-gray-400">7PM</div>
                            </div>
                            <div />
                            <div>
                                <div class="sticky left-0 -mt-2.5 -ml-14 w-14 pr-2 text-right text-xs/5 text-gray-400">8PM</div>
                            </div>
                            <div />
                            <div>
                                <div class="sticky left-0 -mt-2.5 -ml-14 w-14 pr-2 text-right text-xs/5 text-gray-400">9PM</div>
                            </div>
                            <div />
                            <div>
                                <div class="sticky left-0 -mt-2.5 -ml-14 w-14 pr-2 text-right text-xs/5 text-gray-400">10PM</div>
                            </div>
                            <div />
                            <div>
                                <div class="sticky left-0 -mt-2.5 -ml-14 w-14 pr-2 text-right text-xs/5 text-gray-400">11PM</div>
                            </div>
                            <div />
                        </div>

                        <!-- Events -->
                        <ol class="col-start-1 col-end-2 row-start-1 grid grid-cols-1" style="grid-template-rows: 1.75rem repeat(288, minmax(0, 1fr)) auto">
                            <li class="relative mt-px flex" style="grid-row: 74 / span 12">
                                <a href="#" class="group absolute inset-1 flex flex-col overflow-y-auto rounded-lg bg-blue-50 p-2 text-xs/5 hover:bg-blue-100">
                                    <p class="order-1 font-semibold text-blue-700">Breakfast</p>
                                    <p class="text-blue-500 group-hover:text-blue-700">
                                        <time datetime="2022-01-22T06:00">6:00 AM</time>
                                    </p>
                                </a>
                            </li>
                            <li class="relative mt-px flex" style="grid-row: 92 / span 30">
                                <a href="#" class="group absolute inset-1 flex flex-col overflow-y-auto rounded-lg bg-pink-50 p-2 text-xs/5 hover:bg-pink-100">
                                    <p class="order-1 font-semibold text-pink-700">Flight to Paris</p>
                                    <p class="order-1 text-pink-500 group-hover:text-pink-700">John F. Kennedy International Airport</p>
                                    <p class="text-pink-500 group-hover:text-pink-700">
                                        <time datetime="2022-01-22T07:30">7:30 AM</time>
                                    </p>
                                </a>
                            </li>
                            <li class="relative mt-px flex" style="grid-row: 134 / span 18">
                                <a href="#" class="group absolute inset-1 flex flex-col overflow-y-auto rounded-lg bg-indigo-50 p-2 text-xs/5 hover:bg-indigo-100">
                                    <p class="order-1 font-semibold text-indigo-700">Sightseeing</p>
                                    <p class="order-1 text-indigo-500 group-hover:text-indigo-700">Eiffel Tower</p>
                                    <p class="text-indigo-500 group-hover:text-indigo-700">
                                        <time datetime="2022-01-22T11:00">11:00 AM</time>
                                    </p>
                                </a>
                            </li>
                        </ol>
                    </div>
                </div>
            </div>
            <div class="hidden w-1/2 max-w-md flex-none border-l border-gray-100 px-8 py-10 md:block">
                <div class="flex items-center text-center text-gray-900">
                    <button type="button" class="-m-1.5 flex flex-none items-center justify-center p-1.5 text-gray-400 hover:text-gray-500">
                        <span class="sr-only">Previous month</span>
                        <ChevronLeftIcon class="size-5" aria-hidden="true" />
                    </button>
                    <div class="flex-auto text-sm font-semibold">January 2022</div>
                    <button type="button" class="-m-1.5 flex flex-none items-center justify-center p-1.5 text-gray-400 hover:text-gray-500">
                        <span class="sr-only">Next month</span>
                        <ChevronRightIcon class="size-5" aria-hidden="true" />
                    </button>
                </div>
                <div class="mt-6 grid grid-cols-7 text-center text-xs/6 text-gray-500">
                    <div>M</div>
                    <div>T</div>
                    <div>W</div>
                    <div>T</div>
                    <div>F</div>
                    <div>S</div>
                    <div>S</div>
                </div>
                <div class="isolate mt-2 grid grid-cols-7 gap-px rounded-lg bg-gray-200 text-sm shadow-sm ring-1 ring-gray-200">
                    <button v-for="(day, dayIdx) in days" :key="day.date" type="button" :class="['py-1.5 hover:bg-gray-100 focus:z-10', day.isCurrentMonth ? 'bg-white' : 'bg-gray-50', (day.isSelected || day.isToday) && 'font-semibold', day.isSelected && 'text-white', !day.isSelected && day.isCurrentMonth && !day.isToday && 'text-gray-900', !day.isSelected && !day.isCurrentMonth && !day.isToday && 'text-gray-400', day.isToday && !day.isSelected && 'text-indigo-600', dayIdx === 0 && 'rounded-tl-lg', dayIdx === 6 && 'rounded-tr-lg', dayIdx === days.length - 7 && 'rounded-bl-lg', dayIdx === days.length - 1 && 'rounded-br-lg']">
                        <time :datetime="day.date" :class="['mx-auto flex size-7 items-center justify-center rounded-full', day.isSelected && day.isToday && 'bg-indigo-600', day.isSelected && !day.isToday && 'bg-gray-900']">{{ day.date.split('-').pop().replace(/^0/, '') }}</time>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
