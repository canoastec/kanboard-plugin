<?php

namespace Kanboard\Plugin\Ctec\Controller;

require __DIR__.'/../vendor/autoload.php';
use Kanboard\Controller\BaseController;

class CodeReviewController extends BaseController 
{

    public function index()
    {
        $this->response->html($this->helper->layout->dashboard('ctec:dashboard/codereview', array(
            'title' => 'CodeReview / Desenvolvedor',
            'user' => $this->getUser(),
            'reviews' => $this->codeReviewModel->all()
        )));
    }

    public function new()
    {
        $name = $this->request->getStringParam('name');
        $task = $this->request->getStringParam('task');

        $curl = curl_init(env('GESTAOSISTEMAS_API'));
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_POSTFIELDS , [
            'name' => $name,
            'activity' => 'Code Review',
            'task' => $task,
        ]);

        curl_exec($curl);
        curl_close($curl);
        
        $this->codeReviewModel->create($task, $name);
        $this->response->redirect($this->helper->url->to('TaskViewController', 'show', array('task_id' => $task)), true);
    }
    
    public function remove()
    {
        $id = $this->request->getStringParam('code_review_id');

        $curl = curl_init(env('GESTAOSISTEMAS_API').'/remover');
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_POSTFIELDS , ['id' => $id]);
        curl_exec($curl);
        curl_close($curl);
        
        $this->codeReviewModel->delete($id);
        $this->response->redirect($this->helper->url->to('CodeReviewController', 'index', array('plugin' => 'ctec')), true);
    }
}