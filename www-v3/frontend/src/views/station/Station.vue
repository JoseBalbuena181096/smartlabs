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
let ws: WsClient | null = null;
let lastActionTimer: number | null = null;

const status = computed(() => {
  if (online.value === false) return { color: "error", text: "Estación offline" };
  if (sessionUser.value) return { color: "primary", text: `Sesión activa: ${sessionUser.value.name}` };
  return { color: "grey", text: "Pasa tu credencial para iniciar" };
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
  }, 4000);
}

function handleEvent(ev: any) {
  switch (ev.type) {
    case "session.opened":
      sessionUser.value = ev.user;
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
});
onBeforeUnmount(() => {
  ws?.close();
  if (lastActionTimer) clearTimeout(lastActionTimer);
});
</script>

<template>
  <v-container fluid class="pa-8" style="min-height: 100vh">
    <v-row justify="center">
      <v-col cols="12" md="10">
        <v-card :color="status.color" variant="tonal" class="mb-4">
          <v-card-title class="text-h3 text-center pa-8">
            {{ status.text }}
          </v-card-title>
          <v-card-subtitle class="text-center pb-4">
            Estación <strong>{{ sn }}</strong>
          </v-card-subtitle>
        </v-card>

        <v-alert
          v-if="lastAction"
          :type="lastAction.kind === 'prestado' ? 'warning' : 'success'"
          variant="tonal"
          class="mb-4 text-h5"
          :icon="lastAction.kind === 'prestado' ? 'mdi-arrow-up-bold' : 'mdi-arrow-down-bold'"
        >
          {{ lastAction.kind === "prestado" ? "Acabas de prestar" : "Acabas de devolver" }}:
          <strong>{{ lastAction.tool.brand }} {{ lastAction.tool.model }}</strong>
          ({{ lastAction.tool.rfid }})
        </v-alert>

        <v-card v-if="sessionUser">
          <v-card-title class="d-flex align-center">
            <v-icon class="mr-2">mdi-toolbox</v-icon>
            Equipos en posesión
            <v-spacer />
            <v-chip :color="heldTools.length === 0 ? 'success' : 'warning'" variant="tonal">
              {{ heldTools.length }} pendiente{{ heldTools.length === 1 ? "" : "s" }}
            </v-chip>
          </v-card-title>

          <v-list v-if="heldTools.length > 0">
            <v-list-item v-for="t in heldTools" :key="t.loan_id">
              <template #prepend>
                <v-icon color="warning">mdi-tools</v-icon>
              </template>
              <v-list-item-title class="text-h6">
                {{ t.brand }} {{ t.model }}
              </v-list-item-title>
              <v-list-item-subtitle>
                Tag <strong>{{ t.rfid }}</strong> · prestado {{ fmtDate(t.loaned_at) }}
                <span v-if="t.due_at">· devolver antes de {{ fmtDate(t.due_at) }}</span>
              </v-list-item-subtitle>
            </v-list-item>
          </v-list>

          <v-card-text v-else class="text-center pa-8">
            <v-icon color="success" size="64">mdi-check-circle</v-icon>
            <div class="text-h5 mt-2">Sin equipos pendientes</div>
            <div class="text-body-2 text-grey">
              Pasa el tag de una herramienta para prestarla, o tu credencial para cerrar sesión.
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>
