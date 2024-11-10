<?php include 'header.php'; ?>

<main x-data="mapComponent()">

    <div x-show="component === 'splash'" id="splash">
        <h1>Share your adventures</h1>
        <p>When you bike or hike, you can send your location, pictures and messages to your friends.</p>
        <h1>Geogram Test Route</h1>
        <p>Even without <a href="/login">log in</a>, you can see the <a href="/testroute">Test Route</a> where all <a href="/login">log in</a> users can test Geogram.</p>
        <h1>Create your own routes</h1>
        <p>Once <a href="/login">log in</a>, you can create a public or private route. Then you can invite spectators or adventurers to join the route.</p>
        <p style="text-align: center;"><br><a href="/help">More informations…</a></p>

    </div>

    <div x-show="component === 'error'" id="splash">
        <h1>Access denied</h1>
        <p>This route is for invited and logged in users only.</p>
    </div>

    <div x-show="component === 'map'" id="mapcontainer">
        <div id="map"></div>
        <div x-html="getMapFooter('map')"></div>
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
                    <h1 x-text="Story'"></h1>

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
                                        <span class="log-author" x-text="log.username_formated"></span>
                                    </template>
                                    <span class="log-date" x-text="log.date_formated"></span>
                                </div>

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
        <div x-html="getMapFooter('story')"></div>
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
                            <span x-text="`${logs.length} adventurers`"></span>
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
                            <div class="list-row" @click="showUserOnMap(entry)">
                                <div class="user-col">
                                    <span class="expand" @click.stop="expandUser(entry)">+</span>
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

        <div x-html="getMapFooter('list')"></div>

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

</main>

<script>

