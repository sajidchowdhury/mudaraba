<?php
session_start();
header('Content-Type: application/json');

try {
    // ------------------ Validate form token ------------------
    $form_token = $_POST['form_token'] ?? '';
    if (!isset($_SESSION['form_tokens'][$form_token])) {
        throw new Exception('Double submission detected');
    }
    unset($_SESSION['form_tokens'][$form_token]);

    // ------------------ Validate CSRF token ------------------
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!isset($_SESSION['csrf_token']) || $_SESSION['csrf_token'] !== $csrf_token) {
        throw new Exception('Invalid CSRF token');
    }

    $profit_month = $_POST['profit_month'] ?? '';
    $related_id   = $_POST['related_id'] ?? 'New';
    $items        = json_decode($_POST['items'] ?? '[]', true);

    if (!$profit_month || !is_array($items)) {
        throw new Exception('Missing or invalid input data');
    }

    include "autoloader.inc.php";

    // --- Step 1: Process SectorProfit + AutoSave together ---
    $action = new SectorProfitContr($profit_month, $items, $related_id);
    $actionResult = $action->Action();

    echo json_encode($actionResult, JSON_PRETTY_PRINT);
    exit;

} catch (Throwable $e) {
    error_log("SectorProfit POST failed: " . $e->getMessage());

    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'debug' => []
    ], JSON_PRETTY_PRINT);
    exit;
}
