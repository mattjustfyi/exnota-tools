<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import Banner from '@/Components/Banner.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import HelpMessage from '@/Components/HelpMessage.vue';

const props = defineProps({
    title: String,
});

const page = usePage();
const isLocal = page.props.env === 'local';

const showingNavigationDropdown = ref(false);

const switchToTeam = (team) => {
    router.put(route('current-team.update'), {
        team_id: team.id,
    }, {
        preserveState: false,
    });
};

const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <div>

        <Head :title="title" />

        <Banner />

        <div class="min-h-screen bg-background">
            <nav class="bg-secondary border-b border-gray-200">
                <!-- Primary Navigation Menu -->
                <div class="max-w-7xl mx-auto p-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-12">
                        <div class="flex items-center">
                            <!-- Logo -->
                            <div class="shrink-0 flex flex-col items-start">
                                <Link href="/" class="text-sm font-medium text-gray-600">
                                Exnota Tools
                                </Link>
                                <Link :href="route('dashboard')" class="text-xl font-bold text-primary mt-1">
                                Delete Duplicates
                                </Link>
                            </div>
                        </div>

                        <!-- Placeholder for future content -->
                        <div class="flex items-center">
                        </div>

                        <!-- Hamburger -->
                        <div v-if="!$page.props.auth.user || !isLocal" class="">
                            <a href="/">
                                <img src="/images/exnota-128.png" alt="Exnota logo" class="max-h-full" />
                            </a>
                        </div>
                        <div v-else class="">
                            <img src="/images/exnota-128.png" alt="Exnota logo" class="max-h-full"
                                @click="showingNavigationDropdown = !showingNavigationDropdown" />
                        </div>
                    </div>
                </div>

                <!-- Responsive Navigation Menu -->
                <template v-if="$page.props.auth.user">
                    <div :class="{ 'block': showingNavigationDropdown, 'hidden': !showingNavigationDropdown }"
                        class="sm:hidden">
                        <div class="pt-2 pb-3 space-y-1">
                            <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')">
                                Dashboard
                            </ResponsiveNavLink>
                        </div>

                        <!-- Responsive Settings Options -->
                        <div class="pt-4 pb-1 border-t border-gray-200">
                            <div class="flex items-center px-4">
                                <div v-if="$page.props.jetstream.managesProfilePhotos" class="shrink-0 me-3">
                                    <img class="h-10 w-10 rounded-full object-cover"
                                        :src="$page.props.auth.user.profile_photo_url"
                                        :alt="$page.props.auth.user.name">
                                </div>

                                <div>
                                    <div class="font-medium text-base text-gray-800">
                                        {{ $page.props.auth.user.name }}
                                    </div>
                                    <div class="font-medium text-sm text-gray-500">
                                        {{ $page.props.auth.user.email }}
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3 space-y-1">
                                <ResponsiveNavLink :href="route('profile.show')"
                                    :active="route().current('profile.show')">
                                    Profile
                                </ResponsiveNavLink>

                                <ResponsiveNavLink v-if="$page.props.jetstream.hasApiFeatures"
                                    :href="route('api-tokens.index')" :active="route().current('api-tokens.index')">
                                    API Tokens
                                </ResponsiveNavLink>

                                <!-- Authentication -->
                                <form method="POST" @submit.prevent="logout">
                                    <ResponsiveNavLink as="button">
                                        Log Out
                                    </ResponsiveNavLink>
                                </form>

                                <!-- Team Management -->
                                <template v-if="$page.props.jetstream.hasTeamFeatures">
                                    <div class="border-t border-gray-200" />

                                    <div class="block px-4 py-2 text-xs text-gray-400">
                                        Manage Team
                                    </div>

                                    <!-- Team Settings -->
                                    <ResponsiveNavLink :href="route('teams.show', $page.props.auth.user.current_team)"
                                        :active="route().current('teams.show')">
                                        Team Settings
                                    </ResponsiveNavLink>

                                    <ResponsiveNavLink v-if="$page.props.jetstream.canCreateTeams"
                                        :href="route('teams.create')" :active="route().current('teams.create')">
                                        Create New Team
                                    </ResponsiveNavLink>

                                    <!-- Team Switcher -->
                                    <template v-if="$page.props.auth.user.all_teams.length > 1">
                                        <div class="border-t border-gray-200" />

                                        <div class="block px-4 py-2 text-xs text-gray-400">
                                            Switch Teams
                                        </div>

                                        <template v-for="team in $page.props.auth.user.all_teams" :key="team.id">
                                            <form @submit.prevent="switchToTeam(team)">
                                                <ResponsiveNavLink as="button">
                                                    <div class="flex items-center">
                                                        <svg v-if="team.id == $page.props.auth.user.current_team_id"
                                                            class="me-2 h-5 w-5 text-green-400"
                                                            xmlns="http://www.w3.org/2000/svg" fill="none"
                                                            viewBox="0 0 24 24" stroke-width="1.5"
                                                            stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                        <div>{{ team.name }}</div>
                                                    </div>
                                                </ResponsiveNavLink>
                                            </form>
                                        </template>
                                    </template>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>
            </nav>

            <!-- Page Heading -->
            <header v-if="$slots.header" class="bg-white shadow">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main>
                <slot />

                <div class="pt-12 pb-24 px-12">
                    <HelpMessage class="mt-6 text-xs" />
                </div>
            </main>
        </div>
    </div>
</template>
