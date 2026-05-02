<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import { useRoute } from "vue-router";
import { openStationWs, type WsClient } from "../../ws/client";

const route = useRoute();
const sn = (route.params.sn as string) || "";

interface HeldTool {
  loan_id: number;
  tool_id: number;
  brand: string | null;
  model: string | null;
  rfid: string;
  loaned_at: string | null;
  due_at: string | null;
}

const sessionUser = ref<any | null>(null);
const heldTools = ref<HeldTool[]>([]);
const lastAction = ref<{ kind: "prestado" | "devuelto"; tool: HeldTool } | null>(null);
const online = ref<boolean | null>(null);
const sessionStartedAt = ref<Date | null>(null);
const now = ref(new Date());
// Estado del reconocimiento facial: si la estación tiene face_enabled,
// muestra panel de cámara con bbox overlay (verde+nombre / rojo+desconocido).
const faceEnabled = ref<boolean>(false);
const faceMethod = ref<"face" | null>(null);
// Última detección live para dibujar el bbox y label.
const faceLive = ref<{
  recognized: boolean;
  bbox: [number, number, number, number] | null;
  frame_w: number;
  frame_h: number;
  user_name: string | null;
  score: number;
} | null>(null);
const camSnapshotTs = ref(Date.now());
let ws: WsClient | null = null;
let lastActionTimer: number | null = null;
let clockTimer: number | null = null;
let faceLiveTimer: number | null = null;
let camSnapshotTimer: number | null = null;

const clockText = computed(() =>
  now.value.toLocaleTimeString("es-MX", { hour: "2-digit", minute: "2-digit", second: "2-digit" }),
);
const dateText = computed(() =>
  now.value.toLocaleDateString("es-MX", { weekday: "long", day: "numeric", month: "long" }),
);
const sessionElapsed = computed(() => {
  if (!sessionStartedAt.value) return "";
  const sec = Math.floor((now.value.getTime() - sessionStartedAt.value.getTime()) / 1000);
  const m = Math.floor(sec / 60);
  const s = sec % 60;
  return `${m}:${s.toString().padStart(2, "0")}`;
});

const heroState = computed(() => {
  if (online.value === false) return "offline";
  if (sessionUser.value) return "active";
  return "idle";
});

function fmtDate(s: string | null): string {
  if (!s) return "—";
  const d = new Date(s);
  return d.toLocaleString("es-MX", { dateStyle: "short", timeStyle: "short" });
}

function flashAction(kind: "prestado" | "devuelto", tool: HeldTool) {
  lastAction.value = { kind, tool };
  if (lastActionTimer) clearTimeout(lastActionTimer);
  lastActionTimer = window.setTimeout(() => {
    lastAction.value = null;
  }, 4500);
}

function clearFaceLiveSoon(ms = 1500) {
  if (faceLiveTimer) clearTimeout(faceLiveTimer);
  faceLiveTimer = window.setTimeout(() => {
    faceLive.value = null;
  }, ms);
}

