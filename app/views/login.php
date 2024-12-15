<?php include 'header.php'; ?>

<!-- Section Content -->
<main x-data="loginComponent()" >

    <div x-show="loading" class="loading-overlay">
        <div class="spinner"></div>
    </div>

    <template x-if="!isLoggedIn && !waitingMessage">

        <div id="login">

            <h1>Welcome</h1>
            <p style="text-align: center;">Connect to <?=GEONAME; ?></p>

            <button class="btn btn-google" @click="login('google-oauth2')" x-bind:disabled="loading">
                Continue with Google
            </button>

            <div class="divider">OR</div>

            <div>
                <input type="email" x-model="email" placeholder="Email" :required="isEmailLogin" class="userfield" @keyup.enter="login('email')"/>
                <div x-show="emailError" class="error-message" x-text="emailError"></div>
                <button class="btn btn-mail" @click="login('email')" x-bind:disabled="loading">
                    Continue with email
                </button>
            </div>

        </div>
    </template>

    <template x-if="isLoggedIn">
        <div id="login">
            You are logged
        </div>
    </template>

    <template x-if="waitingMessage">
        <div id="login">
            Check your email and click the link to complete your login.
        </div>
    </template>

</main>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('loginComponent', () => ({

        isLoggedIn: false,
        loading: false,
        isEmailLogin: false,
        emailError: '',
        email: '',
        waitingMessage: false,
    
        init(){
            log("loginInit");
            this.isLoggedIn = Alpine.store('headerActions').isLoggedIn;

            if(!this.isLoggedIn){
                log("No logged");
                const urlParams = new URLSearchParams(window.location.search);
                if (urlParams.get('waiting') === '1') {
                    log("Waiting");
                    this.waitingMessage = true;
                }
            }
        },

        login(provider) {
            this.loading = true;
            this.isEmailLogin = provider === 'email';

            // Récupérer les paramètres d'URL
            const urlParams = new URLSearchParams(window.location.search);
            const link = urlParams.get('link');
            const telegram = urlParams.get('telegram');

            const formData = new FormData();
            formData.append('provider', provider);
            if (link) formData.append('link', link);
            if (telegram) formData.append('telegram', telegram);
            if (this.isEmailLogin) {
                console.log(this.email);

                if (!this.checkEmailsername(this.email)) {
                    console.log("Bad email");
                    this.emailError = "Bad email";
                    this.loading = false;
                    return false;
                }

                formData.append('email', this.email);
                formData.append('view', 'loginEmail');
            }else{
                formData.append('view', 'loginSocial');
            }
            
            fetch('/api/', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    this.connected(data.user);
                } else if (data.status === 'redirect' && data.url) {
                    window.location.href = data.url;
                } else if (data.status === 'error') {
                    console.error('Login error:', data.message);
                }
            })
            .catch(error => {
                alert('Login error:' + error);
            });
        },

        checkEmailsername(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        },

        connected(userdata){
            log('Utilisateur connecté:', userdata);
            localStorage.setItem('user', JSON.stringify(userdata));
            window.location.href = `/`;
        },

    }));
});
</script>

