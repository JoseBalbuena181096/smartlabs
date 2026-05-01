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

const users = ref<any[]>([]);
const areas = ref<any[]>([]);
const stations = ref<any[]>([]);
const search = ref("");
const roleFilter = ref<string | null>(null);
const loading = ref(false);
const dialog = ref(false);
const editing = ref<any | null>(null);
const form = ref<any>({ full_name: "", email: "", payroll_number: "", area_id: null, rfid: "", role: "staff" });
const captureStationSn = ref<string | null>(null);
const captureToast = ref("");
const captureWarning = ref("");
let ws: WsClient | null = null;
let warningTimer: number | null = null;
let searchTimer: number | null = null;

const filtered = computed(() => {
  if (!roleFilter.value) return users.value;
  return users.value.filter((u) => u.role === roleFilter.value);
});

async function load() {
  loading.value = true;
  try {
    const { data } = await api.get("/users", { params: { q: search.value || undefined } });
    users.value = data;
  } finally {
    loading.value = false;
  }
}

async function loadAreas() {
  const { data } = await api.get("/areas");
  areas.value = data;
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
  form.value = { full_name: "", email: "", payroll_number: "", area_id: null, rfid: "", role: "staff" };
  captureToast.value = "";
  captureWarning.value = "";
  dialog.value = true;
  enableCapture();
}

function openEdit(u: any) {
  editing.value = u;
  form.value = { ...u };
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
  if (warningTimer) clearTimeout(warningTimer);
}

async function save() {
  try {
    if (editing.value) {
      await api.patch(`/users/${editing.value.id}`, form.value);
      snack.success(`Usuario "${form.value.full_name}" actualizado`);
    } else {
      await api.post("/users", form.value);
      snack.success(`Usuario "${form.value.full_name}" creado`);
    }
    closeDialog();
    load();
  } catch (e: any) {
    snack.error(e?.response?.data?.detail || "No se pudo guardar");
  }
}

async function remove(u: any) {
  const ok = await confirm(
    `¿Eliminar a "${u.full_name}"? Esta acción es reversible (solo desactiva al usuario).`,
    { title: "Eliminar usuario", confirmText: "Eliminar", color: "error" },
  );
  if (!ok) return;
  try {
    await api.delete(`/users/${u.id}`);
    snack.success(`Usuario "${u.full_name}" eliminado`);
    load();
  } catch (e: any) {
    snack.error(e?.response?.data?.detail || "No se pudo eliminar");
  }
}

const stationItems = computed(() => [
  { title: "Cualquier estación", value: null },
  ...stations.value.map((s) => ({
    title: `${s.serial_number}${s.alias ? " · " + s.alias : ""}${s.online ? " 🟢" : " ⚫"}`,
    value: s.serial_number,
  })),
]);

onMounted(() => {
  load();
  loadAreas();
  loadStations();
});
onBeforeUnmount(() => ws?.close());
</script>

