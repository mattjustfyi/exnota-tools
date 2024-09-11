<script setup>
import ApiTokenManager from '@/Pages/API/Partials/ApiTokenManager.vue';
import AppLayout from '@/Layouts/AppLayout.vue';

// defineProps({
//     tokens: Array,
//     availablePermissions: Array,
//     defaultPermissions: Array,
// });



// Get "code" request param
const urlParams = new URLSearchParams(window.location.search);
const code = urlParams.get('code');

// Print the code
console.log(code);

// TODO: Send code to server, which will
// - Get auth credentials
// - Create a user and workspace with them
// - Log in the user
// - Reload the parent window, close itself
// - Parent view controller passes the user with workspaces to the view
// - Parent window detects user is logged in and redirects to the first workspace
//   at /duplicates/workspace/{id}


</script>

<template>
    Taking you back to Exnota Tools...
    <br />
    code = {{ code }}
</template>

