<?php
include '../database/config.php';
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$success_message = '';
$error_message = '';

// Create uploads directory if it doesn't exist
$upload_dir = '../uploads/theaters/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $name = trim($_POST['name']);
    $location = trim($_POST['location']);
    $capacity = (int)$_POST['capacity'];
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $screens = (int)($_POST['screens'] ?? 1);
    $theater_image = ''; // Default empty value

    // Validate inputs
    if (empty($name) || empty($location) || empty($capacity)) {
        $error_message = "All required fields must be filled out";
    } else {
        // Handle image upload
        if (isset($_FILES['theater_image']) && $_FILES['theater_image']['error'] == 0) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
            $file_type = $_FILES['theater_image']['type'];
            $file_size = $_FILES['theater_image']['size'];
            $max_size = 5 * 1024 * 1024; // 5MB

            if (!in_array($file_type, $allowed_types)) {
                $error_message = "Only JPG, JPEG, PNG and WEBP files are allowed.";
            } elseif ($file_size > $max_size) {
                $error_message = "File size must be less than 5MB.";
            } else {
                $file_name = time() . '_' . basename($_FILES['theater_image']['name']);
                $file_path = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['theater_image']['tmp_name'], $file_path)) {
                    $theater_image = 'uploads/theaters/' . $file_name;
                } else {
                    $error_message = "Failed to upload image. Please try again.";
                }
            }
        }

        if (empty($error_message)) {
            try {
                // Insert new theater into the database
                $stmt = $conn->prepare("INSERT INTO theaters (name, location, capacity, address, city, state, screens, theater_image, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                if ($stmt->execute([$name, $location, $capacity, $address, $city, $state, $screens, $theater_image])) {
                    $success_message = "Theater added successfully!";
                    // Clear form data after successful submission
                    $name = $location = $capacity = $address = $city = $state = $screens = '';
                } else {
                    $error_message = "Error adding theater.";
                }
            } catch (PDOException $e) {
                $error_message = "Database error: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Theater - Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #000000;
            color: #ffffff;
        }
        .input-field {
            background-color: #111111;
            border: 1px solid #333333;
            color: #ffffff;
        }
        .input-field:focus {
            border-color: #666666;
            outline: none;
        }
        .submit-button {
            background-color: #ffffff;
            color: #000000;
        }
        .submit-button:hover {
            background-color: #e5e5e5;
        }
        .file-input-label {
            background-color: #333333;
            color: #ffffff;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            cursor: pointer;
            display: inline-block;
        }
        .file-input-label:hover {
            background-color: #444444;
        }
        .file-input {
            width: 0.1px;
            height: 0.1px;
            opacity: 0;
            overflow: hidden;
            position: absolute;
            z-index: -1;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto">
            <h1 class="text-3xl font-bold mb-8">Add New Theater</h1>
            
            <?php if (!empty($success_message)): ?>
            <div class="bg-green-900 text-green-100 px-4 py-3 rounded-lg mb-6">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
            <div class="bg-red-900 text-red-100 px-4 py-3 rounded-lg mb-6">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" class="bg-gray-900 rounded-lg p-6" enctype="multipart/form-data">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-300 mb-1">
                            Theater Name*
                        </label>
                        <input 
                            type="text" 
                            name="name" 
                            id="name" 
                            value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>"
                            class="input-field w-full px-4 py-2 rounded-lg" 
                            required
                        >
                    </div>
                    
                    <div>
                        <label for="location" class="block text-sm font-medium text-gray-300 mb-1">
                            Location* (Area/Neighborhood)
                        </label>
                        <input 
                            type="text" 
                            name="location" 
                            id="location" 
                            value="<?php echo isset($location) ? htmlspecialchars($location) : ''; ?>"
                            placeholder="Downtown, West Side, etc."
                            class="input-field w-full px-4 py-2 rounded-lg" 
                            required
                        >
                    </div>
                    
                    <div>
                        <label for="city" class="block text-sm font-medium text-gray-300 mb-1">
                            City
                        </label>
                        <input 
                            type="text" 
                            name="city" 
                            id="city" 
                            value="<?php echo isset($city) ? htmlspecialchars($city) : ''; ?>"
                            class="input-field w-full px-4 py-2 rounded-lg"
                        >
                    </div>
                    
                    <div>
                        <label for="state" class="block text-sm font-medium text-gray-300 mb-1">
                            State
                        </label>
                        <input 
                            type="text" 
                            name="state" 
                            id="state" 
                            value="<?php echo isset($state) ? htmlspecialchars($state) : ''; ?>"
                            class="input-field w-full px-4 py-2 rounded-lg"
                        >
                    </div>
                    
                    <div>
                        <label for="capacity" class="block text-sm font-medium text-gray-300 mb-1">
                            Capacity* (seats per screen)
                        </label>
                        <input 
                            type="number" 
                            name="capacity" 
                            id="capacity" 
                            value="<?php echo isset($capacity) ? htmlspecialchars($capacity) : ''; ?>"
                            min="1"
                            class="input-field w-full px-4 py-2 rounded-lg" 
                            required
                        >
                    </div>
                    
                    <div>
                        <label for="screens" class="block text-sm font-medium text-gray-300 mb-1">
                            Number of Screens
                        </label>
                        <input 
                            type="number" 
                            name="screens" 
                            id="screens" 
                            value="<?php echo isset($screens) ? htmlspecialchars($screens) : '1'; ?>"
                            min="1"
                            class="input-field w-full px-4 py-2 rounded-lg"
                        >
                    </div>
                    
                    <div class="md:col-span-2">
                        <label for="address" class="block text-sm font-medium text-gray-300 mb-1">
                            Full Address
                        </label>
                        <textarea 
                            name="address" 
                            id="address" 
                            rows="3" 
                            class="input-field w-full px-4 py-2 rounded-lg"
                        ><?php echo isset($address) ? htmlspecialchars($address) : ''; ?></textarea>
                    </div>
                    
                    <div class="md:col-span-2">
                        <label for="theater_image" class="block text-sm font-medium text-gray-300 mb-1">
                            Theater Image
                        </label>
                        <div class="flex items-center space-x-2">
                            <label for="theater_image" class="file-input-label">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline mr-1" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd" />
                                </svg>
                                Choose Image
                            </label>
                            <input 
                                type="file" 
                                name="theater_image" 
                                id="theater_image" 
                                accept=".jpg, .jpeg, .png, .webp"
                                class="file-input"
                                onchange="updateFileName(this)"
                            >
                            <span id="file-name" class="text-sm text-gray-400">No file chosen</span>
                        </div>
                        <p class="text-xs text-gray-400 mt-1">
                            Accepted formats: JPG, JPEG, PNG, WEBP. Max size: 5MB
                        </p>
                        <div id="image-preview" class="mt-3 hidden">
                            <img id="preview-img" src="#" alt="Preview" class="max-h-40 rounded-lg">
                        </div>
                    </div>
                </div>
                
                <div class="mt-8 flex justify-end">
                    <button 
                        type="submit" 
                        class="submit-button font-medium py-2 px-6 rounded-lg"
                    >
                        Add Theater
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function updateFileName(input) {
            const fileName = input.files[0] ? input.files[0].name : 'No file chosen';
            document.getElementById('file-name').textContent = fileName;
            
            // Show image preview
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('preview-img').src = e.target.result;
                    document.getElementById('image-preview').classList.remove('hidden');
                }
                reader.readAsDataURL(input.files[0]);
            } else {
                document.getElementById('image-preview').classList.add('hidden');
            }
        }
    </script>
</body>
</html>