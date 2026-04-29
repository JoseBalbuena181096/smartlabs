<script setup lang="ts">
import { onMounted, onBeforeUnmount, ref } from "vue";
import { api } from "../../api/client";
import { openAdminWs, type WsClient } from "../../ws/client";
import { useAuth } from "../../stores/auth";

const auth = useAuth();
const tools = ref<any[]>([]);
const search = ref("");
const dialog = ref(false);
const editing = ref<any | null>(null);
const form = ref<any>({ brand: "", model: "", description: "", rfid: "", location: "" });
const captureToast = ref("");
let ws: WsClient | null = null;

async function load() {
  const { data } = await api.get("/tools", { params: { q: search.value || undefined } });
  tools.value = data;
}

function openCreate() {
  editing.value = null;
  form.value = { brand: "", model: "", description: "", rfid: "", location: "" };
  dialog.value = true;
  enableCapture();
}

function openEdit(t: any) {
  editing.value = t;
  form.value = { ...t };
  dialog.value = true;
  enableCapture();
}

function enableCapture() {
  if (ws) return;
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
  captureToast.value = "";
}

async function save() {
  if (editing.value) {
    await api.patch(`/tools/${editing.value.id}`, form.value);
  } else {
    await api.post("/tools", form.value);
  }
  closeDialog();
  load();
}

async function retire(t: any) {
  if (!confirm(`¿Retirar ${t.brand} ${t.model}?\nSe cerrará automáticamente cualquier préstamo activo de esta herramienta.`)) return;
  await api.delete(`/tools/${t.id}`);
  load();
}

onMounted(load);
onBeforeUnmount(() => ws?.close());
</script>

<template>
  <v-card>
    <v-card-title class="d-flex align-center">
      Herramientas
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
      <v-btn color="primary" class="ml-2" @click="openCreate">Nueva</v-btn>
    </v-card-title>
    <v-data-table
      :items="tools"
      :headers="[
        { title: 'Marca', key: 'brand' },
        { title: 'Modelo', key: 'model' },
        { title: 'RFID', key: 'rfid' },
        { title: 'Ubicación', key: 'location' },
        { title: 'Estado', key: 'status' },
        { title: '', key: 'actions', sortable: false },
      ]"
      items-per-page="20"
    >
      <template #item.status="{ item }">
        <v-chip
          size="small"
          :color="item.status === 'in_stock' ? 'success' : item.status === 'on_loan' ? 'warning' : 'grey'"
        >
          {{ item.status }}
        </v-chip>
      </template>
      <template #item.actions="{ item }">
        <v-btn icon="mdi-pencil" size="small" variant="text" @click="openEdit(item)" />
        <v-btn icon="mdi-archive-arrow-down" size="small" variant="text" color="error" @click="retire(item)" />
      </template>
    </v-data-table>
  </v-card>

  <v-dialog v-model="dialog" max-width="600" persistent>
    <v-card>
      <v-card-title>{{ editing ? "Editar herramienta" : "Nueva herramienta" }}</v-card-title>
      <v-card-text>
        <v-text-field v-model="form.brand" label="Marca" />
        <v-text-field v-model="form.model" label="Modelo" />
        <v-textarea v-model="form.description" label="Descripción" rows="2" />
        <v-text-field
          v-model="form.rfid"
          label="RFID (tag)"
          hint="Pasa el tag en cualquier estación para capturar"
          persistent-hint
        />
        <v-text-field v-model="form.location" label="Ubicación / cajón" />
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
