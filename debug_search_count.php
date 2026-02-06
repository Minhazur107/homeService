<?php
require_once 'includes/functions.php';

// Simulate empty search
$searchQuery = '';
$categoryId = 0;
$location = '';
$minPrice = 0;
$maxPrice = 1000000;
$minRating = 0;

$sql = "SELECT sp.*, sc.name as category_name, sc.name_bn as category_name_bn, 
        COALESCE(AVG(r.rating), 0) as avg_rating, COUNT(DISTINCT r.id) as review_count,
        COUNT(DISTINCT b.id) as booking_count
        FROM service_providers sp
        LEFT JOIN service_categories sc ON sp.category_id = sc.id
        LEFT JOIN reviews r ON sp.id = r.provider_id
        LEFT JOIN bookings b ON sp.id = b.provider_id
        WHERE sp.verification_status = 'verified' AND sp.is_active = 1";

$sql .= " GROUP BY sp.id";
$sql .= " ORDER BY avg_rating DESC, review_count DESC";

echo "Running SQL: $sql\n";

$providers = fetchAll($sql);

echo "Total Providers Found: " . count($providers) . "\n";

foreach ($providers as $p) {
    echo "Provider: " . $p['name'] . " (Cat ID: " . $p['category_id'] . ")\n";
}

// Check count of verified providers in DB directly
$rawCount = fetchOne("SELECT COUNT(*) as c FROM service_providers WHERE verification_status = 'verified' AND is_active = 1");
echo "Raw Count in DB: " . $rawCount['c'] . "\n";

?>
