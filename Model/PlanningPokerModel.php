<?php

namespace Kanboard\Plugin\Ctec\Model;

use Kanboard\Core\Base;
use Kanboard\Model\ColumnModel;
use Kanboard\Model\TaskModel;

class PlanningPokerModel extends Base
{
    private const SCORE_REFERENCE = [
        0  => ['time_estimated' => 1,  'reference' => 'XXP'],
        1  => ['time_estimated' => 4,  'reference' => 'XP'],
        2  => ['time_estimated' => 8,  'reference' => 'PP'],
        3  => ['time_estimated' => 16, 'reference' => 'P'],
        5  => ['time_estimated' => 24, 'reference' => 'M'],
        8  => ['time_estimated' => 32, 'reference' => 'G'],
        13 => ['time_estimated' => 40, 'reference' => 'GG'],
    ];

    /**
     * Update task score and related fields
     *
     * @param int $taskId
     * @param int $score
     * @return bool
     */
    public function updateTaskScore($taskId, $score, $isNextSprint = false)
    {
        if (!isset(self::SCORE_REFERENCE[$score])) {
            return false;
        }

        $reference = self::SCORE_REFERENCE[$score];
        
        return $this->db->table('tasks')->eq('id', $taskId)->update([
            'date_modification' => time(),
            'score' => $score,
            'time_estimated' => $reference['time_estimated'],
            'reference' => $reference['reference']
        ]);
    }

    /**
     * Attach task to the appropriate sprint
     *
     * @param int $taskId
     * @param bool $isNextSprint
     * @return bool
     */
    public function attachSprint($taskId, $isNextSprint)
    {
        $link = $this->linkModel->getByLabel('faz parte da sprint');
        $columnTitle = $isNextSprint ? 'Próximas' : 'Andamento';

        $sprint = $this->db
            ->table(TaskModel::TABLE)
            ->columns(
                TaskModel::TABLE.'.id',
                ColumnModel::TABLE.'.title'
            )
            ->join(ColumnModel::TABLE, 'id', 'column_id', TaskModel::TABLE)
            ->eq(TaskModel::TABLE.'.project_id', 32)
            ->eq(ColumnModel::TABLE.'.title', $columnTitle)
            ->findOne();

        $this->taskLinkModel->create($taskId, $sprint['id'] ,$link['id']);
    }
}