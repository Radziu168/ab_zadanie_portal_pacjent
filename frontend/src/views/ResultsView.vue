<template>
    <v-container class="pa-6" max-width="800">
        <v-btn icon @click="logout" class="position-absolute" style="top: 16px; right: 16px">
            <v-icon>logout</v-icon>
        </v-btn>
        <Loader v-if="loading" />
        <div v-else>
            <v-card class="mb-6">
                <v-card-title>Dane pacjenta</v-card-title>
                <v-card-text>
                    <v-row>
                        <v-col cols="12" sm="6"> <strong>Imię:</strong> {{ patient.name }} </v-col>
                        <v-col cols="12" sm="6">
                            <strong>Nazwisko:</strong> {{ patient.surname }}
                        </v-col>
                        <v-col cols="12" sm="6"> <strong>Płeć:</strong> {{ patient.sex }} </v-col>
                        <v-col cols="12" sm="6">
                            <strong>Data urodzenia:</strong> {{ patient.birthDate }}
                        </v-col>
                    </v-row>
                </v-card-text>
            </v-card>

            <div v-if="orders.length">
                <v-accordion>
                    <v-accordion-item
                        v-for="order in orders"
                        :key="order.orderId"
                        :title="`Wyniki zlecenia #${order.orderId}`"
                    >
                        <v-list>
                            <v-list-item v-for="result in order.results" :key="result.name">
                                <v-list-item-content>
                                    <div>
                                        <strong>{{ result.name }}:</strong> {{ result.value }}
                                        <span class="text--secondary"
                                            >(norma: {{ result.reference }})</span
                                        >
                                    </div>
                                </v-list-item-content>
                            </v-list-item>
                        </v-list>
                    </v-accordion-item>
                </v-accordion>
            </div>
            <v-alert v-else type="info"> Brak wyników do wyświetlenia. </v-alert>
        </div>
    </v-container>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import Loader from '@/components/Loader.vue'

const router = useRouter()
const patient = ref(null)
const orders = ref([])
const loading = ref(true)

const logout = () => {
    localStorage.removeItem('token')
    router.push('/login')
}

onMounted(async () => {
    const token = localStorage.getItem('token')
    if (!token) {
        logout()
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
            logout()
        }
    } catch (e) {
        console.error('Błąd połączenia z API:', e)
        logout()
    } finally {
        loading.value = false
    }
})
</script>
