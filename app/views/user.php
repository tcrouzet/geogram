<div id="page">

    <?php include 'header.php'; ?>

    <!-- Section Content -->
    <main x-data="loginComponent()" >

        <template x-if="!isLoggedIn">

            <div id="login" class="loginwidth">

                <h1>Welcome</h1>
                <p>Log in/Sign in to Geogram.</p>

                <button class="btn btn-google" @click="loginWithGoogle()" x-bind:disabled="loading">
                    Continue with Google
                </button>

                <button class="btn btn-facebook" @click="loginWithFacebook()" x-bind:disabled="loading">
                    Continue with Facebook
                </button>
        
                <div class="divider">OR</div>

                <input type="email" placeholder="Email" class="input-field" x-model="email" required  @input="validateEmail">
                <div x-show="emailError" class="error-message" x-text="emailError"></div>

                <input type="password"
                    placeholder="Password"
                    class="input-field"
                    x-model="password"
                    required
                    minlength="8"
                    maxlength="20"
                    @input="checkPasswordLength">
                <div x-show="passwordError" class="error-message" x-text="passwordError"></div>

                <button class="btn btn-submit" type="submit" @click="loginWithForm()" x-bind:disabled="loading">
                    Continue
                </button>

            </div>
        </template>

        <template x-if="isLoggedIn">
            <div id="login" class="userwidth">

                <div class="divider">User</div>

                <label>Email</label>
                <input type="email" placeholder="Email" class="input-field" x-model="user.useremail" required @input="validateEmail" @change="updateUser">
                <div x-show="emailError" class="error-message" x-text="emailError"></div>

                <label>User name</label>
                <input type="text" placeholder="User name" class="input-field" x-model="user.username" required  minlength="2" maxlength="30" @change="updateUser">
                <div x-show="usernameError" class="error-message" x-text="usernameError"></div>

                <label>Profile Image (JPEG only)</label>
                <input type="file" @change="userPhotoUpload()" accept="image/jpeg" class="input-field">
                <div x-show="photoError" class="error-message" x-text="photoError"></div>
                <div class="input-group" x-show="photoPreview || user.photopath">
                    <img :src="photoPreview || user.photopath" alt="Image Preview" class="image-preview" style="max-width: 200px; max-height: 200px;">
                </div>

                <div class="divider">Password</div>
                <label>New password</label>
                <input type="password"
                    placeholder="Password"
                    class="input-field"
                    x-model="password"
                    required
                    minlength="8"
                    maxlength="20"
                    @input="checkPasswordLength"  @change="updatePSW">
                <div x-show="passwordError" class="error-message" x-text="passwordError"></div>

                <div class="divider">ACTIONS</div>
                <div id="actions">
                    <button @click="user_actions(user.userid,'purgeuser',$el.textContent)">Delete all logs</button>
                </div>
                <div x-show="actionError" class="error-message" x-text="actionError"></div>


            </div>  
        </template>

    </main>

