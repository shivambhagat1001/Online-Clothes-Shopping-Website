<?php

require_once __DIR__ . '/src/config.php';

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';charset=' . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Read the migration file
    $migration_sql = file_get_contents(__DIR__ . '/database/migration_add_payments.sql');
    
    // Remove the USE statement if database is already selected
    $migration_sql = str_replace('USE clothyyy;', '', $migration_sql);
    
    // Select the database
    $pdo->exec("USE " . DB_NAME);
    
    // Execute the migration
    $pdo->exec($migration_sql);
    
    echo "<h2>✅ Migration Successful!</h2>";
    echo "<p>The payments table has been created successfully.</p>";
    echo "<p><a href='/clothyyy/public/admin/payments.php'>Go to Payments Management</a></p>";
    
} catch (PDOException $e) {
    echo "<h2>❌ Migration Failed</h2>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>If the table already exists, this is normal. You can ignore this error.</p>";
}
?>




