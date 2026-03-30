<template>
  <main class="login-shell">
    <section class="login-hero">
      <h1>Gāzes balonu uzskaites sistēma</h1>
      <p class="lead">
        Vienota darba vide balonu uzskaitei, klientu apkalpošanai un darījumu vēsturei.
      </p>

      <p class="lead login-highlight">
        {{
          adminRegistrationAvailable
            ? 'Sistēmā vēl nav administratora. Izveido pirmo administratora kontu un turpini darbu bez seederiem.'
            : 'Administratora reģistrācijas sadaļa ir pieejama šeit pašā login lapā, bet pēc pirmā administratora izveides tā kļūst tikai informatīva.'
        }}
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
        <div class="auth-mode-switch" role="tablist" aria-label="Autentifikācijas režīms">
          <button
            class="ghost-button tiny-button"
            type="button"
            :class="{ 'is-active': mode === 'login' }"
            @click="mode = 'login'"
          >
            Pieslēgties
          </button>
          <button
            class="ghost-button tiny-button"
            type="button"
            :class="{ 'is-active': mode === 'register' }"
            @click="mode = 'register'"
          >
            Reģistrēt administratoru
          </button>
        </div>

        <h2>{{ mode === 'login' ? 'Ienākt sistēmā' : 'Izveidot administratoru' }}</h2>
        <p v-if="mode === 'register'" class="section-description">
          {{
            adminRegistrationAvailable
              ? 'Publiski var izveidot tikai pirmo administratora kontu. Pēc tam jaunus administratorus varēs pievienot no paneļa.'
              : 'Pirmais administrators sistēmā jau ir izveidots. Nākamos administratorus var pievienot tikai no administratora paneļa.'
          }}
        </p>
      </div>

      <form v-if="mode === 'login'" class="stack" @submit.prevent="submitLogin">
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

      <form v-else class="stack" @submit.prevent="submitRegistration">
        <fieldset class="stack auth-fieldset" :disabled="submitting || !adminRegistrationAvailable">
        <div class="two-column auth-grid">
          <label>
            <span>Vārds</span>
            <input v-model.trim="registrationForm.name" type="text" placeholder="Anna" required />
          </label>

          <label>
            <span>Uzvārds</span>
            <input v-model.trim="registrationForm.surname" type="text" placeholder="Admina" required />
          </label>
        </div>

        <label>
          <span>E-pasts</span>
          <input v-model.trim="registrationForm.email" type="email" placeholder="admin@gaso.lv" required />
        </label>

        <label>
          <span>Telefons</span>
          <input v-model.trim="registrationForm.phone" type="text" placeholder="+37120000001" />
        </label>

        <div class="two-column auth-grid">
          <label>
            <span>Parole</span>
            <input v-model="registrationForm.password" type="password" placeholder="Vismaz 6 simboli" required />
          </label>

          <label>
            <span>Atkārtot paroli</span>
            <input
              v-model="registrationForm.password_confirmation"
              type="password"
              placeholder="Atkārto paroli"
              required
            />
          </label>
        </div>

        <button class="primary-button" type="submit" :disabled="submitting">
          {{
            !adminRegistrationAvailable
              ? 'Reģistrācija nav pieejama'
              : submitting
                ? 'Notiek reģistrācija...'
                : 'Izveidot administratoru'
          }}
        </button>
        </fieldset>
      </form>
    </section>

    <div v-if="errorMessage" class="notice error toast-notice">{{ errorMessage }}</div>
  </main>
</template>

<script setup>
import { onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import { useRouter } from 'vue-router';

import api from '../lib/api';
import { setSession } from '../lib/session';

const router = useRouter();

const form = reactive({
  login: 'admin1@gaso.lv',
  password: 'password',
});

const registrationForm = reactive({
  name: '',
  surname: '',
  email: '',
  phone: '',
  password: '',
  password_confirmation: '',
});

const errorMessage = ref('');
const submitting = ref(false);
const adminRegistrationAvailable = ref(false);
const mode = ref('login');
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

async function loadAdminRegistrationStatus() {
  try {
    const response = await api.get('/register/admin');
    adminRegistrationAvailable.value = Boolean(response.data.data?.available);

  } catch {
    adminRegistrationAvailable.value = false;
  }
}

async function submitRegistration() {
  submitting.value = true;
  errorMessage.value = '';

  try {
    const response = await api.post('/register/admin', registrationForm);
    const payload = response.data.data;

    setSession(payload);
    router.push({ name: 'dashboard' });
  } catch (error) {
    errorMessage.value = extractError(error);
    await loadAdminRegistrationStatus();
  } finally {
    submitting.value = false;
  }
}

onMounted(() => {
  loadAdminRegistrationStatus();
});

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