function handleEvent(ev: any) {
  switch (ev.type) {
    case "session.opened":
      sessionUser.value = ev.user;
      sessionStartedAt.value = new Date(ev.opened_at || Date.now());
      faceMethod.value = ev.method === "face" ? "face" : null;
      faceLive.value = null;
      heldTools.value = (ev.active_loans || []).map((al: any) => ({
        loan_id: al.loan_id,
        tool_id: al.tool.id,
        brand: al.tool.brand,
        model: al.tool.model,
        rfid: al.tool.rfid,
        loaned_at: al.loaned_at,
        due_at: al.due_at,
      }));
      break;

    case "session.closed":
      sessionUser.value = null;
      sessionStartedAt.value = null;
      heldTools.value = [];
      lastAction.value = null;
      faceMethod.value = null;
      faceLive.value = null;
      break;

    case "face.live":
      faceLive.value = {
        recognized: !!ev.recognized,
        bbox: ev.bbox || null,
        frame_w: ev.frame_w || 1280,
        frame_h: ev.frame_h || 720,
        user_name: ev.user?.name || null,
        score: ev.score || 0,
      };
      // TTL alto: el face-service tiene cooldown de hasta 6s post-acción.
      // Si bajamos esto, el bbox parpadea/desaparece entre ticks.
      clearFaceLiveSoon(7000);
      break;

    case "station.face_changed":
      faceEnabled.value = !!ev.face_enabled;
      if (!ev.face_enabled) {
        faceLive.value = null;
      }
      break;

    case "loan.created": {
      const tool: HeldTool = {
        loan_id: ev.loan_id,
        tool_id: ev.tool.id,
        brand: ev.tool.brand,
        model: ev.tool.model,
        rfid: ev.tool.rfid,
        loaned_at: ev.loaned_at,
        due_at: ev.due_at,
      };
      heldTools.value.unshift(tool);
      flashAction("prestado", tool);
      break;
    }

    case "loan.returned": {
      const idx = heldTools.value.findIndex((t) => t.loan_id === ev.loan_id);
      const removed =
        idx >= 0
          ? heldTools.value.splice(idx, 1)[0]
          : ({
              loan_id: ev.loan_id,
              tool_id: ev.tool.id,
              brand: ev.tool.brand,
              model: ev.tool.model,
              rfid: ev.tool.rfid,
              loaned_at: null,
              due_at: null,
            } as HeldTool);
      flashAction("devuelto", removed);
      break;
    }

    case "station.online":
      online.value = true;
      break;
    case "station.offline":
      online.value = false;
      break;
  }
}

async function hydrateState() {
  // Hidrata el estado actual del kiosko al cargar — sin esto el WS solo
  // recibe eventos futuros y la pantalla queda "vacía" si ya había sesión.
  try {
    const r = await fetch(`/api/stations/by-sn/${sn}/state`);
    if (!r.ok) return;
    const data = await r.json();
    online.value = !!data.online;
    faceEnabled.value = !!data.face_enabled;
    if (data.session) {
      sessionUser.value = data.session.user;
      sessionStartedAt.value = new Date(data.session.opened_at);
      heldTools.value = (data.session.active_loans || []).map((al: any) => ({
        loan_id: al.loan_id,
        tool_id: al.tool.id,
        brand: al.tool.brand,
        model: al.tool.model,
        rfid: al.tool.rfid,
        loaned_at: al.loaned_at,
        due_at: al.due_at,
      }));
    }
  } catch (e) {
    console.warn("hydrate failed", e);
  }
}

function onCamSnapshotLoad() {
  if (camSnapshotTimer) clearTimeout(camSnapshotTimer);
  camSnapshotTimer = window.setTimeout(() => {
    camSnapshotTs.value = Date.now();
  }, 200);
}
function onCamSnapshotError() {
  if (camSnapshotTimer) clearTimeout(camSnapshotTimer);
  camSnapshotTimer = window.setTimeout(() => {
    camSnapshotTs.value = Date.now();
  }, 1500);
}

onMounted(async () => {
  await hydrateState();
  ws = openStationWs(sn, handleEvent);
  clockTimer = window.setInterval(() => (now.value = new Date()), 1000);
});
onBeforeUnmount(() => {
  ws?.close();
  if (lastActionTimer) clearTimeout(lastActionTimer);
  if (clockTimer) clearInterval(clockTimer);
  if (faceLiveTimer) clearTimeout(faceLiveTimer);
  if (camSnapshotTimer) clearTimeout(camSnapshotTimer);
});
</script>

