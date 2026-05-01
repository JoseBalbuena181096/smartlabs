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
let ws: WsClient | null = null;
let lastActionTimer: number | null = null;
let clockTimer: number | null = null;

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

function handleEvent(ev: any) {
  switch (ev.type) {
    case "session.opened":
      sessionUser.value = ev.user;
      sessionStartedAt.value = new Date(ev.opened_at || Date.now());
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

onMounted(() => {
  ws = openStationWs(sn, handleEvent);
  clockTimer = window.setInterval(() => (now.value = new Date()), 1000);
});
onBeforeUnmount(() => {
  ws?.close();
  if (lastActionTimer) clearTimeout(lastActionTimer);
  if (clockTimer) clearInterval(clockTimer);
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
      </span>
    </header>

    <main class="tec-kiosk-main">
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
          <div class="text-body-1 mt-1 tec-hero-sub">Coloca tu tarjeta sobre el lector para iniciar sesión</div>
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
</style>
