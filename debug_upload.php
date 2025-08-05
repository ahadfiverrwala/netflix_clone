<?php
// Debug upload functionality
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

session_start();

echo "<h1>Video Upload Debug</h1>";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<p style='color: red;'>User not logged in. Please login first.</p>";
    echo "<a href='login.php'>Go to Login</a>";
    exit();
}

echo "<p style='color: green;'>✓ User logged in (ID: " . $_SESSION['user_id'] . ")</p>";

// Test database connection
try {
    include 'db_connect.php';
    echo "<p style='color: green;'>✓ Database connection successful</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database connection failed: " . $e->getMessage() . "</p>";
    exit();
}

// Check upload directories
$dirs = ['uploads', 'uploads/videos', 'uploads/thumbnails'];
foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "<p style='color: green;'>✓ Created directory: $dir</p>";
        } else {
            echo "<p style='color: red;'>✗ Failed to create directory: $dir</p>";
        }
    } else {
        if (is_writable($dir)) {
            echo "<p style='color: green;'>✓ Directory $dir exists and is writable</p>";
        } else {
            echo "<p style='color: orange;'>⚠ Directory $dir exists but is not writable</p>";
            chmod($dir, 0755);
            echo "<p>Attempted to fix permissions for $dir</p>";
        }
    }
}

// Check PHP settings
echo "<h2>PHP Settings</h2>";
echo "<p>Upload max filesize: " . ini_get('upload_max_filesize') . "</p>";
echo "<p>Post max size: " . ini_get('post_max_size') . "</p>";
echo "<p>Max execution time: " . ini_get('max_execution_time') . " seconds</p>";
echo "<p>Memory limit: " . ini_get('memory_limit') . "</p>";

// Handle file upload test
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_file'])) {
    echo "<h2>Upload Test Results</h2>";
    
    $file = $_FILES['test_file'];
    echo "<p>File name: " . $file['name'] . "</p>";
    echo "<p>File type: " . $file['type'] . "</p>";
    echo "<p>File size: " . round($file['size'] / 1024 / 1024, 2) . " MB</p>";
    echo "<p>Upload error: " . $file['error'] . "</p>";
    
    if ($file['error'] === 0) {
        $test_name = 'test_' . time() . '_' . basename($file['name']);
        $test_path = 'uploads/videos/' . $test_name;
        
        if (move_uploaded_file($file['tmp_name'], $test_path)) {
            echo "<p style='color: green;'>✓ File uploaded successfully to: $test_path</p>";
            chmod($test_path, 0644);
            
            // Test if file is readable
            if (is_readable($test_path)) {
                echo "<p style='color: green;'>✓ File is readable</p>";
            } else {
                echo "<p style='color: red;'>✗ File is not readable</p>";
            }
        } else {
            echo "<p style='color: red;'>✗ Failed to move uploaded file</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ File upload failed with error code: " . $file['error'] . "</p>";
    }
}

// Show existing files
echo "<h2>Existing Files</h2>";
$video_dir = 'uploads/videos';
if (is_dir($video_dir)) {
    $videos = glob($video_dir . '/*');
    if (count($videos) > 0) {
        echo "<p>Found " . count($videos) . " video files:</p>";
        foreach (array_slice($videos, 0, 10) as $video) {
            $size = filesize($video);
            $readable = is_readable($video) ? '✓' : '✗';
            $writable = is_writable($video) ? '✓' : '✗';
            echo "<p>$readable $writable " . basename($video) . " (" . round($size / 1024 / 1024, 2) . " MB)</p>";
        }
    } else {
        echo "<p>No video files found</p>";
    }
}

// Test upload form
echo "<h2>Test File Upload</h2>";
echo "<form method='POST' enctype='multipart/form-data'>";
echo "<input type='file' name='test_file' accept='video/*' required>";
echo "<input type='submit' value='Test Upload'>";
echo "</form>";

echo "<h2>Quick Actions</h2>";
echo "<a href='index.php'>Go to Home</a> | ";
echo "<a href='upload.php'>Go to Upload</a> | ";
echo "<a href='logout.php'>Logout</a>";
?> 