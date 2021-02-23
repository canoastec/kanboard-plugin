<li <?= $this->app->checkMenuSelection('SettingController', 'form') ?>>
    <?=  $this->url->link(t('Definições plugin Ctec'), 'SettingController', 'form', array('plugin' => 'ctec')) ?>
</li>