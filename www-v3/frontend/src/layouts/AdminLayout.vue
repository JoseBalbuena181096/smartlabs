<script setup lang="ts">
import { useRouter } from "vue-router";
import { useAuth } from "../stores/auth";

const router = useRouter();
const auth = useAuth();

const items = [
  { title: "Dashboard", to: "/admin/dashboard", icon: "mdi-view-dashboard" },
  { title: "Usuarios", to: "/admin/users", icon: "mdi-account-group" },
  { title: "Herramientas", to: "/admin/tools", icon: "mdi-tools" },
  { title: "Préstamos", to: "/admin/loans", icon: "mdi-clipboard-list" },
  { title: "Estaciones", to: "/admin/stations", icon: "mdi-radar" },
  { title: "Inventario", to: "/admin/inventory", icon: "mdi-package-variant" },
];

function logout() {
  auth.logout();
  router.replace("/login");
}
</script>

<template>
  <v-navigation-drawer permanent>
    <v-list-item :title="auth.user?.full_name || 'Admin'" :subtitle="auth.user?.email" />
    <v-divider />
    <v-list nav density="compact">
      <v-list-item v-for="i in items" :key="i.to" :to="i.to" :prepend-icon="i.icon" :title="i.title" />
    </v-list>
    <template #append>
      <v-list-item prepend-icon="mdi-logout" title="Cerrar sesión" @click="logout" />
    </template>
  </v-navigation-drawer>

  <v-app-bar color="primary">
    <v-app-bar-title>SmartLabs · Almacén</v-app-bar-title>
  </v-app-bar>

  <v-main>
    <v-container fluid>
      <router-view />
    </v-container>
  </v-main>
</template>
