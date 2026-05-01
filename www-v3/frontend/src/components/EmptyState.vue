<script setup lang="ts">
defineProps<{
  icon: string;
  title: string;
  description?: string;
}>();
</script>

<template>
  <div class="text-center pa-10">
    <v-icon size="64" color="grey-lighten-1">{{ icon }}</v-icon>
    <div class="text-h6 font-weight-bold mt-3">{{ title }}</div>
    <div v-if="description" class="text-body-2 text-medium-emphasis mt-1" style="max-width: 480px; margin: 0 auto">
      {{ description }}
    </div>
    <div class="mt-4"><slot /></div>
  </div>
</template>
