<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

session_start();
include 'db_connect.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='login.php';</script>";
    exit();
}

// Get user info
$user_id = $_SESSION['user_id'];
$user_query = $conn->prepare("SELECT name FROM users WHERE id = ?");
$user_query->bind_param("i", $user_id);
$user_query->execute();
$user_result = $user_query->get_result();
$user_name = $user_result->fetch_assoc()['name'];

// Get movie ID
$movie_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch movie details from DB
$stmt = $conn->prepare("SELECT * FROM movies WHERE id = ?");
$stmt->bind_param("i", $movie_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $movie = $result->fetch_assoc();
    
    // Update view count
    $update_views = $conn->prepare("UPDATE movies SET views = views + 1 WHERE id = ?");
    $update_views->bind_param("i", $movie_id);
    $update_views->execute();
} else {
    echo "<script>alert('Movie not found.'); window.location.href='index.php';</script>";
    exit();
}

// Fetch related movies
$related_query = $conn->prepare("SELECT * FROM movies WHERE genre = ? AND id != ? ORDER BY created_at DESC LIMIT 6");
$related_query->bind_param("si", $movie['genre'], $movie_id);
$related_query->execute();
$related_movies = $related_query->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($movie['title']); ?> - Netflix Clone</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            background: #0a0a0a;
            color: #fff;
            line-height: 1.6;
        }

        /* Header */
        .header {
            background: linear-gradient(to bottom, rgba(0,0,0,0.9) 0%, transparent 100%);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            padding: 20px 4%;
            transition: background 0.3s ease;
        }

        .header.scrolled {
            background: rgba(0,0,0,0.95);
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

        /* Video Player */
        .video-section {
            margin-top: 80px;
            background: #000;
            position: relative;
        }

        .video-container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
        }

        .video-player {
            width: 100%;
            height: 0;
            padding-bottom: 56.25%; /* 16:9 aspect ratio */
            position: relative;
            background: #000;
        }

        .video-player video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .video-controls {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0,0,0,0.8));
            padding: 20px;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .video-container:hover .video-controls {
            opacity: 1;
        }

        .play-pause-btn {
            background: rgba(229, 9, 20, 0.9);
            border: none;
            color: #fff;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 18px;
            margin-right: 15px;
        }

        .progress-bar {
            flex: 1;
            height: 4px;
            background: rgba(255,255,255,0.3);
            border-radius: 2px;
            cursor: pointer;
            margin: 0 15px;
        }

        .progress-fill {
            height: 100%;
            background: #e50914;
            border-radius: 2px;
            width: 0%;
        }

        .time-display {
            color: #fff;
            font-size: 14px;
            margin-left: 15px;
        }

        /* Movie Info */
        .movie-info-section {
            padding: 40px 4%;
            max-width: 1200px;
            margin: 0 auto;
        }

        .movie-header {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 40px;
            margin-bottom: 40px;
            align-items: start;
        }

        .movie-details h1 {
            font-size: 3rem;
            font-weight: bold;
            margin-bottom: 15px;
            color: #fff;
        }

        .movie-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 20px;
            color: #ccc;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .meta-item i {
            color: #e50914;
        }

        .movie-description {
            font-size: 1.1rem;
            line-height: 1.8;
            color: #ccc;
            max-width: 800px;
        }

        .movie-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .action-btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #e50914;
            color: #fff;
        }

        .btn-primary:hover {
            background: #f40612;
            transform: scale(1.05);
        }

        .btn-secondary {
            background: rgba(109, 109, 110, 0.7);
            color: #fff;
        }

        .btn-secondary:hover {
            background: rgba(109, 109, 110, 0.9);
        }

        .movie-stats {
            background: rgba(255,255,255,0.1);
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }

        .stat-item {
            margin-bottom: 15px;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: #e50914;
        }

        .stat-label {
            font-size: 0.9rem;
            color: #ccc;
        }

        /* Related Movies */
        .related-section {
            padding: 40px 4%;
            max-width: 1200px;
            margin: 0 auto;
        }

        .section-title {
            font-size: 1.8rem;
            font-weight: bold;
            margin-bottom: 30px;
            color: #fff;
        }

        .related-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
        }

        .related-movie {
            background: #2f2f2f;
            border-radius: 8px;
            overflow: hidden;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .related-movie:hover {
            transform: scale(1.05);
            box-shadow: 0 10px 25px rgba(0,0,0,0.5);
        }

        .related-thumbnail {
            position: relative;
            height: 120px;
            overflow: hidden;
        }

        .related-thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .related-info {
            padding: 15px;
        }

        .related-title {
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 5px;
            color: #fff;
        }

        .related-genre {
            font-size: 12px;
            color: #ccc;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .movie-header {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .movie-details h1 {
                font-size: 2rem;
            }

            .movie-actions {
                flex-direction: column;
            }

            .related-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 15px;
            }

            .video-controls {
                padding: 15px;
            }
        }

        @media (max-width: 480px) {
            .movie-info-section,
            .related-section {
                padding: 20px;
            }

            .movie-details h1 {
                font-size: 1.5rem;
            }

            .movie-meta {
                flex-direction: column;
                gap: 10px;
            }
        }

        /* Loading Animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>

<header class="header" id="header">
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

<div class="video-section">
    <div class="video-container">
        <div class="video-player">
            <?php if (file_exists($movie['video_url']) && is_readable($movie['video_url'])): ?>
                <video id="videoPlayer" controls preload="metadata">
                    <source src="<?php echo htmlspecialchars($movie['video_url']); ?>" type="video/mp4">
                    <source src="<?php echo htmlspecialchars($movie['video_url']); ?>" type="video/webm">
                    <source src="<?php echo htmlspecialchars($movie['video_url']); ?>" type="video/ogg">
                    Your browser does not support the video tag.
                </video>
            <?php else: ?>
                <div style="display: flex; align-items: center; justify-content: center; height: 100%; background: #000; color: #fff; font-size: 18px;">
                    <div style="text-align: center;">
                        <i class="fas fa-exclamation-triangle" style="font-size: 48px; color: #e50914; margin-bottom: 20px;"></i>
                        <p>Video file not found or not accessible.</p>
                        <p style="font-size: 14px; color: #ccc;">Please contact support if this issue persists.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="movie-info-section">
    <div class="movie-header">
        <div class="movie-details">
            <h1><?php echo htmlspecialchars($movie['title']); ?></h1>
            <div class="movie-meta">
                <div class="meta-item">
                    <i class="fas fa-film"></i>
                    <span><?php echo htmlspecialchars($movie['genre']); ?></span>
                </div>
                <div class="meta-item">
                    <i class="fas fa-calendar"></i>
                    <span><?php echo htmlspecialchars($movie['year']); ?></span>
                </div>
                <div class="meta-item">
                    <i class="fas fa-clock"></i>
                    <span><?php echo htmlspecialchars($movie['duration']); ?></span>
                </div>
                <div class="meta-item">
                    <i class="fas fa-star"></i>
                    <span><?php echo htmlspecialchars($movie['rating']); ?></span>
                </div>
                <div class="meta-item">
                    <i class="fas fa-eye"></i>
                    <span><?php echo number_format($movie['views']); ?> views</span>
                </div>
            </div>
            <p class="movie-description"><?php echo htmlspecialchars($movie['description']); ?></p>
            <div class="movie-actions">
                <a href="index.php" class="action-btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Browse
                </a>
                <a href="upload.php" class="action-btn btn-primary">
                    <i class="fas fa-upload"></i> Upload Video
                </a>
            </div>
        </div>
        <div class="movie-stats">
            <div class="stat-item">
                <div class="stat-value"><?php echo number_format($movie['views']); ?></div>
                <div class="stat-label">Total Views</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?php echo htmlspecialchars($movie['rating']); ?></div>
                <div class="stat-label">Rating</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?php echo htmlspecialchars($movie['genre']); ?></div>
                <div class="stat-label">Genre</div>
            </div>
        </div>
    </div>
</div>

<?php if ($related_movies && $related_movies->num_rows > 0): ?>
<div class="related-section">
    <h2 class="section-title">More Like This</h2>
    <div class="related-grid">
        <?php while ($related = $related_movies->fetch_assoc()): ?>
        <div class="related-movie" onclick="window.location.href='watch.php?id=<?php echo $related['id']; ?>'">
            <div class="related-thumbnail">
                <?php if (file_exists($related['thumbnail']) && is_readable($related['thumbnail'])): ?>
                    <img src="<?php echo htmlspecialchars($related['thumbnail']); ?>" alt="<?php echo htmlspecialchars($related['title']); ?>">
                <?php else: ?>
                    <div style="width: 100%; height: 100%; background: linear-gradient(45deg, #333, #555); display: flex; align-items: center; justify-content: center; color: #999; font-size: 12px;">
                        <?php echo substr(htmlspecialchars($related['title']), 0, 15); ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="related-info">
                <div class="related-title"><?php echo htmlspecialchars($related['title']); ?></div>
                <div class="related-genre"><?php echo htmlspecialchars($related['genre']); ?></div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</div>
<?php endif; ?>

<script>
// Header scroll effect
window.addEventListener('scroll', function() {
    const header = document.getElementById('header');
    if (window.scrollY > 100) {
        header.classList.add('scrolled');
    } else {
        header.classList.remove('scrolled');
    }
});

// Video player enhancements
const video = document.getElementById('videoPlayer');
if (video) {
    // Auto-play when ready
    video.addEventListener('canplay', function() {
        // Don't auto-play on mobile devices
        if (!/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
            // video.play();
        }
    });

    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
        
        switch(e.code) {
            case 'Space':
                e.preventDefault();
                if (video.paused) video.play();
                else video.pause();
                break;
            case 'ArrowLeft':
                e.preventDefault();
                video.currentTime -= 10;
                break;
            case 'ArrowRight':
                e.preventDefault();
                video.currentTime += 10;
                break;
            case 'ArrowUp':
                e.preventDefault();
                video.volume = Math.min(1, video.volume + 0.1);
                break;
            case 'ArrowDown':
                e.preventDefault();
                video.volume = Math.max(0, video.volume - 0.1);
                break;
        }
    });
}

// Smooth scrolling
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});
</script>

</body>
</html>
