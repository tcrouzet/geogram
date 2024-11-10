<?php include 'header.php'; ?>

<main x-data="storyComponent()" >
    <div id="splash">
        <h1 x-text="storyUserName + '\'s story'"></h1>

        <div class="sort-controls">
                <button @click="sortBy('logtime')" class="sort-btn">
                    Date
                    <i class="fas" :class="{
                        'fa-sort': sortField !== 'logtime',
                        'fa-sort-up': sortField === 'logtime' && sortDirection === 'asc',
                        'fa-sort-down': sortField === 'logtime' && sortDirection === 'desc'
                    }"></i>
                </button>
                
                <button @click="sortBy('logkm')" class="sort-btn">
                    Distance
                    <i class="fas" :class="{
                        'fa-sort': sortField !== 'logkm',
                        'fa-sort-up': sortField === 'logkm' && sortDirection === 'asc',
                        'fa-sort-down': sortField === 'logkm' && sortDirection === 'desc'
                    }"></i>
                </button>
            </div>

        <template x-if="!isOpen">
            <p>This story is protected</p>
        </template>

        <template x-if="logs.length > 0">

            <div class="story-logs">
                <template x-for="log in logs" :key="log.logid">
                    <div class="log-entry">
                        <div class="log-header">
                            <span class="log-date" x-text="log.logtime"></span>
                        </div>

                        <div class="log-content">
                            <template x-if="log.photolog">
                                <img :src="log.photolog" class="log-photo" alt="Adventure photo">
                            </template>
                            
                            <template x-if="log.comment_formated">
                                <p class="log-comment" x-html="log.comment_formated"></p>
                            </template>

                            <div class="log-stats">
                                <span x-text="'Distance: ' + log.logkm_km"></span>
                                <span x-text="'Elevation: ' + log.logdev + 'm'"></span>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </template>

    </div>
</main>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('storyComponent', () => ({

        isLoggedIn: false,
        loading: false,
        user: null,
        username: '',
        userstory: null,
        logs: [],
        route: [],
        isOpen: true,
        storyUser: null,
        storyUserName: '',
        sortField: 'logtime',
        sortDirection: 'desc',
    
        async init(){
            await initService.initComponent(this);
            this.loadStory();
            console.log("StoryInit ended");
        },

        async loadStory(){
            const data = await apiService.call('userStory', {
                userid: this.userid,
                userroute: this.userroute,
                routeid: this.route.routeid,
                userstory: this.userstory,
            });
            if (data.status == 'success') {
                this.logs = this.sortData(data.logs, this.sortField, this.sortDirection);
                this.storyUser = data.user;
                this.storyUserName = data.user.fusername;
            }
        },

        // Nouvelle fonction helper pour le tri
        sortData(data, field, direction) {
            return [...data].sort((a, b) => {
                let aVal, bVal;
                
                if (field === 'logkm') {
                    aVal = parseFloat(a.logkm);
                    bVal = parseFloat(b.logkm);
                } else if (field === 'logtime') {
                    aVal = new Date(a.logtime).getTime();
                    bVal = new Date(b.logtime).getTime();
                } else {
                    aVal = a[field];
                    bVal = b[field];
                }
                
                return direction === 'asc' 
                    ? aVal - bVal 
                    : bVal - aVal;
            });
        },

        sortBy(field) {
            if (this.sortField === field) {
                this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortField = field;
                this.sortDirection = 'desc';
            }
            this.logs = this.sortData(this.logs, this.sortField, this.sortDirection);
        },

    }));
});
</script>

