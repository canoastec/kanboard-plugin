<?php

namespace Kanboard\Plugin\Ctec\Model;

use Kanboard\Core\Base;

class PairProgrammingModel extends Base
{
    const TABLE = 'pair_programming';

  
    public function all()
    {
        return $this->db->table(self::TABLE)->findAll();
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
  
    public function create($task, $name, $assignee)
    {
        $this->db->startTransaction();

        $rs = $this->db->table(self::TABLE)->persist(array(
            'task_id' => $task,
            'name' => $name,
            'assignee' => $assignee,
        ));

        $this->db->closeTransaction();

        return $rs;
    }

}
