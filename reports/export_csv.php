<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Authentication Guard: Admin access required
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date   = $_GET['end_date'] ?? date('Y-m-t');

// Fetch orders in date range
$stmt = $pdo->prepare("
    SELECT id, customer_name, phone, address, total_price, created_at 
    FROM orders 
    WHERE DATE(created_at) BETWEEN ? AND ? 
    ORDER BY created_at ASC
");
$stmt->execute([$start_date, $end_date]);
$orders = $stmt->fetchAll();

// Set HTTP headers for file download
$filename = "sales_report_" . $start_date . "_to_" . $end_date . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Open output stream
$output = fopen('php://output', 'w');

// Write CSV Column Headers
fputcsv($output, ['Order ID', 'Customer Name', 'Phone Number', 'Delivery Address', 'Total Price (PHP)', 'Order Date']);

// Write Order Rows
foreach ($orders as $order) {
    fputcsv($output, [
        $order['id'],
        $order['customer_name'],
        $order['phone'],
        $order['address'],
        number_format($order['total_price'], 2, '.', ''),
        $order['created_at']
    ]);
}

fclose($output);
exit;
?>
