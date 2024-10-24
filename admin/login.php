<?php
html_header( "Geogram login" );
?>

<div id="page">

    <?php include 'header.php'; ?>

    <!-- Section Content -->
    <main x-data="loginComponent()" >

        <template x-if="!isLoggedIn">

            <div id="login" class="loginwidth">

                <input type="hidden" x-model="formType" value="login"> 

                <h1>Welcome</h1>
                <p>Log in/Sign in to Geogram.</p>

                <button class="btn btn-google" @click="loginWithGoogle()" x-bind:disabled="loading">
                    Continue with Google
                </button>

                <button class="btn btn-facebook" @click="loginWithFacebook()" x-bind:disabled="loading">
                    Continue with Facebook
                </button>
        
                <div class="divider">OR</div>

                <input type="email" placeholder="Email" class="input-field" x-model="email" required  @input="validateEmail">
                <div x-show="emailError" class="error-message" x-text="emailError"></div>

                <input type="password"
                    placeholder="Password"
                    class="input-field"
                    x-model="password"
                    required
                    minlength="8"
                    maxlength="20"
                    @input="checkPasswordLength">
                <div x-show="passwordError" class="error-message" x-text="passwordError"></div>

                <button class="btn btn-submit" type="submit" @click="loginWithForm()" x-bind:disabled="loading">
                    Continue
                </button>

            </div>
        </template>

        <template x-if="isLoggedIn">
            <div id="login" class="userwidth">

                <input type="hidden" x-model="formType" value="update"> 

                <div class="divider">User</div>

                <div class="input-group">
                    <label>Email:</label>
                    <input type="email" placeholder="Email" class="input-field" x-model="email" required @input="validateEmail">
                </div>
                <div x-show="emailError" class="error-message" x-text="emailError"></div>

                <div class="input-group">
                    <label>User name:</label>
                    <input type="text" placeholder="User name" class="input-field" x-model="username" required  @input="checkUsername">
                </div>
                <div x-show="usernameError" class="error-message" x-text="usernameError"></div>

                <div class="input-group">
                    <label>New password:</label>
                    <input type="password"
                        placeholder="Password"
                        class="input-field"
                        x-model="password"
                        required
                        minlength="8"
                        maxlength="20"
                        @input="checkPasswordLength">
                </div>
                <div x-show="passwordError" class="error-message" x-text="passwordError"></div>

                <!-- Champ pour uploader l'image -->
                <div class="input-group">
                    <label>Profile Image (JPEG only):</label>
                    <input type="file" @change="handleFileUpload" x-ref="fileInput" accept="image/jpeg" class="input-field">
                    <div x-show="fileError" class="error-message" x-text="fileError"></div>
                </div>

                <!-- Prévisualisation de l'image (optionnel) -->
                <div class="input-group" x-show="previewImage">
                    <label>Image Preview:</label>
                    <img :src="previewImage" alt="Image Preview" class="image-preview" style="max-width: 200px; max-height: 200px;">
                </div>

                <button class="btn btn-submit" type="submit" @click="loginWithForm()" x-bind:disabled="loading">
                    Continue
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
    Alpine.data('loginComponent', () => ({

        // Variables locales pour la gestion de l'authentification
        user: null,
        isLoggedIn: false,
        email: '',
        username: '',
        password: '',
        emailError: '',
        usernameError: '',
        fileError: '',
        previewImage: null,
        selectedFile: null,
        passwordError: '',
        formType: '',
        loading: false,

        init(){
            this.isLoggedIn = Alpine.store('headerActions').isLoggedIn;
            if(this.isLoggedIn){
                this.user = Alpine.store('headerActions').user;
                this.email = this.user.useremail;
                this.username = this.user.username;
            }
        },

        validateEmail() {
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(this.email)) {
                this.emailError = 'Please enter a valid email address.';
            } else {
                this.emailError = '';
            }
        },

        checkUsername() {
            if (this.username.length < 2) {
                this.usernameError = 'Unsername must be at least 2 characters.';
            } else if (this.username.length > 30) {
                this.usernameError = 'Username must not exceed 30 characters.';
            } else {
                this.usernameError = '';
            }
        },

        checkPasswordLength() {
            if (this.password.length < 8) {
                this.passwordError = 'Password must be at least 8 characters.';
            } else if (this.password.length > 20) {
                this.passwordError = 'Password must not exceed 20 characters.';
            } else {
                this.passwordError = '';
            }
        },

        // Fonction pour gérer la connexion via le formulaire
        loginWithForm() {
            if (this.emailError || this.passwordError) {
                alert('Please correct the errors before submitting.');
                return;
            }

            this.validateEmail(this.email);
            this.checkPasswordLength(this.password)

            if (this.emailError!='' || this.passwordError!=''){
                console.log("loginWithForm Bug");
                return;
            }
            console.log("loginWithForm OK");

            const formData = new URLSearchParams();
            formData.append('view', 'login');
            formData.append('email', this.email);
            formData.append('password', this.password);
            formData.append('formType', this.formType);

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
                //return response.text(); // testing
                return response.json();
            })
            .then(text => {
                console.log("Raw Response Text:", text);
                return JSON.parse(text);
            })
            .then(data => {
                //console.log(data);
                if (data.status === 'success') {
                    // Utilisateur connecté
                    this.connected(data.userdata)
                } else if (data.status === 'not_found') {
                    if (confirm(data.message)) {
                        this.createUser();
                    }
                } else {
                    this.passwordError = data.message;
                }
            })
            .catch(error => console.error('Error:', error));
        },

    
        createUser(){
            console.log("new user");

            const formData = new URLSearchParams();
            formData.append('view', 'createuser');
            formData.append('email', this.email);
            formData.append('password', this.password);
            formData.append('formType', this.formType);

            fetch('backend.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: formData.toString()
            })
            .then(response => {
                console.log(response);
                if (!response.ok) {
                    throw new Error('Network response was not ok ' + response.statusText);
                }
                //return response.text(); // testing
                return response.json();
            })
           .then(text => {
                console.log("Raw Response Text:", text);
                return JSON.parse(text);
            })
            .then(data => {
                if (data.status === 'success') {
                    // Utilisateur connecté
                    this.connected(data.userdata)
                } else {
                    this.passwordError = data.message;
                }
            })
            .catch(error => console.error('ErrorFetch:', error));
        },


        connected(userdata){
            console.log('Utilisateur connecté:', userdata);
            localStorage.setItem('user', JSON.stringify(userdata));
            window.location.href = `/login/`
        },

        // Fonction pour gérer la connexion via Google
        loginWithGoogle() {
            this.loading = true;

            // Simuler la redirection vers l'API Google
            setTimeout(() => {
                console.log('Connexion via Google');
                alert('Redirection vers Google...');
                this.loading = false;
            }, 2000);
        },

        // Fonction pour gérer la connexion via Facebook
        loginWithFacebook() {
            this.loading = true;

            // Simuler la redirection vers l'API Facebook
            setTimeout(() => {
                console.log('Connexion via Facebook');
                alert('Redirection vers Facebook...');
                this.loading = false;
            }, 2000);
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
    }));
});
</script>

