<?php include 'header.php'; ?>

<main x-data="userComponent()" >

    <template x-if="!isLoggedIn">

        <div id="splash">

            <h1>Welcome</h1>
            <p>Connect to Geogram.</p>

        </div>
    </template>

    <template x-if="isLoggedIn">
        <div id="splash">

            <label>Email</label>
            <input type="email" placeholder="Email" class="input-field" x-model="user.useremail" disabled>

            <label>User name</label>
            <input type="text" placeholder="User name" class="input-field" x-model="user.username" required  minlength="2" maxlength="30" @change="updateUser">
            <div x-show="usernameError" class="error-message" x-text="usernameError"></div>

            <label>Profile Image (JPEG only)</label>
            <input type="file" @change="userPhotoUpload()" accept="image/jpeg" class="input-field">
            <div x-show="photoError" class="error-message" x-text="photoError"></div>
            <div class="input-group" x-show="photoPreview || user.photopath">
                <img :src="photoPreview || user.photopath" alt="Image Preview" class="image-preview" style="max-width: 200px; max-height: 200px;">
            </div>

            <div class="divider">ACTIONS</div>
            <div id="actions">
                <button @click="user_actions(user.userid,'purgeuser',$el.textContent)">Delete all my logs on all routes</button>

                <template x-if="user.userroute > 0">
                    <button @click="user_actions(user.userid, 'purgeuser_route', $el.textContent, user.userroute)">Delete my logs only on current route</button>
                </template>
            </div>
            <div x-show="actionError" class="error-message" x-text="actionError"></div>

            <div class="divider">TELEGRAM (optional)</div>
            <div id="telegram-section">
                <template x-if="!telegramConnected">
                <div>
                    <script 
                        async 
                        src="https://telegram.org/js/telegram-widget.js?22"
                        data-telegram-login="<?= TELEGRAM_BOT ?>"
                        data-size="large"
                        data-auth-url="<?= TELEGRAM_AUTH ?>"
                        data-request-access="write"
                    ></script>
                </div>
                </template>
                <template x-if="telegramConnected">
                    <div>
                        <button @click="disconnectTelegram">Disconnect Telegram</button>
                    </div>
                </template>
            </div>

            <div class="divider">USER DATA</div>
            <ul>
                <li><strong>User ID:</strong> <span x-text="user.userid"></span></li>
                <li><strong>User mail:</strong> <span x-text="user.useremail"></span></li>
                <li><strong>User token:</strong> <span x-text="user.usertoken"></span></li>
                <li><strong>User Route:</strong> <span x-text="user.routename"></span></li>
                <li><strong>User Route ID:</strong> <span x-text="user.userroute"></span></li>
                <li><strong>Route Status:</strong> <span x-text="getRouteStatusDescription(user.routestatus)"></span></li>
                <li><strong>Route User Status:</strong> <span x-text="getConStatusDescription(user.constatus)"></span></li>
                <li><strong>Telegram ID:</strong> <span x-text="user.usertelegram"></span></li>
            </ul>
        </div>  
    </template>

</main>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('userComponent', () => ({

        // Variables locales pour la gestion de l'authentification
        user: null,
        isLoggedIn: false,
        email: '',
        username: '',
        emailError: '',
        usernameError: '',
        fileError: '',
        previewImage: null,
        selectedFile: null,
        loading: false,
        photoError: '',
        photoPreview: null,
        selectedPhoto: null,
        actionError: '',
        //telegram
        telegramConnected: false,

        async init(){
            log();
            await initService.initComponent(this);

            this.isLoggedIn = Alpine.store('headerActions').isLoggedIn;
            if(!this.isLoggedIn)
                window.location.href = "/login";
            else{
                this.user = Alpine.store('headerActions').user;
                this.email = this.user.useremail;
                this.username = this.user.username;
                if(this.user.usertelegram){
                    log("Telegram connected");
                    this.telegramConnected = true;
                }
            }
            log("userInit ended");
        },

        checkUsername() {
            if (this.username.length < 2) {
                this.usernameError = 'Unsername must be at least 2 characters.';
            } else if (this.username.length > 30) {
                this.usernameError = 'Username must not exceed 30 characters.';
            } else {
                this.usernameError = '';
            }
        },

        async updateUser() {
            log();
            const data = await apiService.call('updateuser', {
                username: this.user.username
            });
            if (data.status == 'success') {
                log('User updated');
                this.user.username = data.user.username;
                localStorage.setItem('user', JSON.stringify(this.user));
            }
        },

        userPhotoUpload() {
            return (event) => {
                console.log("photoUpload");
                const file = event.target.files[0];
                if (!file || file.type !== 'image/jpeg') {
                    this.photoError = "Please upload a JPEG file.";
                    this.photoPreview = null;
                    return;
                }

                this.uploading = true;

                this.photoError = '';

                const formData = new FormData();
                formData.append('view', 'userphoto');
                formData.append('photofile', file);

                fetch('/api/', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    this.uploading = false;
                    if (data.status === 'success') {
                        this.photoPreview = URL.createObjectURL(file);
                        return true;
                    } else {
                        this.photoError = "Please upload a JPEG file.";
                        return false;
                    }
                })
                .catch(error => {
                    this.uploading = false;
                    console.error('Error:', error);
                    alert('An error occurred during upload.');
                });
            }
        },


        user_actions(userid, action, message, routeid = null){
            if (!confirm(' Do you really to ' + message.toLowerCase()) ) {
                    return;
            }

            // Construire les paramètres de la requête
            const params = {
                view: "userAction",
                action: action,
                userid: userid
            };
            
            // Ajouter routeid si présent
            if (routeid) {
                params.routeid = routeid;
            }

            fetch('/api/', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams(params)
            })
            .then(response => response.json())
            .then(data => {
                this.actionError = data.message;
                if (data.status === 'success') {
                    return true;
                } else {
                    return false;
                }
            })
            .catch(error => {
                alert('An error occurred during action.');
            });
        },

        updateHeader(userdata){
            localStorage.setItem('user', JSON.stringify(userdata));
            Alpine.store('headerActions').init();
        },

        disconnectTelegram() {
            if (confirm('Are you sure you want to disconnect Telegram?')) {
                fetch('/api/', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        view: "telegramDisconnect"
                    })
                });
            }
        },

        getRouteStatusDescription(status) {
            switch(status) {
                case 0:
                    return "Public (everyone can view, must be logged in to post - 0)";
                case 1:
                    return "Visible (everyone can view, must be invited to post - 1)";
                case 2:
                    return "Private (must be invited to view and post - 2)";
                default:
                    return "Unknown";
            }
        },

        getConStatusDescription(status) {
            switch(status) {
                case 3:
                    return "Creator (3)";
                case 2:
                    return "Invited to publish (2)";
                case 1:
                    return "Invited to view (1)";
                case 0:
                    return "Connected only (0)";
                default:
                    return "Unknown status";
            }
        },

    }));
});
</script>