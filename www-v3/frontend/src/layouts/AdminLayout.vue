<script setup lang="ts">
import { computed, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useDisplay } from "vuetify";
import { useAuth } from "../stores/auth";

const router = useRouter();
const route = useRoute();
const auth = useAuth();
const { mdAndUp, lgAndUp } = useDisplay();

const items = [
  { title: "Dashboard", to: "/admin/dashboard", icon: "mdi-view-dashboard-outline" },
  { title: "Usuarios", to: "/admin/users", icon: "mdi-account-group-outline" },
  { title: "Herramientas", to: "/admin/tools", icon: "mdi-toolbox-outline" },
  { title: "Préstamos", to: "/admin/loans", icon: "mdi-clipboard-list-outline" },
  { title: "Estaciones", to: "/admin/stations", icon: "mdi-radar" },
  { title: "Inventario", to: "/admin/inventory", icon: "mdi-package-variant-closed" },
  { title: "Rostros", to: "/admin/faces", icon: "mdi-face-recognition" },
];

const drawerOpen = ref(true);
const rail = ref(false);

const currentTitle = computed(() => {
  const found = items.find((i) => route.path.startsWith(i.to));
  return found?.title || "SmartLabs";
});

const initials = computed(() => {
  const name = auth.user?.full_name || "A";
  return name
    .split(" ")
    .filter(Boolean)
    .slice(0, 2)
    .map((p: string) => p[0])
    .join("")
    .toUpperCase();
});

function toggleDrawer() {
  if (mdAndUp.value && !lgAndUp.value) rail.value = !rail.value;
  else drawerOpen.value = !drawerOpen.value;
}

function logout() {
  auth.logout();
  router.replace("/login");
}
</script>

<template>
  <v-app-bar class="tec-appbar" elevation="0" density="comfortable">
    <v-app-bar-nav-icon @click="toggleDrawer" />
    <v-app-bar-title>
      <span class="tec-brand d-none d-sm-inline-flex">
        <span class="tec-brand-mark">T</span>
        SmartLabs
      </span>
      <span class="d-sm-none tec-brand">
        <span class="tec-brand-mark">T</span>
      </span>
      <span class="tec-appbar-section ml-3 ml-sm-4">{{ currentTitle }}</span>
    </v-app-bar-title>
    <v-spacer />
    <v-menu offset="8">
      <template #activator="{ props }">
        <v-btn variant="text" v-bind="props" class="tec-user-chip">
          <v-avatar size="32" color="white" class="mr-2">
            <span class="text-primary font-weight-bold">{{ initials }}</span>
          </v-avatar>
          <span class="d-none d-md-inline">{{ auth.user?.full_name || "Admin" }}</span>
          <v-icon class="ml-1">mdi-chevron-down</v-icon>
        </v-btn>
      </template>
      <v-list density="compact" nav>
        <v-list-item :title="auth.user?.full_name" :subtitle="auth.user?.email" disabled />
        <v-divider />
        <v-list-item prepend-icon="mdi-logout" title="Cerrar sesión" @click="logout" />
      </v-list>
    </v-menu>
  </v-app-bar>

  <v-navigation-drawer
    v-model="drawerOpen"
    :rail="rail && mdAndUp && !lgAndUp"
    :permanent="lgAndUp"
    :temporary="!mdAndUp"
    class="tec-nav"
    width="260"
    rail-width="64"
  >
    <v-list nav density="comfortable">
      <v-list-item
        v-for="i in items"
        :key="i.to"
        :to="i.to"
        :prepend-icon="i.icon"
        :title="i.title"
        rounded="lg"
        class="ma-1"
      />
    </v-list>
    <template #append>
      <v-divider />
      <div class="pa-3 text-caption text-medium-emphasis text-center">
        SmartLabs · Tec de Monterrey
      </div>
    </template>
  </v-navigation-drawer>

  <v-main>
    <v-container fluid class="pa-4 pa-md-6">
      <router-view />
    </v-container>
  </v-main>
</template>
