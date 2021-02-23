<?php

namespace Kanboard\Plugin\Ctec;

use Kanboard\Core\Translator;
use Kanboard\Core\Plugin\Base;
use Kanboard\Plugin\Ctec\Api\Procedure\CodeReviewProcedure;

class Plugin extends Base
{
    public function initialize()
    {
        $this->setContentSecurityPolicy(array('script-src' => "'self' 'unsafe-inline' 'unsafe-eval'"));
        $this->hook->on('template:layout:js', array('template' => 'plugins/Ctec/dist/all.js'));

        $this->template->hook->attach('template:config:sidebar', 'ctec:config/sidebar');
        $this->template->hook->attach('template:dashboard:sidebar', 'ctec:dashboard/sidebar');
        $this->template->hook->attach('template:task:details:third-column', 'ctec:dashboard/task');

        $this->api->getProcedureHandler()->withClassAndMethod('getAllTaskOfSprintWithCodeReview', new CodeReviewProcedure($this->container), 'getAllTaskOfSprintWithCodeReview');
    }

    public function getClasses()
    {
        return array(
            'Plugin\Ctec\Controller' => array(
                'CodeReviewController',
                'DashboardController',
                'SettingController'
            ),
            'Plugin\Ctec\Model' => array(
                'CodeReviewModel'
            )
        );
    }

    public function onStartup()
    {
        Translator::load($this->languageModel->getCurrentLanguage(), __DIR__.'/Locale');
    }

    public function getPluginName()
    {
        return 'Ctec';
    }

    public function getPluginDescription()
    {
        return t('My plugin is awesome');
    }

    public function getPluginAuthor()
    {
        return 'Ctec';
    }

    public function getPluginVersion()
    {
        return '1.0.0';
    }

    public function getPluginHomepage()
    {
        return 'https://github.com/canoastec/kanboard-plugin';
    }
}