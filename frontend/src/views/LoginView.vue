<template>
    <v-app>
        <v-main>
            <v-container fluid class="d-flex align-center justify-center" style="height: 100vh">
                <div style="width: 100%; max-width: 500px">
                    <v-card elevation="2">
                        <v-card-title class="justify-center"> 🔐 Logowanie pacjenta </v-card-title>

                        <v-card-text>
                            <v-form ref="form" v-model="valid" @submit.prevent="login">
                                <v-text-field
                                    v-model="loginInput"
                                    :rules="loginRules"
                                    label="Login (ImięNazwisko)"
                                    required
                                />
                                <v-text-field
                                    v-model="passwordInput"
                                    :rules="passwordRules"
                                    label="Hasło (RRRR-MM-DD)"
                                    type="password"
                                    required
                                />
                                <v-btn
                                    :disabled="!valid || loading"
                                    type="submit"
                                    color="primary"
                                    class="mt-4"
                                    block
                                >
                                    Zaloguj się
                                </v-btn>
                            </v-form>
                            <v-alert v-if="error" type="error" class="mt-4" dense text>
                                {{ error }}
                            </v-alert>
                        </v-card-text>

                        <v-card-actions class="justify-center">
                            <v-progress-circular v-if="loading" indeterminate color="primary" />
                        </v-card-actions>
                    </v-card>
                </div>
            </v-container>
        </v-main>
    </v-app>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'

const loginInput = ref('')
const passwordInput = ref('')
const error = ref('')
const loading = ref(false)
const valid = ref(false)
const form = ref(null)
const router = useRouter()

const loginRules = [
    (v) => !!v || 'Login jest wymagany',
    (v) => !/\s/.test(v) || 'Nie używaj spacji',
    (v) => /^\p{Lu}\p{Ll}+\p{Lu}\p{Ll}+$/u.test(v) || 'Login ma format: ImięNazwisko',
]

const passwordRules = [
    (v) => !!v || 'Hasło jest wymagane',
    (v) => /^\d{4}-\d{2}-\d{2}$/.test(v) || 'Format hasła: RRRR-MM-DD',
]

const login = async () => {
    if (form.value && !form.value.validate()) return

    error.value = ''
    loading.value = true

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
    } catch {
        error.value = 'Wystąpił błąd połączenia z API'
    } finally {
        loading.value = false
    }
}
</script>
