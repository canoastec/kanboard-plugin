<?php if ($paginator->isEmpty()): ?>
    <p class="alert"><?= t('No pair programming sessions found.') ?></p>
<?php else: ?>
    <table>
        <tr>
            <td>Ticket</td>
            <td>Desenvolvedor 1</td>
            <td>Desenvolvedor 2</td>
            <td>Ação</td>
        </tr>
        <?php foreach ($paginator->getCollection() as $pairProgramming) { ?>
            <tr>
                <td><?= $this->url->link('#'.$pairProgramming['task_id'], 'TaskViewController', 'show', array('task_id' => $pairProgramming['task_id'])); ?>
                <td><?= $pairProgramming['name']; ?></td>
                <td><?= $pairProgramming['assignee']; ?></td>
                <?php 
                    if($user['username'] == $pairProgramming['name'] || $user['username'] == $pairProgramming['assignee'] || $user['username'] == 'admin'){ 
                        $params =  array(
                            'plugin' => 'ctec', 
                            'pair_programming_id' => $pairProgramming['id'], 
                            'task' => $pairProgramming['task_id'], 
                            'name' => $user['username']
                        );
                ?>
                    <td><?= $this->url->link("Remover", 'PairProgrammingController', 'remove', $params); ?></td>
                <?php }else{ ?>
                    <td></td>
                <?php } ?>
            </tr>
        <?php } ?>
    </table>

    <?= $paginator ?>
<?php endif ?>