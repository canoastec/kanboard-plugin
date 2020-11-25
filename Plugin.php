<?php

namespace Kanboard\Plugin\Ctec;

use Kanboard\Core\Plugin\Base;
use Kanboard\Core\Translator;

class Plugin extends Base
{
    public function initialize()
    {
        $this->setContentSecurityPolicy(array('script-src' => "'self' 'unsafe-inline' 'unsafe-eval'"));
        $this->hook->on('template:layout:js', array('template' => 'plugins/ctec/dist/all.js'));

        $this->template->hook->attach('template:dashboard:sidebar', 'ctec:dashboard/sidebar');
        $this->template->hook->attach('template:task:details:third-column', 'ctec:dashboard/task');
    }

    public function getClasses()
    {
        return array(
            'Plugin\Ctec\Controller' => array(
                'CodeReviewController',
                'DashboardController'
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

