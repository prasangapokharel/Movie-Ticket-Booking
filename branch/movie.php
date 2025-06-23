<?php
include '../database/config.php';
session_start();

if (!isset($_SESSION['branch_id'])) {
    header("Location: login.php");
    exit();
}

$branch_id = $_SESSION['branch_id'];

// Fetch all active movies
try {
    $movies_stmt = $conn->query("SELECT * FROM movies WHERE status = 'active' ORDER BY title ASC");
    $movies = $movies_stmt->fetchAll();
} catch (PDOException $e) {
    $movies = [];
}

// Get branch theaters
try {
    $theaters_stmt = $conn->prepare("SELECT * FROM theaters WHERE branch_id = ?");
    $theaters_stmt->execute([$branch_id]);
    $theaters = $theaters_stmt->fetchAll();
} catch (PDOException $e) {
    $theaters = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movies - Branch Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #000000; color: #ffffff; }
    </style>
</head>
<body>
    <?php include '../includes/branchnav.php'; ?>
    
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="px-4 py-6 sm:px-0">
            <h1 class="text-3xl font-bold text-white mb-8">
                <i class="fas fa-video mr-3"></i>Movies
            </h1>
            
            <?php if (empty($movies)): ?>
                <div class="bg-gray-900 border border-gray-700 rounded-lg p-8 text-center">
                    <i class="fas fa-film text-4xl text-gray-400 mb-4"></i>
                    <p class="text-gray-400">No movies available at the moment.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    <?php foreach ($movies as $movie): ?>
                        <div class="bg-gray-900 border border-gray-700 rounded-lg overflow-hidden hover:border-gray-500 transition-colors">
                            <div class="aspect-w-2 aspect-h-3">
                                <img src="<?php echo htmlspecialchars($movie['poster_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($movie['title']); ?>"
                                     class="w-full h-80 object-cover">
                            </div>
                            
                            <div class="p-4">
                                <h3 class="text-lg font-semibold text-white mb-2"><?php echo htmlspecialchars($movie['title']); ?></h3>
                                
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-400">Genre:</span>
                                        <span class="text-white"><?php echo htmlspecialchars($movie['genre']); ?></span>
                                    </div>
                                    
                                    <div class="flex justify-between">
                                        <span class="text-gray-400">Duration:</span>
                                        <span class="text-white"><?php echo $movie['duration']; ?> min</span>
                                    </div>
                                    
                                    <div class="flex justify-between">
                                        <span class="text-gray-400">Language:</span>
                                        <span class="text-white"><?php echo htmlspecialchars($movie['language']); ?></span>
                                    </div>
                                    
                                    <div class="flex justify-between">
                                        <span class="text-gray-400">Rating:</span>
                                        <span class="text-white">
                                            <?php if ($movie['rating']): ?>
                                                <i class="fas fa-star text-yellow-400"></i> <?php echo $movie['rating']; ?>/5
                                            <?php else: ?>
                                                Not Rated
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                    
                                    <div class="flex justify-between">
                                        <span class="text-gray-400">Certificate:</span>
                                        <span class="px-2 py-1 bg-gray-700 text-white text-xs rounded"><?php echo htmlspecialchars($movie['certificate']); ?></span>
                                    </div>
                                </div>
                                
                                <div class="mt-4 pt-3 border-t border-gray-700">
                                    <p class="text-gray-400 text-sm line-clamp-3"><?php echo htmlspecialchars(substr($movie['description'], 0, 100)) . '...'; ?></p>
                                </div>
                                
                                <div class="mt-4 space-y-2">
                                    <?php if ($movie['trailer_url']): ?>
                                        <a href="<?php echo htmlspecialchars($movie['trailer_url']); ?>" target="_blank"
                                           class="w-full bg-gray-700 text-white hover:bg-gray-600 px-4 py-2 rounded text-sm font-medium transition-colors flex items-center justify-center">
                                            <i class="fas fa-play mr-2"></i>Watch Trailer
                                        </a>
                                    <?php endif; ?>
                                    
                                    <button onclick="showMovieDetails(<?php echo $movie['movie_id']; ?>)"
                                            class="w-full bg-white text-black hover:bg-gray-200 px-4 py-2 rounded text-sm font-medium transition-colors">
                                        <i class="fas fa-info-circle mr-2"></i>View Details
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Movie Details Modal -->
    <div id="movieModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-gray-900 border border-gray-700 rounded-lg p-6 max-w-2xl w-full mx-4 max-h-screen overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 id="modalTitle" class="text-xl font-semibold text-white"></h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-white">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div id="modalContent"></div>
        </div>
    </div>
    
    <script>
        function showMovieDetails(movieId) {
            const movies = <?php echo json_encode($movies); ?>;
            const movie = movies.find(m => m.movie_id == movieId);
            
            if (movie) {
                document.getElementById('modalTitle').textContent = movie.title;
                document.getElementById('modalContent').innerHTML = `
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <img src="${movie.poster_url}" alt="${movie.title}" class="w-full rounded-lg">
                        </div>
                        <div class="space-y-4">
                            <div>
                                <h4 class="text-white font-semibold mb-2">Movie Information</h4>
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-400">Director:</span>
                                        <span class="text-white">${movie.director || 'N/A'}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-400">Release Date:</span>
                                        <span class="text-white">${new Date(movie.release_date).toLocaleDateString()}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-400">Duration:</span>
                                        <span class="text-white">${movie.duration} minutes</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-400">Language:</span>
                                        <span class="text-white">${movie.language}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-400">Certificate:</span>
                                        <span class="text-white">${movie.certificate}</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div>
                                <h4 class="text-white font-semibold mb-2">Cast</h4>
                                <p class="text-gray-300 text-sm">${movie.cast || 'Cast information not available'}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-6">
                        <h4 class="text-white font-semibold mb-2">Description</h4>
                        <p class="text-gray-300 text-sm leading-relaxed">${movie.description}</p>
                    </div>
                `;
                
                document.getElementById('movieModal').classList.remove('hidden');
                document.getElementById('movieModal').classList.add('flex');
            }
        }
        
        function closeModal() {
            document.getElementById('movieModal').classList.add('hidden');
            document.getElementById('movieModal').classList.remove('flex');
        }
        
        document.getElementById('movieModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>
