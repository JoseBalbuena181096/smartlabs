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
  <div class="tec-login">
    <v-card elevation="6" class="pa-6">
      <div class="d-flex align-center mb-4">
        <span class="tec-brand-mark" style="background: var(--tec-blue); color: white; width: 40px; height: 40px; font-size: 18px;">T</span>
        <div class="ml-3">
          <div class="text-h6 font-weight-bold" style="line-height: 1.1; color: var(--tec-blue)">SmartLabs</div>
          <div class="text-caption text-grey-darken-1">Almacén · Tec de Monterrey</div>
        </div>
      </div>
      <v-form @submit.prevent="submit">
        <v-text-field v-model="email" label="Email" type="email" required prepend-inner-icon="mdi-email-outline" />
        <v-text-field v-model="password" label="Contraseña" type="password" required prepend-inner-icon="mdi-lock-outline" />
        <v-alert v-if="error" type="error" variant="tonal" class="mb-2">{{ error }}</v-alert>
        <v-btn type="submit" color="primary" block size="large" :loading="loading">Entrar</v-btn>
      </v-form>
    </v-card>
  </div>
</template>
