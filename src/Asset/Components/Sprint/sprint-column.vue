<template>
    <div class="kanban-column">
        <div class="kanban-column-title">
            {{ title }} ({{count}})
        </div>
        <sprint-ticket v-for="task in tasks" :key="task.id" :task="task"></sprint-ticket>
    </div>
</template>

<script>
export default {
    props: {
        title: {
            type: String,
            default: '',
        },
        tasks: {
            type: Array,
            default: () => { return []},
        }
    },
    computed:{
        count(){
            return this.tasks ? this.tasks.length : 0;
        }
    }
}

</script>