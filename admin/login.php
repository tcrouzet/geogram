<?php
html_header( $group." Map" );
?>

<div x-data="app()" id="alpine">
    <!-- Section Top -->
    <header>
    </header>

    <!-- Section Content -->
    <main>
        <div id="login">
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
    </main>

    <!-- Section Bottom -->
    <footer>
    </footer>
</div>

<?php
html_footer();
?>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('app', () => ({

        // Variables locales pour la gestion de l'authentification
        email: '',
        password: '',
        emailError: '',
        passwordError: '',
        loading: false,

        validateEmail() {
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(this.email)) {
                this.emailError = 'Please enter a valid email address.';
            } else {
                this.emailError = '';
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
                    console.log('Utilisateur connecté:', data.user);
                } else if (data.status === 'not_found') {
                    if (confirm(data.message)) {
                        this.createUser(data);
                    }
                } else {
                    this.passwordError = data.message;
                }
            })
            .catch(error => console.error('Error:', error));
        },

    
        createUser(data){
            console.log("new user", data);

            const formData = new URLSearchParams();
            formData.append('view', 'createuser');
            formData.append('email', data.email);
            formData.append('password', data.password);

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
        //    .then(text => {
        //         console.log("Raw Response Text:", text);
        //         return JSON.parse(text);
        //     })
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
            console.log('Utilisateur connecté:', userdata.username);
            localStorage.setItem('user', userdata);
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
        }
    }));
});
</script>

