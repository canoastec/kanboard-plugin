<?php

namespace Kanboard\Plugin\Ctec\Model;

use Kanboard\Core\Base;
use Kanboard\Model\LinkModel;
use Kanboard\Model\TaskModel;
use Kanboard\Model\UserModel;
use Kanboard\Model\ColumnModel;
use Kanboard\Model\ProjectModel;
use Kanboard\Model\TaskLinkModel;

class CodeReviewModel extends Base
{
    const TABLE = 'code_review';

  
    public function all()
    {
        return $this->db->table(self::TABLE)->findAll();
    }
    
    public function getAll($task_id)
    {
        return $this->db
                    ->table(TaskLinkModel::TABLE)
                    ->columns(
                        TaskLinkModel::TABLE.'.id',
                        TaskLinkModel::TABLE.'.opposite_task_id AS task_id',
                        LinkModel::TABLE.'.label',
                        TaskModel::TABLE.'.title',
                        TaskModel::TABLE.'.is_active',
                        TaskModel::TABLE.'.project_id',
                        TaskModel::TABLE.'.column_id',
                        TaskModel::TABLE.'.color_id',
                        TaskModel::TABLE.'.date_completed',
                        TaskModel::TABLE.'.date_started',
                        TaskModel::TABLE.'.date_due',
                        TaskModel::TABLE.'.time_spent AS task_time_spent',
                        TaskModel::TABLE.'.time_estimated AS task_time_estimated',
                        TaskModel::TABLE.'.owner_id AS task_assignee_id',
                        UserModel::TABLE.'.username AS task_assignee_username',
                        UserModel::TABLE.'.name AS task_assignee_name',
                        ColumnModel::TABLE.'.title AS column_title',
                        ProjectModel::TABLE.'.name AS project_name',
                        PairProgrammingModel::TABLE.'.name AS pair_programming_name',
                        self::TABLE.'.name as dev_name'
                    )
                    ->eq(TaskLinkModel::TABLE.'.task_id', $task_id)
                    ->join(LinkModel::TABLE, 'id', 'link_id')
                    ->join(TaskModel::TABLE, 'id', 'opposite_task_id')
                    ->join(ColumnModel::TABLE, 'id', 'column_id', TaskModel::TABLE)
                    ->join(UserModel::TABLE, 'id', 'owner_id', TaskModel::TABLE)
                    ->join(ProjectModel::TABLE, 'id', 'project_id', TaskModel::TABLE)
                    ->left(self::TABLE, self::TABLE, 'task_id', TaskModel::TABLE, 'id')
                    ->left(PairProgrammingModel::TABLE, PairProgrammingModel::TABLE, 'task_id', TaskModel::TABLE, 'id')
                    ->asc(LinkModel::TABLE.'.id')
                    ->desc(ColumnModel::TABLE.'.position')
                    ->desc(TaskModel::TABLE.'.is_active')
                    ->asc(TaskModel::TABLE.'.position')
                    ->asc(TaskModel::TABLE.'.id')
                    ->findAll();
    }
    
    public function findByTaskAndName($task, $name)
    {
        return $this->db->table(self::TABLE)
            ->eq('task_id', $task)
            ->eq('name', $name)
            ->findOne();
    }
    
    public function findByTask($id)
    {
        return $this->db->table(self::TABLE)->eq('task_id', $id)->findOne();
    }
    
    public function delete($id)
    {
        return $this->db->table(self::TABLE)->eq('id', $id)->remove();
    }
  
    public function create($task, $name)
    {
        $this->db->startTransaction();

        $rs = $this->db->table(self::TABLE)->persist(array(
            'task_id' => $task,
            'name' => $name,
        ));

        $this->db->closeTransaction();

        return $rs;
    }

}