</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('loginComponent', () => ({

        // Variables locales pour la gestion de l'authentification
        user: null,
        isLoggedIn: false,
        email: '',
        username: '',
        password: '',
        emailError: '',
        usernameError: '',
        fileError: '',
        previewImage: null,
        selectedFile: null,
        passwordError: '',
        loading: false,
        photoError: '',
        photoPreview: null,
        selectedPhoto: null,
        actionError: '',

        init(){
            console.log("loginInit");
            this.isLoggedIn = Alpine.store('headerActions').isLoggedIn;
            if(this.isLoggedIn){
                this.user = Alpine.store('headerActions').user;
                this.email = this.user.useremail;
                this.username = this.user.username;
            }
        },

        validateEmail() {
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(this.email)) {
                this.emailError = 'Please enter a valid email address.';
            } else {
                this.emailError = '';
            }
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

        checkPasswordLength() {
            if (this.password.length < 8) {
                this.passwordError = 'Password must be at least 8 characters.';
            } else if (this.password.length > 20) {
                this.passwordError = 'Password must not exceed 20 characters.';
            } else {
                this.passwordError = '';
            }
        },

        // Fonction pour gérer la connexion via le formulaire
        loginWithForm() {
            if (this.emailError || this.passwordError) {
                alert('Please correct the errors before submitting.');
                return;
            }

            this.validateEmail(this.email);
            this.checkPasswordLength(this.password)

            if (this.emailError!='' || this.passwordError!=''){
                console.log("loginWithForm Bug");
                return;
            }
            console.log("loginWithForm OK");

            const formData = new URLSearchParams();
            formData.append('view', 'login');
            formData.append('email', this.email);
            formData.append('password', this.password);

            fetch('/api/', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: formData.toString()
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok ' + response.statusText);
                }
                return response.json();
            })
            .then(data => {
                //console.log(data);
                if (data.status === 'success') {
                    // Utilisateur connecté
                    this.connected(data.userdata)
                } else if (data.status === 'not_found') {
                    if (confirm(data.message)) {
                        this.createUser();
                    }
                } else {
                    this.passwordError = data.message;
                }
            })
            .catch(error => console.error('Error:', error));
        },

    
        createUser(){
            console.log("new user");

            const formData = new URLSearchParams();
            formData.append('view', 'createuser');
            formData.append('email', this.email);
            formData.append('password', this.password);

            fetch('/api/', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: formData.toString()
            })
            .then(response => {
                console.log(response);
                if (!response.ok) {
                    throw new Error('Network response was not ok ' + response.statusText);
                }
                //return response.text(); // testing
                return response.json();
            })
            // .then(text => {
            //     console.log("Raw Response Text:", text);
            //     return JSON.parse(text);
            // })
            .then(data => {
                if (data.status === 'success') {
                    // Utilisateur connecté
                    this.connected(data.userdata)
                } else {
                    this.passwordError = data.message;
                }
            })
            .catch(error => console.error('ErrorFetch:', error));
        },


        updateUser() {
            // Envoyer une requête pour mettre à jour la route
            fetch('/api/', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'Authorization': `Bearer ${this.user.usertoken}`
                },
                body: new URLSearchParams({
                    view: "updateuser",
                    userid: this.user.userid,
                    username: this.user.username,
                    useremail: this.user.useremail
                })
            })
            // .then(response => response.text()) // Récupérer le texte brut pour le débogage
            // .then(text => {
            //     console.log('Response Text:', text); // Affiche la réponse brute
            //     return JSON.parse(text); // Convertir en JSON si nécessaire
            // })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    console.log('User updated');
                    console.log()
                    this.user.username = data.user.username;
                    localStorage.setItem('user', JSON.stringify(this.user));
                } else {
                    console.error('Error updating user', data.message);
                }
            })
            .catch(error => console.error('Error:', error));
        },

        updatePSW(){
            alert("Not yet possible");
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
                formData.append('userid', this.user.userid);
                formData.append('photofile', file);

                fetch('/api/', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${this.user.usertoken}`
                    },
                    body: formData
                })
                // .then(response => response.text()) // Récupérer le texte brut pour le débogage
                // .then(text => {
                //     console.log('Response Text:', text); // Affiche la réponse brute
                //     return JSON.parse(text); // Convertir en JSON si nécessaire
                // })
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


        user_actions(userid, action, message){
            if (!confirm(' Do you really to ' + message.toLowerCase()) ) {
                    return;
            }

            fetch('/api/', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'Authorization': `Bearer ${this.user.usertoken}`
                },
                body: new URLSearchParams({
                    view: "userAction",
                    action: action,
                    userid: userid
                })
            })
            // .then(response => response.text()) // Récupérer le texte brut pour le débogage
            // .then(text => {
            //     console.log('Response Text:', text); // Affiche la réponse brute
            //     return JSON.parse(text); // Convertir en JSON si nécessaire
            // })
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


        connected(userdata){
            console.log('Utilisateur connecté:', userdata);
            localStorage.setItem('user', JSON.stringify(userdata));
            // if(userdata.routeid == userdata.userroute){
            //     window.location.href = `/` + userdata.routeslug;
            // }else{
            //     window.location.href = `/login/`
            // }
            window.location.href = `/`;
        },

        // Fonction pour gérer la connexion via Google
        loginWithGoogle() {
            this.loading = true;

            // Simuler la redirection vers l'API Google
            setTimeout(() => {
                console.log('Connexion via Google');
                alert('Redirection vers Google...');
                this.loading = false;
            }, 2000);
        },

        // Fonction pour gérer la connexion via Facebook
        loginWithFacebook() {
            this.loading = true;

            // Simuler la redirection vers l'API Facebook
            setTimeout(() => {
                console.log('Connexion via Facebook');
                alert('Redirection vers Facebook...');
                this.loading = false;
            }, 2000);
        },

    }));
});
</script>

