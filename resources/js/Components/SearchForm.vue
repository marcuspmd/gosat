<template>
    <div class="max-w-md mx-auto mb-8">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <form @submit.prevent="$emit('search', cpf)">
                <div class="mb-4">
                    <label for="cpf" class="block text-sm font-medium text-gray-700 mb-2">
                        Buscar Ofertas por CPF
                    </label>
                    <input
                        id="cpf"
                        v-model="cpf"
                        type="text"
                        placeholder="000.000.000-00"
                        maxlength="14"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        @input="formatCpfInput"
                        :disabled="loading"
                        required
                    />
                </div>
                <div class="flex space-x-3">
                    <button
                        type="submit"
                        :disabled="loading || cpf.length < 14"
                        class="flex-1 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white font-medium py-2 px-4 rounded-md transition duration-200"
                    >
                        <span v-if="loading" class="flex items-center justify-center">
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Buscando...
                        </span>
                        <span v-else>
                            Buscar Oferta para o CPF
                        </span>
                    </button>
                </div>
            </form>

            <!-- Error Message -->
            <div v-if="error" class="mt-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded">
                {{ error }}
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
    loading: {
        type: Boolean,
        default: false
    },
    error: {
        type: String,
        default: ''
    }
})

const emit = defineEmits(['search'])

const cpf = ref('')

const formatCpfInput = (event) => {
    let value = event.target.value.replace(/\D/g, '')

    if (value.length <= 11) {
        value = value.replace(/(\d{3})(\d)/, '$1.$2')
        value = value.replace(/(\d{3})(\d)/, '$1.$2')
        value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2')
    }

    cpf.value = value
}

// Clear CPF when search completes successfully
watch(() => props.loading, (newLoading, oldLoading) => {
    if (oldLoading && !newLoading && !props.error) {
        cpf.value = ''
    }
})
</script>