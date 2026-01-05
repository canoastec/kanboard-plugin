<?php

namespace Kanboard\Plugin\Ctec\Controller;

require __DIR__.'/../vendor/autoload.php';
use Kanboard\Controller\BaseController;

class CodeReviewController extends BaseController 
{

    public function index()
    {
        $user = $this->getUser();

        $this->response->html($this->helper->layout->dashboard('ctec:dashboard/codereview', array(
            'title' => 'CodeReview / Desenvolvedor',
            'user' => $user,
            'paginator' => $this->codeReviewPagination->getDashboardPaginator('index', 25)
        )));
    }

    public function new()
    {
        $name = $this->request->getStringParam('name');
        $task = $this->request->getStringParam('task');
        $gestaosistemasApi = $this->configModel->getOption('gestaosistemas_api');

        $curl = curl_init($gestaosistemasApi);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_POSTFIELDS , [
            'name' => $name,
            'activity' => 'Code Review',
            'task' => $task,
        ]);

        curl_exec($curl);
        curl_close($curl);
        
        $duplicate = $this->codeReviewModel->findByTaskAndName($task, $name);
        if (empty($duplicate)) {
            $this->codeReviewModel->create($task, $name);
        }
        $this->response->redirect($this->helper->url->to('TaskViewController', 'show', array('task_id' => $task)), true);
    }
    
    public function remove()
    {
        $id = $this->request->getStringParam('code_review_id');
        $task = $this->request->getStringParam('task');
        $name = $this->request->getStringParam('name');
        $gestaosistemasApi = $this->configModel->getOption('gestaosistemas_api');

        $curl = curl_init($gestaosistemasApi.'/remover');
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_POSTFIELDS , [
            'activity' => 'Code Review',
            'task' => $task,
        ]);
        curl_exec($curl);
        curl_close($curl);
        
        $this->codeReviewModel->delete($id);
        $this->response->redirect($this->helper->url->to('TaskViewController', 'show', array('task_id' => $task)), true);
    }
}