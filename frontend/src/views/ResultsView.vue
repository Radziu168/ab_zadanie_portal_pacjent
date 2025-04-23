<template>
    <div class="p-6 max-w-4xl mx-auto">
        <button
            @click="logout"
            class="absolute top-6 right-6 bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded"
        >
            Wyloguj
        </button>
        <h1 class="text-2xl font-semibold mb-4">Dane pacjenta</h1>

        <div v-if="patient" class="mb-8 bg-white p-4 rounded shadow">
            <p><strong>Imię:</strong> {{ patient.name }}</p>
            <p><strong>Nazwisko:</strong> {{ patient.surname }}</p>
            <p><strong>Płeć:</strong> {{ patient.sex }}</p>
            <p><strong>Data urodzenia:</strong> {{ patient.birthDate }}</p>
        </div>

        <h2 class="text-xl font-semibold mb-2">Wyniki badań</h2>

        <div v-if="orders.length" class="space-y-4">
            <div v-for="order in orders" :key="order.orderId" class="bg-white p-4 rounded shadow">
                <h3 class="font-bold text-gray-700 mb-2">Wyniki zlecenia #{{ order.orderId }}</h3>
                <ul class="space-y-1">
                    <li v-for="result in order.results" :key="result.name">
                        <span class="font-medium">{{ result.name }}</span
                        >:
                        {{ result.value }}
                        <span class="text-gray-500 text-sm">(norma: {{ result.reference }})</span>
                    </li>
                </ul>
            </div>
        </div>

        <p v-if="!orders.length" class="text-gray-600">Brak wyników do wyświetlenia.</p>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'

const patient = ref(null)
const orders = ref([])
const router = useRouter()

onMounted(async () => {
    const token = localStorage.getItem('token')

    if (!token) {
        router.push('/login')
        return
    }

    try {
        const API_URL = import.meta.env.VITE_API_URL
        const response = await fetch(`${API_URL}/api/results`, {
            headers: {
                Authorization: 'Bearer ' + token,
                Accept: 'application/json',
            },
        })

        if (response.ok) {
            const data = await response.json()
            patient.value = data.patient
            orders.value = data.orders
        } else {
            localStorage.removeItem('token')
            router.push('/login')
        }
    } catch (error) {
        console.error('Błąd połączenia z API:', error)
        localStorage.removeItem('token')
        router.push('/login')
    }
})
const logout = () => {
    localStorage.removeItem('token')
    router.push('/login')
}
</script>
