import { ref } from 'vue'

type ToastType = 'success' | 'error' | 'info'

interface Toast {
    id: number
    message: string
    type: ToastType
}

const toasts = ref<Toast[]>([])
let id = 0

export function useToast() {
    const showToast = (message: string, type: ToastType = 'info') => {
        const toastId = id++

        toasts.value.push({
            id: toastId,
            message,
            type,
        })

        setTimeout(() => {
            toasts.value = toasts.value.filter(t => t.id !== toastId)
        }, 3000)
    }

    return {
        toasts,
        showToast,
    }
}