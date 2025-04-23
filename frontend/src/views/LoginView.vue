<template>
    <div class="min-h-screen flex flex-col justify-center items-center bg-gray-100">
        <div class="bg-white p-8 rounded-xl shadow-lg w-full max-w-md">
            <h1 class="text-2xl font-semibold text-center mb-6">🔐 Logowanie pacjenta</h1>

            <form @submit.prevent="login">
                <div class="mb-4">
                    <label class="block font-medium mb-1">Login</label>
                    <input
                        v-model="loginInput"
                        type="text"
                        class="w-full border px-3 py-2 rounded"
                        placeholder="ImięNazwisko"
                    />
                </div>

                <div class="mb-4">
                    <label class="block font-medium mb-1">Hasło (data urodzenia)</label>
                    <input
                        v-model="passwordInput"
                        type="text"
                        class="w-full border px-3 py-2 rounded"
                        placeholder="1983-04-12"
                    />
                </div>

                <button
                    type="submit"
                    class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700"
                >
                    Zaloguj się
                </button>
            </form>

            <p v-if="error" class="text-red-500 mt-4 text-center">{{ error }}</p>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'

const loginInput = ref('')
const passwordInput = ref('')
const error = ref(null)
const router = useRouter()

const login = async () => {
    error.value = null

    try {
        const API_URL = import.meta.env.VITE_API_URL
        const response = await fetch(`${API_URL}/api/login`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                login: loginInput.value,
                password: passwordInput.value,
            }),
        })

        if (!response.ok) {
            const data = await response.json()
            error.value = data.error || 'Błąd logowania'
            return
        }

        const data = await response.json()
        localStorage.setItem('token', data.token)
        router.push('/results')
    } catch (e) {
        error.value = 'Wystąpił błąd połączenia z API'
    }
}
</script>
