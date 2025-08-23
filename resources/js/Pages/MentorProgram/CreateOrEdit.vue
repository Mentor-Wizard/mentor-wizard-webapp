<script setup>
import { computed, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TextInput from '@/Components/UI/Forms/TextInput.vue';
import InputLabel from '@/Components/UI/Forms/InputLabel.vue';
import InputError from '@/Components/UI/Forms/InputError.vue';
import PrimaryButton from '@/Components/UI/Button/PrimaryButton.vue';
import DangerButton from '@/Components/UI/Button/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import TextArea from '@/Components/UI/Forms/TextArea.vue';
import SelectField from '@/Components/UI/Forms/SelectField.vue';

const props = defineProps({
  program: {
    type: Object,
    default: null,
  },
  currencies: {
    type: Object,
    required: true,
  },
});

const showDeleteModal = ref(false);

const form = useForm({
  name: props.program?.name ?? '',
  description: props.program?.description ?? '',
  cost: props.program?.cost ?? '',
  currency_id: props.program?.currency_id ?? '',
  slug: props.program?.slug ?? '',
});

const isEdit = computed(() => {
  return props.program !== null && typeof props.program === 'object';
});

const submit = () => {
  if (isEdit.value) {
    form.patch(route('mentor-program.update', props.program.slug), {
      onSuccess: () => {
        form.reset();
      },
    });
  } else {
    form.post(route('mentor-program.store'), {
      onSuccess: () => {
        form.reset();
      },
    });
  }
};

const confirmDelete = () => {
  showDeleteModal.value = true;
};

const deleteProgram = () => {
  form.delete(route('mentor-program.destroy', props.program.slug), {
    onSuccess: () => {
      showDeleteModal.value = false;
    },
  });
};
</script>

<template>
  <AuthenticatedLayout>
    <template #header>
      <h2 class="text-xl leading-tight font-semibold text-gray-800">
        {{ isEdit ? 'Edit Mentor Program' : 'Create New Mentor Program' }}
      </h2>
    </template>

    <div class="py-12">
      <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
        <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
          <div class="border-b border-gray-200 bg-white p-8">
            <form
              class="space-y-8 divide-y divide-gray-200"
              @submit.prevent="submit"
            >
              <div class="space-y-6">
                <div>
                  <div class="space-y-6">
                    <div>
                      <InputLabel for="name" value="Program Name" />
                      <TextInput id="name" v-model="form.name" type="text" />
                      <InputError
                        v-if="form.errors.name"
                        :message="form.errors.name"
                        class="mt-2"
                      />
                    </div>

                    <div>
                      <InputLabel for="description" value="Description" />
                      <TextArea
                        id="description"
                        v-model="form.description"
                        rows="3"
                      />
                      <InputError
                        v-if="form.errors.description"
                        :message="form.errors.description"
                        class="mt-2"
                      />
                    </div>

                    <div
                      class="mb-2 grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2"
                    >
                      <div>
                        <InputLabel for="cost" value="Cost" />
                        <TextInput
                          id="cost"
                          v-model="form.cost"
                          type="number"
                          required
                        />
                        <InputError
                          v-if="form.errors.cost"
                          :message="form.errors.cost"
                          class="mt-2"
                        />
                      </div>

                      <div>
                        <InputLabel for="currency_id" value="Currency" />
                        <SelectField
                          id="currency_id"
                          v-model="form.currency_id"
                          :currencies="currencies"
                          required
                        />
                        <InputError
                          v-if="form.errors.currency_id"
                          :message="form.errors.currency_id"
                          class="mt-2"
                        />
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="pt-6">
                <div class="flex justify-end space-x-3">
                  <DangerButton
                    v-if="isEdit"
                    type="button"
                    class="inline-flex justify-center"
                    @click="confirmDelete"
                  >
                    Delete Program
                  </DangerButton>

                  <PrimaryButton
                    :disabled="form.processing"
                    class="inline-flex justify-center"
                  >
                    {{ isEdit ? 'Update Program' : 'Create Program' }}
                  </PrimaryButton>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <Modal v-model="showDeleteModal">
      <div class="p-6">
        <h2 class="text-lg font-medium text-gray-900">
          Are you sure you want to delete this program?
        </h2>
        <p class="mt-1 text-sm text-gray-600">
          Once this program is deleted, all of its resources and data will be
          permanently deleted.
        </p>
        <div class="mt-6 flex justify-end space-x-3">
          <PrimaryButton @click="showDeleteModal = false">
            Cancel
          </PrimaryButton>
          <DangerButton :disabled="form.processing" @click="deleteProgram">
            Delete Program
          </DangerButton>
        </div>
      </div>
    </Modal>
  </AuthenticatedLayout>
</template>
