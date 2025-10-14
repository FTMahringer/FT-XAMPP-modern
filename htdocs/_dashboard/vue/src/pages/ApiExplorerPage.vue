<script setup>
import ApiExplorer from '../components/ApiExplorer.vue'
</script>

<template>
  <section class="content" style="grid-column: 1 / -1;">
    <div class="left" style="grid-column: 1 / -1;">
      <div class="panel">
        <div class="panel-head">
          <div class="panel-title">🧪 API Explorer <span class="muted">— Swagger-like</span></div>
        </div>
        <ApiExplorer />
      </div>
    </div>
  </section>
</template>
