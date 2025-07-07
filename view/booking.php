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
            user-select: none;
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
            background: linear-gradient(135deg, #3b82f6, #1d4ed8) !important;
            color: white !important;
            border-color: #60a5fa !important;
            transform: translateY(-2px) scale(1.05) !important;
            box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.4) !important;
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

        /* Modal Styles */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .modal.active {
            opacity: 1;
            visibility: visible;
        }
        
        .modal-content {
            background: linear-gradient(135deg, #1e293b, #0f172a);
            border-radius: 20px;
            padding: 2rem;
            max-width: 500px;
            width: 90%;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
        
        .khalti-btn {
            background: linear-gradient(135deg, #5C2D91, #4A2275);
            color: white;
            width: 100%;
            padding: 1rem;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        
        .khalti-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(92, 45, 145, 0.4);
        }
        
        .khalti-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .alert {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #ef4444;
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            z-index: 10001;
            max-width: 400px;
        }

        .alert.success {
            background: #10b981;
        }

        .alert.warning {
            background: #f59e0b;
        }
    </style>
</head>
<body>
    <?php include '../includes/nav.php'; ?>
    
    <!-- Loading overlay -->
    <div id="loadingOverlay" class="loading-overlay">
        <div class="spinner"></div>
        <p class="text-white font-medium">Processing...</p>
    </div>
    
    <div class="container mx-auto px-4 py-8">
        <!-- Movie Info Header -->
        <div class="movie-header max-w-4xl mx-auto p-6 mb-8">
            <div class="flex gap-6 items-center">
                <img src="<?php echo htmlspecialchars($show['poster_url'] ?? '/placeholder.svg?height=120&width=80'); ?>" 
                     alt="<?php echo htmlspecialchars($show['movie_title']); ?>" 
                     class="w-20 h-30 rounded-lg object-cover">
                <div>
                    <h1 class="text-2xl font-bold mb-2"><?php echo htmlspecialchars($show['movie_title']); ?></h1>
                    <div class="flex gap-4 text-sm text-gray-300">
                        <span><?php echo $show['duration']; ?> mins</span>
                        <span><?php echo htmlspecialchars($show['theater_name']); ?></span>
                        <span><?php echo date('D, M j, Y • g:i A', strtotime($show['show_time'])); ?></span>
                        <span>₨<?php echo number_format($show['price'], 2); ?></span>
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
                            $disabled = 'data-disabled="true"';
                        } elseif ($isTempSelected) {
                            $class = 'temp-selected';
                            $disabled = 'data-disabled="true"';
                        } elseif ($isUserSelected) {
                            $class = 'selected';
                        }
                        
                        echo "<div class='seat {$class}' data-seat='{$seatNumber}' {$disabled}>";
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
            
            <!-- Booking Form -->
            <div class="booking-form">
                <h3 class="text-xl font-semibold mb-6">Your Details</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" id="userName" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" id="userEmail" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" class="form-input" required>
                    </div>
                    
                    <div class="form-group md:col-span-2">
                        <label class="form-label">Phone Number</label>
                        <input type="tel" id="userPhone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" class="form-input" required>
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
                
                <button type="button" class="btn btn-primary w-full mt-6" id="bookButton" disabled>
                    Continue to Payment
                </button>
            </div>
        </div>
    </div>
    
    <!-- Payment Modal -->
    <div id="paymentModal" class="modal">
        <div class="modal-content text-center">
            <div class="w-16 h-16 bg-purple-600 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 0h.01M9 16h6m-6 0a2 2 0 01-2-2V9a2 2 0 012-2h6a2 2 0 012 2v3" />
                </svg>
            </div>
            <h3 class="text-xl font-bold text-white mb-2">Complete Payment</h3>
            <p class="text-gray-300 mb-6">Total Amount: <span id="modalTotalAmount" class="font-bold text-green-400"></span></p>
            
            <button id="payWithKhaltiBtn" class="khalti-btn mb-4">
                Pay with Khalti
            </button>
            
            <button id="cancelPaymentBtn" class="w-full bg-gray-600 hover:bg-gray-700 text-white font-semibold py-3 px-6 rounded-lg transition-colors">
                Cancel
            </button>
        </div>
    </div>

    <!-- Success Modal -->
    <div id="successModal" class="modal">
        <div class="modal-content text-center">
            <div class="w-20 h-20 bg-green-500 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h3 class="text-2xl font-bold text-white mb-2">Payment Successful!</h3>
            <p class="text-gray-300 mb-6">Your booking has been confirmed</p>
            
            <!-- Ticket Details -->
            <div class="bg-slate-700 rounded-lg p-4 mb-6 text-left">
                <h4 class="font-semibold text-white mb-3">🎫 Your Ticket</h4>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-400">Movie:</span>
                        <span class="text-white"><?php echo htmlspecialchars($show['movie_title']); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Theater:</span>
                        <span class="text-white"><?php echo htmlspecialchars($show['theater_name']); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Date & Time:</span>
                        <span class="text-white"><?php echo date('D, M j, Y • g:i A', strtotime($show['show_time'])); ?></span>
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

    <script>
    // Global variables
    const ticketPrice = <?php echo $show['price']; ?>;
    const convenienceFee = 20.00;
    const showId = <?php echo $show_id; ?>;
    
    let selectedSeats = [];
    let currentBookingId = null;
    let paymentWindow = null;
    let paymentCheckInterval = null;

    // Utility functions
    function showAlert(message, type = 'error') {
        // Remove existing alerts
        const existingAlerts = document.querySelectorAll('.alert');
        existingAlerts.forEach(alert => alert.remove());
        
        const alert = document.createElement('div');
        alert.className = `alert ${type}`;
        alert.innerHTML = `
            <div class="flex items-center justify-between">
                <span>${message}</span>
                <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-white hover:text-gray-200">
                    ×
                </button>
            </div>
        `;
        document.body.appendChild(alert);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (alert.parentElement) {
                alert.remove();
            }
        }, 5000);
    }

    function showLoading(show = true) {
        const overlay = document.getElementById('loadingOverlay');
        if (show) {
            overlay.classList.add('active');
        } else {
            overlay.classList.remove('active');
        }
    }

    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
        console.log('🚀 Booking system initializing...');
        console.log('Show ID:', showId);
        console.log('Ticket Price:', ticketPrice);
        
        try {
            initializeSeatSelection();
            updateBookingDetails();
            console.log('✅ Booking system initialized successfully');
        } catch (error) {
            console.error('❌ Error initializing booking system:', error);
            showAlert('Failed to initialize booking system. Please refresh the page.');
        }
    });
    
    function initializeSeatSelection() {
        const seats = document.querySelectorAll('.seat');
        console.log(`Found ${seats.length} seats`);
        
        seats.forEach((seat, index) => {
            const seatNumber = seat.dataset.seat;
            const isDisabled = seat.hasAttribute('data-disabled');
            
            if (!isDisabled) {
                seat.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    try {
                        toggleSeat(this);
                    } catch (error) {
                        console.error('Error toggling seat:', error);
                        showAlert('Error selecting seat. Please try again.');
                    }
                });
                
                // Add hover effects
                seat.addEventListener('mouseenter', function() {
                    if (!this.classList.contains('selected') && !this.hasAttribute('data-disabled')) {
                        this.style.transform = 'translateY(-2px) scale(1.05)';
                    }
                });
                
                seat.addEventListener('mouseleave', function() {
                    if (!this.classList.contains('selected')) {
                        this.style.transform = '';
                    }
                });
            }
        });
    }
    
    function toggleSeat(seatElement) {
        const seatNumber = seatElement.dataset.seat;
        console.log(`Toggling seat: ${seatNumber}`);
        
        if (seatElement.classList.contains('selected')) {
            // Deselect
            seatElement.classList.remove('selected');
            seatElement.classList.add('available');
            seatElement.style.transform = '';
            
            const index = selectedSeats.indexOf(seatNumber);
            if (index > -1) {
                selectedSeats.splice(index, 1);
            }
            
            updateTempSeatSelection(seatNumber, 'deselect');
        } else if (seatElement.classList.contains('available')) {
            // Select
            seatElement.classList.remove('available');
            seatElement.classList.add('selected');
            seatElement.style.transform = 'translateY(-2px) scale(1.05)';
            
            if (!selectedSeats.includes(seatNumber)) {
                selectedSeats.push(seatNumber);
            }
            
            updateTempSeatSelection(seatNumber, 'select');
        }
        
        console.log('Selected seats:', selectedSeats);
        updateBookingDetails();
    }
    
    function updateTempSeatSelection(seatNumber, action) {
        const formData = new FormData();
        formData.append('show_id', showId);
        formData.append('seat_number', seatNumber);
        formData.append('action', action);
        
        fetch('update_temp_seat.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (!data.success) {
                console.warn('Temp seat update warning:', data.error);
            }
        })
        .catch(error => {
            console.error('Error updating temp seat:', error);
            // Don't show alert for temp seat errors as they're not critical
        });
    }
    
    function updateBookingDetails() {
        const seatCount = selectedSeats.length;
        
        document.getElementById('selectedSeatsDisplay').textContent = 
            seatCount > 0 ? selectedSeats.join(', ') : 'None';
        document.getElementById('seatCount').textContent = seatCount;
        
        const subtotal = seatCount * ticketPrice;
        const total = subtotal + convenienceFee;
        
        document.getElementById('totalAmount').textContent = '₨' + total.toFixed(2);
        
        const bookButton = document.getElementById('bookButton');
        bookButton.disabled = seatCount === 0;
        
        if (seatCount === 0) {
            bookButton.style.opacity = '0.5';
            bookButton.style.cursor = 'not-allowed';
        } else {
            bookButton.style.opacity = '1';
            bookButton.style.cursor = 'pointer';
        }
    }
    
    // Book button handler
    document.getElementById('bookButton').addEventListener('click', function() {
        console.log('📝 Book button clicked');
        
        if (selectedSeats.length === 0) {
            showAlert('Please select at least one seat before proceeding.', 'warning');
            return;
        }
        
        const name = document.getElementById('userName').value.trim();
        const email = document.getElementById('userEmail').value.trim();
        const phone = document.getElementById('userPhone').value.trim();
        
        if (!name || !email || !phone) {
            showAlert('Please fill in all required fields.', 'warning');
            return;
        }
        
        // Validate email
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            showAlert('Please enter a valid email address.', 'warning');
            return;
        }
        
        // Validate phone
        if (phone.length < 10) {
            showAlert('Please enter a valid phone number.', 'warning');
            return;
        }
        
        createBooking(name, email, phone);
    });
    
    function createBooking(name, email, phone) {
        console.log('🎫 Creating booking...');
        console.log('Data:', { name, email, phone, seats: selectedSeats });
        showLoading(true);
        
        const formData = new FormData();
        formData.append('action', 'create_booking');
        formData.append('name', name);
        formData.append('email', email);
        formData.append('phone', phone);
        selectedSeats.forEach(seat => {
            formData.append('seats[]', seat);
        });
        
        // Log the form data
        for (let pair of formData.entries()) {
            console.log(pair[0] + ': ' + pair[1]);
        }
        
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            // Check if response is JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                return response.text().then(text => {
                    console.error('Non-JSON response:', text);
                    throw new Error('Server returned non-JSON response');
                });
            }
            
            return response.json();
        })
        .then(data => {
            showLoading(false);
            console.log('Booking response:', data);
        
            if (data.success) {
                console.log('✅ Booking created:', data.booking_id);
                console.log('Redirecting to:', data.redirect);
            
                // Show success message briefly before redirect
                showAlert('Booking created successfully! Redirecting to payment...', 'success');
            
                // Redirect after a short delay
                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 1500);
            } else {
                console.error('❌ Booking failed:', data);
                if (data.errors && Array.isArray(data.errors)) {
                    showAlert(data.errors.join('<br>'));
                } else {
                    showAlert(data.message || 'Booking failed. Please try again.');
                }
            }
        })
        .catch(error => {
            showLoading(false);
            console.error('❌ Booking error:', error);
            showAlert('Network error or server issue. Please check the console and try again.');
        
            // Log additional debug info
            console.error('Error details:', {
                message: error.message,
                stack: error.stack
            });
        });
    }
    
    function showPaymentModal() {
        const total = (selectedSeats.length * ticketPrice) + convenienceFee;
        document.getElementById('modalTotalAmount').textContent = '₨' + total.toFixed(2);
        document.getElementById('paymentModal').classList.add('active');
    }
    
    function hidePaymentModal() {
        document.getElementById('paymentModal').classList.remove('active');
    }
    
    function showSuccessModal() {
        document.getElementById('ticketSeats').textContent = selectedSeats.join(', ');
        document.getElementById('ticketTotal').textContent = document.getElementById('modalTotalAmount').textContent;
        
        hidePaymentModal();
        document.getElementById('successModal').classList.add('active');
    }
    
    // Payment handlers
    document.getElementById('payWithKhaltiBtn').addEventListener('click', function() {
        if (!currentBookingId) {
            showAlert('No booking found. Please try again.');
            return;
        }
        
        this.disabled = true;
        this.textContent = 'Processing...';
        
        initiateKhaltiPayment();
    });
    
    function initiateKhaltiPayment() {
        console.log('💳 Initiating Khalti payment...');
        
        const formData = new FormData();
        formData.append('action', 'initiate_payment');
        formData.append('booking_id', currentBookingId);
        
        fetch('payment_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                console.log('✅ Payment initiated successfully');
                paymentWindow = window.open(data.payment_url, 'khaltiPayment', 'width=800,height=600,scrollbars=yes,resizable=yes');
                
                if (!paymentWindow) {
                    throw new Error('Popup blocked. Please allow popups for this site.');
                }
                
                startPaymentStatusCheck(data.pidx);
            } else {
                throw new Error(data.message || 'Payment initiation failed');
            }
        })
        .catch(error => {
            console.error('❌ Payment initiation error:', error);
            showAlert(error.message || 'Payment initiation failed. Please try again.');
            resetPaymentButton();
        });
    }
    
    function startPaymentStatusCheck(pidx) {
        console.log('🔍 Starting payment status check...');
        let checkCount = 0;
        const maxChecks = 60; // 5 minutes max
        
        paymentCheckInterval = setInterval(() => {
            checkCount++;
            
            // Check if payment window is closed
            if (paymentWindow && paymentWindow.closed) {
                console.log('💰 Payment window closed, checking final status...');
                clearInterval(paymentCheckInterval);
                checkPaymentStatus(pidx, true);
                return;
            }
            
            // Timeout after max checks
            if (checkCount >= maxChecks) {
                console.log('⏰ Payment verification timeout');
                clearInterval(paymentCheckInterval);
                resetPaymentButton();
                showAlert('Payment verification timeout. Please check your booking status.');
                return;
            }
            
            // Regular status check
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
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                if (data.status === 'completed') {
                    console.log('✅ Payment completed successfully!');
                    clearInterval(paymentCheckInterval);
                    if (paymentWindow) paymentWindow.close();
                    showSuccessModal();
                } else if (data.status === 'failed') {
                    console.log('❌ Payment failed');
                    clearInterval(paymentCheckInterval);
                    if (paymentWindow) paymentWindow.close();
                    showAlert('Payment failed. Please try again.');
                    resetPaymentButton();
                } else if (isFinalCheck) {
                    console.log('⚠️ Payment status unclear on final check');
                    showAlert('Payment status unclear. Please check your booking status.');
                    resetPaymentButton();
                }
            } else if (isFinalCheck) {
                console.log('⚠️ Payment verification failed on final check');
                showAlert('Payment verification failed. Please check your booking status.');
                resetPaymentButton();
            }
        })
        .catch(error => {
            console.error('Error checking payment status:', error);
            if (isFinalCheck) {
                showAlert('Unable to verify payment. Please check your booking status.');
                resetPaymentButton();
            }
        });
    }
    
    function resetPaymentButton() {
        const btn = document.getElementById('payWithKhaltiBtn');
        btn.disabled = false;
        btn.textContent = 'Pay with Khalti';
    }
    
    // Modal close handlers
    document.getElementById('cancelPaymentBtn').addEventListener('click', function() {
        hidePaymentModal();
        if (paymentCheckInterval) {
            clearInterval(paymentCheckInterval);
        }
        if (paymentWindow) {
            paymentWindow.close();
        }
        resetPaymentButton();
    });
    
    document.getElementById('closeSuccessBtn').addEventListener('click', function() {
        window.location.href = '../view/index.php';
    });
    
    // Periodic seat status check
    let seatCheckInterval = setInterval(() => {
        fetch(`check_seats.php?show_id=${showId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (Array.isArray(data)) {
                    data.forEach(seat => {
                        const seatElement = document.querySelector(`[data-seat="${seat.seat_number}"]`);
                        if (seatElement && !selectedSeats.includes(seat.seat_number)) {
                            // Reset seat classes
                            seatElement.className = 'seat';
                            
                            if (seat.status === 'booked' || seat.status === 'reserved') {
                                seatElement.classList.add('booked');
                                seatElement.setAttribute('data-disabled', 'true');
                            } else if (seat.status === 'temp_selected') {
                                seatElement.classList.add('temp-selected');
                                seatElement.setAttribute('data-disabled', 'true');
                            } else {
                                seatElement.classList.add('available');
                                seatElement.removeAttribute('data-disabled');
                            }
                        }
                    });
                }
            })
            .catch(error => {
                console.error('Error checking seats:', error);
                // Don't show alert for periodic checks
            });
    }, 10000); // Check every 10 seconds
    
    // Cleanup on page unload
    window.addEventListener('beforeunload', function() {
        if (paymentCheckInterval) {
            clearInterval(paymentCheckInterval);
        }
        if (seatCheckInterval) {
            clearInterval(seatCheckInterval);
        }
        if (paymentWindow) {
            paymentWindow.close();
        }
    });

    // Add this debug function after the other functions
    function debugBookingState() {
        console.log('=== BOOKING DEBUG INFO ===');
        console.log('Show ID:', showId);
        console.log('Selected Seats:', selectedSeats);
        console.log('Ticket Price:', ticketPrice);
        console.log('User Details:', {
            name: document.getElementById('userName').value,
            email: document.getElementById('userEmail').value,
            phone: document.getElementById('userPhone').value
        });
        console.log('Current URL:', window.location.href);
        console.log('========================');
    }

    // Call debug function when book button is clicked
    document.getElementById('bookButton').addEventListener('click', function() {
        debugBookingState();
        
        if (selectedSeats.length === 0) {
            showAlert('Please select at least one seat before proceeding.', 'warning');
            return;
        }
        
        const name = document.getElementById('userName').value.trim();
        const email = document.getElementById('userEmail').value.trim();
        const phone = document.getElementById('userPhone').value.trim();
        
        if (!name || !email || !phone) {
            showAlert('Please fill in all required fields.', 'warning');
            return;
        }
        
        // Validate email
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            showAlert('Please enter a valid email address.', 'warning');
            return;
        }
        
        // Validate phone
        if (phone.length < 10) {
            showAlert('Please enter a valid phone number.', 'warning');
            return;
        }
        
        createBooking(name, email, phone);
    });
</script>
</body>
</html>
