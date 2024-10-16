<script setup>
import { router } from '@inertiajs/vue3';
import { route } from "../../../../../../vendor/tightenco/ziggy";


// Get "code" request param
const urlParams = new URLSearchParams(window.location.search);
const code = urlParams.get('code');

// Send code to server
fetch('/api/notion/auth/save', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({ code })
})
    .then(async (response) => {
        if (!response.ok) {
            // Throw so it goes to the catch block
            throw new Error(response.statusText);
        }

        if (window.opener?.notionAuthSuccess) {
            window.opener.notionAuthSuccess();
        } else {
            router.visit(route('duplicates.connect'));
        }
    })
    .catch(error => {
        if (window.opener?.notionAuthError) {
            window.opener.notionAuthError();
        } else {
            router.visit(route('duplicates.connect', { status: 'error' }));
        }
    })
</script>

<template>
    <div class="flex flex-col items-center justify-center h-screen">
        <div class="flex items-center justify-center space-x-2">
            <span class="loading loading-spinner loading-lg"></span>
        </div>
        <p class="mt-4 text-center">Saving and taking you back to Exnota Tools</p>
    </div>
</template>
