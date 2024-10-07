<script setup>
import Heading from '@/Components/Heading.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm } from "@inertiajs/vue3";
import { ref } from 'vue';

const props = defineProps({
    user: {
        type: Object,
        required: true
    }
});

const form = useForm({
    email: props.user.email,
});

const submitForm = () => {
    router.post(route('duplicates.consent'), form)
};
</script>

<template>
    <AppLayout>

        <Head title="Create Account - Exnota Tools" />
        <div class="bg-background">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-background overflow-hidden p-6">
                    <div class="mb-6">
                        <Heading :level="1" class="truncate">
                            2/4 Create account
                        </Heading>
                    </div>
                    <p class="mb-6 text-gray-600">One-step sign-up to save your Notion details.</p>
                    <p class="mb-6 text-gray-600">No marketing. Important emails only.</p>
                    <form @submit.prevent="submitForm">
                        <div class="mb-6">
                            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                            <div class="mt-1 flex rounded-md shadow-sm">
                                <input disabled type="email" name="email" id="email" v-model="form.email"
                                    class="flex-1 input input-bordered" placeholder="Enter your email address..."
                                    required>
                                <button type="button"
                                    class="ml-3 inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    Change
                                </button>
                            </div>
                        </div>
                        <p class="mb-6 text-sm text-gray-600">
                            By continuing, you acknowledge that you understand and agree to the
                            <a href="#" class="text-indigo-600 hover:text-indigo-500">Terms & Conditions</a> and
                            <a href="#" class="text-indigo-600 hover:text-indigo-500">Privacy Policy</a>.
                        </p>
                        <div class="flex justify-end">
                            <button type="submit"
                                class="flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Create account
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
