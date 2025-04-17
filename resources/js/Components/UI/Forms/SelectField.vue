<script setup>
import { onMounted, ref } from 'vue';

const model = defineModel({
    type: [String, Number],
    required: true,
});

const options = defineProps({
    currencies: {
        type: Object,
        required: true,
    }
});

const select = ref(null);

onMounted(() => {
    if (select.value.hasAttribute('autofocus')) {
        select.value.focus();
    }
    if (!model.value && options.currencies.length > 0) {
        model.value = options.currencies[0].value;
    }
});

defineExpose({
    focus: () => select.value.focus(),
    click: () => select.value.click(),
});
</script>

<template>
    <select v-model="model" ref="select"
        class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6"
        required>
        <option value="" disabled>Select currency</option>
        <option v-for="currency in currencies" :key="currency.name" :value="currency.value">
            {{ currency.name }}
        </option>
    </select>
</template>
