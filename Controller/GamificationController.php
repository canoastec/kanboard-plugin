<?php

namespace Kanboard\Plugin\Ctec\Controller;

require __DIR__.'/../vendor/autoload.php';
use Kanboard\Controller\BaseController;

class GamificationController extends BaseController 
{

    public function index()
    {
      
        $list = collect($this->gamificationModel->all());
        $scoresByDelivery = $list->groupBy('username')->map(function($dev) {
            return $dev->sum(function($item) {
                $x = 1;
                // if($item['username'] == 'daniel.cornely'){
                //     $x = 0.8;
                // }
                // if($item['username'] == 'gustavo.bsantos'){
                //     $x = 0.8;
                // }
                // if($item['username'] == 'eduardo.dasilva'){
                //     $x = 0.8;
                // }
                if($item['category'] == 'Process') return 24 * $x;
                if($item['category'] == 'Bug') return 8 * $x;
                return $item['time_estimated'] * $x;
            });
        });

        $scoresByCodeReview = collect();
        // $scoresByCodeReview = $list->groupBy('codereview_username')->map(function($dev) {
        //     return $dev->count() * 2;
        // });
       
        $scoresByPairProgramming = collect();
        $scoresByPairProgramming = $list->groupBy('pairprogramming_username')->map(function($dev) {
            return $dev->sum(function($item) {
                if($item['category'] == 'Process') return 24;
                if($item['category'] == 'Bug') return 8;
                return $item['time_estimated'];
            });
        });
        $scoresTotal = collect([
            // "eduardo.souza" => "eduardo.dasilva",
            'daniel.cornely' => 'daniel.cornely', 
            'fabiano.carreires' => 'fabiano.carreires', 
            'pedro.silveira' => 'pedro.silveira', 
            'franklin.bueno' => 'franklin.bueno', 
            'gustavo.santos' => 'gustavo.bsantos', 
            // 'rafael.marques' => 'rafael.marques',
            'ramiro.vargas' => 'ramiro.vargas',
            "lucas.cunha" => "lucas.cunha",
            "lucas.rocha" => "lucas.rocha",
            "marlon.bueno" => "marlon.bueno",
            'matheus.freitas' => 'matheus.freitas',
        ])->map(function($key) use($scoresByCodeReview, $scoresByPairProgramming, $scoresByDelivery) { 
            return $scoresByDelivery->get($key) + $scoresByCodeReview->get($key) + $scoresByPairProgramming->get($key);
        });

        setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'pt_BR.utf-8', 'portuguese');
        date_default_timezone_set('America/Sao_Paulo');
        $month =  strftime('%B', ( mktime(0, 0, 0, date('m'), 0, date('Y'))));
        $this->response->html($this->helper->layout->dashboard('ctec:dashboard/gamification', array(
            'title' => 'Gamificação',
            'user' => $this->getUser(),
            'list' => $scoresTotal->sortDesc(),
            'month' => $month
        )));
    }

  
}