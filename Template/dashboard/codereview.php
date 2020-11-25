<table>
    <tr>
        <td>Ticket</td>
        <td>Desenvolvedor</td>
    </tr>
    <?php foreach ($reviews as $review) { ?>
        <tr>
            <td><?=  $this->url->link('#'.$review['task_id'], 'TaskViewController', 'show', array('task_id' => $review['task_id'])) ?>
            <td><?=$review['name']?></td>
        </tr>
    <?php } ?>
</table>