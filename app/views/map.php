<?php include 'header.php'; ?>

<main x-data="mapComponent()">

    <div x-show="loading" class="loading-overlay">
        <div class="spinner"></div>
    </div>

    <div x-show="component === 'splash'" id="splash">
        <h1>Share your adventures</h1>
        <p>When you bike or hike, you can send your location, pictures and messages to your friends. Have a look to <a href="/routes">public routes</a>.</p>
        <h1>Geogram Test Route</h1>
        <p>Even without <a href="/login">log in</a>, you can see the <a href="/testroute">Test Route</a> where all <a href="/login">log in</a> users can test Geogram.</p>
        <h1>Create your own routes</h1>
        <p>Once <a href="/login">log in</a>, you can create public or private routes. Then you can invite spectators or adventurers to join the routes.</p>
        <p style="text-align: center;"><br><a href="/help">More informations</a> | <a href="/">Reload</a></p>

    </div>

    <div x-show="component === 'error'" id="splash">
        <h1>Access denied</h1>
        <p>This route is for invited and logged in users only.</p>
    </div>

    <div x-show="component === 'map'" id="mapcontainer">
        <div id="map"></div>
        <div x-html="mapFooter"></div>
    </div>

    <div  x-show="component === 'story'" id="storycontenair" class="longcontenair">
        <div id="story" class="long">

            <template x-if="slogs.length === 0">
                <div class="empty-message">
                    <h1>No story yet</h1>
                    <p>Adventurers need to geolocalise…</p>
                </div>
            </template>

            <template x-if="slogs.length > 0">
                <div>
                    <div class="sort-controls">
                        <button @click="StorySortBy('logtime')" class="sort-btn">
                            Date
                            <i class="fas" :class="{
                                'fa-sort': storySortField !== 'logtime',
                                'fa-sort-up': storySortField === 'logtime' && storySortDirection === 'asc',
                                'fa-sort-down': storySortField === 'logtime' && storySortDirection === 'desc'
                            }"></i>
                        </button>
                        
                        <button @click="StorySortBy('logkm')" class="sort-btn">
                            Distance
                            <i class="fas" :class="{
                                'fa-sort': storySortField !== 'logkm',
                                'fa-sort-up': storySortField === 'logkm' && storySortDirection === 'asc',
                                'fa-sort-down': storySortField === 'logkm' && storySortDirection === 'desc'
                            }"></i>
                        </button>

                        <button @click="storyPhotoOnly = !storyPhotoOnly" class="sort-btn">
                            <i class="fas fa-camera" :class="{ 'active': storyPhotoOnly }"></i>
                        </button>
                    </div>

                    <div class="story-logs">
                        <template x-for="log in slogs.filter(log => {
                            if (!storyPhotoOnly && storyUser === '') return true;    
                            if (storyPhotoOnly && log.logphoto <= 0) return false;                        
                            if (storyUser && log.loguser !== storyUser) return false;
                            return true;})" :key="log.logid">

                            <div class="log-entry" :data-logid="log.logid">
                                <div class="log-header">
                                    <div class="marker markerStory" :style="userIconStyle">
                                        <template x-if="!log.userphoto">
                                            <span x-text="log.userinitials"></span>
                                        </template>
                                    </div>
                                    <span class="log-author"
                                        x-text="log.username_formated"
                                        @click.stop="showUserStory(log)">
                                    </span>
                                    <span class="log-date" x-text="log.date_formated"></span>
                                </div>
                                <template x-if="log.logcontext">
                                    <div class="log-context" x-html="log.logcontext"></div>
                                    <img :src="log.photolog" class="log-photo" alt="Adventure photo">
                                </template>

                                <div class="log-content">
                                    <template x-if="log.photolog">
                                        <img :src="log.photolog" class="log-photo" alt="Adventure photo">
                                    </template>
                                    
                                    <template x-if="log.comment_formated">
                                        <p class="log-comment" x-html="log.comment_formated"></p>
                                    </template>

                                    <div class="log-stats">
                                        <span class="map-link" @click.stop="showUserOnMap(log)">
                                            <i class="fas fa-map-marker-alt"></i>
                                        </span>
                                        <template x-if="log.loguser === userid">
                                            <span class="edit-comment-link" @click.stop="addComment(log.logid)">
                                                <i class="fas fa-edit"></i>
                                            </span>
                                        </template>
                                        <template x-if="log.loguser === userid && log.logphoto > 0">
                                            <span class="rotate-image-link" @click.stop="rotateImage(log.logid)">
                                                <i class="fas fa-redo-alt"></i>
                                            </span>
                                        </template>
                                        <template x-if="log.loguser === userid">
                                            <span class="delete-log-link" @click.stop="deleteLog(log.logid)">
                                                <i class="fas fa-trash"></i>
                                            </span>
                                        </template>
                                        <span x-text="log.logkm_km + 'km'"></span>
                                        <span x-text="log.logdev + 'm+'"></span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>
        <div x-html="mapFooter"></div>
    </div>

    <div x-show="component === 'list'" id="listcontainer" class="longcontenair">
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
        <div x-html="mapFooter"></div>

    </div>

    <div x-show="showCommentModal" class="modal-overlay" style="display: none;">
        <div class="modal-content">
            <textarea x-model="commentText" placeholder="Your comment..." rows="4"></textarea>
            <div class="modal-buttons">
                <button @click="submitComment()">Send</button>
                <button @click="showCommentModal = false">Cansel</button>
            </div>
        </div>
    </div>

    <div x-show="showPopupFlag" id="popup" class="modal-overlay">
        <div class="modal-content">
            <div class="custom-popup">
                <p x-html="popupMessage"></p>
                <div>
                    <template x-if="popupValidate">
                        <button @click="popupOKAction()" class="modal-button">OK</button>
                    </template>
                    <template x-if="popupCancel">
                        <button @click="popupCancelAction()" class="modal-button">Cancel</button>
                    </template>
                </div>
            </div>
        </div>
    </div>

