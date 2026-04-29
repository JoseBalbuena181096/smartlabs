<script setup lang="ts">
import { onMounted, onBeforeUnmount, ref } from "vue";
import { api } from "../../api/client";
import { openAdminWs, type WsClient } from "../../ws/client";
import { useAuth } from "../../stores/auth";

const auth = useAuth();
const users = ref<any[]>([]);
const areas = ref<any[]>([]);
const search = ref("");
const dialog = ref(false);
const editing = ref<any | null>(null);
const form = ref<any>({ full_name: "", email: "", payroll_number: "", area_id: null, rfid: "", role: "staff" });
const captureMode = ref(false);
const captureToast = ref("");
let ws: WsClient | null = null;

async function load() {
  const { data } = await api.get("/users", { params: { q: search.value || undefined } });
  users.value = data;
}

async function loadAreas() {
  const { data } = await api.get("/areas");
  areas.value = data;
}

function openCreate() {
  editing.value = null;
  form.value = { full_name: "", email: "", payroll_number: "", area_id: null, rfid: "", role: "staff" };
  dialog.value = true;
  enableCapture();
}

function openEdit(u: any) {
  editing.value = u;
  form.value = { ...u };
  dialog.value = true;
  enableCapture();
}

function enableCapture() {
  if (ws) return;
  captureMode.value = true;
  ws = openAdminWs(auth.token!, true, (ev) => {
    if (ev.type === "tag.unknown") {
      form.value.rfid = ev.rfid;
      captureToast.value = `RFID capturado: ${ev.rfid} (estación ${ev.station_sn})`;
    }
  });
}

function closeDialog() {
  dialog.value = false;
  ws?.close();
  ws = null;
  captureMode.value = false;
}

async function save() {
  if (editing.value) {
    await api.patch(`/users/${editing.value.id}`, form.value);
  } else {
    await api.post("/users", form.value);
  }
  closeDialog();
  load();
}

async function remove(u: any) {
  if (!confirm(`¿Eliminar a ${u.full_name}?`)) return;
  await api.delete(`/users/${u.id}`);
  load();
}

onMounted(() => {
  load();
  loadAreas();
});
onBeforeUnmount(() => ws?.close());
</script>

<template>
  <v-card>
    <v-card-title class="d-flex align-center">
      Usuarios
      <v-spacer />
      <v-text-field
        v-model="search"
        label="Buscar"
        density="compact"
        hide-details
        single-line
        style="max-width: 300px"
        @keyup.enter="load"
      />
      <v-btn color="primary" class="ml-2" @click="openCreate">Nuevo</v-btn>
    </v-card-title>
    <v-data-table
      :items="users"
      :headers="[
        { title: 'Nombre', key: 'full_name' },
        { title: 'Email', key: 'email' },
        { title: 'Nómina', key: 'payroll_number' },
        { title: 'RFID', key: 'rfid' },
        { title: 'Rol', key: 'role' },
        { title: '', key: 'actions', sortable: false },
      ]"
      items-per-page="20"
    >
      <template #item.actions="{ item }">
        <v-btn icon="mdi-pencil" size="small" variant="text" @click="openEdit(item)" />
        <v-btn icon="mdi-delete" size="small" variant="text" color="error" @click="remove(item)" />
      </template>
    </v-data-table>
  </v-card>

  <v-dialog v-model="dialog" max-width="600" persistent>
    <v-card>
      <v-card-title>{{ editing ? "Editar usuario" : "Nuevo usuario" }}</v-card-title>
      <v-card-text>
        <v-text-field v-model="form.full_name" label="Nombre completo" />
        <v-text-field v-model="form.email" label="Email" type="email" />
        <v-text-field v-model="form.payroll_number" label="Nómina" />
        <v-select v-model="form.area_id" :items="areas" item-title="name" item-value="id" label="Área" clearable />
        <v-text-field v-model="form.rfid" label="RFID (NFC)" :hint="captureMode ? 'Pasa la tarjeta en cualquier estación para capturar' : ''" persistent-hint />
        <v-select v-model="form.role" :items="['staff','admin']" label="Rol" />
        <v-text-field v-if="form.role === 'admin'" v-model="form.password" label="Contraseña (admins)" type="password" />
        <v-alert v-if="captureToast" type="success" variant="tonal" class="mt-2">{{ captureToast }}</v-alert>
      </v-card-text>
      <v-card-actions>
        <v-spacer />
        <v-btn @click="closeDialog">Cancelar</v-btn>
        <v-btn color="primary" @click="save">Guardar</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>
