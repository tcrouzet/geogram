<?php
html_header( "geogram user" );
?>

<div id="page" >


    <?php include 'header.php'; ?>

    <!-- Section Content -->
    <main x-data="userComponent()">
    <div id="login" class="userwidth">
    
            <div class="divider">User</div>

            <div class="input-group">
                <label>Email:</label>
                <input type="email" placeholder="Email" class="input-field" x-model="user.useremail" required @input="validateEmail">
                <div x-show="emailError" class="error-message" x-text="emailError"></div>
            </div>

            <div class="input-group">
                <label>User name:</label>
                <input type="username" placeholder="User name" class="input-field" x-model="user.username" required  @input="validateEmail">
            </div>

            <!-- Champ pour uploader l'image -->
            <div class="input-group">
                <label>Profile Image (JPEG only):</label>
                <input type="file" @change="handleFileUpload" x-ref="fileInput" accept="image/jpeg" class="input-field">
                <div x-show="fileError" class="error-message" x-text="fileError"></div>
            </div>

            <!-- Prévisualisation de l'image (optionnel) -->
            <div class="input-group" x-show="previewImage">
                <label>Image Preview:</label>
                <img :src="previewImage" alt="Image Preview" class="image-preview" style="max-width: 200px; max-height: 200px;">
            </div>

            <button class="btn btn-submit" type="submit" @click="loginWithForm()" x-bind:disabled="loading">
                Continue
            </button>

        </div>        
    </main>

    <?php include 'footer.php'; ?>

</div>

<?php
html_footer();
?>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('userComponent', () => ({

        user: null,
        emailError: '',

        init() {
            console.log("Initializing user...");
            this.user = Alpine.store('headerActions').user;
            console.log(this.user.useremail);
            console.log(this.user.username);
            if(this.isLoggedIn()){
                console.log("User logged");
            }
        },

        isLoggedIn() {
            return this.user !== null;
        },



    }));
});
</script>


