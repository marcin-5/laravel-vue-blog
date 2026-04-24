export const SINGLE_ANSWER_AUTO_CONFIRM_DELAY_MS = 200;
export const TYPE_IDS = ['1', '2', '3', '4', '5', '6', '7', '8', '9'] as const;
export type EnneagramType = (typeof TYPE_IDS)[number];
