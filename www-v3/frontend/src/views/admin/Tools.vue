<script setup lang="ts">
import { computed, onMounted, onBeforeUnmount, ref, watch } from "vue";
import { useDisplay } from "vuetify";
import { api } from "../../api/client";
import { openAdminWs, type WsClient } from "../../ws/client";
import { useAuth } from "../../stores/auth";
import { useConfirm } from "../../composables/useConfirm";
import { useSnack } from "../../composables/useSnack";
import PageHeader from "../../components/PageHeader.vue";
import EmptyState from "../../components/EmptyState.vue";

const auth = useAuth();
const { mdAndUp } = useDisplay();
const { confirm } = useConfirm();
const snack = useSnack();

const tools = ref<any[]>([]);
const stations = ref<any[]>([]);
const search = ref("");
const statusFilter = ref<string | null>(null);
const loading = ref(false);
const dialog = ref(false);
const editing = ref<any | null>(null);
const form = ref<any>({ brand: "", model: "", description: "", rfid: "", location: "" });
const captureStationSn = ref<string | null>(null);
const captureToast = ref("");
const captureWarning = ref("");
let ws: WsClient | null = null;
let warningTimer: number | null = null;
let searchTimer: number | null = null;

const filtered = computed(() => {
  if (!statusFilter.value) return tools.value;
  return tools.value.filter((t) => t.status === statusFilter.value);
});

async function load() {
  loading.value = true;
  try {
    const { data } = await api.get("/tools", { params: { q: search.value || undefined } });
    tools.value = data;
  } finally {
    loading.value = false;
  }
}
async function loadStations() {
  const { data } = await api.get("/stations");
  stations.value = data;
}

watch(search, () => {
  if (searchTimer) clearTimeout(searchTimer);
  searchTimer = window.setTimeout(load, 300);
});

function openCreate() {
  editing.value = null;
  form.value = { brand: "", model: "", description: "", rfid: "", location: "" };
  captureToast.value = "";
  captureWarning.value = "";
  dialog.value = true;
  enableCapture();
}

function openEdit(t: any) {
  editing.value = t;
  form.value = { ...t };
  captureToast.value = "";
  captureWarning.value = "";
  dialog.value = true;
  enableCapture();
}

function flashWarning(msg: string) {
  captureWarning.value = msg;
  if (warningTimer) clearTimeout(warningTimer);
  warningTimer = window.setTimeout(() => (captureWarning.value = ""), 6000);
}

function enableCapture() {
  if (ws) return;
  ws = openAdminWs(auth.token!, true, (ev) => {
    if (captureStationSn.value && ev.station_sn && ev.station_sn !== captureStationSn.value) return;

    if (ev.type === "tag.unknown") {
      form.value.rfid = ev.rfid;
      captureToast.value = `RFID capturado: ${ev.rfid} (estación ${ev.station_sn})`;
      return;
    }
    if (ev.type === "tag.known") {
      const where = ev.entity === "user" ? "un usuario" : "una herramienta";
      flashWarning(
        `La tarjeta ${ev.rfid} ya está registrada como ${where}: "${ev.label}"${
          !ev.active ? " (inactivo)" : ""
        }. Si quieres reasignarla, primero edita el registro existente.`,
      );
    }
  });
}

function closeDialog() {
  dialog.value = false;
  ws?.close();
  ws = null;
  captureToast.value = "";
  if (warningTimer) clearTimeout(warningTimer);
}

async function save() {
  try {
    if (editing.value) {
      await api.patch(`/tools/${editing.value.id}`, form.value);
      snack.success(`Herramienta "${form.value.brand} ${form.value.model}" actualizada`);
    } else {
      await api.post("/tools", form.value);
      snack.success(`Herramienta "${form.value.brand} ${form.value.model}" registrada`);
    }
    closeDialog();
    load();
  } catch (e: any) {
    snack.error(e?.response?.data?.detail || "No se pudo guardar");
  }
}

