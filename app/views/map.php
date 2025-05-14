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

                            <div class="log-entry">
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
                                    <template x-if="log.morephotologs.length > 0">
                                        <template x-for="additionalPhoto in log.morephotologs">
                                            <img :src="additionalPhoto" class="log-photo" alt="Additional photo">
                                        </template>
                                    </template>
                                    
                                    <template x-if="log.comment_formated">
                                        <p class="log-comment" x-html="log.comment_formated"></p>
                                    </template>

                                    <div class="log-stats">
                                        <span class="map-link" @click.stop="showUserOnMap(log)">
                                            <i class="fas fa-map-marker-alt"></i>
                                        </span>
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
                        <div class="sortable-col" @click="sortBy('username')">
                            <span x-text="`${logs.length} ${getAdventurerLabel()}`"></span>
                            <i class="fas" :class="{
                                'fa-sort': sortField !== 'username',
                                'fa-sort-up': sortField === 'username' && sortDirection === 'asc',
                                'fa-sort-down': sortField === 'username' && sortDirection === 'desc'
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

    <div x-show="showCustomPopup" class="custom-popup-modal">
        <button @click="showCustomPopup = false" class="custom-popup-close">×</button>
        <div class="custom-popup" x-html="customPopupContent"></div>
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
        //Calculated
        isLoggedIn: false,
        isOnRoute: false,
        canPost: false,
        userid: 0,
        userroute: 0,
        routeid: 0,
        logs: [],
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
        slogs: false,
        storyPhotoOnly: false,
        //Popup
        showCustomPopup: false,
        customPopupContent: '',

        async init() {
            await initService.initComponent(this);
            
            if(this.component == 'map' || this.component == 'list' || this.component == 'story'){
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

            if(this.component == "story"){
                await this.action_story();
            }else if(this.component == "list"){
                await this.action_list();
            }

            this.loading = false;
            log("Init Map ended");
        },

        checkMap(value) {
            log("checkMap");
            if (value === 'map') {
                this.initializeMap();
            }
        },

        async initializeMap() {
            log();
            this.map = Alpine.raw(L.map('map').setView([0, 0], 13)); // Carte non réactive

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '<a href="https://www.openstreetmap.org/">OSM</a>',
                // attribution: '',
                maxZoom: 18,
            }).addTo(this.map);

            await this.loadMapData();
            this.updateMarkers(this.logs);

            if(this.storyUser){
                log("storyUser",this.storyUser)
                await this.userMarkers(this.storyUser);
            }
            if(this.page && this.page != "map"){
                this.component = this.page;
            }

            if(this.newPhoto){
                this.showPhoto();
            }
            log("end");
        },

        async loadMapData() {
            log();
            const data = await apiService.call('loadMapData', {
                userroute: this.userroute,
                routeid: this.route.routeid,
                // userstory: this.userstory,
                routestatus: this.route.routestatus
            });
            if (data.status == 'success') {
                this.geoJSON = data.geojson;
                this.updateGPX();
                this.mapMode = true;
                this.logs = data.logs;
                log(this.logs);
            }
        },

        async userMarkers(userid){

            const data = await apiService.call('userMarkers', {
                routeid: this.route.routeid,
                loguser: userid
            });
            if (data.status == 'success') {
                this.mapMode = false;
                this.logs = data.logs;
                log(this.logs[0]['username']);
                this.storyUser = this.logs[0]['userid'];
                this.story ="map";
                this.storyName = this.logs[0]['username'];
                this.updateMarkers(this.logs);
                this.fit_markers();
            }
        },

        updateMarkers(logs) {
            log();
            this.showCustomPopup = false;


            // Suppression des marqueurs existants de la carte
            this.cursors.forEach(cursor => this.map.removeLayer(cursor));
            this.cursors = [];

             // Initialisation des limites des marqueurs
            this.markerBounds = new L.LatLngBounds();

            // Ajout de nouveaux marqueurs à la carte
            logs.forEach((entry, index) => {

                if (entry.loglatitude && entry.loglongitude && entry.username_formated){

                    // Vérification de la présence d'une image pour cet utilisateur
                    // const statusIcon = entry.logphoto ? 
                    //     '<i class="fa-solid fa-camera status-icon"></i>' : 
                    //     (entry.logcomment ? '<i class="fa-solid fa-comment status-icon"></i>' : '');

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

                    // Contenu du popup avec des boutons d'action
                    this.markerPopup(marker, entry);

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
        
        // Mise à jour du marker
        updateOneMarker(currentLogId){
            const updatedLog = this.logs.find(log => log.logid === currentLogId);
            if (updatedLog) {
                const marker = this.cursors.find(marker => 
                    marker.getLatLng().lat === updatedLog.loglatitude && 
                    marker.getLatLng().lng === updatedLog.loglongitude
                );
                
                if (marker) {
                    // Recréer le popup avec le nouveau contenu
                    this.markerPopup(marker, updatedLog);
                    marker.openPopup();
                }
            }
        },

        markerPopup(marker, entry){
            const commentButton = entry.loguser === this.userid ? 
                `<button @click="addComment(${entry.logid})">
                    ${entry.logcomment ? 'Edit comment' : 'Add comment'}
                </button>` : '';

            const deleteButton = entry.loguser === this.userid ? 
                `<button @click="deleteCursor(${entry.logid})">Delete</button>` : '';

            const actionButton = this.mapMode ? 
                `<button @click="userMarkers(${entry.userid})">User history</button>` :
                `<button @click="allUsersMarkers()">All Users</button>`;

            const zoomButton = `<button @click="zoom_lastmarker(${entry.userid})">Zoom last</button>`;

            let morePhotos = '';
            if (entry.morephotologs && entry.morephotologs.length > 0) {
                entry.morephotologs.forEach(photoUrl => {
                    morePhotos += `<img src="${photoUrl}">`;
                });
            }


            const popupContent = `<div class="log-entry story-logs">
                <h3>${entry.username_formated}</h3>
                <h4>${entry.date_formated}</h4>
                ${entry.photolog ? `<img src="${entry.photolog}">` : ''}
                ${morePhotos}
                ${entry.logcomment ? `<p class="commentText">${entry.comment_formated}</p>` : ''}
                <div class="popup-actions">
                    ${commentButton}
                    ${deleteButton}
                    ${actionButton}
                    ${zoomButton}
                </div></div>`;
   
            marker.on('click', () => {
                this.customPopupContent = popupContent;
                // this.customPopupContent += `<p>${entry.logid}</p>`;
                this.showCustomPopup = true;
            });

        },

        async allUsersMarkers(){
            await this.loadMapData();
            this.updateMarkers(this.logs);
            this.fit_markers();
        },


        async deleteCursor(logid) {
            // Find the log and marker to delete

            if (!confirm(' Do you really want to delete cursor?') ) {
                    return;
            }

            const logIndex = this.logs.findIndex(log => log.logid === logid);

            const data = await apiService.call('deleteLog', {
                logid: logid,
                routeid: this.routeid,
            });
            if (data.status == 'success') {

                // Fermer tous les popups
                this.map.closePopup();

                this.action_map();
        
                await this.loadMapData();
                this.updateMarkers(this.logs);
                this.action_fitgpx()
        
            }
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
                this.mapMode = false;
                this.showCommentModal = false;
                this.newPhoto = true;
                this.logs = data.logs;
                this.updateOneMarker(this.currentLogId);
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

        get_localisation() {
            if (!navigator.geolocation) {
                alert('Geolocation not supported');
                return null;
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
            const popup = document.getElementById('geoPopup');
            if (popup) {
                popup.querySelector('p').textContent = message;
                if (!document.getElementById('confirmBtn')) {
                    const button = document.createElement('button');
                    button.id = 'confirmBtn';
                    button.textContent = 'Validate';
                    button.onclick = () => {
                        navigator.geolocation.clearWatch(watchId);
                        this.removePopup();
                        resolve(bestPosition);
                    };
                    popup.appendChild(button);
                }
                if (!document.getElementById('cancelBtn')) {
                    const cancelButton = document.createElement('button');
                    cancelButton.id = 'cancelBtn';
                    cancelButton.textContent = 'Cancel';
                    cancelButton.onclick = () => {
                        navigator.geolocation.clearWatch(watchId);
                        this.removePopup();
                        reject('Cancelled by user');
                    };
                    popup.appendChild(cancelButton);
                }
            }
        },

        showPopup(message, validate=false) {
            let popup = document.getElementById('geoPopup');
            if (!popup) {
                let overlay;
                
                // Créer et ajouter l'overlay
                if(validate) {
                    overlay = document.createElement('div');
                    overlay.id = 'popupOverlay';
                    overlay.className = 'overlay';
                    // Fermer quand on clique sur l'overlay
                    overlay.addEventListener('click', () => {
                        overlay.remove();
                        popup.remove();
                    });
                }
                
                popup = document.createElement('div');
                popup.id = 'geoPopup';
                popup.className = 'geo-popup';
                popup.innerHTML = `<div style="position: relative;">`;
                if(validate) {
                    // Utiliser une fonction au lieu d'un onclick inline
                    popup.innerHTML += `<span class="cross">×</span>`;
                }
                popup.innerHTML += `<p>${message}</p></div>`;
                popup.style.position = 'fixed';
                popup.style.top = '50%';
                popup.style.left = '50%';
                popup.style.transform = 'translate(-50%, -50%)';
                popup.style.backgroundColor = 'white';
                popup.style.padding = '20px';
                popup.style.boxShadow = '0 4px 6px rgba(0, 0, 0, 0.1)';
                popup.style.zIndex = '1000';

                if(validate) {
                    // Ajouter le gestionnaire d'événement pour la croix
                    const cross = popup.querySelector('.cross');
                    cross.addEventListener('click', () => {
                        popup.remove();
                        overlay.remove();
                    });

                    // Empêcher la propagation du clic sur le popup
                    popup.addEventListener('click', (e) => {
                        e.stopPropagation();
                    });
                    document.body.appendChild(overlay);
                }

                document.body.appendChild(popup);
            }
        },

        removePopup(){
            const popup = document.getElementById('geoPopup');
            if (popup) {
                document.body.removeChild(popup);
            }
        },

        action_fitall() {
            log();
            this.showCustomPopup = false;
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

        action_fitgpx() {
            log();
            this.showCustomPopup = false;
            if (this.geoJsonLayer && this.map.hasLayer(this.geoJsonLayer)) {
                this.map.fitBounds(this.geoJsonLayer.getBounds(), { padding: [0, 0], animate: false });
            }
            this.action_map();
        },

        fit_markers() {
            this.showCustomPopup = false;
            if (this.cursors.length > 0) {
                const markerBounds = new L.LatLngBounds(this.cursors.map(cursor => cursor.getLatLng()));
                this.map.fitBounds(markerBounds, { 
                    padding: [50, 50], 
                    maxZoom: 18,
                    animate: false
                });
            }
            this.action_map();
        },

        action_map() {  
            log();
            this.showCustomPopup = false;

            // Si nous avons un utilisateur sélectionné en mode story, recharger la page
            if (this.storyUser) {
                // Récupérer l'URL actuelle
                const currentUrl = window.location.href;
                
                // Remplacer "story" ou "list" par "map" dans l'URL
                const newUrl = currentUrl.replace(/\/(story|list)\//, '/map/');
                
                // Si l'URL a été modifiée, charger la nouvelle URL
                if (newUrl !== currentUrl) {
                    window.location.href = newUrl;
                    return; // Arrêter l'exécution ici car la page va se recharger
                }
            }

            Alpine.store('headerActions').initTitle(this.buildStoryObj("map"));
            this.component = "map";
            this.mapFooter = this.getMapFooter();

            this.$nextTick(() => {
                setTimeout(() => {
                    this.map.invalidateSize({ animate: false, pan: false });
                }, 50);
            });            
        },

        action_list() {
            log();
            this.showCustomPopup = false;
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
                input.capture = 'environment';
                this.handleImageSelection(input);
            }else{
                this.showAccessMessage();
            }
        },

        action_gallery() {
            this.showCustomPopup = false;
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
                                    alert('No location available' + geoError );
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
                    
                                // Si on trouve le nouveau log, ouvrir son popup
                                if (newLog) {
                                    log("New photo found", newLog);
                                    const newMarker = this.cursors.find(marker => 
                                        marker.getLatLng().lat === newLog.loglatitude && 
                                        marker.getLatLng().lng === newLog.loglongitude
                                    );
                                    if (newMarker) {
                                        this.map.setView(newMarker.getLatLng(), 12);
                                        newMarker.openPopup();
                                    }
                                }
 
                                resolve(true);
                            }

                            resolve(false);
                        } catch (error) {
                            reject(error);
                        }
                    };
                    
                    reader.onerror = (error) => reject(error);
                } catch (error) {
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

                //console.log("marker",userMarker);

                if (userMarker) {
                    //console.log("showPhoto2", userMarker);
                    userMarker.openPopup();
                }
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
            this.mapFooter = this.getMapFooter();

            if (this.slogs) {
                this.logs = this.slogs;
            }

            await this.$nextTick();

            this.updateMarkers(this.logs);

            // Trouver et afficher le marker
            const userMarker = this.cursors.find(marker => 
                marker.getLatLng().lat === entry.loglatitude && 
                marker.getLatLng().lng === entry.loglongitude
            );

            if (userMarker) {
                this.map.setView(userMarker.getLatLng(), 12);
                userMarker.openPopup();
            }
        },

        showUserStory(entry){
            this.story = "story";
            this.storyUser = entry.userid;
            this.storyName = entry.username_formated;
            this.storyPhotoOnly = false;
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
                        <button @click="action_fitgpx()" class="small-bt">
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
            this.showCustomPopup = false;
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
                targetMarker.openPopup(); // Optionally open the popup for the target marker
            }
        },

        async action_story(){
            this.showCustomPopup = false;
            if(!this.slogs){
                log();
                this.loading = true;
                const data = await apiService.call('story', {
                    routeid: this.route.routeid,
                });
                if (data.status == 'success') {
                    this.slogs = this.sortData(data.logs, this.sortField, this.sortDirection);
                }else{
                    alert(data.message);
                    window.location.reload();
                }
                this.loading = false;
            }
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
            this.slogs = this.sortData(this.slogs, this.sortField, this.sortDirection);
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