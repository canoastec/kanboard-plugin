<?php
$codeReview = $this->model->codeReviewModel->findByTask($task['id']);
$user = session_get('user');

if (empty($codeReview)) {
    $label = "Designar Code Review para ". $this->user->getFullname();
    $params =  array(
        'plugin' => 'ctec', 
        'task' => $task['id'], 
        'name' => $user['username']
    );
    echo $this->url->link($label, 'CodeReviewController',  'new', $params);
} else {
    echo "<strong>Code Review designado: </strong>".$codeReview['name'];
}
?>
