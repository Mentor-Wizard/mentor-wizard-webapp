<script setup>
import InputError from '@/Components/UI/Forms/InputError.vue';
import InputLabel from '@/Components/UI/Forms/InputLabel.vue';
import PrimaryButton from '@/Components/UI/Button/PrimaryButton.vue';
import TextInput from '@/Components/UI/Forms/TextInput.vue';
import TextArea from '@/Components/UI/Forms/TextArea.vue';
import {useForm, usePage} from '@inertiajs/vue3';
import InputSuccess from "@/Components/UI/Forms/InputSuccess.vue";
import PhoneNumberInput from "@/Components/UI/Forms/PhoneNumberInput.vue";

import {ref} from 'vue';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;
const profile = user?.profile;

const form = useForm({
    name: profile?.name,
    last_name: profile?.last_name,
    phone: profile?.phone,
    linkedin: profile?.linkedin ?? '',
    telegram: profile?.telegram ?? '',
    whatsapp: profile?.whatsapp ?? '',
});

const submit = () => {
    form.patch(route('profile.update'), {
        forceFormData: true,
    });
};
</script>

<template>
    <div class="grid max-w-7xl grid-cols-1 gap-x-8 gap-y-10 px-4 py-16 sm:px-6 md:grid-cols-3 lg:px-8">
        <div>
            <h2 class="text-base/7 font-semibold">Personal Information</h2>
            <p class="mt-1 text-sm/6 text-gray-400">Add additional information about yourself.</p>
        </div>
        <form @submit.prevent="submit" class="md:col-span-2">
            <div class="grid grid-cols-1 gap-x-6 gap-y-8 sm:max-w-xl sm:grid-cols-6">
                <div class="col-span-full">
                    <InputLabel for="name" value="Name"/>

                    <div class="mt-2">
                        <TextInput id="name" v-model="form.name" required/>
                    </div>
                    <InputError class="mt-2" :message="form.errors.name"/>
                </div>

                <div class="col-span-full">
                    <InputLabel for="last_name" value="Last name"/>

                    <div class="mt-2">
                        <TextInput id="last_name" v-model="form.last_name" required/>
                    </div>

                    <InputError class="mt-2" :message="form.errors.last_name"/>
                </div>
                <div class="col-span-full">
                    <InputLabel for="email" value="Phone"/>

                    <div class="mt-2">
                        <PhoneNumberInput v-model="form.phone"/>
                    </div>
                    <p class="text-sm/6 text-gray-500">Example: +380671234578</p>
                    <InputError class="mt-2" :message="form.errors.phone"/>
                </div>

                <div class="col-span-full">
                    <InputLabel for="linkedin" value="Linkedin"/>

                    <div class="mt-2">
                        <TextInput id="linkedin" v-model="form.linkedin"/>
                    </div>
                    <p class="text-sm/6 text-gray-500">Example: https://www.linkedin.com/in/john</p>
                    <InputError class="mt-2" :message="form.errors.linkedin"/>
                </div>

                <div class="col-span-full">
                    <InputLabel for="telegram" value="Telegram"/>

                    <div class="mt-2">
                        <TextInput id="telegram" v-model="form.telegram"/>
                    </div>
                    <p class="text-sm/6 text-gray-500">Example: https://t.me/john</p>
                    <InputError class="mt-2" :message="form.errors.telegram"/>
                </div>

                <div class="col-span-full">
                    <InputLabel for="whatsapp" value="Whatsapp"/>

                    <div class="mt-2">
                        <TextInput id="whatsapp" v-model="form.whatsapp"/>
                    </div>
                    <p class="text-sm/6 text-gray-500">Example: https://wa.me/380671234578</p>
                    <InputError class="mt-2" :message="form.errors.whatsapp"/>
                </div>

                <div class="flex">
                    <div class="w-auto">
                        <PrimaryButton :class="{ 'opacity-25 cursor-not-allowed': form.processing }"
                                       :disabled="form.processing">
                            Save
                        </PrimaryButton>

                        <InputSuccess message="Saved" :is-show="form.recentlySuccessful"/>
                    </div>
                </div>
            </div>
        </form>
    </div>
</template>
