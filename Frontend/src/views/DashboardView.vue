<template>
  <main class="dashboard-shell">
    <aside class="dashboard-sidebar panel">
      <div>
        <p class="eyebrow">GASO panelis</p>
        <h1>Balonu uzskaite</h1>
        <p class="sidebar-copy">
          Pieslegtais lietotajs: {{ fullName }}. Loma: {{ roleLabel }}.
        </p>
      </div>

      <div class="sidebar-summary">
        <div>
          <span class="sidebar-label">E-pasts</span>
          <strong>{{ session?.user?.email }}</strong>
        </div>
        <div v-if="isClient">
          <span class="sidebar-label">Lietotajvards</span>
          <strong>{{ session?.user?.username }}</strong>
        </div>
      </div>

      <nav class="sidebar-nav">
        <a href="#overview">Parskats</a>
        <a v-if="isEmployee" href="#cylinders">Baloni</a>
        <a v-if="isEmployee" href="#transactions">Darijumi</a>
        <a v-if="isAdmin" href="#people">Cilveki</a>
        <a v-if="isEmployee" href="#reports">Atskaites</a>
        <a v-if="isClient" href="#profile">Mans profils</a>
      </nav>

      <button class="ghost-button" type="button" @click="logout">Iziet</button>
    </aside>

    <section class="dashboard-main">
      <div v-if="booting" class="panel loading-panel">Ieladeju sistemos datus...</div>

      <template v-else>
        <div v-if="notice.text" class="notice" :class="notice.type">{{ notice.text }}</div>

        <AppSection
          id="overview"
          eyebrow="Darba kopsavilkums"
          title="Sistema vienuviet"
          :description="isClient ? 'Klients redz savu informaciju un darijumu vesturi.' : 'Darbinieki var parvaldit balonus, darijumus un veidot atskaites.'"
        >
          <div class="metric-grid" v-if="isEmployee">
            <MetricCard label="Baloni kopa" :value="dashboard.metrics.totalCylinders" hint="Visi sistemi reģistretie baloni" />
            <MetricCard label="Aktivi pie klientiem" :value="dashboard.metrics.activeIssued" hint="Baloni ar statusu pie klienta" />
            <MetricCard label="Klienti" :value="dashboard.metrics.totalClients" hint="Aktivie klientu ieraksti" />
            <MetricCard label="Inspekcija driz" :value="dashboard.metrics.inspectionDueSoon" hint="Parbaude tuvako 30 dienu laika" />
          </div>

          <div v-else class="metric-grid">
            <MetricCard label="Mani darijumi" :value="transactions.length" hint="Visa piesaistita vesture" />
            <MetricCard label="Pilseta" :value="session?.user?.city || '-'" hint="Klienta registracijas vieta" />
            <MetricCard label="Adrese" :value="clientAddress" hint="Piegades vai kontakta adrese" />
          </div>

          <div v-if="isEmployee" class="two-column">
            <div class="panel inset-panel">
              <h3>Statusu sadalijums</h3>
              <ul class="breakdown-list">
                <li v-for="item in dashboard.statusBreakdown" :key="item.name">
                  <span>{{ item.name }}</span>
                  <strong>{{ item.total }}</strong>
                </li>
              </ul>
            </div>

            <div class="panel inset-panel">
              <h3>Pedejie darijumi</h3>
              <div class="table-wrapper compact-list">
                <table>
                  <thead>
                    <tr>
                      <th>Balons</th>
                      <th>Klients</th>
                      <th>Darbiba</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="item in dashboard.recentTransactions" :key="item.id">
                      <td>{{ item.cylinder?.serial_number }}</td>
                      <td>{{ item.client?.name }} {{ item.client?.surname }}</td>
                      <td>{{ item.action_type }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </AppSection>

        <AppSection
          v-if="isEmployee"
          id="cylinders"
          eyebrow="Balonu registrs"
          title="Balonu parvaldiba"
          description="Meklet balonus pec serijas numura, mainit statusu un admin lomai uzturet pilnus ierakstus."
        >
          <div class="toolbar-grid">
            <label>
              <span>Meklet</span>
              <input v-model.trim="cylinderFilters.search" type="text" placeholder="Serijas numurs" />
            </label>
            <label>
              <span>Statuss</span>
              <select v-model="cylinderFilters.statusId">
                <option value="">Visi statusi</option>
                <option v-for="status in statuses" :key="status.id" :value="String(status.id)">
                  {{ status.name }}
                </option>
              </select>
            </label>
            <div class="button-row align-end">
              <button class="secondary-button" type="button" @click="refreshCylinders">Filtrēt</button>
              <button class="ghost-button" type="button" @click="resetCylinderFilters">Notirit</button>
            </div>
          </div>

          <div class="two-column wide-right">
            <div class="panel inset-panel table-panel">
              <div class="table-wrapper">
                <table>
                  <thead>
                    <tr>
                      <th>Serijas numurs</th>
                      <th>Statuss</th>
                      <th>Ietilpiba</th>
                      <th>Inspekcija</th>
                      <th>Klients</th>
                      <th>Maina statusu</th>
                      <th v-if="isAdmin">Darbibas</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="cylinder in cylinders" :key="cylinder.id">
                      <td>{{ cylinder.serial_number }}</td>
                      <td><StatusBadge :label="cylinder.status?.name || 'nav'" /></td>
                      <td>{{ cylinder.capacity }} L</td>
                      <td>{{ formatDate(cylinder.inspection_date) }}</td>
                      <td>{{ currentClientName(cylinder) }}</td>
                      <td>
                        <div class="inline-form compact-inline">
                          <select v-model="statusDrafts[cylinder.id]">
                            <option v-for="status in statuses" :key="status.id" :value="status.id">
                              {{ status.name }}
                            </option>
                          </select>
                          <button class="secondary-button tiny-button" type="button" @click="applyStatusUpdate(cylinder)">
                            Saglabat
                          </button>
                        </div>
                      </td>
                      <td v-if="isAdmin">
                        <div class="button-row compact-row">
                          <button class="ghost-button tiny-button" type="button" @click="selectCylinder(cylinder)">Rediget</button>
                          <button class="danger-button tiny-button" type="button" @click="removeCylinder(cylinder)">Dzest</button>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <form v-if="isAdmin" class="panel inset-panel stack" @submit.prevent="submitCylinder">
              <div>
                <h3>{{ cylinderForm.id ? 'Rediget balonu' : 'Pievienot balonu' }}</h3>
                <p class="section-description">Saglabat serijas numuru, datumu, ietilpibu un statusu.</p>
              </div>

              <label>
                <span>Statuss</span>
                <select v-model="cylinderForm.status_id" required>
                  <option value="" disabled>Izveleties statusu</option>
                  <option v-for="status in statuses" :key="status.id" :value="String(status.id)">
                    {{ status.name }}
                  </option>
                </select>
              </label>

              <label>
                <span>Serijas numurs</span>
                <input v-model.trim="cylinderForm.serial_number" type="text" required />
              </label>

              <div class="toolbar-grid">
                <label>
                  <span>Ietilpiba</span>
                  <input v-model="cylinderForm.capacity" type="number" min="0.5" step="0.5" required />
                </label>
                <label>
                  <span>Razosanas datums</span>
                  <input v-model="cylinderForm.manufacture_date" type="date" required />
                </label>
              </div>

              <label>
                <span>Inspekcijas datums</span>
                <input v-model="cylinderForm.inspection_date" type="date" required />
              </label>

              <label>
                <span>Piezimes</span>
                <textarea v-model="cylinderForm.notes" rows="4" />
              </label>

              <div class="button-row">
                <button class="primary-button" type="submit">{{ cylinderForm.id ? 'Atjaunot' : 'Pievienot' }}</button>
                <button class="ghost-button" type="button" @click="resetCylinderForm">Atcelt</button>
              </div>
            </form>
          </div>
        </AppSection>

        <AppSection
          v-if="isEmployee"
          id="transactions"
          eyebrow="Darijumu modulis"
          title="Izsniegsana un atgriesana"
          description="Reģistret balonu kustibu un apskatit pilnu vesturi."
        >
          <div class="three-column">
            <form class="panel inset-panel stack" @submit.prevent="submitIssue">
              <div>
                <h3>Izsniegt klientam</h3>
              </div>

              <label>
                <span>Balons</span>
                <select v-model="issueForm.cylinder_id" required>
                  <option value="" disabled>Izveleties balonu</option>
                  <option v-for="cylinder in availableCylinders" :key="cylinder.id" :value="String(cylinder.id)">
                    {{ cylinder.serial_number }} - {{ cylinder.status?.name }}
                  </option>
                </select>
              </label>

              <label>
                <span>Klients</span>
                <select v-model="issueForm.client_id" required>
                  <option value="" disabled>Izveleties klientu</option>
                  <option v-for="client in clients" :key="client.id" :value="String(client.id)">
                    {{ client.name }} {{ client.surname }}
                  </option>
                </select>
              </label>

              <label>
                <span>Datums</span>
                <input v-model="issueForm.issue_date" type="date" required />
              </label>

              <button class="primary-button" type="submit">Izsniegt balonu</button>
            </form>

            <form class="panel inset-panel stack" @submit.prevent="submitReturn">
              <div>
                <h3>Pienemt atpakal</h3>
              </div>

              <label>
                <span>Balons</span>
                <select v-model="returnForm.cylinder_id" required>
                  <option value="" disabled>Izveleties balonu</option>
                  <option v-for="cylinder in issuedCylinders" :key="cylinder.id" :value="String(cylinder.id)">
                    {{ cylinder.serial_number }} - {{ currentClientName(cylinder) }}
                  </option>
                </select>
              </label>

              <label>
                <span>Jaunais statuss</span>
                <select v-model="returnForm.status_id" required>
                  <option value="" disabled>Izveleties statusu</option>
                  <option v-for="status in returnStatuses" :key="status.id" :value="String(status.id)">
                    {{ status.name }}
                  </option>
                </select>
              </label>

              <label>
                <span>Datums</span>
                <input v-model="returnForm.return_date" type="date" required />
              </label>

              <button class="primary-button" type="submit">Registrēt atgriesanu</button>
            </form>

            <div class="panel inset-panel stack">
              <div>
                <h3>Darijumu fakti</h3>
              </div>
              <ul class="facts-list">
                <li>Izsniegsana automātiski parsledz statusu uz pie klienta.</li>
                <li>Atgriesana vienlaikus saglaba vesturi un uzstada jauno statusu.</li>
                <li>Klienti redz tikai savus darijumus.</li>
              </ul>
            </div>
          </div>

          <div class="panel inset-panel table-panel">
            <div class="table-wrapper">
              <table>
                <thead>
                  <tr>
                    <th>Datums</th>
                    <th>Balons</th>
                    <th>Klients</th>
                    <th>Darbinieks</th>
                    <th>Darbiba</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in transactions" :key="item.id">
                    <td>{{ item.action_type === 'izsniegts' ? formatDate(item.issue_date) : formatDate(item.return_date) }}</td>
                    <td>{{ item.cylinder?.serial_number }}</td>
                    <td>{{ item.client?.name }} {{ item.client?.surname }}</td>
                    <td>{{ item.employee?.name }} {{ item.employee?.surname }}</td>
                    <td>{{ item.action_type }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </AppSection>

        <AppSection
          v-if="isAdmin"
          id="people"
          eyebrow="Lietotaju dati"
          title="Klienti un darbinieki"
          description="Administratoram pieejama klientu un darbinieku uzturesana."
        >
          <div class="two-column">
            <form class="panel inset-panel stack" @submit.prevent="submitClient">
              <div>
                <h3>Pievienot klientu</h3>
              </div>

              <div class="toolbar-grid">
                <label><span>Vards</span><input v-model.trim="clientForm.name" type="text" required /></label>
                <label><span>Uzvards</span><input v-model.trim="clientForm.surname" type="text" required /></label>
              </div>
              <div class="toolbar-grid">
                <label><span>E-pasts</span><input v-model.trim="clientForm.email" type="email" required /></label>
                <label><span>Lietotajvards</span><input v-model.trim="clientForm.username" type="text" required /></label>
              </div>
              <div class="toolbar-grid">
                <label><span>Parole</span><input v-model="clientForm.password" type="password" required /></label>
                <label><span>Telefons</span><input v-model.trim="clientForm.phone" type="text" /></label>
              </div>
              <div class="toolbar-grid">
                <label><span>Iela</span><input v-model.trim="clientForm.street" type="text" required /></label>
                <label><span>Pilseta</span><input v-model.trim="clientForm.city" type="text" required /></label>
              </div>
              <div class="toolbar-grid">
                <label><span>Majas nr.</span><input v-model="clientForm.home_number" type="number" min="1" required /></label>
                <label><span>Dzivoklis</span><input v-model="clientForm.flat_number" type="number" min="1" /></label>
              </div>
              <label><span>Pasta indekss</span><input v-model.trim="clientForm.zip_code" type="text" required /></label>

              <button class="primary-button" type="submit">Pievienot klientu</button>
            </form>

            <form class="panel inset-panel stack" @submit.prevent="submitEmployee">
              <div>
                <h3>Pievienot darbinieku</h3>
              </div>

              <div class="toolbar-grid">
                <label><span>Vards</span><input v-model.trim="employeeForm.name" type="text" required /></label>
                <label><span>Uzvards</span><input v-model.trim="employeeForm.surname" type="text" required /></label>
              </div>
              <div class="toolbar-grid">
                <label><span>E-pasts</span><input v-model.trim="employeeForm.email" type="email" required /></label>
                <label><span>Telefons</span><input v-model.trim="employeeForm.phone" type="text" /></label>
              </div>
              <div class="toolbar-grid">
                <label><span>Parole</span><input v-model="employeeForm.password" type="password" required /></label>
                <label>
                  <span>Loma</span>
                  <select v-model="employeeForm.role" required>
                    <option value="administrators">Administrators</option>
                    <option value="darbinieks">Darbinieks</option>
                  </select>
                </label>
              </div>

              <button class="primary-button" type="submit">Pievienot darbinieku</button>
            </form>
          </div>

          <div class="two-column">
            <div class="panel inset-panel table-panel">
              <h3>Klientu saraksts</h3>
              <div class="table-wrapper compact-list">
                <table>
                  <thead>
                    <tr>
                      <th>Klients</th>
                      <th>Lietotajvards</th>
                      <th>E-pasts</th>
                      <th></th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="client in clients" :key="client.id">
                      <td>{{ client.name }} {{ client.surname }}</td>
                      <td>{{ client.username }}</td>
                      <td>{{ client.email }}</td>
                      <td><button class="danger-button tiny-button" type="button" @click="removeClient(client)">Dzest</button></td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <div class="panel inset-panel table-panel">
              <h3>Darbinieku saraksts</h3>
              <div class="table-wrapper compact-list">
                <table>
                  <thead>
                    <tr>
                      <th>Darbinieks</th>
                      <th>Loma</th>
                      <th>E-pasts</th>
                      <th></th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="employee in employees" :key="employee.id">
                      <td>{{ employee.name }} {{ employee.surname }}</td>
                      <td>{{ employee.role }}</td>
                      <td>{{ employee.email }}</td>
                      <td><button class="danger-button tiny-button" type="button" @click="removeEmployee(employee)">Dzest</button></td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </AppSection>

        <AppSection
          v-if="isEmployee"
          id="reports"
          eyebrow="Atskaites"
          title="Atskaitu generešana"
          description="Sistēma saglaba, kas un kad izveidojis atskaiti."
        >
          <div class="button-row wrap-row">
            <button class="secondary-button" type="button" @click="generateReport('balonu atskaite')">Balonu atskaite</button>
            <button class="secondary-button" type="button" @click="generateReport('klientu atskaite')">Klientu atskaite</button>
            <button class="secondary-button" type="button" @click="generateReport('darijumu atskaite')">Darijumu atskaite</button>
          </div>

          <div class="two-column wide-right">
            <div class="panel inset-panel">
              <h3>Pedejas atskaites</h3>
              <ul class="breakdown-list">
                <li v-for="item in reports" :key="item.id">
                  <span>{{ item.type }}</span>
                  <strong>{{ item.employee?.name }} {{ item.employee?.surname }}</strong>
                </li>
              </ul>
            </div>

            <div class="panel inset-panel" v-if="activeReport">
              <h3>{{ activeReport.report.type }}</h3>
              <p class="section-description">Izveidota {{ formatDateTime(activeReport.report.created_at) }}</p>

              <div class="report-totals">
                <div v-for="(value, key) in activeReport.payload.totals" :key="key" class="report-total-card">
                  <span>{{ key }}</span>
                  <strong>{{ value }}</strong>
                </div>
              </div>

              <div class="table-wrapper compact-list">
                <table v-if="activeReport.report.type === 'balonu atskaite'">
                  <thead>
                    <tr>
                      <th>Balons</th>
                      <th>Statuss</th>
                      <th>Inspekcija</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="item in activeReport.payload.items" :key="item.id">
                      <td>{{ item.serial_number }}</td>
                      <td>{{ item.status?.name }}</td>
                      <td>{{ formatDate(item.inspection_date) }}</td>
                    </tr>
                  </tbody>
                </table>

                <table v-else-if="activeReport.report.type === 'klientu atskaite'">
                  <thead>
                    <tr>
                      <th>Klients</th>
                      <th>E-pasts</th>
                      <th>Darijumu skaits</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="item in activeReport.payload.items" :key="item.id">
                      <td>{{ item.name }} {{ item.surname }}</td>
                      <td>{{ item.email }}</td>
                      <td>{{ item.transactions_count }}</td>
                    </tr>
                  </tbody>
                </table>

                <table v-else>
                  <thead>
                    <tr>
                      <th>Balons</th>
                      <th>Klients</th>
                      <th>Darbiba</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="item in activeReport.payload.items" :key="item.id">
                      <td>{{ item.cylinder?.serial_number }}</td>
                      <td>{{ item.client?.name }} {{ item.client?.surname }}</td>
                      <td>{{ item.action_type }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </AppSection>

        <AppSection
          v-if="isClient"
          id="profile"
          eyebrow="Klienta zona"
          title="Mana informacija"
          description="Klients var parskatit savus kontaktus un izsniegsanas vesturi."
        >
          <div class="two-column">
            <div class="panel inset-panel stack">
              <div>
                <h3>Profila dati</h3>
              </div>
              <div class="profile-grid">
                <div><span>Vards</span><strong>{{ session?.user?.name }} {{ session?.user?.surname }}</strong></div>
                <div><span>E-pasts</span><strong>{{ session?.user?.email }}</strong></div>
                <div><span>Telefons</span><strong>{{ session?.user?.phone || '-' }}</strong></div>
                <div><span>Adrese</span><strong>{{ clientAddress }}</strong></div>
              </div>
            </div>

            <div class="panel inset-panel table-panel">
              <h3>Mana darijumu vesture</h3>
              <div class="table-wrapper compact-list">
                <table>
                  <thead>
                    <tr>
                      <th>Balons</th>
                      <th>Darbiba</th>
                      <th>Datums</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="item in transactions" :key="item.id">
                      <td>{{ item.cylinder?.serial_number }}</td>
                      <td>{{ item.action_type }}</td>
                      <td>{{ item.action_type === 'izsniegts' ? formatDate(item.issue_date) : formatDate(item.return_date) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </AppSection>
      </template>
    </section>
  </main>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';

import AppSection from '../components/AppSection.vue';
import MetricCard from '../components/MetricCard.vue';
import StatusBadge from '../components/StatusBadge.vue';
import api from '../lib/api';
import { clearSession, getSession, setSession } from '../lib/session';

const router = useRouter();

const session = ref(getSession());
const booting = ref(true);

const dashboard = reactive({
  metrics: {
    totalCylinders: 0,
    totalClients: 0,
    totalEmployees: 0,
    activeIssued: 0,
    inspectionDueSoon: 0,
  },
  statusBreakdown: [],
  recentTransactions: [],
});

const statuses = ref([]);
const cylinders = ref([]);
const clients = ref([]);
const employees = ref([]);
const transactions = ref([]);
const reports = ref([]);
const activeReport = ref(null);
const statusDrafts = reactive({});

const notice = reactive({
  type: 'info',
  text: '',
});

const cylinderFilters = reactive({
  search: '',
  statusId: '',
});

const cylinderForm = reactive({
  id: null,
  status_id: '',
  serial_number: '',
  capacity: '11',
  manufacture_date: '',
  inspection_date: '',
  notes: '',
});

const issueForm = reactive({
  cylinder_id: '',
  client_id: '',
  issue_date: today(),
});

const returnForm = reactive({
  cylinder_id: '',
  status_id: '',
  return_date: today(),
});

const clientForm = reactive({
  name: '',
  surname: '',
  email: '',
  password: 'password',
  phone: '',
  street: '',
  home_number: '1',
  flat_number: '',
  city: '',
  zip_code: '',
  username: '',
});

const employeeForm = reactive({
  name: '',
  surname: '',
  email: '',
  password: 'password',
  phone: '',
  role: 'darbinieks',
});

const isEmployee = computed(() => session.value?.actorType === 'employee');
const isAdmin = computed(() => isEmployee.value && session.value?.user?.role === 'administrators');
const isClient = computed(() => session.value?.actorType === 'client');

const fullName = computed(() => {
  const user = session.value?.user;
  return user ? `${user.name} ${user.surname}` : '-';
});

const roleLabel = computed(() => {
  if (isClient.value) {
    return 'Klients';
  }

  return session.value?.user?.role === 'administrators' ? 'Administrators' : 'Darbinieks';
});

const issuedCylinders = computed(() => cylinders.value.filter((item) => item.status?.name === 'pie klienta'));
const availableCylinders = computed(() => cylinders.value.filter((item) => item.status?.name !== 'pie klienta'));
const returnStatuses = computed(() => statuses.value.filter((item) => item.name !== 'pie klienta'));

const clientAddress = computed(() => {
  const user = session.value?.user;

  if (!user) {
    return '-';
  }

  const address = [user.street, user.homeNumber, user.flatNumber ? `-${user.flatNumber}` : '', user.city, user.zipCode]
    .filter(Boolean)
    .join(' ')
    .replace(' -', '-');

  return address || '-';
});

function today() {
  return new Date().toISOString().slice(0, 10);
}

function setNotice(type, text) {
  notice.type = type;
  notice.text = text;
}

function clearNotice() {
  notice.text = '';
}

function extractError(error) {
  return error.response?.data?.message || 'Neizdevas izpildit darbibu.';
}

function formatDate(value) {
  if (!value) {
    return '-';
  }

  return new Intl.DateTimeFormat('lv-LV').format(new Date(value));
}

function formatDateTime(value) {
  if (!value) {
    return '-';
  }

  return new Intl.DateTimeFormat('lv-LV', {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(new Date(value));
}

function currentClientName(cylinder) {
  if (cylinder.status?.name !== 'pie klienta') {
    return '-';
  }

  const client = cylinder.latest_transaction?.client;
  return client ? `${client.name} ${client.surname}` : '-';
}

function resetCylinderFilters() {
  cylinderFilters.search = '';
  cylinderFilters.statusId = '';
  refreshCylinders();
}

function resetCylinderForm() {
  cylinderForm.id = null;
  cylinderForm.status_id = statuses.value[0] ? String(statuses.value[0].id) : '';
  cylinderForm.serial_number = '';
  cylinderForm.capacity = '11';
  cylinderForm.manufacture_date = '';
  cylinderForm.inspection_date = '';
  cylinderForm.notes = '';
}

function resetClientForm() {
  clientForm.name = '';
  clientForm.surname = '';
  clientForm.email = '';
  clientForm.password = 'password';
  clientForm.phone = '';
  clientForm.street = '';
  clientForm.home_number = '1';
  clientForm.flat_number = '';
  clientForm.city = '';
  clientForm.zip_code = '';
  clientForm.username = '';
}

function resetEmployeeForm() {
  employeeForm.name = '';
  employeeForm.surname = '';
  employeeForm.email = '';
  employeeForm.password = 'password';
  employeeForm.phone = '';
  employeeForm.role = 'darbinieks';
}

function selectCylinder(cylinder) {
  cylinderForm.id = cylinder.id;
  cylinderForm.status_id = String(cylinder.status_id);
  cylinderForm.serial_number = cylinder.serial_number;
  cylinderForm.capacity = String(cylinder.capacity);
  cylinderForm.manufacture_date = String(cylinder.manufacture_date).slice(0, 10);
  cylinderForm.inspection_date = String(cylinder.inspection_date).slice(0, 10);
  cylinderForm.notes = cylinder.notes || '';
}

async function refreshSession() {
  const response = await api.get('/me');
  session.value = {
    ...session.value,
    ...response.data.data,
    token: session.value?.token,
  };
  setSession(session.value);
}

async function refreshDashboard() {
  const response = await api.get('/dashboard');
  Object.assign(dashboard, response.data.data);
}

async function refreshStatuses() {
  const response = await api.get('/statuses');
  statuses.value = response.data.data;

  if (!cylinderForm.status_id && statuses.value[0]) {
    cylinderForm.status_id = String(statuses.value[0].id);
  }

  if (!returnForm.status_id && returnStatuses.value[0]) {
    returnForm.status_id = String(returnStatuses.value[0].id);
  }
}

async function refreshCylinders() {
  const params = {};

  if (cylinderFilters.search) {
    params.search = cylinderFilters.search;
  }

  if (cylinderFilters.statusId) {
    params.status_id = cylinderFilters.statusId;
  }

  const response = await api.get('/cylinders', { params });
  cylinders.value = response.data.data;

  cylinders.value.forEach((item) => {
    statusDrafts[item.id] = item.status_id;
  });
}

async function refreshClients() {
  const response = await api.get('/clients');
  clients.value = response.data.data;
}

async function refreshEmployees() {
  if (!isAdmin.value) {
    return;
  }

  const response = await api.get('/employees');
  employees.value = response.data.data;
}

async function refreshTransactions() {
  const response = await api.get('/transactions');
  transactions.value = response.data.data;
}

async function refreshReports() {
  if (!isEmployee.value) {
    return;
  }

  const response = await api.get('/reports');
  reports.value = response.data.data;
}

async function bootstrap() {
  booting.value = true;

  try {
    await refreshSession();

    if (isClient.value) {
      await refreshTransactions();
    } else {
      const tasks = [
        refreshDashboard(),
        refreshStatuses(),
        refreshCylinders(),
        refreshClients(),
        refreshTransactions(),
        refreshReports(),
      ];

      if (isAdmin.value) {
        tasks.push(refreshEmployees());
      }

      await Promise.all(tasks);
    }

    clearNotice();
  } catch (error) {
    clearSession();
    router.push({ name: 'login' });
    setNotice('error', extractError(error));
  } finally {
    booting.value = false;
  }
}

async function applyStatusUpdate(cylinder) {
  try {
    await api.patch(`/cylinders/${cylinder.id}/status`, {
      status_id: statusDrafts[cylinder.id],
    });

    await Promise.all([refreshCylinders(), refreshDashboard()]);
    setNotice('success', `Balona ${cylinder.serial_number} statuss atjaunots.`);
  } catch (error) {
    setNotice('error', extractError(error));
  }
}

async function submitCylinder() {
  const payload = {
    ...cylinderForm,
    status_id: Number(cylinderForm.status_id),
    capacity: Number(cylinderForm.capacity),
    home_number: undefined,
  };

  delete payload.id;
  delete payload.home_number;

  try {
    if (cylinderForm.id) {
      await api.put(`/cylinders/${cylinderForm.id}`, payload);
      setNotice('success', 'Balons atjaunots.');
    } else {
      await api.post('/cylinders', payload);
      setNotice('success', 'Balons pievienots.');
    }

    resetCylinderForm();
    await Promise.all([refreshCylinders(), refreshDashboard()]);
  } catch (error) {
    setNotice('error', extractError(error));
  }
}

async function removeCylinder(cylinder) {
  if (!window.confirm(`Dzest balonu ${cylinder.serial_number}?`)) {
    return;
  }

  try {
    await api.delete(`/cylinders/${cylinder.id}`);
    await Promise.all([refreshCylinders(), refreshDashboard()]);
    resetCylinderForm();
    setNotice('success', 'Balons dzests.');
  } catch (error) {
    setNotice('error', extractError(error));
  }
}

async function submitIssue() {
  try {
    await api.post('/transactions/issue', {
      cylinder_id: Number(issueForm.cylinder_id),
      client_id: Number(issueForm.client_id),
      issue_date: issueForm.issue_date,
    });

    issueForm.cylinder_id = '';
    issueForm.client_id = '';
    issueForm.issue_date = today();

    await Promise.all([refreshCylinders(), refreshTransactions(), refreshDashboard()]);
    setNotice('success', 'Balons izsniegts klientam.');
  } catch (error) {
    setNotice('error', extractError(error));
  }
}

async function submitReturn() {
  try {
    await api.post('/transactions/return', {
      cylinder_id: Number(returnForm.cylinder_id),
      status_id: Number(returnForm.status_id),
      return_date: returnForm.return_date,
    });

    returnForm.cylinder_id = '';
    returnForm.return_date = today();

    await Promise.all([refreshCylinders(), refreshTransactions(), refreshDashboard()]);
    setNotice('success', 'Balona atgriesana registrēta.');
  } catch (error) {
    setNotice('error', extractError(error));
  }
}

async function submitClient() {
  try {
    await api.post('/clients', {
      ...clientForm,
      home_number: Number(clientForm.home_number),
      flat_number: clientForm.flat_number ? Number(clientForm.flat_number) : null,
    });

    resetClientForm();
    await Promise.all([refreshClients(), refreshDashboard()]);
    setNotice('success', 'Klients pievienots.');
  } catch (error) {
    setNotice('error', extractError(error));
  }
}

async function removeClient(client) {
  if (!window.confirm(`Dzest klientu ${client.name} ${client.surname}?`)) {
    return;
  }

  try {
    await api.delete(`/clients/${client.id}`);
    await Promise.all([refreshClients(), refreshDashboard()]);
    setNotice('success', 'Klients dzests.');
  } catch (error) {
    setNotice('error', extractError(error));
  }
}

async function submitEmployee() {
  try {
    await api.post('/employees', employeeForm);
    resetEmployeeForm();
    await Promise.all([refreshEmployees(), refreshDashboard()]);
    setNotice('success', 'Darbinieks pievienots.');
  } catch (error) {
    setNotice('error', extractError(error));
  }
}

async function removeEmployee(employee) {
  if (!window.confirm(`Dzest darbinieku ${employee.name} ${employee.surname}?`)) {
    return;
  }

  try {
    await api.delete(`/employees/${employee.id}`);
    await refreshEmployees();
    setNotice('success', 'Darbinieks dzests.');
  } catch (error) {
    setNotice('error', extractError(error));
  }
}

async function generateReport(type) {
  try {
    const response = await api.get('/reports/generate', {
      params: { type },
    });

    activeReport.value = response.data.data;
    await refreshReports();
    setNotice('success', `${type} izveidota.`);
  } catch (error) {
    setNotice('error', extractError(error));
  }
}

async function logout() {
  try {
    await api.post('/logout');
  } catch {
    // ignore logout transport errors
  } finally {
    clearSession();
    router.push({ name: 'login' });
  }
}

onMounted(() => {
  bootstrap();
});
</script>