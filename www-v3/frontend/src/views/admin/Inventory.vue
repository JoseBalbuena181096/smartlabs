<script setup lang="ts">
import { computed, onMounted, onBeforeUnmount, ref } from "vue";
import { api } from "../../api/client";
import { openAdminWs, type WsClient } from "../../ws/client";
import { useAuth } from "../../stores/auth";
import { useConfirm } from "../../composables/useConfirm";
import { useSnack } from "../../composables/useSnack";
import PageHeader from "../../components/PageHeader.vue";
import EmptyState from "../../components/EmptyState.vue";

const auth = useAuth();
const { confirm } = useConfirm();
const snack = useSnack();

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
  try {
    await api.post("/inventory/start", {});
    snack.success("Inventario iniciado");
    await load();
  } catch (e: any) {
    snack.error(e?.response?.data?.detail || "No se pudo iniciar");
  }
}

async function finish() {
  if (!current.value) return;
  const ok = await confirm(
    "Cerrar el inventario y generar reporte. Las herramientas no escaneadas aparecerán como faltantes.",
    { title: "Cerrar inventario", confirmText: "Cerrar y generar" },
  );
  if (!ok) return;
  try {
    const { data } = await api.post(`/inventory/${current.value.id}/finish`, {});
    report.value = data;
    current.value = null;
    snack.success("Inventario cerrado");
    await load();
  } catch (e: any) {
    snack.error(e?.response?.data?.detail || "No se pudo cerrar");
  }
}

function toolById(id: number) {
  return tools.value.find((t) => t.id === id);
}

function fmt(s: string | undefined | null) {
  if (!s) return "—";
  return new Date(s).toLocaleString("es-MX", { dateStyle: "short", timeStyle: "short" });
}

