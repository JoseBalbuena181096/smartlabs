<script setup lang="ts">
import { computed, onMounted, onBeforeUnmount, ref } from "vue";
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

const loans = ref<any[]>([]);
const filter = ref<"open" | "closed" | "all">("open");
const loading = ref(false);
let ws: WsClient | null = null;

async function load() {
  loading.value = true;
  try {
    const { data } = await api.get("/loans", { params: { status: filter.value } });
    loans.value = data;
  } finally {
    loading.value = false;
  }
}

function fmt(s: string | undefined | null) {
  if (!s) return "—";
  return new Date(s).toLocaleString("es-MX", { dateStyle: "short", timeStyle: "short" });
}

const overdueOf = (l: any) => {
  if (l.returned_at || !l.due_at) return false;
  return new Date(l.due_at) < new Date();
};

async function returnLoan(l: any) {
  const ok = await confirm(
    `Marcar como devuelto el préstamo de "${l.tool?.brand} ${l.tool?.model}" sin pasarlo por el lector físico.`,
    { title: "Devolución manual", confirmText: "Marcar devuelto", color: "warning" },
  );
  if (!ok) return;
  try {
    await api.post(`/loans/${l.id}/return`, { reason: "admin_manual" });
    snack.success("Préstamo cerrado manualmente");
    load();
  } catch (e: any) {
    snack.error(e?.response?.data?.detail || "No se pudo cerrar");
  }
}

onMounted(async () => {
  await load();
  ws = openAdminWs(auth.token!, false, (ev) => {
    if (["loan.created", "loan.returned", "tool.retired"].includes(ev.type)) load();
  });
});
onBeforeUnmount(() => ws?.close());
</script>

<template>
  <PageHeader title="Préstamos" subtitle="Qué hay afuera del almacén y quién lo tiene" icon="mdi-clipboard-list-outline">
    <v-btn-toggle v-model="filter" mandatory color="primary" density="comfortable" variant="outlined" divided @update:model-value="load">
      <v-btn value="open">Activos</v-btn>
      <v-btn value="closed">Cerrados</v-btn>
      <v-btn value="all">Todos</v-btn>
    </v-btn-toggle>
  </PageHeader>

  <v-card rounded="lg" elevation="1">
    <v-data-table
      v-if="mdAndUp"
      :loading="loading"
      :items="loans"
      :headers="[
        { title: 'Usuario', key: 'user.full_name' },
        { title: 'Herramienta', key: 'tool' },
        { title: 'Tag', key: 'tool.rfid' },
        { title: 'Prestado', key: 'loaned_at' },
        { title: 'Vence', key: 'due_at' },
        { title: 'Estado', key: 'state', sortable: false },
        { title: '', key: 'actions', sortable: false, align: 'end' },
      ]"
      items-per-page="25"
    >
      <template #item.user.full_name="{ item }">
        <div class="d-flex align-center">
          <v-avatar size="30" color="primary" variant="tonal" class="mr-2">
            <span class="text-caption font-weight-bold">{{ (item.user?.full_name || "?").slice(0, 1) }}</span>
          </v-avatar>
          {{ item.user?.full_name || `Usuario #${item.user_id}` }}
        </div>
      </template>
      <template #item.tool="{ item }">
        <div class="font-weight-medium">{{ item.tool?.brand }} {{ item.tool?.model }}</div>
      </template>
      <template #item.tool.rfid="{ item }"><code>{{ item.tool?.rfid }}</code></template>
      <template #item.loaned_at="{ item }">{{ fmt(item.loaned_at) }}</template>
      <template #item.due_at="{ item }">
        <span :class="{ 'text-error font-weight-bold': overdueOf(item) }">{{ fmt(item.due_at) }}</span>
      </template>
      <template #item.state="{ item }">
        <v-chip
          v-if="!item.returned_at"
          :color="overdueOf(item) ? 'error' : 'warning'"
          size="small"
          variant="tonal"
        >
          {{ overdueOf(item) ? "Atrasado" : "Activo" }}
        </v-chip>
        <v-chip v-else color="success" size="small" variant="tonal">
          Devuelto {{ fmt(item.returned_at) }}
        </v-chip>
      </template>
      <template #item.actions="{ item }">
        <v-btn
          v-if="!item.returned_at"
          size="small"
          color="warning"
          variant="tonal"
          prepend-icon="mdi-arrow-down-bold"
          @click="returnLoan(item)"
        >
          Devolver
        </v-btn>
      </template>
      <template #no-data>
        <EmptyState
          icon="mdi-clipboard-text-outline"
          title="Sin préstamos"
          :description="filter === 'open' ? 'Nadie tiene herramientas afuera del almacén ahora.' : 'Aún no hay registros.'"
        />
      </template>
    </v-data-table>

    <div v-else>
      <EmptyState
        v-if="!loading && loans.length === 0"
        icon="mdi-clipboard-text-outline"
        title="Sin préstamos"
        :description="filter === 'open' ? 'Nadie tiene herramientas afuera del almacén ahora.' : 'Aún no hay registros.'"
      />
      <v-list v-else lines="three" class="pa-0">
        <template v-for="(l, i) in loans" :key="l.id">
          <v-list-item>
            <template #prepend>
              <v-avatar :color="overdueOf(l) ? 'error' : !l.returned_at ? 'warning' : 'success'" variant="tonal">
                <v-icon>{{ l.returned_at ? "mdi-check" : "mdi-arrow-up-bold" }}</v-icon>
              </v-avatar>
            </template>
            <v-list-item-title class="font-weight-bold">{{ l.tool?.brand }} {{ l.tool?.model }}</v-list-item-title>
            <v-list-item-subtitle>
              <div>{{ l.user?.full_name }} · prestado {{ fmt(l.loaned_at) }}</div>
              <div v-if="l.due_at" :class="{ 'text-error': overdueOf(l) }">vence {{ fmt(l.due_at) }}</div>
            </v-list-item-subtitle>
            <template #append>
              <v-btn
                v-if="!l.returned_at"
                size="small"
                color="warning"
                variant="tonal"
                @click="returnLoan(l)"
              >Devolver</v-btn>
            </template>
          </v-list-item>
          <v-divider v-if="i < loans.length - 1" />
        </template>
      </v-list>
    </div>
  </v-card>
</template>
