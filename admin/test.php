<?php
html_header( "Geogram login" );
?>

<div id="page">

    <?php include 'header.php'; ?>

    <main x-data="loginComponent()">
        <div style="background-color: #000;">
        <img src="/images/geogram-logo.svg" style="margin:0;padding:0;width: 305px;height:60px" alt="Geogram">
        </div>

    </main>


</div>

<?php
html_footer();
?>

<script src="/js/test.js?a2" defer></script>
<script type="module">

import { TestModule } from '/js/test.js';


document.addEventListener('alpine:init', () => {
    Alpine.data('loginComponent', () => ({

        // Variables locales pour la gestion de l'authentification
        user: null,

        // ...TestModule,

        init(){
            console.log("Init");
            this.user = Alpine.store('headerActions').user;
            console.log(testModuleVar);
        },


    }));
});
</script>

