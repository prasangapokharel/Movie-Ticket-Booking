<?php
include '../database/config.php';
session_start();

// Check if user is logged in as admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

$success_message = '';
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $title = trim($_POST['title']);
    $genre = trim($_POST['genre']);
    $duration = intval($_POST['duration']);
    $release_date = $_POST['release_date'];
    $description = trim($_POST['description']);
    $rating = floatval($_POST['rating']);
    $language = trim($_POST['language']);
    $poster_url = trim($_POST['poster_url']);
    $trailer_url = trim($_POST['trailer_url']);
    $director = trim($_POST['director']);
    $cast = trim($_POST['cast']);
    $certificate = trim($_POST['certificate']);

    // Validate inputs
    if (empty($title) || empty($genre) || empty($duration) || empty($release_date) || empty($description)) {
        $error_message = "All required fields must be filled out";
    } elseif ($duration <= 0) {
        $error_message = "Duration must be a positive number";
    } elseif ($rating < 0 || $rating > 5) {
        $error_message = "Rating must be between 0 and 5";
    } else {
        try {
            // Insert new movie into the database
            $stmt = $conn->prepare("
                INSERT INTO movies (
                    title, genre, duration, release_date, description, 
                    rating, language, poster_url, trailer_url, 
                    director, cast, certificate, created_at
                ) VALUES (
                    ?, ?, ?, ?, ?, 
                    ?, ?, ?, ?, 
                    ?, ?, ?, NOW()
                )
            ");
            
            if ($stmt->execute([
                $title, $genre, $duration, $release_date, $description, 
                $rating, $language, $poster_url, $trailer_url, 
                $director, $cast, $certificate
            ])) {
                $success_message = "Movie added successfully!";
                // Clear form data after successful submission
                $title = $genre = $duration = $release_date = $description = '';
                $rating = $language = $poster_url = $trailer_url = $director = $cast = $certificate = '';
            } else {
                $error_message = "Error adding movie.";
            }
        } catch (PDOException $e) {
            $error_message = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Movie - Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #000000;
            color: #ffffff;
        }
        .form-card {
            background-color: #111111;
            border: 1px solid #333333;
        }
        .input-field {
            background-color: #1a1a1a;
            border: 1px solid #333333;
            color: #ffffff;
        }
        .input-field:focus {
            border-color: #4b5563;
            box-shadow: 0 0 0 2px rgba(75, 85, 99, 0.2);
        }
        .submit-button {
            background-color: #ffffff;
            color: #000000;
        }
        .submit-button:hover {
            background-color: #e5e5e5;
        }
    </style>
</head>
<body class="min-h-screen">
    <?php include '../includes/header.php'; ?>
    
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-3xl font-bold mb-8 text-center">Add New Movie</h1>
            
            <?php if (!empty($success_message)): ?>
            <div class="bg-green-900 text-green-100 px-4 py-3 rounded-lg mb-6">
                <p><?php echo htmlspecialchars($success_message); ?></p>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
            <div class="bg-red-900 text-red-100 px-4 py-3 rounded-lg mb-6">
                <p><?php echo htmlspecialchars($error_message); ?></p>
            </div>
            <?php endif; ?>
            
            <div class="form-card rounded-lg overflow-hidden shadow-lg">
                <div class="p-6">
                    <form method="POST" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="title" class="block text-sm font-medium text-gray-300 mb-1">
                                    Movie Title <span class="text-red-500">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    name="title" 
                                    id="title" 
                                    value="<?php echo isset($title) ? htmlspecialchars($title) : ''; ?>"
                                    class="input-field w-full px-4 py-2 rounded-lg focus:outline-none" 
                                    required
                                >
                            </div>
                            
                            <div>
                                <label for="genre" class="block text-sm font-medium text-gray-300 mb-1">
                                    Genre <span class="text-red-500">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    name="genre" 
                                    id="genre" 
                                    value="<?php echo isset($genre) ? htmlspecialchars($genre) : ''; ?>"
                                    placeholder="Action, Drama, Comedy, etc."
                                    class="input-field w-full px-4 py-2 rounded-lg focus:outline-none" 
                                    required
                                >
                                <p class="text-xs text-gray-500 mt-1">Separate multiple genres with commas</p>
                            </div>
                            
                            <div>
                                <label for="duration" class="block text-sm font-medium text-gray-300 mb-1">
                                    Duration (minutes) <span class="text-red-500">*</span>
                                </label>
                                <input 
                                    type="number" 
                                    name="duration" 
                                    id="duration" 
                                    value="<?php echo isset($duration) ? htmlspecialchars($duration) : ''; ?>"
                                    min="1"
                                    class="input-field w-full px-4 py-2 rounded-lg focus:outline-none" 
                                    required
                                >
                            </div>
                            
                            <div>
                                <label for="release_date" class="block text-sm font-medium text-gray-300 mb-1">
                                    Release Date <span class="text-red-500">*</span>
                                </label>
                                <input 
                                    type="date" 
                                    name="release_date" 
                                    id="release_date" 
                                    value="<?php echo isset($release_date) ? htmlspecialchars($release_date) : ''; ?>"
                                    class="input-field w-full px-4 py-2 rounded-lg focus:outline-none" 
                                    required
                                >
                            </div>
                            
                            <div>
                                <label for="rating" class="block text-sm font-medium text-gray-300 mb-1">
                                    Rating (0-5)
                                </label>
                                <input 
                                    type="number" 
                                    step="0.1" 
                                    name="rating" 
                                    id="rating" 
                                    value="<?php echo isset($rating) ? htmlspecialchars($rating) : ''; ?>"
                                    min="0" 
                                    max="5"
                                    class="input-field w-full px-4 py-2 rounded-lg focus:outline-none"
                                >
                            </div>
                            
                            <div>
                                <label for="language" class="block text-sm font-medium text-gray-300 mb-1">
                                    Language
                                </label>
                                <input 
                                    type="text" 
                                    name="language" 
                                    id="language" 
                                    value="<?php echo isset($language) ? htmlspecialchars($language) : ''; ?>"
                                    class="input-field w-full px-4 py-2 rounded-lg focus:outline-none"
                                >
                            </div>
                            
                            <div>
                                <label for="director" class="block text-sm font-medium text-gray-300 mb-1">
                                    Director
                                </label>
                                <input 
                                    type="text" 
                                    name="director" 
                                    id="director" 
                                    value="<?php echo isset($director) ? htmlspecialchars($director) : ''; ?>"
                                    class="input-field w-full px-4 py-2 rounded-lg focus:outline-none"
                                >
                            </div>
                            
                            <div>
                                <label for="cast" class="block text-sm font-medium text-gray-300 mb-1">
                                    Cast
                                </label>
                                <input 
                                    type="text" 
                                    name="cast" 
                                    id="cast" 
                                    value="<?php echo isset($cast) ? htmlspecialchars($cast) : ''; ?>"
                                    placeholder="Actor 1, Actor 2, Actor 3"
                                    class="input-field w-full px-4 py-2 rounded-lg focus:outline-none"
                                >
                                <p class="text-xs text-gray-500 mt-1">Separate cast members with commas</p>
                            </div>
                            
                            <div>
                                <label for="certificate" class="block text-sm font-medium text-gray-300 mb-1">
                                    Certificate
                                </label>
                                <select 
                                    name="certificate" 
                                    id="certificate" 
                                    class="input-field w-full px-4 py-2 rounded-lg focus:outline-none"
                                >
                                    <option value="U" <?php echo (isset($certificate) && $certificate == 'U') ? 'selected' : ''; ?>>U (Universal)</option>
                                    <option value="UA" <?php echo (isset($certificate) && $certificate == 'UA') ? 'selected' : ''; ?>>UA (Parental Guidance)</option>
                                    <option value="A" <?php echo (isset($certificate) && $certificate == 'A') ? 'selected' : ''; ?>>A (Adults Only)</option>
                                    <option value="S" <?php echo (isset($certificate) && $certificate == 'S') ? 'selected' : ''; ?>>S (Special Category)</option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="poster_url" class="block text-sm font-medium text-gray-300 mb-1">
                                    Poster URL <span class="text-red-500">*</span>
                                </label>
                                <input 
                                    type="url" 
                                    name="poster_url" 
                                    id="poster_url" 
                                    value="<?php echo isset($poster_url) ? htmlspecialchars($poster_url) : ''; ?>"
                                    placeholder="https://example.com/poster.jpg"
                                    class="input-field w-full px-4 py-2 rounded-lg focus:outline-none" 
                                    required
                                >
                            </div>
                            
                            <div>
                                <label for="trailer_url" class="block text-sm font-medium text-gray-300 mb-1">
                                    Trailer URL
                                </label>
                                <input 
                                    type="url" 
                                    name="trailer_url" 
                                    id="trailer_url" 
                                    value="<?php echo isset($trailer_url) ? htmlspecialchars($trailer_url) : ''; ?>"
                                    placeholder="https://youtube.com/watch?v=..."
                                    class="input-field w-full px-4 py-2 rounded-lg focus:outline-none"
                                >
                            </div>
                        </div>
                        
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-300 mb-1">
                                Description <span class="text-red-500">*</span>
                            </label>
                            <textarea 
                                name="description" 
                                id="description" 
                                rows="5" 
                                class="input-field w-full px-4 py-2 rounded-lg focus:outline-none" 
                                required
                            ><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
                        </div>
                        
                        <div class="flex justify-end">
                            <button 
                                type="submit" 
                                class="submit-button font-medium py-2 px-6 rounded-lg transition-colors"
                            >
                                Add Movie
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Preview poster image when URL is entered
        document.getElementById('poster_url').addEventListener('blur', function() {
            const url = this.value.trim();
            if (url) {
                // You could add code here to show a preview of the poster
                console.log("Poster URL entered:", url);
            }
        });
    </script>
</body>
</html>