<script setup lang="ts">
import { ref } from "vue";
import { useRouter } from "vue-router";
import { useAuth } from "../stores/auth";

const auth = useAuth();
const router = useRouter();
const email = ref("");
const password = ref("");
const error = ref("");
const loading = ref(false);

async function submit() {
  error.value = "";
  loading.value = true;
  try {
    await auth.login(email.value, password.value);
    router.replace("/admin/dashboard");
  } catch (e: any) {
    error.value = e?.response?.data?.detail || "Credenciales inválidas";
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <v-container class="fill-height" style="height: 100vh">
    <v-row justify="center" align="center">
      <v-col cols="12" sm="8" md="4">
        <v-card>
          <v-card-title>SmartLabs · Almacén</v-card-title>
          <v-card-text>
            <v-form @submit.prevent="submit">
              <v-text-field v-model="email" label="Email" type="email" required />
              <v-text-field v-model="password" label="Contraseña" type="password" required />
              <v-alert v-if="error" type="error" variant="tonal" class="mb-2">{{ error }}</v-alert>
              <v-btn type="submit" color="primary" block :loading="loading">Entrar</v-btn>
            </v-form>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>
