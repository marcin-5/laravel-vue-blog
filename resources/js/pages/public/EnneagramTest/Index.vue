<script lang="ts" setup>
import { computed, shallowRef } from 'vue';
import { Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import StartScreen from './components/StartScreen.vue';
import TestToolbar from './components/TestToolbar.vue';
import TestSessionView from './components/TestSessionView.vue';
import ApiErrorState from './components/ApiErrorState.vue';
import Summary from './components/Summary.vue';
import en from './locales/en.json';
import pl from './locales/pl.json';
import '@/../css/enneagram-test.css';
import { useEnneagramPreferences } from './composables/useEnneagramPreferences';
import { useEnneagramTestSession } from './composables/useEnneagramTestSession';
import type { EnneagramPageProps, SelectedAnswer, TestAction } from './types';

const props = defineProps<EnneagramPageProps>();

const { t, locale, mergeLocaleMessage } = useI18n();
mergeLocaleMessage('en', en);
mergeLocaleMessage('pl', pl);
locale.value = props.initialLocale;

const autoConfirmSingle = shallowRef(props.autoConfirmSingleDefault);
const preferences = useEnneagramPreferences(props.initialLocale);
const session = useEnneagramTestSession();
const state = computed(() => session.state.value);
const processing = session.processing;
const hasSession = session.hasSession;
const error = session.error;
const theme = preferences.theme;
const localeLabel = preferences.localeLabel;
const result = computed(() => session.state.value?.result ?? null);

function handleAction(action: TestAction, answers: SelectedAnswer[] = []): void {
    void session.apply(action, answers);
}

function handleReset(): void {
    if (session.hasSession.value) {
        void session.reset();
    } else {
        session.clearError();
    }
}

function startTest(extended: boolean): void {
    void session.start(extended);
}
</script>

<template>
    <div :class="['enneagram-test-container p-2 md:p-3 lg:p-6', theme]" :aria-busy="processing">
        <Head :title="props.seo.title">
            <meta head-key="description" name="description" :content="props.seo.description" />
        </Head>

        <TestToolbar
            :has-session="hasSession"
            :locale-label="localeLabel"
            :processing="processing"
            :theme="theme"
            @reset="handleReset"
            @theme="preferences.setTheme"
        />

        <h1 class="mb-6 text-center font-recursive text-3xl font-bold">{{ t('title') }}</h1>

        <ApiErrorState
            v-if="error"
            :message="error.message"
            :status="error.status"
            @reset="handleReset"
            @retry="session.retry"
        />

        <StartScreen
            v-if="!state"
            :auto-confirm-single="autoConfirmSingle"
            :processing="processing"
            @start="startTest"
            @update:auto-confirm-single="autoConfirmSingle = $event"
        />

        <TestSessionView
            v-else-if="state.status === 'in_progress'"
            :auto-confirm-single="autoConfirmSingle"
            :processing="processing"
            :state="state"
            @action="handleAction"
        />

        <Summary
            v-else-if="result"
            :debug="props.appDebug"
            :stage1-results="result.stage1"
            :stage2-results="result.stage2"
            @reset="handleReset"
        />
    </div>
</template>
