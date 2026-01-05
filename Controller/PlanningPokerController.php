<?php

namespace Kanboard\Plugin\Ctec\Controller;

require __DIR__.'/../vendor/autoload.php';

use Kanboard\Controller\BaseController;
use Kanboard\Plugin\Ctec\Model\PlanningPokerModel;

class PlanningPokerController extends BaseController 
{
    public function index()
    {
        define('DEVELOPER_GROUP', 8);
        define('ANALYST_GROUP', 5);
        define('LEADER_GROUP', 15);
        $data['title'] = t('Planning Poker');
        $data['user'] = $this->getUser();
        $userId = $this->userSession->getId();
        $groups = $this->groupMemberModel->getGroups($userId);
        $isDeveloper = false;
        $isLeader = false;
        $isAnalyst = false;
        foreach ($groups as $group) {
            if($group['id'] == DEVELOPER_GROUP){
                $isDeveloper = true;
            }
            if($group['id'] == LEADER_GROUP){
                $isLeader = true;
            }
            if($group['id'] == ANALYST_GROUP){
                $isAnalyst = true;
            }
        }
        $planningPokerCards = $this->configModel->getOption('planning_poker_cards');
        $planningPokerServerUrl = $this->configModel->getOption('planning_poker_server_url');
        $this->hook->on('template:layout:css', array('template' => 'plugins/Ctec/Asset/Css/style.css'));
        $this->hook->on('template:layout:js', array('template' => 'plugins/Ctec/Asset/Js/planning-poker-adapter.js'));
        $this->response->html($this->helper->layout->app('ctec:dashboard/planningpoker', array(
            'title' => $data['title'],
            'user' => $data['user'],
            'planningPokerCards' => $planningPokerCards,
            'planningPokerServerUrl' => $planningPokerServerUrl,
            'role' => $isLeader ? 'LEADER' : ($isAnalyst ? 'ANALYST' : ($isDeveloper ? 'DEVELOPER' : 'GUEST')),
        )));

    }

    public function updateScore()
    {
        try {
            $values = $this->request->getJson();

            if (empty($values) || !isset($values['task_id']) || !isset($values['score'])) {
                return $this->response->json(array(
                    'success' => false,
                    'message' => 'Parâmetros task_id e score são obrigatórios'
                ));
            }

            $taskId = intval($values['task_id']);
            $score = intval($values['score']);
            $isNextSprint = intval($values['next_sprint']) === 1;

            $planningPokerModel = new PlanningPokerModel($this->container);
            $result = $planningPokerModel->updateTaskScore($taskId, $score);
            $planningPokerModel->attachSprint($taskId, $isNextSprint);

            if ($result) {
                return $this->response->json(array(
                    'success' => true,
                    'message' => 'Score atualizado com sucesso'
                ));
            }

            return $this->response->json(array(
                'success' => false,
                'message' => 'Falha ao atualizar o score'
            ));
        } catch (\Exception $e) {
            return $this->response->json(array(
                'success' => false,
                'message' => 'Erro: ' . $e->getMessage()
            ));
        }
    }

    public function proxyTicket()
    {
        try {
            $taskId = $this->request->getIntegerParam('task_id');

            if (empty($taskId)) {
                return $this->response->html('<div class="error-ticket"><div class="error-icon">⚠️</div><div class="error-message">ID do ticket não fornecido</div></div>', 400);
            }

            $url = "https://sistemas.canoas.rs.gov.br/kanboard/?controller=TaskViewController&action=show&task_id=" . $taskId;

            // Inicializar cURL
            $ch = curl_init($url);
            
            // Copiar cookies da sessão atual se existirem
            $cookies = '';
            if (isset($_COOKIE)) {
                foreach ($_COOKIE as $key => $value) {
                    $cookies .= $key . '=' . $value . '; ';
                }
            }

            curl_setopt_array($ch, array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_COOKIE => $cookies,
                CURLOPT_HTTPHEADER => array(
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language: pt-BR,pt;q=0.9,en;q=0.8',
                )
            ));

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                return $this->response->html(
                    '<div class="error-ticket"><div class="error-icon">⚠️</div><div class="error-message">Erro ao carregar: ' . htmlspecialchars($error) . '</div></div>',
                    500
                );
            }

            if ($httpCode !== 200) {
                return $this->response->html(
                    '<div class="error-ticket"><div class="error-icon">⚠️</div><div class="error-message">Erro HTTP: ' . $httpCode . '</div><a href="' . $url . '" target="_blank" class="btn-open-external">Abrir em nova aba</a></div>',
                    $httpCode
                );
            }

            // Retornar o HTML
            return $this->response->html($response);

        } catch (\Exception $e) {
            return $this->response->html(
                '<div class="error-ticket"><div class="error-icon">⚠️</div><div class="error-message">Erro: ' . htmlspecialchars($e->getMessage()) . '</div></div>',
                500
            );
        }
    }
}