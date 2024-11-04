<!-- Section Top -->
<header x-data="headerComponent()">

    <div id="geogram"><a href="/"><img src="/assets/img/geogram-logo.svg?1" alt="Geogram"></a></div>

    <div id="routename">
        <template x-if="route && route.routename">
            <span x-text="route.routename"></span>
        </template>
    </div>

    <div  id="signin">

        <template x-if="isLoggedIn">
            <div class="user-menu">
                <button @click="menuOpen = !menuOpen" class="icon-button">
                    <div class="marker" :style="userIconStyle">
                        <template x-if="!user.userphoto">
                            <span x-text="user.userinitials"></span>
                        </template>
                    </div>
                </button>
                <div x-show="menuOpen" @click.outside="menuOpen = false" class="dropdown-menu">
                    <a href="#" @click.prevent="userpage">Profil</a>
                    <a href="#" @click.prevent="newroute">Routes</a>
                    <a href="#" @click.prevent="logout">Logout</a>
                </div>
            </div>
        </template>

        <template x-if="!isLoggedIn">
            <div class="user-sign">
                <a href="#" @click.prevent="login">Connect</a>
            </div>
        </template>
    </div>
</header>
 

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('headerComponent', () => ({

        user: null,
        route: <?= json_encode($route) ?>,
        menuOpen: false,
        isLoggedIn: false,
        isOnRoute: false,

        async init(reset=false) {
            console.log("Initializing header");

            this.initStore();
            
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('login') === 'success') {
                this.user = await this.checkAuthStatus();
            } else {
                this.user = this.getUserFromLocalStorage();
            }
            console.log("Initializing header end");


            console.log("initialize");
            this.isLoggedIn = this.user !== null && this.user !== undefined;
            if(this.isLoggedIn) {
                console.log("logged");
                //console.log(this.user);
                this.isOnRoute = this.user.routeid > 0 ? true : false;
                if ((this.route === null || reset === true) && this.isOnRoute) {
                    this.route = {};
                    for (const [key, value] of Object.entries(this.user)) {
                        if (!key.startsWith('user')) {
                            this.route[key] = value;
                        }
                    }
                }
            }

            this.initStore(true);
            Alpine.store('headerActions').init = this.init.bind(this);
            console.log("Header initilalized");
        },

        initStore(ended=false){
            console.log("Init store");
            Alpine.store('headerActions', {
                user: this.user,
                route: this.route,
                isLoggedIn: this.isLoggedIn,
                isOnRoute: this.isOnRoute,
                ended: ended,
            });
        },

        async checkAuthStatus() {
            console.log("checkAuthStatus");
            const formData = new FormData();
            formData.append('view', 'getSession');
            
            return new Promise((resolve) => {
                fetch('/api/', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        console.log("GetSession OK");
                        localStorage.setItem('user', JSON.stringify(data.user));
                        console.log(data.user);
                        resolve(data.user);
                    } else {
                        console.log("No user data");
                        resolve(null);
                    }
                })
                .catch(error => {
                    console.error("Auth check failed:", error);
                    resolve(null);
                });
            });
        },

        getUserFromLocalStorage() {
            console.log("getUserFromLocalStorage");
            const user = localStorage.getItem('user');
            return user ? JSON.parse(user) : null;
        },

        get userIconStyle() {
            console.log("iconStyle");
            $style = this.user.userphoto ? `background-image: url('/userdata/users/${this.user.userid}/photo.jpeg'); width: 34px; height: 34px; border: 2px solid white; background-size: cover;`
                : `background-color: ${this.user.usercolor}; width: 34px; height: 34px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 14px;`;
            return $style;
        },

        logout() {
            const formData = new FormData();
            formData.append('view', 'logout');
            
            fetch('/api/', {
                method: 'POST',
                body: formData,
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Nettoyer le stockage local
                    console.log("Logout success");
                    localStorage.removeItem('user');
                    window.location.href = '/';
                }
            });
        },

        userpage() {
            window.location.href = `/user`;
        },

        login() {
            // Logique de déconnexion
            window.location.href = `/login`;
        },

        newroute() {
            window.location.href = `/routes`;
        },


    }));
});
</script>