<template>
  <div class="tec-kiosk" :class="`tec-kiosk--${heroState}`">
    <header class="tec-kiosk-bar">
      <span class="tec-brand">
        <span class="tec-brand-mark" style="background: #fff; color: var(--tec-blue); width: 32px; height: 32px; font-size: 16px;">T</span>
        <span class="d-none d-sm-inline">SmartLabs · Almacén</span>
      </span>
      <span class="tec-kiosk-meta">
        <span class="tec-kiosk-conn">
          <span class="tec-pulse" :class="{ 'tec-pulse--off': online === false }" />
          <span class="d-none d-sm-inline">{{ online === false ? "Offline" : "Online" }}</span>
        </span>
        <span class="tec-kiosk-station">
          <v-icon size="16" class="mr-1">mdi-radar</v-icon>
          <strong>{{ sn }}</strong>
        </span>
        <span class="tec-kiosk-clock-inline d-none d-sm-inline-flex">
          <v-icon size="16" class="mr-1">mdi-clock-outline</v-icon>
          <strong>{{ clockText }}</strong>
        </span>
        <span
          v-if="faceEnabled"
          class="tec-kiosk-face tec-kiosk-face--on"
          title="Reconocimiento facial activo"
        >
          <v-icon size="16" class="mr-1">mdi-face-recognition</v-icon>
          <span class="d-none d-sm-inline">Cámara</span>
        </span>
      </span>
    </header>

    <main class="tec-kiosk-main" :class="{ 'tec-kiosk-main--split': faceEnabled }">

      <!-- COL IZQUIERDA: cámara (solo cuando face_enabled) -->
      <aside v-if="faceEnabled" class="tec-kiosk-col-cam">
        <section class="tec-cam-panel" :class="{
          'tec-cam-panel--ok': faceLive?.recognized,
          'tec-cam-panel--bad': faceLive && !faceLive.recognized,
        }">
          <div class="tec-cam-frame">
            <img
              :src="`/face-svc/camera/snapshot?ts=${camSnapshotTs}`"
              class="tec-cam-img"
              alt="Cámara"
              @load="onCamSnapshotLoad"
              @error="onCamSnapshotError"
            />
            <div
              v-if="faceLive?.bbox"
              class="tec-cam-bbox"
              :class="faceLive.recognized ? 'tec-cam-bbox--ok' : 'tec-cam-bbox--bad'"
              :style="{
                left:   ((faceLive.bbox[0] / faceLive.frame_w) * 100) + '%',
                top:    ((faceLive.bbox[1] / faceLive.frame_h) * 100) + '%',
                width:  (((faceLive.bbox[2] - faceLive.bbox[0]) / faceLive.frame_w) * 100) + '%',
                height: (((faceLive.bbox[3] - faceLive.bbox[1]) / faceLive.frame_h) * 100) + '%',
              }"
            >
              <span class="tec-cam-tag">
                <v-icon size="14" class="mr-1">
                  {{ faceLive.recognized ? 'mdi-check-circle' : 'mdi-help-circle' }}
                </v-icon>
                {{ faceLive.recognized ? faceLive.user_name : 'No identificado' }}
              </span>
            </div>
          </div>
          <div class="tec-cam-foot">
            <v-icon size="14" class="mr-1">mdi-face-recognition</v-icon>
            Reconocimiento facial activo
          </div>
        </section>
      </aside>

      <!-- COL DERECHA: hero + lista de equipos -->
      <section class="tec-kiosk-col-info">
      <!-- HERO compacto -->
      <section class="tec-hero" :class="`tec-hero--${heroState}`">
        <div class="tec-hero-icon">
          <v-icon size="56">
            {{
              heroState === "offline"
                ? "mdi-wifi-off"
                : heroState === "active"
                ? "mdi-account-check"
                : "mdi-card-account-details-outline"
            }}
          </v-icon>
        </div>

        <div v-if="heroState === 'idle'" class="tec-hero-text">
          <div class="text-h4 font-weight-bold tec-hero-title">Pasa tu credencial</div>
          <div class="text-body-1 mt-1 tec-hero-sub">
            Coloca tu tarjeta sobre el lector
            <span class="d-block d-sm-inline">o mira a la cámara para iniciar sesión</span>
          </div>
        </div>

        <div v-else-if="heroState === 'active'" class="tec-hero-text">
          <div class="text-overline tec-hero-greet">Sesión activa</div>
          <div class="text-h4 font-weight-bold tec-hero-title">{{ sessionUser.name }}</div>
          <div class="d-flex align-center mt-2 flex-wrap" style="gap: 8px">
            <v-chip color="white" variant="elevated" size="small" prepend-icon="mdi-clock-outline">
              {{ sessionElapsed }}
            </v-chip>
            <v-chip color="white" variant="elevated" size="small" prepend-icon="mdi-toolbox">
              {{ heldTools.length }} {{ heldTools.length === 1 ? "equipo" : "equipos" }}
            </v-chip>
            <v-chip v-if="faceMethod === 'face'" color="white" variant="elevated" size="small" prepend-icon="mdi-face-recognition">
              Por rostro
            </v-chip>
          </div>
        </div>

        <div v-else class="tec-hero-text">
          <div class="text-h4 font-weight-bold tec-hero-title">Sin conexión</div>
          <div class="text-body-1 mt-1 tec-hero-sub">Verifica WiFi y broker MQTT</div>
        </div>
      </section>

      <!-- ACCIÓN RECIENTE: toast flotante en esquina -->
      <transition name="toast">
        <div
          v-if="lastAction"
          class="tec-action-toast"
          :class="`tec-action-toast--${lastAction.kind}`"
        >
          <v-icon size="22" class="mr-2">
            {{ lastAction.kind === "prestado" ? "mdi-arrow-up-bold" : "mdi-arrow-down-bold" }}
          </v-icon>
          <div class="flex-grow-1" style="min-width: 0">
            <div class="text-overline" style="line-height: 1; opacity: .85">
              {{ lastAction.kind === "prestado" ? "Prestado" : "Devuelto" }}
            </div>
            <div class="font-weight-bold" style="line-height: 1.15">
              {{ lastAction.tool.brand }} {{ lastAction.tool.model }}
            </div>
            <div class="text-caption" style="opacity: .85">{{ lastAction.tool.rfid }}</div>
          </div>
        </div>
      </transition>

      <!-- LISTA DE EQUIPOS -->
      <v-card v-if="sessionUser" class="tec-held-card" rounded="lg" elevation="2">
        <v-card-title class="d-flex align-center pa-3 pa-sm-4">
          <v-icon class="mr-2" color="primary">mdi-toolbox</v-icon>
          <span class="text-subtitle-1 font-weight-bold">Equipos en posesión</span>
          <v-spacer />
          <v-chip :color="heldTools.length === 0 ? 'success' : 'warning'" variant="tonal" size="small">
            {{ heldTools.length }} {{ heldTools.length === 1 ? "pendiente" : "pendientes" }}
          </v-chip>
        </v-card-title>

        <v-divider />

        <v-list v-if="heldTools.length > 0" lines="two" density="compact" class="pa-0">
          <transition-group name="list">
            <template v-for="t in heldTools" :key="t.loan_id">
              <v-list-item class="py-2">
                <template #prepend>
                  <v-avatar color="warning" variant="tonal" size="36">
                    <v-icon size="20">mdi-tools</v-icon>
                  </v-avatar>
                </template>
                <v-list-item-title class="font-weight-bold">
                  {{ t.brand }} {{ t.model }}
                </v-list-item-title>
                <v-list-item-subtitle class="text-caption">
                  Tag <code>{{ t.rfid }}</code> · prestado {{ fmtDate(t.loaned_at) }}
                  <span v-if="t.due_at"> · devolver antes de {{ fmtDate(t.due_at) }}</span>
                </v-list-item-subtitle>
              </v-list-item>
            </template>
          </transition-group>
        </v-list>

        <v-card-text v-else class="text-center py-6">
          <v-icon color="success" size="48">mdi-check-circle</v-icon>
          <div class="text-subtitle-1 mt-1 font-weight-bold">Sin equipos pendientes</div>
          <div class="text-caption text-medium-emphasis">
            Pasa el tag de una herramienta para prestarla, o tu credencial para cerrar sesión
          </div>
        </v-card-text>
      </v-card>
      </section>
    </main>

    <footer class="tec-kiosk-footer">
      <span>SmartLabs · Tec de Monterrey</span>
      <span>{{ clockText }}</span>
    </footer>
  </div>
