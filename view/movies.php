<?php 
include '../model/Movies.php';
?>

    <title>Movies - Movie Booking System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="../assets/js/talwind.js"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #0f172a;
            color: #f1f5f9;
            min-height: 100vh;
        }
        
        .movie-card {
            background: linear-gradient(to bottom, rgba(30, 41, 59, 0.7), rgba(15, 23, 42, 0.9));
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
            height: 100%;
            overflow: hidden;
        }
        
        .movie-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 10px 10px -5px rgba(0, 0, 0, 0.2);
            z-index: 10;
        }
        
        .movie-poster {
            height: 360px;
            background-size: cover;
            background-position: center;
            position: relative;
            overflow: hidden;
        }
        
        .movie-poster::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 70%;
            background: linear-gradient(to top, rgba(15, 23, 42, 1), transparent);
        }
        
        .movie-info {
            transform: translateY(70%);
            transition: transform 0.3s ease;
        }
        
        .movie-card:hover .movie-info {
            transform: translateY(0);
        }
        
        .movie-title {
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
        }
        
        .form-input {
            background-color: rgba(30, 41, 59, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
            transition: all 0.3s ease;
        }
        
        .form-input:focus {
            background-color: rgba(30, 41, 59, 0.9);
            border-color: rgba(99, 102, 241, 0.5);
            box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.25);
        }
        
        .filter-button {
            background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%);
            transition: all 0.3s ease;
        }
        
        .filter-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(185, 28, 28, 0.3);
        }
        
        .badge {
            background-color: rgba(239, 68, 68, 0.2);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        
        .rating {
            background-color: rgba(245, 158, 11, 0.2);
            color: #fbbf24;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }
        
        .now-showing {
            background-color: rgba(16, 185, 129, 0.2);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        
        .coming-soon {
            background-color: rgba(99, 102, 241, 0.2);
            color: #818cf8;
            border: 1px solid rgba(99, 102, 241, 0.3);
        }
        
        .book-button {
            background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%);
            transition: all 0.3s ease;
        }
        
        .book-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(185, 28, 28, 0.3);
        }
        
        .trailer-button {
            background-color: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }
        
        .trailer-button:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }
        
        .hero-section {
            background-image: linear-gradient(to bottom, rgba(15, 23, 42, 0.8), rgba(15, 23, 42, 1)), url('assets/images/cinema-background.jpg');
            background-size: cover;
            background-position: center;
        }
        
        /* Scrollbar styling */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: rgba(15, 23, 42, 0.8);
        }
        
        ::-webkit-scrollbar-thumb {
            background: rgba(239, 68, 68, 0.5);
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(239, 68, 68, 0.7);
        }
    </style>
</head>
<body>
<?php include '../includes/nav.php'; ?>

<!-- Hero Section -->
<div class="hero-section py-16 mb-8">
    <div class="container mx-auto px-4">
        <h1 class="text-4xl md:text-5xl font-bold mb-4">Explore Our Movies</h1>
        <p class="text-xl text-gray-300 max-w-2xl">Discover the latest blockbusters, timeless classics, and everything in between. Book your tickets now for an unforgettable cinematic experience.</p>
    </div>
</div>

