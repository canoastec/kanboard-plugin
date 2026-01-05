<?php
$codeReview = $this->model->codeReviewModel->findByTask($task['id']);
$pairProgramming = $this->model->pairProgrammingModel->findByTask($task['id']);
$user = session_get('user');

echo '<li>';
if (empty($codeReview)) {
    $label = "Atribuir-me";
    $params =  array(
        'plugin' => 'ctec', 
        'task' => $task['id'], 
        'name' => $user['username']
    );
    echo "<strong>Code Review: </strong>".$this->url->link($label, 'CodeReviewController',  'new', $params);
} else {
    echo "<strong>Code Review designado: </strong>".$codeReview['name'];

    if($user['username'] == $codeReview['name']){
        $params =  array(
            'plugin' => 'ctec', 
            'task' => $task['id'], 
            'name' => $user['username'],
            'code_review_id' => $codeReview['id']
        );
        echo ' - '.$this->url->link('Remover', 'CodeReviewController',  'remove', $params);
    }
}
echo '</li>';

echo '<li>';
if (empty($pairProgramming)) {
    $label = "Atribuir-me";
    $params =  array(
        'plugin' => 'ctec', 
        'task' => $task['id'], 
        'assignee' => $task['assignee_username'],
        'name' => $user['username'],
    );
    echo "<strong>Pareamento: </strong>".$this->url->link($label, 'PairProgrammingController',  'new', $params);
} else {
    echo "<strong>Pareado com: </strong>".$pairProgramming['name'];

    if($user['username'] == $pairProgramming['name']){
        $params =  array(
            'plugin' => 'ctec', 
            'task' => $task['id'], 
            'name' => $user['username'],
            'pair_programming_id' => $pairProgramming['id']
        );
        echo ' - '.$this->url->link('Remover', 'PairProgrammingController',  'remove', $params);
    }
}
echo '</li>';
?>
