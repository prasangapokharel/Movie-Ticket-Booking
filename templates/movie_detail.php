<?php
session_start();
include '../database/config.php';
include '../includes/loader.php'; 

// Set timezone to Nepal Standard Time (UTC+5:45)
date_default_timezone_set('Asia/Kathmandu');
$title = basename($_SERVER['REQUEST_URI']);

// Check if movie_id is provided
if (!isset($_GET['id'])) {
    $_SESSION['alert'] = [
        'type' => 'error',
        'message' => 'Movie ID is required'
    ];
    header('Location: index.php');
    exit;
}

$movie_id = $_GET['id'];

try {
    // Fetch movie details
    $stmt = $conn->prepare("
        SELECT * FROM movies 
        WHERE movie_id = :movie_id
    ");
    $stmt->bindParam(':movie_id', $movie_id);
    $stmt->execute();
    $movie = $stmt->fetch();
    
    if (!$movie) {
        $_SESSION['alert'] = [
            'type' => 'error',
            'message' => 'Movie not found'
        ];
        header('Location: index.php');
        exit;
    }
    
    // Fetch theaters and showtimes for this movie
    // Use Nepal Standard Time for current date/time
    $currentDateTime = date('Y-m-d H:i:s');
    
    $stmt = $conn->prepare("
        SELECT 
            s.show_id, 
            s.show_time, 
            s.price,
            t.theater_id,
            t.name AS theater_name,
            t.location AS theater_location,
            t.capacity,
            (SELECT COUNT(*) FROM seats WHERE show_id = s.show_id AND status = 'booked') AS booked_seats
        FROM 
            shows s
        JOIN 
            theaters t ON s.theater_id = t.theater_id
        WHERE 
            s.movie_id = :movie_id
            AND s.show_time >= :currentDateTime
        ORDER BY 
            t.location ASC, s.show_time ASC
    ");
    $stmt->bindParam(':movie_id', $movie_id);
    $stmt->bindParam(':currentDateTime', $currentDateTime);
    $stmt->execute();
    $showtimes = $stmt->fetchAll();
    
    // Group showtimes by theater and date
    $theaterShowtimes = [];
    foreach ($showtimes as $showtime) {
        $theaterId = $showtime['theater_id'];
        $theaterName = $showtime['theater_name'];
        $theaterLocation = $showtime['theater_location'];
        $showDate = date('Y-m-d', strtotime($showtime['show_time']));
        
        if (!isset($theaterShowtimes[$theaterId])) {
            $theaterShowtimes[$theaterId] = [
                'theater_id' => $theaterId,
                'theater_name' => $theaterName,
                'theater_location' => $theaterLocation,
                'dates' => []
            ];
        }
        
        if (!isset($theaterShowtimes[$theaterId]['dates'][$showDate])) {
            $theaterShowtimes[$theaterId]['dates'][$showDate] = [];
        }
        
        // Calculate available seats
        $capacity = $showtime['capacity'] ?? 100; // Default to 100 if capacity not set
        $bookedSeats = $showtime['booked_seats'] ?? 0;
        $availableSeats = $capacity - $bookedSeats;
        $availabilityPercentage = ($availableSeats / $capacity) * 100;
        
        // Determine availability status
        if ($availabilityPercentage <= 10) {
            $availability = 'almost-full';
        } elseif ($availabilityPercentage <= 50) {
            $availability = 'filling-fast';
        } else {
            $availability = 'available';
        }
        
        $theaterShowtimes[$theaterId]['dates'][$showDate][] = [
            'show_id' => $showtime['show_id'],
            'show_time' => $showtime['show_time'],
            'price' => $showtime['price'],
            'availability' => $availability,
            'available_seats' => $availableSeats
        ];
    }
    
    // Fetch similar movies (same genre)
    if (!empty($movie['genre'])) {
        $genres = explode(',', $movie['genre']);
        $primaryGenre = trim($genres[0]); // Use the first genre
        
        $stmt = $conn->prepare("
            SELECT 
                m.movie_id, 
                m.title, 
                m.poster_url, 
                m.rating
            FROM 
                movies m
            WHERE 
                m.movie_id != :movie_id
                AND m.genre LIKE :genre
            ORDER BY 
                m.release_date DESC
            LIMIT 4
        ");
        $stmt->bindParam(':movie_id', $movie_id);
        $stmt->bindValue(':genre', '%' . $primaryGenre . '%');
        $stmt->execute();
        $similarMovies = $stmt->fetchAll();
    } else {
        $similarMovies = [];
    }
    
} catch (PDOException $e) {
    $_SESSION['alert'] = [
        'type' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ];
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($movie['title']); ?> - CineBook</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="../assets/js/talwind.js"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #0f172a;
            color: #f1f5f9;
            min-height: 100vh;
        }
        
        .hero-section {
            position: relative;
            min-height: 70vh;
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: flex-end;
        }
        
        .hero-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(to bottom, rgba(15, 23, 42, 0.3), rgba(15, 23, 42, 0.9) 70%, #0f172a);
        }
        
        .movie-info {
            position: relative;
            z-index: 10;
            width: 100%;
            padding-bottom: 2rem;
        }
        
        .play-button {
            background-color: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(5px);
            border-radius: 50%;
            width: 80px;
            height: 80px;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }
        
        .play-button:hover {
            background-color: rgba(255, 255, 255, 0.25);
            transform: scale(1.1);
        }
        
        .genre-tag {
            background-color: rgba(99, 102, 241, 0.15);
            color: #c7d2fe;
            border: 1px solid rgba(99, 102, 241, 0.3);
            transition: all 0.3s ease;
        }
        
        .genre-tag:hover {
            background-color: rgba(99, 102, 241, 0.25);
        }
        
        .book-button {
            transition: all 0.3s ease;
            background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%);
            position: relative;
            overflow: hidden;
        }
        
        .book-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 15px -3px rgba(185, 28, 28, 0.3);
        }
        
        .book-button::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 50%);
            transition: opacity 0.3s ease;
        }
        
        .book-button:hover::after {
            opacity: 0;
        }
        
        .rating-star {
            filter: drop-shadow(0 0 2px rgba(250, 204, 21, 0.3));
        }
        
        .section-heading {
            position: relative;
            display: inline-block;
        }
        
        .section-heading::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 40px;
            height: 3px;
            background: linear-gradient(90deg, #ef4444, #b91c1c);
            border-radius: 3px;
        }
        
        .theater-card {
            background: linear-gradient(to bottom right, rgba(30, 41, 59, 0.7), rgba(15, 23, 42, 0.9));
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }
        
        .theater-card:hover {
            border-color: rgba(255, 255, 255, 0.2);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        
        .date-tab {
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .date-tab.active {
            background-color: rgba(99, 102, 241, 0.15);
            border-color: rgba(99, 102, 241, 0.3);
            color: #c7d2fe;
        }
        
        .show-time-btn {
            transition: all 0.3s ease;
            background-color: rgba(99, 102, 241, 0.15);
            border: 1px solid rgba(99, 102, 241, 0.3);
        }
        
        .show-time-btn:hover {
            transform: scale(1.05);
            background-color: rgba(99, 102, 241, 0.25);
        }
        
        .show-time-btn.prime-time {
            background-color: rgba(245, 158, 11, 0.15);
            border: 1px solid rgba(245, 158, 11, 0.3);
            color: #fcd34d;
        }
        
        .show-time-btn.prime-time:hover {
            background-color: rgba(245, 158, 11, 0.25);
        }
        
        .show-time-btn.almost-full {
            background-color: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }
        
        .show-time-btn.almost-full:hover {
            background-color: rgba(239, 68, 68, 0.25);
        }
        
        .show-time-btn.filling-fast {
            background-color: rgba(245, 158, 11, 0.15);
            border: 1px solid rgba(245, 158, 11, 0.3);
            color: #fcd34d;
        }
        
        .modal {
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        
        .movie-poster {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 10px 10px -5px rgba(0, 0, 0, 0.2);
            border: 3px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }
        
        .movie-poster:hover {
            transform: scale(1.02);
            border-color: rgba(255, 255, 255, 0.2);
        }
        
        .similar-movie-card {
            background: linear-gradient(to bottom, rgba(30, 41, 59, 0.5), rgba(15, 23, 42, 0.8));
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }
        
        .similar-movie-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            border-color: rgba(255, 255, 255, 0.2);
        }
        
       
    </style>
</head>
<body>
    <?php include '../includes/nav.php'; ?>
    
    <!-- Alert Messages -->
    <?php if (isset($_SESSION['alert'])): ?>
    <div class="fixed top-20 right-4 z-50 max-w-sm transition-all duration-500 ease-in-out transform" id="alertMessage">
        <div class="<?php echo $_SESSION['alert']['type'] === 'success' ? 'bg-green-900/80 text-green-200 border-green-800/50' : 'bg-red-900/80 text-red-200 border-red-800/50'; ?> backdrop-blur-md px-4 py-3 rounded-lg shadow-lg border">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <?php if ($_SESSION['alert']['type'] === 'success'): ?>
                    <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <?php else: ?>
                    <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                    <?php endif; ?>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium"><?php echo $_SESSION['alert']['message']; ?></p>
                </div>
                <div class="ml-auto pl-3">
                    <div class="-mx-1.5 -my-1.5">
                        <button type="button" onclick="document.getElementById('alertMessage').remove()" class="inline-flex rounded-md p-1.5 <?php echo $_SESSION['alert']['type'] === 'success' ? 'text-green-300 hover:text-green-100' : 'text-red-300 hover:text-red-100'; ?> focus:outline-none">
                            <span class="sr-only">Dismiss</span>
                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php unset($_SESSION['alert']); ?>
    <?php endif; ?>
    
    <!-- Hero Section with Movie Backdrop -->
    <section class="hero-section" style="background-image: url('<?php echo htmlspecialchars($movie['poster_url']); ?>');">
        <div class="hero-overlay"></div>
        <div class="container mx-auto px-4 movie-info">
            <div class="flex flex-col md:flex-row items-end md:items-end gap-8">
                <div class="movie-poster w-64 h-96 flex-shrink-0 hidden md:block">
                    <img src="<?php echo htmlspecialchars($movie['poster_url']); ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>" class="w-full h-full object-cover">
                </div>
                <div class="flex-1">
                    <h1 class="text-4xl md:text-5xl font-bold mb-4"><?php echo htmlspecialchars($movie['title']); ?></h1>
                    
                    <?php if (isset($movie['genre'])): ?>
                    <div class="flex flex-wrap gap-2 mb-4">
                        <?php 
                        $genres = explode(',', $movie['genre']);
                        foreach ($genres as $genre): 
                        ?>
                            <span class="genre-tag text-xs px-3 py-1 rounded-full"><?php echo trim($genre); ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="flex items-center mb-4">
                        <?php if (isset($movie['rating'])): ?>
                        <div class="flex items-center mr-6">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <?php if ($i <= round($movie['rating'])): ?>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-yellow-400 rating-star" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                    </svg>
                                <?php else: ?>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-600" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                    </svg>
                                <?php endif; ?>
                            <?php endfor; ?>
                            <span class="ml-2 text-sm text-gray-400 font-light"><?php echo $movie['rating']; ?>/5</span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="flex items-center text-sm text-gray-400 font-light">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-indigo-300" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                            </svg>
                            <?php echo $movie['duration']; ?> min
                            <span class="mx-2 text-gray-600">•</span>
                            <span><?php echo $movie['language']; ?></span>
                            <?php if (isset($movie['release_date'])): ?>
                            <span class="mx-2 text-gray-600">•</span>
                            <span><?php echo date('M d, Y', strtotime($movie['release_date'])); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <p class="text-gray-300 mb-6 max-w-3xl font-light leading-relaxed">
                        <?php echo htmlspecialchars($movie['description']); ?>
                    </p>
                    
                    <div class="flex flex-wrap gap-4 items-center">
                        <?php if (isset($movie['trailer_url']) && !empty($movie['trailer_url'])): ?>
                        <button onclick="playTrailer('<?php echo htmlspecialchars($movie['trailer_url']); ?>', '<?php echo htmlspecialchars($movie['title']); ?>')" class="flex items-center gap-2 px-6 py-3 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white transition-all">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd" />
                            </svg>
                            Watch Trailer
                        </button>
                        <?php endif; ?>
                        
                        <a href="#showtimes" class="book-button px-6 py-3 rounded-lg font-medium text-white shadow-lg transition-all">
                            Book Tickets
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Movie Details Section -->
    <section class="container mx-auto px-4 py-12">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="md:col-span-2">
                <h2 class="text-2xl font-bold mb-6 section-heading">About the Movie</h2>
                
                <div class="bg-slate-800/50 rounded-xl p-6 mb-8">
                    <p class="text-gray-300 leading-relaxed mb-6">
                        <?php echo nl2br(htmlspecialchars($movie['description'])); ?>
                    </p>
                    
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div>
                            <h3 class="text-sm text-gray-400 mb-1">Genre</h3>
                            <p class="font-medium"><?php echo htmlspecialchars($movie['genre']); ?></p>
                        </div>
                        
                        <div>
                            <h3 class="text-sm text-gray-400 mb-1">Duration</h3>
                            <p class="font-medium"><?php echo $movie['duration']; ?> minutes</p>
                        </div>
                        
                        <div>
                            <h3 class="text-sm text-gray-400 mb-1">Language</h3>
                            <p class="font-medium"><?php echo htmlspecialchars($movie['language']); ?></p>
                        </div>
                        
                        <div>
                            <h3 class="text-sm text-gray-400 mb-1">Release Date</h3>
                            <p class="font-medium"><?php echo date('M d, Y', strtotime($movie['release_date'])); ?></p>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($similarMovies)): ?>
                <h2 class="text-2xl font-bold mb-6 section-heading">Similar Movies</h2>
                
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
    <?php foreach ($similarMovies as $similarMovie): ?>
    <a href="movie_detail.php?id=<?php echo $similarMovie['movie_id']; ?>" class="similar-movie-card rounded-lg overflow-hidden">
        <div class="relative aspect-[2/3]">
            <img src="<?php echo htmlspecialchars($similarMovie['poster_url']); ?>" alt="<?php echo htmlspecialchars($similarMovie['title']); ?>" class="w-full h-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-t from-gray-900 to-transparent opacity-70"></div>
            <div class="absolute bottom-0 left-0 right-0 p-3">
                <h3 class="text-sm font-medium text-white"><?php echo htmlspecialchars($similarMovie['title']); ?></h3>
                <?php if (isset($similarMovie['rating'])): ?>
                <div class="flex items-center mt-1">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-yellow-400">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />
                    </svg>
                    <span class="text-xs text-gray-300 ml-1"><?php echo $similarMovie['rating']; ?>/5</span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </a>
    <?php endforeach; ?>
</div>
                <?php endif; ?>
            </div>
            
            <div>
                <h2 class="text-2xl font-bold mb-6 section-heading">Movie Trailer</h2>
                
                <?php if (isset($movie['trailer_url']) && !empty($movie['trailer_url'])): ?>
                <div class="bg-slate-800/50 rounded-xl overflow-hidden mb-8">
                    <div class="aspect-video relative">
                        <?php
                        // Extract YouTube video ID
                        $videoId = '';
                        if (strpos($movie['trailer_url'], 'youtube.com') !== false) {
                            parse_str(parse_url($movie['trailer_url'], PHP_URL_QUERY), $params);
                            $videoId = isset($params['v']) ? $params['v'] : '';
                        } elseif (strpos($movie['trailer_url'], 'youtu.be') !== false) {
                            $videoId = basename(parse_url($movie['trailer_url'], PHP_URL_PATH));
                        }
                        
                        if (!empty($videoId)):
                        ?>
                        <iframe 
                            src="https://www.youtube.com/embed/<?php echo $videoId; ?>?rel=0" 
                            class="absolute top-0 left-0 w-full h-full" 
                            frameborder="0" 
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                            allowfullscreen>
                        </iframe>
                        <?php else: ?>
                        <div class="absolute inset-0 flex items-center justify-center bg-gray-900">
                            <p class="text-gray-400">Trailer not available</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php else: ?>
                <div class="bg-slate-800/50 rounded-xl p-6 mb-8 flex items-center justify-center h-48">
                    <p class="text-gray-400">No trailer available for this movie</p>
                </div>
                <?php endif; ?>
                
                <h2 class="text-2xl font-bold mb-6 section-heading">Movie Info</h2>
                
                <div class="bg-slate-800/50 rounded-xl p-6 mb-8">
                    <ul class="space-y-4">
                        <?php if (isset($movie['language'])): ?>
                        <li class="flex">
                            <span class="text-gray-400 w-24">Language:</span>
                            <span class="font-medium"><?php echo htmlspecialchars($movie['language']); ?></span>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (isset($movie['release_date'])): ?>
                        <li class="flex">
                            <span class="text-gray-400 w-24">Release:</span>
                            <span class="font-medium"><?php echo date('F j, Y', strtotime($movie['release_date'])); ?></span>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (isset($movie['duration'])): ?>
                        <li class="flex">
                            <span class="text-gray-400 w-24">Duration:</span>
                            <span class="font-medium"><?php echo $movie['duration']; ?> minutes</span>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (isset($movie['rating'])): ?>
                        <li class="flex">
                            <span class="text-gray-400 w-24">Rating:</span>
                            <span class="font-medium"><?php echo $movie['rating']; ?>/5</span>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Showtimes Section -->
    <section id="showtimes" class="container mx-auto px-4 py-16">
        <h2 class="text-3xl font-bold text-white mb-10 section-heading">Showtimes & Tickets</h2>
        
        <?php if (empty($theaterShowtimes)): ?>
            <div class="bg-slate-800/50 rounded-lg p-8 text-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-gray-600 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <h3 class="text-xl font-semibold mb-2">No Showtimes Available</h3>
                <p class="text-gray-400 max-w-lg mx-auto">There are currently no scheduled showtimes for this movie. Please check back later or explore other movies.</p>
                <a href="index.php" class="mt-6 inline-block px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition-all">
                    Browse Movies
                </a>
            </div>
        <?php else: ?>
            <div class="mb-6 bg-slate-800/50 rounded-xl p-4 flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-yellow-400 mr-2" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                </svg>
                <p class="text-sm text-gray-300">
                    <span class="font-medium text-yellow-400">Booking Info:</span> 
                    Select a showtime to proceed with booking. Prime time shows (6 PM - 9 PM) are highlighted in gold.
                    <span class="ml-2 text-red-400">Almost full</span> showtimes have limited seats available.
                </p>
            </div>
            
            <?php foreach ($theaterShowtimes as $theater): ?>
                <div class="theater-card rounded-lg overflow-hidden mb-8">
                    <div class="bg-gray-800/50 px-6 py-4 border-b border-gray-700">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                            <div>
                                <h3 class="text-xl font-semibold"><?php echo htmlspecialchars($theater['theater_name']); ?></h3>
                                <p class="text-gray-400 text-sm"><?php echo htmlspecialchars($theater['theater_location']); ?></p>
                            </div>
                            <div class="mt-2 md:mt-0">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    Available
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-6">
                        <!-- Date Tabs -->
                        <div class="flex overflow-x-auto pb-4 mb-4 space-x-2">
                            <?php 
                            $dateIndex = 0;
                            foreach ($theater['dates'] as $date => $shows): 
                                $isToday = $date === date('Y-m-d');
                                $isTomorrow = $date === date('Y-m-d', strtotime('+1 day'));
                                $displayDate = $isToday ? 'Today' : ($isTomorrow ? 'Tomorrow' : date('D, M j', strtotime($date)));
                            ?>
                                <div class="date-tab px-4 py-2 rounded-lg text-sm whitespace-nowrap <?php echo $dateIndex === 0 ? 'active' : 'bg-gray-800/50'; ?>" 
                                     data-theater="<?php echo $theater['theater_id']; ?>" 
                                     data-date="<?php echo $date; ?>">
                                    <?php echo $displayDate; ?>
                                </div>
                            <?php 
                                $dateIndex++;
                            endforeach; 
                            ?>
                        </div>
                        
                        <!-- Showtimes by Date -->
                        <?php 
                        $dateIndex = 0;
                        foreach ($theater['dates'] as $date => $shows): 
                        ?>
                            <div class="showtimes-container" 
                                 id="theater-<?php echo $theater['theater_id']; ?>-date-<?php echo $date; ?>" 
                                 style="<?php echo $dateIndex > 0 ? 'display: none;' : ''; ?>">
                                <div class="flex flex-wrap gap-3">
                                    <?php foreach ($shows as $show): 
                                        $showTime = date('h:i A', strtotime($show['show_time']));
                                        $showDate = date('Y-m-d', strtotime($show['show_time']));
                                        $primeTime = (strtotime($show['show_time']) >= strtotime($showDate . ' 18:00:00') && 
                                                     strtotime($show['show_time']) <= strtotime($showDate . ' 21:00:00'));
                                        
                                        // Set button class based on availability and prime time
                                        $btnClass = '';
                                        if ($show['availability'] === 'almost-full') {
                                            $btnClass = 'almost-full';
                                        } elseif ($show['availability'] === 'filling-fast') {
                                            $btnClass = 'filling-fast';
                                        } elseif ($primeTime) {
                                            $btnClass = 'prime-time';
                                        }
                                    ?>
                                        <a href="booking.php?show_id=<?php echo $show['show_id']; ?>" 
                                           class="show-time-btn <?php echo $btnClass; ?> px-4 py-2 rounded-lg flex flex-col items-center">
                                            <span class="font-medium"><?php echo $showTime; ?></span>
                                            <span class="text-xs text-gray-400 mt-1">₨<?php echo number_format($show['price'], 2); ?></span>
                                            <?php if ($show['availability'] === 'almost-full'): ?>
                                            <span class="text-xs mt-1">Almost Full</span>
                                            <?php elseif ($show['availability'] === 'filling-fast'): ?>
                                            <span class="text-xs mt-1">Filling Fast</span>
                                            <?php endif; ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php 
                            $dateIndex++;
                        endforeach; 
                        ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>
    
    <!-- Video Modal -->
    <div id="trailerModal" class="fixed inset-0 z-50 flex items-center justify-center hidden modal">
        <div class="absolute inset-0 bg-black bg-opacity-80 backdrop-blur-sm" onclick="closeTrailerModal()"></div>
        <div class="relative bg-gray-900 rounded-xl overflow-hidden w-11/12 max-w-4xl border border-gray-800">
            <div class="absolute top-4 right-4 z-10">
                <button onclick="closeTrailerModal()" class="bg-gray-800 bg-opacity-70 hover:bg-gray-700 text-white p-2 rounded-full flex items-center justify-center transition-all duration-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            
            <div id="trailerTitleBar" class="bg-gray-900 bg-opacity-80 backdrop-blur-sm py-3 px-5 border-b border-gray-800">
                <h3 id="trailerTitle" class="text-lg font-medium text-white"></h3>
            </div>
            
            <div id="trailerContainer" class="relative pt-16:9 h-0 pb-[56.25%]">
                <iframe id="trailerPlayer" class="absolute top-0 left-0 w-full h-full" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script>
        // Date tab switching
        const dateTabs = document.querySelectorAll('.date-tab');
        
        dateTabs.forEach(tab => {
            tab.addEventListener('click', function() {
                const theaterId = this.getAttribute('data-theater');
                const date = this.getAttribute('data-date');
                
                // Hide all showtimes containers for this theater
                document.querySelectorAll(`[id^="theater-${theaterId}-date-"]`).forEach(container => {
                    container.style.display = 'none';
                });
                
                // Show the selected date's showtimes
                document.getElementById(`theater-${theaterId}-date-${date}`).style.display = 'block';
                
                // Update active tab styling
                document.querySelectorAll(`.date-tab[data-theater="${theaterId}"]`).forEach(t => {
                    t.classList.remove('active');
                    t.classList.add('bg-gray-800', 'bg-opacity-50');
                });
                
                this.classList.add('active');
                this.classList.remove('bg-gray-800', 'bg-opacity-50');
            });
        });
        
        // Trailer functionality
        const trailerModal = document.getElementById('trailerModal');
        const trailerPlayer = document.getElementById('trailerPlayer');
        const trailerTitle = document.getElementById('trailerTitle');
        
        function playTrailer(trailerUrl, movieTitle) {
            if (trailerModal && trailerPlayer) {
                trailerModal.classList.remove('hidden');
                
                // Set title
                if (trailerTitle) {
                    trailerTitle.textContent = movieTitle || 'Movie Trailer';
                }
                
                // Extract YouTube video ID
                let videoId = '';
                if (trailerUrl.includes('youtube.com')) {
                    videoId = new URL(trailerUrl).searchParams.get('v');
                } else if (trailerUrl.includes('youtu.be')) {
                    videoId = trailerUrl.split('/').pop();
                }
                
                if (videoId) {
                    trailerPlayer.src = `https://www.youtube.com/embed/${videoId}?autoplay=1&rel=0`;
                } else {
                    // Fallback for non-YouTube videos
                    trailerPlayer.src = trailerUrl;
                }
                
                // Prevent body scrolling
                document.body.style.overflow = 'hidden';
            }
        }
        
        function closeTrailerModal() {
            if (trailerModal && trailerPlayer) {
                trailerModal.classList.add('hidden');
                trailerPlayer.src = '';
                
                // Re-enable body scrolling
                document.body.style.overflow = '';
            }
        }
        
        // Close modal with escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeTrailerModal();
            }
        });
        
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                
                const targetId = this.getAttribute('href');
                const targetElement = document.querySelector(targetId);
                
                if (targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 80,
                        behavior: 'smooth'
                    });
                }
            });
        });
        
        // Auto-dismiss alerts after 5 seconds
        setTimeout(() => {
            const alert = document.getElementById('alertMessage');
            if (alert) {
                alert.classList.add('opacity-0', 'translate-x-full');
                setTimeout(() => {
                    alert.remove();
                }, 500);
            }
        }, 5000);
    </script>
</body>
</html>

