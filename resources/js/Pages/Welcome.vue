<script setup>
import { Head, Link } from '@inertiajs/vue3';

</script>

<template>

    <Head title="Welcome" />
    <div class="max-w-screen-md mx-auto mt-16">
        <h1 class="text-4xl font-bold text-center">Exnota Tools</h1>

        <div class="card card-side bg-base-100 mt-16 shadow-xl">
            <figure class="max-w-xs">
                <img src="/images/duplicates/search-results-sample2.png" />
            </figure>
            <div class="card-body">
                <h2 class="card-title">Delete Duplicates</h2>
                <p>Delete database entries with identical titles.</p>
                <div class="card-actions justify-end">
                    <a class="btn btn-primary" :href="route('duplicates')">Delete Duplicates</a>
                </div>
            </div>
        </div>

        <div class="divider my-16"></div>

        <div class="card bg-base-100 w-96 shadow-xl mx-auto">
            <div class="card-body">
                <h2 class="card-title text-center mx-auto">More coming soon</h2>
            </div>
        </div>
    </div>
</template>
