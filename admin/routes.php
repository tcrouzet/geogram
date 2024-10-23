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

                <input type="hidden" x-model="formType" value="newroute"> 

                <div class="input-group">
                    <input type="text" placeholder="Route name" class="input-field" x-model="routename" required minlength="3" maxlength="30" @input="checkRoutename">
                </div>
                <div x-show="routenameError" class="error-message" x-text="routenameError"></div>

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
        routename: '',
        routenameError: '',
        gpxFile: null,
        gpxError: '',
        fileError: '',
        previewImage: null,
        selectedFile: null,
        formType: '',
        userid: null,
        loading: false,

        init(){
            console.log("Init routes");
            this.user = Alpine.store('headerActions').user;
            this.isLoggedIn = Alpine.store('headerActions').isLoggedIn;
            this.username = this.user.username;
            this.userid = this.user.userid;
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

            const formData = new URLSearchParams();
            formData.append('view', 'newroute');
            formData.append('routename', this.routename);
            formData.append('userid', this.userid);
            formData.append('formType', this.formType);

            console.log(formData);

            fetch('backend.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: formData.toString()
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok ' + response.statusText);
                }
                return response.json();
            })
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

        handleGPXUpload(event) {
            const file = event.target.files[0];
            if (!file){
                this.gpxError = 'No GPX File';
                return;
            }

            // Vérifier si le fichier est bien un JPEG
            if (file.type === 'application/gpx+xml' || file.type === 'text/xml') {
                this.gpxError = "Please upload a GPX file.";
                this.gpxFile = null;
                return;
            }

            // S'il est valide, stockez-le et préparez la prévisualisation
            this.gpxError = '';
            this.gpxFile = file;

            // Générer un URL de prévisualisation
            this.previewImage = URL.createObjectURL(file);
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
            localStorage.setItem('route', JSON.stringify(rootedata));
            window.location.href = `/routes/`
        },

    }));
});
</script>

