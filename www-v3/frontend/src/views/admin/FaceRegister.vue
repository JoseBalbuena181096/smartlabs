<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import { useDisplay } from "vuetify";

import PageHeader from "../../components/PageHeader.vue";
import EmptyState from "../../components/EmptyState.vue";
import { useConfirm } from "../../composables/useConfirm";
import { useSnack } from "../../composables/useSnack";
import {
  faceApi,
  type CaptureTickResult,
  type FaceUserStatus,
} from "../../api/face";

const { mdAndUp } = useDisplay();
const { confirm } = useConfirm();
const snack = useSnack();

const users = ref<FaceUserStatus[]>([]);
const loading = ref(false);
const search = ref("");
const selected = ref<FaceUserStatus | null>(null);

const capturing = ref(false);
const lastTick = ref<CaptureTickResult | null>(null);
const snapshotTs = ref(Date.now());
let pollTimer: number | null = null;
let snapshotTimer: number | null = null;

const POSITION_LABELS: Record<string, { label: string; icon: string }> = {
  frontal: { label: "Frontal", icon: "mdi-circle-outline" },
  izquierda: { label: "Izquierda", icon: "mdi-arrow-left-bold" },
  derecha: { label: "Derecha", icon: "mdi-arrow-right-bold" },
  arriba: { label: "Arriba", icon: "mdi-arrow-up-bold" },
  abajo: { label: "Abajo", icon: "mdi-arrow-down-bold" },
};
const ALL_POSITIONS = ["frontal", "izquierda", "derecha", "arriba", "abajo"];

const filtered = computed(() => {
  if (!search.value.trim()) return users.value;
  const q = search.value.trim().toLowerCase();
  return users.value.filter(
    (u) =>
      u.full_name.toLowerCase().includes(q) ||
      u.email.toLowerCase().includes(q) ||
      u.rfid.toLowerCase().includes(q),
  );
});

const captured = computed(() => lastTick.value?.captured ?? []);
const progress = computed(() => lastTick.value?.progress_percent ?? 0);
const instruction = computed(
  () => lastTick.value?.instruction ?? "Pulsa Iniciar para comenzar",
);

const distanceLabel = computed(() => {
  const d = lastTick.value?.distance;
  if (!d || d === "unknown") return null;
  return {
    far: { text: "Demasiado lejos", color: "warning", icon: "mdi-arrow-expand-up" },
    okay: { text: "Acércate un poco más", color: "warning", icon: "mdi-arrow-expand-up" },
    good: { text: "Distancia perfecta", color: "success", icon: "mdi-check-circle" },
    close: { text: "Distancia óptima", color: "success", icon: "mdi-check-circle" },
    too_close: { text: "Aléjate un poco", color: "error", icon: "mdi-arrow-collapse-down" },
  }[d];
});

// Estado del óvalo guía: cambia color según hay rostro y a qué distancia.
// Sin bbox real-time (saltaba mucho con polling 600ms) — el óvalo es la
// única referencia visual y se "rellena" verde cuando todo está bien.
const ovalState = computed(() => {
  const t = lastTick.value;
  if (!t || t.status === "no_face") return "idle";
  if (t.distance === "too_close" || t.distance === "far") return "warn";
  if (t.distance === "good" || t.distance === "close") return "ok";
  return "detect";
});

const cameraFrameClass = computed(() => {
  const t = lastTick.value;
  if (!t) return "";
  if (t.status === "no_face" || t.status === "no_camera") return "face-camera--idle";
  if (t.distance === "good" || t.distance === "close") return "face-camera--ok";
  if (t.distance === "too_close" || t.distance === "far") return "face-camera--warn";
  return "face-camera--detect";
});

async function load() {
  loading.value = true;
  try {
    users.value = await faceApi.listUsers();
    if (selected.value) {
      const updated = users.value.find((u) => u.user_id === selected.value!.user_id);
      if (updated) selected.value = updated;
    }
  } finally {
    loading.value = false;
  }
}

function startSnapshotLoop() {
  stopSnapshotLoop();
  snapshotTs.value = Date.now();
}
function stopSnapshotLoop() {
  if (snapshotTimer) {
    clearTimeout(snapshotTimer);
    snapshotTimer = null;
  }
}
// Dispara el siguiente snapshot SOLO cuando el actual cargó (o falló).
// El RTSP tarda ~1.5s por frame; con setInterval fijo el browser cancela
// los previos (ERR_ABORTED) y nunca pinta nada.
function onSnapshotLoaded() {
  if (snapshotTimer) clearTimeout(snapshotTimer);
  snapshotTimer = window.setTimeout(() => {
    snapshotTs.value = Date.now();
  }, 200);
}
function onSnapshotError() {
  if (snapshotTimer) clearTimeout(snapshotTimer);
  snapshotTimer = window.setTimeout(() => {
    snapshotTs.value = Date.now();
  }, 1500);
}