const scannedCount = computed(() => report.value?.scans?.length || 0);
const missingCount = computed(() => report.value?.missing_tool_ids?.length || 0);
const unknownCount = computed(() => report.value?.scans?.filter((s: any) => !s.tool_id).length || 0);

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
  <PageHeader title="Inventario" subtitle="Conciliación física vs catálogo" icon="mdi-package-variant-closed">
    <v-btn v-if="!current" color="primary" prepend-icon="mdi-play" rounded="lg" @click="start">
      Iniciar inventario
    </v-btn>
    <v-btn v-else color="warning" prepend-icon="mdi-stop" rounded="lg" @click="finish">
      Terminar
    </v-btn>
  </PageHeader>

  <v-alert
    v-if="current"
    type="info"
    variant="tonal"
    rounded="lg"
    icon="mdi-radar"
    class="mb-4"
  >
    <strong>Inventario en curso</strong> · iniciado {{ fmt(current.started_at) }}.
    Mientras dure, los ESP32 reciben "modo inventario" y los préstamos quedan pausados.
    Pasa todos los tags físicos en cualquier estación.
  </v-alert>

  <v-row v-if="report" dense>
    <v-col cols="6" md="4">
      <v-card rounded="lg" elevation="1">
        <v-card-text class="pa-4">
          <div class="text-overline text-medium-emphasis">Escaneadas</div>
          <div class="text-h3 font-weight-bold">{{ scannedCount }}</div>
        </v-card-text>
      </v-card>
    </v-col>
    <v-col cols="6" md="4">
      <v-card rounded="lg" elevation="1">
        <v-card-text class="pa-4">
          <div class="text-overline text-medium-emphasis">Faltantes</div>
          <div class="text-h3 font-weight-bold" :class="missingCount > 0 ? 'text-error' : ''">{{ missingCount }}</div>
        </v-card-text>
      </v-card>
    </v-col>
    <v-col cols="12" md="4">
      <v-card rounded="lg" elevation="1">
        <v-card-text class="pa-4">
          <div class="text-overline text-medium-emphasis">Tags desconocidos</div>
          <div class="text-h3 font-weight-bold" :class="unknownCount > 0 ? 'text-warning' : ''">{{ unknownCount }}</div>
        </v-card-text>
      </v-card>
    </v-col>
  </v-row>

  <v-row v-if="report" class="mt-2">
    <v-col cols="12" md="6">
      <v-card rounded="lg" elevation="1">
        <v-card-title class="d-flex align-center pa-4">
          <v-icon class="mr-2" color="success">mdi-check-circle-outline</v-icon>
          <span class="font-weight-bold">Escaneadas ({{ scannedCount }})</span>
        </v-card-title>
        <v-divider />
        <v-list v-if="scannedCount > 0" density="compact" max-height="420" class="overflow-auto">
          <v-list-item v-for="s in report.scans" :key="s.id">
            <template #prepend>
              <v-icon :color="s.tool_id ? 'success' : 'warning'" size="20">
                {{ s.tool_id ? "mdi-check" : "mdi-help-circle-outline" }}
              </v-icon>
            </template>
            <v-list-item-title>
              <code>{{ s.tool_rfid }}</code>
              <v-chip v-if="!s.tool_id" size="x-small" color="warning" variant="tonal" class="ml-2">
                desconocido
              </v-chip>
            </v-list-item-title>
            <v-list-item-subtitle>
              <span v-if="s.tool_id">{{ toolById(s.tool_id)?.brand }} {{ toolById(s.tool_id)?.model }}</span>
              <span v-else class="text-medium-emphasis">tag no registrado en catálogo</span>
            </v-list-item-subtitle>
          </v-list-item>
        </v-list>
        <EmptyState
          v-else
          icon="mdi-barcode-scan"
          title="Aún sin scans"
          description="Pasa los tags en cualquier estación para registrarlos."
        />
      </v-card>
    </v-col>

    <v-col cols="12" md="6">
      <v-card rounded="lg" elevation="1">
        <v-card-title class="d-flex align-center pa-4">
          <v-icon class="mr-2" color="error">mdi-alert-circle-outline</v-icon>
          <span class="font-weight-bold">Faltantes ({{ missingCount }})</span>
        </v-card-title>
        <v-divider />
        <v-list v-if="missingCount > 0" density="compact" max-height="420" class="overflow-auto">
          <v-list-item v-for="id in report.missing_tool_ids" :key="id">
            <template #prepend>
              <v-avatar color="error" variant="tonal" size="32">
                <v-icon size="18">mdi-tools</v-icon>
              </v-avatar>
            </template>
            <v-list-item-title class="font-weight-medium">
              {{ toolById(id)?.brand }} {{ toolById(id)?.model }}
            </v-list-item-title>
            <v-list-item-subtitle>
              <code>{{ toolById(id)?.rfid }}</code> · {{ toolById(id)?.location || "sin ubicación" }}
            </v-list-item-subtitle>
          </v-list-item>
        </v-list>
        <EmptyState
          v-else
          icon="mdi-check-all"
          title="Todo presente"
          description="No hay herramientas marcadas como faltantes en este inventario."
        />
      </v-card>
    </v-col>
  </v-row>

  <v-card class="mt-4" rounded="lg" elevation="1">
    <v-card-title class="d-flex align-center pa-4">
      <v-icon class="mr-2" color="primary">mdi-history</v-icon>
      <span class="font-weight-bold">Histórico de corridas</span>
    </v-card-title>
    <v-divider />
    <v-data-table
      :items="runs"
      :headers="[
        { title: '#', key: 'id' },
        { title: 'Inicio', key: 'started_at' },
        { title: 'Fin', key: 'finished_at' },
        { title: 'Notas', key: 'notes' },
      ]"
      items-per-page="10"
    >
      <template #item.started_at="{ item }">{{ fmt(item.started_at) }}</template>
      <template #item.finished_at="{ item }">
        <v-chip v-if="!item.finished_at" size="small" color="info" variant="tonal">en curso</v-chip>
        <span v-else>{{ fmt(item.finished_at) }}</span>
      </template>
      <template #no-data>
        <EmptyState
          icon="mdi-history"
          title="Sin corridas previas"
          description="Cuando hagas el primer inventario aparecerá aquí."
        />
      </template>
    </v-data-table>
  </v-card>
</template>
