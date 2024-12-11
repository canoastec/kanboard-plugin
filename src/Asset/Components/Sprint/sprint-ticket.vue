<template>
    <div class="kanban-column-ticket late-task task-board" :class="'color-'+task.color_id" v-if="task">
        <div class="kanban-column-ticket-left">
            <div class="kanban-column-ticket-left-title">
                #{{ task.id }} - {{ task.title }}
            </div>
            <div class="kanban-column-ticket-left-project">
                {{ task.project_name }}
            </div>
        </div>
        <div class="kanban-column-ticket-right">
            <div class="kanban-column-ticket-right-time">
                {{ task.task_time_estimated }}h
            </div>
            <div class="avatar avatar-48 avatar-inline" v-if="task.avatar_path">
                <img :src="'https://sistemas.canoas.rs.gov.br/kanboard/?controller=AvatarFileController&action=image&user_id='+task.task_assignee_id+'&size=48&v=13'" alt="Usuario Fantasma" title="Usuario Fantasma">
            </div>
            <div class="kanban-column-ticket-right-avatar" v-else>
                <span else>
                    {{getInitials()}}
                </span>
            </div>
        </div>
        <div class="kanban-column-ticket-footer">
            <div class="kanban-column-ticket-footer-pairprograming">
                Pareado com usuario.fantasma
            </div>
            <div class="kanban-column-ticket-footer-codereview">
                Code Review por usuario.fantasma
            </div>
            <div class="kanban-column-ticket-footer-tags">
                <span class="kanban-column-ticket-footer-tags-item">Melhoria</span>
                <span class="kanban-column-ticket-footer-tags-item">Ver Comentarios</span>
            </div>

        </div>
    </div>
</template>


<script>
export default {
    props: {
        task: {
            type: Object,
            default: null
        }
    },
    methods: {
        getInitials() {
            let initials = '';

            const words = this.task.task_assignee_username.split('.', 2);
            words.forEach(word => {
                initials += word.charAt(0); 
            });

            return initials.toUpperCase();  
        }
    },      
}

</script>