<script>

const convertCoordinate = (coordArray) => {
    log('Converting coordinate array:', coordArray);

    let degrees, minutes, seconds;

    // Gestion des objets Number avec numerator/denominator
    if (coordArray[0].numerator !== undefined) {
        log("Standard EXIF for GPS coordinates");
        degrees = coordArray[0].numerator / coordArray[0].denominator;
        minutes = coordArray[1].numerator / coordArray[1].denominator;
        seconds = coordArray[2].numerator / coordArray[2].denominator;
                
    } else {
        const [deg, min, sec, denominator = 3600] = coordArray;
        degrees = deg;
        minutes = min;
        seconds = sec / denominator;
    }
    
    if (isNaN(degrees) || isNaN(minutes) || isNaN(seconds)) {
        log("ERROR: NaN detected! "+degrees+" "+minutes+" "+seconds);
        return false
    }
    
    const result = degrees + minutes/60 + seconds/3600;
    log(`Conversion result: ${result}`);
    
    if (isNaN(result)) {
        log('ERROR: Final result is NaN!');
        return false;
    }
    
    return Math.round(result * 1000000) / 1000000;;
};

document.addEventListener('alpine:init', () => {

    Alpine.data('mapComponent', () => ({
        //Display
        component: 'splash',
        //Datas
        route: null,
        user: null,
        page: <?= json_encode($page) ?>,
        data: null,
        //Calculated
        isLoggedIn: false,
        isOnRoute: false,
        canPost: false,
        userid: 0,
        userroute: 0,
        routeid: 0,
        logs: [],
        slogs: [],
        // Map
        map: [],
        cursors: Alpine.raw([]),
        geoJSON: null,
        geoJsonLayer: null,
        bestPosition: null,
        // uploading: false,
        baseMaps: null,
        // Actions
        canPost: false,
        mapFooter: '',
        loading: true,
        // Comments
        showCommentModal: false,
        commentText: '',
        currentLogId: null,
        //List
        sortField: 'username',
        sortDirection: 'asc',
        //Story
        storyUser: parseInt(<?= json_encode($UserStoryId) ?>,10),
        storyName: '',
        sortField: 'logtime',
        sortDirection: 'desc',
        storyPhotoOnly: false,
        storySortField: 'logtime',
        storySortDirection: 'desc',
        // Popup
        showPopupFlag: false,
        popupMessage: '',
        popupValidate: false,
        popupCancel: false,
        popupOKCallback: null,
        popupCancelCallback: null,

        async init() {
            await initService.initComponent(this);
            
            if(this.component == 'map' || this.component == 'list' || this.component == 'story'){
                await this.getData();
                await new Promise(resolve => setTimeout(resolve, 100));
                await this.initializeMap();
            }
            this.canPost = this.isPostPossible();
            this.mapFooter = this.getMapFooter();

            window.addEventListener('error', (event) => {
                if (event.message && event.message.includes('_latLngToNewLayerPoint')) {
                    console.error('Leaflet zoom error details:', event);
                    
                    // Au lieu de recharger immédiatement, essayer de récupérer
                    try {
                        // Forcer un redimensionnement de la carte
                        this.map.invalidateSize();
                        
                        // Réinitialiser les couches problématiques
                        if (this.geoJsonLayer) {
                            this.map.removeLayer(this.geoJsonLayer);
                            this.updateGPX();
                        }
                        
                        event.preventDefault();
                        return false;
                    } catch (e) {
                        // Si la récupération échoue, alors recharger
                        alert('Leaflet error detected, reloading...');
                        window.location.reload();
                    }
                }
            });

            this.loading = false;
            log("Init Map ended");
        },

        checkMap(value) {
            log("checkMap");
            if (value === 'map') {
                this.initializeMap();
            }
        },

        async getData() {
            log();
            const data = await apiService.call('getData', {
                routeid: this.route.routeid,
            });
            
            if (data.status == 'success') {
                log("Data loaded successfully");
                this.data = data;
            }
        },

        async initializeMap() {
            log();
            log(this.page);
            this.map = Alpine.raw(L.map('map').setView([0, 0], 13)); // Carte non réactive

            // Définir les fonds de carte
            this.baseMaps = {
                "Standard": L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap',
                    maxZoom: 18,
                }),
                "Terrain": L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap',
                    maxZoom: 18,
                }),
                "Satellite": L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                    attribution: '© Esri',
                    maxZoom: 18,
                }),
                "Vélo": L.tileLayer('https://{s}.tile-cyclosm.openstreetmap.fr/cyclosm/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap',
                    subdomains: 'abc',
                    maxZoom: 18,
                }),
                "Randonnée": L.tileLayer('https://tile.waymarkedtrails.org/hiking/{z}/{x}/{y}.png', {
                    attribution: '© waymarkedtrails.org',
                    maxZoom: 18,
                })
            };

            this.baseMaps["Standard"].addTo(this.map);

            // Ajouter le contrôle de couches
            L.control.layers(this.baseMaps, null, {
                position: 'topright',
                collapsed: true
            }).addTo(this.map);

            if(this.data){
                log("Data ready");
                this.geoJSON = this.data.geojson;
                this.updateGPX();
                this.chooseMarkers(this.storyUser);
            }

            if(this.page && this.page != "map"){
                this.component = this.page;
            }

            // if(this.newPhoto){
            //     this.showPhoto();
            // }
            log("end");
        },

        // Méthode pour obtenir le dernier log de chaque utilisateur
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

        // Méthode pour obtenir tous les logs d'un utilisateur spécifique
        getUserLogs(logs, userId) {
            return logs.filter(log => log.loguser === userId)
                .sort((a, b) => new Date(b.logtime) - new Date(a.logtime));
        },

        chooseMarkers(storyUserID){
            if (storyUserID) {
                this.userMarkers(storyUserID);
            }else{
                this.mapMarkers();
            }
        },
        
        mapMarkers(){
            log();
            this.logs = this.getLatestUserLogs(this.data.logs)
            this.slogs = this.data.logs;
            this.updateMarkers(this.logs);
        },

        userMarkers(userid){
            log(userid);
            this.storyUser = userid;
            this.logs = this.getUserLogs(this.data.logs, this.storyUser);
            this.slogs = this.logs;
            this.storyName = this.logs[0]['username'];
            this.updateMarkers(this.logs);
            this.fit_markers();
            this.story = "map";
        },


        updateMarkers(logs) {
            log();

            // Suppression des marqueurs existants de la carte
            this.cursors.forEach(cursor => this.map.removeLayer(cursor));
            this.cursors = [];

             // Initialisation des limites des marqueurs
            this.markerBounds = new L.LatLngBounds();

            // Ajout de nouveaux marqueurs à la carte
            logs.forEach((entry, index) => {

                if (entry.loglatitude && entry.loglongitude && entry.username_formated){

                    const statusIcon = entry.logphoto ? 
                        '<div class="status-icon-photo"></div>' : 
                        (entry.logcomment ? 
                        '<div class="status-icon-comment"></div>' : 
                        '');

                    const icon = entry.userphoto ? L.divIcon({
                        className: 'custom-div-icon',
                        html: `<div class="marker" style="background-size: cover;background-image: url('${entry.photopath}')">${statusIcon}</div>`,
                        iconSize: [34, 34],
                        iconAnchor: [15, 15]
                    }) : L.divIcon({
                        className: 'custom-div-icon',
                        html: `<div class="marker" style="background-color: ${entry.usercolor};">${entry.userinitials}${statusIcon}</div>`,
                        iconSize: [30, 30],
                        iconAnchor: [15, 15]
                    });

                    // Initialisation du marqueur avec l'icône personnalisée
                    const marker = L.marker([entry.loglatitude, entry.loglongitude], {
                        icon,
                     }).addTo(this.map);

                    marker.on('click', () => {
                        this.showMarkerStory(entry);
                    });

                    // Ajout des coordonnées du marqueur aux limites de la carte
                    this.markerBounds.extend(marker.getLatLng());

                    // Stockage du marqueur dans le tableau des curseurs
                    this.cursors.push(marker);
                }
            });

            if (!this.geoJSON && this.cursors.length == 0) {
                //No cursor
                this.showPopup('No GPX on this route', true);
            }
            log("Fin Markers");
        },
    

        showMarkerStory(entry) {
            if (!this.storyUser) {
                // Mode général (tous les utilisateurs) - ouvrir story générale et scroller vers l'entrée
                this.action_story().then(() => {
                    this.scrollToLogEntry(entry.logid);
                });
            } else {
                // Mode utilisateur spécifique - ouvrir story de l'utilisateur et scroller vers l'entrée
                this.showUserStory(entry);
                this.$nextTick(() => {
                    this.scrollToLogEntry(entry.logid);
                });
            }
        },

        scrollToLogEntry(logId) {
            log();
            this.$nextTick(() => {
                log("Inside");
                const logElement = document.querySelector(`[data-logid="${logId}"]`);
                if (logElement) {
                    logElement.scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'center' 
                    });
                    // Optionnel : ajouter un highlight temporaire
                    logElement.classList.add('highlighted');
                    // setTimeout(() => {
                    //     logElement.classList.remove('highlighted');
                    // }, 2000);
                }
            });
        },

        addComment(logId) {
            this.currentLogId = logId;
            const entry = this.logs.find(log => log.logid === logId);
            if (entry) {
                this.commentText = entry.logcomment || '';
            }
            this.showCommentModal = true;
        },

        async submitComment() {
            const data = await apiService.call('submitComment', {
                logid: this.currentLogId,
                comment: this.commentText,
                routeid: this.route.routeid,
            });
            if (data.status == 'success') {
                this.data = data;
                this.chooseMarkers(this.storyUser);
                this.showCommentModal = false;
            }else{
                log(data);
            }
        },

        async rotateImage(logId) {
            const data = await apiService.call('rotateImage', {
                logid: logId,
                routeid: this.route.routeid,
            });

            if (data.status == 'success') {
                this.data = data;
                this.chooseMarkers(this.storyUser);
            }else{
                log(data);
            }
        },

        async deleteLog(logId) {
            if (!confirm('Do you really want to delete this log?')) {
                return;
            }

            const data = await apiService.call('deleteLog', {
                logid: logId,
                routeid: this.route.routeid,
            });

            if (data.status == 'success') {
                // Supprimer le log des données locales
                this.data = data;
                this.chooseMarkers(this.storyUser);
            }else{
                log(data);
            }
        },

        updateGPX() {
            log("Display Geojson",this.geoJSON);

            if (!this.geoJSON) return;
            if (this.geoJsonLayer && this.map.hasLayer(this.geoJsonLayer)) {
                return;
            }

            const startIcon = L.icon({
                iconUrl: 'images/start.png',
                iconSize: [30, 30],
                iconAnchor: [15, 15],
            });


            let isFirstTrack = true;
            const map = this.map;

            // Charge la trace geoJSON et l'ajoute à la carte
            log("geoJSON:",this.geoJSON);
            fetch(this.geoJSON)
                .then(response => response.json())
                .then(data => {
                    this.geoJsonLayer = L.geoJSON(data, {
                        style: function(feature) {
                            return { color: feature.properties.stroke || '#3388ff' };
                        },
                        onEachFeature: function(feature, layer) {
                            const firstPointLatLng = [feature.geometry.coordinates[0][1], feature.geometry.coordinates[0][0]];
                            if (isFirstTrack) {
                                const startMarker = L.marker(firstPointLatLng, {
                                    icon: startIcon,
                                    zIndexOffset: 1000,
                                    nonBubblingEvents: ['click', 'dblclick', 'mouseover', 'mouseout', 'contextmenu'],
                                }).addTo(map);
                                isFirstTrack = false;
                            }
                        }
                    }).addTo(this.map);

                    this.map.fitBounds(this.geoJsonLayer.getBounds(), { padding: [0, 0], animate: false });
                })
                .catch(error => {
                    console.error('Erreur GeoJSON:', error);
                });
        },

        updateList(data) {
            // Logic to update the list view with data
            const listContainer = document.getElementById('list');
            listContainer.innerHTML = data.map(entry => `<div>${entry.username}</div>`).join('');
        },

        setCursorDiv(cursor, color, initials, img, highlight) {
            let size = highlight ? 50 : 30;
            let border = highlight ? '4px solid red' : '2px solid white';
            let customMarker = L.divIcon({
                className: 'marker',
                html: img
                    ? `<div style="width:${size}px;height:${size}px;border:${border};background-size: cover;background-image: url('${img}')"></div>`
                    : `<div style="width:${size}px;height:${size}px;border:${border};background-color: ${color}">${initials}</div>`,
                iconAnchor: [size / 2, size / 2],
                iconSize: [size, size]
            });
            cursor.setIcon(customMarker);
        },

        async action_localise(){
            log(this.canPost);
            if(this.canPost){
                this.action_map();
                try {
                    const bestPosition = await this.get_localisation();
                    this.removePopup();
                    if(bestPosition){
                        this.bestPosition = bestPosition;
                        this.sendgeolocation();
                    }
                } catch (error) {
                    console.error('Error or cancelled:', error);
                }
            }else{
                this.showAccessMessage();
            }
        },

        async get_localisation() {
            if (!navigator.geolocation) {
                this.showPopup('Geolocation not supported', true);
                throw new Error('Geolocation not supported');
            }

            return new Promise((resolve, reject) => {
                this.showPopup("Looking for position...");

                const options = {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                };

                let bestPosition = null;
                let bestAccuracy = Infinity;

                const watchId = navigator.geolocation.watchPosition(
                    position => {
                        const { latitude, longitude, accuracy } = position.coords;

                        if (accuracy < bestAccuracy) {
                            bestPosition = {
                                latitude: latitude,
                                longitude: longitude,
                                timestamp: Math.floor(Date.now() / 1000)
                            };
                            bestAccuracy = Math.floor(accuracy);
                        }
                        log(`Latitude: ${latitude}, Longitude: ${longitude}, Précision: ${bestAccuracy}m`);

                        if (bestAccuracy < 50) {
                            navigator.geolocation.clearWatch(watchId);
                            resolve(bestPosition);
                        } else if (bestAccuracy < 10000) {
                            // Afficher popup avec boutons OK/Cancel
                            this.showPopup(
                                `Accuracy: ${bestAccuracy}m`,
                                true,  // OK button
                                true,  // Cancel button
                                () => { // OK callback
                                    navigator.geolocation.clearWatch(watchId);
                                    resolve(bestPosition);
                                },
                                () => { // Cancel callback
                                    navigator.geolocation.clearWatch(watchId);
                                    reject('Cancelled by user');
                                }
                            );
                        }
                    },
                    error => {
                        navigator.geolocation.clearWatch(watchId);
                        this.showPopup(
                            'Geolocalisation Error: ' + error.message,
                            true,  // OK button
                            true,  // Cancel button
                            () => { // OK callback - utiliser la meilleure position disponible
                                if (bestPosition) {
                                    resolve(bestPosition);
                                } else {
                                    reject(error);
                                }
                            },
                            () => { // Cancel callback
                                reject('Cancelled by user');
                            }
                        );
                    },
                    options
                );
            });
        },

        async sendgeolocation() {
            log();
            const data = await apiService.call('sendgeolocation', {
                userid: this.userid,
                routeid: this.routeid,
                latitude: this.bestPosition["latitude"],
                longitude: this.bestPosition["longitude"]

            });
            if (data.status == 'success') {
                this.data = data;
                this.chooseMarkers(this.userid);
                this.zoom_lastmarker(this.userid);
            }
        },

        showPopup(message, validate = false, cancel = false, okCallback = null, cancelCallback = null) {
            log(message)
            this.popupMessage = message;
            this.popupValidate = validate;
            this.popupCancel = cancel;
            this.popupOKCallback = okCallback;
            this.popupCancelCallback = cancelCallback;
            this.showPopupFlag = true;
        },

        async showError(message) {
            return new Promise((resolve) => {
                this.showPopup(
                    message,
                    true,  // OK button
                    false, // No cancel
                    () => {
                        this.removePopup();
                        resolve();
                    }
                );
            });
        },

        popupOKAction() {
            if (this.popupOKCallback) {
                this.popupOKCallback();
            }else{
                this.removePopup();
            }
        },

        popupCancelAction() {
            if (this.popupCancelCallback) {
                this.popupCancelCallback();
            }else{
                this.removePopup();
            }
        },

        removePopup(){
            log();
            this.showPopupFlag = false;
            this.popupMessage = '';
            this.popupValidate = false;
            this.popupOKCallback = null;
            this.popupCancelCallback = null;
            this.popupCancel = false;
        },

        // Fit GPX and all markers
        action_fitall() {
            log();
            let bounds = null;

            // Inclure les limites des marqueurs
            if (this.cursors.length > 0) {
                const markerBounds = new L.LatLngBounds(this.cursors.map(cursor => cursor.getLatLng()));
                bounds = markerBounds;
            }

            // Inclure les limites du GeoJSON si disponible
            if (this.geoJsonLayer && this.map.hasLayer(this.geoJsonLayer)) {
                const geoJsonBounds = this.geoJsonLayer.getBounds();
                if (bounds) {
                    bounds.extend(geoJsonBounds);
                } else {
                    bounds = geoJsonBounds;
                }
            }

            // Ajuster la carte aux nouvelles limites combinées
            if (bounds && bounds.isValid()) {
                this.map.fitBounds(bounds, { 
                    padding: [50, 50], 
                    maxZoom: 18,
                    animate: false
                });
            }
            this.action_map();
        },

        // Fit GPX only
        action_fitgpx() {
            log();
            if (this.geoJsonLayer && this.map.hasLayer(this.geoJsonLayer)) {
                this.map.fitBounds(this.geoJsonLayer.getBounds(), { padding: [0, 0], animate: false });
            }
            this.action_map();
        },

        // Fit all markers
        fit_markers() {
            log();
            if (this.cursors.length > 0) {
                const markerBounds = new L.LatLngBounds(this.cursors.map(cursor => cursor.getLatLng()));
                this.map.fitBounds(markerBounds, { 
                    padding: [50, 50], 
                    maxZoom: 18,
                    animate: false
                });
            }
            // this.action_map();
        },

        async action_map() {  
            log();
            log(this.storyUser);

            Alpine.store('headerActions').initTitle(this.buildStoryObj("map"));
            this.component = "map";

            this.mapFooter = this.getMapFooter();

            this.$nextTick(() => {
  
                setTimeout(() => {
                    this.map.invalidateSize({ animate: false, pan: false });
                }, 50);
            });            
        },

        async action_story(){
            log();
            Alpine.store('headerActions').initTitle(this.buildStoryObj("story"));
            this.component = "story";
            this.mapFooter = this.getMapFooter();
        },


        action_list() {
            log();
            Alpine.store('headerActions').initTitle(this.buildStoryObj("list"));
            this.component = "list";
            this.mapFooter = this.getMapFooter();
        },

        action_reload(){
            window.location.reload();
        },

        action_testlocalise() {
            const clickHandler = (e) => {
                log();
                // Vérifier si on n'a pas cliqué sur un marker
                const clickedMarker = this.cursors.some(marker => 
                    marker.getLatLng().equals(e.latlng)
                );
                
                if (!clickedMarker) {
                    this.bestPosition = {
                        latitude: e.latlng.lat,
                        longitude: e.latlng.lng,
                        timestamp: Math.floor(Date.now() / 1000)
                    };      
                    log(this.bestPosition);
                    this.sendgeolocation();
                }
                
                // Réactiver l'écoute pour le prochain clic
                this.map.once('click', clickHandler);
            }            
            this.map.once('click', clickHandler);
        },

        async action_photo() {
            if(this.canPost){
                const input = document.createElement('input');
                input.type = 'file';
                input.accept = 'image/*';
                input.capture = 'camera';
                await this.handleImageSelection(input);
            }else{
                this.showAccessMessage();
            }
        },

        action_gallery() {
            log("Gallery");
            if(this.canPost){
                const input = document.createElement('input');
                input.type = 'file';
                input.accept = 'image/*';
                input.multiple = true;
                this.handleImageSelection(input);
            }else{
                this.showAccessMessage();
            }
        },

        async handleImageSelection(input) {
            input.onchange = async (e) => {
                const files = e.target.files;
                if (!files || files.length === 0) return;

                this.showPopup("Uploading…");
                
                try {
                    for (const file of Array.from(files)) {
                        if (!file.type.startsWith('image/')) {
                            await this.showError('Please select only images');
                            continue;
                        }

                        let gpsData = null;
                        let trace = '';
                        
                        try {
                            gpsData = await this.getExifData(file);
                            log('GPS data from EXIF');
                        } catch (error) {
                            await this.showError("Error in Exif "+error,true);
                            continue;
                        }
                        log('GPS data from EXIF:', gpsData);

                        if(gpsData["latitude"] === 0 && gpsData["longitude"] === 0){
                            log('No valid GPS data in EXIF, using geolocation');
                            // Si pas d'EXIF, utiliser la géolocalisation
                            timestamp_source = gpsData["timestamp"];
                            try {
                                gpsData = await this.get_localisation();
                                log("Position from geolocation OK");
                            } catch (geoError) {
                                await this.showError('Location required for photo upload.');
                                log('Geolocation failed:', geoError);
                                continue;
                            }
                            gpsData["timestamp"] = timestamp_source; // Conserver le timestamp de l'EXIF
                            trace = "GPS data";
                        }else{
                            trace = "Exif data";
                        }

                        if (gpsData["latitude"] != 0 && gpsData["longitude"] != 0) {
                            this.showPopup("Uploading continue… "+trace,false, false);
                            const uploadAnswer = await this.uploadImage(file, gpsData);
                            if (uploadAnswer['status'] == 'success') {
                                log(`Uploaded successfully`);
                            } else {
                                log(`Failed to upload image!!!`);
                                log(uploadAnswer);
                                await this.showError('Error: ' + uploadAnswer['message']);
                            }
                        } else {
                            log('Invalid GPS data:', gpsData);
                            await this.showError('Invalid GPS data: ' + trace);
                        }
                    }
                } catch (error) {
                    this.showPopup("Error in handleImageSelection",true);
                    log('Error in handleImageSelection:', error);
                } finally {
                    this.removePopup();
                }
            };
            
            input.click();
        },

        async getExifData(file) {
            return new Promise((resolve, reject) => {
                const routeTimeDiff = this.data?.route?.routetimediff || 0;
                const reader = new FileReader();
                reader.onload = function(e) {
                    EXIF.getData(file, function() {
                        const tags = EXIF.getAllTags(this);
                        log('All EXIF tags:', tags);
                        
                        if (tags.GPSLatitude && tags.GPSLongitude) {
                            try {
                                let latitude = convertCoordinate(tags.GPSLatitude);
                                let longitude = convertCoordinate(tags.GPSLongitude);

                                // Gestion des directions
                                if(latitude) {
                                    if(tags.GPSLatitudeRef === 'S') {
                                        latitude = -latitude;
                                    }
                                }else{
                                    latitude = 0;
                                }
                                if (longitude){
                                    if(tags.GPSLongitudeRef === 'W') {
                                        longitude = -longitude;
                                    }
                                }else{
                                    longitude = 0;
                                }
                                log('After convertCoordinate1:', { latitude, longitude });

                                // Gestion du timestamp
                                let timestamp = Date.now();
                                if (tags.DateTime) {
                                    // Format EXIF standard : "2024:01:31 15:30:45"
                                    // Format Android parfois: "20240131_153045"
                                    let dateString = tags.DateTime;

                                    // Correction pour le format Android
                                    if (/^\d{8}_\d{6}$/.test(dateString)) {
                                        dateString = dateString.replace(
                                            /(\d{4})(\d{2})(\d{2})_(\d{2})(\d{2})(\d{2})/,
                                            "$1:$2:$3 $4:$5:$6"
                                        );
                                    }
                                    log('Parsed DateTime:', dateString);

                                    const [date, time] = dateString.split(' ');
                                    const [year, month, day] = date.split(':');
                                    const [hours, minutes, seconds] = time.split(':');
                                    timestamp = Date.UTC(year, month - 1, day, hours, minutes, seconds);

                                    if (routeTimeDiff) {
                                        log('Applying route time difference:', routeTimeDiff);
                                        timestamp -= parseInt(routeTimeDiff)*60*1000;
                                    }
 
                                }
                                timestamp = Math.floor(timestamp / 1000);
                                log('After convertCoordinate2:', { latitude, longitude, timestamp });
                                resolve({
                                    latitude: latitude,
                                    longitude: longitude,
                                    timestamp: timestamp
                                });
            
                            } catch (error) {
                                log('Error validating EXIF data:', error);
                                reject('Invalid GPS data format');
                                return false;
                            } 
                            
                        } else {
                            log('No GPS data found in EXIF');
                            reject('No GPS data found in Exif');
                        }
                    });
                };
                
                reader.onerror = (error) => {
                    log('FileReader error:', error);
                    reject('Error reading file for EXIF data');
                };
                
                reader.readAsDataURL(file);
            });
        },

        async uploadImage(file, gpsData) {
            log();
            try {

                const base64Image = await new Promise((resolve, reject) => {
                    const reader = new FileReader();
                    reader.onload = (event) => resolve(event.target.result);
                    reader.onerror = (error) => reject(error);
                    reader.readAsDataURL(file);
                });
                log("base64 OK");
                
                if (!gpsData) {
                    log('Error:', "No GPS Data");
                    return { status: 'error', message: 'No GPS Data'};
                }

                const data = await apiService.call('logphoto', {
                    userid: this.user.userid,
                    routeid: this.route.routeid,
                    photofile: base64Image,
                    latitude: gpsData['latitude'],
                    longitude: gpsData['longitude'],
                    timestamp: gpsData['timestamp']
                });

                log("Image upload response:", data);

                if (data.status == 'success') {
                    log("Image uploaded successfully");
                    this.data = data;
                    this.chooseMarkers(this.userid);
                    this.action_map();
                    this.zoom_lastmarker(this.userid);
                }else{
                    log("Image upload failed:", data);
                }
                
                return data;
            } catch (error) {
                log('Error uploading image:', error);
                return { status: 'error', message: 'Upload exception: ' + error.message };
            }
        },


        showAccessMessage() {
            log();
            log("Active:", this.isRouteActive());
            if (!this.isLoggedIn) {
                this.showPopup('You need to <a href="/login">log in</a> to use this feature', true);
            } else if (!this.isRouteActive()) {
                this.showPopup('This road is closed', true);
            } else {
                this.showPopup('You need to be invited to post on this route', true);
            }
        },

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

        async showUserOnMap(entry) {
            this.component = 'map';
            this.userMarkers(entry.userid);
            this.zoom_lastmarker(entry.userid, entry.logid);
        },

        async showUserStory(entry){
            log(entry);
            this.chooseMarkers(entry.loguser);
            this.action_story();
        },

        getMapFooter() {
            log();
            // Définir quels boutons de mode afficher
            const modeButtons = {
                'map': `
                    <button @click="action_list()" class="small-bt">
                        <i class="fas fa-list"></i>
                    </button>
                    <button @click="action_story()" class="small-bt">
                        <i class="fas fa-book"></i>
                    </button>
                `,
                'list': `
                    <button @click="action_map()" class="small-bt">
                        <i class="fas fa-map"></i>
                    </button>
                    <button @click="action_story()" class="small-bt">
                        <i class="fas fa-book"></i>
                    </button>
                `,
                'story': `
                    <button @click="action_map()" class="small-bt">
                        <i class="fas fa-map"></i>
                    </button>
                    <button @click="action_list()" class="small-bt">
                        <i class="fas fa-list"></i>
                    </button>
                `
            }[this.component];

            return `
                <div id="mapfooter">
                    <div class="small-line">
                        ${modeButtons}
                        <button @click="action_fitall()" class="small-bt">
                            <i class="fas fa-expand-arrows-alt"></i>
                        </button>
                        <button @click="fit_markers()" class="small-bt">
                            <i class="fas fa-compress"></i>
                        </button>
                        <button @click="action_gallery()" class="small-bt ${this.canPost ? '' : 'disabled-bt'}">
                            <i class="fas fa-images"></i>
                        </button>
                    </div>
                    <div class="big-line">
                        <button @click="action_localise()" class="big-bt ${this.canPost ? '' : 'disabled-bt'}">
                            <i class="fas fa-map-marker-alt"></i>
                        </button>
                        <button @click="action_photo()" class="big-bt ${this.canPost ? '' : 'disabled-bt'}">
                            <i class="fas fa-camera"></i>
                        </button>
                    </div>
                </div>
            `;
            //<button @click="action_reload()" class="small-bt"><i class="fas fa-rotate"></i></button>
        },

        buildStoryObj(story){
            return {story: story, storyUserName: this.storyName, storyUser: this.storyUser}
        },

        zoom_lastmarker(userid = null, logid = null) {
            let targetMarker = null;

                // Si un logid est spécifié, chercher ce marker en priorité
            if (logid !== null) {
                for (let i = 0; i < this.cursors.length; i++) {
                    const cursor = this.cursors[i];
                    const log = this.slogs.find(log => 
                        log.loglatitude === cursor.getLatLng().lat && 
                        log.loglongitude === cursor.getLatLng().lng &&
                        log.logid === logid
                    );

                    if (log) {
                        targetMarker = cursor;
                        break;
                    }
                }
            }
            // Sinon, si un userid est spécifié, chercher le dernier marker de cet utilisateur
            else if (userid !== null) {
                // Find the last marker for the specified user
                for (let i = this.cursors.length - 1; i >= 0; i--) {
                    const cursor = this.cursors[i];
                    const log = this.slogs.find(log => log.loglatitude === cursor.getLatLng().lat && log.loglongitude === cursor.getLatLng().lng);

                    if (log && log.userid === userid) {
                        targetMarker = cursor;
                        break;
                    }
                }
            }

            // If no specific user marker found, or no userid provided, use the last marker in general
            if (!targetMarker && this.cursors.length > 0) {
                targetMarker = this.cursors[this.cursors.length - 1];
            }

            // Zoom to the target marker
            if (targetMarker) {
                this.highlightMarker(targetMarker);
                this.map.setView(targetMarker.getLatLng(), 15);
            }
        },

        highlightMarker(marker) {
            // Sauvegarder l'icône originale
            const originalIcon = marker.getIcon();
            
            // Créer une version agrandie et avec bordure rouge
            const highlightIcon = L.divIcon({
                className: 'custom-div-icon highlighted',
                html: originalIcon.options.html.replace('class="marker"', 'class="marker highlighted"'),
                iconSize: [50, 50], // Plus grand
                iconAnchor: [25, 25]
            });
            
            // Appliquer l'icône mise en évidence
            marker.setIcon(highlightIcon);
        },


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

        StorySortBy(field) {
            if (this.storySortField === field) {
                this.storySortDirection = this.storySortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                this.storySortField = field;
                this.storySortDirection = 'desc';
            }
            this.slogs = this.sortData(this.slogs, this.storySortField, this.storySortDirection);
        },

        isRouteActive() {
            log();
            const now = new Date();
            if(!this.route.routestop) return true;
            if(this.route.routestop == null || this.route.routestop == '0000-00-00 00:00:00') return true;
                
            const stopDate = new Date(this.route.routestop);
            if (!isNaN(stopDate.getTime())) {
                return stopDate > now;
            }
            return false;
        },

        isPostPossible() {
            log();
            if (!this.isLoggedIn) return false;

            if (!this.isRouteActive()){
                log("Route inactive");
                return false;
            }
            log("Route active");

            if (this.user.userroute != this.route.routeid){
                //Not connected to the route
                log("isPostPossible: not on route");
                return false;
            }
            
            // Route publique, tout utilisateur connecté peut poster
            if(this.route.routestatus === 0) return true;

            // Route visible ou privée, seuls les invités peuvent publier
            if(this.route.routestatus > 0 && this.user.constatus > 0) return true;

            return false;
        },

        getAdventurerLabel() {
            return this.storyUser ? 'pings' : 'adventurers';
        },

        get userIconStyle() {
            $style = this.log.userphoto ? `background-image: url('/userdata/users/${this.log.userid}/photo.jpeg');`
                : `background-color: ${this.log.usercolor};`;
            return $style;
        },

    }));

});
</script>