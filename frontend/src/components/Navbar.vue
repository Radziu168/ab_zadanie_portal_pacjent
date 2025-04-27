<template>
    <v-app-bar flat color="primary" dark class="px-4" height="64">
        <v-container class="d-flex align-center justify-between" fluid>
            <div class="d-flex align-center">
                <img
                    :src="Logo"
                    alt="Logo"
                    style="max-height: 40px; margin-right: 16px; display: block"
                />
            </div>

            <div class="d-flex justify-center flex-grow-1">
                <span class="white--text font-weight-bold text-h6"> Panel Pacjenta </span>
            </div>

            <div v-if="route.name !== 'Login'" class="d-flex align-center">
                <v-btn icon @click="logout">
                    <v-icon color="white">mdi-logout</v-icon>
                </v-btn>
            </div>
        </v-container>
    </v-app-bar>
</template>

<script setup>
import { useRoute, useRouter } from 'vue-router'
import Logo from '@/assets/logo_alab.svg'

const router = useRouter()
const route = useRoute()

const logout = () => {
    localStorage.removeItem('token')
    router.push('/login')
}
</script>