async function startCapture() {
  if (!selected.value) return;
  try {
    await faceApi.registerStart(selected.value.user_id);
  } catch (e: any) {
    snack.error("No se pudo iniciar el registro: " + (e.response?.data?.detail || e.message));
    return;
  }
  capturing.value = true;
  lastTick.value = null;
  pollTimer = window.setInterval(pollTick, 600);
}

async function pollTick() {
  if (!selected.value) return;
  try {
    const tick = await faceApi.captureTick(selected.value.user_id);
    lastTick.value = tick;
    if (tick.just_captured) {
      snack.success(`Capturado: ${POSITION_LABELS[tick.just_captured]?.label || tick.just_captured}`);
    }
    if (tick.ready_to_commit) {
      await commitCapture();
    }
  } catch (e: any) {
    // Si la sesión expira en backend, paramos el loop.
    console.warn("capture tick failed", e);
  }
}

async function commitCapture() {
  if (!selected.value) return;
  stopPolling();
  try {
    const res = await faceApi.registerCommit(selected.value.user_id);
    snack.success(`Rostro registrado: ${res.vectors_count} posiciones`);
    capturing.value = false;
    lastTick.value = null;
    await load();
  } catch (e: any) {
    snack.error("No se pudo guardar: " + (e.response?.data?.detail || e.message));
  }
}

async function cancelCapture() {
  stopPolling();
  if (selected.value) {
    try {
      await faceApi.registerCancel(selected.value.user_id);
    } catch {}
  }
  capturing.value = false;
  lastTick.value = null;
}

function stopPolling() {
  if (pollTimer) {
    clearInterval(pollTimer);
    pollTimer = null;
  }
}

async function deleteFace(u: FaceUserStatus) {
  const ok = await confirm(
    `¿Borrar todos los embeddings de ${u.full_name}? Esta acción no se puede deshacer.`,
    { title: "Eliminar rostro", confirmText: "Eliminar", color: "error" },
  );
  if (!ok) return;
  try {
    await faceApi.deleteFace(u.user_id);
    snack.success("Rostro eliminado");
    await load();
  } catch (e: any) {
    snack.error("No se pudo eliminar: " + (e.response?.data?.detail || e.message));
  }
}

function selectUser(u: FaceUserStatus) {
  if (capturing.value) cancelCapture();
  selected.value = u;
}

onMounted(async () => {
  await load();
  startSnapshotLoop();
});
onBeforeUnmount(() => {
  stopPolling();
  stopSnapshotLoop();
  if (capturing.value && selected.value) {
    faceApi.registerCancel(selected.value.user_id).catch(() => {});
  }
});
</script>

