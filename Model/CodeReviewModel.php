<?php

namespace Kanboard\Plugin\Ctec\Model;

use Kanboard\Core\Base;

class CodeReviewModel extends Base
{
    const TABLE = 'code_review';

  
    public function all()
    {
        return $this->db->table(self::TABLE)->findAll();
    }
    
    public function findByTask($id)
    {
        return $this->db->table(self::TABLE)->eq('task_id', $id)->findOne();
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
