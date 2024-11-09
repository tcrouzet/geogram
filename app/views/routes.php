<div id="page">

    <?php include 'header.php'; ?>

    <!-- Section Content -->
    <main x-data="routesComponent()" >

        <template x-if="!isLoggedIn">

            <div id="splash">

                <h1>Sorry</h1>
                <p>You have to be logged.</p>

            </div>
        </template>

        <template x-if="isLoggedIn">
            <div id="splash">


                <div class="divider">ROUTE CONNECTOR</div>

                <p>You can connect to a route you created, a route you were invited to or a public route. Only one route at a time.</p>

                <ul>
                    <template x-for="route in routes" :key="route.routeid">
                        <li>
                            <a :href="`/${route.routeslug}`" x-text="route.routename"></a>
                            <span x-show="route.routeid === user.userroute">(connected)</span>
                            <button x-show="route.routeid != user.userroute" @click="toggleConnect(route.routeid)">Connect</button>
                            <button x-show="route.constatus === 3" @click="toggleEdit(route.routeid)">Edit</button>
                            
                            <div x-show="route.routeid === editingRouteId">
                                <!-- Bloc d'édition -->
                                <label>Name</label>
                                <input type="text" class="input-field" x-model="route.routename" required minlength="3" maxlength="30" @change="updateRoute(route)">

                                <label>Description</label>
                                <input type="text" class="input-field" x-model="route.routerem" required minlength="30" maxlength="256" @change="updateRoute(route)">

                                <label>
                                    <input type="checkbox" x-model="route.routeclosed" @change="updateRoute(route)" :checked="route.routeclosed === 1"> Route closed
                                </label>
                                                        
                                <label x-text="route.gpx === 0 ? 'New GPX' : 'Update GPX'"></label>
                                <input type="file" @change="handleGPXUpload(route.routeid)" accept=".gpx" class="input-field">
                                <div x-show="gpxError" class="error-message" x-text="gpxError"></div>
                                
                                <label>Status</label>
                                <select x-model="route.routestatus" @change="updateRoute(route)">
                                    <option value="2">Private</option>
                                    <option value="1">Open for logged in</option>
                                    <option value="0">Open for all</option>
                                </select>
                                <div x-show="route.routestatus > 0">
                                    <a :href="`${route.publishpath}`" x-text="'Invitation link for publishers'"></a>
                                    <button class="copy-button" @click="copyToClipboard(`${route.publishpath}`)">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                                <div x-show="route.routestatus > 1">
                                    <a :href="`${route.invitpath}`" x-text="'Invitation link for viewers'"></a>
                                    <button class="copy-button" @click="copyToClipboard(`${route.invitpath}`)">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>

                                <label>Route image (JPEG only):</label>
                                <input type="file" @change="handlePhotoUpload(route.routeid)" accept="image/jpeg" class="input-field">
                                <div x-show="photoError" class="error-message" x-text="photoError"></div>
                                <div class="input-group" x-show="photoPreview || route.photopath">
                                    <img :src="photoPreview || route.photopath" alt="Image Preview" class="image-preview" style="max-width: 200px; max-height: 200px;">
                                </div>

                                <template x-if="telegramChannels && telegramChannels.length > 0">
                                    <div>
                                        <div class="divider">TELEGRAM</div>
                                        <label>Telegram channels</label>
                                        <div x-effect="$el.value = route.routetelegram">
                                            <select @change="updateRoute(route)">
                                                <option value="">Select a channel...</option>
                                                <template x-for="channel in telegramChannels" :key="channel.id">
                                                    <option 
                                                        :value="channel.id" 
                                                        x-text="channel.title"
                                                        :selected="channel.id === route.routetelegram">
                                                    </option>
                                                </template>
                                            </select>
                                        </div>

                                        <label>Mode</label>
                                        <select x-model="route.routemode" @change="updateRoute(route)">
                                            <option value="2">Nothing deleted</option>
                                            <option value="1">Locations deleted</option>
                                            <option value="0">All messages deleted</option>
                                        </select>
                                    </div>
                                </template>

                                <div class="divider">ACTIONS</div>
                                <div id="actions">
                                    <button @click="route_actions(route.routeid,'purgeroute',$el.textContent)">Delete logs</button>
                                    <button @click="route_actions(route.routeid,'purgephotos',$el.textContent)">Delete logs & photos</button>
                                </div>
                                <div x-show="actionError" class="error-message" x-text="actionError"></div>

                                <div class="divider"></div>

                            </div>
                        </li>
                    </template>
                </ul>
    
                <div class="divider">ROUTE PLANNER</div>

                <div class="input-group">
                    <input type="text" placeholder="Route name" class="input-field" x-model="routename" required minlength="3" maxlength="30" @keyup="checkRoutename">
                </div>
                <div x-show="routenameError" class="error-message" x-text="routenameError"></div>

                <button class="btn btn-submit" type="submit" @click="newRouteForm()" x-bind:disabled="loading">
                    New route
                </button>

            </div>  
        </template>

    </main>

