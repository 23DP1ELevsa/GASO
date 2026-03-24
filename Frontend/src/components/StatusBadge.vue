<template>
  <span class="status-badge" :class="badgeClass">{{ label }}</span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  label: {
    type: String,
    required: true,
  },
});

const badgeClass = computed(() => {
  const value = props.label.toLowerCase();

  if (value.includes('klienta')) {
    return 'is-issued';
  }

  if (value.includes('pilns')) {
    return 'is-full';
  }

  if (value.includes('tukss')) {
    return 'is-empty';
  }

  if (value.includes('remonta')) {
    return 'is-service';
  }

  return 'is-stock';
});
</script>