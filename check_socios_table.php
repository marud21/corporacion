<?php
require_once 'includes/database/Database.php';

$db = new Database();
$conn = $db->getConnection();
$result = $conn->query('DESCRIBE socios');

echo "Estructura de tabla socios:\n";
echo str_repeat("-", 80) . "\n";
foreach($result as $row) {
    printf("%-20s %-20s %-10s %-5s %-20s\n", $row['Field'], $row['Type'], $row['Null'], $row['Key'], $row['Default']);
}
?>
