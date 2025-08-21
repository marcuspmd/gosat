<template>
    <MainLayout>
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-gray-800 mb-2">
                Clientes e Ofertas de Crédito
            </h1>
            <p class="text-gray-600">
                Visualize todos os CPFs cadastrados e suas respectivas ofertas
            </p>
        </div>

        <!-- Search Form -->
        <div class="max-w-md mx-auto mb-8">
            <div class="bg-white rounded-lg shadow-lg p-6">
                <form @submit.prevent="buscarOfertas">
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
                            :disabled="searchLoading"
                            required
                        />
                    </div>
                    <div class="flex space-x-3">
                        <button
                            type="submit"
                            :disabled="searchLoading || cpf.length < 14"
                            class="flex-1 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white font-medium py-2 px-4 rounded-md transition duration-200"
                        >
                            <span v-if="searchLoading" class="flex items-center justify-center">
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
                <div v-if="searchError" class="mt-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded">
                    {{ searchError }}
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="text-center py-8">
            <div class="inline-flex items-center">
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Carregando clientes...
            </div>
        </div>

        <!-- Error Message -->
        <div v-if="error && !loading" class="max-w-md mx-auto mb-8">
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                {{ error }}
            </div>
        </div>

        <!-- Customers Table -->
        <div v-if="!loading && customersData.length > 0" class="max-w-7xl mx-auto mb-8">
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-800">
                        Clientes Cadastrados ({{ customersData.length }})
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        Total de ofertas: {{ totalOffers }}
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    CPF
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Ofertas Disponíveis
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Última Consulta
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Ações
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="customer in customersData" :key="customer.cpf" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ formatCpfDisplay(customer.cpf) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ customer.offers_count }} {{ customer.offers_count === 1 ? 'oferta' : 'ofertas' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ formatDate(customer.offers[0]?.created_at) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button
                                        @click="abrirSimulacao(customer)"
                                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md transition duration-200"
                                    >
                                        Simular
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Simulation Modal -->
        <div v-if="showSimulationModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800">
                                Simulação de Crédito
                            </h3>
                            <p class="text-sm text-gray-600 mt-1">
                                CPF: {{ formatCpfDisplay(selectedCustomer?.cpf) }} - {{ selectedCustomer?.offers_count }} ofertas disponíveis
                            </p>
                        </div>
                        <button
                            @click="fecharSimulacao"
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

                        <form @submit.prevent="simularOferta" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Valor Desejado
                                    </label>
                                    <input
                                        v-model="simulationForm.valorDesejado"
                                        type="text"
                                        placeholder="R$ 0,00"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        @input="formatSimulationValor"
                                        :disabled="simulationLoading"
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
                                        v-model="simulationForm.parcelasDesejadas"
                                        type="number"
                                        :placeholder="currentRanges.min_installments?.toString() || '12'"
                                        :min="currentRanges.min_installments || 1"
                                        :max="currentRanges.max_installments || 120"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        :disabled="simulationLoading"
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
                                        v-model="simulationForm.modalidadeSelecionada"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        :disabled="simulationLoading"
                                        @change="updateRangesForSelectedModality"
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
                                    @click="fecharSimulacao"
                                    class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-700 rounded-md transition duration-200"
                                    :disabled="simulationLoading"
                                >
                                    Cancelar
                                </button>
                                <button
                                    type="submit"
                                    class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md transition duration-200"
                                    :disabled="simulationLoading || !simulationForm.valorDesejado || !simulationForm.parcelasDesejadas"
                                >
                                    <span v-if="simulationLoading">Simulando...</span>
                                    <span v-else>Simular</span>
                                </button>
                            </div>
                        </form>

                        <!-- Validation Error -->
                        <div v-if="searchError" class="mt-6">
                            <div class="bg-gradient-to-r from-orange-50 to-red-50 border border-orange-200 rounded-xl p-6 text-center">
                                <div class="flex justify-center mb-4">
                                    <div class="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center">
                                        <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.664-.833-2.464 0L4.35 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <h4 class="text-lg font-semibold text-gray-800 mb-2">Ops! 😔</h4>
                                <p class="text-gray-700 mb-4">{{ searchError }}</p>
                            </div>
                        </div>

                        <!-- Simulation Result -->
                        <div v-if="simulationResult" class="mt-6">
                            <h4 class="text-lg font-semibold text-gray-800 mb-4">
                                Melhores Ofertas Encontradas ({{ simulationResult.ofertas.length }})
                            </h4>

                            <div class="space-y-4">
                                <div
                                    v-for="(oferta, index) in simulationResult.ofertas"
                                    :key="index"
                                    class="border rounded-lg p-4"
                                    :class="index === 0 ? 'border-green-500 bg-green-50' : index === 1 ? 'border-blue-500 bg-blue-50' : 'border-orange-500 bg-orange-50'"
                                >
                                    <div class="flex justify-between items-start mb-3">
                                        <div>
                                            <div class="flex items-center space-x-2">
                                                <h5 class="text-lg font-semibold text-gray-900">
                                                    {{ oferta.instituicaoFinanceira }}
                                                </h5>
                                                <span
                                                    class="px-2 py-1 text-xs font-medium rounded-full"
                                                    :class="index === 0 ? 'bg-green-100 text-green-800' : index === 1 ? 'bg-blue-100 text-blue-800' : 'bg-orange-100 text-orange-800'"
                                                >
                                                    {{ index === 0 ? 'Melhor Oferta' : index === 1 ? '2ª Melhor' : '3ª Melhor' }}
                                                </span>
                                            </div>
                                            <p class="text-sm text-gray-600 mt-1">
                                                {{ capitalizeModalidade(oferta.modalidadeCredito) }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                        <div>
                                            <span class="text-gray-600">Valor Solicitado:</span>
                                            <div class="font-medium">{{ formatMoney(oferta.valorSolicitado) }}</div>
                                        </div>
                                        <div>
                                            <span class="text-gray-600">Parcela Mensal:</span>
                                            <div class="font-medium text-blue-600">{{ formatMoney(oferta.parcelaMensal) }}</div>
                                        </div>
                                        <div>
                                            <span class="text-gray-600">Total a Pagar:</span>
                                            <div class="font-medium text-green-600">{{ formatMoney(oferta.valorAPagar) }}</div>
                                        </div>
                                        <div>
                                            <span class="text-gray-600">Total Juros:</span>
                                            <div class="font-medium text-red-600">{{ formatMoney(oferta.totalJuros) }}</div>
                                        </div>
                                    </div>

                                    <div class="mt-3 pt-3 border-t space-y-3">
                                        <div class="text-sm text-gray-600">
                                            <div class="flex flex-wrap items-center justify-between">
                                                <span>Taxa: {{ (oferta.taxaJurosMensal * 100).toFixed(2) }}% a.m. ({{ (oferta.taxaJurosAnual * 100).toFixed(2) }}% a.a.)</span>
                                                <span>{{ oferta.qntParcelas }}x parcelas</span>
                                            </div>
                                        </div>

                                        <!-- Limites da Oferta -->
                                        <div v-if="oferta.limites" class="bg-gray-50 rounded-lg p-3">
                                            <h6 class="text-xs font-medium text-gray-700 mb-2">Limites disponíveis nesta modalidade:</h6>
                                            <div class="grid grid-cols-2 gap-3 text-xs text-gray-600">
                                                <div>
                                                    <span class="text-gray-500">Valor:</span>
                                                    <div class="font-medium">{{ formatMoney(oferta.limites.valorMinimo) }} - {{ formatMoney(oferta.limites.valorMaximo) }}</div>
                                                </div>
                                                <div>
                                                    <span class="text-gray-500">Parcelas:</span>
                                                    <div class="font-medium">{{ oferta.limites.parcelasMinima }} - {{ oferta.limites.parcelasMaxima }}x</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-if="simulationResult.total_ofertas_encontradas > 3" class="mt-4 p-3 bg-blue-100 rounded-lg text-sm text-blue-800">
                                <strong>Info:</strong> Foram encontradas {{ simulationResult.total_ofertas_encontradas }} ofertas.
                                Mostrando as 3 mais vantajosas para você.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- No Results -->
        <div v-else-if="!loading && customersData.length === 0" class="max-w-md mx-auto">
            <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded text-center">
                Nenhum cliente encontrado com ofertas.
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import axios from 'axios'
import MainLayout from '@/Layouts/MainLayout.vue'

const customersData = ref([])
const loading = ref(false)
const error = ref('')

const cpf = ref('')
const searchLoading = ref(false)
const searchError = ref('')

// Simulation modal state
const showSimulationModal = ref(false)
const selectedCustomer = ref(null)
const simulationLoading = ref(false)
const simulationResult = ref(null)
const simulationForm = ref({
    valorDesejado: '',
    parcelasDesejadas: '',
    modalidadeSelecionada: ''
})

const totalOffers = computed(() => {
    return customersData.value.reduce((total, customer) => total + customer.offers_count, 0)
})

const availableModalities = computed(() => {
    if (!selectedCustomer.value) return []
    
    const modalities = selectedCustomer.value.offers.map(offer => offer.modality_name)
    return [...new Set(modalities)].sort()
})

const currentRanges = computed(() => {
    if (!selectedCustomer.value) return {}
    
    const selectedModality = simulationForm.value.modalidadeSelecionada
    
    // Se nenhuma modalidade específica foi selecionada, usar os ranges gerais
    if (!selectedModality) {
        return selectedCustomer.value.available_ranges || {}
    }
    
    // Filtrar ofertas pela modalidade selecionada
    const modalityOffers = selectedCustomer.value.offers.filter(
        offer => offer.modality_name === selectedModality
    )
    
    if (modalityOffers.length === 0) return {}
    
    // Calcular ranges específicos para a modalidade
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

const loadCustomersWithOffers = async () => {
    loading.value = true
    error.value = ''

    try {
        const response = await axios.get('/api/v1/credit/customers-with-offers')

        if (response.data.status === 'success') {
            // Sort customers by the most recent offer date
            const sortedData = response.data.data.map(customer => {
                // Sort offers within each customer by creation date (most recent first)
                customer.offers.sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
                return customer
            })

            // Sort customers by their most recent offer
            sortedData.sort((a, b) => {
                const aLatest = new Date(a.offers[0]?.created_at || 0)
                const bLatest = new Date(b.offers[0]?.created_at || 0)
                return bLatest - aLatest
            })

            customersData.value = sortedData
        } else {
            error.value = 'Erro ao carregar dados dos clientes'
        }

    } catch (err) {
        console.error('Erro ao carregar clientes:', err)
        error.value = err.response?.data?.message || 'Erro ao carregar dados dos clientes'
    } finally {
        loading.value = false
    }
}

const formatCpfInput = (event) => {
    let value = event.target.value.replace(/\D/g, '')

    if (value.length <= 11) {
        value = value.replace(/(\d{3})(\d)/, '$1.$2')
        value = value.replace(/(\d{3})(\d)/, '$1.$2')
        value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2')
    }

    cpf.value = value
}

const buscarOfertas = async () => {
    searchError.value = ''
    searchLoading.value = true

    try {
        const cpfNumbers = cpf.value.replace(/\D/g, '')

        // Make request to trigger credit search
        const response = await axios.post('/api/v1/credit', {
            cpf: cpfNumbers
        })

        if (response.data.status === 'processing') {
            searchError.value = ''
            cpf.value = '' // Clear form

            // Reload all customers to show updated data
            setTimeout(() => {
                loadCustomersWithOffers()
            }, 2000) // Wait 2 seconds then refresh
        }

    } catch (err) {
        console.error('Erro na busca:', err)
        searchError.value = err.response?.data?.message || 'Erro ao buscar ofertas. Tente novamente.'
    } finally {
        searchLoading.value = false
    }
}


// Simulation functions
const abrirSimulacao = (customer) => {
    selectedCustomer.value = customer
    simulationResult.value = null
    simulationForm.value.valorDesejado = ''
    simulationForm.value.parcelasDesejadas = ''
    simulationForm.value.modalidadeSelecionada = ''
    showSimulationModal.value = true
}

const updateRangesForSelectedModality = () => {
    // Não resetar os valores, apenas recalcular ranges
}

const fecharSimulacao = () => {
    showSimulationModal.value = false
    selectedCustomer.value = null
    simulationResult.value = null
    searchError.value = ''
}

const formatSimulationValor = (event) => {
    let value = event.target.value.replace(/\D/g, '')

    if (value.length > 0) {
        value = (parseInt(value) / 100).toLocaleString('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        })
    }

    simulationForm.value.valorDesejado = value
}


const simularOferta = async () => {
    if (!selectedCustomer.value) return

    // Validar valores antes de enviar
    const valorNumbers = parseInt(simulationForm.value.valorDesejado.replace(/\D/g, ''))
    const parcelasDesejadas = parseInt(simulationForm.value.parcelasDesejadas)
    const ranges = currentRanges.value

    // Validação de valor
    if (valorNumbers < ranges.min_amount_cents || valorNumbers > ranges.max_amount_cents) {
        searchError.value = `Valor deve estar entre ${formatMoney(ranges.min_amount_cents)} e ${formatMoney(ranges.max_amount_cents)}`
        return
    }

    // Validação de parcelas
    if (parcelasDesejadas < ranges.min_installments || parcelasDesejadas > ranges.max_installments) {
        searchError.value = `Número de parcelas deve estar entre ${ranges.min_installments} e ${ranges.max_installments}`
        return
    }

    simulationLoading.value = true
    simulationResult.value = null
    searchError.value = ''

    try {
        const cpfNumbers = selectedCustomer.value.cpf.toString()

        const requestData = {
            cpf: cpfNumbers,
            valor_desejado: valorNumbers, // em centavos
            quantidade_parcelas: parcelasDesejadas
        }

        // Adicionar modalidade se uma específica foi selecionada
        if (simulationForm.value.modalidadeSelecionada) {
            requestData.modalidade = simulationForm.value.modalidadeSelecionada
        }

        const response = await axios.post('/api/v1/credit/simulate', requestData)

        if (response.data.status === 'success') {
            let result = response.data
            
            // Se uma modalidade específica foi selecionada, filtrar os resultados
            if (simulationForm.value.modalidadeSelecionada) {
                result.ofertas = result.ofertas.filter(
                    oferta => oferta.modalidadeCredito === simulationForm.value.modalidadeSelecionada
                )
                result.total_ofertas_encontradas = result.ofertas.length
            }
            
            simulationResult.value = result
        } else {
            searchError.value = response.data.error || 'Erro na simulação'
        }

    } catch (err) {
        console.error('Erro na simulação:', err)
        if (err.response?.status === 404) {
            searchError.value = 'Nenhuma oferta encontrada para os dados solicitados.'
        } else {
            searchError.value = err.response?.data?.message || 'Erro ao simular oferta'
        }
    } finally {
        simulationLoading.value = false
    }
}

const formatDate = (dateString) => {
    if (!dateString) return ''
    const date = new Date(dateString)
    return date.toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

// Listen for SSE events from MainLayout
const handleCreditConsultationCompleted = () => {
    // Reload customers when a new consultation is completed
    setTimeout(() => {
        loadCustomersWithOffers()
    }, 1000)
}

onMounted(() => {
    loadCustomersWithOffers()

    // Listen for credit consultation completion events
    window.addEventListener('credit-consultation-completed', handleCreditConsultationCompleted)
})

onUnmounted(() => {
    window.removeEventListener('credit-consultation-completed', handleCreditConsultationCompleted)
})

const formatCpfDisplay = (cpf) => {
    if (!cpf) return ''
    // Convert to string if it's a number
    const cpfString = cpf.toString()
    // Pad with zeros if needed to ensure 11 digits
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
</script>
