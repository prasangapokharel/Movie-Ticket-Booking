<?php 
include '../model/Index.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CineBook - Movie Booking System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #000000; color: #ffffff; }
        .movie-card { background: linear-gradient(145deg, #1a1a1a, #2d2d2d); }
        .movie-card:hover { transform: translateY(-5px); transition: all 0.3s ease; }
        .book-button { background: linear-gradient(45deg, #ff6b6b, #ee5a24); }
        .book-button:hover { background: linear-gradient(45deg, #ee5a24, #ff6b6b); }
        .search-input { background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); }
        .genre-tag { background: rgba(255,255,255,0.1); }
        .show-time-btn { background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); }
        .show-time-btn:hover { background: rgba(255,255,255,0.2); }
        .location-modal { background: linear-gradient(145deg, #1a1a1a, #2d2d2d); }
    </style>
</head>
<body>
    <?php include '../includes/nav.php'; ?>
    
    <div class="page-content">
        <!-- Location Selection Modal -->
        <?php if (!isset($_SESSION['location'])): ?>
        <div id="locationModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-60">
            <div class="location-modal p-8 rounded-2xl shadow-2xl max-w-md w-full mx-4">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-white">Select Your Location</h2>
                    <i class="fas fa-map-marker-alt text-2xl text-red-400"></i>
                </div>
                <p class="text-gray-300 mb-6 text-sm leading-relaxed">Please select your location to see movie showtimes near you.</p>
                
                <form method="post" action="">
                    <div class="mb-6">
                        <label for="location" class="block text-sm font-medium text-gray-300 mb-2">Your Location</label>
                        <div class="relative">
                            <select id="location" name="location" class="w-full search-input rounded-lg py-3 px-4 pl-10 text-white focus:outline-none appearance-none">
                                <?php foreach ($locations as $loc): ?>
                                    <option value="<?php echo htmlspecialchars($loc); ?>"><?php echo htmlspecialchars($loc); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <i class="fas fa-map-marker-alt text-red-400 absolute left-3 top-3.5"></i>
                        </div>
                    </div>
                    
                    <button type="submit" name="select_location" class="w-full book-button py-3 rounded-lg font-medium text-white shadow-lg transition-all">
                        <i class="fas fa-search mr-2"></i>Explore Movies
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Hero Section -->
        <section class="relative bg-gradient-to-r from-gray-900 via-black to-gray-900 py-20">
            <div class="container mx-auto px-4 text-center">
                <h1 class="text-5xl md:text-7xl font-bold mb-6 bg-gradient-to-r from-red-400 to-red-600 bg-clip-text text-transparent">
                    CineBook
                </h1>
                <p class="text-xl md:text-2xl text-gray-300 mb-8">Your Ultimate Movie Booking Experience</p>
                <div class="flex justify-center space-x-8 text-gray-400">
                    <div class="text-center">
                        <i class="fas fa-film text-3xl text-red-400 mb-2"></i>
                        <p>Latest Movies</p>
                    </div>
                    <div class="text-center">
                        <i class="fas fa-ticket-alt text-3xl text-red-400 mb-2"></i>
                        <p>Easy Booking</p>
                    </div>
                    <div class="text-center">
                        <i class="fas fa-star text-3xl text-red-400 mb-2"></i>
                        <p>Best Experience</p>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Now Showing Section -->
        <section id="now-showing" class="container mx-auto px-4 py-20">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-12">
                <h2 class="text-3xl font-bold text-white mb-6 md:mb-0">
                    Now Showing in 
                    <span class="text-red-400"><?php echo isset($_SESSION['location']) ? htmlspecialchars($_SESSION['location']) : ''; ?></span>
                </h2>
                <div class="flex items-center space-x-4">
                    <div class="relative w-full md:w-64">
                        <input type="text" id="searchInput" placeholder="Search movies..." 
                               class="w-full search-input rounded-full py-2.5 px-4 pl-10 text-white focus:outline-none">
                        <i class="fas fa-search text-red-400 absolute left-3 top-2.5"></i>
                    </div>
                    <?php if (isset($_SESSION['location'])): ?>
                    <form method="post" action="" class="hidden md:block">
                        <div class="relative">
                            <select id="change_location" name="location" 
                                    class="search-input rounded-lg py-2 px-4 pl-10 text-white focus:outline-none appearance-none pr-10" 
                                    onchange="this.form.submit()">
                                <?php foreach ($locations as $loc): ?>
                                    <option value="<?php echo htmlspecialchars($loc); ?>" <?php echo $loc === $_SESSION['location'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($loc); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <i class="fas fa-map-marker-alt text-red-400 absolute left-3 top-2.5"></i>
                        </div>
                        <input type="hidden" name="select_location" value="1">
                    </form>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Movie Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8" id="movieGrid">
                <?php if (empty($movies)): ?>
                    <div class="col-span-full text-center py-16">
                        <i class="fas fa-film text-6xl text-gray-600 mb-6"></i>
                        <h3 class="text-2xl font-semibold mb-3">No Movies Available</h3>
                        <p class="text-gray-400 max-w-lg mx-auto">There are no movies showing in your selected location at this time.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($movies as $movie): ?>
                    <div class="movie-card rounded-xl overflow-hidden shadow-lg">
                        <div class="relative h-80">
                            <img src="<?php echo !empty($movie['poster_url']) ? htmlspecialchars($movie['poster_url']) : '/placeholder.svg?height=400&width=300'; ?>" 
                                 alt="<?php echo htmlspecialchars($movie['title']); ?>" 
                                 class="w-full h-full object-cover">
                            <?php if (!empty($movie['trailer_url'])): ?>
                            <div class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center opacity-0 hover:opacity-100 transition-opacity">
                                <button onclick="playTrailer('<?php echo htmlspecialchars($movie['trailer_url']); ?>', '<?php echo htmlspecialchars($movie['title']); ?>')"
                                        class="bg-red-600 hover:bg-red-700 text-white p-4 rounded-full">
                                    <i class="fas fa-play text-2xl"></i>
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="p-6">
                            <h3 class="text-xl font-semibold mb-2 text-white"><?php echo htmlspecialchars($movie['title']); ?></h3>
                            
                            <?php if (isset($movie['rating']) && $movie['rating']): ?>
                            <div class="flex items-center mb-3">
                                <?php 
                                $rating = min(5, max(0, $movie['rating']));
                                $fullStars = floor($rating);
                                $halfStar = $rating - $fullStars >= 0.5;
                                
                                for ($i = 0; $i < $fullStars; $i++): ?>
                                    <i class="fas fa-star text-yellow-400"></i>
                                <?php endfor; ?>
                                
                                <?php if ($halfStar): ?>
                                    <i class="fas fa-star-half-alt text-yellow-400"></i>
                                <?php endif; ?>
                                
                                <span class="ml-2 text-sm text-gray-400"><?php echo $movie['rating']; ?>/5</span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (isset($movie['genre'])): ?>
                            <div class="flex flex-wrap gap-2 mb-4">
                                <?php 
                                $genres = explode(',', $movie['genre']);
                                foreach ($genres as $genre): 
                                    $genre = trim($genre);
                                    if (!empty($genre)):
                                ?>
                                    <span class="genre-tag text-xs px-3 py-1 rounded-full text-gray-300"><?php echo htmlspecialchars($genre); ?></span>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </div>
                            <?php endif; ?>
                            
                            <div class="flex items-center text-sm text-gray-400 mb-4">
                                <i class="fas fa-clock mr-1 text-red-400"></i>
                                <?php echo $movie['duration']; ?> min
                                <span class="mx-2">•</span>
                                <span><?php echo htmlspecialchars($movie['language']); ?></span>
                            </div>
                            
                            <!-- Showtimes -->
                            <div class="mb-4">
                                <h4 class="text-sm font-medium text-gray-300 mb-3">Showtimes:</h4>
                                <div class="flex flex-wrap gap-2">
                                    <?php 
                                    $currentDateTime = date('Y-m-d H:i:s');
                                    $showCount = 0;
                                    foreach ($movie['shows'] as $show): 
                                        if (strtotime($show['show_time']) > strtotime($currentDateTime) && $showCount < 4):
                                            $showTime = date('h:i A', strtotime($show['show_time']));
                                    ?>
                                        <button class="show-time-btn text-xs px-3 py-1.5 rounded-full text-white hover:bg-red-600 transition-colors" 
                                                onclick="window.location.href='booking.php?show_id=<?php echo $show['show_id']; ?>'">
                                            <?php echo $showTime; ?>
                                        </button>
                                    <?php 
                                        $showCount++;
                                        endif;
                                    endforeach; 
                                    ?>
                                </div>
                            </div>
                            
                            <div class="flex items-center justify-between">
                                <div class="text-sm text-gray-400">
                                    <span class="text-red-400 font-medium">From </span>
                                    <span class="text-lg font-semibold text-white">Rs<?php 
                                        $prices = array_column($movie['shows'], 'price');
                                        echo !empty($prices) ? number_format(min($prices), 2) : '0.00'; 
                                    ?></span>
                                </div>
                                <a href="movie_detail.php?id=<?php echo $movie['movie_id']; ?>" 
                                   class="book-button px-6 py-2 text-white rounded-lg font-medium transition-all hover:shadow-lg">
                                    Book Now
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
        
        <!-- Coming Soon Section -->
        <section class="container mx-auto px-4 py-20">
            <h2 class="text-3xl font-bold text-white mb-10">Coming Soon</h2>
            
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-6">
                <?php if (isset($comingSoonMovies) && !empty($comingSoonMovies)): ?>
                    <?php foreach ($comingSoonMovies as $movie): ?>
                    <div class="movie-card rounded-xl overflow-hidden shadow-lg">
                        <div class="h-48">
                            <img src="<?php echo !empty($movie['poster_url']) ? htmlspecialchars($movie['poster_url']) : '/placeholder.svg?height=300&width=200'; ?>" 
                                 alt="<?php echo htmlspecialchars($movie['title']); ?>" 
                                 class="w-full h-full object-cover">
                        </div>
                        <div class="p-4">
                            <h3 class="text-sm font-medium text-white mb-1 truncate"><?php echo htmlspecialchars($movie['title']); ?></h3>
                            <p class="text-xs text-gray-400">
                                <?php echo !empty($movie['release_date']) ? date('M d, Y', strtotime($movie['release_date'])) : 'Coming Soon'; ?>
                            </p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <?php for ($i = 0; $i < 6; $i++): ?>
                    <div class="movie-card rounded-xl overflow-hidden shadow-lg">
                        <div class="h-48 bg-gray-800 flex items-center justify-center">
                            <i class="fas fa-film text-4xl text-gray-600"></i>
                        </div>
                        <div class="p-4">
                            <div class="h-4 bg-gray-700 rounded mb-2"></div>
                            <div class="h-3 bg-gray-700 rounded w-3/4"></div>
                        </div>
                    </div>
                    <?php endfor; ?>
                <?php endif; ?>
            </div>
        </section>
    </div>
    
    <!-- Video Modal -->
    <div id="trailerModal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
        <div class="absolute inset-0 bg-black bg-opacity-80" onclick="closeTrailerModal()"></div>
        <div class="relative bg-gray-900 rounded-xl overflow-hidden w-11/12 max-w-4xl">
            <div class="absolute top-4 right-4 z-10">
                <button onclick="closeTrailerModal()" class="bg-gray-800 hover:bg-gray-700 text-white p-2 rounded-full">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="trailerContainer" class="relative pt-16:9 h-0 pb-[56.25%]">
                <iframe id="trailerPlayer" class="absolute top-0 left-0 w-full h-full" frameborder="0" allowfullscreen></iframe>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script>
        // Search functionality
        const searchInput = document.getElementById('searchInput');
        const movieGrid = document.getElementById('movieGrid');
        const movieCards = movieGrid.querySelectorAll('.movie-card');
        
        searchInput.addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            
            movieCards.forEach(card => {
                const title = card.querySelector('h3').textContent.toLowerCase();
                const genres = Array.from(card.querySelectorAll('.genre-tag')).map(tag => tag.textContent.toLowerCase());
                
                if (title.includes(searchTerm) || genres.some(genre => genre.includes(searchTerm))) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
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
    </script>
</body>
</html>
