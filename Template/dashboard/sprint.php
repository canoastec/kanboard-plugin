<body>
    <div id="container" v-cloak>
        <h1 class="sprint-container">
            <select v-model="selectedSprint" @change="getSprint">
                <option v-for="sprint in sprints" :value="sprint">{{ sprint.title }}</option>
            </select>
            <div class="sprint-info" v-if="selectedSprint">
                {{ formatDate(selectedSprint.date_started) }} - {{ formatDate(selectedSprint.date_due) }}
            </div>
        </h1>
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
                columns: null,
                sprints: <?php echo json_encode($sprints); ?>,
                selectedSprint: <?php echo json_encode($sprintCurrent); ?>
            }
        },
        methods: {
            formatDate(timestamp) {
                if (!timestamp) return '';
                const date = new Date(timestamp * 1000);
                const day = String(date.getDate()).padStart(2, '0');
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const year = date.getFullYear();
                return `${day}/${month}/${year}`;
            },
            getSprint(){
                $.get(`?controller=DashboardController&action=sprintApi&plugin=ctec&sprint_id=${this.sprintId}`).done((response) => {
                    this.columns = response
                });
            }
        },
        computed: {
            sprintId(){
                return this.selectedSprint ? this.selectedSprint.id : null;
            }
        },
        created(){
            setInterval(() => {
                this.getSprint();
            }, 100000);
            this.getSprint();
        }
	});
</script>

<style>
    .page {
        margin-left: unset;
        margin-right: unset;
    }
</style>