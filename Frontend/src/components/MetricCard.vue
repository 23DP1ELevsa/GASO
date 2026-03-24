<template>
  <article class="metric-card">
    <p class="metric-label">{{ label }}</p>
    <strong class="metric-value">{{ value }}</strong>
    <p class="metric-hint">{{ hint }}</p>
  </article>
</template>

<script setup>
defineProps({
  label: {
    type: String,
    required: true,
  },
  value: {
    type: [String, Number],
    required: true,
  },
  hint: {
    type: String,
    default: '',
  },
});
</script>