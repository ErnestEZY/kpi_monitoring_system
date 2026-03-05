<?php
/**
 * Test Peer Comparison API Response
 * This helps debug what data is being returned
 */

require_once 'config/database.php';

echo "<h1>Peer Comparison API Test</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; }
    pre { background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto; }
    .success { color: green; }
    .error { color: red; }
</style>";

try {
    $pdo = getDBConnection();
    
    // Get first staff with data in 2025
    $stmt = $pdo->query("
        SELECT s.staff_id, s.name
        FROM staff s
        JOIN kpi_scores ks ON s.staff_id = ks.staff_id
        WHERE ks.evaluation_year = 2025
        GROUP BY s.staff_id
        LIMIT 1
    ");
    $testStaff = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$testStaff) {
        echo "<p class='error'>No staff found with data in 2025</p>";
        exit;
    }
    
    echo "<h2>Testing with Staff: {$testStaff['name']} (ID: {$testStaff['staff_id']})</h2>";
    
    // Simulate the API call
    $_GET['action'] = 'get_peer_comparison';
    $_GET['staff_id'] = $testStaff['staff_id'];
    $_GET['compare_with'] = 'auto';
    $_GET['year'] = 2025;
    
    // Start output buffering to capture API response
    ob_start();
    include 'api/innovative_features_api.php';
    $response = ob_get_clean();
    
    echo "<h3>API Response:</h3>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
    
    // Try to decode and display formatted
    $data = json_decode($response, true);
    
    if ($data) {
        echo "<h3>Decoded Response:</h3>";
        
        if ($data['success']) {
            echo "<p class='success'>✓ Success: true</p>";
            
            echo "<h4>Staff 1:</h4>";
            echo "<pre>" . print_r($data['data']['staff1'], true) . "</pre>";
            
            echo "<h4>Staff 2:</h4>";
            echo "<pre>" . print_r($data['data']['staff2'], true) . "</pre>";
            
            echo "<h4>Similarity:</h4>";
            echo "<p>{$data['data']['similarity']}%</p>";
            
            echo "<h4>Radar Data:</h4>";
            echo "<pre>" . print_r($data['data']['radar'], true) . "</pre>";
            
            echo "<h4>Detailed Comparison:</h4>";
            echo "<pre>" . print_r($data['data']['detailed'], true) . "</pre>";
            
            echo "<h4>Insights:</h4>";
            echo "<pre>" . print_r($data['data']['insights'], true) . "</pre>";
            
            echo "<h4>Actions:</h4>";
            echo "<pre>" . print_r($data['data']['actions'], true) . "</pre>";
            
            echo "<h4>Similar Peers:</h4>";
            echo "<pre>" . print_r($data['data']['similar_peers'], true) . "</pre>";
            
        } else {
            echo "<p class='error'>✗ Success: false</p>";
            echo "<p>Message: {$data['message']}</p>";
        }
    } else {
        echo "<p class='error'>Failed to decode JSON response</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
}
?>
