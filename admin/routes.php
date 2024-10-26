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
                            <button @click="toggleEdit(route.routeid)">Edit</button>
                            
                            <div x-show="route.routeid === editingRouteId">
                                <!-- Bloc d'édition -->
                                <br/><label>Name</label><br/>
                                <input type="text" class="input-field" x-model="route.routename" required minlength="3" maxlength="30" @change="updateRoute(route)">
                                <label>Description</label><br/>
                                <input type="text" class="input-field" x-model="route.routerem" required minlength="30" maxlength="256" @change="updateRoute(route)">
                                <label x-text="route.gpx === 0 ? 'New GPX' : 'Update GPX'"></label><br/>
                                <input type="file" @change="handleGPXUpload(route.routeid)" accept=".gpx" class="input-field">
                                <div x-show="uploading" class="popup">Uploading...</div>
                                <label>Status</label><br/>
                                <select x-model="route.routestatus" @change="updateRoute(route)">
                                    <option value="0">Private</option>
                                    <option value="1">Open for spectators</option>
                                    <option value="2">Open for all</option>
                                </select>
                                <div x-show="route.routestatus == 0 || route.routestatus == 1">
                                    <a href="#" x-text="'Invitation link for publishers: ' + route.routepublisherlink"></a><br/>
                                </div>
                                <div x-show="route.routestatus == 0">
                                    <a href="#" x-text="'Invitation link for viewers: ' + route.routeviewerlink"></a><br/>
                                </div>                                
                                <!-- <div class="input-group">
                                <label>GPX:</label>
                                <input type="file" @change="handleGPXUpload" x-ref="fileInput" accept="file/gpx" class="input-field">
                                <div x-show="gpxError" class="error-message" x-text="gpxError"></div>
                                </div> -->

                                <!-- Champ pour uploader l'image -->
                                <!-- <div class="input-group">
                                    <label>Route image (JPEG only):</label>
                                    <input type="file" @change="handleFileUpload" x-ref="fileInput" accept="image/jpeg" class="input-field">
                                    <div x-show="fileError" class="error-message" x-text="fileError"></div>
                                </div> -->

                                <!-- Prévisualisation de l'image (optionnel) -->
                                <!-- <div class="input-group" x-show="previewImage">
                                    <label>Image Preview:</label>
                                    <img :src="previewImage" alt="Image Preview" class="image-preview" style="max-width: 200px; max-height: 200px;">
                                </div> -->
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

    <?php include 'footer.php'; ?>

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
        fileError: '',
        previewImage: null,
        selectedFile: null,
        userid: null,
        loading: false,
        uploading: false,

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
                    'Authorization': `Bearer ${this.user.auth_token}`
                },
                body: formData.toString()
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok ' + response.statusText);
                }
                return response.json();
                //return response.text(); // testing
            })
            // .then(text => {
            //     console.log("Raw Response Text:", text); // Vérifiez ici le texte brut
            //     return JSON.parse(text); // Parse manuellement pour détecter les erreurs
            // })
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
                    'Authorization': `Bearer ${this.user.auth_token}`
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
                    'Authorization': `Bearer ${this.user.auth_token}`
                },
                body: new URLSearchParams({
                    view: "updateroute",
                    userid: this.user.userid,
                    routeid: route.routeid,
                    routename: route.routename,
                    routerem: route.routerem
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

        handleGPXUpload(routeid) {
            return (event) => {
                const file = event.target.files[0];
                if (!file || file.type !== 'application/gpx+xml') {
                    alert('Please upload a valid GPX file.');
                    return;
                }

                this.uploading = true;

                const formData = new FormData();
                formData.append('view', 'gpxupload');
                formData.append('userid', this.user.userid);
                formData.append('routeid', routeid);
                formData.append('gpxfile', file);

                fetch('backend.php', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${this.user.auth_token}`
                    },
                    body: formData
                })
                .then(response => response.text()) // Récupérer le texte brut pour le débogage
                .then(text => {
                    console.log('Response Text:', text); // Affiche la réponse brute
                    return JSON.parse(text); // Convertir en JSON si nécessaire
                })
                .then(response => response.json())
                .then(data => {
                    this.uploading = false;
                    if (data.status === 'success') {
                        alert('Upload successful!');
                    } else {
                        alert('Upload failed: ' + data.message);
                    }
                })
                .catch(error => {
                    this.uploading = false;
                    console.error('Error:', error);
                    alert('An error occurred during upload.');
                });
            }
        },

        handleFileUpload(event) {
            const file = event.target.files[0];
            if (!file) return;

            // Vérifier si le fichier est bien un JPEG
            if (file.type !== 'image/jpeg') {
                this.fileError = "Please upload a JPEG file.";
                this.selectedFile = null;
                this.previewImage = null;
                return;
            }

            // S'il est valide, stockez-le et préparez la prévisualisation
            this.fileError = '';
            this.selectedFile = file;

            // Générer un URL de prévisualisation
            this.previewImage = URL.createObjectURL(file);
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

    }));
});
</script>

