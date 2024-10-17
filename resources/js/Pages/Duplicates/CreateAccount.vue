<script setup>
import Heading from '@/Components/Heading.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm, router } from "@inertiajs/vue3";
import { ref } from 'vue';

const props = defineProps({
    user: {
        type: Object,
        required: true
    },
    errors: Object,
});

const form = useForm({
    email: props.user.email,
});

const initialEmail = props.user.email;

const tempEmail = ref();

const submitForm = () => {
    router.post(route('duplicates.consent'), form)
};

const openModal = () => {
    document.getElementById('modal-change-email').showModal();
};

const updateEmail = () => {
    form.email = tempEmail.value;
    document.getElementById('modal-change-email').close();
};
</script>

<template>
    <AppLayout>

        <Head title="Create Account - Exnota Tools" />
        <!--
                        🚨🚨🚨
                        If updating any of this page, also update consent grant
                        in app/Http/Controllers/DuplicatesConsentController.php
                        🚨🚨🚨
                     -->
        <div class="mb-6">
            <Heading :level="1" class="truncate">
                2/4 Create account
            </Heading>
        </div>
        <div>
            <p>One-step sign up to save your Notion details. No spam.</p>
        </div>
        <form @submit.prevent="submitForm">
            <div class="mb-16 mt-16">
                <!-- <label for="email" class="block text-sm font-medium text-gray-700">Email</label> -->
                <p v-if="initialEmail === form.email" class="mb-4">We'll use your Notion email for signup, unless you
                    change it below.</p>
                <p v-if="initialEmail !== form.email" class="mb-4">We'll use this email instead of your Notion email.
                </p>
                <div class="mt-1 flex rounded-md justify-between border p-4">
                    <div>
                        <p class="font-bold">Email address</p>
                        <p>{{ form.email }}</p>
                        <input type="hidden" name="email" id="email" v-model="form.email"
                            class="flex-1 input input-bordered" placeholder="Enter your email address..." required>

                    </div>

                    <button type="button" @click="openModal"
                        class="ml-3 inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Change email
                    </button>
                </div>
                <div v-if="errors.email" role="alert" class="alert alert-error mt-4">
                    <span>{{ errors.email }}</span>
                </div>
            </div>
            <p class="mt-16 mb-4 text-sm">
                By continuing, you acknowledge that you understand and agree to the
                <a href="/terms-of-service" target="_blank" class="text-indigo-600 hover:text-indigo-500">Terms &
                    Conditions</a> and
                <a href="/privacy-policy" target="_blank" class="text-indigo-600 hover:text-indigo-500">Privacy
                    Policy</a>.
            </p>
            <div class="flex justify-end">
                <button type="submit"
                    class="flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Create account
                </button>
            </div>
        </form>

        <!-- Modal for changing email -->
        <dialog id="modal-change-email" class="modal">
            <div class="modal-box">
                <form method="dialog">
                    <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
                </form>
                <h3 class="font-bold text-lg">Change Email</h3>
                <label class="input input-bordered flex items-center gap-2 mt-4">
                    <input type="email" id="new-email" v-model="tempEmail" class="grow border-0"
                        placeholder="Enter your email address..." />
                </label>
                <div class="modal-action">
                    <form method="dialog">
                        <button class="btn">Cancel</button>
                    </form>
                    <button @click="updateEmail" class="btn btn-primary">Change Email</button>
                </div>
            </div>
            <form method="dialog" class="modal-backdrop">
                <button>close</button>
            </form>
        </dialog>
    </AppLayout>
</template>
