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
                                'fa-sort': sortField !== 'logtime',
                                'fa-sort-up': sortField === 'logtime' && sortDirection === 'asc',
                                'fa-sort-down': sortField === 'logtime' && sortDirection === 'desc'
                            }"></i>
                        </button>
                        
                        <button @click="StorySortBy('logkm')" class="sort-btn">
                            Distance
                            <i class="fas" :class="{
                                'fa-sort': sortField !== 'logkm',
                                'fa-sort-up': sortField === 'logkm' && sortDirection === 'asc',
                                'fa-sort-down': sortField === 'logkm' && sortDirection === 'desc'
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
                                    <template x-if="!storyUser">
                                        <span class="log-author" x-text="log.username_formated" @click.stop="showUserStory(log)"></span>
                                    </template>
                                    <span class="log-date" x-text="log.date_formated"></span>
                                </div>

                                <div class="log-content">
                                    <template x-if="log.photolog">
                                        <img :src="log.photolog" class="log-photo" alt="Adventure photo">
                                    </template>

                                    <!-- Photos additionnelles du même endroit -->
                                    <!-- <template x-if="log.morephotologs.length > 0">
                                        <template x-for="additionalPhoto in log.morephotologs">
                                            <img :src="additionalPhoto" class="log-photo" alt="Additional photo">
                                        </template>
                                    </template> -->
                                    
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
                        <button @click="showPopupFlag = false" class="modal-button">OK</button>
                    </template>
                    <template x-if="popupCancel">
                        <button @click="showPopupFlag = false" class="modal-button">Cancel</button>
                    </template>
                </div>
            </div>
        </div>
    </div>

</main>

