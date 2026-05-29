<?php
require_once "db.php";

// Read raw JSON from fetch()
$input = file_get_contents("php://input");
$data = json_decode($input, true);

// Validate data
if (!$data || !isset($data['item_id'])) {
    echo json_encode([
        "success" => false,
        "error" => "Invalid input."
    ]);
    exit;
}

$item_id  = $data["item_id"];
$question = $data["question"] ?? "";
$answer   = $data["answer"] ?? "";
$options  = $data["options"] ?? null; // may be null for non-MC items

// Convert options array → JSON string for database
$options_json = $options ? json_encode($options) : null;

try {
    // Update query (supports any item type)
    $stmt = $pdo->prepare("
        UPDATE pretest_items 
        SET question = ?, answer = ?, options = ? 
        WHERE item_id = ?
    ");

    $stmt->execute([$question, $answer, $options_json, $item_id]);

    echo json_encode(["success" => true]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}
?>
