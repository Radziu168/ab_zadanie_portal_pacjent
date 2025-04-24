<template>
    <v-app-bar flat color="primary" dark class="d-flex align-center px-4">
        <div class="d-flex align-center">
            <img
                :src="Logo"
                alt="Logo"
                style="max-height: 40px; margin-right: 16px; display: block"
            />
        </div>

        <div class="flex-grow-1 text-center">
            <span class="white--text font-weight-bold text-h6"> Panel Pacjenta </span>
        </div>

        <div class="d-flex align-center">
            <v-btn icon @click="logout">
                <v-icon color="white">mdi-logout</v-icon>
            </v-btn>
        </div>
    </v-app-bar>
</template>

<script setup>
import { useRouter } from 'vue-router'
import Logo from '@/assets/logo_alab.svg'
const router = useRouter()
const logout = () => {
    localStorage.removeItem('token')
    router.push('/login')
}
</script>
