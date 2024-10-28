
<!-- Section Top -->
<header x-data="headerComponent()">

    <div id="geogram"><a href="/"><img src="/images/geogram-logo-2.svg" alt="Geogram"></a></div>

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
                <a href="#" @click.prevent="login">Log in/Sign in</a>
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

        init(reset=false) {
            console.log("Initializing header");

            this.user = this.getUserFromLocalStorage();
            this.isLoggedIn = this.user !== null;
            if(this.isLoggedIn){
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
            //console.log(this.user);
            //console.log(this.route);

            // Enregistrer les fonctions dans le store
            Alpine.store('headerActions', {
                user: this.user,
                route: this.route,
                isLoggedIn: this.isLoggedIn,
                isOnRoute: this.isOnRoute,
            });

            // Ajouter init au store après l'initialisation
            Alpine.store('headerActions').init = this.init.bind(this);

        },
        
        getUserFromLocalStorage() {
            const user = localStorage.getItem('user');
            return user ? JSON.parse(user) : null;
        },

        get userIconStyle() {
            console.log("iconStyle");
            $style = this.user.userphoto ? `background-image: url('/userdata/users/${this.user.userid}/photo.jpeg'); width: 34px; height: 34px; border: 2px solid white; background-size: cover;`
                : `background-color: ${this.user.usercolor}; width: 34px; height: 34px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 14px;`;
            return $style;
        },

        userpage() {
            window.location.href = `/login`;
        },

        logout() {
            // Logique de déconnexion
            localStorage.removeItem('user');
            this.isLoggedIn = false;
            window.location.href = `/`;
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