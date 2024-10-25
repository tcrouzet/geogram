<?php
html_header( "Map" );
?>

<div id="page">

    <?php include 'header.php'; ?>

    <!-- Section Content -->
    <main x-data="mapComponent()">
        <div x-show="view === 'map'" id="map" x-init="initializeMap"></div>
        <div x-show="view === 'list'" id="list"></div>
    </main>

    <?php include 'footer.php'; ?>

</div>
<?php
html_footer();
?>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('mapComponent', () => ({
        user: null,
        isLoggedIn: false,
        isOnRoute: false,
        view: 'map', // Default view
        data: {},
        map: null,
        cursors: [],
        geoJsonLayer: null,
        bestPosition: null,

        init(){
            this.isLoggedIn = Alpine.store('headerActions').isLoggedIn;
            if(this.isLoggedIn){
                console.log("Logged routes");
                this.user = Alpine.store('headerActions').user;
                this.isOnRoute = Alpine.store('headerActions').isOnRoute;
                this.username = this.user.username;
                this.userid = this.user.userid;
            }
        },

        initializeMap() {
            console.log('Initializing Map...');
            this.map = L.map('map').setView([0, 0], 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '<a href="https://www.openstreetmap.org/">OpenStreetMap</a>',
                maxZoom: 18,
            }).addTo(this.map);
            this.loadData('map');

            // Enregistrer les fonctions dans le store
            Alpine.store('mapActions', {
                actionFitAll: () => this.action_fitall(),
                actionFitGPX: () => this.action_fitgpx(),
                actionLocalise: () => this.action_localise()
            });

        },

        loadData() {
            console.log("loadData");
            const formData = new URLSearchParams();
            formData.append('view', this.view);
            formData.append('userid', this.user.userid);
            formData.append('routeid', this.user.routeid);

            // console.log(formData.toString());

            fetch('backend.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'Authorization': `Bearer ${this.user.auth_token}`
                },
                body: formData.toString()
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok ' + response.statusText);
                }
                //return response.text(); // testing
                return response.json();
            })
            // .then(text => {
            //     console.log("Raw Response Text:", text); // Vérifiez ici le texte brut
            //     return JSON.parse(text); // Parse manuellement pour détecter les erreurs
            // })
            .then(data => {
                //console.log(data);
                //this.data = data;
                if(data.status == 'error'){
                    alert("Error:" + data.message);
                }else if (this.view === 'map') {
                    this.updateMap(data);
                } else if (type === 'list') {
                    this.updateList(data);
                }
            })
            .catch(error => console.error('Error:', error));
        },


        updateMap(data) {
            console.log("updateMap");

            console.log(data);
            if (!data || data.length === 0) {
                console.log("No cursors.");
                this.action_localise();
                return;
            }

            // Suppression des marqueurs existants de la carte
            this.cursors.forEach(cursor => this.map.removeLayer(cursor));
            this.cursors = [];

             // Initialisation des limites des marqueurs
            this.markerBounds = new L.LatLngBounds();

            // Ajout de nouveaux marqueurs à la carte
            data.forEach((entry, index) => {

                if (index == 0) this.updateGPX(entry.gpxfile);

                // Vérification de la présence d'une image pour cet utilisateur
                const icon = entry.userimg ? L.divIcon({
                    className: 'custom-div-icon',
                    html: `<div class="marker" style="width:30px;height:30px;border:2px solid white;background-size: cover;background-image: url('${entry.userimg}')"></div>`,
                    iconSize: [34, 34],
                    iconAnchor: [15, 15]
                }) : L.divIcon({
                    className: 'custom-div-icon',
                    html: `<div class="marker" style="background-color: ${entry.usercolor};">${entry.userinitials}</div>`,
                    iconSize: [30, 30],
                    iconAnchor: [15, 15]
                });

                // Initialisation du marqueur avec l'icône personnalisée
                const marker = L.marker([entry.latitude, entry.longitude], { icon }).addTo(this.map);

                // Attachement d'une info-bulle au marqueur
                marker.bindTooltip(entry.username_formatted);

                // Ajout des coordonnées du marqueur aux limites de la carte
                this.markerBounds.extend(marker.getLatLng());

                // Gestion de l'événement de clic sur le marqueur
                marker.on("click", () => this.highlightMarker(index));

                // Stockage du marqueur dans le tableau des curseurs
                this.cursors.push(marker);
            });

            // Ajustement des limites de la carte pour inclure tous les marqueurs
            if (this.cursors.length > 0) {
                const bounds = new L.LatLngBounds(this.cursors.map(cursor => cursor.getLatLng()));
                this.map.fitBounds(this.markerBounds, { maxZoom: 10 });
            } else {
                //No cursor
                this.action_localise();
            }
        },


        updateGPX(gpxfile) {
            if (gpxfile) {
                //console.log(gpxfile);

                const startIcon = L.icon({
                    iconUrl: 'images/start.png',
                    iconSize: [30, 30],
                    iconAnchor: [15, 15],
                });

                let isFirstTrack = true;
                const map = this.map;

                // Charge la trace GPX et l'ajoute à la carte
                fetch(gpxfile)
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
                    })
                    .catch(error => {
                        console.log('Erreur GeoJSON:', error);
                    });
            }
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

        action_localise1() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(position => {
                    const { latitude, longitude } = position.coords;
                    console.log(latitude, longitude)
                    // Update map with the new location
                    L.marker([latitude, longitude]).addTo(this.map).bindTooltip("Vous êtes ici");
                    this.map.setView([latitude, longitude], 13);
                });
            } else {
                alert('Geolocalisation not supported in this browser.');
            }
        },

        action_localise() {
            if (navigator.geolocation) {
                const options = {
                    enableHighAccuracy: true,
                    timeout: 5000,
                    maximumAge: 0
                };

                this.bestPosition = null;
                let bestAccuracy = Infinity;
                let firstwatch = true;

                const watchId = navigator.geolocation.watchPosition(
                    position => {
                        const { latitude, longitude, accuracy } = position.coords;
                        console.log(`Latitude: ${latitude}, Longitude: ${longitude}, Précision: ${accuracy}m`);

                        if (accuracy < bestAccuracy) {
                            this.bestPosition = [ latitude, longitude ];
                            bestAccuracy = Math.floor(accuracy);
                        }

                        if (bestAccuracy < 20) {
                            this.finalizePosition(watchId);
                        }else{
                            if(firstwatch){
                                this.showPopup("Looking for position...");
                                firstwatch = false;
                            }else{
                                this.updatePopup(`Accuracy: ${bestAccuracy}m`, watchId);
                            }
                        }
                    },
                    error => {
                        alert('Geolocalisation Error:' + error);
                    },
                    options
                );
            } else {
                alert('Geoilocation not suppored.');
            }
        },

        finalizePosition(watchId) {
            navigator.geolocation.clearWatch(watchId);
            L.marker(this.bestPosition).addTo(this.map).bindTooltip("Tourposition");
            this.map.setView(this.bestPosition, 13);
            this.removePopup();
        },

        updatePopup(message) {
            const popup = document.getElementById('geoPopup');
            if (popup) {
                popup.querySelector('p').textContent = message;
            }
        },

        updatePopup(message, watchId) {
            const popup = document.getElementById('geoPopup');
            if (popup) {
                popup.querySelector('p').textContent = message;
                if (!document.getElementById('confirmBtn')) {
                    const button = document.createElement('button');
                    button.id = 'confirmBtn';
                    button.textContent = 'Validate';
                    button.addEventListener('click', () => {
                        this.finalizePosition(watchId);
                    });
                    popup.appendChild(button);
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
            if (this.cursors.length > 0) {
                const bounds = new L.LatLngBounds(this.cursors.map(cursor => cursor.getLatLng()));
                this.map.fitBounds(this.markerBounds, { maxZoom: 10 });
            }
        },

        action_fitgpx() {
            if (this.geoJsonLayer) {
                this.map.fitBounds(this.geoJsonLayer.getBounds(), { padding: [0, 0] });
            }
        }

    }));
});
</script>