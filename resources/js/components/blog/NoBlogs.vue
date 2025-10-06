<script lang="ts" setup>
const headingClasses = 'text-amber-800 dark:text-amber-50';
const subheadingClasses = 'text-orange-800 dark:text-orange-50';
const bodyTextClasses = 'text-orange-900 dark:text-orange-100';
const footerTextClasses = 'text-orange-950 dark:text-amber-100';
</script>

<template>
    <div class="mt-20 flex flex-col items-center justify-center gap-4 text-center">
        <h1 :class="headingClasses" class="text-xl font-semibold">Witaj</h1>
        <h2 :class="subheadingClasses" class="mb-8 text-lg">Miło, że tu jesteś</h2>

        <p :class="bodyTextClasses" class="text-base">Jeszcze nie ma na tej stronie żadnego bloga.</p>
        <p :class="bodyTextClasses" class="text-base">Wkrótce to się zmieni i znajdziesz tu ciekawe wpisy.</p>

        <p :class="footerTextClasses" class="mt-8">Zapraszam wkrótce</p>
    </div>
</template>

<style scoped></style>