document.addEventListener('alpine:init', () => {
    Alpine.data('mapComponent', () => ({
        //Display
        component: 'splash',
        //Datas
        route: null,
        user: null,
        isLoggedIn: false,
        isOnRoute: false,
        userid: 0,
        userroute: 0,
        routeid: 0,
        logs: [],
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
        // Comments
        showCommentModal: false,
        commentText: '',
        currentLogId: null,
        //List
        sortField: 'username',
        sortDirection: 'asc',
        //Story
        storyUser: null,
        storyName: '',
        sortField: 'logtime',
        sortDirection: 'desc',
        slogs: false,
        storyPhotoOnly: false,

        async init() {
            await initService.initComponent(this);
            
            if (!this.route) {
                this.component = 'splash';
            } else if (this.route.routestatus > 1 && !(this.isLoggedIn && this.routeid == this.userroute)) {
                this.component = 'error';
            } else {
                this.component = 'map';
                await new Promise(resolve => setTimeout(resolve, 100));
                await this.initializeMap();
            }
            console.log("Component:" + this.component);

            window.addEventListener('error', (event) => {
                if (event.message && (
                    event.message.includes('_latLngToNewLayerPoint')
                )) {
                    alert('Leaflet error detected, reloading...');
                    window.location.reload();
                }
            });
            console.log("Init Map ended");
        },

        checkMap(value) {
            console.log("checkMap");
            if (value === 'map') {
                this.initializeMap();
            }
        },

        initializeMap() {
            console.log('Initializing Map...');
            this.map = Alpine.raw(L.map('map').setView([0, 0], 13)); // Carte non réactive

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '<a href="https://www.openstreetmap.org/">OSM</a>',
                // attribution: '',
                maxZoom: 18,
            }).addTo(this.map);
            //this.action_testlocalise();
            this.showPopup("Loading map…");
            this.loadMapData();
            this.$watch('logs', (newLogs) => {
                console.log("New logs");
                if(this.component === 'map'){
                    this.updateMarkers(newLogs);
                    this.action_fitall();
                    if(this.newPhoto){
                        this.showPhoto();
                    }
                }
                console.log("End new log");
            });
        },

        async loadMapData() {
            const data = await apiService.call('loadMapData', {
                userroute: this.userroute,
                routeid: this.route.routeid,
                userstory: this.userstory,
                routestatus: this.route.routestatus
            });
            if (data.status == 'success') {
                this.geoJSON = data.geojson;
                this.updateGPX();
                this.mapMode = true;
                this.logs = data.logs;
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
            }
        },

        updateMarkers(logs) {
            console.log("updateMarkers");

            // Suppression des marqueurs existants de la carte
            this.cursors.forEach(cursor => this.map.removeLayer(cursor));
            this.cursors = [];

             // Initialisation des limites des marqueurs
            this.markerBounds = new L.LatLngBounds();

            // Ajout de nouveaux marqueurs à la carte
            logs.forEach((entry, index) => {

                if (entry.loglatitude && entry.loglongitude && entry.username_formated){

                    // Vérification de la présence d'une image pour cet utilisateur
                    const statusIcon = entry.logphoto ? 
                        '<i class="fa-solid fa-camera status-icon"></i>' : 
                        (entry.logcomment ? '<i class="fa-solid fa-comment status-icon"></i>' : '');

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
                this.action_localise();
            }
            console.log("Fin Markers");
        },

        markerPopup(marker, entry){
            //console.log(entry);

            const commentButton = entry.loguser === this.userid ? 
                `<button @click="addComment(${entry.logid})">
                    ${entry.logcomment ? 'Edit comment' : 'Add comment'}
                </button>` : '';
 
            const popupContent = this.mapMode ? 
                `<div class="geoPopup">
                    <h3>${entry.username_formated}</h3>
                    ${entry.photolog ? `<img src="${entry.photolog}">` : ''}
                    ${entry.logcomment ? `<p class="commentText">${entry.logcomment}</p>` : ''}
                    <div class="popup-actions">
                        ${commentButton}
                        <button @click="userMarkers(${entry.userid})">Map history</button>
                    </div>
                </div>` :
                `<div class="geoPopup">
                    <h3>${entry.username_formated}</h3>
                    ${entry.photolog ? `<img src="${entry.photolog}">` : ''}
                    ${entry.logcomment ? `<p class="commentText">${entry.logcomment}</p>` : ''}
                    <div class="popup-actions">
                        ${commentButton}
                        <button @click="loadMapData()">All Users</button>
                    </div>
                </div>`;
                      
            marker.bindPopup(popupContent,{className: 'custom-popup-content'});
        },

        addComment(logId) {
            this.currentLogId = logId;
            const entry = this.logs.find(log => log.logid === logId);
            if (entry) {
                this.commentText = entry.logcomment || '';
            }
            this.showCommentModal = true;
        },

        submitComment() {
            if (this.commentText.trim() === '') {
                alert('Le commentaire ne peut pas être vide');
                return;
            }
            
            const formData = new FormData();
            formData.append('view', 'submitComment');
            formData.append('logid', this.currentLogId);
            formData.append('comment', this.commentText);
            formData.append('userid', this.userid);
            formData.append('routeid', this.routeid);
            
            fetch('/api/', {
                method: 'POST',
                body: formData,
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    this.mapMode = false;
                    this.showCommentModal = false;
                    this.newPhoto = true;
                    this.logs = data.logs;
                    //this.showPhoto();
                } else {
                    console.log('Erreur : ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Erreur lors de l\'ajout du commentaire');
            });
        },

        updateGPX() {
            console.log("Display Geojson",this.geoJSON);

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

            // Charge la trace GPX et l'ajoute à la carte
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
                                L.marker(firstPointLatLng, {icon: startIcon}).addTo(map);
                                isFirstTrack = false;
                            }
                        }
                    }).addTo(this.map);

                    this.map.fitBounds(this.geoJsonLayer.getBounds(), { padding: [0, 0] });
                    this.removePopup();
                })
                .catch(error => {
                    console.log('Erreur GeoJSON:', error);
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
            console.log("Localise " + this.canPost);
            if(this.canPost){
                this.action_map();
                try {
                    const bestPosition = await this.get_localisation();
                    if(bestPosition){
                        this.bestPosition = bestPosition;
                        this.sendgeolocation();
                    }
                } catch (error) {
                    console.log('Error or cancelled:', error);
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
                        console.log(`Latitude: ${latitude}, Longitude: ${longitude}, Précision: ${bestAccuracy}m`);

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

        sendgeolocation() {
            // Envoyer une requête pour mettre à jour la route
            fetch('/api/', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',                },
                body: new URLSearchParams({
                    view: "sendgeolocation",
                    userid: this.userid,
                    routeid: this.routeid,
                    latitude: this.bestPosition["latitude"],
                    longitude: this.bestPosition["longitude"]
                })
            })
            // .then(response => response.text()) // Récupérer le texte brut pour le débogage
            // .then(text => {
            //     console.log('Response Text:', text); // Affiche la réponse brute
            //     return JSON.parse(text); // Convertir en JSON si nécessaire
            // })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    this.logs = data.logs;
                    console.log('New location');
                } else {
                    console.error('Error updating location:', data.message);
                }
            })
            .catch(error => console.error('Error:', error));
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
            console.log("fitall");
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
            console.log("fitgpx");
            if (this.geoJsonLayer && this.map.hasLayer(this.geoJsonLayer)) {
                this.map.fitBounds(this.geoJsonLayer.getBounds(), { padding: [0, 0], animate: false });
            }
            this.action_map();
        },

        action_map() {
            console.log("map");
            this.component = "map";
        },

        action_list() {
            console.log("list");
            this.component = "list";
        },

        action_reload(){
            window.location.reload();
        },

        action_testlocalise() {
            const clickHandler = (e) => {
                console.log("testGeolocalise");
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
                    console.log(this.bestPosition);
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
                            console.log('GPS data:');
                            this.uploadImage(file, gpsData);
                        })
                        .catch(error => {

                            console.log('No GPS data:', error);
                            // Si pas d'EXIF, utiliser la géolocalisation actuelle
                            return this.get_localisation()
                                .then(position => {
                                    console.log("position OK");
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
                            console.log(timestamp);

                            resolve({ latitude, longitude, timestamp });
                        } else {
                            reject('No GPS data found');
                        }
                    });
                };
                reader.readAsDataURL(file);  // Changé de readAsArrayBuffer à readAsDataURL
            });
        },

        uploadImage(file, gpsData) {
            try {

                const reader = new FileReader();
                reader.readAsDataURL(file);
                reader.onload = (event) => {

                    const base64Image = event.target.result;

                    const formData = new FormData();
                    formData.append('view', 'logphoto');
                    formData.append('userid', this.user.userid);
                    formData.append('routeid', this.routeid);
                    formData.append('photofile',  base64Image);

                    if(gpsData){
                        formData.append('latitude', gpsData['latitude']);
                        formData.append('longitude', gpsData['longitude']);
                        formData.append('timestamp', gpsData['timestamp']);
                    }else{
                        console.error('Error:', "No GPS Data");
                        return false;
                    }

                    fetch('/api/', {
                        method: 'POST',
                        body: formData
                    })
                    // .then(response => response.text()) // Récupérer le texte brut pour le débogage
                    // .then(text => {
                    //     console.log('Response Text:', text); // Affiche la réponse brute
                    //     return JSON.parse(text); // Convertir en JSON si nécessaire
                    // })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            this.newPhoto = true;
                            this.mapMode = false;
                            this.logs = data.logs;
                            return true;
                        } else {
                            alert(data.message);
                            return false;
                        }
                    })
                    .catch(error => {
                        this.uploading = false;
                        console.error('Error:', error);
                        alert('An error occurred during upload.');
                    });
                }

            } catch (error) {
                this.uploading = false;
                console.error('Error:', error);
                alert('Upload error: ' + error.message);
            }
        },

        showPhoto() {
            console.log("showPhoto");
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
            if (!this.isLoggedIn) {
                this.showPopup('You need to <a href="/login">log in</a> to use this feature', true);
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

        showUserOnMap(entry) {
            this.component = 'map';
            // Attendre que la carte soit prête
            this.$nextTick(() => {
                // Trouver le marker correspondant
                const userMarker = this.cursors.find(marker => 
                    marker.getLatLng().lat === entry.loglatitude && 
                    marker.getLatLng().lng === entry.loglongitude
                );

                if (userMarker) {
                    // Centrer la carte sur le marker
                    this.map.setView(userMarker.getLatLng(), 12);
                    // Ouvrir le popup
                    userMarker.openPopup();
                }
            });
        },

        expandUser(entry){
            this.storyUser = entry.userid;
            console.log(this.storyName)
            this.storyName = entry.username_formated;
            this.storyPhotoOnly = false;
            this.action_story();
        },

        getMapFooter(mode) {
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
            };

            return `
                <div id="mapfooter">
                    <div class="small-line">
                        ${modeButtons[mode]}
                        <button @click="action_fitall()" class="small-bt">
                            <i class="fas fa-expand-arrows-alt"></i>
                        </button>
                        <button @click="action_fitgpx()" class="small-bt">
                            <i class="fas fa-compress"></i>
                        </button>
                        <button @click="action_gallery()" class="small-bt" ${!this.canPost ? 'disabled-bt' : ''}">
                            <i class="fas fa-images"></i>
                        </button>
                    </div>
                    <div class="big-line">
                        <button @click="action_localise()" class="big-bt" ${!this.canPost ? 'disabled-bt' : ''}">
                            <i class="fas fa-map-marker-alt"></i>
                        </button>
                        <button @click="action_photo()" class="big-bt" ${!this.canPost ? 'disabled-bt' : ''}">
                            <i class="fas fa-camera"></i>
                        </button>
                    </div>
                </div>
            `;
            //<button @click="action_reload()" class="small-bt"><i class="fas fa-rotate"></i></button>
        },

        async action_story(){
            if(!this.slogs){
                const data = await apiService.call('story', {
                    routeid: this.route.routeid,
                });
                if (data.status == 'success') {
                    this.slogs = this.sortData(data.logs, this.sortField, this.sortDirection);
                }
            }
            this.component = "story";
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

    }));
});
</script>