<template>
  <PageHeader
    title="Reconocimiento facial"
    subtitle="Registra rostros para que los empleados puedan abrir sesión sin tag"
    icon="mdi-face-recognition"
  />

  <v-row dense>
    <!-- ── Panel izquierdo: lista de usuarios ───────────────────── -->
    <v-col cols="12" md="5" lg="4">
      <v-card rounded="lg" elevation="1">
        <v-card-text class="pa-3 pa-sm-4">
          <v-text-field
            v-model="search"
            density="comfortable"
            variant="outlined"
            placeholder="Buscar usuario, email o tag…"
            prepend-inner-icon="mdi-magnify"
            hide-details
            clearable
          />
        </v-card-text>
        <v-divider />

        <v-progress-linear v-if="loading" indeterminate color="primary" />

        <EmptyState
          v-if="!loading && filtered.length === 0"
          icon="mdi-account-off-outline"
          title="Sin usuarios"
          description="No hay coincidencias para tu búsqueda"
        />

        <v-list lines="two" density="comfortable" class="pa-0">
          <v-list-item
            v-for="u in filtered"
            :key="u.user_id"
            :active="selected?.user_id === u.user_id"
            class="px-3 py-2"
            @click="selectUser(u)"
          >
            <template #prepend>
              <v-avatar size="36" :color="u.has_face ? 'success' : 'grey-lighten-2'">
                <v-icon size="18" :color="u.has_face ? 'white' : 'grey-darken-1'">
                  {{ u.has_face ? "mdi-face-recognition" : "mdi-account-outline" }}
                </v-icon>
              </v-avatar>
            </template>
            <v-list-item-title class="font-weight-medium">{{ u.full_name }}</v-list-item-title>
            <v-list-item-subtitle class="text-caption">
              <span v-if="u.has_face" class="text-success">
                {{ u.positions.length }} {{ u.positions.length === 1 ? "posición" : "posiciones" }} ·
              </span>
              <span>tag {{ u.rfid }}</span>
            </v-list-item-subtitle>
          </v-list-item>
        </v-list>
      </v-card>
    </v-col>

    <!-- ── Panel derecho: cámara + registro ─────────────────────── -->
    <v-col cols="12" md="7" lg="8">
      <v-card v-if="!selected" rounded="lg" elevation="1" class="pa-6 text-center">
        <EmptyState
          icon="mdi-arrow-left-bold"
          title="Selecciona un usuario"
          description="Elige a quién registrar o eliminar de la lista de la izquierda"
        />
      </v-card>

      <v-card v-else rounded="lg" elevation="1">
        <v-card-title class="d-flex align-center pa-3 pa-sm-4 flex-wrap" style="gap: 8px">
          <v-avatar size="40" :color="selected.has_face ? 'success' : 'grey-lighten-2'" class="mr-2">
            <v-icon :color="selected.has_face ? 'white' : 'grey-darken-1'">
              {{ selected.has_face ? "mdi-face-recognition" : "mdi-account-outline" }}
            </v-icon>
          </v-avatar>
          <div>
            <div class="text-h6 font-weight-bold">{{ selected.full_name }}</div>
            <div class="text-caption text-medium-emphasis">
              {{ selected.email }} · tag {{ selected.rfid }}
            </div>
          </div>
          <v-spacer />
          <v-chip
            :color="selected.has_face ? 'success' : 'default'"
            variant="tonal"
            size="small"
          >
            {{ selected.has_face ? `${selected.positions.length} posiciones` : "Sin rostro" }}
          </v-chip>
        </v-card-title>

        <v-divider />

        <v-card-text class="pa-3 pa-sm-4">
          <!-- Cámara en vivo con overlays -->
          <div class="face-camera-wrap" :class="cameraFrameClass">
            <img
              :src="faceApi.snapshotUrl(snapshotTs)"
              alt="Vista de cámara"
              class="face-camera"
              :class="{ 'face-camera--flash': lastTick?.just_captured }"
              @load="onSnapshotLoaded"
              @error="onSnapshotError"
            />

            <!-- Zona objetivo: óvalo central donde debe colocarse la cara.
                 Es la única guía visual: el bbox real-time saltaba mucho con
                 el polling cada 600ms y daba sensación de retraso. El óvalo
                 cambia de color según calidad (gris/amarillo/verde/rojo). -->
            <div v-if="capturing" class="face-target" :class="`face-target--${ovalState}`">
              <div class="face-target-oval" />
              <div class="face-target-corner face-target-corner--tl" />
              <div class="face-target-corner face-target-corner--tr" />
              <div class="face-target-corner face-target-corner--bl" />
              <div class="face-target-corner face-target-corner--br" />
              <div v-if="lastTick?.current_position" class="face-target-label">
                {{ POSITION_LABELS[lastTick.current_position]?.label || lastTick.current_position }}
              </div>
            </div>

            <!-- Indicador de "no rostro" sobrepuesto -->
            <div v-if="capturing && lastTick?.status === 'no_face'" class="face-no-face">
              <v-icon size="48">mdi-account-question-outline</v-icon>
              <div class="text-h6 mt-2">No se detecta rostro</div>
              <div class="text-body-2">Coloca tu cara dentro del óvalo</div>
            </div>
          </div>

          <!-- Indicador de distancia -->
          <v-alert
            v-if="capturing && distanceLabel"
            :type="distanceLabel.color as any"
            variant="tonal"
            density="compact"
            :icon="distanceLabel.icon"
            class="mt-3"
          >
            {{ distanceLabel.text }}
          </v-alert>

          <!-- Estado del registro -->
          <div v-if="capturing" class="mt-4">
            <div class="d-flex align-center mb-2" style="gap: 8px">
              <v-icon color="primary">mdi-information-outline</v-icon>
              <span class="text-subtitle-2">{{ instruction }}</span>
            </div>
            <v-progress-linear
              :model-value="progress"
              color="primary"
              height="8"
              rounded
            />
            <div class="text-caption text-medium-emphasis mt-1">
              {{ captured.length }} / {{ ALL_POSITIONS.length }} posiciones · {{ progress }}%
            </div>

            <!-- Indicadores de posición -->
            <div class="d-flex flex-wrap mt-3" style="gap: 8px">
              <v-chip
                v-for="pos in ALL_POSITIONS"
                :key="pos"
                :color="captured.includes(pos) ? 'success' : 'default'"
                :variant="captured.includes(pos) ? 'flat' : 'tonal'"
                :prepend-icon="captured.includes(pos) ? 'mdi-check-circle' : POSITION_LABELS[pos].icon"
                size="small"
              >
                {{ POSITION_LABELS[pos].label }}
              </v-chip>
            </div>

            <div class="mt-4 d-flex flex-wrap" style="gap: 8px">
              <v-btn variant="outlined" color="error" prepend-icon="mdi-close" @click="cancelCapture">
                Cancelar
              </v-btn>
              <v-btn
                v-if="captured.length >= 3 && !lastTick?.ready_to_commit"
                variant="elevated"
                color="primary"
                prepend-icon="mdi-content-save"
                @click="commitCapture"
              >
                Guardar con {{ captured.length }} posiciones
              </v-btn>
            </div>
          </div>

          <!-- Estado idle -->
          <div v-else class="mt-4 d-flex flex-wrap" style="gap: 8px">
            <v-btn
              variant="elevated"
              color="primary"
              size="large"
              prepend-icon="mdi-record-circle-outline"
              @click="startCapture"
            >
              {{ selected.has_face ? "Re-registrar rostro" : "Iniciar registro" }}
            </v-btn>
            <v-btn
              v-if="selected.has_face"
              variant="text"
              color="error"
              prepend-icon="mdi-delete-outline"
              @click="deleteFace(selected)"
            >
              Eliminar rostro
            </v-btn>
          </div>

          <v-alert
            v-if="!capturing && selected.has_face"
            type="info"
            variant="tonal"
            class="mt-4"
            density="compact"
          >
            Posiciones registradas:
            <strong>{{ selected.positions.join(", ") }}</strong>
            <span v-if="selected.last_captured_at">
              · última captura {{ new Date(selected.last_captured_at).toLocaleString("es-MX") }}
            </span>
          </v-alert>
        </v-card-text>
      </v-card>
    </v-col>
  </v-row>
