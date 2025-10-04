<script setup>
import ProjectItem from './ProjectItem.vue'
defineProps({ items: { type:Array, required: true } })
</script>

<template>
  <div class="list">
    <ProjectItem v-for="p in items" :key="p.name" :p="p" />
  </div>
</template>

<style>
.list{ display:flex; flex-direction:column; gap:14px; padding:6px; max-height:70vh; overflow:auto; }
</style>