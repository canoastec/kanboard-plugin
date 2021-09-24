<li>
    <?=  $this->url->link(t('Code Review/Desenvolvedor'), 'CodeReviewController', 'index', array('plugin' => 'ctec')) ?>
</li>
<li>
    <?=  $this->url->link(t('Pareamento/Desenvolvedor'), 'PairProgrammingController', 'index', array('plugin' => 'ctec')) ?>
</li>
<li>
    <?=  $this->url->link(t('Grafico estimado x executado'), 'DashboardController', 'charts', array('plugin' => 'ctec')) ?>
</li>