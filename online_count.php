<?php
header('Content-Type: application/json');

$redis = new Redis();
$redis->connect('127.0.0.1', 6379);

$cacheKey = 'online_count_today';
$cachedCount = $redis->get($cacheKey);

if ($cachedCount !== false) {
    echo json_encode(['count' => (int)$cachedCount]);
    exit;
}

// Connect to PostgreSQL
$conn = pg_connect("host=localhost dbname=yourdb user=youruser password=yourpass");
if (!$conn) {
    echo json_encode(['error' => 'DB connection failed']);
    exit;
}

// Query to count online participants today
$query = "SELECT COUNT(DISTINCT participant_id) as count FROM raw_data WHERE online_status=1 AND created_at::date = CURRENT_DATE";
$result = pg_query($conn, $query);

if (!$result) {
    echo json_encode(['error' => 'Query failed']);
    exit;
}

$row = pg_fetch_assoc($result);
$count = (int)$row['count'];

// Cache count in Redis for 1 hour (3600 seconds)
$redis->setex($cacheKey, 3600, $count);

echo json_encode(['count' => $count]);
?>
