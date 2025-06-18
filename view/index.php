<?php 
include '../model/Index.php';


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CineBook - Movie Booking System</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="../assets/js/talwind.js"></script>
    <script src="../assets/js/privacy.js"></script>
    <link href="../assets/css/index.css" rel="stylesheet">
   
</head>
<body>
    <?php include '../includes/nav.php'; ?>
    <div class="page-content">
        <?php if ($needsSetup): ?>
        <div class="container mx-auto px-4 mt-4">
     
        </div>
        <?php endif; ?>
        
        <?php if ($debug && !empty($debugInfo)): ?>
        <div class="container mx-auto px-4 mt-4">
            <div class="debug-panel">
                <h3>Debug Information</h3>
                <pre><?php echo json_encode($debugInfo, JSON_PRETTY_PRINT); ?></pre>
            </div>
        </div>
        <?php endif; ?>
                <?php if (!isset($_SESSION['location'])): ?>
        <div id="locationModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-60">
            <div class="location-modal p-8 rounded-2xl shadow-2xl max-w-md w-full mx-4 transform transition-all">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold heading-gradient">Select Your Location</h2>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-indigo-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <p class="text-gray-300 mb-6 text-sm font-light leading-relaxed">Please select your location to see movie showtimes near you. We'll remember your preference for future visits.</p>
                
                <form method="post" action="">
                    <div class="mb-6">
                        <label for="location" class="block text-sm font-medium text-gray-300 mb-2">Your Location</label>
                        <div class="relative">
                            <select id="location" name="location" class="w-full search-input rounded-lg py-3 px-4 pl-10 text-white focus:outline-none appearance-none">
                                <?php foreach ($locations as $loc): ?>
                                    <option value="<?php echo htmlspecialchars($loc); ?>"><?php echo htmlspecialchars($loc); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-300 absolute left-3 top-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 absolute right-3 top-3.5 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </div>
                    
                    <button type="submit" name="select_location" class="w-full book-button py-3 rounded-lg font-medium text-white shadow-lg transition-all">
                        Explore Movies
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>
        
    <!-- Hero Section -->
 <?php include '../component/hero.php'; ?>
        
        <!-- Now Showing Section -->
        <section id="now-showing" class="container mx-auto px-4 mb-20">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-12">
                <h2 class="text-3xl font-bold text-white mb-6 md:mb-0 section-heading">
                    Now Showing in 
                    <span class="text-indigo-300"><?php echo isset($_SESSION['location']) ? htmlspecialchars($_SESSION['location']) : ''; ?></span>
                </h2>
                <div class="flex items-center space-x-4">
                    <div class="relative w-full md:w-64">
                        <input type="text" id="searchInput" placeholder="Search movies..." class="w-full search-input rounded-full py-2.5 px-4 pl-10 text-white focus:outline-none">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-300 absolute left-3 top-2.5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <?php if (isset($_SESSION['location'])): ?>
                    <form method="post" action="" class="hidden md:block">
                        <div class="relative">
                            <select id="change_location" name="location" class="search-input rounded-lg py-2 px-4 pl-10 text-white focus:outline-none appearance-none pr-10" onchange="this.form.submit()">
                                <?php foreach ($locations as $loc): ?>
                                    <option value="<?php echo htmlspecialchars($loc); ?>" <?php echo $loc === $_SESSION['location'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($loc); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-300 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.  stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 absolute right-3 top-2.5 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                        <input type="hidden" name="select_location" value="1">
                    </form>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Movie Grid -->
    <div class="movie-grid" id="movieGrid">
                <?php if (empty($movies)): ?>
                    <div class="col-span-full text-center py-16">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-gray-600 mb-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
                        </svg>
                        <h3 class="text-2xl font-semibold mb-3">No Movies Available</h3>
                        <p class="text-gray-400 max-w-lg mx-auto font-light">There are no movies showing in your selected location at this time. Please check back later or choose a different location.</p>
                        
                        <?php if (isset($_SESSION['location'])): ?>
                        <div class="mt-6">
                            <form method="post" action="">
                                <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                                    <div class="relative w-full sm:w-64">
                                        <select id="change_location" name="location" class="w-full search-input rounded-lg py-2.5 px-4 pl-10 text-white focus:outline-none appearance-none">
                                            <?php foreach ($locations as $loc): ?>
                                                <option value="<?php echo htmlspecialchars($loc); ?>" <?php echo $loc === $_SESSION['location'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($loc); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-300 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </div>
                                    <button type="submit" name="select_location" class="book-button py-2.5 px-6 rounded-lg font-medium text-white shadow-lg transition-all">
                                        Change Location
                                    </button>
                                </div>
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <?php foreach ($movies as $movie): ?>
                    <div class="movie-card rounded-xl overflow-hidden">
                        <div class="video-container shimmer poster-container">
                            <img src="<?php echo !empty($movie['poster_url']) ? htmlspecialchars($movie['poster_url']) : 'assets/images/movie-placeholder.jpg'; ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>" class="w-full h-full object-cover">
                            <?php if (!empty($movie['trailer_url'])): ?>
                            <div class="video-overlay">
                                <div class="play-button" onclick="playTrailer('<?php echo htmlspecialchars($movie['trailer_url']); ?>', '<?php echo htmlspecialchars($movie['title']); ?>')">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="p-5 movie-info">
                            <h3 class="text-xl font-semibold mb-2"><?php echo htmlspecialchars($movie['title']); ?></h3>
                            <?php if (isset($movie['rating'])): ?>
                            <div class="flex items-center mb-3">
                                <?php 
                                $rating = min(5, max(0, $movie['rating']));
                                $fullStars = floor($rating);
                                $halfStar = $rating - $fullStars >= 0.5;
                                $emptyStars = 5 - $fullStars - ($halfStar ? 1 : 0);
                                
                                for ($i = 0; $i < $fullStars; $i++): 
                                ?>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-yellow-400 rating-star" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                    </svg>
                                <?php endfor; ?>
                                
                                <?php if ($halfStar): ?>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-yellow-400 rating-star" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                    </svg>
                                <?php endif; ?>
                                
                                <?php for ($i = 0; $i < $emptyStars; $i++): ?>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-600" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                    </svg>
                                <?php endfor; ?>
                                <span class="ml-2 text-sm text-gray-400 font-light"><?php echo $movie['rating']; ?>/5</span>
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
                                    <span class="genre-tag text-xs px-3 py-1 rounded-full"><?php echo htmlspecialchars($genre); ?></span>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </div>
                            <?php endif; ?>
                            <div class="flex items-center text-sm text-gray-400 mb-5 font-light">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-indigo-300" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                                </svg>
                                <?php echo $movie['duration']; ?> min
                                <span class="mx-2 text-gray-600">•</span>
                                <span><?php echo htmlspecialchars($movie['language']); ?></span>
                            </div>
                            
                            <!-- Theaters -->
                            <?php if (!empty($movie['theaters'])): ?>
                            <div class="mb-3">
                                <h4 class="text-sm font-medium text-gray-300 mb-2">Available at:</h4>
                                <div class="flex flex-wrap">
                                    <?php foreach ($movie['theaters'] as $theaterId => $theaterName): ?>
                                        <span class="theater-badge"><?php echo htmlspecialchars($theaterName); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="mb-5">
                                <h4 class="text-sm font-medium text-gray-300 mb-3">Available Showtimes:</h4>
                                <div class="flex flex-wrap gap-2">
                                    <?php 
                                    $showIndex = 0;
                                    $currentDate = date('Y-m-d');
                                    $currentDateTime = date('Y-m-d H:i:s');
                                    $displayedDates = [];
                                    
                                    // Sort shows by time
                                    usort($movie['shows'], function($a, $b) {
                                        return strtotime($a['show_time']) - strtotime($b['show_time']);
                                    });
                                    
                                    foreach ($movie['shows'] as $show): 
                                        $showDate = date('Y-m-d', strtotime($show['show_time']));
                                        $showTime = date('h:i A', strtotime($show['show_time']));
                                        $primeTime = (strtotime($show['show_time']) >= strtotime($showDate . ' 18:00:00') && 
                                                     strtotime($show['show_time']) <= strtotime($showDate . ' 21:00:00'));
                                        
                                        // Only show future showtimes
                                        if (strtotime($show['show_time']) > strtotime($currentDateTime)):
                                            
                                            // Add date separator if this is a new date
                                            if (!in_array($showDate, $displayedDates)):
                                                $displayedDates[] = $showDate;
                                                $dateDisplay = ($showDate === $currentDate) ? 'Today' : date('D, M j', strtotime($showDate));
                                                if ($showIndex > 0): // Don't add separator before the first date
                                                ?>
                                                    <div class="w-full date-separator"><?php echo $dateDisplay; ?></div>
                                                <?php 
                                                endif;
                                            endif;
                                            
                                            // Only show first 6 showtimes
                                            if ($showIndex < 6):
                                        ?>
                                            <button class="show-time-btn <?php echo $primeTime ? 'prime-time' : ''; ?> text-xs px-3 py-1.5 rounded-full flex items-center" 
                                                    onclick="window.location.href='booking.php?show_id=<?php echo $show['show_id']; ?>'">
                                                <span><?php echo $showTime; ?></span>
                                                <?php if($primeTime): ?>
                                                    <span class="ml-1 w-1.5 h-1.5 rounded-full bg-yellow-400"></span>
                                                <?php endif; ?>
                                            </button>
                                        <?php 
                                            $showIndex++;
                                            endif; 
                                        endif;
                                    endforeach; 
                                    
                                    // Count future shows
                                    $futureShows = 0;
                                    foreach ($movie['shows'] as $show) {
                                        if (strtotime($show['show_time']) > strtotime($currentDateTime)) {
                                            $futureShows++;
                                        }
                                    }
                                    
                                    // Show "more" button if needed
                                    if ($futureShows > 6): 
                                    ?>
                                        <button class="show-time-btn bg-indigo-600 hover:bg-indigo-700 text-white text-xs px-3 py-1.5 rounded-full" 
                                                onclick="window.location.href='movie_detail.php?id=<?php echo $movie['movie_id']; ?>'">
                                            +<?php echo $futureShows - 6; ?> more
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="movie-info-footer mt-auto pt-4 border-t border-gray-700">
    <div class="flex items-center justify-between mb-3">
        <div class="text-sm text-gray-400">
            <span class="font-medium text-indigo-300">From </span>
            <span class="text-lg font-semibold">₹<?php 
                $prices = array_column($movie['shows'], 'price');
                echo !empty($prices) ? number_format(min($prices), 2) : '0.00'; 
            ?></span>
        </div>
        <?php if (!empty($movie['shows'])): ?>
            <span class="text-xs text-green-400 bg-green-400/10 px-2 py-1 rounded-full">
                <?php echo count($movie['shows']); ?> shows available
            </span>
        <?php endif; ?>
    </div>
    
    <div class="flex gap-2">
        <a href="movie_detail.php?id=<?php echo $movie['movie_id']; ?>" 
           class="book-button flex-1 py-3 px-4 text-center text-white rounded-lg transition duration-300 hover:bg-red-600 bg-red-500 font-medium">
            Book Now
        </a>
        <?php if (!empty($movie['trailer_url'])): ?>
            <button onclick="playTrailer('<?php echo htmlspecialchars($movie['trailer_url']); ?>', '<?php echo htmlspecialchars($movie['title']); ?>')"
                    class="trailer-button px-4 rounded-lg bg-gray-700 hover:bg-gray-600 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd" />
                </svg>
            </button>
        <?php endif; ?>
    </div>
</div>

                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
        
        <!-- Coming Soon Section -->
        <section class="container mx-auto px-4 mb-20">
            <h2 class="text-3xl font-bold text-white mb-10 section-heading">Coming Soon</h2>
            
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-5">
                <?php if (isset($comingSoonMovies) && !empty($comingSoonMovies)): ?>
                    <?php foreach ($comingSoonMovies as $movie): ?>
                    <div class="coming-soon-card rounded-xl overflow-hidden shadow-md">
                        <div class="h-40 bg-gray-800 shimmer">
                            <?php if (!empty($movie['poster_url'])): ?>
                                <img src="<?php echo htmlspecialchars($movie['poster_url']); ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>" class="w-full h-full object-cover">
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
                                    </svg>
                                </div>
                            <?php endif; ?>
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
                    <!-- Placeholder for coming soon movies -->
                    <?php for ($i = 0; $i < 6; $i++): ?>
                    <div class="coming-soon-card rounded-xl overflow-hidden shadow-md">
                        <div class="h-40 bg-gray-800 flex items-center justify-center shimmer">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
                            </svg>
                        </div>
                        <div class="p-4">
                            <div class="h-4 bg-gray-700 rounded mb-2 shimmer"></div>
                            <div class="h-3 bg-gray-700 rounded w-3/4 shimmer"></div>
                        </div>
                    </div>
                    <?php endfor; ?>
                <?php endif; ?>
            </div>
        </section>
    </div>
    
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
        // Search functionality
        const searchInput = document.getElementById('searchInput');
        const movieGrid = document.getElementById('movieGrid');
        const movieCards = movieGrid ? movieGrid.querySelectorAll('.movie-card') : [];
        
        if (searchInput && movieCards.length > 0) {
            searchInput.addEventListener('keyup', function() {
                const searchTerm = this.value.toLowerCase();
                let foundMovies = false;
                
                movieCards.forEach(card => {
                    const title = card.querySelector('h3').textContent.toLowerCase();
                    const genreTags = card.querySelectorAll('.genre-tag');
                    const genres = Array.from(genreTags).map(tag => tag.textContent.toLowerCase());
                    const theaterBadges = card.querySelectorAll('.theater-badge');
                    const theaters = Array.from(theaterBadges).map(badge => badge.textContent.toLowerCase());
                    
                    if (title.includes(searchTerm) || 
                        genres.some(genre => genre.includes(searchTerm)) ||
                        theaters.some(theater => theater.includes(searchTerm))) {
                        card.style.display = 'block';
                        foundMovies = true;
                    } else {
                        card.style.display = 'none';
                    }
                });
                
                // Show no results message if needed
                let noResultsMsg = document.getElementById('noResultsMessage');
                if (!foundMovies && searchTerm) {
                    if (!noResultsMsg) {
                        noResultsMsg = document.createElement('div');
                        noResultsMsg.id = 'noResultsMessage';
                        noResultsMsg.className = 'col-span-full text-center py-8';
                        noResultsMsg.innerHTML = `
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-gray-600 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <h3 class="text-xl font-medium text-gray-400 mb-2">No movies found</h3>
                            <p class="text-gray-500">No movies match your search for "${searchTerm}"</p>
                        `;
                        movieGrid.appendChild(noResultsMsg);
                    }
                } else if (noResultsMsg) {
                    noResultsMsg.remove();
                }
            });
        }
        
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
        
        // Fix image loading errors
        document.addEventListener('DOMContentLoaded', function() {
            const images = document.querySelectorAll('img');
            images.forEach(img => {
                img.addEventListener('error', function() {
                    this.src = '../assets/image/kgf.png';
                });
            });
        });
    </script>
</body>
</html>
