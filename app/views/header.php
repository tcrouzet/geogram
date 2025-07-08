<!-- Section Top -->
<div id="header" x-data="headerComponent()">

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

</div>
 

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('headerComponent', () => ({

        TOKEN_EXPIRY_SECONDS: <?= TOKEN_EXPIRATION ?>,
        user: null,
        route: <?= json_encode($route) ?>,
        page: <?= json_encode($page) ?>,
        menuOpen: false,
        isLoggedIn: false,
        isOnRoute: false,
        storyUser: <?= json_encode($UserStoryId) ?>,
        storyNowUser: <?= json_encode($UserStory) ?>,
        component: 'splash',
        title: '',

        async init(reset=false) {
            log("***Initializing header");
            log(this.page);
            log(this.storyUser);
            log(this.route);
            log("this.storyNowUser:");
            log(this.storyNowUser);
            log("User");
            log(this.user);
            log("End init vars");

            this.initStore();
            
            const urlParams = new URLSearchParams(window.location.search);
            log(urlParams);
            if (urlParams.get('login') === 'success') {
                this.user = await this.checkAuthStatus();
                if (!this.user){
                    localStorage.removeItem('user');
                    alert("Bad user");
                }
                reset = true;
                // Nettoyer l'URL pour éviter les re-exécutions
                window.history.replaceState({}, '', window.location.pathname);
            } else if (urlParams.get('login') === 'fail') {
                localStorage.removeItem('user');
                this.user = null;
                alert(urlParams.get('message'));
            } else if (urlParams.get('login') === 'token') {
                this.user = await this.checkAuthToken();
                if (!this.user){
                    localStorage.removeItem('user');
                    alert("Bad user");
                }
                reset = true;
                window.history.replaceState({}, '', window.location.pathname);
            } else {
                this.user = this.getUserFromLocalStorage();
            }

            this.isLoggedIn = !!this.user;
            if(this.isLoggedIn) {
                log("***logged");
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
            Alpine.store('headerActions').updateStatus = this.updateStatus.bind(this);
            log("initilalized");
        },

        initTitle(params = {}){
            log(params);

            if (Object.keys(params).length === 0 && this.storyNowUser){
                log("No params, using storyNowUser");
                params = {story: this.page, storyUserName: this.storyNowUser.username, storyUser: this.storyNowUser.userid};
                log(params);
            }

            let title = '';
            const base = '<?= BASE_URL ?>/';
            let link = ''
            let pageTitle = '<?= GEONAME ?>';
            if (this.route && this.route.routename){
                log("Route found");
                link += this.route.routeslug
                title += `<a href="${link}">${this.route.routename}</a>`;
                pageTitle += " - " + this.route.routename;

                if(params && params.story){
                    log("Story params:");
                    log(params.story);
                    //Path
                    link += "/" + params.story;
                    log(link);
                    if(params.story != "map" || (params.story == "map" && params.storyUserName)){
                        title += ` &raquo; <a href="${link}">${params.story}</a>`;
                        pageTitle += " - " + params.story;
                    }
                    //User
                    if(params.storyUserName && params.storyUser){
                        link += "/" + params.storyUser;
                        title += ` &raquo; <a href="${link}">${params.storyUserName}</a>`;
                        pageTitle += " - " + params.storyUserName;
                    }
                    // else if (this.storyNowUser && this.storyNowUser.username) {
                    //     link += "/" + this.storyNowUser.userid;
                    //     title += ` &raquo; <a href="${link}">${this.storyNowUser.username}</a>`;
                    //     pageTitle += " - " + this.storyNowUser.username;
                    // }
                }
            }
            let reallink = "/" + link;
            log(title);
            log(link);
            // log(reallink);
            // log("wlink:");
            // log(window.location.pathname);
            this.$nextTick(() => {
                if (title !== '' && reallink !== window.location.pathname) {
                    log("Changing link");
                    history.pushState({}, '', base + link);
                    document.title = pageTitle;
                }
                this.title = title;
            });
            log("Title initialized");
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
            return null;
        },

        async checkAuthToken() {
            log();
            const urlParams = new URLSearchParams(window.location.search);
            const token = urlParams.get('token');
            
            log("=== DEBUG TOKEN LOGIN ===");
            log("Token from URL:", token);

            const data = await apiService.call('loginToken', {token: token});

            if (data.status == 'success') {
                localStorage.setItem('user', JSON.stringify(data.user));
                return data.user;
            }
            return null;
        },

        getUserFromLocalStorage() {
            log();
            const user = localStorage.getItem('user');
            if (user) {
                try {
                    const userData = JSON.parse(user);

                    if (userData.usertoken && !this.isTokenValid(userData.usertoken)) {
                        log('Token expiré, suppression des données');
                        localStorage.removeItem('user');
                        return null;
                    }
                    
                    return userData;
                } catch (e) {
                    log('Error parsing JSON');
                    log(user);
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
            log();
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

        updateStatus(user){
            localStorage.setItem('user', JSON.stringify(user));
            Alpine.store('headerActions').user = user;
            this.init(true);
        },

        isTokenValid(token) {
            const parts = token.split('_');
            if (parts.length !== 3) return false;
            
            const tokenTimestamp = parseInt(parts[1]);
            const currentTime = Math.floor(Date.now() / 1000);
            
            return (currentTime - tokenTimestamp) < this.TOKEN_EXPIRY_SECONDS;
        },

    }));
});
</script>