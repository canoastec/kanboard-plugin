<table>
    <tr>
        <td>Ticket</td>
        <td>Desenvolvedor</td>
        <td>Ação</td>
    </tr>
    <?php foreach ($reviews as $review) { ?>
        <tr>
            <td><?= $this->url->link('#'.$review['task_id'], 'TaskViewController', 'show', array('task_id' => $review['task_id'])); ?>
            <td><?= $review['name']; ?></td>
            <?php 
                if($user['username'] == $review['name']){ 
                    $params =  array(
                        'plugin' => 'ctec', 
                        'code_review_id' => $review['id'], 
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