</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('routesComponent', () => ({

        // Variables locales pour la gestion de l'authentification
        user: null,
        isLoggedIn: false,
        isOnRoute: false,
        routes: [],
        editingRouteId: [],
        routename: '',
        routenameError: '',
        gpxFile: null,
        gpxError: '',
        photoError: '',
        photoPreview: null,
        actionError: '',
        userid: null,
        loading: false,
        reservedNames: <?= json_encode(FORBIDDEN_SLUG) ?>,
        // Telegram
        telegramConnected: false,
        telegramChannels: [],

        init(){
            console.log("Init routes");
            this.isLoggedIn = Alpine.store('headerActions').isLoggedIn;
            if(this.isLoggedIn){
                console.log("Logged routes");
                this.user = Alpine.store('headerActions').user;
                this.isOnRoute = Alpine.store('headerActions').isOnRoute;
                this.username = this.user.username;
                this.userid = this.user.userid;
                if(this.user.usertelegram){
                    console.log("usertelegram");
                    this.telegramConnected = true;
                    this.loadTelegramChannels();
                }
                this.loadRoutes();
            }
        },

        loadRoutes() {
            console.log("loadRoutes");
            const formData = new URLSearchParams();
            formData.append('view', "getroutes");
            formData.append('userid', this.userid);

            // console.log(formData.toString());

            fetch('/api/', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
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
                this.routes = data;
                //this.printRoutes(data);
            })
            .catch(error => console.error('Error:', error));
        },

        toggleEdit(routeId) {
            this.editingRouteId = this.editingRouteId === routeId ? null : routeId;
        },

        checkRoutename() {
            if (this.routename.length < 3) {
                this.routenameError = 'Routename must be at least 3 characters.';
            } else if (this.routename.length > 30) {
                this.routenameError = 'Routename must not exceed 30 characters.';
            } else if (this.reservedNames.some(name => name === this.routename.toLowerCase())) {
                this.routenameError = 'Forbidden routename';
            } else {
                this.routenameError = '';
            }
            console.log("checkRoutename");
        },

        // Fonction pour gérer la connexion via le formulaire
        newRouteForm() {
            console.log("newRouteForm1");

            if (this.routenameError) {
                alert('Please correct the errors before submitting.');
                return;
            }

            this.checkRoutename(this.routename)

            if (this.routenameError){
                console.log("Form Bug");
                alert('Please correct the errors before submitting.');
                return;
            }

            fetch('/api/', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    view: "newRoute",
                    userid: this.user.userid,
                    routename: this.routename
                })
            })
            // .then(response => response.text()) // Récupérer le texte brut pour le débogage
            // .then(text => {
            //     console.log('Response Text:', text); // Affiche la réponse brute
            //     return JSON.parse(text); // Convertir en JSON si nécessaire
            // })
            .then(response => response.json())
            .then(data => {
                //console.log(data);
                if (data.status === 'success') {
                    // Utilisateur connecté
                    this.rooted(data.routedata)
                } else {
                    this.routenameError = data.message;
                }
            })
            .catch(error => console.error('Error:', error));
        },

        toggleEdit(routeId) {
            this.editingRouteId = this.editingRouteId === routeId ? null : routeId;
        },

        updateRoute(route) {
            // Envoyer une requête pour mettre à jour la route
            fetch('/api/', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    view: "updateroute",
                    userid: this.user.userid,
                    routeid: route.routeid,
                    routename: route.routename,
                    routerem: route.routerem,
                    routestatus: route.routestatus,
                    telegram: route.routetelegram,
                    routemode: route.routemode,
                    routeclosed: route.routeclosed,
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
                    console.log('Route updated successfully');
                } else {
                    console.error('Error updating route:', data.message);
                }
            })
            .catch(error => console.error('Error:', error));
        },

        toggleConnect(routeid) {
            // Envoyer une requête pour mettre à jour la route
            console.log("connect",routeid);
            fetch('/api/', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    view: "routeconnect",
                    userid: this.user.userid,
                    routeid: routeid,
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
                    console.log('New connexion');
                    this.updateStatus(data.user);
                    //window.location.href = `/routes/`;
                } else {
                    console.error('Error updating route:', data.message);
                }
            })
            .catch(error => console.error('Error:', error));
        },

        route_actions(routeid, action, message){
            if (!confirm(' Do you really to ' + message.toLowerCase()) ) {
                    return;
            }

            fetch('/api/', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    view: "routeAction",
                    action: action,
                    userid: this.user.userid,
                    routeid: routeid,
                })
            })
            // .then(response => response.text()) // Récupérer le texte brut pour le débogage
            // .then(text => {
            //     console.log('Response Text:', text); // Affiche la réponse brute
            //     return JSON.parse(text); // Convertir en JSON si nécessaire
            // })
            .then(response => response.json())
            .then(data => {
                this.actionError = data.message;
                if (data.status === 'success') {
                    return true;
                } else {
                    return false;
                }
            })
            .catch(error => {
                alert('An error occurred during action.');
            });
        },

        handleGPXUpload(routeid) {
            return (event) => {
                const file = event.target.files[0];
                if (!file || file.type !== 'application/gpx+xml') {
                    this.gpxError = 'Please upload a valid GPX file.';
                    return;
                }
                this.gpxError = 'Uploading…';

                const formData = new FormData();
                formData.append('view', 'gpxupload');
                formData.append('userid', this.user.userid);
                formData.append('routeid', routeid);
                formData.append('gpxfile', file);

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
                        this.gpxError = 'Distance: ' + data.gpx.total_km + "m Up:" + data.gpx.total_dev + "m Points:" + data.gpx.total_points + " Tracks:" + data.gpx.total_tracks;
                        return true;
                    } else {
                        this.gpxError = 'Upload failed: ' + data.message;
                        return false;
                    }
                })
                .catch(error => {
                    this.uploading = false;
                    console.error('Error:', error);
                    alert('An error occurred during upload.');
                });
            }
        },

        handlePhotoUpload(routeid) {
            return (event) => {
                const file = event.target.files[0];
                if (!file || file.type !== 'image/jpeg') {
                    this.photoError = "Please upload a JPEG file.";
                    //this.selectedPhoto = null;
                    this.photoPreview = null;
                    return;
                }

                this.uploading = true;

                this.photoError = '';
                //this.selectedPhoto = file;

                const formData = new FormData();
                formData.append('view', 'routephoto');
                formData.append('userid', this.user.userid);
                formData.append('routeid', routeid);
                formData.append('photofile', file);

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
                    this.uploading = false;
                    if (data.status === 'success') {
                        this.photoPreview = URL.createObjectURL(file);
                        return true;
                    } else {
                        this.photoError = "Please upload a JPEG file.";
                        return false;
                    }
                })
                .catch(error => {
                    this.uploading = false;
                    console.error('Error:', error);
                    alert('An error occurred during upload.');
                });
            }
        },

        updateStatus(user){
            localStorage.setItem('user', JSON.stringify(user));
            this.user = user;
            Alpine.store('headerActions').user = user;
            Alpine.store('headerActions').init(true);
        },

        rooted(routedata){
            console.log('Utilisateur connecté:', routedata);
            this.user.userroute = routedata.routeid;
            for (const key in routedata) {
                this.user[key] = routedata[key];
            }
            localStorage.setItem('user', JSON.stringify(this.user));
            window.location.href = `/routes/`
        },

        copyToClipboard(text){
            navigator.clipboard.writeText(text).then(() => {
                alert('Link copied to clipboard!');
            }).catch(err => {
                console.error('Could not copy text:', err);
            });
        },

        loadTelegramChannels() {
            console.log("loadTelegram");
            if(!this.telegramConnected)
                return false;
            fetch('/api/', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    view: "getUserChannels"
                })
            })
            .then(response => response.json())
            .then(data => {
                console.log(data);
                if (data.status === 'success') {
                    this.telegramChannels = data.channels;
                    console.log(this.telegramChannels);
                }
            });
        },

    }));
});
</script>

