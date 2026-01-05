<?php if ($paginator->isEmpty()): ?>
    <p class="alert"><?= t('No code reviews found.') ?></p>
<?php else: ?>
    <table>
        <tr>
            <td>Ticket</td>
            <td>Desenvolvedor</td>
            <td>Ação</td>
        </tr>
        <?php foreach ($paginator->getCollection() as $review) { ?>
            <tr>
                <td><?= $this->url->link('#'.$review['task_id'], 'TaskViewController', 'show', array('task_id' => $review['task_id'])); ?>
                <td><?= $review['name']; ?></td>
                <?php 
                    if($user['username'] == $review['name'] || $user['username'] == 'admin'){ 
                        $params =  array(
                            'plugin' => 'ctec', 
                            'code_review_id' => $review['id'], 
                            'task' => $review['task_id'], 
                            'name' => $user['username']
                        );
                ?>
                    <td><?= $this->url->link("Remover", 'CodeReviewController', 'remove', $params); ?></td>
                <?php }else{ ?>
                    <td></td>
                <?php } ?>
            </tr>
        <?php } ?>
    </table>

    <?= $paginator ?>
<?php endif ?>