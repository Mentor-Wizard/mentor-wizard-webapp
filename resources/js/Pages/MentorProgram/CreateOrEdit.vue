<script setup>
import { computed, ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import TextInput from '@/Components/UI/Forms/TextInput.vue'
import InputLabel from '@/Components/UI/Forms/InputLabel.vue'
import InputError from '@/Components/UI/Forms/InputError.vue'
import PrimaryButton from '@/Components/UI/Button/PrimaryButton.vue'
import DangerButton from '@/Components/UI/Button/DangerButton.vue'
import Modal from '@/Components/Modal.vue'

const props = defineProps({
    program: {
        type: Object,
        default: null
    },
    currencies: {
        type: Array,
        required: true
    }

})

const showDeleteModal = ref(false)

const form = useForm({
    name: props.program?.name || '',
    description: props.program?.description || '',
    cost: props.program?.cost || '',
    currency_id: props.program?.currency_id || '',
    slug: props.program?.slug || ''
})

const isEdit = computed(() => !!props.program)

const submit = () => {
    if (isEdit.value) {
        form.put(route('mentor-programs.update', props.program.id))
    } else {
        form.post(route('mentor-programs.store'))
    }
}

const confirmDelete = () => {
    showDeleteModal.value = true
}

const deleteProgram = () => {
    form.delete(route('mentor-programs.destroy', props.program.id), {
        onSuccess: () => {
            showDeleteModal.value = false
        }
    })
}
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ isEdit ? 'Edit Mentor Program' : 'Create New Mentor Program' }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-8 bg-white border-b border-gray-200">
                        <form @submit.prevent="submit" class="space-y-8 divide-y divide-gray-200">
                            <div class="space-y-6">
                                <div>
                                    <h3 class="text-lg font-medium leading-6 text-gray-900 mb-6">
                                        Program Information
                                    </h3>

                                    <div class="space-y-6">
                                        <div>
                                            <InputLabel for="name" value="Program Name"
                                                class="block text-sm font-medium text-gray-700" />
                                            <TextInput id="name" v-model="form.name" type="text"
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                                required />
                                            <InputError :message="form.errors.name" class="mt-2" />
                                        </div>

                                        <div>
                                            <InputLabel for="description" value="Description"
                                                class="block text-sm font-medium text-gray-700" />
                                            <textarea id="description" v-model="form.description" rows="4"
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                                            <InputError :message="form.errors.description" class="mt-2" />
                                        </div>

                                        <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-2">
                                            <div>
                                                <InputLabel for="cost" value="Cost"
                                                    class="block text-sm font-medium text-gray-700" />
                                                <TextInput id="cost" v-model="form.cost" type="number"
                                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                                    required />
                                                <InputError :message="form.errors.cost" class="mt-2" />
                                            </div>

                                            <div>
                                                <InputLabel for="currency_id" value="Currency"
                                                    class="block text-sm font-medium text-gray-700" />
                                                <select id="currency_id" v-model="form.currency_id"
                                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                                    required>
                                                    <option v-for="currency in currencies" :key="currency.id"
                                                        :value="currency.id">
                                                        {{ currency.code }}
                                                    </option>
                                                </select>
                                                <InputError :message="form.errors.currency_id" class="mt-2" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="pt-6">
                                <div class="flex justify-end space-x-3">
                                    <DangerButton v-if="program" type="button" @click="confirmDelete"
                                        class="inline-flex justify-center">
                                        Delete Program
                                    </DangerButton>

                                    <PrimaryButton :disabled="form.processing" class="inline-flex justify-center">
                                        {{ program ? 'Update Program' : 'Create Program' }}
                                    </PrimaryButton>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <Modal :show="showDeleteModal" @close="showDeleteModal = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">
                    Are you sure you want to delete this program?
                </h2>
                <p class="mt-1 text-sm text-gray-600">
                    Once this program is deleted, all of its resources and data will be permanently deleted.
                </p>
                <div class="mt-6 flex justify-end space-x-3">
                    <PrimaryButton @click="showDeleteModal = false">
                        Cancel
                    </PrimaryButton>
                    <DangerButton @click="deleteProgram" :disabled="form.processing">
                        Delete Program
                    </DangerButton>
                </div>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>