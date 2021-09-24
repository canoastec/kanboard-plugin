<?php

namespace Kanboard\Plugin\Ctec\Controller;

require __DIR__.'/../vendor/autoload.php';
use Kanboard\Controller\BaseController;

class PairProgrammingController extends BaseController 
{

    public function index()
    {
        $this->response->html($this->helper->layout->dashboard('ctec:dashboard/pairprogramming', array(
            'title' => 'Pareamento / Desenvolvedor',
            'user' => $this->getUser(),
            'pairProgrammings' => $this->pairProgrammingModel->all()
        )));
    }

    public function new()
    {
        $assignee = $this->request->getStringParam('assignee');
        $name = $this->request->getStringParam('name');
        $task = $this->request->getStringParam('task');
        $gestaosistemasApi = $this->configModel->getOption('gestaosistemas_api');

        $curl = curl_init($gestaosistemasApi);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_POSTFIELDS , [
            'name' => $name,
            'assignee' => $assignee,
            'activity' => 'Pareamento',
            'task' => $task,
        ]);

        curl_exec($curl);
        curl_close($curl);
        
        $duplicate = $this->pairProgrammingModel->findByTaskAndName($task, $name);
        if (empty($duplicate)) {
            $this->pairProgrammingModel->create($task, $name, $assignee);
        }
        $this->response->redirect($this->helper->url->to('TaskViewController', 'show', array('task_id' => $task)), true);
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
        $this->response->redirect($this->helper->url->to('PairProgrammingController', 'index', array('plugin' => 'ctec')), true);
    }
}