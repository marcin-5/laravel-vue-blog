<script generic="TItem extends { id: number }" lang="ts" setup>
import { shallowRef } from 'vue';

interface Props {
    items: TItem[];
    canCreate: boolean;
}

interface ManagementActions {
    isOpen: boolean;
    open: () => void;
    close: () => void;
    toggle: () => void;
    requestCreate: () => void;
}

const props = defineProps<Props>();

defineSlots<{
    create: (props: ManagementActions) => unknown;
    list: (props: ManagementActions & { items: TItem[]; canCreate: boolean }) => unknown;
}>();

const isOpen = shallowRef(false);

function open(): void {
    if (!props.canCreate) {
        return;
    }

    isOpen.value = true;
}

function close(): void {
    isOpen.value = false;
}

function toggle(): void {
    if (isOpen.value) {
        close();
        return;
    }

    open();
}

function requestCreate(): void {
    open();
}

function getActions(): ManagementActions {
    return {
        isOpen: isOpen.value,
        open,
        close,
        toggle,
        requestCreate,
    };
}
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
        <slot name="create" v-bind="getActions()" />
        <slot name="list" v-bind="{ ...getActions(), items: props.items, canCreate: props.canCreate }" />
    </div>
</template>
