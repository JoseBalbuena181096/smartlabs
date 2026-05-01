<script setup lang="ts">
import { useConfirm } from "../composables/useConfirm";
import { useSnack } from "../composables/useSnack";

const { state: confirmState, answer } = useConfirm();
const { state: snackState } = useSnack();
</script>

<template>
  <v-dialog v-model="confirmState.open" max-width="440" persistent>
    <v-card rounded="lg">
      <v-card-title class="text-h6 font-weight-bold pa-5">
        {{ confirmState.title }}
      </v-card-title>
      <v-card-text class="text-body-1 px-5 pb-2">
        {{ confirmState.message }}
      </v-card-text>
      <v-card-actions class="px-4 pb-4">
        <v-spacer />
        <v-btn variant="text" @click="answer(false)">{{ confirmState.cancelText }}</v-btn>
        <v-btn :color="confirmState.color" variant="flat" @click="answer(true)">
          {{ confirmState.confirmText }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>

  <v-snackbar
    v-model="snackState.open"
    :color="snackState.kind"
    location="bottom right"
    rounded="lg"
    elevation="6"
  >
    {{ snackState.message }}
    <template #actions>
      <v-btn icon="mdi-close" variant="text" size="small" @click="snackState.open = false" />
    </template>
  </v-snackbar>
</template>
