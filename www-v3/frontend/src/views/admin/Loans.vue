<script setup lang="ts">
import { onMounted, onBeforeUnmount, ref } from "vue";
import { api } from "../../api/client";
import { openAdminWs, type WsClient } from "../../ws/client";
import { useAuth } from "../../stores/auth";

const auth = useAuth();
const loans = ref<any[]>([]);
const filter = ref("open");
let ws: WsClient | null = null;

async function load() {
  const { data } = await api.get("/loans", { params: { status: filter.value } });
  loans.value = data;
}

async function returnLoan(l: any) {
  const reason = prompt("Razón de devolución manual:", "admin_manual");
  if (!reason) return;
  await api.post(`/loans/${l.id}/return`, { reason });
  load();
}

onMounted(async () => {
  await load();
  ws = openAdminWs(auth.token!, false, (ev) => {
    if (["loan.created", "loan.returned"].includes(ev.type)) load();
  });
});
onBeforeUnmount(() => ws?.close());
</script>

<template>
  <v-card>
    <v-card-title class="d-flex align-center">
      Préstamos
      <v-spacer />
      <v-btn-toggle v-model="filter" mandatory density="compact" @update:model-value="load">
        <v-btn value="open">Abiertos</v-btn>
        <v-btn value="closed">Cerrados</v-btn>
        <v-btn value="all">Todos</v-btn>
      </v-btn-toggle>
    </v-card-title>
    <v-data-table
      :items="loans"
      :headers="[
        { title: 'Usuario', key: 'user.full_name' },
        { title: 'Herramienta', key: 'tool' , value: (l: any) => `${l.tool?.brand || ''} ${l.tool?.model || ''}` },
        { title: 'Tag', key: 'tool.rfid' },
        { title: 'Prestado', key: 'loaned_at' },
        { title: 'Vence', key: 'due_at' },
        { title: 'Devuelto', key: 'returned_at' },
        { title: '', key: 'actions', sortable: false },
      ]"
      items-per-page="25"
    >
      <template #item.actions="{ item }">
        <v-btn
          v-if="!item.returned_at"
          size="small"
          color="warning"
          variant="tonal"
          @click="returnLoan(item)"
        >
          Devolver manual
        </v-btn>
      </template>
    </v-data-table>
  </v-card>
</template>
