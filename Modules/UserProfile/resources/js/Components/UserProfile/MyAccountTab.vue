<script setup>
import UpdatePasswordForm from '@modules/Auth/resources/js/Components/Auth/UpdatePasswordForm.vue';
import DeleteUserForm from '@modules/UserProfile/resources/js/Components/UserProfile/Form/DeleteUserForm.vue';
import UpdateProfileForm from '@modules/UserProfile/resources/js/Components/UserProfile/Form/UpdateProfileForm.vue';
import UpdateUserForm from '@modules/UserProfile/resources/js/Components/UserProfile/Form/UpdateUserForm.vue';
</script>

<template>
  <UpdateUserForm />

  <UpdateProfileForm />

  <UpdatePasswordForm />

  <DeleteUserForm />
</template>
