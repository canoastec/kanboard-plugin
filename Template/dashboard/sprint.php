<body>
    <div id="container">
        <div class="kanban" v-if="columns">
            <sprint-column title="Aguardando" :tasks="columns['Aguardando']"></sprint-column>
            <sprint-column title="Executando" :tasks="columns['Executando']"></sprint-column>
            <sprint-column title="Code Review" :tasks="columns['Code Review']"></sprint-column>
            <sprint-column title="Validando" :tasks="columns['Validando']"></sprint-column>
            <sprint-column title="Pronto" :tasks="columns['Pronto']"></sprint-column>
            <sprint-column title="Homologação" :tasks="columns['Homologação']"></sprint-column>
            <sprint-column title="Produção" :tasks="columns['Produção']"></sprint-column>
        </div>
    </div>

</body>

<script type="module">
	new Vue({
		el: "#container",
        data() {
            return {
                columns: null
            }
        },
        methods: {
            getSprint(){
                $.get('?controller=DashboardController&action=sprintApi&plugin=ctec').done((response) => {
                    this.columns = response
                });
            }
        },
        created(){
            this.getSprint();
        }
	});
</script>