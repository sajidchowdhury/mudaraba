<?php


// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

        session_start();


    header('Content-Type: application/json');

    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

// Get and sanitize inputs
$employee_id     = $_POST['employee_id'] ?? '';
$menu_id         = $_POST['menu_id'] ?? '';
$permission_type = $_POST['permission_type'] ?? '';
$status          = $_POST['status'] ?? '';



// Basic validation
if ($employee_id === '' || $menu_id === '' || $permission_type === '') {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit;
}

    include "autoloader.inc.php";



try {
    $permission = new User();

    // Call backend method
    $result = $permission->addUserPermission($employee_id, $menu_id, $permission_type, $status);

    if ($result) {
        echo json_encode([
            'status' => 'success',
            'message' => ucfirst($permission_type) . ' permission ' . ($status == 1 ? 'granted' : 'revoked')
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to update permission'
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
