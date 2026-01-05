<template>
    <div class="kanban-column-ticket late-task task-board" :class="'color-'+task.color_id" v-if="task" @click="openTask">
        <div class="kanban-column-ticket-left">
            <div class="kanban-column-ticket-left-title">
                #{{ task.task_id }} - {{ task.title }}
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
                <img :src="getAvatar" :alt="task.task_assignee_name" :title="task.task_assignee_name">
            </div>
            <div class="kanban-column-ticket-right-avatar" v-else-if="task.task_assignee_username">
                <span>
                    {{getInitials()}}
                </span>
            </div>
        </div>
        <div class="kanban-column-ticket-footer">
            <div class="kanban-column-ticket-footer-pairprograming" v-if="task.pair_programming_name">
                Pareado com {{ displayName(task.pair_programming_name) }}
            </div>
            <div class="kanban-column-ticket-footer-codereview" v-if="task.code_review_name">
                Code Review por {{ displayName(task.code_review_name) }}
            </div>
            <div class="kanban-column-ticket-footer-tags">
                <span class="kanban-column-ticket-footer-tags-item task-tag" v-for="tag in task.tags" :key="tag.id" :class="'color-'+tag.color_id">
                    {{ tag.name }}
                </span>
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
            if(!this.task.task_assignee_username) return '';
            let initials = '';

            const words = this.task.task_assignee_username.split('.', 2);
            words.forEach(word => {
                initials += word.charAt(0); 
            });

            return initials.toUpperCase();  
        },
        displayName(name) {
            return name.split('.')
                .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                .join(' ');
        },
        openTask(){
            window.open('https://sistemas.canoas.rs.gov.br/kanboard/?controller=TaskViewController&action=show&task_id='+this.task.task_id, '_blank');
        },
    },    
    computed: {
        getAvatar(){
            let versionImg = Math.random() * (1000 - 1) + 1;
            return 'https://sistemas.canoas.rs.gov.br/kanboard/?controller=AvatarFileController&action=image&user_id='+this.task.task_assignee_id+'&size=48&v='+versionImg;
        }
    }  
}

</script>