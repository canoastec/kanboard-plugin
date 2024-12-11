<?php

namespace Kanboard\Plugin\Ctec\Controller;

require __DIR__.'/../vendor/autoload.php';

use Kanboard\Controller\BaseController;
use Kanboard\Plugin\Ctec\Service\GenerateDataChartService;

class DashboardController extends BaseController 
{

    public function charts()
    {
        $data = (new GenerateDataChartService($this->container))->get();

        $data['title'] = t('Grafico estimado x executado');
        $data['user'] = $this->getUser();
        $this->hook->on('template:layout:js', array('template' => 'plugins/Ctec/Asset/Js/charts.js'));
        $this->response->html($this->helper->layout->dashboard('ctec:dashboard/charts', array(
            'title' => $data['title'],
            'user' => $data['user'],
            'percentageChart' => json_encode($data['percentageChart']),
            'tasksChart' => json_encode($data['tasksChart']),
            'timeChart' => json_encode($data['timeChart']),
        )));
    }

    public function sprint()
    {
        $data['title'] = t('Dashboard Sprint');
        $data['user'] = $this->getUser();
        $this->hook->on('template:layout:css', array('template' => 'plugins/Ctec/Asset/Css/sprintStyle.css'));
        $this->response->html($this->helper->layout->app('ctec:dashboard/sprint', array(
            'title' => $data['title'],
            'user' => $data['user'],
        )));

    }

    public function sprintApi()
    {
        $sprintTasks = $this->codeReviewModel->getAll(7075);
        $sprintTasks = collect($sprintTasks)->sortBy('project_name')->groupBy('column_title')->toArray();
        if($sprintTasks) {
            $sprintTasks = [
                'Aguardando'    => (isset($sprintTasks['Aguardando'])) ? $sprintTasks['Aguardando'] : [],
                'Executando'    => (isset($sprintTasks['Executando'])) ? $sprintTasks['Executando'] : [],
                'Code Review'   => (isset($sprintTasks['Code Review'])) ? $sprintTasks['Code Review'] : [],
                'Validando'     => (isset($sprintTasks['Validando'])) ? $sprintTasks['Validando'] : [],
                'Pronto'        => (isset($sprintTasks['Pronto'])) ? $sprintTasks['Pronto'] : [],
                'Homologação'   => (isset($sprintTasks['Homologação'])) ? $sprintTasks['Homologação'] : [],
                'Produção'      => (isset($sprintTasks['Produção'])) ? $sprintTasks['Produção'] : [],
            ];

        }
        
        return $this->response->json($sprintTasks);
    }
}