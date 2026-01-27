<?php

namespace Kanboard\Plugin\Ctec\Model;

use Kanboard\Core\Base;
use Kanboard\Model\LinkModel;
use Kanboard\Model\TaskModel;
use Kanboard\Model\UserModel;
use Kanboard\Model\ColumnModel;
use Kanboard\Model\ProjectModel;
use Kanboard\Model\TagModel;
use Kanboard\Model\TaskLinkModel;
use Kanboard\Model\TaskTagModel;

class DashboardCtecModel extends Base
{
    public function getAllSprints($limit = 10)
    {
        return $this->db
            ->table(TaskModel::TABLE)
            ->columns(
                TaskModel::TABLE . '.id',
                TaskModel::TABLE . '.title',
                TaskModel::TABLE . '.date_started',
                TaskModel::TABLE . '.date_due'
            )
            ->eq(TaskModel::TABLE . '.project_id', 32)
            ->desc(TaskModel::TABLE . '.id')
            ->limit($limit)
            ->findAll();
    }

    public function getAll($task_id)
    {

        $tasks = $this->db
            ->table(TaskLinkModel::TABLE)
            ->columns(
                TaskLinkModel::TABLE . '.id',
                TaskLinkModel::TABLE . '.opposite_task_id AS task_id',
                LinkModel::TABLE . '.label',
                TaskModel::TABLE . '.title',
                TaskModel::TABLE . '.is_active',
                TaskModel::TABLE . '.project_id',
                TaskModel::TABLE . '.column_id',
                TaskModel::TABLE . '.color_id',
                TaskModel::TABLE . '.date_completed',
                TaskModel::TABLE . '.date_started',
                TaskModel::TABLE . '.date_modification',
                TaskModel::TABLE . '.date_due',
                TaskModel::TABLE . '.time_spent AS task_time_spent',
                TaskModel::TABLE . '.time_estimated AS task_time_estimated',
                TaskModel::TABLE . '.reference AS task_time_reference',
                TaskModel::TABLE . '.owner_id AS task_assignee_id',
                UserModel::TABLE . '.username AS task_assignee_username',
                UserModel::TABLE . '.name AS task_assignee_name',
                UserModel::TABLE . '.avatar_path AS avatar_path',
                ColumnModel::TABLE . '.title AS column_title',
                ProjectModel::TABLE . '.name AS project_name',
                PairProgrammingModel::TABLE . '.name AS pair_programming_name',
                CodeReviewModel::TABLE . '.name as code_review_name',
                CodeReviewModel::TABLE . '.name as dev_name'

            )
            ->eq(TaskLinkModel::TABLE . '.task_id', $task_id)
            ->join(LinkModel::TABLE, 'id', 'link_id')
            ->join(TaskModel::TABLE, 'id', 'opposite_task_id')
            ->join(ColumnModel::TABLE, 'id', 'column_id', TaskModel::TABLE)
            ->join(UserModel::TABLE, 'id', 'owner_id', TaskModel::TABLE)
            ->join(ProjectModel::TABLE, 'id', 'project_id', TaskModel::TABLE)
            ->left(CodeReviewModel::TABLE, CodeReviewModel::TABLE, 'task_id', TaskModel::TABLE, 'id')
            ->left(PairProgrammingModel::TABLE, PairProgrammingModel::TABLE, 'task_id', TaskModel::TABLE, 'id')
            ->asc(LinkModel::TABLE . '.id')
            ->desc(ColumnModel::TABLE . '.position')
            ->desc(TaskModel::TABLE . '.is_active')
            ->asc(TaskModel::TABLE . '.position')
            ->asc(TaskModel::TABLE . '.id')
            ->findAll();

        $tags = $this->db
            ->table(TagModel::TABLE)
            ->join(TaskTagModel::TABLE, 'tag_id', 'id', TagModel::TABLE)
            ->in(TaskTagModel::TABLE . '.task_id', array_column($tasks, 'task_id'))
            ->findAll();
        $links = $this->db
            ->table(TaskLinkModel::TABLE)
            ->columns(
                LinkModel::TABLE . '.label',
                TaskLinkModel::TABLE . '.opposite_task_id',
                TaskLinkModel::TABLE . '.task_id',
                ColumnModel::TABLE . '.title'
            )
            ->join(LinkModel::TABLE, 'id', 'link_id', TaskLinkModel::TABLE)
            ->join(TaskModel::TABLE, 'id', 'opposite_task_id', TaskLinkModel::TABLE)
            ->join(ColumnModel::TABLE, 'id', 'column_id', TaskModel::TABLE)
            ->in(TaskLinkModel::TABLE . '.task_id', array_column($tasks, 'task_id'))
            ->findAll();

        return collect($tasks)->map(function ($task) use ($tags, $links) {
            $task['tags'] = collect($tags)->filter(function ($tag) use ($task) {
                return $tag['task_id'] == $task['task_id'];
            })->values()->all();
            $task['links'] = collect($links)->filter(function ($link) use ($task) {
                return $link['task_id'] == $task['task_id'];
            })->values()->toArray();
            return $task;
        });
    }
}
