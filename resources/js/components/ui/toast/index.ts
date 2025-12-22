import { cva, type VariantProps } from 'class-variance-authority';

export { default as Toast } from '@/components/ui/toast/Toast.vue'
export { default as ToastAction } from '@/components/ui/toast/ToastAction.vue';
export { default as ToastClose } from '@/components/ui/toast/ToastClose.vue';
export { default as ToastDescription } from '@/components/ui/toast/ToastDescription.vue';
export { default as ToastProvider } from '@/components/ui/toast/ToastProvider.vue';
export { default as ToastTitle } from '@/components/ui/toast/ToastTitle.vue';
export { default as ToastViewport } from '@/components/ui/toast/ToastViewport.vue';
export { default as Toaster } from './Toaster.vue'

export const toastVariants = cva(
  'group pointer-events-auto relative flex w-full items-center justify-between space-x-4 overflow-hidden rounded-md border shadow-lg transition-all data-[transitioning]:transition-none data-[state=open]:animate-in data-[state=closed]:animate-out data-[swipe=end]:animate-out data-[state=closed]:fade-out-80 data-[state=closed]:slide-out-to-right-full data-[state=open]:slide-in-from-top-full data-[state=open]:sm:slide-in-from-bottom-full',
  {
    variants: {
      variant: {
        default: 'border bg-background text-foreground',
        destructive:
          'destructive group border-destructive bg-destructive text-destructive-foreground',
        success: 'success group border-constructive bg-constructive text-constructive-foreground',
      },
      size: {
        default: 'p-6 pr-8',
        sm: 'p-4 pr-6',
        lg: 'p-8 pr-10',
      },
    },
    defaultVariants: {
      variant: 'default',
      size: 'default',
    },
  },
)

export type ToastVariants = VariantProps<typeof toastVariants>
