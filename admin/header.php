
<!-- Section Top -->
<header x-data="headerComponent()">

    <div id="geogram"><a href="/">Geogram</a></div>

    <div  id="signin">

        <template x-if="isLoggedIn">
            <div class="user-menu">
                <button @click="menuOpen = !menuOpen" class="icon-button">
                    <div class="marker" :style="userIconStyle">
                        <template x-if="!user.img">
                            <span x-text="user.userinitials"></span>
                        </template>
                    </div>
                </button>
                <div x-show="menuOpen" @click.outside="menuOpen = false" class="dropdown-menu">
                    <a href="#" @click.prevent="newroute">Routes</a>
                    <a href="#" @click.prevent="userpage">Parameters</a>
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
        route: null,
        menuOpen: false,
        isLoggedIn: false,
        isOnRoute: false,

        init() {
            console.log("Initializing header");
            this.user = this.getUserFromLocalStorage();
            this.isLoggedIn = this.user !== null;
            console.log(this.user, this.isLoggedIn);
            if(this.isLoggedIn){
                this.route = this.getRouteFromLocalStorage;
                console.log("route:", this.route);
                isOnRoute = this.route !== null;
            }

            // Enregistrer les fonctions dans le store
            Alpine.store('headerActions', {
                user: this.user,
                isLoggedIn: this.isLoggedIn,
                route: this.route,
            });

            console.log("Store initialized:", Alpine.store('headerActions'));
        },
        
        getUserFromLocalStorage() {
            const user = localStorage.getItem('user');
            const userData = user ? JSON.parse(user) : null;
            console.log("getUserFromLocalStorage OK");
            return userData;
        },

        getRouteFromLocalStorage() {
            const route = localStorage.getItem('route');
            return route ? JSON.parse(route) : null;
        },

        get userIconStyle() {
            //console.log("iconStyle");
            $style = this.user.img ? `background-image: url('${this.user.img}'); width: 34px; height: 34px; border: 2px solid white; background-size: cover;`
                : `background-color: ${this.user.usercolor}; width: 34px; height: 34px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 14px;`;
            //console.log($style);
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