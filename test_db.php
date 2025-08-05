<?php
// Database connection test file
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Database Connection Test</h2>";

// Test different connection methods
$connections = [
    [
        'name' => 'Your Current Credentials',
        'host' => 'localhost',
        'user' => 'ux7p426rgegze',
        'pass' => 'd14cdc)L61_$',
        'db' => 'dbgk2bqd1ybqww'
    ],
    [
        'name' => 'Alternative Hosting',
        'host' => 'localhost',
        'user' => 'ux7p426rgegze',
        'pass' => 'd14cdc)L61_$',
        'db' => 'ux7p426rgegze_netflix'
    ],
    [
        'name' => 'Local MySQL',
        'host' => 'localhost',
        'user' => 'root',
        'pass' => '',
        'db' => 'netflix_clone'
    ]
];

foreach ($connections as $config) {
    echo "<h3>Testing: {$config['name']}</h3>";
    
    try {
        $conn = new mysqli($config['host'], $config['user'], $config['pass'], $config['db']);
        
        if ($conn->connect_error) {
            echo "<p style='color: red;'>❌ Connection failed: " . $conn->connect_error . "</p>";
        } else {
            echo "<p style='color: green;'>✅ Connection successful!</p>";
            echo "<p>Server info: " . $conn->server_info . "</p>";
            echo "<p>Database: " . $config['db'] . "</p>";
            
            // Test if tables exist
            $result = $conn->query("SHOW TABLES");
            if ($result) {
                echo "<p>Tables found: " . $result->num_rows . "</p>";
                while ($row = $result->fetch_array()) {
                    echo "- " . $row[0] . "<br>";
                }
            }
            
            $conn->close();
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Exception: " . $e->getMessage() . "</p>";
    }
    
    echo "<hr>";
}

echo "<h3>Instructions:</h3>";
echo "<ol>";
echo "<li>Get your database credentials from your hosting provider</li>";
echo "<li>Update the credentials in db_connect.php</li>";
echo "<li>Import the netflix_database.sql file to create tables</li>";
echo "<li>Test the upload functionality again</li>";
echo "</ol>";

echo "<h3>Common Hosting Database Settings:</h3>";
echo "<ul>";
echo "<li><strong>Host:</strong> Usually 'localhost'</li>";
echo "<li><strong>Username:</strong> Your hosting account username (often starts with your hosting username)</li>";
echo "<li><strong>Password:</strong> Database password (different from hosting password)</li>";
echo "<li><strong>Database:</strong> Database name (often starts with your hosting username)</li>";
echo "</ul>";
?> 