async function retire(t: any) {
  const ok = await confirm(
    `Vas a retirar "${t.brand} ${t.model}" del catálogo. Si tiene un préstamo activo se cerrará automáticamente.`,
    { title: "Retirar herramienta", confirmText: "Retirar", color: "error" },
  );
  if (!ok) return;
  try {
    await api.delete(`/tools/${t.id}`);
    snack.success(`Herramienta retirada`);
    load();
  } catch (e: any) {
    snack.error(e?.response?.data?.detail || "No se pudo retirar");
  }
}

const stationItems = computed(() => [
  { title: "Cualquier estación", value: null },
  ...stations.value.map((s) => ({
    title: `${s.serial_number}${s.alias ? " · " + s.alias : ""}${s.online ? " 🟢" : " ⚫"}`,
    value: s.serial_number,
  })),
]);

const statusColor = (s: string) =>
  s === "in_stock" ? "success" : s === "on_loan" ? "warning" : "grey";

const statusLabel = (s: string) =>
  s === "in_stock" ? "En almacén" : s === "on_loan" ? "Prestada" : "Retirada";

onMounted(() => {
  load();
  loadStations();
});
onBeforeUnmount(() => ws?.close());
</script>

<template>
  <PageHeader title="Herramientas" subtitle="Catálogo del almacén" icon="mdi-toolbox-outline">
    <v-btn color="primary" prepend-icon="mdi-plus" rounded="lg" @click="openCreate">
      Nueva herramienta
    </v-btn>
  </PageHeader>

  <v-card rounded="lg" elevation="1">
    <div class="pa-3 pa-md-4 d-flex flex-wrap align-center" style="gap: 12px">
      <v-text-field
        v-model="search"
        prepend-inner-icon="mdi-magnify"
        placeholder="Buscar por marca, modelo, RFID, ubicación…"
        density="comfortable"
        hide-details
        variant="solo-filled"
        flat
        bg-color="grey-lighten-4"
        style="min-width: 240px; flex: 1 1 280px"
        clearable
      />
      <v-btn-toggle v-model="statusFilter" color="primary" density="comfortable" variant="outlined" divided>
        <v-btn :value="null">Todas</v-btn>
        <v-btn value="in_stock">En almacén</v-btn>
        <v-btn value="on_loan">Prestadas</v-btn>
        <v-btn value="retired">Retiradas</v-btn>
      </v-btn-toggle>
    </div>

    <v-divider />

    <v-data-table
      v-if="mdAndUp"
      :loading="loading"
      :items="filtered"
      :headers="[
        { title: 'Marca', key: 'brand' },
        { title: 'Modelo', key: 'model' },
        { title: 'RFID', key: 'rfid' },
        { title: 'Ubicación', key: 'location' },
        { title: 'Estado', key: 'status' },
        { title: '', key: 'actions', sortable: false, align: 'end' },
      ]"
      items-per-page="20"
    >
      <template #item.brand="{ item }">
        <div class="d-flex align-center">
          <v-avatar size="32" color="grey-lighten-3" class="mr-3">
            <v-icon size="18" color="primary">mdi-tools</v-icon>
          </v-avatar>
          <span class="font-weight-medium">{{ item.brand }}</span>
        </div>
      </template>
      <template #item.rfid="{ item }">
        <code class="text-body-2">{{ item.rfid }}</code>
      </template>
      <template #item.status="{ item }">
        <v-chip :color="statusColor(item.status)" size="small" variant="tonal">
          {{ statusLabel(item.status) }}
        </v-chip>
      </template>
      <template #item.actions="{ item }">
        <v-btn icon="mdi-pencil-outline" size="small" variant="text" @click="openEdit(item)" />
        <v-btn icon="mdi-archive-arrow-down-outline" size="small" variant="text" color="error" @click="retire(item)" />
      </template>
      <template #no-data>
        <EmptyState
          icon="mdi-toolbox-outline"
          title="Sin herramientas"
          description="Registra una con el botón de arriba — el RFID se captura solo pasando el tag en una estación."
        />
      </template>
    </v-data-table>

    <div v-else>
      <EmptyState
        v-if="!loading && filtered.length === 0"
        icon="mdi-toolbox-outline"
        title="Sin herramientas"
        description="Registra una con el botón Nueva herramienta."
      />
      <v-list v-else lines="three" class="pa-0">
        <template v-for="(t, i) in filtered" :key="t.id">
          <v-list-item @click="openEdit(t)">
            <template #prepend>
              <v-avatar color="grey-lighten-3" size="40">
                <v-icon color="primary">mdi-tools</v-icon>
              </v-avatar>
            </template>
            <v-list-item-title class="font-weight-bold">{{ t.brand }} {{ t.model }}</v-list-item-title>
            <v-list-item-subtitle>
              <div><code>{{ t.rfid }}</code> · {{ t.location || "sin ubicación" }}</div>
              <v-chip size="x-small" :color="statusColor(t.status)" variant="tonal" class="mt-1">
                {{ statusLabel(t.status) }}
              </v-chip>
            </v-list-item-subtitle>
            <template #append>
              <v-btn icon="mdi-archive-arrow-down-outline" size="small" variant="text" color="error" @click.stop="retire(t)" />
            </template>
          </v-list-item>
          <v-divider v-if="i < filtered.length - 1" />
        </template>
      </v-list>
    </div>
  </v-card>

  <v-dialog v-model="dialog" :max-width="mdAndUp ? 600 : '100%'" :fullscreen="!mdAndUp" persistent scrollable>
    <v-card rounded="lg">
      <v-card-title class="d-flex align-center pa-5">
        <v-icon class="mr-2" color="primary">{{ editing ? "mdi-pencil-outline" : "mdi-plus-circle-outline" }}</v-icon>
        <span class="font-weight-bold">{{ editing ? "Editar herramienta" : "Nueva herramienta" }}</span>
        <v-spacer />
        <v-btn icon="mdi-close" variant="text" @click="closeDialog" />
      </v-card-title>
      <v-divider />
      <v-card-text class="pa-5">
        <v-row dense>
          <v-col cols="12" sm="6"><v-text-field v-model="form.brand" label="Marca" prepend-inner-icon="mdi-tag-outline" /></v-col>
          <v-col cols="12" sm="6"><v-text-field v-model="form.model" label="Modelo" prepend-inner-icon="mdi-toolbox-outline" /></v-col>
        </v-row>
        <v-textarea v-model="form.description" label="Descripción" rows="2" auto-grow />
        <v-select
          v-model="captureStationSn"
          :items="stationItems"
          label="Estación de captura"
          prepend-inner-icon="mdi-radar"
          density="comfortable"
        />
        <v-text-field
          v-model="form.rfid"
          label="RFID (tag)"
          prepend-inner-icon="mdi-card-bulleted-outline"
          hint="Pasa el tag en la estación elegida para capturar"
          persistent-hint
        />
        <v-text-field v-model="form.location" label="Ubicación / cajón" prepend-inner-icon="mdi-map-marker-outline" />
        <v-alert v-if="captureWarning" type="warning" variant="tonal" rounded="lg" class="mt-2" closable @click:close="captureWarning = ''">
          <strong>Tarjeta ya registrada.</strong>
          <div class="text-body-2">{{ captureWarning }}</div>
        </v-alert>
        <v-alert v-if="captureToast" type="success" variant="tonal" rounded="lg" class="mt-2">{{ captureToast }}</v-alert>
      </v-card-text>
      <v-divider />
      <v-card-actions class="pa-4">
        <v-spacer />
        <v-btn variant="text" @click="closeDialog">Cancelar</v-btn>
        <v-btn color="primary" variant="flat" prepend-icon="mdi-content-save-outline" @click="save">Guardar</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>
