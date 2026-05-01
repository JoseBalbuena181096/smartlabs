<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import { api } from "../../api/client";
import { openAdminWs, type WsClient } from "../../ws/client";
import { useAuth } from "../../stores/auth";

const auth = useAuth();
const stations = ref<any[]>([]);
const activeLoans = ref<any[]>([]);
const tools = ref<any[]>([]);
const sessions = ref<any[]>([]);
const events = ref<any[]>([]);
let ws: WsClient | null = null;

const stationsOnline = computed(() => stations.value.filter((s) => s.online).length);
const toolsInStock = computed(() => tools.value.filter((t) => t.status === "in_stock").length);
const toolsOnLoan = computed(() => tools.value.filter((t) => t.status === "on_loan").length);

const eventLabel: Record<string, string> = {
  "session.opened": "Sesión iniciada",
  "session.closed": "Sesión cerrada",
  "loan.created": "Préstamo",
  "loan.returned": "Devolución",
  "tool.retired": "Herramienta retirada",
  "station.online": "Estación online",
  "station.offline": "Estación offline",
  "inventory.scan": "Inventario · scan",
};
const eventColor: Record<string, string> = {
  "session.opened": "primary",
  "session.closed": "grey",
  "loan.created": "warning",
  "loan.returned": "success",
  "tool.retired": "error",
  "station.online": "success",
  "station.offline": "grey",
};
const eventIcon: Record<string, string> = {
  "session.opened": "mdi-account-arrow-right",
  "session.closed": "mdi-account-arrow-left",
  "loan.created": "mdi-arrow-up-bold-circle",
  "loan.returned": "mdi-arrow-down-bold-circle",
  "tool.retired": "mdi-archive-arrow-down",
  "station.online": "mdi-radar",
  "station.offline": "mdi-radar-off",
  "inventory.scan": "mdi-barcode-scan",
};

function fmtTime(s: string | undefined) {
  if (!s) return "";
  return new Date(s).toLocaleTimeString("es-MX", { hour: "2-digit", minute: "2-digit", second: "2-digit" });
}

function eventDescription(e: any): string {
  if (e.user) return e.user.name || `Usuario #${e.user.id}`;
  if (e.tool) return `${e.tool.brand || ""} ${e.tool.model || ""}`.trim() || `Tag ${e.tool.rfid}`;
  if (e.sn) return `Estación ${e.sn}`;
  return "";
}

async function refresh() {
  const [{ data: st }, { data: ln }, { data: tl }, { data: ss }] = await Promise.all([
    api.get("/stations"),
    api.get("/loans/active"),
    api.get("/tools"),
    api.get("/sessions/active"),
  ]);
  stations.value = st;
  activeLoans.value = ln;
  tools.value = tl;
  sessions.value = ss;
}

onMounted(async () => {
  await refresh();
  ws = openAdminWs(auth.token!, false, (ev) => {
    events.value.unshift({ ...ev, _at: ev.at || ev.opened_at || ev.loaned_at || ev.returned_at || new Date().toISOString() });
    events.value = events.value.slice(0, 25);
    if (
      ["loan.created", "loan.returned", "tool.retired", "session.opened", "session.closed", "station.online", "station.offline"].includes(ev.type)
    ) {
      refresh();
    }
  });
});
onBeforeUnmount(() => ws?.close());
</script>