</template>

<style scoped>
.face-camera-wrap {
  position: relative;
  background: #000;
  border-radius: 12px;
  overflow: hidden;
  aspect-ratio: 4 / 3;
  width: 100%;
  max-width: 720px;
  margin: 0 auto;
  border: 3px solid transparent;
  transition: border-color .3s ease;
}
.face-camera-wrap.face-camera--idle  { border-color: rgba(255,255,255,.1); }
.face-camera-wrap.face-camera--detect { border-color: #f9ab00; }
.face-camera-wrap.face-camera--warn  { border-color: #f57c00; }
.face-camera-wrap.face-camera--ok    { border-color: #34a853; }

.face-camera {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}
.face-camera--flash {
  animation: cam-flash .35s ease-out;
}
@keyframes cam-flash {
  0%   { filter: brightness(1.5); }
  100% { filter: brightness(1);   }
}

/* ────── Zona objetivo: oval central donde debe quedar la cara ────── */
.face-target {
  position: absolute; inset: 0;
  pointer-events: none;
}
.face-target-oval {
  position: absolute;
  top: 50%; left: 50%;
  width: 42%;
  aspect-ratio: 3 / 4;
  transform: translate(-50%, -50%);
  border: 3px dashed rgba(255,255,255,.6);
  border-radius: 50% / 50%;
  box-shadow: 0 0 0 9999px rgba(0,0,0,.22);
  transition: border-color .25s ease, border-style .25s ease, box-shadow .25s ease;
}
/* Estados del óvalo guía */
.face-target--idle .face-target-oval {
  border-color: rgba(255,255,255,.45);
}
.face-target--detect .face-target-oval {
  border-color: #f9ab00;
  border-style: solid;
}
.face-target--warn .face-target-oval {
  border-color: #f57c00;
  border-style: solid;
}
.face-target--ok .face-target-oval {
  border-color: #34a853;
  border-style: solid;
  box-shadow: 0 0 0 9999px rgba(0,0,0,.22), 0 0 24px 4px rgba(52, 168, 83, .55);
}
.face-target-corner {
  position: absolute;
  width: 28px; height: 28px;
  border: 3px solid rgba(255,255,255,.8);
}
.face-target-corner--tl { top: 12px; left: 12px; border-right: 0; border-bottom: 0; border-radius: 6px 0 0 0; }
.face-target-corner--tr { top: 12px; right: 12px; border-left: 0; border-bottom: 0; border-radius: 0 6px 0 0; }
.face-target-corner--bl { bottom: 12px; left: 12px; border-right: 0; border-top: 0; border-radius: 0 0 0 6px; }
.face-target-corner--br { bottom: 12px; right: 12px; border-left: 0; border-top: 0; border-radius: 0 0 6px 0; }
/* Label de posición detectada actualmente — flotante encima del óvalo */
.face-target-label {
  position: absolute;
  top: calc(50% - 32% - 16px);
  left: 50%;
  transform: translate(-50%, -100%);
  background: rgba(0, 0, 0, .65);
  color: #fff;
  font-weight: 700;
  font-size: .85rem;
  padding: 4px 12px;
  border-radius: 999px;
  white-space: nowrap;
  text-transform: capitalize;
}

/* Overlay "no rostro" centrado encima de la cámara */
.face-no-face {
  position: absolute; inset: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: rgba(255,255,255,.85);
  background: rgba(0,0,0,.45);
  text-align: center;
}
</style>
