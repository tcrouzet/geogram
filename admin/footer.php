<!-- Section footer -->
<footer x-data="footerComponent()">
    <button @click="$store.mapActions.actionFitAll()">FitAll</button>
    <button @click="$store.mapActions.actionFitGPX()">FitGPX</button>
    <button @click="$store.mapActions.actionLocalise()">Localise</button>
</footer>


<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('footerComponent', () => ({


    }));
});
</script>