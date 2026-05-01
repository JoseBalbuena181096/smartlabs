<script setup lang="ts">
defineProps<{
  title: string;
  subtitle?: string;
  icon?: string;
}>();
</script>

<template>
  <div class="d-flex align-center mb-4 flex-wrap" style="gap: 12px">
    <div class="d-flex align-center">
      <v-avatar v-if="icon" color="primary" variant="tonal" size="44" class="mr-3">
        <v-icon>{{ icon }}</v-icon>
      </v-avatar>
      <div>
        <div class="text-h5 text-md-h4 font-weight-bold">{{ title }}</div>
        <div v-if="subtitle" class="text-body-2 text-medium-emphasis">{{ subtitle }}</div>
      </div>
    </div>
    <v-spacer />
    <slot />
  </div>
</template>
