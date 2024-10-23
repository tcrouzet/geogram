
<!-- Section Top -->
<header x-data="headerComponent()">

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
                    <a href="#" @click.prevent="logout">Parameters</a>
                    <a href="#" @click.prevent="logout">Logout</a>
                </div>
            </div>
        </template>
        <template x-if="!isLoggedIn">
            <p>Sign in</p>
        </template>
    </div>
</header>
 

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('headerComponent', () => ({

        user: null,
        menuOpen: false,

        init() {
            console.log("Initializing...");
            this.user = this.getUserFromLocalStorage();
        },
        
        isLoggedIn() {
            return this.user !== null;
        },

        getUserFromLocalStorage() {
            const user = localStorage.getItem('user');
            const userData = user ? JSON.parse(user) : null;
            return userData;
        },

        get userIconStyle() {
            //console.log("iconStyle");
            $style = this.user.img ? `background-image: url('${this.user.img}'); width: 34px; height: 34px; border: 2px solid white; background-size: cover;`
                : `background-color: ${this.user.usercolor}; width: 34px; height: 34px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 14px;`;
            //console.log($style);
            return $style;
        },

        logout() {
            // Logique de déconnexion
            localStorage.setItem('user', null);
            this.isLoggedIn = false;
            window.location.href = `/`;
        }
    }));
});
</script>