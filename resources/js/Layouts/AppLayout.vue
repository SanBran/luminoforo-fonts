<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import NavBar from '@/Components/NavBar.vue';

</script>

<template>

    <NavBar/>
            <main>
                <slot />
            </main>

</template>