<script>

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
        mapMode: true,
        uploading: false,
        newPhoto: false,
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
        storyUser: <?= json_encode($userid) ?>,
        storyName: '',
        sortField: 'logtime',
        sortDirection: 'desc',
        storyPhotoOnly: false,
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

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '<a href="https://www.openstreetmap.org/">OSM</a>',
                // attribution: '',
                maxZoom: 18,
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

            if(this.newPhoto){
                this.showPhoto();
            }
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
            this.mapMode = false;
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

                        if (bestAccuracy < 20) {
                            navigator.geolocation.clearWatch(watchId);
                            this.removePopup();
                            resolve(bestPosition);
                        } else if (bestAccuracy < 10000) {
                            this.updatePopup(`Accuracy: ${bestAccuracy}m`, watchId, bestPosition, resolve, reject);
                        }
                    },
                    error => {
                        this.updatePopup('Geolocalisation Error:' + error, watchId, bestPosition, resolve, reject);
                        reject(error);
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
                this.logs = data.logs;
                this.updateMarkers(this.logs);
                log('New location');
                this.zoom_lastmarker(this.userid);
            }
        },

        updatePopup(message, watchId, bestPosition, resolve, reject) {
            this.showPopup(
                message, 
                true, // OK button
                true, // Cancel button
                () => { // OK callback
                    navigator.geolocation.clearWatch(watchId);
                    resolve(bestPosition);
                },
                () => { // Cancel callback
                    navigator.geolocation.clearWatch(watchId);
                    reject('Cancelled by user');
                }
            );
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

        popupOKAction() {
            if (this.popupOKCallback) {
                this.popupOKCallback();
            }
            this.removePopup();
        },

        popupCancelAction() {
            if (this.popupCancelCallback) {
                this.popupCancelCallback();
            }
            this.removePopup();
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

            // Vérifier si on a les bonnes données pour l'utilisateur
            if (this.storyUser) {
                log("storyUser OK");
                this.updateMarkers(this.logs);
                this.fit_markers();
                this.component = "map";
            }

            this.mapFooter = this.getMapFooter();

            this.$nextTick(() => {
  
                setTimeout(() => {
                    this.map.invalidateSize({ animate: false, pan: false });
                }, 50);
            });            
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

        action_photo() {
            if(this.canPost){
                const input = document.createElement('input');
                input.type = 'file';
                input.accept = 'image/*';
                input.capture = 'camera';
                this.handleImageSelection(input);
            }else{
                this.showAccessMessage();
            }
        },

        action_gallery() {
            log()
            if(this.canPost){
                const input = document.createElement('input');
                input.type = 'file';
                input.accept = 'image/*';
                this.handleImageSelection(input);
            }else{
                this.showAccessMessage();
            }
        },

        handleImageSelection(input) {
            input.onchange = (e) => {
                const files = e.target.files;
                if (!files || files.length === 0) return;
                
                this.showPopup("Uploading…");
                Array.from(files).forEach(file => {
                    if (!file.type.startsWith('image/')) {
                        alert('Please select only images');
                        return;
                    }

                    // Lancer l'extraction EXIF
                    this.getExifData(file)
                        .then(gpsData => {
                            log('GPS data:');
                            this.uploadImage(file, gpsData);
                        })
                        .catch(error => {

                            log('No GPS data in Exif:', error);
                            // Si pas d'EXIF, utiliser la géolocalisation actuelle
                            return this.get_localisation()
                                .then(position => {
                                    log("position OK");
                                    this.uploadImage(file, position);
                                })
                                .catch(geoError => {
                                    // alert('No location available' + geoError );
                                    return;
                            });            

                        });
            
                });
                this.removePopup();
            };
            input.click();
        },

        async getExifData(file) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    EXIF.getData(file, function() {
                        const tags = EXIF.getAllTags(this);
                        //console.log('All EXIF tags:', tags);
                        
                        if (tags.GPSLatitude && tags.GPSLongitude) {
                            let latitude = tags.GPSLatitude[0] + 
                                        tags.GPSLatitude[1]/60 + 
                                        tags.GPSLatitude[2]/3600;
                            let longitude = tags.GPSLongitude[0] + 
                                        tags.GPSLongitude[1]/60 + 
                                        tags.GPSLongitude[2]/3600;
                            
                            if (tags.GPSLatitudeRef === 'S') latitude = -latitude;
                            if (tags.GPSLongitudeRef === 'W') longitude = -longitude;

                            //console.log(tags.DateTime);
                            let timestamp = Date.now();
                            if (tags.DateTime) {
                                // Format EXIF : "2024:01:31 15:30:45"
                                const [date, time] = tags.DateTime.split(' ');
                                const [year, month, day] = date.split(':');
                                const [hours, minutes, seconds] = time.split(':');
                                timestamp = Date.UTC(year, month - 1, day, hours, minutes, seconds);
                            }
                            timestamp = Math.floor(timestamp / 1000);
                            log(timestamp);

                            resolve({ latitude, longitude, timestamp });
                        } else {
                            reject('No GPS data found in Exif');
                        }
                    });
                };
                reader.readAsDataURL(file);  // Changé de readAsArrayBuffer à readAsDataURL
            });
        },

        uploadImage(file, gpsData) {
            return new Promise((resolve, reject) => {
                try {
                    this.showPopup("Processing image...");

                    const reader = new FileReader();
                    reader.readAsDataURL(file);
                    
                    reader.onload = async (event) => {
                        try {
                            const base64Image = event.target.result;

                            if(!gpsData){
                               log('Error:', "No GPS Data");
                                resolve(false);
                            }
                    
                            const data = await apiService.call('logphoto', {
                                userid: this.user.userid,
                                routeid: this.route.routeid,
                                photofile: base64Image,
                                latitude: gpsData['latitude'],
                                longitude: gpsData['longitude'],
                                timestamp: gpsData['timestamp']
                            });

                            if (data.status == 'success') {
                                //this.newPhoto = true;
                                this.mapMode = true;
                                this.logs = data.logs;
                                await this.$nextTick();

                                const newLog = this.logs.reduce((latest, current) => {
                                    const latestTime = latest ? new Date(latest.logupdate).getTime() : 0;
                                    const currentTime = new Date(current.logupdate).getTime();
                                    return (currentTime > latestTime) ? current : latest;
                                }, null);
                                
                                // Mettre à jour les markers
                                this.updateMarkers(this.logs);
 
                                // NOUVEAU : Passer en mode map et zoomer sur le nouveau log
                                if (newLog) {
                                    log("New photo found", newLog);

                                    this.storyUser = this.user.userid;
                                    this.storyName = this.user.username;
                                    
                                    // Passer en mode map
                                    this.component = 'map';
                                    this.mapFooter = this.getMapFooter();

                                    Alpine.store('headerActions').initTitle({
                                        story: "map", 
                                        storyUserName: this.user.username, // Nom de l'utilisateur qui prend la photo
                                        storyUser: this.user.userid        // ID de l'utilisateur qui prend la photo
                                    });
                                    
                                    // Zoomer sur le nouveau marker
                                    const newMarker = this.cursors.find(marker => 
                                        marker.getLatLng().lat === newLog.loglatitude && 
                                        marker.getLatLng().lng === newLog.loglongitude
                                    );
                                    if (newMarker) {
                                        this.map.setView(newMarker.getLatLng(), 12);
                                    }
                                }
                                this.removePopup();
                                resolve(true);
                            }
                            this.removePopup();
                            resolve(false);
                        } catch (error) {
                            this.removePopup();
                            reject(error);
                        }
                    };
                    this.removePopup();
                    reader.onerror = (error) => reject(error);
                } catch (error) {
                    this.removePopup();
                    reject(error);
                }
            });
        },

        showPhoto() {
            log();
            this.newPhoto = false;
            
            // Trouver le log le plus récent
            const latestLog = this.logs.reduce((latest, current) => {
                return (!latest || current.logupdate > latest.logupdate) ? current : latest;
            }, null);


            if (latestLog) {
                //console.log("showPhoto1",latestLog);
                // Trouver le marker correspondant
                const userMarker = this.cursors.find(marker => 
                    marker.getLatLng().lat === latestLog.loglatitude && 
                    marker.getLatLng().lng === latestLog.loglongitude
                );

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
            this.map.setView([entry.loglatitude, entry.loglongitude], 12);
        },

        async showUserStory(entry){
            log(entry);
            log("---after---");
            this.story = "story";
            this.storyUser = entry.loguser;
            log(this.storyUser);
            this.storyName = entry.username_formated;
            log(this.storyName);
            this.storyPhotoOnly = false;
            log(this.storyUser);
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

        zoom_lastmarker(userid = null) {
            let targetMarker = null;

            // this.map.closePopup();

            if (userid !== null) {
                // Find the last marker for the specified user
                for (let i = this.cursors.length - 1; i >= 0; i--) {
                    const cursor = this.cursors[i];
                    const log = this.logs.find(log => log.loglatitude === cursor.getLatLng().lat && log.loglongitude === cursor.getLatLng().lng);

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
                this.map.setView(targetMarker.getLatLng(), 15); // Adjust zoom level as necessary
            }
        },

        async action_story(){
            log();
            Alpine.store('headerActions').initTitle(this.buildStoryObj("story"));
            this.component = "story";
            this.mapFooter = this.getMapFooter();
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
            if (this.sortField === field) {
                this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortField = field;
                this.sortDirection = 'desc';
            }
            this.logs = this.sortData(this.logs, this.sortField, this.sortDirection);
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

    }));

});
</script>