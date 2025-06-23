<?php
session_start();
include '../database/config.php';

// Set timezone to Nepal Standard Time (UTC+5:45)
date_default_timezone_set('Asia/Kathmandu');

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
    $stmt = $conn->prepare("SELECT * FROM movies WHERE movie_id = ? AND status = 'active'");
    $stmt->execute([$movie_id]);
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
            h.hall_name,
            h.total_capacity,
            (SELECT COUNT(*) FROM seats WHERE show_id = s.show_id AND status = 'booked') AS booked_seats
        FROM shows s
        JOIN theaters t ON s.theater_id = t.theater_id
        LEFT JOIN halls h ON s.hall_id = h.hall_id
        WHERE s.movie_id = ? AND s.show_time >= ?
        ORDER BY t.location ASC, s.show_time ASC
    ");
    $stmt->execute([$movie_id, $currentDateTime]);
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
        $capacity = $showtime['total_capacity'] ?? $showtime['capacity'] ?? 100;
        $bookedSeats = $showtime['booked_seats'] ?? 0;
        $availableSeats = $capacity - $bookedSeats;
        $availabilityPercentage = $capacity > 0 ? ($availableSeats / $capacity) * 100 : 0;
        
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
            'available_seats' => $availableSeats,
            'hall_name' => $showtime['hall_name']
        ];
    }
    
    // Fetch similar movies (same genre)
    if (!empty($movie['genre'])) {
        $genres = explode(',', $movie['genre']);
        $primaryGenre = trim($genres[0]);
        
        $stmt = $conn->prepare("
            SELECT movie_id, title, poster_url, rating
            FROM movies
            WHERE movie_id != ? AND genre LIKE ? AND status = 'active'
            ORDER BY release_date DESC
            LIMIT 4
        ");
        $stmt->execute([$movie_id, '%' . $primaryGenre . '%']);
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
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #000000; color: #ffffff; }
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
            background: linear-gradient(to bottom, rgba(0, 0, 0, 0.3), rgba(0, 0, 0, 0.9) 70%, #000000);
        }
        .movie-info {
            position: relative;
            z-index: 10;
            width: 100%;
            padding-bottom: 2rem;
        }
        .genre-tag {
            background-color: rgba(99, 102, 241, 0.15);
            color: #c7d2fe;
            border: 1px solid rgba(99, 102, 241, 0.3);
        }
        .book-button {
            background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%);
        }
        .book-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(185, 28, 28, 0.3);
        }
        .theater-card {
            background: linear-gradient(to bottom right, rgba(30, 41, 59, 0.7), rgba(15, 23, 42, 0.9));
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .theater-card:hover {
            border-color: rgba(255, 255, 255, 0.2);
        }
        .date-tab {
            cursor: pointer;
            transition: all 0.3s ease;
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
        .show-time-btn.almost-full {
            background-color: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }
        .show-time-btn.filling-fast {
            background-color: rgba(245, 158, 11, 0.15);
            border: 1px solid rgba(245, 158, 11, 0.3);
            color: #fcd34d;
        }
    </style>
</head>
<body>
    <?php include '../includes/nav.php'; ?>
    
    <!-- Alert Messages -->
    <?php if (isset($_SESSION['alert'])): ?>
    <div class="fixed top-20 right-4 z-50 max-w-sm" id="alertMessage">
        <div class="<?php echo $_SESSION['alert']['type'] === 'success' ? 'bg-green-900/80 text-green-200 border-green-800/50' : 'bg-red-900/80 text-red-200 border-red-800/50'; ?> backdrop-blur-md px-4 py-3 rounded-lg shadow-lg border">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <i class="fas fa-<?php echo $_SESSION['alert']['type'] === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> text-lg"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium"><?php echo $_SESSION['alert']['message']; ?></p>
                </div>
                <button type="button" onclick="document.getElementById('alertMessage').remove()" class="ml-auto pl-3">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </div>
    <?php unset($_SESSION['alert']); ?>
    <?php endif; ?>
    
    <!-- Hero Section -->
    <section class="hero-section" style="background-image: url('<?php echo htmlspecialchars($movie['poster_url']); ?>');">
        <div class="hero-overlay"></div>
        <div class="container mx-auto px-4 movie-info">
            <div class="flex flex-col md:flex-row items-end md:items-end gap-8">
                <div class="w-64 h-96 flex-shrink-0 hidden md:block">
                    <img src="<?php echo htmlspecialchars($movie['poster_url']); ?>" 
                         alt="<?php echo htmlspecialchars($movie['title']); ?>" 
                         class="w-full h-full object-cover rounded-lg shadow-2xl">
                </div>
                <div class="flex-1">
                    <h1 class="text-4xl md:text-5xl font-bold mb-4"><?php echo htmlspecialchars($movie['title']); ?></h1>
                    
                    <?php if (!empty($movie['genre'])): ?>
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
                        <?php if (!empty($movie['rating'])): ?>
                        <div class="flex items-center mr-6">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star <?php echo $i <= round($movie['rating']) ? 'text-yellow-400' : 'text-gray-600'; ?>"></i>
                            <?php endfor; ?>
                            <span class="ml-2 text-sm text-gray-400"><?php echo $movie['rating']; ?>/5</span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="flex items-center text-sm text-gray-400">
                            <i class="fas fa-clock mr-1 text-indigo-300"></i>
                            <?php echo $movie['duration']; ?> min
                            <span class="mx-2">•</span>
                            <span><?php echo $movie['language']; ?></span>
                            <?php if (!empty($movie['release_date'])): ?>
                            <span class="mx-2">•</span>
                            <span><?php echo date('M d, Y', strtotime($movie['release_date'])); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <p class="text-gray-300 mb-6 max-w-3xl leading-relaxed">
                        <?php echo htmlspecialchars($movie['description']); ?>
                    </p>
                    
                    <div class="flex flex-wrap gap-4 items-center">
                        <?php if (!empty($movie['trailer_url'])): ?>
                        <button onclick="playTrailer('<?php echo htmlspecialchars($movie['trailer_url']); ?>', '<?php echo htmlspecialchars($movie['title']); ?>')" 
                                class="flex items-center gap-2 px-6 py-3 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white transition-all">
                            <i class="fas fa-play"></i>
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
                <h2 class="text-2xl font-bold mb-6">About the Movie</h2>
                
                <div class="bg-gray-900 rounded-xl p-6 mb-8">
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
                <h2 class="text-2xl font-bold mb-6">Similar Movies</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                    <?php foreach ($similarMovies as $similarMovie): ?>
                    <a href="movie_detail.php?id=<?php echo $similarMovie['movie_id']; ?>" 
                       class="bg-gray-900 rounded-lg overflow-hidden hover:transform hover:scale-105 transition-all">
                        <div class="aspect-[2/3]">
                            <img src="<?php echo htmlspecialchars($similarMovie['poster_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($similarMovie['title']); ?>" 
                                 class="w-full h-full object-cover">
                        </div>
                        <div class="p-3">
                            <h3 class="text-sm font-medium text-white truncate"><?php echo htmlspecialchars($similarMovie['title']); ?></h3>
                            <?php if (!empty($similarMovie['rating'])): ?>
                            <div class="flex items-center mt-1">
                                <i class="fas fa-star text-yellow-400 text-xs"></i>
                                <span class="text-xs text-gray-300 ml-1"><?php echo $similarMovie['rating']; ?>/5</span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <div>
                <h2 class="text-2xl font-bold mb-6">Movie Info</h2>
                <div class="bg-gray-900 rounded-xl p-6">
                    <ul class="space-y-4">
                        <li class="flex">
                            <span class="text-gray-400 w-24">Language:</span>
                            <span class="font-medium"><?php echo htmlspecialchars($movie['language']); ?></span>
                        </li>
                        <li class="flex">
                            <span class="text-gray-400 w-24">Release:</span>
                            <span class="font-medium"><?php echo date('F j, Y', strtotime($movie['release_date'])); ?></span>
                        </li>
                        <li class="flex">
                            <span class="text-gray-400 w-24">Duration:</span>
                            <span class="font-medium"><?php echo $movie['duration']; ?> minutes</span>
                        </li>
                        <?php if (!empty($movie['rating'])): ?>
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
        <h2 class="text-3xl font-bold text-white mb-10">Showtimes & Tickets</h2>
        
        <?php if (empty($theaterShowtimes)): ?>
            <div class="bg-gray-900 rounded-lg p-8 text-center">
                <i class="fas fa-clock text-4xl text-gray-600 mb-4"></i>
                <h3 class="text-xl font-semibold mb-2">No Showtimes Available</h3>
                <p class="text-gray-400 max-w-lg mx-auto">There are currently no scheduled showtimes for this movie. Please check back later or explore other movies.</p>
                <a href="index.php" class="mt-6 inline-block px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition-all">
                    Browse Movies
                </a>
            </div>
        <?php else: ?>
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
                                    <i class="fas fa-check mr-1"></i>
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
                                <div class="date-tab px-4 py-2 rounded-lg text-sm whitespace-nowrap border <?php echo $dateIndex === 0 ? 'active' : 'bg-gray-800/50 border-gray-600'; ?>" 
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
                                        $btnClass = $show['availability'];
                                    ?>
                                        <a href="booking.php?show_id=<?php echo $show['show_id']; ?>" 
                                           class="show-time-btn <?php echo $btnClass; ?> px-4 py-2 rounded-lg flex flex-col items-center text-white no-underline">
                                            <span class="font-medium"><?php echo $showTime; ?></span>
                                            <span class="text-xs text-gray-400 mt-1">₹<?php echo number_format($show['price'], 2); ?></span>
                                            <?php if (!empty($show['hall_name'])): ?>
                                            <span class="text-xs text-gray-400"><?php echo htmlspecialchars($show['hall_name']); ?></span>
                                            <?php endif; ?>
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
    <div id="trailerModal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
        <div class="absolute inset-0 bg-black bg-opacity-80" onclick="closeTrailerModal()"></div>
        <div class="relative bg-gray-900 rounded-xl overflow-hidden w-11/12 max-w-4xl">
            <div class="absolute top-4 right-4 z-10">
                <button onclick="closeTrailerModal()" class="bg-gray-800 hover:bg-gray-700 text-white p-2 rounded-full">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="aspect-video">
                <iframe id="trailerPlayer" class="w-full h-full" frameborder="0" allowfullscreen></iframe>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script>
        // Date tab switching
        document.querySelectorAll('.date-tab').forEach(tab => {
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
                    t.classList.add('bg-gray-800', 'border-gray-600');
                });
                
                this.classList.add('active');
                this.classList.remove('bg-gray-800', 'border-gray-600');
            });
        });
        
        // Trailer functionality
        function playTrailer(trailerUrl, movieTitle) {
            const modal = document.getElementById('trailerModal');
            const player = document.getElementById('trailerPlayer');
            
            modal.classList.remove('hidden');
            
            let videoId = '';
            if (trailerUrl.includes('youtube.com')) {
                videoId = new URL(trailerUrl).searchParams.get('v');
            } else if (trailerUrl.includes('youtu.be')) {
                videoId = trailerUrl.split('/').pop();
            }
            
            if (videoId) {
                player.src = `https://www.youtube.com/embed/${videoId}?autoplay=1&rel=0`;
            }
        }
        
        function closeTrailerModal() {
            const modal = document.getElementById('trailerModal');
            const player = document.getElementById('trailerPlayer');
            
            modal.classList.add('hidden');
            player.src = '';
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
        
        // Auto-dismiss alerts
        setTimeout(() => {
            const alert = document.getElementById('alertMessage');
            if (alert) {
                alert.remove();
            }
        }, 5000);
    </script>
</body>
</html>
