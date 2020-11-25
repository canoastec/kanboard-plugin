<?php

namespace Kanboard\Plugin\Ctec\Service;

require __DIR__.'/../vendor/autoload.php';

use Kanboard\Core\Base;
use Kanboard\Api\Procedure\TaskProcedure;
use Tightenco\Collect\Support\Collection;
use Kanboard\Api\Procedure\TaskLinkProcedure;

class SearchSprintService extends Base
{
    
    public function getAllSprints()
    {
        $tasks = $this->api
            ->getProcedureHandler()
            ->executeMethod(
                new TaskProcedure($this->container), 
                'searchTasks', 
                [env('PROJECT_ID'), '']
            );
        $tasks = collect($tasks);

        return $tasks->sortBy(function ($sprint){
            return str_replace("Sprint ", "", $sprint['title']);
        });
    }

    public function getCurrentSprint()
    {
        $sprint = $this->api
            ->getProcedureHandler()
            ->executeMethod(
                new TaskProcedure($this->container), 
                'searchTasks', 
                [env('PROJECT_ID'), env('QUERY_CURRENT_SPRINT')]
            );
        $sprint = collect($sprint);

        return $sprint->first();
    }

    public function getSprintsAndTasks(Collection $sprintsId)
    {
        return $sprintsId->map(function ($sprintId){
            $sprint = $this->getSprint($sprintId);

            $tasks = $this->getAllTaskLinks($sprintId)->map(function($task){
                $task['color_id'] == "red" && $task['task_time_estimated'] == '0' ? $task['task_time_estimated'] = '24' : '';
                $task['color_id'] == "blue" && $task['task_time_estimated'] == '0' ? $task['task_time_estimated'] = '24' : '';
                return $task;   
            });

            $tasks = $tasks->filter(function($task){
                return $task['label'] != 'possui tarefas de outra sprint';
            });

            $sprint['estimated'] = $tasks->sum(function($task){
                return $task['task_time_spent'] != '0' ? (int)$task['task_time_estimated'] : false;
            });

            $sprint['spent'] = $tasks->sum(function($task){
                return (int)$task['task_time_spent'];
            });

            $sprint['tasks'] = $tasks;

            return $sprint;
        })->filter(function($sprint){
            return $sprint['estimated'] && $sprint['spent'] != 0;
        });
    }
   
    public function getSprint($sprintId)
    {
        $sprint = $this->api
            ->getProcedureHandler()
            ->executeMethod(
                new TaskProcedure($this->container), 
                'searchTasks', 
                [env('PROJECT_ID'), "id:".$sprintId]
            );
        $sprint = collect($sprint);

        return $sprint->first();
    }

    public function getAllTaskLinks($sprintId)
    {
        $sprint = $this->api
            ->getProcedureHandler()
            ->executeMethod(
                new TaskLinkProcedure($this->container), 
                'getAllTaskLinks', 
                [$sprintId]
            );
    
        return collect($sprint);
    }
}