<template>
  <div class="d-flex align-center mb-4 mb-md-6">
    <div>
      <div class="text-h4 font-weight-bold">Dashboard</div>
      <div class="text-body-2 text-medium-emphasis">Resumen en tiempo real del almacén</div>
    </div>
    <v-spacer />
    <v-chip color="success" variant="tonal" prepend-icon="mdi-broadcast" size="small">
      Tiempo real
    </v-chip>
  </div>

  <v-row dense>
    <v-col cols="6" md="3">
      <v-card class="tec-kpi" rounded="lg" elevation="1">
        <v-card-text class="pa-4">
          <div class="d-flex align-center justify-space-between mb-2">
            <span class="text-overline text-medium-emphasis">Préstamos activos</span>
            <v-icon color="warning" size="22">mdi-clipboard-clock-outline</v-icon>
          </div>
          <div class="text-h3 font-weight-bold">{{ activeLoans.length }}</div>
          <div class="text-caption text-medium-emphasis">herramientas afuera</div>
        </v-card-text>
      </v-card>
    </v-col>

    <v-col cols="6" md="3">
      <v-card class="tec-kpi" rounded="lg" elevation="1">
        <v-card-text class="pa-4">
          <div class="d-flex align-center justify-space-between mb-2">
            <span class="text-overline text-medium-emphasis">En almacén</span>
            <v-icon color="success" size="22">mdi-package-variant-closed</v-icon>
          </div>
          <div class="text-h3 font-weight-bold">{{ toolsInStock }}</div>
          <div class="text-caption text-medium-emphasis">de {{ tools.length }} totales</div>
        </v-card-text>
      </v-card>
    </v-col>

    <v-col cols="6" md="3">
      <v-card class="tec-kpi" rounded="lg" elevation="1">
        <v-card-text class="pa-4">
          <div class="d-flex align-center justify-space-between mb-2">
            <span class="text-overline text-medium-emphasis">Sesiones ahora</span>
            <v-icon color="primary" size="22">mdi-account-arrow-right</v-icon>
          </div>
          <div class="text-h3 font-weight-bold">{{ sessions.length }}</div>
          <div class="text-caption text-medium-emphasis">usuarios usando estación</div>
        </v-card-text>
      </v-card>
    </v-col>

    <v-col cols="6" md="3">
      <v-card class="tec-kpi" rounded="lg" elevation="1">
        <v-card-text class="pa-4">
          <div class="d-flex align-center justify-space-between mb-2">
            <span class="text-overline text-medium-emphasis">Estaciones</span>
            <v-icon :color="stationsOnline === stations.length ? 'success' : 'warning'" size="22">mdi-radar</v-icon>
          </div>
          <div class="text-h3 font-weight-bold">{{ stationsOnline }} <span class="text-h5 text-medium-emphasis">/ {{ stations.length }}</span></div>
          <div class="text-caption text-medium-emphasis">{{ stationsOnline === stations.length ? "todas online" : "verificar offline" }}</div>
        </v-card-text>
      </v-card>
    </v-col>
  </v-row>

  <v-row class="mt-2">
    <v-col cols="12" md="7">
      <v-card rounded="lg" elevation="1">
        <v-card-title class="d-flex align-center pa-4">
          <v-icon class="mr-2" color="primary">mdi-pulse</v-icon>
          <span class="font-weight-bold">Actividad en vivo</span>
          <v-spacer />
          <v-chip size="x-small" variant="tonal">{{ events.length }}</v-chip>
        </v-card-title>
        <v-divider />
        <v-list v-if="events.length > 0" lines="two" density="compact" class="pa-0">
          <template v-for="(e, i) in events" :key="i">
            <v-list-item>
              <template #prepend>
                <v-avatar :color="eventColor[e.type] || 'grey'" variant="tonal" size="36">
                  <v-icon size="20">{{ eventIcon[e.type] || "mdi-circle-small" }}</v-icon>
                </v-avatar>
              </template>
              <v-list-item-title class="font-weight-medium">
                {{ eventLabel[e.type] || e.type }}
                <span v-if="eventDescription(e)" class="text-medium-emphasis"> · {{ eventDescription(e) }}</span>
              </v-list-item-title>
              <v-list-item-subtitle>
                <span v-if="e.station_sn || e.sn">📡 {{ e.station_sn || e.sn }} · </span>{{ fmtTime(e._at) }}
              </v-list-item-subtitle>
            </v-list-item>
            <v-divider v-if="i < events.length - 1" />
          </template>
        </v-list>
        <v-card-text v-else class="text-center pa-10">
          <v-icon size="48" color="grey-lighten-1">mdi-pulse</v-icon>
          <div class="text-body-1 text-medium-emphasis mt-2">
            Esperando actividad. Cuando alguien escanee una credencial aparecerá aquí.
          </div>
        </v-card-text>
      </v-card>
    </v-col>

    <v-col cols="12" md="5">
      <v-card rounded="lg" elevation="1" class="mb-4">
        <v-card-title class="d-flex align-center pa-4">
          <v-icon class="mr-2" color="primary">mdi-radar</v-icon>
          <span class="font-weight-bold">Estaciones</span>
        </v-card-title>
        <v-divider />
        <v-list v-if="stations.length > 0" density="compact">
          <v-list-item v-for="s in stations" :key="s.id">
            <template #prepend>
              <span class="tec-pulse" :class="{ 'tec-pulse--off': !s.online }" />
            </template>
            <v-list-item-title class="font-weight-medium">
              {{ s.alias || s.serial_number }}
            </v-list-item-title>
            <v-list-item-subtitle>
              {{ s.alias ? s.serial_number + ' · ' : '' }}{{ s.online ? "online" : "offline" }}
            </v-list-item-subtitle>
          </v-list-item>
        </v-list>
        <v-card-text v-else class="text-center pa-6 text-medium-emphasis">
          <v-icon>mdi-radar-off</v-icon>
          Sin estaciones registradas
        </v-card-text>
      </v-card>

      <v-card rounded="lg" elevation="1">
        <v-card-title class="d-flex align-center pa-4">
          <v-icon class="mr-2" color="warning">mdi-account-arrow-right</v-icon>
          <span class="font-weight-bold">Sesiones activas</span>
        </v-card-title>
        <v-divider />
        <v-list v-if="sessions.length > 0" density="compact">
          <v-list-item v-for="s in sessions" :key="s.id">
            <template #prepend>
              <v-avatar size="32" color="primary" variant="tonal">
                <v-icon size="18">mdi-account</v-icon>
              </v-avatar>
            </template>
            <v-list-item-title>{{ s.user?.full_name || `Usuario #${s.user_id}` }}</v-list-item-title>
            <v-list-item-subtitle>📡 {{ s.station?.serial_number }} · desde {{ fmtTime(s.opened_at) }}</v-list-item-subtitle>
          </v-list-item>
        </v-list>
        <v-card-text v-else class="text-center pa-6 text-medium-emphasis">
          Nadie tiene sesión abierta ahora
        </v-card-text>
      </v-card>
    </v-col>
  </v-row>
</template>

<style scoped>
.tec-kpi { transition: transform .15s ease, box-shadow .15s ease; }
.tec-kpi:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(0, 57, 166, 0.10); }
</style>
