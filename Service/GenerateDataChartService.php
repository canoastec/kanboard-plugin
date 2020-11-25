<?php

namespace Kanboard\Plugin\Ctec\Service;

require __DIR__.'/../vendor/autoload.php';

use Kanboard\Core\Base;
use Kanboard\Plugin\Ctec\Service\SearchSprintService;
use Pimple\Container;

class GenerateDataChartService
{
    private $searchSprintService;

    public function __construct(Container $container)
    {
        $this->searchSprintService = new SearchSprintService($container);
    }

    public function get()
    {
        $selectedSprints = $this->getSeasonCompareHomeOffice();
        $selectedSprints = $this->searchSprintService->getSprintsAndTasks($selectedSprints);

        $timeChart = $this->timeChart($selectedSprints);
        $tasksChart = $this->tasksChart($selectedSprints);
        $percentageChart = $this->percentageChart($selectedSprints);

        return [
            'selectedSprints' => $selectedSprints,
            'timeChart' => $timeChart,
            'tasksChart' => $tasksChart,
            'percentageChart' => $percentageChart,
        ];
    }

    private function getSeasonCompareHomeOffice()
    {
        $sprints = $this->searchSprintService->getAllSprints()->keyBy('title');
        $currentSprint = $this->searchSprintService->getCurrentSprint();
        $currentSprint = (int)str_replace("Sprint ", "", $currentSprint['title']);
        $seasonSprints = [];
        for ($i = 75-($currentSprint-75); $i <= 75; $i++) {
            array_push($seasonSprints, "Sprint ".$i);
        }
        for ($i = 76; $i <= $currentSprint; $i++) {
            array_push($seasonSprints, "Sprint ".$i);
        }
        $seasonSprints = $sprints->whereIn('title', $seasonSprints)->map(function ($sprint){
            return $sprint['id'];
        });
        
        return $seasonSprints;
    }


    private function timeChart($sprints)
    {
        $labels = [];
        $estimatedData = [];
        $spentData = [];
        foreach ($sprints as $sprint){
            array_push($labels, $sprint['title']);
            array_push($estimatedData, $sprint['estimated']);
            array_push($spentData, $sprint['spent']);
        }

        foreach($estimatedData as $key => $data){
            $estimated[$key] = (object) array('name' => "(".$labels[$key].")", 'data' => $data); 
        }

        foreach($spentData as $key => $data){
            $spent[$key] = (object) array('name' => "(".$labels[$key].")", 'data' => $data); 
        }

        return (object) [
            'title'=> 'Total de horas',
            'labels'=> $labels,
            'datasets' => [
                (object)[
                    'label' => 'Tempo estimado ('.collect($estimatedData)->sum().' horas)',
                    'color' => 'red',
                    'data' => $estimated
                ],
                (object)[
                    'label' => 'Tempo executado ('.collect($spentData)->sum().' horas)',
                    'color' => 'blue',
                    'data' => $spent
                ]
            ]
        ];
    }

    private function tasksChart($sprints)
    {
        $labels = [];
        $tasksData = [];
        foreach ($sprints as $sprint){
            array_push($labels, $sprint['title']);
            array_push($tasksData, $sprint['tasks']->filter(function ($task){
                return $task['task_time_spent'] != '0';
            })->count());
        }
        
        foreach($tasksData as $key => $data){
            $tasks[$key] = (object) array('name' => "(".$labels[$key].")", 'data' => $data); 
        }

        return (object) [
            'title' => 'Total de tarefas executadas',
            'labels'=> $labels,
            'datasets' => [
                (object)[
                    'label' => 'Total de tarefas',
                    'color' => 'orange',
                    'data' => $tasks
                ]
            ]
        ];
    }

    private function percentageChart($sprints)
    {
        $labels = [];
        $percentSpentData = [];
        foreach ($sprints as $sprint){
            array_push($labels, $sprint['title']);
            array_push($percentSpentData, round((int)100*($sprint['spent']/$sprint['estimated'])));
        }
        foreach($percentSpentData as $key => $data){
            $percentSpent[$key] = (object) array('name' => "(".$labels[$key].")", 'data' => $data); 
        }

        return (object) [
            'title'=> 'Percentual utilizado do tempo estimado (total '.round((int)100*($sprints->sum('spent')/$sprints->sum('estimated'))).'%)',
            'labels'=> $labels,
            'datasets' => [
                (object)[
                    'label' => 'Percentual utilizado do tempo estimado',
                    'color' => 'green',
                    'data' => $percentSpent
                ]
            ]
        ];
    }

}