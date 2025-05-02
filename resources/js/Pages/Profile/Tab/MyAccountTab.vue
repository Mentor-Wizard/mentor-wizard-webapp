<script setup>

import UpdateProfileInformationForm from "@/Pages/Profile/Partials/Form/UpdateProfileInformationForm.vue";
import UpdateProfileMainForm from "@/Pages/Profile/Partials/Form/UpdateProfileMainForm.vue";
import DeleteUserForm from "@/Pages/Profile/Partials/Form/DeleteUserForm.vue";
import UpdatePasswordForm from "@/Pages/Profile/Partials/Form/UpdatePasswordForm.vue";
</script>

<template>
  <UpdateProfileMainForm/>

  <UpdateProfileInformationForm/>

  <UpdatePasswordForm/>

  <DeleteUserForm/>
</template>
