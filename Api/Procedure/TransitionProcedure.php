<?php

namespace Kanboard\Plugin\Ctec\Api\Procedure;

use Kanboard\Api\Procedure\BaseProcedure;

class TransitionProcedure extends BaseProcedure
{
    public function getAllTransitionsByTask($task_id)
    {
        return $this->transitionModel->getAllByTask($task_id);
    }

    
}

