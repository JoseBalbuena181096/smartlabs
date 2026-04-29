<script setup lang="ts">
import { onMounted, ref } from "vue";
import { api } from "../../api/client";

const stations = ref<any[]>([]);
const dialog = ref(false);
const form = ref<any>({ serial_number: "", alias: "" });

async function load() {
  const { data } = await api.get("/stations");
  stations.value = data;
}

async function save() {
  await api.post("/stations", form.value);
  dialog.value = false;
  form.value = { serial_number: "", alias: "" };
  load();
}

async function rename(s: any) {
  const alias = prompt("Alias:", s.alias || "");
  if (alias === null) return;
  await api.patch(`/stations/${s.id}`, { alias });
  load();
}

onMounted(load);
</script>

<template>
  <v-card>
    <v-card-title class="d-flex align-center">
      Estaciones
      <v-spacer />
      <v-btn color="primary" @click="dialog = true">Nueva</v-btn>
    </v-card-title>
    <v-data-table
      :items="stations"
      :headers="[
        { title: 'SN', key: 'serial_number' },
        { title: 'Alias', key: 'alias' },
        { title: 'Online', key: 'online' },
        { title: 'Último visto', key: 'last_seen' },
        { title: '', key: 'actions', sortable: false },
      ]"
      items-per-page="50"
    >
      <template #item.online="{ item }">
        <v-chip size="small" :color="item.online ? 'success' : 'grey'">
          {{ item.online ? "online" : "offline" }}
        </v-chip>
      </template>
      <template #item.actions="{ item }">
        <v-btn size="small" variant="text" @click="rename(item)">Renombrar</v-btn>
      </template>
    </v-data-table>
  </v-card>

  <v-dialog v-model="dialog" max-width="500">
    <v-card>
      <v-card-title>Nueva estación</v-card-title>
      <v-card-text>
        <v-text-field v-model="form.serial_number" label="Serial number (p.ej. SMART10003)" />
        <v-text-field v-model="form.alias" label="Alias (opcional)" />
      </v-card-text>
      <v-card-actions>
        <v-spacer />
        <v-btn @click="dialog = false">Cancelar</v-btn>
        <v-btn color="primary" @click="save">Guardar</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>
