<?php
session_start();
include '../database/config.php';


try {
    // Fetch all theaters
    $stmt = $conn->prepare("
        SELECT 
            theater_id, 
            name, 
            location, 
            capacity, 
            address, 
            city, 
            state, 
            screens, 
            theater_image,
            created_at
        FROM 
            theaters 
        ORDER BY 
            name ASC
    ");
    $stmt->execute();
    $theaters = $stmt->fetchAll();

    // Get cities for filter
    $city_stmt = $conn->prepare("SELECT DISTINCT city FROM theaters WHERE city IS NOT NULL AND city != '' ORDER BY city");
    $city_stmt->execute();
    $cities = $city_stmt->fetchAll(PDO::FETCH_COLUMN);

    // Apply filters if set
    $filter_city = isset($_GET['city']) ? $_GET['city'] : '';
    $search_term = isset($_GET['search']) ? $_GET['search'] : '';

    if (!empty($filter_city) || !empty($search_term)) {
        $filter_query = "
            SELECT 
                theater_id, 
                name, 
                location, 
                capacity, 
                address, 
                city, 
                state, 
                screens, 
                theater_image,
                created_at
            FROM 
                theaters 
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filter_city)) {
            $filter_query .= " AND city = ?";
            $params[] = $filter_city;
        }

        if (!empty($search_term)) {
            $filter_query .= " AND (name LIKE ? OR location LIKE ? OR address LIKE ?)";
            $search_param = "%$search_term%";
            $params[] = $search_param;
            $params[] = $search_param;
            $params[] = $search_param;
        }

        $filter_query .= " ORDER BY name ASC";
        $filter_stmt = $conn->prepare($filter_query);
        $filter_stmt->execute($params);
        $theaters = $filter_stmt->fetchAll();
    }

} catch (PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Theaters - Movie Booking System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="../assets/js/talwind.js"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #0f172a;
            color: #f1f5f9;
            min-height: 100vh;
        }
        
        .theater-card {
            background: linear-gradient(to bottom, rgba(30, 41, 59, 0.7), rgba(15, 23, 42, 0.9));
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .theater-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 10px 10px -5px rgba(0, 0, 0, 0.2);
        }
        
        .theater-image {
            height: 200px;
            background-size: cover;
            background-position: center;
            position: relative;
        }
        
        .theater-image::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 50%;
            background: linear-gradient(to top, rgba(15, 23, 42, 1), transparent);
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
    </style>
</head>
<body>
    <?php include '../includes/nav.php'; ?>
    
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-2">Our Theaters</h1>
        <p class="text-gray-400 mb-8">Discover the perfect venue for your movie experience</p>
        
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
                        placeholder="Search by name or location..." 
                        value="<?php echo htmlspecialchars($search_term ?? ''); ?>"
                        class="form-input w-full rounded-lg px-4 py-2"
                    >
                </div>
                
                <div class="md:w-1/4">
                    <label for="city" class="block text-sm font-medium text-gray-300 mb-1">City</label>
                    <select id="city" name="city" class="form-input w-full rounded-lg px-4 py-2">
                        <option value="">All Cities</option>
                        <?php foreach ($cities as $city): ?>
                            <option value="<?php echo htmlspecialchars($city); ?>" <?php echo ($filter_city === $city) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($city); ?>
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
                
                <?php if (!empty($filter_city) || !empty($search_term)): ?>
                <div class="md:w-auto flex items-end">
                    <a href="theaters.php" class="text-gray-400 hover:text-white px-4 py-2 rounded-lg border border-gray-700 hover:border-gray-600">
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
        
        <!-- Theater Cards -->
        <?php if (empty($theaters)): ?>
            <div class="text-center py-16">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-gray-600 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                <h3 class="text-xl font-medium text-gray-400">No theaters found</h3>
                <p class="text-gray-500 mt-2">Try adjusting your search or filter criteria</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <?php foreach ($theaters as $theater): ?>
                    <div class="theater-card rounded-xl overflow-hidden shadow-lg">
                        <div class="theater-image" style="background-image: url('<?php echo !empty($theater['theater_image']) ? htmlspecialchars($theater['theater_image']) : 'assets/images/theater-placeholder.jpg'; ?>');">
                            <?php if (empty($theater['theater_image'])): ?>
                                <!-- Fallback image if no theater image -->
                                <div class="w-full h-full flex items-center justify-center bg-gray-800">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
                                    </svg>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="p-5">
                            <h3 class="text-xl font-bold mb-2"><?php echo htmlspecialchars($theater['name']); ?></h3>
                            
                            <div class="flex items-start space-x-2 mb-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-400 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <div>
                                    <p class="text-gray-300"><?php echo htmlspecialchars($theater['location']); ?></p>
                                    <?php if (!empty($theater['city']) && !empty($theater['state'])): ?>
                                        <p class="text-gray-500 text-sm"><?php echo htmlspecialchars($theater['city'] . ', ' . $theater['state']); ?></p>
                                    <?php elseif (!empty($theater['city'])): ?>
                                        <p class="text-gray-500 text-sm"><?php echo htmlspecialchars($theater['city']); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="flex flex-wrap gap-2 mb-4">
                                <div class="badge text-xs px-2 py-1 rounded-full">
                                    <?php echo htmlspecialchars($theater['capacity']); ?> seats per screen
                                </div>
                                
                                <div class="badge text-xs px-2 py-1 rounded-full">
                                    <?php echo htmlspecialchars($theater['screens']); ?> screen<?php echo $theater['screens'] > 1 ? 's' : ''; ?>
                                </div>
                            </div>
                            
                            <a href="theater_detail.php?id=<?php echo $theater['theater_id']; ?>" class="block text-center bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-lg transition duration-300">
                                View Shows
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script>
        // Add any JavaScript functionality here
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-submit form when city filter changes
            document.getElementById('city').addEventListener('change', function() {
                this.form.submit();
            });
        });
    </script>
</body>
</html>