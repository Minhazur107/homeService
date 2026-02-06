<?php
// Temporary script to create missing tables
// Run this once, then delete the file

require_once 'config/database.php';

try {
    $pdo = getDBConnection();
    
    // Read the SQL file
    $sql = file_get_contents('add_missing_tables.sql');
    
    // Execute the SQL
    $pdo->exec($sql);
    
    echo "Successfully created missing tables!\n";
    echo "You can now delete this file (run_sql.php).\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
