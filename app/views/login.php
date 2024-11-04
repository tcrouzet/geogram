<div id="page">

    <?php include 'header.php'; ?>

    <!-- Section Content -->
    <main x-data="loginComponent()" >

        <template x-if="!isLoggedIn">

            <div id="login" class="loginwidth">

                <h1>Welcome</h1>
                <p style="text-align: center;">Connect to Geogram.</p>

                <button class="btn btn-google" @click="login('google-oauth2')" x-bind:disabled="loading">
                    Continue with Google
                </button>
        
                <button class="btn btn-mail" @click="login('Username-Password-Authentication')" x-bind:disabled="loading">
                    Continue with your mail
                </button>

            </div>
        </template>

        <template x-if="isLoggedIn">
            <div id="login" class="userwidth">
                You are logged
            </div>
        </template>

    </main>

</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('loginComponent', () => ({

        isLoggedIn: false,
        loading: false,
    
        init(){
            console.log("loginInit");
            this.isLoggedIn = Alpine.store('headerActions').isLoggedIn;
        },

        login(provider) {
            this.loading = true;
            const formData = new FormData();
            console.log("loginSocial");
            formData.append('view', 'loginSocial');
            formData.append('provider', provider);
            
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

        connected(userdata){
            console.log('Utilisateur connecté:', userdata);
            localStorage.setItem('user', JSON.stringify(userdata));
            window.location.href = `/`;
        },

    }));
});
</script>

