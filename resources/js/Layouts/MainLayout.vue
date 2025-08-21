<template>
    <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100">
        <!-- Header -->
        <header class="bg-white shadow-sm border-b border-gray-200">
            <div class="container mx-auto px-4 py-4">
                <div class="flex items-center justify-between">
                    <h1 class="text-2xl font-bold text-gray-800">
                        Sistema de Crédito
                    </h1>

                    <!-- Notifications Area -->
                    <div class="flex items-center space-x-4">
                        <div v-if="connectionStatus" class="flex items-center text-sm">
                            <div class="w-2 h-2 rounded-full mr-2"
                                 :class="connectionStatus === 'connected' ? 'bg-green-500' : 'bg-red-500'">
                            </div>
                            <span class="text-gray-600">
                                {{ connectionStatus === 'connected' ? 'Online' : 'Desconectado' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="container mx-auto px-4 py-8">
            <slot />
        </main>

        <!-- Toast Notifications -->
        <div class="fixed top-4 right-4 z-50 space-y-2">
            <div
                v-for="notification in notifications"
                :key="notification.id"
                class="max-w-sm bg-white border border-gray-200 rounded-lg shadow-lg p-4 transform transition-all duration-300"
                :class="getNotificationClass(notification.type)"
            >
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <div class="w-5 h-5 rounded-full flex items-center justify-center"
                             :class="getIconClass(notification.type)">
                            <svg v-if="notification.type === 'success'" class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            <svg v-else-if="notification.type === 'error'" class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                            <svg v-else class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>
                    <div class="ml-3 flex-1">
                        <h4 class="text-sm font-medium text-gray-900">
                            {{ notification.title }}
                        </h4>
                        <p class="text-sm text-gray-600 mt-1">
                            {{ notification.message }}
                        </p>
                        <div v-if="notification.data" class="text-xs text-gray-500 mt-2">
                            <span v-if="notification.data.valor">Valor: {{ notification.data.valor }}</span>
                            <span v-if="notification.data.cpf" class="ml-2">CPF: {{ notification.data.cpf }}</span>
                        </div>
                    </div>
                    <button
                        @click="removeNotification(notification.id)"
                        class="ml-3 text-gray-400 hover:text-gray-600"
                    >
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'

const notifications = ref([])
const connectionStatus = ref('disconnected')
let eventSource = null
let notificationId = 0

const addNotification = (type, title, message, data = null) => {
    const id = ++notificationId
    const notification = { id, type, title, message, data }

    notifications.value.push(notification)

    // Auto remove after 5 seconds for success, 8 seconds for others
    const timeout = type === 'success' ? 5000 : 8000
    setTimeout(() => {
        removeNotification(id)
    }, timeout)

    return id
}

const removeNotification = (id) => {
    const index = notifications.value.findIndex(n => n.id === id)
    if (index > -1) {
        notifications.value.splice(index, 1)
    }
}

const getNotificationClass = (type) => {
    switch (type) {
        case 'success': return 'border-green-200'
        case 'error': return 'border-red-200'
        case 'info': return 'border-blue-200'
        default: return 'border-gray-200'
    }
}

const getIconClass = (type) => {
    switch (type) {
        case 'success': return 'bg-green-500'
        case 'error': return 'bg-red-500'
        case 'info': return 'bg-blue-500'
        default: return 'bg-gray-500'
    }
}

const connectSSE = () => {
    if (eventSource) {
        eventSource.close()
    }

    // Generate or reuse a client ID for this session
    let clientId = sessionStorage.getItem('sse_client_id')
    if (!clientId) {
        clientId = 'client_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9)
        sessionStorage.setItem('sse_client_id', clientId)
    }

    eventSource = new EventSource(`/api/sse/notifications?client_id=${clientId}`)

    eventSource.onopen = () => {
        connectionStatus.value = 'connected'
        console.log('SSE connection established')
    }

    eventSource.onerror = (error) => {
        connectionStatus.value = 'disconnected'
        console.error('SSE connection error:', error)

        // Reconnect after 5 seconds
        setTimeout(() => {
            if (connectionStatus.value === 'disconnected') {
                connectSSE()
            }
        }, 5000)
    }

    eventSource.addEventListener('job.started', (event) => {
        const data = JSON.parse(event.data)
        // Only show notifications for recent events (last 2 minutes)
        const eventTime = new Date(data.timestamp)
        const now = new Date()
        const timeDiff = (now - eventTime) / 1000 / 60 // minutes
        
        if (timeDiff <= 2) {
            addNotification('info', 'Consulta Iniciada',
                'Buscando ofertas de crédito...', data)
        }
    })

    eventSource.addEventListener('job.completed', (event) => {
        const data = JSON.parse(event.data)
        const ofertasCount = data.ofertas?.length || 0
        
        // Only show notifications for recent events (last 2 minutes)
        const eventTime = new Date(data.timestamp)
        const now = new Date()
        const timeDiff = (now - eventTime) / 1000 / 60 // minutes
        
        if (timeDiff <= 2) {
            addNotification('success', 'Consulta Finalizada',
                `${ofertasCount} ofertas encontradas para o valor solicitado`, data)
        }
        
        // Always emit the custom event for page updates
        window.dispatchEvent(new CustomEvent('credit-consultation-completed', {
            detail: { ofertas: data.ofertas || [], data }
        }))
    })

    eventSource.addEventListener('job.failed', (event) => {
        const data = JSON.parse(event.data)
        
        // Only show notifications for recent events (last 2 minutes)
        const eventTime = new Date(data.timestamp)
        const now = new Date()
        const timeDiff = (now - eventTime) / 1000 / 60 // minutes
        
        if (timeDiff <= 2) {
            addNotification('error', 'Erro na Consulta',
                data.error || 'Erro ao buscar ofertas de crédito', data)
        }
        
        // Always emit the custom event for error handling
        window.dispatchEvent(new CustomEvent('credit-consultation-failed', {
            detail: { error: data.error, data }
        }))
    })
}

onMounted(() => {
    connectSSE()
})

onUnmounted(() => {
    if (eventSource) {
        eventSource.close()
    }
})

defineExpose({
    addNotification,
    removeNotification
})
</script>
