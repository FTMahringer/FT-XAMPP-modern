<script setup lang="ts">
import { computed, reactive } from 'vue'

// Vue 3.5+: defineModel für v-model:open
const open = defineModel<boolean>('open', { default: false })

const emit = defineEmits<{
  (e: 'submit', payload: {
    name: string,
    type: 'plain-php' | 'plain-html' | 'symfony' | 'vue',
    options?: Record<string, any>
  }): void
  (e: 'cancel'): void
}>()

// Form-State
const form = reactive({
  name: '',
  type: 'plain-php' as 'plain-php' | 'plain-html' | 'symfony' | 'vue',
  symfonyPreset: 'webapp' as 'webapp' | 'api' | 'minimal',
  symfonyVersion: '7.2',
  initGit: false,     // default OFF
  addReadme: true
})


const canSubmit = computed(() =>
    !!form.name && /^[a-zA-Z0-9._-]+$/.test(form.name)
)

function reset() {
  form.name = ''
  form.type = 'plain-php'
  form.symfonyPreset = 'webapp'
  form.symfonyVersion = '7.2'
  form.initGit = false      // stay OFF after reset, wie gewünscht
  form.addReadme = true
}

function onCancel() {
  reset()
  open.value = false
  emit('cancel')
}

function onSubmit() {
  if (!canSubmit.value) return
  const payload = {
    name: form.name.trim(),
    type: form.type,
    options: {
      initGit: form.initGit,
      addReadme: form.addReadme,
      ...(form.type === 'symfony' ? {
        symfonyPreset: form.symfonyPreset,
        symfonyVersion: form.symfonyVersion
      } : {})
    }
  }
  emit('submit', payload)
}
</script>

<template>
  <div v-if="open" class="modal-backdrop" @click.self="onCancel">
    <div class="modal-card">
      <div class="modal-header">
        <h3>Create New Project</h3>
      </div>

      <div class="modal-body">
        <label class="row">
          <span>Project Folder Name</span>
          <input
              v-model="form.name"
              type="text"
              placeholder="my-project"
              autocomplete="off"
          />
        </label>

        <label class="row">
          <span>Template</span>
          <select v-model="form.type">
            <option value="plain-php">Plain PHP</option>
            <option value="plain-html">Plain HTML</option>
            <option value="symfony">Symfony</option>
            <option value="vue" disabled>Vue (coming soon)</option>
          </select>
        </label>

        <div v-if="form.type === 'symfony'" class="box">
          <div class="row">
            <span>Symfony Preset</span>
            <select v-model="form.symfonyPreset">
              <option value="webapp">Webapp (Twig + Assets)</option>
              <option value="api">API (API Platform)</option>
              <option value="minimal">Minimal (Skeleton only)</option>
            </select>
          </div>
          <div class="row">
            <span>Symfony Version</span>
            <input v-model="form.symfonyVersion" type="text" placeholder="7.2 or ^7.2" />
          </div>
        </div>

        <div class="row checkbox">
          <label><input type="checkbox" v-model="form.initGit" /> Init Git repository</label>
        </div>
        <div class="row checkbox">
          <label><input type="checkbox" v-model="form.addReadme" /> Add README.md</label>
        </div>

        <p class="hint">
          Hinweis: Der Projektordner wird im Webroot erzeugt (z. B. <code>htdocs/&lt;name&gt;</code>).
          Backend erledigt Scaffold je nach Template.
        </p>
      </div>

      <div class="modal-footer">
        <button class="ghost" @click="onCancel">Cancel</button>
        <button :disabled="!canSubmit" @click="onSubmit">Create</button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.modal-backdrop {
  position: fixed; inset: 0;
  background: rgba(0,0,0,.6);
  display: grid; place-items: center;
  z-index: 1000;
}
.modal-card {
  width: 560px; max-width: calc(100vw - 2rem);
  background: #0f0f10; color: #eaeaea;
  border: 1px solid #333; border-radius: 16px;
  box-shadow: 0 10px 40px rgba(0,0,0,.6);
  overflow: hidden;
}
.modal-header { padding: 1rem 1.25rem; border-bottom: 1px solid #222; }
.modal-body { padding: 1rem 1.25rem; display: grid; gap: .75rem; }
.modal-footer {
  padding: .75rem 1.25rem; border-top: 1px solid #222;
  display: flex; justify-content: flex-end; gap: .5rem;
}
.row { display: grid; gap: .35rem; }
.row span { font-size: .9rem; opacity: .9; }
.row input, .row select {
  background: #111; color: #eee; border: 1px solid #333; border-radius: .5rem;
  padding: .5rem .6rem;
}
.box {
  border: 1px dashed #333; border-radius: .75rem; padding: .75rem; display: grid; gap: .5rem;
}
.checkbox { margin-top: .25rem; }
button {
  padding: .5rem .9rem; border-radius: .6rem; border: 1px solid #444;
  background: #181818; color: #eee; cursor: pointer;
}
button:hover { filter: brightness(1.15); }
button.ghost { background: transparent; }
button:disabled { opacity: .5; cursor: not-allowed; }
.hint { font-size: .85rem; opacity: .8; margin-top: .25rem; }
</style>
