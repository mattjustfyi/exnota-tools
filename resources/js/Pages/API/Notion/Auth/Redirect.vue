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
        // const data = await response.text();
        const json = await response.json();
        return json;
    })
    .finally(() => {
        // console.log(data);
        // window.location.href = `/duplicates/workspace/${data.workspace_id}`;
        window.opener.location.reload(); // Reload parent window
        window.close(); // Close current window
    })
// .then(data => {
//     console.log(data);
//     // window.location.href = `/duplicates/workspace/${data.workspace_id}`;
//     window.opener.location.reload(); // Reload parent window
//     window.close(); // Close current window

//     // if (data && data.workspace_id) {
//     //     // Redirect to the workspace page
//     //     window.location.href = `/duplicates/workspace/${data.workspace_id}`;
//     // } else {
//     //     console.error('Workspace ID not found in the response');
// })
// .catch(error => {
//     // Handle any errors
//     console.error('Error:', error);
// });

</script>

<template>
    Taking you back to Exnota Tools...
    <br />
    code = {{ code }}
</template>
