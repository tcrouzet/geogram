<?php
html_header( "Geogram routes" );
?>

<div id="page">

    <?php include 'header.php'; ?>

    <!-- Section Content -->
    <main x-data="routesComponent()" >

        <template x-if="!isLoggedIn">

            <div id="login" class="loginwidth">

                <h1>Sorry</h1>
                <p>You have to be logged.</p>

            </div>
        </template>

        <template x-if="isLoggedIn">
            <div id="login" class="userwidth">


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
                                
                                <label x-text="route.gpx === 0 ? 'New GPX' : 'Update GPX'"></label>
                                <input type="file" @change="handleGPXUpload(route.routeid)" accept=".gpx" class="input-field">
                                <div x-show="gpxError" class="error-message" x-text="gpxError"></div>
                                
                                <label>Status</label>
                                <select x-model="route.routestatus" @change="updateRoute(route)">
                                    <option value="2">Private</option>
                                    <option value="1">Open for spectators</option>
                                    <option value="0">Open for all</option>
                                </select>
                                <div x-show="route.routestatus > 0">
                                    <a :href="`/login/${route.routepublisherlink}`" x-text="'Invitation link for publishers'"></a>
                                    <button class="copy-button" @click="copyToClipboard(`/login/${route.routepublisherlink}`)">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                                <div x-show="route.routestatus > 1">
                                    <a :href="`/login/${route.routeviewerlink}`" x-text="'Invitation link for viewers'"></a>
                                    <button class="copy-button" @click="copyToClipboard(`/login/${route.routeviewerlink}`)">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
            
                                <label>Route image (JPEG only):</label>
                                <input type="file" @change="handlePhotoUpload(route.routeid)" accept="image/jpeg" class="input-field">
                                <div x-show="photoError" class="error-message" x-text="photoError"></div>
                                <div class="input-group" x-show="photoPreview || route.photopath">
                                    <img :src="photoPreview || route.photopath" alt="Image Preview" class="image-preview" style="max-width: 200px; max-height: 200px;">
                                </div>

                                <div class="divider">ACTIONS</div>
                                <button @click="route_actions(route.routeid,'delete_all_logs')">Delete all logs</button>
                                <div x-show="actionError" class="error-message" x-text="actionError"></div>

                                <div class="divider"></div>

                            </div>
                        </li>
                    </template>
                </ul>
    
                <div class="divider">ROUTE PLANNER</div>

                <div class="input-group">
                    <input type="text" placeholder="Route name" class="input-field" x-model="routename" required minlength="3" maxlength="30" @input="checkRoutename">
                </div>
                <div x-show="routenameError" class="error-message" x-text="routenameError"></div>

                <button class="btn btn-submit" type="submit" @click="newRouteForm()" x-bind:disabled="loading">
                    New route
                </button>

            </div>  
        </template>

    </main>

</div>

<?php
html_footer();
?>

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

        init(){
            console.log("Init routes");
            this.isLoggedIn = Alpine.store('headerActions').isLoggedIn;
            if(this.isLoggedIn){
                console.log("Logged routes");
                this.user = Alpine.store('headerActions').user;
                this.isOnRoute = Alpine.store('headerActions').isOnRoute;
                this.username = this.user.username;
                this.userid = this.user.userid;
                this.loadRoutes();
            }
        },

        loadRoutes() {
            console.log("loadRoutes");
            const formData = new URLSearchParams();
            formData.append('view', "getroutes");
            formData.append('userid', this.userid);

            // console.log(formData.toString());

            fetch('backend.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'Authorization': `Bearer ${this.user.usertoken}`
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

            fetch('backend.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'Authorization': `Bearer ${this.user.usertoken}`
                },
                body: new URLSearchParams({
                    view: "route",
                    userid: this.user.userid,
                    routename: this.routename
                })
            })
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
            fetch('backend.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'Authorization': `Bearer ${this.user.usertoken}`
                },
                body: new URLSearchParams({
                    view: "updateroute",
                    userid: this.user.userid,
                    routeid: route.routeid,
                    routename: route.routename,
                    routerem: route.routerem,
                    routestatus: route.routestatus
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
            fetch('backend.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'Authorization': `Bearer ${this.user.usertoken}`
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

        route_actions(routeid, action){
            if (!confirm(' Do you really to ' + action.replace(/\_/g, " ") + '?')) {
                    return;
            }

            fetch('backend.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'Authorization': `Bearer ${this.user.usertoken}`
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

    }));
});
</script>

