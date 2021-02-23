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
}