<template>
  <main class="login-shell">
    <section class="login-hero">
      <h1>Gāzes balonu uzskaites sistēma</h1>
      <p class="lead">
        Vienota darba vide balonu uzskaitei, klientu apkalpošanai un darījumu vēsturei.
      </p>

      <div class="credential-card panel">
        <h2>Demo pieejas</h2>
        <ul>
          <li>Administrators: admin1@gaso.lv / password</li>
          <li>Darbinieks: darbinieks@gaso.lv / password</li>
          <li>Klients: klients1 / password</li>
        </ul>
      </div>
    </section>

    <section class="login-panel panel">
      <div>
        <h2>Ienākt sistēmā</h2>
      </div>

      <form class="stack" @submit.prevent="submitLogin">
        <label>
          <span>Lietotājvārds vai e-pasts</span>
          <input v-model.trim="form.login" type="text" placeholder="E-pasts" required />
        </label>

        <label>
          <span>Parole</span>
          <input v-model="form.password" type="password" placeholder="Parole" required />
        </label>

        <button class="primary-button" type="submit" :disabled="submitting">
          {{ submitting ? 'Notiek pieslēgšanās...' : 'Pieslēgties' }}
        </button>
      </form>
    </section>

    <div v-if="errorMessage" class="notice error toast-notice">{{ errorMessage }}</div>
  </main>
</template>

<script setup>
import { onBeforeUnmount, reactive, ref, watch } from 'vue';
import { useRouter } from 'vue-router';

import api from '../lib/api';
import { setSession } from '../lib/session';

const router = useRouter();

const form = reactive({
  login: 'admin1@gaso.lv',
  password: 'password',
});

const errorMessage = ref('');
const submitting = ref(false);
const toastDuration = 3200;
let toastTimeoutId;

function extractError(error) {
  return error.response?.data?.message || 'Neizdevās pieslēgties sistēmai.';
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

watch(errorMessage, (value) => {
  if (toastTimeoutId) {
    clearTimeout(toastTimeoutId);
  }

  if (!value) {
    return;
  }

  // Keep the latest login error visible briefly, then clear it automatically.
  toastTimeoutId = window.setTimeout(() => {
    errorMessage.value = '';
  }, toastDuration);
});

onBeforeUnmount(() => {
  if (toastTimeoutId) {
    clearTimeout(toastTimeoutId);
  }
});
</script>