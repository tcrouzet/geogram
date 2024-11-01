<?php
html_header( "Map" );
?>

<div id="page">

    <?php include 'header.php'; ?>

    <main x-data="mapComponent()">

        <template x-if="!route">
            <div id="login" class="loginwidth">
                <p>No route selected or available.</p>
            </div>
        </template>
        <template x-if="route && route.routestatus > 1 && !(isLoggedIn && routeid == userroute)">
            <div id="login" class="loginwidth">
                <p>This route is for invited and logged-in users only.</p>
            </div>
        </template>
        <template x-if="route && route.routestatus < 2 || (isLoggedIn && routeid == userroute)">
            <div id="globalcontainer">

                <template x-if="viewMode === 'map'">
                    <div id="mapcontainer">
                        <div id="map" x-init="initializeMap()"></div>
                        <div id="mapfooter">
                            <button @click="action_list()">List</button>
                            <button @click="action_fitall()">FitAll</button>
                            <button @click="action_fitgpx()">FitGPX</button>
                            <button @click="action_localise()">Ping</button>
                            <button @click="action_photo()">Photo</button>
                            <button @click="action_gallery()">Gallery</button>
                        </div>
                    </div>
                </template>

                <template x-if="viewMode === 'list'">
                    <div id="listcontainer">
                        <div id="list">
                            <div class="list-header">
                                <span x-text="`${logs.length} adventurers`"></span>
                                <div class="stats">
                                    <span>km</span>
                                    <span>m+</span>
                                </div>
                            </div>
                            <div class="list-content">
                                <template x-for="entry in logs" :key="entry.logid">
                                    <div class="list-row">
                                        <div class="user-col">
                                            <span class="expand">+</span>
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
                        <div id="mapfooter">
                            <button @click="action_map()">Map</button>
                            <button @click="action_localise()">Ping</button>
                            <button @click="action_message()">Message</button>
                        </div>
                    </div>
                </template>
            </div>

        </template>


    </main>

</div>
<?php
html_footer();
?>

<script>

