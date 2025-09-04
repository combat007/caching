<?php
$redis = new Redis();
$redis->connect('192.168.1.5', 6379);

// Delete the cache key
$redis->del('online_count_today');
?>