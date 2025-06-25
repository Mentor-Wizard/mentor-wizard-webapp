<script setup>
defineProps({
  url: {
    type: String,
      required: true,
  },
});
</script>

<template>
    <a v-if="url" :href="url" target="_blank"
       rel="noopener noreferrer">
        <button
            class="cursor-pointer w-10 h-10 flex items-center justify-center rounded-lg bg-white shadow-md shadow-gray-200 group transition-all duration-300">
            <svg
                class="rounded-md transition-all duration-300 group-hover:scale-110"
                xmlns="http://www.w3.org/2000/svg" width="28" height="28"
                viewBox="0 0 72 72" fill="none">
                <path fill-rule="evenodd" clip-rule="evenodd"
                      d="M14.6975 11C12.6561 11 11 12.6057 11 14.5838V57.4474C11 59.4257 12.6563 61.03 14.6975 61.03H57.3325C59.3747 61.03 61.03 59.4255 61.03 57.4468V14.5838C61.03 12.6057 59.3747 11 57.3325 11H14.6975ZM26.2032 30.345V52.8686H18.7167V30.345H26.2032ZM26.6967 23.3793C26.6967 25.5407 25.0717 27.2703 22.4615 27.2703L22.4609 27.2701H22.4124C19.8998 27.2701 18.2754 25.5405 18.2754 23.3791C18.2754 21.1686 19.9489 19.4873 22.5111 19.4873C25.0717 19.4873 26.6478 21.1686 26.6967 23.3793ZM37.833 52.8686H30.3471L30.3469 52.8694C30.3469 52.8694 30.4452 32.4588 30.3475 30.3458H37.8336V33.5339C38.8288 31.9995 40.6098 29.8169 44.5808 29.8169C49.5062 29.8169 53.1991 33.0363 53.1991 39.9543V52.8686H45.7133V40.8204C45.7133 37.7922 44.6293 35.7269 41.921 35.7269C39.8524 35.7269 38.6206 37.1198 38.0796 38.4653C37.8819 38.9455 37.833 39.6195 37.833 40.2918V52.8686Z"
                      fill="#006699"></path>
            </svg>
        </button>
    </a>
</template>
