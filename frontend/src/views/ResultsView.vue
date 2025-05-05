<template>
    <v-container class="pa-6" max-width="800">
        <template v-if="loading">
            <Loader />
        </template>

        <template v-else>
            <transition name="fade" mode="out-in">
                <div>
                    <v-card class="mb-6" elevation="4">
                        <v-card-title>Dane pacjenta</v-card-title>
                        <v-card-text>
                            <v-row>
                                <v-col cols="12" sm="6"
                                    ><strong>Imię:</strong> {{ patient.name }}</v-col
                                >
                                <v-col cols="12" sm="6"
                                    ><strong>Nazwisko:</strong> {{ patient.surname }}</v-col
                                >
                                <v-col cols="12" sm="6"
                                    ><strong>Płeć:</strong> {{ patient.sex }}</v-col
                                >
                                <v-col cols="12" sm="6"
                                    ><strong>Data urodzenia:</strong> {{ patient.birthDate }}</v-col
                                >
                            </v-row>
                        </v-card-text>
                    </v-card>

                    <div v-if="orders.length">
                        <v-expansion-panels v-model="expanded">
                            <v-expansion-panel v-for="order in orders" :key="order.orderId">
                                <v-expansion-panel-title>
                                    Wyniki badania nr {{ order.orderId }}
                                </v-expansion-panel-title>
                                <v-expansion-panel-text>
                                    <v-list>
                                        <v-list-item
                                            v-for="result in order.results"
                                            :key="result.name"
                                            :class="{
                                                'highlight-high':
                                                    analyzeResult(result.value, result.reference)
                                                        .status === 'high',
                                                'highlight-low':
                                                    analyzeResult(result.value, result.reference)
                                                        .status === 'low',
                                            }"
                                        >
                                            <v-list-item-title class="break-word">
                                                <template
                                                    v-if="
                                                        analyzeResult(
                                                            result.value,
                                                            result.reference,
                                                            result.name,
                                                        ).message
                                                    "
                                                >
                                                    <v-tooltip location="top">
                                                        <template #activator="{ props }">
                                                            <span
                                                                v-bind="props"
                                                                :class="
                                                                    analyzeResult(
                                                                        result.value,
                                                                        result.reference,
                                                                        result.name,
                                                                    ).status
                                                                "
                                                            >
                                                                <strong>{{ result.name }}:</strong>
                                                                {{ result.value }}
                                                            </span>
                                                        </template>
                                                        <span>{{
                                                            analyzeResult(
                                                                result.value,
                                                                result.reference,
                                                                result.name,
                                                            ).message
                                                        }}</span>
                                                    </v-tooltip>
                                                </template>
                                                <template v-else>
                                                    <span
                                                        :class="
                                                            analyzeResult(
                                                                result.value,
                                                                result.reference,
                                                                result.name,
                                                            ).status
                                                        "
                                                    >
                                                        <strong>{{ result.name }}:</strong>
                                                        {{ result.value }}
                                                    </span>
                                                </template>
                                                <span class="text--secondary"
                                                    >(norma: {{ result.reference }})</span
                                                >
                                            </v-list-item-title>
                                        </v-list-item>
                                    </v-list>
                                </v-expansion-panel-text>
                            </v-expansion-panel>
                        </v-expansion-panels>
                    </div>

                    <v-alert v-else type="info" class="mt-6">
                        Brak wyników do wyświetlenia.
                    </v-alert>
                </div>
            </transition>
        </template>
    </v-container>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import Loader from '@/components/Loader.vue'
import { adviceMap } from '@/utils/labAdvice.js'

const router = useRouter()
const patient = ref(null)
const orders = ref([])
const loading = ref(true)
const expanded = ref([0])

const logout = () => {
    localStorage.removeItem('token')
    router.push('/login')
}

function getResultClass(result) {
    const ref = result.reference
    const val = parseFloat(result.value)

    if (!ref || isNaN(val)) return ''

    const match = ref.match(/([\d.]+)\s*-\s*([\d.]+)/)
    if (!match) return ''

    const min = parseFloat(match[1])
    const max = parseFloat(match[2])

    if (val < min || val > max) {
        const offBy = Math.abs(val < min ? min - val : val - max)
        const range = max - min
        const severity = offBy / range

        if (severity > 0.3) return 'severe-warning'
        return 'mild-warning'
    }

    return ''
}

const analyzeResult = (valueStr, refStr, testName = '') => {
    const [min, max] = refStr.split('-').map((v) => parseFloat(v.trim()))
    const value = parseFloat(valueStr)
    if (isNaN(value) || isNaN(min) || isNaN(max)) return { status: 'normal', message: '' }

    let status = 'normal'
    let message = ''
    if (value < min) {
        status = 'low'
        message = adviceMap[testName]?.low || 'Wynik poniżej normy. Skonsultuj się z lekarzem.'
    } else if (value > max) {
        status = 'high'
        message = adviceMap[testName]?.high || 'Wynik powyżej normy. Skonsultuj się z lekarzem.'
    }
    return { status, message }
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
            if (orders.value.length > 0) {
                expanded.value = [0]
            }
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

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.5s;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
.break-word {
    word-wrap: break-word;
    word-break: break-word;
    white-space: normal;
}
.highlight-high {
    background-color: #ffebee;
    border-left: 4px solid #f44336;
}

.highlight-low {
    background-color: #fffde7;
    border-left: 4px solid #ffeb3b;
}
.low {
    background-color: rgba(255, 205, 0, 0.2);
    border-radius: 4px;
    padding: 0 4px;
}

.high {
    background-color: rgba(255, 0, 0, 0.2);
    border-radius: 4px;
    padding: 0 4px;
}
@media (max-width: 600px) {
    .v-list-item-title {
        word-wrap: break-word;
        word-break: break-word;
        white-space: normal;
    }
}
</style>
