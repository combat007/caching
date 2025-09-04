<?php
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

// Allow requests from any origin (for development only, restrict in production)
header("Access-Control-Allow-Origin: *");

// Allow common methods
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

// Allow these headers from client requests
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Handle OPTIONS requests (preflight)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    // Return only the headers and terminate the script for preflight
    exit(0);
}

header('Content-Type: application/json');

$redis = new Redis();
try {
    $redis->connect('192.168.1.5', 6379);
    // echo "Connected to Redis successfully.";
} catch (Exception $e) {
    echo "Failed to connect to Redis: " . $e->getMessage();
}

$cacheKey = 'online_count_today';
$cachedCount = $redis->get($cacheKey);

if ($cachedCount !== false) {
    echo json_encode(['count' => (int)$cachedCount]);
    exit;
}

// Connect to PostgreSQL
$conn = pg_connect("host=192.168.1.5 dbname=tags user=postgres password=redhat");
if (!$conn) {
    echo json_encode(['error' => 'DB connection failed']);
    exit;
}

// Query to count online participants today
$query = "SELECT COUNT(*) as count FROM raw_data";
$result = pg_query($conn, $query);

if (!$result) {
    echo json_encode(['error' => 'Query failed']);
    exit;
}

$row = pg_fetch_assoc($result);
$count = (int)$row['count'];

// Cache count in Redis for 1 hour (3600 seconds)
$redis->setex($cacheKey, 30, $count);

echo json_encode(['count' => $count]);
?>
