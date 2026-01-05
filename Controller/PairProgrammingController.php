<?php

namespace Kanboard\Plugin\Ctec\Controller;

require __DIR__.'/../vendor/autoload.php';
use Kanboard\Controller\BaseController;

class PairProgrammingController extends BaseController 
{

    public function index()
    {
        $user = $this->getUser();

        $this->response->html($this->helper->layout->dashboard('ctec:dashboard/pairprogramming', array(
            'title' => 'Pareamento / Desenvolvedor',
            'user' => $user,
            'paginator' => $this->pairProgrammingPagination->getDashboardPaginator('index', 25)
        )));
    }

    public function new()
    {
        $assignee = $this->request->getStringParam('assignee');
        $name = $this->request->getStringParam('name');
        $task = $this->request->getStringParam('task');

        $this->sendToApi($assignee, $task);        
        $this->sendToApi($name, $task);        
        
        $duplicate = $this->pairProgrammingModel->findByTaskAndName($task, $name);
        if (empty($duplicate)) {
            $this->pairProgrammingModel->create($task, $name, $assignee);
        }
        $this->response->redirect($this->helper->url->to('TaskViewController', 'show', array('task_id' => $task)), true);
    }
    
    private function sendToApi($name, $task)
    {
        $gestaosistemasApi = $this->configModel->getOption('gestaosistemas_api');

        $curl = curl_init($gestaosistemasApi);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_POSTFIELDS , [
            'name' => $name,
            'activity' => 'Pareamento',
            'task' => $task,
        ]);

        curl_exec($curl);
        curl_close($curl);
    }

    public function remove()
    {
        $id = $this->request->getStringParam('pair_programming_id');
        $task = $this->request->getStringParam('task');
        $name = $this->request->getStringParam('name');
        $gestaosistemasApi = $this->configModel->getOption('gestaosistemas_api');

        $curl = curl_init($gestaosistemasApi.'/remover');
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_POSTFIELDS , [
            'activity' => 'Pareamento',
            'task' => $task,
        ]);
        curl_exec($curl);
        curl_close($curl);
        
        $this->pairProgrammingModel->delete($id);
        $this->response->redirect($this->helper->url->to('TaskViewController', 'show', array('task_id' => $task)), true);
    }
}