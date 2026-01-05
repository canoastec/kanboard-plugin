<?php

namespace Kanboard\Plugin\Ctec\Model;

use Kanboard\Core\Base;
use Kanboard\Model\CategoryModel;
use Kanboard\Model\TaskModel;
use Kanboard\Model\ColumnModel;
use Kanboard\Model\TransitionModel;
use Kanboard\Model\UserModel;

class GamificationModel extends Base
{
    public function all()
    {
        $firstDayOfMonth = $this->dateParser->getTimestamp(date("d/m/Y", mktime(0, 0, 0, date('m')-0, 1, date('Y'))));
        // $firstDayOfMonth = $this->dateParser->getTimestamp('01/07/2025');
       
        $from = $this->dateParser->removeTimeFromTimestamp(strtotime('-2 month', $firstDayOfMonth));
        $to = $this->dateParser->removeTimeFromTimestamp(strtotime('-1 month', $firstDayOfMonth));
        $subquery =$this->db
            ->table(TaskModel::TABLE)
            ->columns(TaskModel::TABLE.'.id')
            ->join(CategoryModel::TABLE, 'id', 'category_id', TaskModel::TABLE)
            ->join(TransitionModel::TABLE, 'task_id', 'id', TaskModel::TABLE)
            ->join(ColumnModel::TABLE, 'id', 'dst_column_id', TransitionModel::TABLE)
            ->left(CodeReviewModel::TABLE, CodeReviewModel::TABLE, 'task_id', TaskModel::TABLE, 'id')
            ->left(UserModel::TABLE, UserModel::TABLE, 'id', TaskModel::TABLE, 'owner_id')
            ->in(ColumnModel::TABLE.'.title', ['Homologação', 'Produção', 'Pronto'])
            // ->gte(TransitionModel::TABLE . '.date', $from)
            ->lt(TransitionModel::TABLE . '.date', $to);



        $from = $this->dateParser->removeTimeFromTimestamp(strtotime('-1 month', $firstDayOfMonth));
        $to = $this->dateParser->removeTimeFromTimestamp($firstDayOfMonth);
        
        return $this->db
            ->table(TaskModel::TABLE)
            ->columns(
                TaskModel::TABLE.'.id',
                'MAX('.TaskModel::TABLE.'.project_id) AS project_id',
                'MAX('.ColumnModel::TABLE.'.title) AS title',
                'MAX('.TaskModel::TABLE.'.time_estimated) AS time_estimated',
                'MAX('.UserModel::TABLE.'.name) AS name',
                'MAX('.UserModel::TABLE.'.username) AS username',
                'MAX('.CodeReviewModel::TABLE.'.name) AS codereview_username',
                'MAX('.PairProgrammingModel::TABLE.'.name) AS pairprogramming_username',
                'MAX('.CategoryModel::TABLE.'.name) AS category'
            )
            ->join(CategoryModel::TABLE, 'id', 'category_id', TaskModel::TABLE)
            ->join(TransitionModel::TABLE, 'task_id', 'id', TaskModel::TABLE)
            ->join(ColumnModel::TABLE, 'id', 'dst_column_id', TransitionModel::TABLE)
            ->left(CodeReviewModel::TABLE, CodeReviewModel::TABLE, 'task_id', TaskModel::TABLE, 'id')
            ->left(PairProgrammingModel::TABLE, PairProgrammingModel::TABLE, 'task_id', TaskModel::TABLE, 'id')
            ->left(UserModel::TABLE, UserModel::TABLE, 'id', TaskModel::TABLE, 'owner_id')
            ->in(ColumnModel::TABLE.'.title', ['Homologação', 'Produção', 'Pronto'])
            ->gte(TransitionModel::TABLE . '.date', $from)
            ->lt(TransitionModel::TABLE . '.date', $to)
            ->notInSubquery(TaskModel::TABLE.'.id', $subquery)
            ->groupBy(TaskModel::TABLE.'.id')
            ->findAll();
    }
    
 

}
