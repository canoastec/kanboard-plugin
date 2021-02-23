<?php

namespace Kanboard\Plugin\Ctec\Controller;

require __DIR__.'/../vendor/autoload.php';

use Kanboard\Controller\BaseController;

class SettingController extends BaseController 
{

    public function form()
    {
        $data['title'] = t('Definições plugin Ctec');
        $data['user'] = $this->getUser();
        $this->response->html($this->helper->layout->config('ctec:config/form', array(
            'title' => $data['title'],
            'user' => $data['user']
        )));
    }

    public function save()
    {
        $values =  $this->request->getValues();
        $redirect = $this->request->getStringParam('redirect');

        if ($this->configModel->save($values)) {
            $this->languageModel->loadCurrentLanguage();
            $this->flash->success(t('Settings saved successfully.'));
        } else {
            $this->flash->failure(t('Unable to save your settings.'));
        }

        $this->response->redirect($this->helper->url->to('SettingController', $redirect, array('plugin' => 'ctec')));
    }
}