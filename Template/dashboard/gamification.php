<link rel="stylesheet" href="https://sistemas.canoas.rs.gov.br/idvisual/dist/css/main.css">

<p style="font-weight: bold;font-size: 2em;color: #f7b800;">
    <i class="fa fa-trophy" aria-hidden="true"></i> Ranking do mês <?= utf8_encode($month); ?>
</p>
<table class="table-canoastec">
    <thead>
        <tr>
            <th>#</th>
            <th>Desenvolvedor</th>
            <th>Pontos</th>
        </tr>
    </thead>
    <?php $position = 0; ?>
    <?php foreach ($list as $name => $score) { ?>
        <?php $position++; ?>
        <tr>
            <td><?= $position; ?></td>
            <td><?= ucwords(str_replace('.', ' ', $name)); ?></td>
            <td><?= $score; ?></td>
        </tr>
    <?php } ?>
</table>
<br>
<div>
    <h3>Regras de pontuação</h3>
    <p>Serão contabilizados apenas tickets da coluna pronto adiante, dentro do vigente.</p>

    <h4>Tickets de Feature(Laranja) executados</h4>
    <ul style="margin-left: 20px;">
        <li>
            <div style="display: flex;">
                <div style="width: 35px;">XP</div>
                <div>- 04 Pontos</div>
            </div>
            
        </li>
        <li>
            <div style="display: flex;">
                <div style="width: 35px;">PP</div>
                <div>- 08 Pontos</div>
            </div>
            
        </li>
        <li>
            <div style="display: flex;">
                <div style="width: 35px;">P</div>
                <div>- 16 Pontos</div>
            </div>
            
        </li>
        <li>
            <div style="display: flex;">
                <div style="width: 35px;">M</div>
                <div>- 24 Pontos</div>
            </div>
            
        </li>
        <li>
            <div style="display: flex;">
                <div style="width: 35px;">G</div>
                <div>- 32 Pontos</div>
            </div>
            
        </li>
        <li>
            <div style="display: flex;">
                <div style="width: 35px;">GG</div>
                <div>- 40 Pontos</div>
            </div>
            
        </li>

    </ul>

    <h4>Tickets de Bug(Vermelho) executados</h4>
    <ul style="margin-left: 20px;">
        <li>
            08 pontos
        </li>
    </ul>

    <h4>Tickets de Processo(Azul) executados</h4>
    <ul style="margin-left: 20px;">
        <li>
            24 pontos
        </li>
    </ul>
    
    <h4>CodeReview executado</h4>
    <ul style="margin-left: 20px;">
        <li>
            02 pontos
        </li>
    </ul>

</div>
