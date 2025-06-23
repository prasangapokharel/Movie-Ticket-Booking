<?php include '../model/Booking.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Tickets - <?php echo htmlspecialchars($show['movie_title']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #f1f5f9;
            min-height: 100vh;
        }
        
        .booking-container {
            background: rgba(30, 41, 59, 0.8);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
        
        .movie-header {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(185, 28, 28, 0.1) 100%);
            border-radius: 16px;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }
        
        .screen {
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            height: 6px;
            border-radius: 3px;
            margin: 3rem auto;
            max-width: 400px;
            position: relative;
            box-shadow: 0 0 20px rgba(255, 255, 255, 0.3);
        }
        
        .screen::after {
            content: 'SCREEN';
            position: absolute;
            top: 15px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 0.75rem;
            color: #94a3b8;
            letter-spacing: 0.2em;
            font-weight: 600;
        }
        
        .seat-grid {
            display: grid;
            grid-template-columns: repeat(10, 1fr);
            gap: 12px;
            max-width: 600px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .seat {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: 600;
            position: relative;
            border: 2px solid transparent;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        .seat.available {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }
        
        .seat.available:hover {
            background: linear-gradient(135deg, #059669, #047857);
            transform: translateY(-2px) scale(1.05);
            box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.4);
        }
        
        .seat.selected {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            border-color: #60a5fa;
            transform: translateY(-2px) scale(1.05);
            box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.4);
            animation: pulse 2s infinite;
        }
        
        .seat.booked {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            cursor: not-allowed;
            opacity: 0.7;
        }
        
        .seat.temp-selected {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            cursor: not-allowed;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }
        
        .legend {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin: 2rem 0;
            flex-wrap: wrap;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.875rem;
            padding: 0.5rem 1rem;
            background: rgba(30, 41, 59, 0.5);
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .legend-seat {
            width: 24px;
            height: 24px;
            border-radius: 6px;
        }
        
        .booking-form {
            background: rgba(15, 23, 42, 0.6);
            border-radius: 16px;
            padding: 2rem;
            margin-top: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #e2e8f0;
            font-size: 0.875rem;
        }
        
        .form-input {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            background: rgba(15, 23, 42, 0.8);
            color: white;
            transition: all 0.3s ease;
            font-size: 0.875rem;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            background: rgba(15, 23, 42, 0.9);
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.875rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            border: none;
            font-size: 0.875rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #ef4444, #b91c1c);
            color: white;
            box-shadow: 0 4px 6px -1px rgba(239, 68, 68, 0.3);
        }
        
        .btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(239, 68, 68, 0.4);
        }
        
        .btn-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        
        .booking-summary {
            background: rgba(15, 23, 42, 0.8);
            border-radius: 12px;
            padding: 1.5rem;
            margin-top: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.75rem;
            font-size: 0.875rem;
        }
        
        .summary-row:last-child {
            margin-bottom: 0;
            padding-top: 0.75rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            font-weight: 600;
            font-size: 1rem;
        }
        
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            border: 1px solid;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border-color: rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }
        
        .movie-info {
            display: flex;
            gap: 1.5rem;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .movie-poster {
            width: 80px;
            height: 120px;
            border-radius: 12px;
            object-fit: cover;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
        }
        
        .movie-details h1 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .movie-meta {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            font-size: 0.875rem;
            color: #94a3b8;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.9);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease;
        }
        
        .loading-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            border-top-color: #ef4444;
            animation: spin 1s linear infinite;
            margin-bottom: 1rem;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <?php include '../includes/nav.php'; ?>
    
    <!-- Loading overlay -->
    <div id="loadingOverlay" class="loading-overlay">
        <div class="spinner"></div>
        <p class="text-white font-medium">Processing your booking...</p>
    </div>
    
    <div class="container mx-auto px-4 py-8">
        <!-- Error Messages -->
        <?php if (!empty($errors)): ?>
        <div class="alert alert-error max-w-4xl mx-auto mb-6">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
            </svg>
            <div>
                <h3 class="font-semibold mb-2">Please fix the following errors:</h3>
                <ul class="list-disc list-inside space-y-1">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Movie Info Header -->
        <div class="movie-header max-w-4xl mx-auto p-6 mb-8">
            <div class="movie-info">
                <img src="<?php echo htmlspecialchars($show['poster_url'] ?? '/placeholder.svg?height=120&width=80'); ?>" 
                     alt="<?php echo htmlspecialchars($show['movie_title']); ?>" 
                     class="movie-poster">
                <div class="movie-details">
                    <h1><?php echo htmlspecialchars($show['movie_title']); ?></h1>
                    <div class="movie-meta">
                        <span class="meta-item">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                            </svg>
                            <?php echo $show['duration']; ?> mins
                        </span>
                        <span class="meta-item">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                            </svg>
                            <?php echo htmlspecialchars($show['theater_name']); ?>
                        </span>
                        <span class="meta-item">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                            </svg>
                            <?php echo date('D, M j, Y • g:i A', strtotime($show['show_time'])); ?>
                        </span>
                        <span class="meta-item">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z" />
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd" />
                            </svg>
                            ₨<?php echo number_format($show['price'], 2); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Seat Booking -->
        <div class="booking-container max-w-5xl mx-auto p-8">
            <h2 class="text-2xl font-bold text-center mb-6">Select Your Seats</h2>
            
            <!-- Screen -->
            <div class="screen"></div>
            
            <!-- Seat Grid -->
            <form method="post" id="bookingForm">
                <div class="seat-grid" id="seatGrid">
                    <?php
                    $rows = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
                    $seatsPerRow = 10;
                    
                    foreach ($rows as $rowIndex => $row) {
                        for ($seat = 1; $seat <= $seatsPerRow; $seat++) {
                            $seatNumber = $row . $seat;
                            $isBooked = isset($bookedSeats[$seatNumber]) && in_array($bookedSeats[$seatNumber], ['booked', 'reserved']);
                            $isTempSelected = in_array($seatNumber, $tempSelectedSeats);
                            $isUserSelected = in_array($seatNumber, $userTempSeats);
                            
                            $class = 'available';
                            $disabled = '';
                            
                            if ($isBooked) {
                                $class = 'booked';
                                $disabled = 'disabled';
                            } elseif ($isTempSelected) {
                                $class = 'temp-selected';
                                $disabled = 'disabled';
                            } elseif ($isUserSelected) {
                                $class = 'selected';
                            }
                            
                            echo "<div class='seat {$class}' data-seat='{$seatNumber}' {$disabled}>";
                            echo "<input type='checkbox' name='seats[]' value='{$seatNumber}' style='display: none;' " . ($isUserSelected ? 'checked' : '') . " " . ($disabled ? 'disabled' : '') . ">";
                            echo $seat;
                            echo "</div>";
                        }
                    }
                    ?>
                </div>
                
                <!-- Legend -->
                <div class="legend">
                    <div class="legend-item">
                        <div class="legend-seat available"></div>
                        <span>Available</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-seat selected"></div>
                        <span>Selected</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-seat booked"></div>
                        <span>Booked</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-seat temp-selected"></div>
                        <span>Selected by Others</span>
                    </div>
                </div>
                
                <!-- Booking Form Fields -->
                <div class="booking-form">
                    <h3 class="text-xl font-semibold mb-6">Your Details</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="form-group">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" class="form-input" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" class="form-input" required>
                        </div>
                        
                        <div class="form-group md:col-span-2">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" class="form-input" required>
                        </div>
                    </div>
                    
                    <!-- Booking Summary -->
                    <div class="booking-summary">
                        <h4 class="font-semibold mb-4 text-lg">Booking Summary</h4>
                        
                        <div class="summary-row">
                            <span>Selected Seats:</span>
                            <span id="selectedSeatsDisplay">None</span>
                        </div>
                        
                        <div class="summary-row">
                            <span>Ticket Price:</span>
                            <span>₨<?php echo number_format($show['price'], 2); ?> × <span id="seatCount">0</span></span>
                        </div>
                        
                        <div class="summary-row">
                            <span>Convenience Fee:</span>
                            <span>₨20.00</span>
                        </div>
                        
                        <div class="summary-row">
                            <span>Total Amount:</span>
                            <span id="totalAmount">₨20.00</span>
                        </div>
                    </div>
                    
                    <button type="button" name="book_tickets" class="btn btn-primary w-full mt-6" id="bookButton" disabled onclick="document.getElementById('bookingForm').dispatchEvent(new Event('submit'))">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 2a4 4 0 00-4 4v1H5a1 1 0 00-.994.89l-1 9A1 1 0 004 18h12a1 1 0 00.994-1.11l-1-9A1 1 0 0015 7h-1V6a4 4 0 00-4-4zm2 5V6a2 2 0 10-4 0v1h4zm-6 3a1 1 0 112 0 1 1 0 01-2 0zm7-1a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd" />
                        </svg>
                        Continue to Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <!-- Payment Modal -->
    <div id="paymentModal" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 hidden">
        <div class="bg-slate-800 rounded-2xl p-8 max-w-md w-full mx-4 border border-slate-600">
            <div class="text-center">
                <div class="w-16 h-16 bg-purple-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 0h.01M9 16h6m-6 0a2 2 0 01-2-2V9a2 2 0 012-2h6a2 2 0 012 2v3" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-white mb-2">Complete Payment</h3>
                <p class="text-gray-300 mb-6">Total Amount: <span id="modalTotalAmount" class="font-bold text-green-400"></span></p>
                
                <button id="payWithKhaltiBtn" class="w-full bg-purple-600 hover:bg-purple-700 text-white font-semibold py-3 px-6 rounded-lg transition-colors mb-4">
                    Pay with Khalti
                </button>
                
                <button id="cancelPaymentBtn" class="w-full bg-gray-600 hover:bg-gray-700 text-white font-semibold py-2 px-6 rounded-lg transition-colors">
                    Cancel
                </button>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div id="successModal" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 hidden">
        <div class="bg-slate-800 rounded-2xl p-8 max-w-lg w-full mx-4 border border-green-500">
            <div class="text-center">
                <div class="w-20 h-20 bg-green-500 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-white mb-2">Payment Successful!</h3>
                <p class="text-gray-300 mb-6">Your booking has been confirmed</p>
                
                <!-- Ticket Details -->
                <div class="bg-slate-700 rounded-lg p-4 mb-6 text-left">
                    <h4 class="font-semibold text-white mb-3">Booking Details</h4>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-400">Movie:</span>
                            <span class="text-white" id="ticketMovie"><?php echo htmlspecialchars($show['movie_title']); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-400">Theater:</span>
                            <span class="text-white" id="ticketTheater"><?php echo htmlspecialchars($show['theater_name']); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-400">Date & Time:</span>
                            <span class="text-white" id="ticketDateTime"><?php echo date('D, M j, Y • g:i A', strtotime($show['show_time'])); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-400">Seats:</span>
                            <span class="text-white" id="ticketSeats"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-400">Total Paid:</span>
                            <span class="text-green-400 font-semibold" id="ticketTotal"></span>
                        </div>
                    </div>
                </div>
                
                <button id="closeSuccessBtn" class="w-full bg-green-500 hover:bg-green-600 text-white font-semibold py-3 px-6 rounded-lg transition-colors">
                    View My Bookings
                </button>
            </div>
        </div>
    </div>

    <script>
        // Payment integration variables
        let currentBookingId = null;
        let paymentWindow = null;
        let paymentCheckInterval = null;

        // Modal elements
        const paymentModal = document.getElementById('paymentModal');
        const successModal = document.getElementById('successModal');
        const modalTotalAmount = document.getElementById('modalTotalAmount');
        const payWithKhaltiBtn = document.getElementById('payWithKhaltiBtn');
        const cancelPaymentBtn = document.getElementById('cancelPaymentBtn');
        const closeSuccessBtn = document.getElementById('closeSuccessBtn');

        // Update the form submission to show payment modal instead of redirecting
        document.getElementById('bookingForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const checkedSeats = document.querySelectorAll('input[name="seats[]"]:checked');
            
            if (checkedSeats.length === 0 || selectedSeats.length === 0) {
                alert('Please select at least one seat before proceeding.');
                return false;
            }
            
            // Show loading overlay
            document.getElementById('loadingOverlay').classList.add('active');
            
            // Prepare form data
            const formData = new FormData(this);
            
            // Submit booking via AJAX
            fetch('booking.php?show_id=<?php echo $show_id; ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                document.getElementById('loadingOverlay').classList.remove('active');
                
                // Check if booking was successful (look for booking_id in response)
                const bookingIdMatch = data.match(/booking_id["\s]*[:=]["\s]*(\d+)/);
                if (bookingIdMatch) {
                    currentBookingId = bookingIdMatch[1];
                    showPaymentModal();
                } else {
                    // If booking failed, reload page to show errors
                    location.reload();
                }
            })
            .catch(error => {
                document.getElementById('loadingOverlay').classList.remove('active');
                alert('An error occurred. Please try again.');
                console.error('Error:', error);
            });
        });

        function showPaymentModal() {
            const total = (selectedSeats.length * ticketPrice) + convenienceFee;
            modalTotalAmount.textContent = '₨' + total.toFixed(2);
            paymentModal.classList.remove('hidden');
        }

        function hidePaymentModal() {
            paymentModal.classList.add('hidden');
        }

        function showSuccessModal() {
            // Update ticket details
            document.getElementById('ticketSeats').textContent = selectedSeats.join(', ');
            document.getElementById('ticketTotal').textContent = modalTotalAmount.textContent;
            
            hidePaymentModal();
            successModal.classList.remove('hidden');
        }

        // Payment button click handler
        payWithKhaltiBtn.addEventListener('click', function() {
            if (!currentBookingId) {
                alert('Booking ID not found. Please try again.');
                return;
            }
            
            this.disabled = true;
            this.innerHTML = 'Processing...';
            
            // Initiate Khalti payment
            initiateKhaltiPayment();
        });

        function initiateKhaltiPayment() {
            const formData = new FormData();
            formData.append('action', 'initiate_payment');
            formData.append('booking_id', currentBookingId);
            
            fetch('payment_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Open Khalti payment window
                    paymentWindow = window.open(data.payment_url, 'khaltiPayment', 'width=800,height=600,scrollbars=yes,resizable=yes');
                    
                    // Start checking payment status
                    startPaymentStatusCheck(data.pidx);
                } else {
                    alert(data.message || 'Failed to initiate payment');
                    resetPaymentButton();
                }
            })
            .catch(error => {
                alert('An error occurred. Please try again.');
                resetPaymentButton();
                console.error('Error:', error);
            });
        }

        function startPaymentStatusCheck(pidx) {
            let checkCount = 0;
            const maxChecks = 60; // Check for 5 minutes (60 * 5 seconds)
            
            paymentCheckInterval = setInterval(() => {
                checkCount++;
                
                // Check if payment window is closed
                if (paymentWindow && paymentWindow.closed) {
                    clearInterval(paymentCheckInterval);
                    checkPaymentStatus(pidx, true); // Final check
                    return;
                }
                
                // Stop checking after max attempts
                if (checkCount >= maxChecks) {
                    clearInterval(paymentCheckInterval);
                    resetPaymentButton();
                    alert('Payment verification timeout. Please check your booking status.');
                    return;
                }
                
                checkPaymentStatus(pidx);
            }, 5000);
        }

        function checkPaymentStatus(pidx, isFinalCheck = false) {
            const formData = new FormData();
            formData.append('action', 'check_payment_status');
            formData.append('pidx', pidx);
            formData.append('booking_id', currentBookingId);
            
            fetch('payment_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.status === 'completed') {
                        // Payment successful
                        clearInterval(paymentCheckInterval);
                        if (paymentWindow) paymentWindow.close();
                        showSuccessModal();
                    } else if (data.status === 'failed') {
                        // Payment failed
                        clearInterval(paymentCheckInterval);
                        if (paymentWindow) paymentWindow.close();
                        alert('Payment failed. Please try again.');
                        resetPaymentButton();
                    }
                    // If pending, continue checking (unless it's final check)
                } else if (isFinalCheck) {
                    // Final check failed, assume payment didn't complete
                    alert('Payment status unclear. Please check your booking status.');
                    resetPaymentButton();
                }
            })
            .catch(error => {
                if (isFinalCheck) {
                    console.error('Error checking payment status:', error);
                    resetPaymentButton();
                }
            });
        }

        function resetPaymentButton() {
            payWithKhaltiBtn.disabled = false;
            payWithKhaltiBtn.innerHTML = 'Pay with Khalti';
        }

        // Cancel payment button
        cancelPaymentBtn.addEventListener('click', hidePaymentModal);

        // Close success modal and redirect
        closeSuccessBtn.addEventListener('click', function() {
            window.location.href = 'my_bookings.php';
        });
    </script>
</body>
</html>
</merged_code>
