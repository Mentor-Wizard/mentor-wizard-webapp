<script setup>
import InputError from '@/Components/UI/Forms/InputError.vue';
import InputLabel from '@/Components/UI/Forms/InputLabel.vue';
import PrimaryButton from '@/Components/UI/Button/PrimaryButton.vue';
import TextInput from '@/Components/UI/Forms/TextInput.vue';
import {useForm, usePage} from '@inertiajs/vue3';
import InputSuccess from "@/Components/UI/Forms/InputSuccess.vue";
import SecondaryButton from "@/Components/UI/Button/SecondaryButton.vue";

import { ref } from 'vue';

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
const avatar = ref(usePage().props.avatar);
const avatarInput = ref(null)

const chooseFiles = () => {
  avatarInput.value?.click()
}

const form = useForm({
    username: user.username,
    email: user.email,
    avatar: null,
});

const onFileChange = (e) => {
    const file = e.target.files[0];
    if (!file) return;

    form.avatar = file;
    avatar.value = URL.createObjectURL(file);
};

const submit = () => {
    const formData = new FormData();
    formData.append('username', form.username);
    formData.append('email', form.email);
    if (form.avatar instanceof File) {
        formData.append('avatar', form.avatar);
    }

    form.patch(route('user.update'), {
        forceFormData: true,
    });
};
</script>

<template>
    <div class="grid max-w-7xl grid-cols-1 gap-x-8 gap-y-10 px-4 py-16 sm:px-6 md:grid-cols-3 lg:px-8">
        <div>
            <h2 class="text-base/7 font-semibold">Account Information</h2>
            <p class="mt-1 text-sm/6 text-gray-400">Use a permanent address where you can receive mail.</p>
        </div>
        <form @submit.prevent="submit" class="md:col-span-2">
            <div class="grid grid-cols-1 gap-x-6 gap-y-8 sm:max-w-xl sm:grid-cols-6">
                <div class="col-span-full flex items-center gap-x-8">
                    <img
                        :src="avatar"
                        alt="" class="size-24 flex-none rounded-lg bg-gray-800 object-cover"/>
                    <div>
                        <SecondaryButton
                            class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 ring-1 shadow-xs ring-gray-300 ring-inset hover:bg-gray-50"
                            @click="chooseFiles">
                            Change avatar
                        </SecondaryButton>
                        <p class="mt-2 text-xs/5 text-gray-400">JPG, GIF or PNG. 1MB max.</p>
                    </div>
                    <input type="file"
                           :hidden="true"
                           ref="avatarInput"
                           accept="image/gif, image/jpeg, image/png"
                           @change="onFileChange"/>

                </div>

                <div class="col-span-full">
                    <InputLabel for="username" value="User name"/>

                    <div class="mt-2">
                        <TextInput id="username" v-model="form.username" required/>
                    </div>
                    <InputError class="mt-2" :message="form.errors.username"/>
                </div>

                <div class="col-span-full">

                    <InputLabel for="email" value="Email address"/>

                    <div class="mt-2">
                        <TextInput id="email" type="email" autocomplete="email" v-model="form.email" required/>
                    </div>

                    <InputError class="mt-2" :message="form.errors.email"/>
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
