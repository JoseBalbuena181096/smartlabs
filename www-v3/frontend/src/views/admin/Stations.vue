<script setup lang="ts">
import { onMounted, ref } from "vue";
import { useDisplay } from "vuetify";
import { api } from "../../api/client";
import { useSnack } from "../../composables/useSnack";
import PageHeader from "../../components/PageHeader.vue";
import EmptyState from "../../components/EmptyState.vue";

const { mdAndUp } = useDisplay();
const snack = useSnack();
const stations = ref<any[]>([]);
const dialog = ref(false);
const editing = ref<any | null>(null);
const form = ref<any>({ serial_number: "", alias: "" });

function fmt(s: string | null) {
  if (!s) return "—";
  return new Date(s).toLocaleString("es-MX", { dateStyle: "short", timeStyle: "short" });
}

async function load() {
  const { data } = await api.get("/stations");
  stations.value = data;
}

function openCreate() {
  editing.value = null;
  form.value = { serial_number: "", alias: "", face_enabled: true };
  dialog.value = true;
}

function openEdit(s: any) {
  editing.value = s;
  form.value = { serial_number: s.serial_number, alias: s.alias || "", face_enabled: s.face_enabled };
  dialog.value = true;
}

async function save() {
  try {
    if (editing.value) {
      await api.patch(`/stations/${editing.value.id}`, { alias: form.value.alias });
      snack.success("Estación actualizada");
    } else {
      await api.post("/stations", form.value);
      snack.success("Estación registrada");
    }
    dialog.value = false;
    load();
  } catch (e: any) {
    snack.error(e?.response?.data?.detail || "No se pudo guardar");
  }
}

async function toggleFace(s: any, val: boolean) {
  // Optimista: actualizamos UI antes de la respuesta para que el switch
  // no se sienta lento. Si falla, rollback + snack de error.
  const prev = s.face_enabled;
  s.face_enabled = val;
  try {
    await api.patch(`/stations/${s.id}`, { face_enabled: val });
    snack.success(val ? "Reconocimiento facial activado" : "Reconocimiento facial desactivado");
  } catch (e: any) {
    s.face_enabled = prev;
    snack.error(e?.response?.data?.detail || "No se pudo cambiar");
  }
}

onMounted(load);
</script>

<template>
  <PageHeader title="Estaciones" subtitle="ESP32 / lectores físicos del almacén" icon="mdi-radar">
    <v-btn color="primary" prepend-icon="mdi-plus" rounded="lg" @click="openCreate">Nueva estación</v-btn>
  </PageHeader>

  <v-row dense>
    <v-col v-for="s in stations" :key="s.id" cols="12" sm="6" lg="4">
      <v-card
        rounded="lg"
        elevation="1"
        class="tec-station-card"
      >
        <v-card-text class="pa-5">
          <div class="d-flex align-start">
            <span class="tec-pulse" :class="{ 'tec-pulse--off': !s.online }" style="margin-top: 6px" />
            <div class="ml-3 flex-grow-1">
              <a
                :href="`/station/${s.serial_number}`"
                target="_blank"
                rel="noopener"
                class="d-flex align-center text-decoration-none"
                style="color: inherit"
              >
                <span class="text-h6 font-weight-bold">{{ s.alias || s.serial_number }}</span>
                <v-icon size="16" class="ml-2 text-medium-emphasis">mdi-open-in-new</v-icon>
              </a>
              <div v-if="s.alias" class="text-caption text-medium-emphasis">{{ s.serial_number }}</div>
              <div class="d-flex align-center mt-2 flex-wrap" style="gap: 10px">
                <v-chip size="small" :color="s.online ? 'success' : 'grey'" variant="tonal">
                  {{ s.online ? "Online" : "Offline" }}
                </v-chip>
                <span class="text-caption text-medium-emphasis">
                  Último visto · {{ fmt(s.last_seen) }}
                </span>
              </div>
            </div>
            <v-btn
              icon="mdi-pencil-outline"
              size="small"
              variant="text"
              @click.stop.prevent="openEdit(s)"
            />
          </div>
          <v-divider class="my-3" />
          <div class="d-flex align-center">
            <v-icon :color="s.face_enabled ? 'primary' : 'grey'" class="mr-2">
              mdi-face-recognition
            </v-icon>
            <div class="flex-grow-1">
              <div class="text-body-2 font-weight-medium">Reconocimiento facial</div>
              <div class="text-caption text-medium-emphasis">
                {{ s.face_enabled ? "La cámara abre/cierra sesiones" : "Solo por tarjeta RFID" }}
              </div>
            </div>
            <v-switch
              :model-value="s.face_enabled"
              color="primary"
              hide-details
              density="compact"
              inset
              @update:model-value="(v) => toggleFace(s, !!v)"
            />
          </div>
        </v-card-text>
      </v-card>
    </v-col>

    <v-col v-if="stations.length === 0" cols="12">
      <v-card rounded="lg" elevation="0" border>
        <EmptyState
          icon="mdi-radar-off"
          title="Sin estaciones"
          description="Las estaciones se registran solas cuando un ESP32 se conecta al broker. También puedes crear una manualmente con el botón de arriba."
        />
      </v-card>
    </v-col>
  </v-row>

  <v-dialog v-model="dialog" :max-width="mdAndUp ? 520 : '100%'" :fullscreen="!mdAndUp" persistent>
    <v-card rounded="lg">
      <v-card-title class="pa-5 d-flex align-center">
        <v-icon class="mr-2" color="primary">mdi-radar</v-icon>
        <span class="font-weight-bold">{{ editing ? "Editar estación" : "Nueva estación" }}</span>
        <v-spacer />
        <v-btn icon="mdi-close" variant="text" @click="dialog = false" />
      </v-card-title>
      <v-divider />
      <v-card-text class="pa-5">
        <v-text-field
          v-model="form.serial_number"
          label="Serial number (p.ej. SMART10003)"
          prepend-inner-icon="mdi-identifier"
          :disabled="!!editing"
          :hint="editing ? 'El SN no se puede cambiar' : 'Coincide con el primer segmento del topic MQTT del ESP32'"
          persistent-hint
        />
        <v-text-field
          v-model="form.alias"
          label="Alias (opcional)"
          prepend-inner-icon="mdi-tag-outline"
          placeholder="Almacén principal, Caja A, etc."
        />
        <v-switch
          v-if="!editing"
          v-model="form.face_enabled"
          color="primary"
          inset
          hide-details
          density="comfortable"
          label="Activar reconocimiento facial"
          class="mt-2"
        />
      </v-card-text>
      <v-divider />
      <v-card-actions class="pa-4">
        <v-spacer />
        <v-btn variant="text" @click="dialog = false">Cancelar</v-btn>
        <v-btn color="primary" variant="flat" prepend-icon="mdi-content-save-outline" @click="save">Guardar</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>
