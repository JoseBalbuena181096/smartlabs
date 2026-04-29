<script setup lang="ts">
import { onMounted, onBeforeUnmount, ref } from "vue";
import { api } from "../../api/client";
import { openAdminWs, type WsClient } from "../../ws/client";
import { useAuth } from "../../stores/auth";

const auth = useAuth();
const stations = ref<any[]>([]);
const activeLoans = ref<any[]>([]);
const events = ref<any[]>([]);
let ws: WsClient | null = null;

async function refresh() {
  const [{ data: st }, { data: ln }] = await Promise.all([
    api.get("/stations"),
    api.get("/loans/active"),
  ]);
  stations.value = st;
  activeLoans.value = ln;
}

onMounted(async () => {
  await refresh();
  ws = openAdminWs(auth.token!, false, (ev) => {
    events.value.unshift(ev);
    events.value = events.value.slice(0, 30);
    // refrescar contadores ante eventos clave
    if (
      ["loan.created", "loan.returned", "tool.retired", "session.opened", "session.closed"].includes(ev.type)
    ) {
      refresh();
    }
  });
});
onBeforeUnmount(() => ws?.close());
</script>

<template>
  <v-row>
    <v-col cols="12" md="4">
      <v-card>
        <v-card-title>Estaciones online</v-card-title>
        <v-card-text class="text-h3">
          {{ stations.filter((s) => s.online).length }} / {{ stations.length }}
        </v-card-text>
      </v-card>
    </v-col>
    <v-col cols="12" md="4">
      <v-card>
        <v-card-title>Préstamos activos</v-card-title>
        <v-card-text class="text-h3">{{ activeLoans.length }}</v-card-text>
      </v-card>
    </v-col>
    <v-col cols="12" md="4">
      <v-card>
        <v-card-title>Eventos en vivo</v-card-title>
        <v-list density="compact">
          <v-list-item v-for="(e, i) in events" :key="i" :title="e.type" :subtitle="e.station_sn || ''" />
        </v-list>
      </v-card>
    </v-col>
  </v-row>
</template>