document.addEventListener('alpine:init', () => {
    Alpine.data('mapComponent', () => ({
        viewMode: 'map',
        route: null,
        user: null,
        isLoggedIn: false,
        isOnRoute: false,
        userid: 0,
        userroute: 0,
        routeid: 0,
        usertoken: '',
        logs: [],
        map: [],
        cursors: Alpine.raw([]),
        geoJSON: null,
        geoJsonLayer: null,
        bestPosition: null,
        mapMode: true,
        uploading: false,

        
        init(){
            this.route = Alpine.store('headerActions').route;
            this.isLoggedIn = Alpine.store('headerActions').isLoggedIn;
            if(this.isLoggedIn){
                console.log("Logged routes");
                this.user = Alpine.store('headerActions').user;
                this.isOnRoute = Alpine.store('headerActions').isOnRoute;
                this.username = this.user.username;
                this.userid = this.user.userid;
                this.usertoken = this.user.usertoken;
                this.userroute = this.user.userroute;
                this.routeid = this.route.routeid;
            }
        },

        initializeMap() {
            console.log('Initializing Map...');

            this.map = Alpine.raw(L.map('map').setView([0, 0], 13)); // Carte non réactive

            // for debug
            // this.map.on('movestart move moveend zoomstart zoom zoomend drag dragend', (e) => {
            //     console.log('Map event:', e.type);
            // });
            // this.map.on('viewreset', (e) => {
            //     console.log('View reset event');
            // });
            // this.map.on('tileerror', (e) => {
            //     console.log('Tile error:', e);
            // });

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '<a href="https://www.openstreetmap.org/">OSM</a>',
                // attribution: '',
                maxZoom: 18,
            }).addTo(this.map);
            this.action_testlocalise();
            this.showPopup("Loading map…");
            this.loadMapData();
            this.$watch('logs', (newLogs) => {
                console.log("New logs");
                if(this.viewMode === 'map'){
                    this.updateMarkers(newLogs);
                    this.action_fitall();
                }
                console.log("End new log");
            });
        },

        loadMapData() {
            console.log("loadMapData");
            const formData = new URLSearchParams();
            formData.append('view', 'loadMapData');
            formData.append('userid', this.userid);
            formData.append('userroute', this.userroute);
            formData.append('routeid', this.route.routeid);
            formData.append('routestatus', this.route.routestatus);

            // console.log(formData.toString());

            fetch('backend.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'Authorization': `Bearer ${this.usertoken}`
                },
                body: formData.toString()
            })
            // .then(response => response.text()) // Récupérer le texte brut pour le débogage
            // .then(text => {
            //     console.log('Response Text:', text); // Affiche la réponse brute
            //     return JSON.parse(text); // Convertir en JSON si nécessaire
            // })
            .then(response => response.json())
            .then(data => {
                console.log(data);
                if(data.status == 'error'){
                    alert("Error:" + data.message);
                }else if (data.status == 'success') {
                    this.geoJSON = data.geojson;
                    this.updateGPX();
                    this.mapMode = true;
                    this.logs = data.logs;
                }
            })
            .catch(error => console.error('Error:', error));
        },

        userMarkers(userid){
            console.log("userMarkers");
            const formData = new URLSearchParams();
            formData.append('view', 'userMarkers');
            formData.append('userid', this.userid);
            formData.append('loguser', userid);
            formData.append('routeid', this.route.routeid);

            // console.log(formData.toString());

            fetch('backend.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'Authorization': `Bearer ${this.usertoken}`
                },
                body: formData.toString()
            })
            // .then(response => response.text()) // Récupérer le texte brut pour le débogage
            // .then(text => {
            //     console.log('Response Text:', text); // Affiche la réponse brute
            //     return JSON.parse(text); // Convertir en JSON si nécessaire
            // })
            .then(response => response.json())
            .then(data => {
                if(data.status == 'error'){
                    alert("Error:" + data.message);
                }else if (data.status == 'success') {
                    this.mapMode = false;
                    this.logs = data.logs;
                }
            })
            .catch(error => console.error('Error:', error));
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

                if (entry.loglatitude && entry.loglongitude && entry.username_formatted){

                    // Vérification de la présence d'une image pour cet utilisateur
                    const icon = entry.userphoto ? L.divIcon({
                        className: 'custom-div-icon',
                        html: `<div class="marker" style="width:30px;height:30px;border:2px solid white;background-size: cover;background-image: url('${entry.photopath}')"></div>`,
                        iconSize: [34, 34],
                        iconAnchor: [15, 15]
                    }) : L.divIcon({
                        className: 'custom-div-icon',
                        html: `<div class="marker" style="background-color: ${entry.usercolor};">${entry.userinitials}</div>`,
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
            const popupContent = this.mapMode ? 
                `<div class="geoPopup">
                    <h3>${entry.username_formatted}</h3>
                    ${entry.photolog ? `<img src="${entry.photolog}">` : ''}
                    <div class="popup-actions">
                        <button @click="userMarkers(${entry.userid})">Map history</button>
                    </div>
                </div>` :
                `<div class="geoPopup">
                    <h3>${entry.username_formatted}</h3>
                    ${entry.photolog ? `<img src="${entry.photolog}">` : ''}
                    <div class="popup-actions">
                        <button @click="loadMapData()">All Users</button>
                    </div>
                </div>`;
                      
            marker.bindPopup(popupContent,{className: 'custom-popup-content'});
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

        highlightMarker(index) {
            this.cursors.forEach((cursor, i) => {
                const element = document.getElementById(`tr${i}`);
                if (i === index) {
                    element.className = "lineG";
                    this.setCursorDiv(cursor, this.usercolors[i], this.userinitials[i], this.userimgs[i], true);
                } else {
                    element.className = i % 2 ? "line" : "lineW";
                    this.setCursorDiv(cursor, this.usercolors[i], this.userinitials[i], this.userimgs[i], false);
                }
            });
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
            fetch('backend.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'Authorization': `Bearer ${this.user.usertoken}`
                },
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

        showPopup(message) {
            let popup = document.getElementById('geoPopup');
            if (!popup) {
                popup = document.createElement('div');
                popup.id = 'geoPopup';
                popup.className = 'geo-popup';
                popup.innerHTML = `<p>${message}</p>`;
                popup.style.position = 'fixed';
                popup.style.top = '50%';
                popup.style.left = '50%';
                popup.style.transform = 'translate(-50%, -50%)';
                popup.style.backgroundColor = 'white';
                popup.style.padding = '20px';
                popup.style.boxShadow = '0 4px 6px rgba(0, 0, 0, 0.1)';
                popup.style.zIndex = '1000';
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
            
            // Attendre un court instant avant d'effectuer l'opération
            // setTimeout(() => {
            //     try {

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
                // } catch (error) {
                //     console.error('Error in fitall:', error);
                // }
            // }, 100); // Délai de 100ms
        },


        action_fitgpx() {
            console.log("fitgpx");

            // setTimeout(() => {
            //     try {
                    if (this.geoJsonLayer && this.map.hasLayer(this.geoJsonLayer)) {
                        this.map.fitBounds(this.geoJsonLayer.getBounds(), { padding: [0, 0], animate: false });
                    }
            //     } catch (error) {
            //         console.error('Error in figpx:', error);
            //     }
            // }, 100);
        },

        action_map() {
            console.log("map");
            this.viewMode = "map";
        },

        action_list() {
            console.log("list");
            this.viewMode = "list";
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
            };
            
            this.map.once('click', clickHandler);
        },

        action_photo() {
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = 'image/*';
            input.capture = 'environment';
            this.handleImageSelection(input);
        },

        action_gallery() {
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = 'image/*';
            this.handleImageSelection(input);
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

                    fetch('backend.php', {
                        method: 'POST',
                        headers: {
                            'Authorization': `Bearer ${this.user.usertoken}`
                        },
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
                            this.logs = data.logs;
                            this.showPhoto();
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

        showPhoto(){
            console.log("showPhoto");
            const userMarker = this.cursors.find(marker => {
                // Recherche de l'entrée correspondante dans les logs
                const entry = this.logs.find(log => log.userid === this.user.userid);
                return marker.getLatLng().lat === entry.loglatitude && 
                    marker.getLatLng().lng === entry.loglongitude;
            });

            if (userMarker) {
                console.log("showPhoto2");
                // Recherche de l'entrée correspondante dans les logs pour la mise à jour du popup
                const entry = this.logs.find(log => log.userid === this.user.userid);
                this.markerPopup(userMarker, entry);
                // Ouvre le popup immédiatement
                userMarker.openPopup();
            }
        }

    }));
});
</script>