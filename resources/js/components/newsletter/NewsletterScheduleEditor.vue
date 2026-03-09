<script lang="ts" setup>
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import type { NewsletterSubscription } from '@/types/newsletter.types';

const props = defineProps<{
    modelValue: NewsletterSubscription;
    // Using any for translations to avoid over-constraining the expected shape
    t: any;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: NewsletterSubscription): void;
}>();

const onChangeSendTime = (value: string) => {
    emit('update:modelValue', { ...props.modelValue, send_time: value });
};

const onChangeSendTimeWeekend = (value: string) => {
    emit('update:modelValue', { ...props.modelValue, send_time_weekend: value });
};

const onChangeSendDay = (value: any) => {
    emit('update:modelValue', { ...props.modelValue, send_day: value ? Number(value) : null });
};
</script>

<template>
    <div class="flex flex-col gap-2">
        <!-- Daily schedule -->
        <template v-if="props.modelValue.frequency === 'daily'">
            <div class="flex items-center gap-2">
                <span class="w-24 text-xs text-secondary-foreground">{{ props.t.form.weekday }}:</span>
                <input
                    :value="props.modelValue.send_time ?? ''"
                    class="w-20 rounded-md border border-input bg-background px-2 py-1 text-sm ring-offset-background focus:ring-2 focus:ring-ring focus:ring-offset-2 focus:outline-none"
                    type="time"
                    @input="onChangeSendTime(($event.target as HTMLInputElement).value)"
                />
            </div>
            <div class="flex items-center gap-2">
                <span class="w-24 text-xs text-secondary-foreground">{{ props.t.form.weekend }}:</span>
                <input
                    :value="props.modelValue.send_time_weekend ?? ''"
                    class="w-20 rounded-md border border-input bg-background px-2 py-1 text-sm ring-offset-background focus:ring-2 focus:ring-ring focus:ring-offset-2 focus:outline-none"
                    type="time"
                    @input="onChangeSendTimeWeekend(($event.target as HTMLInputElement).value)"
                />
            </div>
        </template>

        <!-- Weekly schedule -->
        <div v-else-if="props.modelValue.frequency === 'weekly'" class="flex items-center gap-2">
            <input
                :value="props.modelValue.send_time ?? ''"
                class="w-20 rounded-md border border-input bg-background px-2 py-1 text-sm ring-offset-background focus:ring-2 focus:ring-ring focus:ring-offset-2 focus:outline-none"
                type="time"
                @input="onChangeSendTime(($event.target as HTMLInputElement).value)"
            />
            <Select :model-value="props.modelValue.send_day?.toString()" @update:model-value="onChangeSendDay">
                <SelectTrigger class="h-8 w-30">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="1">{{ props.t.form.monday }}</SelectItem>
                    <SelectItem value="2">{{ props.t.form.tuesday }}</SelectItem>
                    <SelectItem value="3">{{ props.t.form.wednesday }}</SelectItem>
                    <SelectItem value="4">{{ props.t.form.thursday }}</SelectItem>
                    <SelectItem value="5">{{ props.t.form.friday }}</SelectItem>
                    <SelectItem value="6">{{ props.t.form.saturday }}</SelectItem>
                    <SelectItem value="7">{{ props.t.form.sunday }}</SelectItem>
                </SelectContent>
            </Select>
        </div>
    </div>
</template>
