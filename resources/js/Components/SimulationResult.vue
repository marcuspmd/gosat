<template>
    <div class="mt-6">
        <h4 class="text-lg font-semibold text-gray-800 mb-4">
            Melhores Ofertas Encontradas ({{ result.simulations.length }})
        </h4>

        <div class="space-y-4">
            <div
                v-for="(offer, index) in result.simulations"
                :key="index"
                class="border rounded-lg p-4"
                :class="index === 0 ? 'border-green-500 bg-green-50' : index === 1 ? 'border-blue-500 bg-blue-50' : 'border-orange-500 bg-orange-50'"
            >
                <div class="flex justify-between items-start mb-3">
                    <div>
                        <div class="flex items-center space-x-2">
                            <h5 class="text-lg font-semibold text-gray-900">
                                {{ offer.financial_institution }}
                            </h5>
                            <span
                                class="px-2 py-1 text-xs font-medium rounded-full"
                                :class="index === 0 ? 'bg-green-100 text-green-800' : index === 1 ? 'bg-blue-100 text-blue-800' : 'bg-orange-100 text-orange-800'"
                            >
                                {{ index === 0 ? 'Melhor Oferta' : index === 1 ? '2ª Melhor' : '3ª Melhor' }}
                            </span>
                        </div>
                        <p class="text-sm text-gray-600 mt-1">
                            {{ capitalizeModalidade(offer.credit_modality) }}
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                    <div>
                        <span class="text-gray-600">Valor Solicitado:</span>
                        <div class="font-medium">{{ formatMoney(offer.requested_amount) }}</div>
                    </div>
                    <div>
                        <span class="text-gray-600">Parcela Mensal:</span>
                        <div class="font-medium text-blue-600">{{ formatMoney(offer.monthly_payment) }}</div>
                    </div>
                    <div>
                        <span class="text-gray-600">Total a Pagar:</span>
                        <div class="font-medium text-green-600">{{ formatMoney(offer.total_amount) }}</div>
                    </div>
                    <div>
                        <span class="text-gray-600">Total Juros:</span>
                        <div class="font-medium text-red-600">{{ formatMoney(offer.total_interest) }}</div>
                    </div>
                </div>

                <div class="mt-3 pt-3 border-t space-y-3">
                    <div class="text-sm text-gray-600">
                        <div class="flex flex-wrap items-center justify-between">
                            <span>Taxa: {{ (offer.monthly_interest_rate * 100).toFixed(2) }}% a.m. ({{ (offer.annual_interest_rate * 100).toFixed(2) }}% a.a.)</span>
                            <span>{{ offer.installments }}x parcelas</span>
                        </div>
                    </div>

                    <!-- Limites da Oferta -->
                    <div v-if="offer.limits" class="bg-gray-50 rounded-lg p-3">
                        <h6 class="text-xs font-medium text-gray-700 mb-2">Limites disponíveis nesta modalidade:</h6>
                        <div class="grid grid-cols-2 gap-3 text-xs text-gray-600">
                            <div>
                                <span class="text-gray-500">Valor:</span>
                                <div class="font-medium">{{ formatMoney(offer.limits.min_amount) }} - {{ formatMoney(offer.limits.max_amount) }}</div>
                            </div>
                            <div>
                                <span class="text-gray-500">Parcelas:</span>
                                <div class="font-medium">{{ offer.limits.min_installments }} - {{ offer.limits.max_installments }}x</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="result.total_offers_found > 3" class="mt-4 p-3 bg-blue-100 rounded-lg text-sm text-blue-800">
            <strong>Info:</strong> Foram encontradas {{ result.total_offers_found }} ofertas.
            Mostrando as 3 mais vantajosas para você.
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    result: {
        type: Object,
        required: true
    }
})

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