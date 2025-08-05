<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Increase upload limits
ini_set('upload_max_filesize', '200M');
ini_set('post_max_size', '200M');
ini_set('max_execution_time', 300);
ini_set('memory_limit', '256M');

session_start();

// Test database connection first with better error handling
try {
    include 'db_connect.php';
    if (!isset($conn) || $conn->connect_error) {
        throw new Exception("Database connection failed: " . ($conn->connect_error ?? 'Unknown error'));
    }
} catch (Exception $e) {
    error_log("Database Error in upload.php: " . $e->getMessage());
    die("Database connection error. Please check your database settings.");
}

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get user info
$user_id = $_SESSION['user_id'];
$user_query = $conn->prepare("SELECT name FROM users WHERE id = ?");
$user_query->bind_param("i", $user_id);
$user_query->execute();
$user_result = $user_query->get_result();
$user_name = $user_result->fetch_assoc()['name'];

$error = '';
$success = '';

// Handle video upload
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        // Debug: Log upload attempt
        error_log("Upload attempt started for user: " . $user_id);
        
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $genre = trim($_POST['genre'] ?? '');
        $user_id = $_SESSION['user_id'];

        // Validate required fields
        if (empty($title) || empty($description) || empty($genre)) {
            $error = "All fields are required.";
            error_log("Upload validation failed: Missing required fields");
        } elseif (!isset($_FILES['video']) || $_FILES['video']['error'] !== 0) {
            $error = "Please upload a video file. Error code: " . (isset($_FILES['video']) ? $_FILES['video']['error'] : 'No file');
            error_log("Video upload error: " . $error);
        } elseif (!isset($_FILES['thumbnail']) || $_FILES['thumbnail']['error'] !== 0) {
            $error = "Please upload a thumbnail image. Error code: " . (isset($_FILES['thumbnail']) ? $_FILES['thumbnail']['error'] : 'No file');
            error_log("Thumbnail upload error: " . $error);
        } else {
            $allowed_video_types = ['video/mp4', 'video/avi', 'video/mov', 'video/mpeg', 'video/webm', 'video/quicktime'];
            $allowed_image_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp', 'image/gif'];

            $video = $_FILES['video'];
            $thumbnail = $_FILES['thumbnail'];

            // Debug: Log file info
            error_log("Video file: " . $video['name'] . " (Size: " . $video['size'] . " bytes, Type: " . $video['type'] . ")");
            error_log("Thumbnail file: " . $thumbnail['name'] . " (Size: " . $thumbnail['size'] . " bytes, Type: " . $thumbnail['type'] . ")");

            if (!in_array($video['type'], $allowed_video_types)) {
                $error = "Invalid video format. Only mp4, avi, mov, mpeg, webm allowed. Got: " . $video['type'];
                error_log("Invalid video format: " . $video['type']);
            } elseif (!in_array($thumbnail['type'], $allowed_image_types)) {
                $error = "Invalid image format. Only jpg, png, webp allowed. Got: " . $thumbnail['type'];
                error_log("Invalid thumbnail format: " . $thumbnail['type']);
            } elseif ($video['size'] > 209715200) { // 200MB limit (increased)
                $error = "Video file size exceeds 200MB. Current size: " . round($video['size'] / 1048576, 2) . "MB";
                error_log("Video file too large: " . $video['size'] . " bytes");
            } elseif ($thumbnail['size'] > 10485760) { // 10MB limit (increased)
                $error = "Thumbnail file size exceeds 10MB. Current size: " . round($thumbnail['size'] / 1048576, 2) . "MB";
                error_log("Thumbnail file too large: " . $thumbnail['size'] . " bytes");
            } else {
                // Create safe filenames
                $video_name = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', basename($video['name']));
                $thumb_name = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', basename($thumbnail['name']));

                $video_path = "uploads/videos/" . $video_name;
                $thumb_path = "uploads/thumbnails/" . $thumb_name;

                // Create directories with proper permissions
                $upload_dirs = ['uploads', 'uploads/videos', 'uploads/thumbnails'];
                foreach ($upload_dirs as $dir) {
                    if (!is_dir($dir)) {
                        if (!mkdir($dir, 0755, true)) {
                            throw new Exception("Failed to create directory: $dir");
                        }
                        error_log("Created directory: $dir");
                    }
                    
                    // Ensure directory is writable
                    if (!is_writable($dir)) {
                        if (!chmod($dir, 0755)) {
                            throw new Exception("Failed to set permissions for directory: $dir");
                        }
                        error_log("Set permissions for directory: $dir");
                    }
                }

                // Move uploaded files
                if (move_uploaded_file($video['tmp_name'], $video_path)) {
                    error_log("Video file moved successfully to: $video_path");
                    
                    if (move_uploaded_file($thumbnail['tmp_name'], $thumb_path)) {
                        error_log("Thumbnail file moved successfully to: $thumb_path");
                        
                        // Set proper file permissions
                        chmod($video_path, 0644);
                        chmod($thumb_path, 0644);

                        $category = (rand(0, 1) == 0) ? 'trending' : 'new';

                        // Insert into database
                        $stmt = $conn->prepare("INSERT INTO movies (title, description, genre, video_url, thumbnail, user_id, category) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        if (!$stmt) {
                            throw new Exception("Prepare statement failed: " . $conn->error);
                        }
                        
                        $stmt->bind_param("sssssis", $title, $description, $genre, $video_path, $thumb_path, $user_id, $category);

                        if ($stmt->execute()) {
                            $success = "Video uploaded successfully! Redirecting to home...";
                            error_log("Video uploaded successfully for user: $user_id");
                            echo "<script>
                                setTimeout(() => { window.location.href = 'index.php'; }, 2000);
                            </script>";
                        } else {
                            $error = "Database error: " . $stmt->error;
                            error_log("Database insert failed: " . $stmt->error);
                            // Clean up uploaded files if database insert fails
                            if (file_exists($video_path)) unlink($video_path);
                            if (file_exists($thumb_path)) unlink($thumb_path);
                        }
                    } else {
                        $error = "Error moving thumbnail file. Check directory permissions.";
                        error_log("Failed to move thumbnail file from " . $thumbnail['tmp_name'] . " to $thumb_path");
                        // Clean up video file if thumbnail fails
                        if (file_exists($video_path)) unlink($video_path);
                    }
                } else {
                    $error = "Error moving video file. Check directory permissions.";
                    error_log("Failed to move video file from " . $video['tmp_name'] . " to $video_path");
                }
            }
        }
    } catch (Exception $e) {
        $error = "Upload error: " . $e->getMessage();
        error_log("Upload Exception: " . $e->getMessage() . " in " . __FILE__ . " on line " . __LINE__);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Video - Netflix Clone</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #141414 0%, #1a1a1a 100%);
            color: #fff;
            min-height: 100vh;
            line-height: 1.6;
        }

        /* Header */
        .header {
            background: rgba(0,0,0,0.95);
            padding: 20px 4%;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }

        .nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 28px;
            font-weight: bold;
            color: #e50914;
            text-decoration: none;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 30px;
        }

        .nav-links a {
            color: #fff;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s ease;
        }

        .nav-links a:hover {
            color: #e50914;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 32px;
            height: 32px;
            background: #e50914;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        /* Main Content */
        .main-content {
            padding: 120px 4% 40px;
            max-width: 800px;
            margin: 0 auto;
        }

        .upload-container {
            background: rgba(0,0,0,0.8);
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }

        .page-title {
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 10px;
            color: #fff;
        }

        .page-subtitle {
            text-align: center;
            color: #ccc;
            margin-bottom: 40px;
            font-size: 1.1rem;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #fff;
        }

        .form-input {
            width: 100%;
            padding: 15px;
            background: #333;
            border: 2px solid #444;
            border-radius: 8px;
            color: #fff;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: #e50914;
            background: #444;
        }

        .form-textarea {
            resize: vertical;
            min-height: 100px;
        }

        .form-select {
            cursor: pointer;
        }

        /* File Upload */
        .file-upload {
            position: relative;
            display: inline-block;
            width: 100%;
        }

        .file-upload input[type="file"] {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .file-upload-label {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: #333;
            border: 2px dashed #555;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            min-height: 120px;
        }

        .file-upload-label:hover {
            border-color: #e50914;
            background: #444;
        }

        .file-upload-label i {
            font-size: 2rem;
            margin-right: 15px;
            color: #e50914;
        }

        .file-info {
            text-align: center;
        }

        .file-name {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .file-size {
            color: #ccc;
            font-size: 14px;
        }

        /* Preview */
        .preview-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 20px 0;
        }

        .preview-item {
            background: #222;
            border-radius: 8px;
            overflow: hidden;
            text-align: center;
        }

        .preview-item img,
        .preview-item video {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }

        .preview-label {
            padding: 10px;
            font-size: 14px;
            color: #ccc;
        }

        /* Buttons */
        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s ease;
            width: 100%;
        }

        .btn-primary {
            background: #e50914;
            color: #fff;
        }

        .btn-primary:hover {
            background: #f40612;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #333;
            color: #fff;
            margin-top: 15px;
        }

        .btn-secondary:hover {
            background: #444;
        }

        /* Messages */
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            text-align: center;
            font-weight: 500;
        }

        .error {
            background: rgba(255,0,0,0.2);
            color: #ff6b6b;
            border: 1px solid rgba(255,0,0,0.3);
        }

        .success {
            background: rgba(0,255,0,0.2);
            color: #51cf66;
            border: 1px solid rgba(0,255,0,0.3);
        }

        /* Progress Bar */
        .progress-bar {
            width: 100%;
            height: 6px;
            background: #333;
            border-radius: 3px;
            overflow: hidden;
            margin-top: 10px;
        }

        .progress-fill {
            height: 100%;
            background: #e50914;
            width: 0%;
            transition: width 0.3s ease;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .main-content {
                padding: 100px 20px 40px;
            }

            .upload-container {
                padding: 30px 20px;
            }

            .page-title {
                font-size: 2rem;
            }

            .preview-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<header class="header">
    <nav class="nav">
        <a href="index.php" class="logo">NETFLIX</a>
        <div class="nav-links">
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
            <a href="upload.php"><i class="fas fa-upload"></i> Upload</a>
        </div>
        <div class="user-menu">
            <div class="user-avatar">
                <?php echo strtoupper(substr($user_name, 0, 1)); ?>
            </div>
            <a href="logout.php" style="color: #999;">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </nav>
</header>

<div class="main-content">
    <div class="upload-container">
        <h1 class="page-title">Upload Your Video</h1>
        <p class="page-subtitle">Share your content with the world</p>

        <?php if ($error): ?>
            <div class="message error">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
            </div>
        <?php elseif ($success): ?>
            <div class="message success">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST" enctype="multipart/form-data" id="uploadForm">
            <div class="form-group">
                <label class="form-label">Video Title *</label>
                <input type="text" name="title" class="form-input" placeholder="Enter video title" required>
            </div>

            <div class="form-group">
                <label class="form-label">Description *</label>
                <textarea name="description" class="form-input form-textarea" placeholder="Describe your video content" required></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Genre *</label>
                <select name="genre" class="form-input form-select" required>
                    <option value="">Select Genre</option>
                    <option value="action">Action</option>
                    <option value="comedy">Comedy</option>
                    <option value="drama">Drama</option>
                    <option value="horror">Horror</option>
                    <option value="romance">Romance</option>
                    <option value="sci-fi">Sci-Fi</option>
                    <option value="thriller">Thriller</option>
                    <option value="documentary">Documentary</option>
                    <option value="animation">Animation</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Video File *</label>
                <div class="file-upload">
                    <input type="file" name="video" accept="video/*" required id="videoInput">
                    <label for="videoInput" class="file-upload-label">
                        <i class="fas fa-video"></i>
                        <div class="file-info">
                            <div class="file-name">Choose Video File</div>
                            <div class="file-size">MP4, AVI, MOV, MPEG, WEBM (Max 200MB)</div>
                        </div>
                    </label>
                </div>
                <div class="progress-bar" id="videoProgress" style="display: none;">
                    <div class="progress-fill" id="videoProgressFill"></div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Thumbnail Image *</label>
                <div class="file-upload">
                    <input type="file" name="thumbnail" accept="image/*" required id="thumbnailInput">
                    <label for="thumbnailInput" class="file-upload-label">
                        <i class="fas fa-image"></i>
                        <div class="file-info">
                            <div class="file-name">Choose Thumbnail</div>
                            <div class="file-size">JPG, PNG, WEBP (Max 10MB)</div>
                        </div>
                    </label>
                </div>
                <div class="progress-bar" id="thumbnailProgress" style="display: none;">
                    <div class="progress-fill" id="thumbnailProgressFill"></div>
                </div>
            </div>

            <div class="preview-container" id="previewContainer" style="display: none;">
                <div class="preview-item">
                    <video id="videoPreview" controls></video>
                    <div class="preview-label">Video Preview</div>
                </div>
                <div class="preview-item">
                    <img id="thumbnailPreview" alt="Thumbnail Preview">
                    <div class="preview-label">Thumbnail Preview</div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-upload"></i> Upload Video
            </button>
        </form>

        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Home
        </a>
    </div>
</div>

<script>
// File preview functionality
document.getElementById('videoInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const video = document.getElementById('videoPreview');
            video.src = e.target.result;
            document.getElementById('previewContainer').style.display = 'grid';
        };
        reader.readAsDataURL(file);
        
        // Update file info
        const label = this.nextElementSibling;
        const fileName = label.querySelector('.file-name');
        const fileSize = label.querySelector('.file-size');
        fileName.textContent = file.name;
        fileSize.textContent = formatFileSize(file.size);
    }
});

document.getElementById('thumbnailInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = document.getElementById('thumbnailPreview');
            img.src = e.target.result;
            document.getElementById('previewContainer').style.display = 'grid';
        };
        reader.readAsDataURL(file);
        
        // Update file info
        const label = this.nextElementSibling;
        const fileName = label.querySelector('.file-name');
        const fileSize = label.querySelector('.file-size');
        fileName.textContent = file.name;
        fileSize.textContent = formatFileSize(file.size);
    }
});

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Form validation
document.getElementById('uploadForm').addEventListener('submit', function(e) {
    const videoFile = document.getElementById('videoInput').files[0];
    const thumbnailFile = document.getElementById('thumbnailInput').files[0];
    
    if (!videoFile || !thumbnailFile) {
        e.preventDefault();
        alert('Please select both video and thumbnail files.');
        return;
    }
    
    if (videoFile.size > 209715200) { // 200MB limit
        e.preventDefault();
        alert('Video file size exceeds 200MB limit.');
        return;
    }
    
    if (thumbnailFile.size > 10485760) { // 10MB limit
        e.preventDefault();
        alert('Thumbnail file size exceeds 10MB limit.');
        return;
    }
});
</script>

</body>
</html>
