<!-- Section Top -->
<header x-data="headerComponent()">

    <div class="header-top">

        <div id="geogram"><a href="/"><img src="<?= LOGO ?>" alt="Geogram"></a></div>

        <div  id="signin">

            <div class="share-button">
                <button class="share-btn" @click="showShareDialog()">
                    <i class="fas fa-up-right-from-square"></i>
                <span>Share</span>
                </button>
            </div>

            <template x-if="isLoggedIn">
                <div class="user-menu">
                    <button @click="menuOpen = !menuOpen" class="icon-button">
                        <div class="marker markerS" :style="userIconStyle">
                            <template x-if="!user.userphoto">
                                <span x-text="user.userinitials"></span>
                            </template>
                        </div>
                    </button>
                    <div x-show="menuOpen" @click.outside="menuOpen = false" class="dropdown-menu">
                        <a href="#" @click.prevent="userpage">Profil</a>
                        <a href="#" @click.prevent="newroute">Routes</a>
                        <a href="#" @click.prevent="help">Help</a>
                        <a href="#" @click.prevent="contact">Contact</a>
                        <a href="#" @click.prevent="donate">Donate</a>
                        <a href="#" @click.prevent="logout">Logout</a>
                    </div>
                </div>
            </template>

            <template x-if="!isLoggedIn">
                <img src="/assets/img/sign-in.svg?2" @click="login" class="marker markerS" alt="Sign in">
            </template>
        </div>

    </div>

    <template x-if="title">
        <div id="routename" x-html="title"></div>
    </template>

</header>
 

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('headerComponent', () => ({

        user: null,
        route: <?= json_encode($route) ?>,
        page: <?= json_encode($page) ?>,
        menuOpen: false,
        isLoggedIn: false,
        isOnRoute: false,
        storyUser: <?= json_encode($userid) ?>,
        component: 'splash',
        title: '',

        async init(reset=false) {
            console.log("***Initializing header");

            this.initStore();
            
            const urlParams = new URLSearchParams(window.location.search);
            log(urlParams);
            if (urlParams.get('login') === 'success') {
                this.user = await this.checkAuthStatus();
            } else if (urlParams.get('login') === 'token') {
                this.user = await this.checkAuthToken();
            } else {
                this.user = this.getUserFromLocalStorage();
            }
            // console.log("***Initializing header ended");

            this.isLoggedIn = this.user !== null && this.user !== undefined;
            if(this.isLoggedIn) {
                console.log("***logged");
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

            this.initMode();
            this.initTitle();

            this.initStore(true);
            Alpine.store('headerActions').init = this.init.bind(this);
            Alpine.store('headerActions').initTitle = this.initTitle.bind(this);
            log("initilalized");
        },

        initTitle(params = {}){
            log(params);
            let title = '';
            if (this.route && this.route.routename){
                title += `<a href="${this.route.routeslug}">${this.route.routename}</a>`;
            }
            if(params && params.story){
                title += ` &raquo; <a href="${this.route.routeslug}/${params.story}">${params.story}</a>`;
                if(params.storyUserName && params.storyUserName){
                    title += ` &raquo; <a href="${this.route.routeslug}/${params.story}/${params.storyUser}">${params.storyUserName}</a>`;
                }
            }
            log(title);
            this.$nextTick(() => {
                this.title = title;
            });
        },

        initMode(){
            if (!this.route) {
                this.component = 'splash';
            } else if (this.route.routestatus > 1 && !(this.isLoggedIn && this.routeid == this.userroute)) {
                this.component = 'error';
            } else if (this.page == 'Story'){
                this.component = 'story';
            } else if (this.page == 'List'){
                this.component = 'list';
            } else {
                this.component = 'map';
            }
            log(this.component);
        },

        initStore(ended=false){
            // console.log("***Init store");
            Alpine.store('headerActions', {
                user: this.user,
                route: this.route,
                isLoggedIn: this.isLoggedIn,
                isOnRoute: this.isOnRoute,
                storyUser: this.storyUser,
                component: this.component,
                init: null,
                initTitle: null,
                ended: ended,
            });
        },

        async checkAuthStatus() {
            log();
            const data = await apiService.call('getSession', {});
            if (data.status == 'success') {
                localStorage.setItem('user', JSON.stringify(data.user));
                return data.user;
            }
        },

        async checkAuthToken() {
            log();
            const urlParams = new URLSearchParams(window.location.search);
            const token = urlParams.get('token');
            log(token);

            const data = await apiService.call('loginToken', {token: token});

            if (data.status == 'success') {
                localStorage.setItem('user', JSON.stringify(data.user));
                return data.user;
            }
        },

        getUserFromLocalStorage() {
            log();
            const user = localStorage.getItem('user');
            if (user) {
                try {
                    return JSON.parse(user);
                } catch (e) {
                    console.log('Error parsing JSON');
                    console.log(user);
                    return null;
                }
            }
            return null;
        },

        get userIconStyle() {
            $style = this.user.userphoto ? `background-image: url('/userdata/users/${this.user.userid}/photo.jpeg');`
                : `background-color: ${this.user.usercolor};`;
            return $style;
        },

        async logout() {
            const data = await apiService.call('logout', {});
            if (data.status == 'success') {
                localStorage.removeItem('user');
                window.location.href = '/';
            }
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

        help() {
            window.location.href = `/help`;
        },

        contact() {
            window.location.href = `/contact`;
        },

        donate() {
            window.location.href = `https://www.paypal.com/donate/?business=MCZTJGYPGXXCW&no_recurring=0&currency_code=EUR`;
        },

        showShareDialog() {
            // Logique pour afficher le dialogue de partage
            if (navigator.share) {
                // API Web Share si disponible
                navigator.share({
                    title: document.title,
                    url: window.location.href
                }).catch(console.error);
            } else {
                // Fallback : copier le lien dans le presse-papier
                navigator.clipboard.writeText(window.location.href)
                    .then(() => alert('Link copied to clipboard!'))
                    .catch(console.error);
            }
        },

    }));
});
</script>