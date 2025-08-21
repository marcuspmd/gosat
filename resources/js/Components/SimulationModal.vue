<template>
    <div v-if="show" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">
                            Simulação de Crédito
                        </h3>
                        <p class="text-sm text-gray-600 mt-1">
                            CPF: {{ formatCpfDisplay(customer?.cpf) }} - {{ customer?.offers_count }} ofertas disponíveis
                        </p>
                    </div>
                    <button
                        @click="$emit('close')"
                        class="text-gray-400 hover:text-gray-600"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="p-6 overflow-y-auto max-h-[calc(90vh-120px)]">
                <!-- Simulation Form -->
                <div>
                    <h4 class="text-md font-medium text-gray-800 mb-4">Dados para Simulação:</h4>

                    <form @submit.prevent="handleSubmit" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Valor Desejado
                                </label>
                                <input
                                    v-model="form.valorDesejado"
                                    type="text"
                                    placeholder="R$ 0,00"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    @input="formatValor"
                                    :disabled="loading"
                                    required
                                />
                                <p class="text-xs text-gray-500 mt-1">
                                    Disponível: {{ formatMoney(currentRanges.min_amount_cents || 0) }} - {{ formatMoney(currentRanges.max_amount_cents || 0) }}
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Número de Parcelas
                                </label>
                                <input
                                    v-model="form.parcelasDesejadas"
                                    type="number"
                                    :placeholder="currentRanges.min_installments?.toString() || '12'"
                                    :min="currentRanges.min_installments || 1"
                                    :max="currentRanges.max_installments || 120"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    :disabled="loading"
                                    required
                                />
                                <p class="text-xs text-gray-500 mt-1">
                                    Disponível: {{ currentRanges.min_installments || 1 }} - {{ currentRanges.max_installments || 120 }} parcelas
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Modalidade
                                </label>
                                <select
                                    v-model="form.modalidadeSelecionada"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    :disabled="loading"
                                    @change="$emit('modalityChanged')"
                                >
                                    <option value="">Todas as modalidades</option>
                                    <option 
                                        v-for="modalidade in availableModalities" 
                                        :key="modalidade" 
                                        :value="modalidade"
                                    >
                                        {{ capitalizeModalidade(modalidade) }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="flex justify-end space-x-3 pt-4">
                            <button
                                type="button"
                                @click="$emit('close')"
                                class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-700 rounded-md transition duration-200"
                                :disabled="loading"
                            >
                                Cancelar
                            </button>
                            <button
                                type="submit"
                                class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md transition duration-200"
                                :disabled="loading || !form.valorDesejado || !form.parcelasDesejadas"
                            >
                                <span v-if="loading">Simulando...</span>
                                <span v-else>Simular</span>
                            </button>
                        </div>
                    </form>

                    <!-- Error Message -->
                    <div v-if="error" class="mt-6">
                        <div class="bg-gradient-to-r from-orange-50 to-red-50 border border-orange-200 rounded-xl p-6 text-center">
                            <div class="flex justify-center mb-4">
                                <div class="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center">
                                    <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.664-.833-2.464 0L4.35 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                    </svg>
                                </div>
                            </div>
                            <h4 class="text-lg font-semibold text-gray-800 mb-2">Ops! 😔</h4>
                            <p class="text-gray-700 mb-4">{{ error }}</p>
                        </div>
                    </div>

                    <!-- Simulation Result -->
                    <SimulationResult v-if="result" :result="result" />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import SimulationResult from './SimulationResult.vue'

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    customer: {
        type: Object,
        default: null
    },
    loading: {
        type: Boolean,
        default: false
    },
    error: {
        type: String,
        default: ''
    },
    result: {
        type: Object,
        default: null
    }
})

const emit = defineEmits(['close', 'submit', 'modalityChanged'])

const form = ref({
    valorDesejado: '',
    parcelasDesejadas: '',
    modalidadeSelecionada: ''
})

const availableModalities = computed(() => {
    if (!props.customer) return []
    
    const modalities = props.customer.offers.map(offer => offer.modality_name)
    return [...new Set(modalities)].sort()
})

const currentRanges = computed(() => {
    if (!props.customer) return {}
    
    const selectedModality = form.value.modalidadeSelecionada
    
    if (!selectedModality) {
        return props.customer.available_ranges || {}
    }
    
    const modalityOffers = props.customer.offers.filter(
        offer => offer.modality_name === selectedModality
    )
    
    if (modalityOffers.length === 0) return {}
    
    const minAmount = Math.min(...modalityOffers.map(offer => offer.min_amount_cents))
    const maxAmount = Math.max(...modalityOffers.map(offer => offer.max_amount_cents))
    const minInstallments = Math.min(...modalityOffers.map(offer => offer.min_installments))
    const maxInstallments = Math.max(...modalityOffers.map(offer => offer.max_installments))
    
    return {
        min_amount_cents: minAmount,
        max_amount_cents: maxAmount,
        min_installments: minInstallments,
        max_installments: maxInstallments
    }
})

const formatValor = (event) => {
    let value = event.target.value.replace(/\D/g, '')

    if (value.length > 0) {
        value = (parseInt(value) / 100).toLocaleString('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        })
    }

    form.value.valorDesejado = value
}

const handleSubmit = () => {
    emit('submit', {
        valorDesejado: form.value.valorDesejado,
        parcelasDesejadas: form.value.parcelasDesejadas,
        modalidadeSelecionada: form.value.modalidadeSelecionada
    })
}

const formatCpfDisplay = (cpf) => {
    if (!cpf) return ''
    const cpfString = cpf.toString()
    const paddedCpf = cpfString.padStart(11, '0')
    return paddedCpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4')
}

const formatMoney = (centavos) => {
    return (centavos / 100).toLocaleString('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    })
}

const capitalizeModalidade = (modalidade) => {
    if (!modalidade) return ''
    return modalidade.split(' ').map(word => 
        word.charAt(0).toUpperCase() + word.slice(1).toLowerCase()
    ).join(' ')
}

// Reset form when modal opens
watch(() => props.show, (newShow) => {
    if (newShow) {
        form.value.valorDesejado = ''
        form.value.parcelasDesejadas = ''
        form.value.modalidadeSelecionada = ''
    }
})
</script>