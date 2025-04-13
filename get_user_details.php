<?php
include 'db.php';

function get_users_list($request) {
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
    $search = isset($request['search']) ? $request['search'] : "";
    $page = isset($request['page']) ? (int)$request['page'] : 1;
    $limit = isset($request['limit']) ? (int)$request['limit'] : 20;
    $offset = ($page - 1) * $limit;

    // Validate user
    $query = $conn->prepare("SELECT access_token FROM `user-details` WHERE user_id = ?");
    $query->bind_param("i", $user_id);
    $query->execute();
    $result = $query->get_result();

    if ($result->num_rows === 0) {
        return json_encode(["status" => 0, "message" => "User not found."]);
    }

    $user = $result->fetch_assoc();
    if ($user['access_token'] !== $access_token) {
        return json_encode(["status" => 4, "message" => "Unauthorized access. Invalid access token."]);
    }

    // Build query
    $where = "WHERE user_id != ?";
    $params = [$user_id];
    $types = "i";

    if (!empty($search)) {
        $where .= " AND phone_number LIKE ?";
        $params[] = "%" . $search . "%";
        $types .= "s";
    }

    // Count total
    $count_sql = "SELECT COUNT(*) AS total FROM `user-details` $where";
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param($types, ...$params);
    $count_stmt->execute();
    $total_result = $count_stmt->get_result();
    $total_users = $total_result->fetch_assoc()['total'];
    $total_pages = ceil($total_users / $limit);

    // Fetch users
    $sql = "SELECT * FROM `user-details` $where LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $users_details = [];
    while ($row = $result->fetch_assoc()) {
        unset($row['access_token']); // Hide token
        $users_details[] = $row;
    }

    return json_encode([
        "status" => 1,
        "message" => "Success",
        "users_details" => $users_details,
        "pagination" => [
            "total_users" => $total_users,
            "total_pages" => $total_pages,
            "current_page" => $page
        ]
    ]);
}

// Handle raw JSON body
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents("php://input"), true);

    if (is_array($input)) {
        echo get_users_list($input);
    } else {
        echo json_encode([
            "status" => 0,
            "message" => "Invalid or empty JSON body."
        ]);
    }
}
?>
