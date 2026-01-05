<div class="page-header">
    <h2>Definições plugin Ctec</h2>
</div>
<form method="post" action="<?= $this->url->href('SettingController', 'save', array('plugin' => 'ctec', 'redirect' => 'form')) ?>" autocomplete="off">
    <?= $this->form->csrf() ?>

    <fieldset>
        <?= $this->form->label('ID Projeto Sprint', 'project_id') ?>
        <?= $this->form->text('project_id', $values, $errors, array('placeholder="32"')) ?>

        <?= $this->form->label('Query Sprint Atual', 'query_current_sprint') ?>
        <?= $this->form->text('query_current_sprint', $values, $errors, array('placeholder="column:Andamento"')) ?>
        
        <?= $this->form->label('URL API Gestão de Sistemas', 'gestaosistemas_api') ?>
        <?= $this->form->text('gestaosistemas_api', $values, $errors, array('placeholder="http://dsv.pmcanoas.rs.gov.br/gestaosistemas/gamification/api/auto-registrar"')) ?>

        <?= $this->form->label('Cartas Planning Poker', 'planning_poker_cards') ?>
        <?= $this->form->textarea('planning_poker_cards', $values, $errors, array('placeholder="0=>#888B8D|1=>#00B5E2|2=>#00AF66|3=>#FFD700|5=>#FF6900|8=>#7C3F98|13=>#C8102E"')) ?>
    
        <?= $this->form->label('URL Servidor Planning Poker', 'planning_poker_server_url') ?>
        <?= $this->form->text('planning_poker_server_url', $values, $errors, array('placeholder="ws://localhost:8080"')) ?>
    </fieldset>


    <div class="form-actions">
        <button type="submit" class="btn btn-blue"><?= t('Save') ?></button>
    </div>
</form>
