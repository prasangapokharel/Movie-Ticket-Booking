<?php

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);

// Get current page to highlight active nav item
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<style>
    
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@200&display=swap');
 * {

            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        body{
            padding: 16px;

        }
</style>
<nav class="fixed w-full top-0 z-50 backdrop-blur-md bg-gray-900/90 text-white shadow-lg border-b border-gray-800/80">
    <div class="container mx-auto px-4 py-3">
        <div class="flex justify-between items-center">
            <!-- Logo and Main Navigation -->
            <div class="flex items-center">
                <a href="index.php" class="flex items-center group">
                    <div class="relative">
                        <div class="relative bg-gray-900 rounded-full p-1">
                        <img class="h-12 w-22" src="https://logos-world.net/wp-content/uploads/2022/03/AMC-Theatres-Symbol.png">
                        </div>
                    </div>
                </a>
                
                <?php if (isset($_SESSION['location'])): ?>
                <div class="ml-8 hidden md:flex space-x-8">
                    <a href="index.php" class="<?= $currentPage === 'index.php' ? 'text-red-400 border-b-2 border-red-400' : 'text-gray-300 hover:text-white' ?> transition duration-300 py-1 relative group">
                        <span>Home</span>
                        <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-red-400 group-hover:w-full transition-all duration-300"></span>
                    </a>
                    <a href="movies.php" class="<?= $currentPage === 'movies.php' ? 'text-red-400 border-b-2 border-red-400' : 'text-gray-300 hover:text-white' ?> transition duration-300 py-1 relative group">
                        <span>Movies</span>
                        <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-red-400 group-hover:w-full transition-all duration-300"></span>
                    </a>
                    <a href="theaters.php" class="<?= $currentPage === 'theaters.php' ? 'text-red-400 border-b-2 border-red-400' : 'text-gray-300 hover:text-white' ?> transition duration-300 py-1 relative group">
                        <span>Theaters</span>
                        <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-red-400 group-hover:w-full transition-all duration-300"></span>
                    </a>
                    <?php if ($isLoggedIn): ?>
                    <a href="my_bookings.php" class="<?= $currentPage === 'my_bookings.php' ? 'text-red-400 border-b-2 border-red-400' : 'text-gray-300 hover:text-white' ?> transition duration-300 py-1 relative group">
                        <span>My Bookings</span>
                        <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-red-400 group-hover:w-full transition-all duration-300"></span>
                    </a>
                    <a href="support.php" class="<?= $currentPage === 'support.php' ? 'text-red-400 border-b-2 border-red-400' : 'text-gray-300 hover:text-white' ?> transition duration-300 py-1 relative group">
                        <span>Support</span>
                        <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-red-400 group-hover:w-full transition-all duration-300"></span>
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Right Side Navigation -->
            <div class="flex items-center space-x-5">
                <?php if (isset($_SESSION['location'])): ?>
                <div class="flex items-center bg-gray-800/70 px-3 py-1.5 rounded-full border border-gray-700/50 shadow-inner">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-red-400 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span class="text-sm font-medium"><?php echo htmlspecialchars($_SESSION['location']); ?></span>
                </div>
                <?php endif; ?>
                
                <!-- Mobile Menu Button -->
                <button id="mobile-menu-button" class="md:hidden flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                
                <?php if ($isLoggedIn): ?>
                <!-- User Menu (Desktop) -->
                <div class="relative hidden md:block">
                    <button id="user-menu-button" class="flex items-center space-x-2 focus:outline-none group">
                            <div class="w-8 h-8 rounded-full bg-gray-800 flex items-center justify-center overflow-hidden group-hover:w-7 group-hover:h-7 transition-all duration-300">
                            <svg class="text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
  <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
</svg>
                            </div>
                    </button>
                    
                    <!-- User Dropdown Menu -->
                    <div id="user-dropdown" class="absolute right-0 mt-3 w-56 bg-gray-800/95 backdrop-blur-md rounded-xl shadow-2xl py-2 z-50 hidden transform transition-all duration-300 scale-95 opacity-0 border border-gray-700/50">
                        <div class="px-4 py-3 border-b border-gray-700/50">
                            <p class="text-sm text-gray-300">Signed in as</p>
                            <p class="text-sm font-medium truncate"><?= isset($_SESSION['email']) ? htmlspecialchars($_SESSION['email']) : 'User' ?></p>
                        </div>
                        <a href="profile.php" class="flex items-center px-4 py-2 text-sm text-gray-300 hover:bg-gray-700/50 hover:text-white transition duration-150">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Profile Settings
                        </a>
                        <a href="my_bookings.php" class="flex items-center px-4 py-2 text-sm text-gray-300 hover:bg-gray-700/50 hover:text-white transition duration-150">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v10a2 2 0 002 2h14a2 2 0 002-2V7a2 2 0 00-2-2H5z" />
                            </svg>
                            My Bookings
                        </a>
                        <div class="border-t border-gray-700/50 my-1"></div>
                        <a href="../includes/logout.php" class="flex items-center px-4 py-2 text-sm text-red-400 hover:bg-red-500/10 transition duration-150">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            Sign Out
                        </a>
                    </div>
                </div>
                <?php else: ?>
                <!-- Login/Register Buttons -->
                <div class="hidden md:flex items-center space-x-3">
                    <a href="login.php" class="text-gray-300 hover:text-white transition duration-300">Login</a>
                    <a href="register.php" class="bg-gradient-to-r from-red-500  hover:from-red-600  text-white px-4 py-2 rounded-lg transition duration-300 shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                        Register
                    </a>
                </div>
                
                <!-- Mobile Login Button -->
                <a href="login.php" class="md:hidden bg-gradient-to-r from-red-500  text-white px-4 py-1.5 rounded-lg text-sm shadow-md">
                    Login
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Mobile Menu -->
    <div id="mobile-menu" class="md:hidden hidden bg-gray-900/95 backdrop-blur-md border-t border-gray-800/80">
        <div class="px-2 pt-2 pb-3 space-y-1">
            <a href="index.php" class="<?= $currentPage === 'index.php' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?> block px-3 py-2 rounded-md text-base font-medium">Home</a>
            <a href="movies.php" class="<?= $currentPage === 'movies.php' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?> block px-3 py-2 rounded-md text-base font-medium">Movies</a>
            <a href="theaters.php" class="<?= $currentPage === 'theaters.php' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?> block px-3 py-2 rounded-md text-base font-medium">Theaters</a>
            
            <?php if ($isLoggedIn): ?>
            <a href="my_bookings.php" class="<?= $currentPage === 'my_bookings.php' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?> block px-3 py-2 rounded-md text-base font-medium">My Bookings</a>
            <a href="profile.php" class="<?= $currentPage === 'profile.php' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?> block px-3 py-2 rounded-md text-base font-medium">Profile</a>
            <div class="border-t border-gray-800 my-2"></div>
            <a href="logout.php" class="text-red-400 hover:bg-red-500/10 block px-3 py-2 rounded-md text-base font-medium">Sign Out</a>
            <?php else: ?>
            <div class="border-t border-gray-800 my-2"></div>
            <a href="register.php" class="text-gray-300 hover:bg-gray-800 hover:text-white block px-3 py-2 rounded-md text-base font-medium">Register</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- Spacer for fixed navbar -->
<div class="h-16"></div>

<script>
    // Mobile menu toggle
    document.getElementById('mobile-menu-button').addEventListener('click', function() {
        const mobileMenu = document.getElementById('mobile-menu');
        mobileMenu.classList.toggle('hidden');
    });
    
    // User dropdown toggle
    const userMenuButton = document.getElementById('user-menu-button');
    const userDropdown = document.getElementById('user-dropdown');
    
    if (userMenuButton && userDropdown) {
        userMenuButton.addEventListener('click', function() {
            if (userDropdown.classList.contains('hidden')) {
                // Show the dropdown
                userDropdown.classList.remove('hidden');
                setTimeout(() => {
                    userDropdown.classList.remove('scale-95', 'opacity-0');
                    userDropdown.classList.add('scale-100', 'opacity-100');
                }, 10);
            } else {
                // Hide the dropdown
                userDropdown.classList.remove('scale-100', 'opacity-100');
                userDropdown.classList.add('scale-95', 'opacity-0');
                setTimeout(() => {
                    userDropdown.classList.add('hidden');
                }, 200);
            }
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            if (!userMenuButton.contains(event.target) && !userDropdown.contains(event.target)) {
                userDropdown.classList.remove('scale-100', 'opacity-100');
                userDropdown.classList.add('scale-95', 'opacity-0');
                setTimeout(() => {
                    userDropdown.classList.add('hidden');
                }, 200);
            }
        });
    }
</script>

