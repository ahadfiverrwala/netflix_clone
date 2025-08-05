<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href = 'login.php';</script>";
    exit();
}

// Fetch user info
$user_id = $_SESSION['user_id'];
$user_query = $conn->prepare("SELECT name FROM users WHERE id = ?");
$user_query->bind_param("i", $user_id);
$user_query->execute();
$user_result = $user_query->get_result();
$user_name = $user_result->fetch_assoc()['name'];

// Fetch movies by condition
function fetchMovies($conn, $condition, $limit = 10) {
    $query = "SELECT * FROM movies WHERE $condition ORDER BY created_at DESC LIMIT $limit";
    return $conn->query($query);
}

// Display a movie section
function displaySection($title, $result, $fallbackStartId = 1, $fallbackLabel = 'Movie') {
    echo "<div class='section'>";
    echo "<h2 class='section-title'>$title</h2>";
    echo "<div class='movie-row'>";
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo '<div class="movie-card">';
            echo '<div class="movie-thumbnail">';
            echo '<a href="watch.php?id=' . $row['id'] . '">';
            
            // Check if thumbnail exists and is accessible
            $thumbnail_path = $row['thumbnail'];
            if (file_exists($thumbnail_path) && is_readable($thumbnail_path)) {
                echo '<img src="' . $thumbnail_path . '" alt="' . htmlspecialchars($row['title']) . '" loading="lazy">';
            } else {
                // Generate a placeholder with movie title
                echo '<div class="placeholder-thumbnail">';
                echo '<span>' . substr(htmlspecialchars($row['title']), 0, 20) . '</span>';
                echo '</div>';
            }
            
            echo '<div class="movie-overlay">';
            echo '<div class="play-button">▶</div>';
            echo '<div class="movie-info-overlay">';
            echo '<h3>' . htmlspecialchars($row['title']) . '</h3>';
            echo '<p>' . htmlspecialchars($row['genre']) . '</p>';
            echo '</div>';
            echo '</div>';
            echo '</a>';
            echo '</div>';
            echo '<h3 class="movie-title">' . htmlspecialchars($row['title']) . '</h3>';
            echo '</div>';
        }
    } else {
        // Show placeholder cards
        for ($i = 0; $i < 6; $i++) {
            echo '<div class="movie-card placeholder">';
            echo '<div class="movie-thumbnail">';
            echo '<div class="placeholder-thumbnail">';
            echo '<span>Coming Soon</span>';
            echo '</div>';
            echo '</div>';
            echo '<h3 class="movie-title">' . $fallbackLabel . ' ' . ($i + 1) . '</h3>';
            echo '</div>';
        }
    }
    
    echo "</div>";
    echo "</div>";
}

// Queries
$trending = fetchMovies($conn, "category='trending'", 8);
$new = fetchMovies($conn, "category='new'", 8);
$action = fetchMovies($conn, "genre='action'", 8);
$comedy = fetchMovies($conn, "genre='comedy'", 8);
$all_movies = fetchMovies($conn, "1=1", 12);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Netflix Clone - Home</title>
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

        .upload-btn {
            background: #e50914;
            color: #fff;
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
            transition: background 0.3s ease;
        }

        .upload-btn:hover {
            background: #f40612;
        }

        /* Hero Section */
        .hero {
            height: 80vh;
            background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), 
                        url('https://images.unsplash.com/photo-1489599832529-2ea8c3d2b5f3?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80');
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: center;
            padding: 0 4%;
            margin-top: 80px;
        }

        .hero-content {
            max-width: 600px;
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: bold;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.8);
        }

        .hero-subtitle {
            font-size: 1.2rem;
            margin-bottom: 30px;
            color: #ccc;
        }

        .hero-buttons {
            display: flex;
            gap: 15px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
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

        /* Main Content */
        .main-content {
            padding: 40px 4%;
        }

        .section {
            margin-bottom: 50px;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 20px;
            color: #fff;
        }

        .movie-row {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            overflow-x: auto;
        }

        .movie-card {
            background: #2f2f2f;
            border-radius: 8px;
            overflow: hidden;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .movie-card:hover {
            transform: scale(1.05);
            box-shadow: 0 10px 25px rgba(0,0,0,0.5);
        }

        .movie-thumbnail {
            position: relative;
            height: 280px;
            overflow: hidden;
        }

        .movie-thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .movie-card:hover .movie-thumbnail img {
            transform: scale(1.1);
        }

        .placeholder-thumbnail {
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, #333, #555);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
            font-size: 14px;
            text-align: center;
        }

        .movie-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .movie-card:hover .movie-overlay {
            opacity: 1;
        }

        .play-button {
            width: 60px;
            height: 60px;
            background: rgba(229, 9, 20, 0.9);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #fff;
            margin-bottom: 15px;
        }

        .movie-info-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 20px;
            background: linear-gradient(transparent, rgba(0,0,0,0.9));
        }

        .movie-info-overlay h3 {
            font-size: 16px;
            margin-bottom: 5px;
        }

        .movie-info-overlay p {
            font-size: 12px;
            color: #ccc;
        }

        .movie-title {
            padding: 15px;
            font-size: 14px;
            font-weight: 500;
            color: #fff;
            text-align: center;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }

            .nav-links {
                gap: 15px;
            }

            .movie-row {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 10px;
            }

            .movie-thumbnail {
                height: 200px;
            }

            .hero-buttons {
                flex-direction: column;
            }
        }

        @media (max-width: 480px) {
            .header {
                padding: 15px 20px;
            }

            .nav-links {
                display: none;
            }

            .hero {
                padding: 0 20px;
            }

            .main-content {
                padding: 20px;
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
            <a href="#"><i class="fas fa-tv"></i> TV Shows</a>
            <a href="#"><i class="fas fa-film"></i> Movies</a>
            <a href="#"><i class="fas fa-heart"></i> My List</a>
        </div>
        <div class="user-menu">
            <a href="upload.php" class="upload-btn">
                <i class="fas fa-upload"></i> Upload
            </a>
            <div class="user-avatar">
                <?php echo strtoupper(substr($user_name, 0, 1)); ?>
            </div>
            <a href="logout.php" style="color: #999;">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </nav>
</header>

<div class="hero">
    <div class="hero-content">
        <h1 class="hero-title">Welcome to Netflix Clone</h1>
        <p class="hero-subtitle">Watch unlimited movies, TV shows, and more. Stream anywhere. Cancel anytime.</p>
        <div class="hero-buttons">
            <a href="#trending" class="btn btn-primary">
                <i class="fas fa-play"></i> Watch Now
            </a>
            <a href="upload.php" class="btn btn-secondary">
                <i class="fas fa-plus"></i> Upload Video
            </a>
        </div>
    </div>
</div>

<div class="main-content">
    <?php
        displaySection("Trending Now", $trending, 1, "Trending Movie");
        displaySection("New Releases", $new, 11, "New Release");
        displaySection("Action Movies", $action, 21, "Action Movie");
        displaySection("Comedy Movies", $comedy, 31, "Comedy Movie");
        displaySection("All Movies", $all_movies, 41, "Movie");
    ?>
</div>

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

// Smooth scrolling for anchor links
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

// Add loading animation to images
document.querySelectorAll('img').forEach(img => {
    img.addEventListener('load', function() {
        this.style.opacity = '1';
    });
    img.style.opacity = '0';
    img.style.transition = 'opacity 0.3s ease';
});
</script>

</body>
</html>
