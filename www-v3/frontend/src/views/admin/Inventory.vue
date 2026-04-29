<script setup lang="ts">
import { onMounted, onBeforeUnmount, ref } from "vue";
import { api } from "../../api/client";
import { openAdminWs, type WsClient } from "../../ws/client";
import { useAuth } from "../../stores/auth";

const auth = useAuth();
const runs = ref<any[]>([]);
const current = ref<any | null>(null);
const report = ref<any | null>(null);
const tools = ref<any[]>([]);
let ws: WsClient | null = null;

async function load() {
  const [{ data: rs }, { data: ts }] = await Promise.all([
    api.get("/inventory"),
    api.get("/tools"),
  ]);
  runs.value = rs;
  tools.value = ts;
  const open = rs.find((r: any) => !r.finished_at);
  current.value = open || null;
  if (current.value) {
    const { data } = await api.get(`/inventory/${current.value.id}`);
    report.value = data;
  } else {
    report.value = null;
  }
}

async function start() {
  await api.post("/inventory/start", {});
  await load();
}

async function finish() {
  if (!current.value) return;
  if (!confirm("¿Cerrar inventario y generar reporte?")) return;
  const { data } = await api.post(`/inventory/${current.value.id}/finish`, {});
  report.value = data;
  current.value = null;
  await load();
}

function toolByRfid(rfid: string) {
  return tools.value.find((t) => t.rfid === rfid);
}

function toolById(id: number) {
  return tools.value.find((t) => t.id === id);
}

onMounted(async () => {
  await load();
  ws = openAdminWs(auth.token!, false, async (ev) => {
    if (ev.type === "inventory.scan" && current.value) {
      const { data } = await api.get(`/inventory/${current.value.id}`);
      report.value = data;
    }
  });
});
onBeforeUnmount(() => ws?.close());
</script>

<template>
  <v-row>
    <v-col cols="12">
      <v-card>
        <v-card-title>
          Inventario
          <v-spacer />
          <v-btn v-if="!current" color="primary" @click="start">Iniciar</v-btn>
          <v-btn v-else color="warning" @click="finish">Terminar</v-btn>
        </v-card-title>
        <v-card-text>
          <v-alert v-if="current" type="info" variant="tonal" class="mb-4">
            Inventario abierto desde {{ current.started_at }}. El sistema pausa préstamos
            mientras dure (los ESP32 reciben "modo inventario").
          </v-alert>
        </v-card-text>
      </v-card>
    </v-col>

    <v-col v-if="report" cols="12" md="6">
      <v-card>
        <v-card-title>Escaneadas ({{ report.scans?.length || 0 }})</v-card-title>
        <v-list density="compact" max-height="400" class="overflow-auto">
          <v-list-item v-for="s in report.scans" :key="s.id">
            <template #title>
              <strong>{{ s.tool_rfid }}</strong>
              <v-chip v-if="!s.tool_id" size="x-small" color="error" class="ml-2">desconocido</v-chip>
            </template>
            <template #subtitle>
              <span v-if="s.tool_id">
                {{ toolById(s.tool_id)?.brand }} {{ toolById(s.tool_id)?.model }}
              </span>
              <span v-else>tag no registrado</span>
            </template>
          </v-list-item>
        </v-list>
      </v-card>
    </v-col>

    <v-col v-if="report" cols="12" md="6">
      <v-card>
        <v-card-title>Faltantes ({{ report.missing_tool_ids?.length || 0 }})</v-card-title>
        <v-list density="compact" max-height="400" class="overflow-auto">
          <v-list-item v-for="id in report.missing_tool_ids" :key="id">
            <template #title>{{ toolById(id)?.brand }} {{ toolById(id)?.model }}</template>
            <template #subtitle>RFID {{ toolById(id)?.rfid }} · Ubicación {{ toolById(id)?.location || "—" }}</template>
          </v-list-item>
        </v-list>
      </v-card>
    </v-col>

    <v-col cols="12">
      <v-card>
        <v-card-title>Histórico</v-card-title>
        <v-data-table
          :items="runs"
          :headers="[
            { title: 'ID', key: 'id' },
            { title: 'Inicio', key: 'started_at' },
            { title: 'Fin', key: 'finished_at' },
            { title: 'Notas', key: 'notes' },
          ]"
          items-per-page="10"
        />
      </v-card>
    </v-col>
  </v-row>
</template>
