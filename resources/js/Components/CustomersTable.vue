<template>
    <div class="max-w-7xl mx-auto mb-8">
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-800">
                    Clientes Cadastrados ({{ customers.length }})
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
                        <tr v-for="customer in customers" :key="customer.cpf" class="hover:bg-gray-50">
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
                                    @click="$emit('simulate', customer)"
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
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    customers: {
        type: Array,
        default: () => []
    }
})

const emit = defineEmits(['simulate'])

const totalOffers = computed(() => {
    return props.customers.reduce((total, customer) => total + customer.offers_count, 0)
})

const formatCpfDisplay = (cpf) => {
    if (!cpf) return ''
    const cpfString = cpf.toString()
    const paddedCpf = cpfString.padStart(11, '0')
    return paddedCpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4')
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
</script>