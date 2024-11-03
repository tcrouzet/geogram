<div id="page">

    <?php include 'header.php'; ?>

    <!-- Section Content -->
    <main x-data="loginComponent()" >

        <template x-if="!isLoggedIn">

            <div id="login" class="loginwidth">

                <h1>Welcome</h1>
                <p>Log in/Sign in to Geogram.</p>


                <button class="btn btn-google" @click="loginWithSocial('google-oauth2')" x-bind:disabled="loading">
                    Continue with Google
                </button>

                <button class="btn btn-facebook" @click="loginWithSocial('facebook')" x-bind:disabled="loading">
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

                <button class="btn btn-submit" type="submit" @click="loginWithCredentials()" x-bind:disabled="loading">
                    Continue
                </button>

            </div>
        </template>

        <template x-if="isLoggedIn">
            <div id="login" class="userwidth">
                Unlog
            </div>
        </template>

    </main>

</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('loginComponent', () => ({

        // Variables locales pour la gestion de l'authentification
        email: '',
        password: '',
        user: null,
        isLoggedIn: false,
        loading: false,
        emailError: '',
        passwordError: '',
    
        init(){
            console.log("loginInit");
            this.isLoggedIn = Alpine.store('headerActions').isLoggedIn;
            if(this.isLoggedIn){
                this.user = Alpine.store('headerActions').user;
                this.email = this.user.useremail;
            }
        },

        loginWithCredentials() {
            const formData = new FormData();
            formData.append('view', 'login');
            formData.append('email', this.email);
            formData.append('password', this.password);
            
            fetch('/api/', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    this.user = data.userdata;
                }
            });
        },

        loginWithSocial(provider) {
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
                if (data.status === 'redirect' && data.url) {
                    window.location.href = data.url;
                } else if (data.status === 'error') {
                    console.error('Login error:', data.message);
                }
            })
            .catch(error => {
                console.error('Login error:', error);
            });
        },


        validateEmail() {
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(this.email)) {
                this.emailError = 'Please enter a valid email address.';
            } else {
                this.emailError = '';
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

    }));
});
</script>

