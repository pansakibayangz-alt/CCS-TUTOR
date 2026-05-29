<?php
require_once 'db.php';

if(isset($_POST['feedback_id'])){

    $feedbackId = $_POST['feedback_id'];

    try {
        $stmt = $pdo->prepare("
            UPDATE feedback 
            SET reply_is_read = 1 
            WHERE feedback_id = ? AND reply_is_read = 0
        ");
        $stmt->execute([$feedbackId]);

        echo json_encode([
            'success' => true,
            'id' => $feedbackId
        ]);

    } catch(PDOException $e){
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }

} else {
    echo json_encode([
        'success' => false,
        'error' => 'No ID received'
    ]);
}