<div id="page">

    <?php include 'header.php'; ?>

    <!-- Section Content -->
    <main x-data="userComponent()" >

        <template x-if="!isLoggedIn">

            <div id="login" class="loginwidth">

                <h1>Welcome</h1>
                <p>Connect to Geogram.</p>

            </div>
        </template>

        <template x-if="isLoggedIn">
            <div id="login" class="userwidth">

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
                    <button @click="user_actions(user.userid,'purgeuser',$el.textContent)">Delete all logs</button>
                </div>
                <div x-show="actionError" class="error-message" x-text="actionError"></div>


            </div>  
        </template>

    </main>

</div>

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

        init(){
            console.log("userInit");
            this.isLoggedIn = Alpine.store('headerActions').isLoggedIn;
            if(!this.isLoggedIn)
                window.location.href = "/login";
            else{
                this.user = Alpine.store('headerActions').user;
                this.email = this.user.useremail;
                this.username = this.user.username;
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

        // Fonction pour gérer la connexion via le formulaire
        // loginWithForm() {
        //     if (this.emailError || this.passwordError) {
        //         alert('Please correct the errors before submitting.');
        //         return;
        //     }

        //     this.validateEmail(this.email);
        //     this.checkPasswordLength(this.password)

        //     if (this.emailError!='' || this.passwordError!=''){
        //         console.log("loginWithForm Bug");
        //         return;
        //     }
        //     console.log("loginWithForm OK");

        //     const formData = new URLSearchParams();
        //     formData.append('view', 'login');
        //     formData.append('email', this.email);
        //     formData.append('password', this.password);

        //     fetch('/api/', {
        //         method: 'POST',
        //         headers: {
        //             'Content-Type': 'application/x-www-form-urlencoded'
        //         },
        //         body: formData.toString()
        //     })
        //     .then(response => {
        //         if (!response.ok) {
        //             throw new Error('Network response was not ok ' + response.statusText);
        //         }
        //         return response.json();
        //     })
        //     .then(data => {
        //         //console.log(data);
        //         if (data.status === 'success') {
        //             // Utilisateur connecté
        //             this.connected(data.userdata)
        //         } else if (data.status === 'not_found') {
        //             if (confirm(data.message)) {
        //                 this.createUser();
        //             }
        //         } else {
        //             this.passwordError = data.message;
        //         }
        //     })
        //     .catch(error => console.error('Error:', error));
        // },

        updateUser() {
            // Envoyer une requête pour mettre à jour la route
            fetch('/api/', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    view: "updateuser",
                    username: this.user.username
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
                    this.user.username = data.user.username;
                    localStorage.setItem('user', JSON.stringify(this.user));
                } else {
                    console.error('Error updating user', data.message);
                }
            })
            .catch(error => console.error('Error:', error));
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
                    'Content-Type': 'application/x-www-form-urlencoded'
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

        updateHeader(userdata){
            localStorage.setItem('user', JSON.stringify(userdata));
            Alpine.store('headerActions').init();
        },


    }));
});
</script>

