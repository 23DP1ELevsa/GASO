<template>
  <main class="login-shell">
    <section class="login-hero">
      <p class="eyebrow">GASO</p>
      <h1>Gazes balonu uzskaites sistema</h1>
      <p class="lead">
        Vienota darba vide balonu uzskaitei, klientu apkalposanai un darijumu vesturei.
      </p>

      <div class="credential-card panel">
        <h2>Demo pieejas</h2>
        <ul>
          <li>Administrators: admin@gaso.lv / password</li>
          <li>Darbinieks: darbinieks@gaso.lv / password</li>
          <li>Klients: klients1 / password</li>
        </ul>
      </div>
    </section>

    <section class="login-panel panel">
      <div>
        <p class="eyebrow">Pieslegsanas logs</p>
        <h2>Ienakt sistema</h2>
      </div>

      <form class="stack" @submit.prevent="submitLogin">
        <label>
          <span>Lietotajvards vai e-pasts</span>
          <input v-model.trim="form.login" type="text" placeholder="admin@gaso.lv vai klients1" required />
        </label>

        <label>
          <span>Parole</span>
          <input v-model="form.password" type="password" placeholder="password" required />
        </label>

        <p v-if="errorMessage" class="form-message error">{{ errorMessage }}</p>

        <button class="primary-button" type="submit" :disabled="submitting">
          {{ submitting ? 'Notiek pieslegsanas...' : 'Pieslegties' }}
        </button>
      </form>
    </section>
  </main>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';

import api from '../lib/api';
import { setSession } from '../lib/session';

const router = useRouter();

const form = reactive({
  login: 'admin@gaso.lv',
  password: 'password',
});

const errorMessage = ref('');
const submitting = ref(false);

function extractError(error) {
  return error.response?.data?.message || 'Neizdevas pieslegties sistemai.';
}

async function submitLogin() {
  submitting.value = true;
  errorMessage.value = '';

  try {
    const response = await api.post('/login', form);
    const payload = response.data.data;

    setSession(payload);
    router.push({ name: 'dashboard' });
  } catch (error) {
    errorMessage.value = extractError(error);
  } finally {
    submitting.value = false;
  }
}
</script>