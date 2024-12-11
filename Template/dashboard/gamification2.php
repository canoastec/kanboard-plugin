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
            <th>Time</th>
        </tr>
    </thead>
    <?php 
        $position = 0; 

        $somaABC = 0;
        $somaXYZ = 0;
    ?>
    <?php foreach ($list as $name => $score) { ?>
        <?php $position++; ?>
        <tr
            <?
                switch ($name) {
                    case "daniel.cornely": echo " style='background: green;'"; break;
                    case "pedro.silveira": echo "style='background: yellow;'"; break;
                    case "fabiano.carreires": echo "style='background: yellow;'"; break;
                    case "franklin.bueno": echo " style='background: green;'"; break;
                    case "gustavo.santos": echo " style='background: green;'"; break;
                    case "rafael.marques": echo " style='background: green;'"; break;
                    case "ramiro.vargas": echo " style='background: green;'"; break;
                    case "lucas.cunha": echo " style='background: green;'"; break;
                    case "lucas.rocha": echo "style='background: yellow;'"; break;
                    case "marlon.bueno": echo "style='background: yellow;'"; break;
                    case "matheus.freitas": echo "style='background: yellow;'"; break;
                }
            ?>
        >
            <td><?= $position; ?></td>
            <td><?= ucwords(str_replace('.', ' ', $name)); ?></td>
            <td><?= $score; ?></td>
            <td>
                <?
                    switch ($name) {
                        case "daniel.cornely": $somaABC += $score; echo "Time ABC"; break;
                        case "fabiano.carreires": $somaXYZ += $score; echo "Time XYZ"; break;
                        case "pedro.silveira": $somaXYZ += $score; echo "Time XYZ"; break;
                        case "franklin.bueno": $somaABC += $score; echo "Time ABC"; break;
                        case "gustavo.santos": $somaABC += $score; echo "Time ABC"; break;
                        case "rafael.marques": $somaABC += $score; echo "Time ABC"; break;
                        case "ramiro.vargas": $somaABC += $score; echo "Time ABC"; break;
                        case "lucas.cunha": $somaABC += $score; echo "Time ABC"; break;
                        case "lucas.rocha": $somaXYZ += $score; echo "Time XYZ"; break;
                        case "marlon.bueno": $somaXYZ += $score; echo "Time XYZ"; break;
                        case "matheus.freitas": $somaXYZ += $score; echo "Time XYZ"; break;
                    }
                ?>
            </td>
        </tr>
    <?php } ?>
</table>
<?php
// echo "soma ABC = {$somaABC}<br>";
echo "média ABC = ".($somaABC/5)."<br>";
// echo "soma XYZ = {$somaXYZ}<br>";
echo "média XYZ = ".($somaXYZ/5)."<br>";
// echo "soma = ".($somaXYZ+$somaABC)."<br>";
echo "média = ".(($somaXYZ+$somaABC)/10)."<br>";
?>
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