</template>

<style scoped>
.tec-action-toast {
  position: fixed;
  right: 20px;
  bottom: 24px;
  display: flex;
  align-items: center;
  padding: 12px 18px;
  border-radius: 12px;
  color: #fff;
  box-shadow: 0 12px 30px rgba(0, 0, 0, 0.18);
  min-width: 240px;
  max-width: 360px;
  z-index: 100;
}
.tec-action-toast--prestado { background: linear-gradient(135deg, #ef6c00, #f57c00); }
.tec-action-toast--devuelto { background: linear-gradient(135deg, #2e7d32, #43a047); }
.toast-enter-active, .toast-leave-active { transition: transform .35s cubic-bezier(.2,.8,.2,1), opacity .25s; }
.toast-enter-from { opacity: 0; transform: translate(20px, 20px) scale(.95); }
.toast-leave-to { opacity: 0; transform: translate(0, 12px); }

.list-enter-active, .list-leave-active { transition: all .3s ease; }
.list-enter-from { opacity: 0; transform: translateX(-20px); }
.list-leave-to { opacity: 0; transform: translateX(20px); }

/* Layout split: cámara izquierda, info derecha (solo si face_enabled). En
   pantallas chicas se apila vertical (cámara arriba, info abajo). */
.tec-kiosk-main--split {
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  gap: 18px;
  align-items: start;
}
@media (min-width: 900px) {
  .tec-kiosk-main--split {
    grid-template-columns: minmax(0, 5fr) minmax(0, 7fr);
    gap: 24px;
  }
}
.tec-kiosk-col-cam {
  position: sticky;
  top: 12px;
  align-self: start;
}
.tec-kiosk-col-info {
  display: flex;
  flex-direction: column;
  gap: 16px;
  min-width: 0;
}

/* Chip de estado de reconocimiento facial en el header del kiosko */
.tec-kiosk-face {
  display: inline-flex;
  align-items: center;
  padding: 4px 10px;
  border-radius: 999px;
  font-size: .85rem;
  font-weight: 600;
  margin-left: 8px;
}
.tec-kiosk-face--on {
  background: rgba(255,255,255,.18);
  color: #fff;
}

/* Panel cámara con bbox overlay */
.tec-cam-panel {
  width: 100%;
  margin: 0 auto;
  border-radius: 14px;
  background: #0c1a2e;
  overflow: hidden;
  box-shadow: 0 4px 14px rgba(0,0,0,.18);
  border: 2px solid rgba(255,255,255,.05);
  transition: border-color .25s ease;
}
.tec-kiosk-col-cam .tec-cam-panel { max-width: 100%; }
.tec-cam-panel--ok { border-color: #43a047; }
.tec-cam-panel--bad { border-color: #e53935; }

.tec-cam-frame {
  position: relative;
  aspect-ratio: 16 / 9;
  width: 100%;
  background: #000;
}
.tec-cam-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}
.tec-cam-bbox {
  position: absolute;
  border: 3px solid;
  border-radius: 4px;
  pointer-events: none;
  transition: left .15s ease, top .15s ease, width .15s ease, height .15s ease,
              border-color .2s ease;
}
.tec-cam-bbox--ok {
  border-color: #43a047;
  box-shadow: 0 0 0 2px rgba(67, 160, 71, .35);
}
.tec-cam-bbox--bad {
  border-color: #e53935;
  box-shadow: 0 0 0 2px rgba(229, 57, 53, .35);
}
.tec-cam-tag {
  position: absolute;
  bottom: -28px;
  left: -3px;
  display: inline-flex;
  align-items: center;
  padding: 4px 10px;
  border-radius: 4px;
  font-size: .8rem;
  font-weight: 700;
  color: #fff;
  white-space: nowrap;
  max-width: calc(100% + 6px);
  overflow: hidden;
  text-overflow: ellipsis;
}
.tec-cam-bbox--ok .tec-cam-tag { background: #43a047; }
.tec-cam-bbox--bad .tec-cam-tag { background: #e53935; }
.tec-cam-foot {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 8px 12px;
  font-size: .82rem;
  color: rgba(255,255,255,.85);
  background: linear-gradient(0deg, rgba(0,0,0,.35), rgba(0,0,0,.05));
}
</style>
