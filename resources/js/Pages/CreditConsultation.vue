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

        <!-- Search Form Component -->
        <SearchForm
            :loading="searchLoading"
            :error="searchError"
            @search="buscarOfertas"
        />

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

        <!-- Customers Table Component -->
        <CustomersTable
            v-if="!loading && customersData.length > 0"
            :customers="customersData"
            @simulate="abrirSimulacao"
        />

        <!-- Simulation Modal Component -->
        <SimulationModal
            :show="showSimulationModal"
            :customer="selectedCustomer"
            :loading="simulationLoading"
            :error="searchError"
            :result="simulationResult"
            @close="fecharSimulacao"
            @submit="simularOferta"
            @modalityChanged="updateRangesForSelectedModality"
        />

        <!-- No Results -->
        <div v-if="!loading && customersData.length === 0" class="max-w-md mx-auto">
            <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded text-center">
                Nenhum cliente encontrado com ofertas.
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import axios from 'axios'
import MainLayout from '@/Layouts/MainLayout.vue'
import SearchForm from '@/Components/SearchForm.vue'
import CustomersTable from '@/Components/CustomersTable.vue'
import SimulationModal from '@/Components/SimulationModal.vue'

const customersData = ref([])
const loading = ref(false)
const error = ref('')

const searchLoading = ref(false)
const searchError = ref('')

// Simulation modal state
const showSimulationModal = ref(false)
const selectedCustomer = ref(null)
const simulationLoading = ref(false)
const simulationResult = ref(null)

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

const buscarOfertas = async (cpfInput) => {
    searchError.value = ''
    searchLoading.value = true

    try {
        const cpfNumbers = cpfInput.replace(/\D/g, '')

        // Make request to trigger credit search
        const response = await axios.post('/api/v1/credit', {
            cpf: cpfNumbers
        })

        if (response.data.status === 'processing') {
            searchError.value = ''
            // Don't use setTimeout anymore - rely on SSE events for refresh
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
    searchError.value = ''
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

const simularOferta = async (formData) => {
    if (!selectedCustomer.value) return

    // Validar valores antes de enviar
    const valorNumbers = parseInt(formData.valorDesejado.replace(/\D/g, ''))
    const parcelasDesejadas = parseInt(formData.parcelasDesejadas)

    // Calculate ranges for validation
    const selectedModality = formData.modalidadeSelecionada
    let ranges = {}

    if (!selectedModality) {
        ranges = selectedCustomer.value.available_ranges || {}
    } else {
        const modalityOffers = selectedCustomer.value.offers.filter(
            offer => offer.modality_name === selectedModality
        )
        
        if (modalityOffers.length > 0) {
            const minAmount = Math.min(...modalityOffers.map(offer => offer.min_amount_cents))
            const maxAmount = Math.max(...modalityOffers.map(offer => offer.max_amount_cents))
            const minInstallments = Math.min(...modalityOffers.map(offer => offer.min_installments))
            const maxInstallments = Math.max(...modalityOffers.map(offer => offer.max_installments))
            
            ranges = {
                min_amount_cents: minAmount,
                max_amount_cents: maxAmount,
                min_installments: minInstallments,
                max_installments: maxInstallments
            }
        }
    }

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
        if (formData.modalidadeSelecionada) {
            requestData.modalidade = formData.modalidadeSelecionada
        }

        const response = await axios.post('/api/v1/credit/simulate', requestData)

        if (response.data.status === 'success') {
            let result = response.data
            
            // Se uma modalidade específica foi selecionada, filtrar os resultados
            if (formData.modalidadeSelecionada) {
                result.ofertas = result.ofertas.filter(
                    oferta => oferta.modalidadeCredito === formData.modalidadeSelecionada
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

// SSE Event handlers - improved to be more responsive
const handleCreditConsultationCompleted = (event) => {
    console.log('Credit consultation completed:', event.detail)
    // Immediately reload customers when a new consultation is completed via SSE
    loadCustomersWithOffers()
}

const handleCreditConsultationFailed = (event) => {
    console.log('Credit consultation failed:', event.detail)
    // Still reload customers in case there were partial results
    loadCustomersWithOffers()
}

onMounted(() => {
    loadCustomersWithOffers()

    // Listen for SSE events from MainLayout
    window.addEventListener('credit-consultation-completed', handleCreditConsultationCompleted)
    window.addEventListener('credit-consultation-failed', handleCreditConsultationFailed)
})

onUnmounted(() => {
    window.removeEventListener('credit-consultation-completed', handleCreditConsultationCompleted)
    window.removeEventListener('credit-consultation-failed', handleCreditConsultationFailed)
})

const formatMoney = (centavos) => {
    return (centavos / 100).toLocaleString('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    })
}
</script>
