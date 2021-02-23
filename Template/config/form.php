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
    </fieldset>


    <div class="form-actions">
        <button type="submit" class="btn btn-blue"><?= t('Save') ?></button>
    </div>
</form>
