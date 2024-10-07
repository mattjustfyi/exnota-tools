<script setup>
import ApiTokenManager from '@/Pages/API/Partials/ApiTokenManager.vue';
import AppLayout from '@/Layouts/AppLayout.vue';


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
        console.log(response);
        const json = await response.json();
        return json;
    })
    .finally(() => {
        window.opener.location.reload(); // Reload parent window
        window.close(); // Close current window
    })
</script>

<template>
    <div class="flex flex-col items-center justify-center h-screen">
        <div class="flex items-center justify-center space-x-2">
            <span class="loading loading-spinner loading-lg"></span>
        </div>
        <p class="mt-4 text-center">Taking you back to Exnota Tools</p>
    </div>
</template>
