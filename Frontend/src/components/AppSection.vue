<template>
  <section class="panel section-panel">
    <div class="section-header">
      <div>
        <p class="eyebrow">{{ eyebrow }}</p>
        <h2>{{ title }}</h2>
        <p v-if="description" class="section-description">{{ description }}</p>
      </div>
      <div v-if="$slots.actions" class="section-actions">
        <slot name="actions" />
      </div>
    </div>

    <slot />
  </section>
</template>

<script setup>
defineProps({
  eyebrow: {
    type: String,
    default: 'Modulis',
  },
  title: {
    type: String,
    required: true,
  },
  description: {
    type: String,
    default: '',
  },
});
</script>