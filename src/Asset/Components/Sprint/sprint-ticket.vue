<template>
  <div
    class="kanban-column-ticket task-board"
    :class="
      'color-' +
      task.color_id +
      (hasOtherSprint ? ' late-task' : '') +
      (blockeds.length ? ' blocked-task' : '')
    "
    v-if="task"
    @click="openTask"
  >
    <div
      class="kanban-column-ticket-blocked"
      v-for="(blocked, i) in blockeds"
      :key="i"
      @click="openTaskById(blocked.opposite_task_id)"
    >
      <i class="fa fa-ban" aria-hidden="true"></i>
      Bloqueado por #{{ blocked.opposite_task_id }} ({{ blocked.title }})
    </div>

    <div
      class="kanban-column-ticket-related"
      v-for="(related, i) in relatetesTo"
      :key="i"
      @click="openTaskById(related.opposite_task_id)"
    >
      <i class="fa fa-exclamation-triangle" aria-hidden="true"></i>
      Associado com #{{ related.opposite_task_id }} ({{ related.title }})
    </div>
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
        {{ task.task_time_reference }}
      </div>
      <div class="avatar avatar-48 avatar-inline" v-if="task.avatar_path">
        <img
          :src="getAvatar"
          :alt="task.task_assignee_name"
          :title="task.task_assignee_name"
        />
      </div>
      <div
        class="kanban-column-ticket-right-avatar"
        v-else-if="task.task_assignee_username"
      >
        <span>
          {{ getInitials() }}
        </span>
      </div>
    </div>
    <div class="kanban-column-ticket-footer">
      <div
        class="kanban-column-ticket-footer-pairprograming"
        v-if="task.pair_programming_name"
      >
        Pareado com {{ displayName(task.pair_programming_name) }}
      </div>
      <div
        class="kanban-column-ticket-footer-codereview"
        v-if="task.code_review_name"
      >
        Code Review por {{ displayName(task.code_review_name) }}
      </div>
      <div class="kanban-column-ticket-footer-tags">
        <span
          class="kanban-column-ticket-footer-tags-item task-tag"
          v-for="tag in task.tags"
          :key="tag.id"
          :class="'color-' + tag.color_id"
        >
          {{ tag.name }}
        </span>
      </div>
      <div class="kanban-column-ticket-footer-modification">
        Última modificação:
        {{ new Date(task.date_modification * 1000).toLocaleString('pt-BR') }}
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
    getInitials () {
      if (!this.task.task_assignee_username) return ''
      let initials = ''

      const words = this.task.task_assignee_username.split('.', 2)
      words.forEach(word => {
        initials += word.charAt(0)
      })

      return initials.toUpperCase()
    },
    displayName (name) {
      return name
        .split('.')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ')
    },
    openTask () {
      if (this.blockeds.length) return
      window.open(
        'https://sistemas.canoas.rs.gov.br/kanboard/?controller=TaskViewController&action=show&task_id=' +
          this.task.task_id,
        '_blank'
      )
    },
    openTaskById (taskId) {
      window.open(
        'https://sistemas.canoas.rs.gov.br/kanboard/?controller=TaskViewController&action=show&task_id=' +
          taskId,
        '_blank'
      )
    }
  },
  computed: {
    hasOtherSprint () {
      return this.task.links.some(link => {
        return link.label == 'veio de outra sprint'
      })
    },
    blockeds () {
      return this.task.links.filter(link => {
        return (
          link.label == 'is blocked by' &&
          (link.title.toUpperCase() == 'AGUARDANDO' ||
            link.title.toUpperCase() == 'EXECUTANDO')
        )
      })
    },
    relatetesTo () {
      return this.task.links.filter(link => {
        return link.label == 'relates to'
      })
    },
    getAvatar () {
      let versionImg = Math.random() * (1000 - 1) + 1
      return (
        'https://sistemas.canoas.rs.gov.br/kanboard/?controller=AvatarFileController&action=image&user_id=' +
        this.task.task_assignee_id +
        '&size=48&v=' +
        versionImg
      )
    }
  }
}
</script>
