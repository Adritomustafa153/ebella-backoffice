<?php
require_once 'db_config.php';

header('Content-Type: application/json');

$query = isset($_GET['q']) ? $_GET['q'] : '';

if (strlen($query) < 2) {
    echo json_encode([]);
    exit();
}

$sql = "SELECT product_id, name, product_code, product_category 
        FROM products 
        WHERE (name LIKE ? OR product_code LIKE ? OR product_category LIKE ?) 
        AND is_active = 1 
        LIMIT 10";
$stmt = $conn->prepare($sql);
$search_param = "%$query%";
$stmt->bind_param("sss", $search_param, $search_param, $search_param);
$stmt->execute();
$result = $stmt->get_result();

$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

$stmt->close();
$conn->close();

echo json_encode($products);
?>