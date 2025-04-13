<?php
include 'db.php';

function logout_user($request) {
    if (
        !isset($request['user_id']) ||
        !isset($request['access_token'])
    ) {
        return json_encode([
            "status" => 0,
            "message" => "Missing required fields: user_id and access_token."
        ]);
    }

    global $conn;
    $user_id = $request['user_id'];
    $access_token = $request['access_token'];

    // Check if user exists
    $query = $conn->prepare("SELECT access_token FROM `user-details` WHERE user_id = ?");
    $query->bind_param("i", $user_id);
    $query->execute();
    $result = $query->get_result();

    if ($result->num_rows === 0) {
        return json_encode([
            "status" => 0,
            "message" => "User not found."
        ]);
    }

    $user = $result->fetch_assoc();

    if ($user['access_token'] !== $access_token) {
        return json_encode([
            "status" => 4,
            "message" => "Unauthorized access. Invalid access token."
        ]);
    }

    // Option 1: Invalidate token (recommended)
    $update = $conn->prepare("UPDATE `user-details` SET access_token = NULL WHERE user_id = ?");
    $update->bind_param("i", $user_id);
    $update->execute();

    return json_encode([
        "status" => 1,
        "message" => "Logout successful."
    ]);
}

// Handle raw JSON body
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents("php://input"), true);

    if (is_array($input)) {
        echo logout_user($input);
    } else {
        echo json_encode([
            "status" => 0,
            "message" => "Invalid or empty JSON body."
        ]);
    }
}
?>
