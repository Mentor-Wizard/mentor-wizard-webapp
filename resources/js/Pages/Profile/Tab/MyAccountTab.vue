<script setup>
import UpdatePasswordForm from '@modules/Auth/resources/js/Components/Auth/UpdatePasswordForm.vue';

import DeleteUserForm from '@/Pages/Profile/Partials/Form/DeleteUserForm.vue';
import UpdateProfileForm from '@/Pages/Profile/Partials/Form/UpdateProfileForm.vue';
import UpdateUserForm from '@/Pages/Profile/Partials/Form/UpdateUserForm.vue';
</script>

<template>
  <UpdateUserForm />

  <UpdateProfileForm />

  <UpdatePasswordForm />

  <DeleteUserForm />
</template>
