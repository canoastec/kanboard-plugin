<?php

namespace Kanboard\Plugin\Ctec\Api\Procedure;

use Kanboard\Api\Procedure\BaseProcedure;

class CodeReviewProcedure extends BaseProcedure
{
    public function getAllTaskOfSprintWithCodeReview($task_id)
    {
        return $this->codeReviewModel->getAll($task_id);
    }

    
}

