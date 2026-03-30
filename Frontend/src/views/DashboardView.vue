<template>
  <main class="dashboard-shell">
    <aside class="dashboard-sidebar panel">
      <div>
        <h1>Balonu uzskaite</h1>
        <p class="sidebar-copy">
          Pieslēgtais lietotājs: {{ fullName }}. Loma: {{ roleLabel }}.
        </p>
      </div>

      <div class="sidebar-summary">
        <div>
          <span class="sidebar-label">E-pasts:</span>
          <strong>{{ session?.user?.email }}</strong>
        </div>
        <div v-if="isClient">
          <span class="sidebar-label">Lietotājvārds</span>
          <strong>{{ session?.user?.username }}</strong>
        </div>
      </div>

      <nav class="sidebar-nav">
        <a href="#overview">Pārskats</a>
        <a v-if="isEmployee" href="#cylinders">Baloni</a>
        <a v-if="isEmployee" href="#transactions">Darījumi</a>
        <a v-if="isAdmin" href="#people">Cilvēki</a>
        <a v-if="isEmployee" href="#reports">Atskaites</a>
        <a v-if="isClient" href="#profile">Mans profils</a>
      </nav>

      <button class="ghost-button" type="button" @click="logout">Iziet</button>
    </aside>

    <section class="dashboard-main">
      <div v-if="booting" class="panel loading-panel">Ielādēju sistēmas datus...</div>

      <template v-else>
        <div v-if="notice.text" class="notice toast-notice" :class="notice.type">{{ notice.text }}</div>

        <AppSection
          id="overview"
          title="Sistēma vienuviet"
          :description="isClient ? 'Klients redz savu informāciju un darījumu vēsturi.' : 'Darbinieki var pārvaldīt balonus, darījumus un veidot atskaites.'"
        >
          <div class="metric-grid" v-if="isEmployee">
            <MetricCard label="Baloni kopā" :value="dashboard.metrics.totalCylinders" hint="Visi sistēmā reģistrētie baloni" />
            <MetricCard label="Aktīvi pie klientiem" :value="dashboard.metrics.activeIssued" hint="Baloni ar statusu pie klienta" />
            <MetricCard label="Klienti" :value="dashboard.metrics.totalClients" hint="Aktīvie klientu ieraksti" />
            <MetricCard label="Inspekcija drīz" :value="dashboard.metrics.inspectionDueSoon" hint="Pārbaude tuvāko 30 dienu laikā" />
          </div>

          <div v-else class="metric-grid">
            <MetricCard label="Mani darījumi" :value="transactions.length" hint="Visa piesaistītā vēsture" />
            <MetricCard label="Pilsēta" :value="session?.user?.city || '-'" hint="Klienta reģistrācijas vieta" />
            <MetricCard label="Adrese" :value="clientAddress" hint="Piegādes vai kontakta adrese" />
          </div>

          <div v-if="isEmployee" class="two-column">
            <div class="panel inset-panel">
              <h3>Statusu sadalījums</h3>
              <ul class="breakdown-list">
                <li v-for="item in dashboard.statusBreakdown" :key="item.name">
                  <span>{{ item.name }}</span>
                  <strong>{{ item.total }}</strong>
                </li>
              </ul>
            </div>

            <div class="panel inset-panel">
              <h3>Pēdējie darījumi</h3>
              <div class="table-wrapper compact-list">
                <table>
                  <thead>
                    <tr>
                      <th>Balons</th>
                      <th>Klients</th>
                      <th>Darbība</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="item in paginatedRecentTransactions" :key="item.id">
                      <td>{{ item.cylinder?.serial_number }}</td>
                      <td>{{ item.client?.name }} {{ item.client?.surname }}</td>
                      <td>{{ item.action_type }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <div v-if="dashboard.recentTransactions.length > itemsPerPage" class="pagination-row">
                <button class="ghost-button tiny-button pagination-button" type="button" :disabled="pages.recentTransactions === 1" @click="changePage('recentTransactions', -1, totalRecentTransactionsPages)">&lt;</button>
                <p class="pagination-status">Lapa {{ pages.recentTransactions }} no {{ totalRecentTransactionsPages }}</p>
                <button class="ghost-button tiny-button pagination-button" type="button" :disabled="pages.recentTransactions === totalRecentTransactionsPages" @click="changePage('recentTransactions', 1, totalRecentTransactionsPages)">&gt;</button>
              </div>
            </div>
          </div>
        </AppSection>

        <AppSection
          v-if="isEmployee"
          id="cylinders"
          eyebrow="Balonu reģistrs"
          title="Balonu pārvaldība"
          description="Meklēt balonus pēc sērijas numura, mainīt statusu un admin lomai uzturēt pilnus ierakstus."
        >
          <div class="toolbar-grid section-action-gap">
            <label>
              <span>Meklēt</span>
              <input v-model.trim="cylinderFilters.search" type="text" placeholder="Sērijas numurs" />
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
              <button class="ghost-button" type="button" @click="resetCylinderFilters">Notīrīt</button>
            </div>
          </div>

          <div class="two-column wide-right cylinders-layout">
            <div class="panel inset-panel table-panel cylinders-table-panel">
              <div class="table-wrapper">
                <table>
                  <thead>
                    <tr>
                      <th>Sērijas numurs</th>
                      <th>Statuss</th>
                      <th>Ietilpība</th>
                      <th>Inspekcija</th>
                      <th>Klients</th>
                      <th>Mainīt statusu</th>
                      <th v-if="isAdmin">Darbības</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="cylinder in paginatedCylinders" :key="cylinder.id">
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
                            Saglabāt
                          </button>
                        </div>
                      </td>
                      <td v-if="isAdmin">
                        <div class="button-row compact-row">
                          <button class="ghost-button tiny-button" type="button" @click="selectCylinder(cylinder)">Rediģēt</button>
                          <button class="danger-button tiny-button" type="button" @click="removeCylinder(cylinder)">Dzēst</button>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <div v-if="cylinders.length > itemsPerPage" class="pagination-row">
                <button class="ghost-button tiny-button pagination-button" type="button" :disabled="pages.cylinders === 1" @click="changePage('cylinders', -1, totalCylindersPages)">&lt;</button>
                <p class="pagination-status">Lapa {{ pages.cylinders }} no {{ totalCylindersPages }}</p>
                <button class="ghost-button tiny-button pagination-button" type="button" :disabled="pages.cylinders === totalCylindersPages" @click="changePage('cylinders', 1, totalCylindersPages)">&gt;</button>
              </div>
            </div>

            <form v-if="isAdmin" class="panel inset-panel stack cylinders-form-panel" @submit.prevent="submitCylinder">
              <div>
                <h3>{{ cylinderForm.id ? 'Rediģēt balonu' : 'Pievienot balonu' }}</h3>
                <p class="section-description">Saglabāt sērijas numuru, datumu, ietilpību un statusu.</p>
              </div>

              <label>
                <span>Statuss</span>
                <select v-model="cylinderForm.status_id" required>
                  <option value="" disabled>Izvēlēties statusu</option>
                  <option v-for="status in statuses" :key="status.id" :value="String(status.id)">
                    {{ status.name }}
                  </option>
                </select>
              </label>

              <label>
                <span>Sērijas numurs</span>
                <input v-model.trim="cylinderForm.serial_number" type="text" required />
              </label>

              <div class="toolbar-grid">
                <label>
                  <span>Ietilpība</span>
                  <input v-model="cylinderForm.capacity" type="number" min="0.5" step="0.5" required />
                </label>
                <label>
                  <span>Ražošanas datums</span>
                  <input v-model="cylinderForm.manufacture_date" type="date" required />
                </label>
              </div>

              <label>
                <span>Inspekcijas datums</span>
                <input v-model="cylinderForm.inspection_date" type="date" required />
              </label>

              <label>
                <span>Piezīmes</span>
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
          eyebrow="Darījumu modulis"
          title="Izsniegšana un atgriešana"
          description="Reģistrēt balonu kustību un apskatīt pilnu vēsturi."
        >
          <div class="two-column section-action-gap">
            <form class="panel inset-panel stack" @submit.prevent="submitIssue">
              <div>
                <h3>Izsniegt klientam</h3>
              </div>

              <label>
                <span>Balons</span>
                <select v-model="issueForm.cylinder_id" required>
                  <option value="" disabled>Izvēlēties balonu</option>
                  <option v-for="cylinder in availableCylinders" :key="cylinder.id" :value="String(cylinder.id)">
                    {{ cylinder.serial_number }} - {{ cylinder.status?.name }}
                  </option>
                </select>
              </label>

              <label>
                <span>Klients</span>
                <select v-model="issueForm.client_id" required>
                  <option value="" disabled>Izvēlēties klientu</option>
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
                <h3>Pieņemt atpakaļ</h3>
              </div>

              <label>
                <span>Balons</span>
                <select v-model="returnForm.cylinder_id" required>
                  <option value="" disabled>Izvēlēties balonu</option>
                  <option v-for="cylinder in issuedCylinders" :key="cylinder.id" :value="String(cylinder.id)">
                    {{ cylinder.serial_number }} - {{ currentClientName(cylinder) }}
                  </option>
                </select>
              </label>

              <label>
                <span>Jaunais statuss</span>
                <select v-model="returnForm.status_id" required>
                  <option value="" disabled>Izvēlēties statusu</option>
                  <option v-for="status in returnStatuses" :key="status.id" :value="String(status.id)">
                    {{ status.name }}
                  </option>
                </select>
              </label>

              <label>
                <span>Datums</span>
                <input v-model="returnForm.return_date" type="date" required />
              </label>

                <button class="primary-button" type="submit">Reģistrēt atgriešanu</button>
            </form>
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
                    <th>Darbība</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in paginatedTransactions" :key="item.id">
                    <td>{{ item.action_type === 'izsniegts' ? formatDate(item.issue_date) : formatDate(item.return_date) }}</td>
                    <td>{{ item.cylinder?.serial_number }}</td>
                    <td>{{ item.client?.name }} {{ item.client?.surname }}</td>
                    <td>{{ item.employee?.name }} {{ item.employee?.surname }}</td>
                    <td>{{ item.action_type }}</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div v-if="transactions.length > itemsPerPage" class="pagination-row">
              <button class="ghost-button tiny-button pagination-button" type="button" :disabled="pages.transactions === 1" @click="changePage('transactions', -1, totalTransactionsPages)">&lt;</button>
              <p class="pagination-status">Lapa {{ pages.transactions }} no {{ totalTransactionsPages }}</p>
              <button class="ghost-button tiny-button pagination-button" type="button" :disabled="pages.transactions === totalTransactionsPages" @click="changePage('transactions', 1, totalTransactionsPages)">&gt;</button>
            </div>
          </div>
        </AppSection>

        <AppSection
          v-if="isAdmin"
          id="people"
          eyebrow="Lietotāju dati"
          title="Klienti un darbinieki"
          description="Administratoram pieejama klientu un darbinieku uzturēšana."
        >
          <div class="two-column people-grid people-forms-grid">
            <form class="panel inset-panel stack people-card people-form" @submit.prevent="submitClient">
              <div>
                <h3>{{ clientForm.id ? 'Rediģēt klientu' : 'Pievienot klientu' }}</h3>
                <p class="section-description">
                  {{ clientForm.id ? 'Atjaunināt klienta profila datus un saglabāt izmaiņas.' : 'Reģistrēt jaunu klienta profilu sistēmā.' }}
                </p>
              </div>

              <div class="toolbar-grid">
                <label><span>Vārds</span><input v-model.trim="clientForm.name" type="text" required /></label>
                <label><span>Uzvārds</span><input v-model.trim="clientForm.surname" type="text" required /></label>
              </div>
              <div class="toolbar-grid">
                <label><span>E-pasts</span><input v-model.trim="clientForm.email" type="email" required /></label>
                <label><span>Lietotājvārds</span><input v-model.trim="clientForm.username" type="text" required /></label>
              </div>
              <div class="toolbar-grid">
                <label>
                  <span>Parole</span>
                  <input
                    v-model="clientForm.password"
                    type="password"
                    :required="!clientForm.id"
                    :placeholder="clientForm.id ? 'Atstāt tukšu, lai nemainītu' : ''"
                  />
                </label>
                <label><span>Telefons</span><input v-model.trim="clientForm.phone" type="text" /></label>
              </div>
              <div class="toolbar-grid">
                <label><span>Iela</span><input v-model.trim="clientForm.street" type="text" required /></label>
                <label><span>Pilsēta</span><input v-model.trim="clientForm.city" type="text" required /></label>
              </div>
              <div class="toolbar-grid">
                <label><span>Mājas nr.</span><input v-model="clientForm.home_number" type="number" min="1" required /></label>
                <label><span>Dzīvoklis</span><input v-model="clientForm.flat_number" type="number" min="1" /></label>
              </div>
              <label><span>Pasta indekss</span><input v-model.trim="clientForm.zip_code" type="text" required /></label>

              <div class="button-row">
                <button class="primary-button" type="submit">{{ clientForm.id ? 'Saglabāt izmaiņas' : 'Pievienot klientu' }}</button>
                <button class="ghost-button" type="button" @click="resetClientForm">Atcelt</button>
              </div>
            </form>

            <form class="panel inset-panel stack people-card people-form" @submit.prevent="submitEmployee">
              <div>
                <h3>{{ employeeForm.id ? 'Rediģēt darbinieku' : 'Pievienot darbinieku' }}</h3>
                <p class="section-description">
                  {{ employeeForm.id ? 'Mainīt darbinieka profila datus un lomu.' : 'Reģistrēt jaunu darbinieka profilu sistēmā.' }}
                </p>
              </div>

              <div class="toolbar-grid">
                <label><span>Vārds</span><input v-model.trim="employeeForm.name" type="text" required /></label>
                <label><span>Uzvārds</span><input v-model.trim="employeeForm.surname" type="text" required /></label>
              </div>
              <div class="toolbar-grid">
                <label><span>E-pasts</span><input v-model.trim="employeeForm.email" type="email" required /></label>
                <label><span>Telefons</span><input v-model.trim="employeeForm.phone" type="text" /></label>
              </div>
              <div class="toolbar-grid">
                <label>
                  <span>Parole</span>
                  <input
                    v-model="employeeForm.password"
                    type="password"
                    :required="!employeeForm.id"
                    :placeholder="employeeForm.id ? 'Atstāt tukšu, lai nemainītu' : ''"
                  />
                </label>
                <label>
                  <span>Loma</span>
                  <select v-model="employeeForm.role" required>
                    <option value="administrators">Administrators</option>
                    <option value="darbinieks">Darbinieks</option>
                  </select>
                </label>
              </div>

              <div class="button-row">
                <button class="primary-button" type="submit">{{ employeeForm.id ? 'Saglabāt izmaiņas' : 'Pievienot darbinieku' }}</button>
                <button class="ghost-button" type="button" @click="resetEmployeeForm">Atcelt</button>
              </div>
            </form>
          </div>

          <div class="two-column people-grid people-edit-grid">
            <div class="panel inset-panel stack people-card people-form">
              <div>
                <h3>Rediģēt klientu</h3>
                <p class="section-description">Izvēlēties klientu un atvērt viņa profilu rediģēšanas formā.</p>
              </div>

              <label>
                <span>Klients</span>
                <select v-model="selectedClientEditId">
                  <option value="" disabled>Izvēlēties klientu</option>
                  <option v-for="client in clients" :key="client.id" :value="String(client.id)">
                    {{ client.name }} {{ client.surname }}
                  </option>
                </select>
              </label>

              <button class="ghost-button" type="button" :disabled="!selectedClientEditId" @click="startSelectedClientEdit">
                Atvērt rediģēšanu
              </button>
            </div>

            <div class="panel inset-panel stack people-card people-form">
              <div>
                <h3>Rediģēt darbinieku</h3>
                <p class="section-description">Izvēlēties darbinieku un atvērt viņa profilu rediģēšanas formā.</p>
              </div>

              <label>
                <span>Darbinieks</span>
                <select v-model="selectedEmployeeEditId">
                  <option value="" disabled>Izvēlēties darbinieku</option>
                  <option v-for="employee in removableEmployees" :key="employee.id" :value="String(employee.id)">
                    {{ employee.name }} {{ employee.surname }}
                  </option>
                </select>
              </label>

              <button class="ghost-button" type="button" :disabled="!selectedEmployeeEditId" @click="startSelectedEmployeeEdit">
                Atvērt rediģēšanu
              </button>
            </div>
          </div>

          <div class="two-column people-grid people-delete-grid">
            <div class="panel inset-panel stack people-card people-form">
              <div>
                <h3>Dzēst klientu</h3>
                <p class="section-description">Izvēlēties klientu no saraksta un apstiprināt dzēšanu.</p>
              </div>

              <label>
                <span>Klients</span>
                <select v-model="deleteClientId">
                  <option value="" disabled>Izvēlēties klientu</option>
                  <option v-for="client in removableClients" :key="client.id" :value="String(client.id)">
                    {{ client.name }} {{ client.surname }}
                  </option>
                </select>
              </label>

              <button class="danger-button" type="button" :disabled="!deleteClientId" @click="removeSelectedClient">
                Dzēst klientu
              </button>
            </div>

            <div class="panel inset-panel stack people-card people-form">
              <div>
                <h3>Dzēst darbinieku</h3>
                <p class="section-description">Izvēlēties darbinieku no saraksta un apstiprināt dzēšanu.</p>
              </div>

              <label>
                <span>Darbinieks</span>
                <select v-model="deleteEmployeeId">
                  <option value="" disabled>Izvēlēties darbinieku</option>
                  <option v-for="employee in removableEmployees" :key="employee.id" :value="String(employee.id)">
                    {{ employee.name }} {{ employee.surname }}
                  </option>
                </select>
              </label>

              <button class="danger-button" type="button" :disabled="!deleteEmployeeId" @click="removeSelectedEmployee">
                Dzēst darbinieku
              </button>
            </div>
          </div>

          <div class="two-column people-grid people-lists-grid">
            <div class="panel inset-panel table-panel people-card people-table">
              <h3>Klientu saraksts</h3>
              <div class="table-wrapper compact-list">
                <table>
                  <thead>
                    <tr>
                      <th>Klients</th>
                      <th>Lietotājvārds</th>
                      <th>E-pasts</th>
                      <th>Darbības</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="client in paginatedClients" :key="client.id">
                      <td>{{ client.name }} {{ client.surname }}</td>
                      <td>{{ client.username }}</td>
                      <td>{{ client.email }}</td>
                      <td>
                        <div class="button-row compact-row people-actions">
                          <button class="ghost-button tiny-button" type="button" @click="selectClient(client)">Rediģēt</button>
                          <button class="danger-button tiny-button" type="button" @click="removeClient(client)">Dzēst</button>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <div v-if="clients.length > itemsPerPage" class="pagination-row">
                <button class="ghost-button tiny-button pagination-button" type="button" :disabled="pages.clients === 1" @click="changePage('clients', -1, totalClientsPages)">&lt;</button>
                <p class="pagination-status">Lapa {{ pages.clients }} no {{ totalClientsPages }}</p>
                <button class="ghost-button tiny-button pagination-button" type="button" :disabled="pages.clients === totalClientsPages" @click="changePage('clients', 1, totalClientsPages)">&gt;</button>
              </div>
            </div>

            <div class="panel inset-panel table-panel people-card people-table">
              <h3>Darbinieku saraksts</h3>
              <div class="table-wrapper compact-list">
                <table>
                  <thead>
                    <tr>
                      <th>Darbinieks</th>
                      <th>Loma</th>
                      <th>E-pasts</th>
                      <th>Darbības</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="employee in paginatedEmployees" :key="employee.id">
                      <td>{{ employee.name }} {{ employee.surname }}</td>
                      <td>{{ employee.role }}</td>
                      <td>{{ employee.email }}</td>
                      <td>
                        <div class="button-row compact-row people-actions">
                          <button class="ghost-button tiny-button" type="button" @click="selectEmployee(employee)">Rediģēt</button>
                          <button class="danger-button tiny-button" type="button" @click="removeEmployee(employee)">Dzēst</button>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <div v-if="employees.length > itemsPerPage" class="pagination-row">
                <button class="ghost-button tiny-button pagination-button" type="button" :disabled="pages.employees === 1" @click="changePage('employees', -1, totalEmployeesPages)">&lt;</button>
                <p class="pagination-status">Lapa {{ pages.employees }} no {{ totalEmployeesPages }}</p>
                <button class="ghost-button tiny-button pagination-button" type="button" :disabled="pages.employees === totalEmployeesPages" @click="changePage('employees', 1, totalEmployeesPages)">&gt;</button>
              </div>
            </div>
          </div>
        </AppSection>

        <AppSection
          v-if="isEmployee"
          id="reports"
          eyebrow="Atskaites"
          title="Atskaišu ģenerēšana"
          description="Sistēma saglabā, kas un kad izveidojis atskaiti."
        >
          <div class="button-row wrap-row section-action-gap">
            <button class="secondary-button" type="button" @click="generateReport('balonu atskaite')">Balonu atskaite</button>
            <button class="secondary-button" type="button" @click="generateReport('klientu atskaite')">Klientu atskaite</button>
            <button class="secondary-button" type="button" @click="generateReport('darijumu atskaite')">Darījumu atskaite</button>
          </div>

          <div class="two-column wide-right">
            <div class="panel inset-panel">
              <h3>Pēdējās atskaites</h3>
              <ul class="breakdown-list">
                <li v-for="item in paginatedReports" :key="item.id">
                  <span>{{ item.type }}</span>
                  <div class="button-row compact-row people-actions">
                    <strong>{{ item.employee?.name }} {{ item.employee?.surname }}</strong>
                    <button class="ghost-button tiny-button" type="button" @click="viewReport(item.id)">Skatīt</button>
                  </div>
                </li>
              </ul>

              <div v-if="reports.length > reportsPerPage" class="pagination-row">
                <button
                  class="ghost-button tiny-button pagination-button"
                  type="button"
                  :disabled="currentReportsPage === 1"
                  @click="goToPreviousReportsPage"
                >
                  &lt;
                </button>
                <p class="pagination-status">Lapa {{ currentReportsPage }} no {{ totalReportsPages }}</p>
                <button
                  class="ghost-button tiny-button pagination-button"
                  type="button"
                  :disabled="currentReportsPage === totalReportsPages"
                  @click="goToNextReportsPage"
                >
                  &gt;
                </button>
              </div>
            </div>

            <div class="panel inset-panel" v-if="activeReport">
              <h3>{{ activeReport.report.type }}</h3>
              <p class="section-description">
                Izveidoja: {{ activeReportCreatorLogin }}. 
              </p>

              <div class="report-totals">
                <div v-for="(value, key) in activeReport.payload.totals" :key="key" class="report-total-card">
                  <span>{{ formatReportTotalLabel(key) }}</span>
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
                    <tr v-for="item in paginatedActiveReportItems" :key="item.id">
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
                      <th>Darījumu skaits</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="item in paginatedActiveReportItems" :key="item.id">
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
                      <th>Darbība</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="item in paginatedActiveReportItems" :key="item.id">
                      <td>{{ item.cylinder?.serial_number }}</td>
                      <td>{{ item.client?.name }} {{ item.client?.surname }}</td>
                      <td>{{ item.action_type }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <div v-if="activeReportItems.length > itemsPerPage" class="pagination-row">
                <button class="ghost-button tiny-button pagination-button" type="button" :disabled="pages.activeReportItems === 1" @click="changePage('activeReportItems', -1, totalActiveReportItemsPages)">&lt;</button>
                <p class="pagination-status">Lapa {{ pages.activeReportItems }} no {{ totalActiveReportItemsPages }}</p>
                <button class="ghost-button tiny-button pagination-button" type="button" :disabled="pages.activeReportItems === totalActiveReportItemsPages" @click="changePage('activeReportItems', 1, totalActiveReportItemsPages)">&gt;</button>
              </div>
            </div>
          </div>
        </AppSection>

        <AppSection
          v-if="isClient"
          id="profile"
          eyebrow="Klienta zona"
          title="Mana informācija"
          description="Klients var pārskatīt savus kontaktus un izsniegšanas vēsturi."
        >
          <div class="two-column">
            <div class="panel inset-panel stack">
              <div>
                <h3>Profila dati</h3>
              </div>
              <div class="profile-grid">
                <div><span>Vārds</span><strong>{{ session?.user?.name }} {{ session?.user?.surname }}</strong></div>
                <div><span>E-pasts</span><strong>{{ session?.user?.email }}</strong></div>
                <div><span>Telefons</span><strong>{{ session?.user?.phone || '-' }}</strong></div>
                <div><span>Adrese</span><strong>{{ clientAddress }}</strong></div>
              </div>
            </div>

            <div class="panel inset-panel table-panel">
              <h3>Mana darījumu vēsture</h3>
              <div class="table-wrapper compact-list">
                <table>
                  <thead>
                    <tr>
                      <th>Balons</th>
                      <th>Darbība</th>
                      <th>Datums</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="item in paginatedTransactions" :key="item.id">
                      <td>{{ item.cylinder?.serial_number }}</td>
                      <td>{{ item.action_type }}</td>
                      <td>{{ item.action_type === 'izsniegts' ? formatDate(item.issue_date) : formatDate(item.return_date) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <div v-if="transactions.length > itemsPerPage" class="pagination-row">
                <button class="ghost-button tiny-button pagination-button" type="button" :disabled="pages.transactions === 1" @click="changePage('transactions', -1, totalTransactionsPages)">&lt;</button>
                <p class="pagination-status">Lapa {{ pages.transactions }} no {{ totalTransactionsPages }}</p>
                <button class="ghost-button tiny-button pagination-button" type="button" :disabled="pages.transactions === totalTransactionsPages" @click="changePage('transactions', 1, totalTransactionsPages)">&gt;</button>
              </div>
            </div>
          </div>
        </AppSection>
      </template>
    </section>
  </main>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
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
const itemsPerPage = 10;
const reportsPerPage = 10;
const currentReportsPage = ref(1);
// Each data block keeps its own page so pagination changes do not affect other tables.
const pages = reactive({
  recentTransactions: 1,
  cylinders: 1,
  transactions: 1,
  clients: 1,
  employees: 1,
  activeReportItems: 1,
});

const notice = reactive({
  type: 'info',
  text: '',
});
const toastDuration = 3200;
let toastTimeoutId;

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
  id: null,
  name: '',
  surname: '',
  email: '',
  password: '',
  phone: '',
  street: '',
  home_number: '1',
  flat_number: '',
  city: '',
  zip_code: '',
  username: '',
});

const employeeForm = reactive({
  id: null,
  name: '',
  surname: '',
  email: '',
  password: '',
  phone: '',
  role: 'darbinieks',
});

const deleteClientId = ref('');
const deleteEmployeeId = ref('');
const selectedClientEditId = ref('');
const selectedEmployeeEditId = ref('');

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
const totalRecentTransactionsPages = computed(() => totalPagesFor(dashboard.recentTransactions.length, itemsPerPage));
const paginatedRecentTransactions = computed(() => paginateItems(dashboard.recentTransactions, pages.recentTransactions, itemsPerPage));
const totalCylindersPages = computed(() => totalPagesFor(cylinders.value.length, itemsPerPage));
const paginatedCylinders = computed(() => paginateItems(cylinders.value, pages.cylinders, itemsPerPage));
const totalTransactionsPages = computed(() => totalPagesFor(transactions.value.length, itemsPerPage));
const paginatedTransactions = computed(() => paginateItems(transactions.value, pages.transactions, itemsPerPage));
const totalClientsPages = computed(() => totalPagesFor(clients.value.length, itemsPerPage));
const paginatedClients = computed(() => paginateItems(clients.value, pages.clients, itemsPerPage));
const totalEmployeesPages = computed(() => totalPagesFor(employees.value.length, itemsPerPage));
const paginatedEmployees = computed(() => paginateItems(employees.value, pages.employees, itemsPerPage));
const removableClients = computed(() => clients.value);
const removableEmployees = computed(() => employees.value.filter((employee) => employee.id !== session.value?.user?.id));
const activeReportItems = computed(() => activeReport.value?.payload?.items || []);
const totalActiveReportItemsPages = computed(() => totalPagesFor(activeReportItems.value.length, itemsPerPage));
const paginatedActiveReportItems = computed(() => paginateItems(activeReportItems.value, pages.activeReportItems, itemsPerPage));
const totalReportsPages = computed(() => Math.max(1, Math.ceil(reports.value.length / reportsPerPage)));
const paginatedReports = computed(() => {
  const startIndex = (currentReportsPage.value - 1) * reportsPerPage;
  return reports.value.slice(startIndex, startIndex + reportsPerPage);
});

const activeReportCreatorLogin = computed(() => {
  const employee = activeReport.value?.report?.employee;

  if (!employee) {
    return '-';
  }

  return employee.email || `${employee.name} ${employee.surname}`;
});

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

function totalPagesFor(totalItems, perPage) {
  return Math.max(1, Math.ceil(totalItems / perPage));
}

function paginateItems(items, currentPage, perPage) {
  const startIndex = (currentPage - 1) * perPage;
  return items.slice(startIndex, startIndex + perPage);
}

function clampPage(currentPage, totalItems, perPage) {
  return Math.min(currentPage, totalPagesFor(totalItems, perPage));
}

function changePage(key, delta, totalPages) {
  pages[key] = Math.min(totalPages, Math.max(1, pages[key] + delta));
}

function setNotice(type, text) {
  notice.type = type;
  notice.text = text;
}

function clearNotice() {
  notice.text = '';
}

function extractError(error) {
  return error.response?.data?.message || 'Neizdevās izpildīt darbību.';
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

function formatReportTotalLabel(key) {
  const labels = {
    count: 'Kopskaits',
    inspectionDue: 'Inspekcija drīzumā (30 dienu laikā)',
    issued: 'Izsniegti',
    returned: 'Atgriezti',
  };

  return labels[key] || key;
}

function currentClientName(cylinder) {
  if (cylinder.status?.name !== 'pie klienta') {
    return '-';
  }

  // Client details are shown only for cylinders that are currently issued.
  const client = cylinder.latest_transaction?.client;
  return client ? `${client.name} ${client.surname}` : '-';
}

function goToPreviousReportsPage() {
  currentReportsPage.value = Math.max(1, currentReportsPage.value - 1);
}

function goToNextReportsPage() {
  currentReportsPage.value = Math.min(totalReportsPages.value, currentReportsPage.value + 1);
}

function resetCylinderFilters() {
  cylinderFilters.search = '';
  cylinderFilters.statusId = '';
  pages.cylinders = 1;
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
  clientForm.id = null;
  clientForm.name = '';
  clientForm.surname = '';
  clientForm.email = '';
  clientForm.password = '';
  clientForm.phone = '';
  clientForm.street = '';
  clientForm.home_number = '1';
  clientForm.flat_number = '';
  clientForm.city = '';
  clientForm.zip_code = '';
  clientForm.username = '';
  selectedClientEditId.value = '';
}

function resetEmployeeForm() {
  employeeForm.id = null;
  employeeForm.name = '';
  employeeForm.surname = '';
  employeeForm.email = '';
  employeeForm.password = '';
  employeeForm.phone = '';
  employeeForm.role = 'darbinieks';
  selectedEmployeeEditId.value = '';
}

function selectClient(client) {
  clientForm.id = client.id;
  clientForm.name = client.name;
  clientForm.surname = client.surname;
  clientForm.email = client.email;
  clientForm.password = '';
  clientForm.phone = client.phone || '';
  clientForm.street = client.street;
  clientForm.home_number = String(client.home_number);
  clientForm.flat_number = client.flat_number ? String(client.flat_number) : '';
  clientForm.city = client.city;
  clientForm.zip_code = client.zip_code;
  clientForm.username = client.username;
  selectedClientEditId.value = String(client.id);
}

function selectEmployee(employee) {
  employeeForm.id = employee.id;
  employeeForm.name = employee.name;
  employeeForm.surname = employee.surname;
  employeeForm.email = employee.email;
  employeeForm.password = '';
  employeeForm.phone = employee.phone || '';
  employeeForm.role = employee.role;
  selectedEmployeeEditId.value = String(employee.id);
}

function startSelectedClientEdit() {
  const client = clients.value.find((item) => String(item.id) === selectedClientEditId.value);

  if (!client) {
    return;
  }

  selectClient(client);
}

function startSelectedEmployeeEdit() {
  const employee = employees.value.find((item) => String(item.id) === selectedEmployeeEditId.value);

  if (!employee) {
    return;
  }

  selectEmployee(employee);
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
  pages.recentTransactions = clampPage(pages.recentTransactions, dashboard.recentTransactions.length, itemsPerPage);
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
  pages.cylinders = clampPage(pages.cylinders, cylinders.value.length, itemsPerPage);

  cylinders.value.forEach((item) => {
    statusDrafts[item.id] = item.status_id;
  });
}

async function refreshClients() {
  const response = await api.get('/clients');
  clients.value = response.data.data;
  pages.clients = clampPage(pages.clients, clients.value.length, itemsPerPage);

  if (deleteClientId.value && !clients.value.some((client) => String(client.id) === deleteClientId.value)) {
    deleteClientId.value = '';
  }
}

async function refreshEmployees() {
  if (!isAdmin.value) {
    return;
  }

  const response = await api.get('/employees');
  employees.value = response.data.data;
  pages.employees = clampPage(pages.employees, employees.value.length, itemsPerPage);

  if (deleteEmployeeId.value && !employees.value.some((employee) => String(employee.id) === deleteEmployeeId.value)) {
    deleteEmployeeId.value = '';
  }
}

async function refreshTransactions() {
  const response = await api.get('/transactions');
  transactions.value = response.data.data;
  pages.transactions = clampPage(pages.transactions, transactions.value.length, itemsPerPage);
}

async function refreshReports() {
  if (!isEmployee.value) {
    return;
  }

  const response = await api.get('/reports');
  reports.value = response.data.data;
  currentReportsPage.value = Math.min(currentReportsPage.value, Math.max(1, Math.ceil(reports.value.length / reportsPerPage)));
}

async function bootstrap() {
  booting.value = true;

  try {
    await refreshSession();

    if (isClient.value) {
      // Clients only need their own transaction history and profile data.
      await refreshTransactions();
    } else {
      // Employee dashboard data is loaded in parallel to keep the first render responsive.
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
  if (!window.confirm(`Dzēst balonu ${cylinder.serial_number}?`)) {
    return;
  }

  try {
    await api.delete(`/cylinders/${cylinder.id}`);
    await Promise.all([refreshCylinders(), refreshDashboard()]);
    resetCylinderForm();
    setNotice('success', 'Balons dzēsts.');
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
    setNotice('success', 'Balona atgriešana reģistrēta.');
  } catch (error) {
    setNotice('error', extractError(error));
  }
}

async function submitClient() {
  const payload = {
    ...clientForm,
    home_number: Number(clientForm.home_number),
    flat_number: clientForm.flat_number ? Number(clientForm.flat_number) : null,
  };

  if (!payload.password) {
    delete payload.password;
  }

  try {
    if (clientForm.id) {
      await api.put(`/clients/${clientForm.id}`, payload);
      setNotice('success', 'Klienta profils atjaunots.');
    } else {
      await api.post('/clients', payload);
      setNotice('success', 'Klients pievienots.');
    }

    resetClientForm();
    await Promise.all([refreshClients(), refreshDashboard()]);
  } catch (error) {
    setNotice('error', extractError(error));
  }
}

async function removeClient(client) {
  if (!window.confirm(`Dzēst klientu ${client.name} ${client.surname}?`)) {
    return;
  }

  try {
    await api.delete(`/clients/${client.id}`);
    await Promise.all([refreshClients(), refreshDashboard()]);
    deleteClientId.value = '';
    selectedClientEditId.value = '';
    setNotice('success', 'Klients dzēsts.');
  } catch (error) {
    setNotice('error', extractError(error));
  }
}

async function removeSelectedClient() {
  const client = clients.value.find((item) => String(item.id) === deleteClientId.value);

  if (!client) {
    return;
  }

  await removeClient(client);
}

async function submitEmployee() {
  const payload = { ...employeeForm };
  const editedEmployeeId = employeeForm.id;

  if (!payload.password) {
    delete payload.password;
  }

  try {
    if (employeeForm.id) {
      await api.put(`/employees/${employeeForm.id}`, payload);
      setNotice('success', 'Darbinieka profils atjaunots.');
    } else {
      await api.post('/employees', payload);
      setNotice('success', 'Darbinieks pievienots.');
    }

    resetEmployeeForm();
    await Promise.all([
      refreshEmployees(),
      refreshDashboard(),
      editedEmployeeId === session.value?.user?.id ? refreshSession() : Promise.resolve(),
    ]);
  } catch (error) {
    setNotice('error', extractError(error));
  }
}

async function removeEmployee(employee) {
  if (!window.confirm(`Dzēst darbinieku ${employee.name} ${employee.surname}?`)) {
    return;
  }

  try {
    await api.delete(`/employees/${employee.id}`);
    await refreshEmployees();
    deleteEmployeeId.value = '';
    selectedEmployeeEditId.value = '';
    setNotice('success', 'Darbinieks dzēsts.');
  } catch (error) {
    setNotice('error', extractError(error));
  }
}

async function removeSelectedEmployee() {
  const employee = employees.value.find((item) => String(item.id) === deleteEmployeeId.value);

  if (!employee) {
    return;
  }

  await removeEmployee(employee);
}

async function generateReport(type) {
  try {
    const response = await api.get('/reports/generate', {
      params: { type },
    });

    // Store the generated payload locally so the preview can be shown immediately.
    activeReport.value = response.data.data;
    pages.activeReportItems = 1;
    await refreshReports();
    currentReportsPage.value = 1;
    setNotice('success', `${type} izveidota.`);
  } catch (error) {
    setNotice('error', extractError(error));
  }
}

async function viewReport(reportId) {
  try {
    const response = await api.get(`/reports/${reportId}`);
    activeReport.value = response.data.data;
    pages.activeReportItems = 1;
    setNotice('success', 'Atskaite atvērta.');
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

watch(
  () => notice.text,
  (value) => {
    if (toastTimeoutId) {
      clearTimeout(toastTimeoutId);
    }

    if (!value) {
      return;
    }

    // Reset the timer whenever a new toast replaces the previous one.
    toastTimeoutId = window.setTimeout(() => {
      clearNotice();
    }, toastDuration);
  },
);

onBeforeUnmount(() => {
  if (toastTimeoutId) {
    clearTimeout(toastTimeoutId);
  }
});
</script>