<template>
  <PageHeader title="Usuarios" subtitle="Personas que usan o administran el almacén" icon="mdi-account-group-outline">
    <v-btn color="primary" prepend-icon="mdi-plus" rounded="lg" @click="openCreate">
      Nuevo usuario
    </v-btn>
  </PageHeader>

  <v-card rounded="lg" elevation="1">
    <div class="pa-3 pa-md-4 d-flex flex-wrap align-center" style="gap: 12px">
      <v-text-field
        v-model="search"
        prepend-inner-icon="mdi-magnify"
        placeholder="Buscar por nombre, email, nómina, RFID…"
        density="comfortable"
        hide-details
        variant="solo-filled"
        flat
        bg-color="grey-lighten-4"
        style="min-width: 240px; flex: 1 1 280px"
        clearable
      />
      <v-btn-toggle v-model="roleFilter" color="primary" density="comfortable" variant="outlined" divided>
        <v-btn :value="null">Todos</v-btn>
        <v-btn value="admin">Admin</v-btn>
        <v-btn value="staff">Staff</v-btn>
      </v-btn-toggle>
    </div>

    <v-divider />

    <!-- Tabla en md+ -->
    <v-data-table
      v-if="mdAndUp"
      :loading="loading"
      :items="filtered"
      :headers="[
        { title: 'Nombre', key: 'full_name' },
        { title: 'Email', key: 'email' },
        { title: 'Nómina', key: 'payroll_number' },
        { title: 'RFID', key: 'rfid' },
        { title: 'Rol', key: 'role' },
        { title: '', key: 'actions', sortable: false, align: 'end' },
      ]"
      items-per-page="20"
      class="tec-data-table"
    >
      <template #item.full_name="{ item }">
        <div class="d-flex align-center">
          <v-avatar size="32" color="primary" variant="tonal" class="mr-3">
            <span class="text-caption font-weight-bold">{{ (item.full_name || "?").slice(0, 1).toUpperCase() }}</span>
          </v-avatar>
          <span class="font-weight-medium">{{ item.full_name }}</span>
        </div>
      </template>
      <template #item.role="{ item }">
        <v-chip :color="item.role === 'admin' ? 'primary' : 'default'" size="small" variant="tonal">
          {{ item.role }}
        </v-chip>
      </template>
      <template #item.rfid="{ item }">
        <code class="text-body-2">{{ item.rfid }}</code>
      </template>
      <template #item.actions="{ item }">
        <v-btn icon="mdi-pencil-outline" size="small" variant="text" @click="openEdit(item)" />
        <v-btn icon="mdi-delete-outline" size="small" variant="text" color="error" @click="remove(item)" />
      </template>
      <template #no-data>
        <EmptyState
          icon="mdi-account-search-outline"
          title="No hay usuarios que coincidan"
          description="Prueba con otra búsqueda o crea un usuario nuevo con el botón de arriba."
        />
      </template>
    </v-data-table>

    <!-- Cards en mobile -->
    <div v-else>
      <EmptyState
        v-if="!loading && filtered.length === 0"
        icon="mdi-account-search-outline"
        title="No hay usuarios"
        description="Toca el botón Nuevo usuario para registrar el primero."
      />
      <v-list v-else lines="three" class="pa-0">
        <template v-for="(u, i) in filtered" :key="u.id">
          <v-list-item @click="openEdit(u)">
            <template #prepend>
              <v-avatar color="primary" variant="tonal" size="40">
                <span class="font-weight-bold">{{ (u.full_name || "?").slice(0, 1).toUpperCase() }}</span>
              </v-avatar>
            </template>
            <v-list-item-title class="font-weight-bold">{{ u.full_name }}</v-list-item-title>
            <v-list-item-subtitle>
              <div>{{ u.email }}</div>
              <div class="d-flex align-center mt-1" style="gap: 8px">
                <v-chip size="x-small" :color="u.role === 'admin' ? 'primary' : 'default'" variant="tonal">{{ u.role }}</v-chip>
                <code class="text-caption">{{ u.rfid }}</code>
              </div>
            </v-list-item-subtitle>
            <template #append>
              <v-btn icon="mdi-delete-outline" size="small" variant="text" color="error" @click.stop="remove(u)" />
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
        <v-icon class="mr-2" color="primary">{{ editing ? "mdi-account-edit-outline" : "mdi-account-plus-outline" }}</v-icon>
        <span class="font-weight-bold">{{ editing ? "Editar usuario" : "Nuevo usuario" }}</span>
        <v-spacer />
        <v-btn icon="mdi-close" variant="text" @click="closeDialog" />
      </v-card-title>
      <v-divider />
      <v-card-text class="pa-5">
        <v-text-field v-model="form.full_name" label="Nombre completo" prepend-inner-icon="mdi-account" />
        <v-text-field v-model="form.email" label="Email" type="email" prepend-inner-icon="mdi-email-outline" />
        <v-text-field v-model="form.payroll_number" label="Nómina" prepend-inner-icon="mdi-card-account-details-outline" />
        <v-select v-model="form.area_id" :items="areas" item-title="name" item-value="id" label="Área" prepend-inner-icon="mdi-domain" clearable />
        <v-select
          v-model="captureStationSn"
          :items="stationItems"
          label="Estación de captura"
          prepend-inner-icon="mdi-radar"
          density="comfortable"
        />
        <v-text-field v-model="form.rfid" label="RFID (NFC)" prepend-inner-icon="mdi-card-bulleted-outline" hint="Pasa la tarjeta en la estación elegida para capturar" persistent-hint />
        <v-select v-model="form.role" :items="['staff', 'admin']" label="Rol" prepend-inner-icon="mdi-shield-account-outline" />
        <v-text-field v-if="form.role === 'admin'" v-model="form.password" label="Contraseña (admins)" type="password" prepend-inner-icon="mdi-lock-outline" />
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
