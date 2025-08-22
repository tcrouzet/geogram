<?php include 'header.php'; ?>

<main x-data="routeUsersComponent()">

  <div x-show="loading" class="loading-overlay">
    <div class="spinner"></div>
  </div>

  <div x-show="!loading" id="listcontainer" class="longcontenair">
    <div id="list" class="long">

      <template x-if="logs.length === 0">
        <div class="empty-message">
          <h1>No list yet</h1>
          <p>Adventurers need to geolocalise…</p>
        </div>
      </template>

      <template x-if="logs.length > 0">
        <div>
          <div class="list-header">
            <div class="sortable-col" @click="sortBy('logtime')">
              <span x-text="`${logs.length} ${getAdventurerLabel()}`"></span>
              <i class="fas" :class="{
                'fa-sort': sortField !== 'logtime',
                'fa-sort-up': sortField === 'logtime' && sortDirection === 'asc',
                'fa-sort-down': sortField === 'logtime' && sortDirection === 'desc'
              }"></i>
            </div>
            <div class="stats">
              <div class="sortable-col" @click="sortBy('logkm')">
                <span>km</span>
                <i class="fas" :class="{
                  'fa-sort': sortField !== 'logkm',
                  'fa-sort-up': sortField === 'logkm' && sortDirection === 'asc',
                  'fa-sort-down': sortField === 'logkm' && sortDirection === 'desc'
                }"></i>
              </div>
              <div class="sortable-col" @click="sortBy('logdev')">
                <span>m+</span>
                <i class="fas" :class="{
                  'fa-sort': sortField !== 'logdev',
                  'fa-sort-up': sortField === 'logdev' && sortDirection === 'asc',
                  'fa-sort-down': sortField === 'logdev' && sortDirection === 'desc'
                }"></i>
              </div>
            </div>
          </div>

          <div class="list-content">
            <template x-for="entry in logs" :key="entry.logid">
              <div class="list-row" @click="showUserStory(entry)">
                <div class="user-col">
                  <i class="fas fa-map-marker-alt" @click.stop="showUserOnMap(entry)"></i>
                  <span x-text="entry.date_formated" class="list-date"></span>
                  <span x-text="entry.username"></span>
                </div>
                <div class="stats">
                  <span x-text="entry.logkm_km"></span>
                  <span x-text="entry.logdev"></span>
                </div>
              </div>
            </template>
          </div>
        </div>
      </template>

    </div>
  </div>

</main>

<script>
document.addEventListener('alpine:init', () => {
  Alpine.data('routeUsersComponent', () => ({

    // Données “contexte” (comme sur map)
    route: null,
    user: null,
    data: null,

    // UI
    loading: true,

    // Liste affichée
    allLogs: [],   // tous les logs de la route
    logs: [],      // soit 1 log par user (vue initiale), soit tous les logs d’un user
    storyUser: 0,  // 0 => vue “tous les users”, sinon id user filtré

    // Tri (identique map.php)
    sortField: 'logtime',
    sortDirection: 'desc',

    async init() {
      await initService.initComponent(this);

      // NE PAS MODIFIER loadAllData
      await this.loadAllData();

      // Source
      this.allLogs = this.data?.logs || this.data?.data?.logs || [];

      // Vue initiale: 1 ligne par user (dernier log)
      this.logs = this.getLatestUserLogs(this.allLogs);

      // Tri initial
      this.applySort();

      this.loading = false;
    },

    // ========= NE PAS MODIFIER =========
    async loadAllData() {
      log();
      const data = await apiService.call('getAllData', {
            routeid: this.route.routeid,
      });
      
      if (data.status == 'success') {
          log("Data all loaded successfully");
          this.data = data;
      }
    },
    // ===================================

    // Comme sur map: dernier log par user
    getLatestUserLogs(logs) {
      const userLatestLogs = new Map();
      logs.forEach(log => {
        const userId = log.loguser;
        const logTime = new Date(log.logtime).getTime();
        if (!userLatestLogs.has(userId) || 
            logTime > new Date(userLatestLogs.get(userId).logtime).getTime()) {
          userLatestLogs.set(userId, log);
        }
      });
      return Array.from(userLatestLogs.values())
        .sort((a, b) => new Date(b.logtime) - new Date(a.logtime));
    },

    // Tous les logs d’un user
    getUserLogs(logs, userId) {
      return logs
        .filter(log => log.loguser === userId)
        .sort((a, b) => new Date(b.logtime) - new Date(a.logtime));
    },

    // Clic sur une ligne: si on est en mode “tous”, on filtre sur ce user;
    // si on est déjà filtré sur ce user, on revient à “tous”.
    showUserStory(entry){
      if (this.storyUser && this.storyUser === entry.loguser) {
        // revenir à “tous”
        this.storyUser = 0;
        this.logs = this.getLatestUserLogs(this.allLogs);
        this.applySort();
        return;
      }
      // filtrer sur cet utilisateur
      this.storyUser = entry.loguser;
      this.logs = this.getUserLogs(this.allLogs, this.storyUser);
      this.applySort();
    },

    // Pas de carte ici, stub pour conserver le même markup
    showUserOnMap(entry){ /* noop */ },

    // Tri (copié du mode list de map.php)
    sortBy(field) {
      if (this.sortField === field) {
        this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
      } else {
        this.sortField = field;
        this.sortDirection = 'asc';
      }

      this.logs = [...this.logs].sort((a, b) => {
        let aVal = field === 'logkm' ? parseFloat(a.logkm_km) : 
                   field === 'logdev' ? parseFloat(a.logdev) : 
                   a[field];
        let bVal = field === 'logkm' ? parseFloat(b.logkm_km) : 
                   field === 'logdev' ? parseFloat(b.logdev) : 
                   b[field];
        
        if (this.sortDirection === 'asc') {
          return aVal > bVal ? 1 : -1;
        } else {
          return aVal < bVal ? 1 : -1;
        }
      });
    },

    // Tri initial (même logique que sortBy sur champ courant)
    applySort() {
      const field = this.sortField;
      const direction = this.sortDirection;

      this.logs = [...this.logs].sort((a, b) => {
        let aVal = field === 'logkm' ? parseFloat(a.logkm_km) : 
                   field === 'logdev' ? parseFloat(a.logdev) : 
                   a[field];
        let bVal = field === 'logkm' ? parseFloat(b.logkm_km) : 
                   field === 'logdev' ? parseFloat(b.logdev) : 
                   b[field];

        if (direction === 'asc') {
          return aVal > bVal ? 1 : -1;
        } else {
          return aVal < bVal ? 1 : -1;
        }
      });
    },

    getAdventurerLabel() {
      return this.storyUser ? 'pings' : 'adventurers';
    },

  }));
});
</script>