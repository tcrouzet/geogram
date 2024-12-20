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

        user: null,
        isLoggedIn: false,
        loading: false,
        isEmailLogin: false,
        emailError: '',
        email: '',
        waitingMessage: false,
        link: '',
    
        async init(){
            log("loginInit");
            await initService.initComponent(this);

            this.isLoggedIn = Alpine.store('headerActions').isLoggedIn;

            if(!this.isLoggedIn){
                log("No logged");
                const urlParams = new URLSearchParams(window.location.search);
                if (urlParams.get('waiting') === '1') {
                    log("Waiting");
                    this.waitingMessage = true;
                }
            }else{
                const urlParams = new URLSearchParams(window.location.search);
                if (urlParams.get('link')) {
                    await this.toggleConnect(urlParams.get('link'));
                }
                window.location.href = '/';
            }
        },

        async login(provider) {
            try {

                this.loading = true;
                this.isEmailLogin = provider === 'email';

                // Récupérer les paramètres d'URL
                const urlParams = new URLSearchParams(window.location.search);
                let link = urlParams.get('link');
                if(link) link = encodeURIComponent(link);
                const telegram = urlParams.get('telegram');
                let data;

                if (this.isEmailLogin) {
                    log(this.email);

                    if (!this.checkEmailsername(this.email)) {
                        log("Bad email");
                        this.emailError = "Bad email";
                        this.loading = false;
                        return false;
                    }

                    data = await apiService.call('loginEmail', {
                        link: link,
                        telegram: telegram,
                        email: this.email,
                        provider: provider
                    });
                }else{
                    data = await apiService.call('loginSocial', {
                        link: link,
                        telegram: telegram,
                        provider: provider
                    });
                }
                
                if (data.status === 'success') {
                    this.connected(data.user);
                } else if (data.status === 'redirect' && data.url) {
                    window.location.href = data.url;
                } else if (data.status === 'error') {
                    console.error('Login error:', data.message);
                }
            } catch (error) {
                console.error('Unexpected error during login:', error);
            } finally {
                this.loading = false;
            }
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

        async toggleConnect(link) {
            log();
            const encodedLink = encodeURIComponent(link);
            const data = await apiService.call('routeconnect', {
                userid: this.user.userid,
                link: encodedLink,
            });
            if (data.status == 'success') {
                console.log('New connexion');
                Alpine.store('headerActions').updateStatus(data.user);
                this.user = data.user;
            }
        },

    }));
});
</script>