<div class="container mx-auto px-4 pb-16">
    <?php if (isset($error_message)): ?>
    <div class="bg-red-900/80 backdrop-blur-md text-red-200 px-4 py-3 rounded-lg shadow-lg border border-red-800/50 mb-6">
        <?php echo htmlspecialchars($error_message); ?>
    </div>
    <?php endif; ?>
    
    <!-- Filters -->
    <div class="bg-gray-800/50 rounded-lg p-6 mb-8">
        <form method="GET" class="flex flex-col md:flex-row gap-4">
            <div class="flex-1">
                <label for="search" class="block text-sm font-medium text-gray-300 mb-1">Search</label>
                <input 
                    type="text" 
                    id="search" 
                    name="search" 
                    placeholder="Search by title, cast, director..." 
                    value="<?php echo htmlspecialchars($search_term ?? ''); ?>"
                    class="form-input w-full rounded-lg px-4 py-2"
                >
            </div>
            
            <div class="md:w-1/4">
                <label for="genre" class="block text-sm font-medium text-gray-300 mb-1">Genre</label>
                <select id="genre" name="genre" class="form-input w-full rounded-lg px-4 py-2">
                    <option value="">All Genres</option>
                    <?php foreach ($genres as $genre): ?>
                        <option value="<?php echo htmlspecialchars($genre); ?>" <?php echo ($filter_genre === $genre) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($genre); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="md:w-1/4">
                <label for="language" class="block text-sm font-medium text-gray-300 mb-1">Language</label>
                <select id="language" name="language" class="form-input w-full rounded-lg px-4 py-2">
                    <option value="">All Languages</option>
                    <?php foreach ($languages as $language): ?>
                        <option value="<?php echo htmlspecialchars($language); ?>" <?php echo ($filter_language === $language) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($language); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="md:w-auto flex items-end">
                <button type="submit" class="filter-button px-6 py-2 rounded-lg text-white font-medium">
                    <span class="flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        Filter
                    </span>
                </button>
            </div>
            
            <?php if (!empty($filter_genre) || !empty($filter_language) || !empty($search_term)): ?>
            <div class="md:w-auto flex items-end">
                <a href="movies.php" class="text-gray-400 hover:text-white px-4 py-2 rounded-lg border border-gray-700 hover:border-gray-600">
                    <span class="flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Clear Filters
                    </span>
                </a>
            </div>
            <?php endif; ?>
        </form>
    </div>
    
    <!-- Now Showing Section -->
    <?php
    $now_showing = array_filter($movies, function($movie) {
        return strtolower($movie['status']) === 'now showing';
    });
    
    if (!empty($now_showing)):
    ?>
    <div class="mb-12">
        <h2 class="text-2xl font-bold mb-6 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
            </svg>
            Now Showing
        </h2>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <?php foreach ($now_showing as $movie): ?>
                <div class="movie-card rounded-xl overflow-hidden shadow-lg">
                    <div class="movie-poster" style="background-image: url('<?php echo !empty($movie['poster_url']) ? htmlspecialchars($movie['poster_url']) : 'assets/images/movie-placeholder.jpg'; ?>');">
                        <?php if (empty($movie['poster_url'])): ?>
                            <!-- Fallback image if no poster -->
                            <div class="w-full h-full flex items-center justify-center bg-gray-800">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
                                </svg>
                            </div>
                        <?php endif; ?>
                        
                        <div class="absolute bottom-0 left-0 right-0 p-6 z-10">
                            <h3 class="text-xl font-bold movie-title"><?php echo htmlspecialchars($movie['title']); ?></h3>
                            
                            <div class="flex flex-wrap gap-2 mt-2">
                                <?php if (!empty($movie['rating'])): ?>
                                <div class="rating text-xs px-2 py-1 rounded-full">
                                    <?php echo htmlspecialchars($movie['rating']); ?>/10
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($movie['duration'])): ?>
                                <div class="badge text-xs px-2 py-1 rounded-full">
                                    <?php echo htmlspecialchars($movie['duration']); ?> min
                                </div>
                                <?php endif; ?>
                                
                                <div class="now-showing text-xs px-2 py-1 rounded-full">
                                    Now Showing
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-6 movie-info">
                        <?php if (!empty($movie['genre'])): ?>
                        <div class="mb-3">
                            <span class="text-gray-400 text-sm">Genre:</span>
                            <span class="text-gray-300"><?php echo htmlspecialchars($movie['genre']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($movie['language'])): ?>
                        <div class="mb-3">
                            <span class="text-gray-400 text-sm">Language:</span>
                            <span class="text-gray-300"><?php echo htmlspecialchars($movie['language']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($movie['release_date'])): ?>
                        <div class="mb-3">
                            <span class="text-gray-400 text-sm">Release Date:</span>
                            <span class="text-gray-300"><?php echo date('d M Y', strtotime($movie['release_date'])); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($movie['director'])): ?>
                        <div class="mb-3">
                            <span class="text-gray-400 text-sm">Director:</span>
                            <span class="text-gray-300"><?php echo htmlspecialchars($movie['director']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($movie['description'])): ?>
                        <div class="mb-4">
                            <p class="text-gray-400 text-sm line-clamp-3"><?php echo htmlspecialchars($movie['description']); ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <div class="flex gap-2 mt-4">
                            <a href="movie_detail.php?id=<?php echo $movie['movie_id']; ?>" class="book-button flex-1 text-center text-white font-medium py-2 px-4 rounded-lg">
                                Book Now
                            </a>
                            
                            <?php if (!empty($movie['trailer_url'])): ?>
                            <a href="<?php echo htmlspecialchars($movie['trailer_url']); ?>" target="_blank" class="trailer-button flex items-center justify-center px-3 rounded-lg">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd" />
                                </svg>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Coming Soon Section -->
    <?php
    $coming_soon = array_filter($movies, function($movie) {
        return strtolower($movie['status']) === 'coming soon';
    });
    
    if (!empty($coming_soon)):
    ?>
    <div class="mb-12">
        <h2 class="text-2xl font-bold mb-6 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Coming Soon
        </h2>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <?php foreach ($coming_soon as $movie): ?>
                <div class="movie-card rounded-xl overflow-hidden shadow-lg">
                    <div class="movie-poster" style="background-image: url('<?php echo !empty($movie['poster_url']) ? htmlspecialchars($movie['poster_url']) : 'assets/images/movie-placeholder.jpg'; ?>');">
                        <?php if (empty($movie['poster_url'])): ?>
                            <!-- Fallback image if no poster -->
                            <div class="w-full h-full flex items-center justify-center bg-gray-800">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
                                </svg>
                            </div>
                        <?php endif; ?>
                        
                        <div class="absolute bottom-0 left-0 right-0 p-6 z-10">
                            <h3 class="text-xl font-bold movie-title"><?php echo htmlspecialchars($movie['title']); ?></h3>
                            
                            <div class="flex flex-wrap gap-2 mt-2">
                                <?php if (!empty($movie['rating'])): ?>
                                <div class="rating text-xs px-2 py-1 rounded-full">
                                    <?php echo htmlspecialchars($movie['rating']); ?>/10
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($movie['duration'])): ?>
                                <div class="badge text-xs px-2 py-1 rounded-full">
                                    <?php echo htmlspecialchars($movie['duration']); ?> min
                                </div>
                                <?php endif; ?>
                                
                                <div class="coming-soon text-xs px-2 py-1 rounded-full">
                                    Coming Soon
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-6 movie-info">
                        <?php if (!empty($movie['genre'])): ?>
                        <div class="mb-3">
                            <span class="text-gray-400 text-sm">Genre:</span>
                            <span class="text-gray-300"><?php echo htmlspecialchars($movie['genre']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($movie['language'])): ?>
                        <div class="mb-3">
                            <span class="text-gray-400 text-sm">Language:</span>
                            <span class="text-gray-300"><?php echo htmlspecialchars($movie['language']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($movie['release_date'])): ?>
                        <div class="mb-3">
                            <span class="text-gray-400 text-sm">Release Date:</span>
                            <span class="text-gray-300"><?php echo date('d M Y', strtotime($movie['release_date'])); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($movie['director'])): ?>
                        <div class="mb-3">
                            <span class="text-gray-400 text-sm">Director:</span>
                            <span class="text-gray-300"><?php echo htmlspecialchars($movie['director']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($movie['description'])): ?>
                        <div class="mb-4">
                            <p class="text-gray-400 text-sm line-clamp-3"><?php echo htmlspecialchars($movie['description']); ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <div class="flex gap-2 mt-4">
                            <a href="movie_detail.php?id=<?php echo $movie['movie_id']; ?>" class="flex-1 text-center bg-gray-700 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded-lg transition duration-300">
                                View Details
                            </a>
                            
                            <?php if (!empty($movie['trailer_url'])): ?>
                            <a href="<?php echo htmlspecialchars($movie['trailer_url']); ?>" target="_blank" class="trailer-button flex items-center justify-center px-3 rounded-lg">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd" />
                                </svg>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- All Movies (if no filters or no categorized movies) -->
    <?php if (empty($now_showing) && empty($coming_soon) || (!empty($filter_genre) || !empty($filter_language) || !empty($search_term))): ?>
        <?php if (empty($movies)): ?>
            <div class="text-center py-16">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-gray-600 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
                </svg>
                <h3 class="text-xl font-medium text-gray-400">No movies found</h3>
                <p class="text-gray-500 mt-2">Try adjusting your search or filter criteria</p>
            </div>
        <?php else: ?>
            <div>
                <h2 class="text-2xl font-bold mb-6 flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    All Movies
                </h2>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    <?php foreach ($movies as $movie): ?>
                        <div class="movie-card rounded-xl overflow-hidden shadow-lg">
                            <div class="movie-poster" style="background-image: url('<?php echo !empty($movie['poster_url']) ? htmlspecialchars($movie['poster_url']) : 'assets/images/movie-placeholder.jpg'; ?>');">
                                <?php if (empty($movie['poster_url'])): ?>
                                    <!-- Fallback image if no poster -->
                                    <div class="w-full h-full flex items-center justify-center bg-gray-800">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
                                        </svg>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="absolute bottom-0 left-0 right-0 p-6 z-10">
                                    <h3 class="text-xl font-bold movie-title"><?php echo htmlspecialchars($movie['title']); ?></h3>
                                    
                                    <div class="flex flex-wrap gap-2 mt-2">
                                        <?php if (!empty($movie['rating'])): ?>
                                        <div class="rating text-xs px-2 py-1 rounded-full">
                                            <?php echo htmlspecialchars($movie['rating']); ?>/10
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($movie['duration'])): ?>
                                        <div class="badge text-xs px-2 py-1 rounded-full">
                                            <?php echo htmlspecialchars($movie['duration']); ?> min
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if (strtolower($movie['status']) === 'now showing'): ?>
                                        <div class="now-showing text-xs px-2 py-1 rounded-full">
                                            Now Showing
                                        </div>
                                        <?php elseif (strtolower($movie['status']) === 'coming soon'): ?>
                                        <div class="coming-soon text-xs px-2 py-1 rounded-full">
                                            Coming Soon
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="p-6 movie-info">
                                <?php if (!empty($movie['genre'])): ?>
                                <div class="mb-3">
                                    <span class="text-gray-400 text-sm">Genre:</span>
                                    <span class="text-gray-300"><?php echo htmlspecialchars($movie['genre']); ?></span>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($movie['language'])): ?>
                                <div class="mb-3">
                                    <span class="text-gray-400 text-sm">Language:</span>
                                    <span class="text-gray-300"><?php echo htmlspecialchars($movie['language']); ?></span>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($movie['release_date'])): ?>
                                <div class="mb-3">
                                    <span class="text-gray-400 text-sm">Release Date:</span>
                                    <span class="text-gray-300"><?php echo date('d M Y', strtotime($movie['release_date'])); ?></span>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($movie['director'])): ?>
                                <div class="mb-3">
                                    <span class="text-gray-400 text-sm">Director:</span>
                                    <span class="text-gray-300"><?php echo htmlspecialchars($movie['director']); ?></span>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($movie['description'])): ?>
                                <div class="mb-4">
                                    <p class="text-gray-400 text-sm line-clamp-3"><?php echo htmlspecialchars($movie['description']); ?></p>
                                </div>
                                <?php endif; ?>
                                
                                <div class="flex gap-2 mt-4">
                                    <?php if (strtolower($movie['status']) === 'now showing'): ?>
                                    <a href="movie_detail.php?id=<?php echo $movie['movie_id']; ?>" class="book-button flex-1 text-center text-white font-medium py-2 px-4 rounded-lg">
                                        Book Now
                                    </a>
                                    <?php else: ?>
                                    <a href="movie_detail.php?id=<?php echo $movie['movie_id']; ?>" class="flex-1 text-center bg-gray-700 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded-lg transition duration-300">
                                        View Details
                                    </a>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($movie['trailer_url'])): ?>
                                    <a href="<?php echo htmlspecialchars($movie['trailer_url']); ?>" target="_blank" class="trailer-button flex items-center justify-center px-3 rounded-lg">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd" />
                                        </svg>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-submit form when filters change
        document.getElementById('genre').addEventListener('change', function() {
            this.form.submit();
        });
        
        document.getElementById('language').addEventListener('change', function() {
            this.form.submit();
        });
        
        // Trailer link handling - open in modal or new window
        const trailerLinks = document.querySelectorAll('a[href*="youtube"], a[href*="vimeo"]');
        trailerLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                // You can implement a modal here if desired
                // For now, just open in a new window
                e.preventDefault();
                window.open(this.href, '_blank', 'width=800,height=500');
            });
        });
    });
</script>
</body>
</html>