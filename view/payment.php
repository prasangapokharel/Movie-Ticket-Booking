<?php include '../model/Payment.php';?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - CineBook</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body{font-family:'Inter',sans-serif;background:#0f172a;color:#f8fafc;min-height:100vh}
        .payment-card{background:#1e293b;border:1px solid #334155;border-radius:12px;padding:1.5rem}
        .divider{height:1px;background:#334155;margin:12px 0}
        .ticket-info{background:#1e293b;padding:10px;border-radius:8px;margin-bottom:8px;border:1px solid #334155}
        .btn{display:inline-block;padding:10px 16px;border-radius:8px;font-weight:500;text-align:center;cursor:pointer;transition:background-color .2s}
        .btn-primary{background:#b91c1c;color:#fff}
        .btn-primary:hover{background:#991b1b}
        .btn-khalti{background:#5C2D91;color:#fff}
        .btn-khalti:hover{background:#4A2275}
        .payment-method{border:2px solid transparent;cursor:pointer}
        .payment-method.selected{border-color:#5C2D91;background:rgba(92,45,145,.1)}
        .loading-overlay{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(15,23,42,.9);display:flex;flex-direction:column;justify-content:center;align-items:center;z-index:9999;opacity:0;visibility:hidden;transition:opacity .3s ease}
        .loading-overlay.active{opacity:1;visibility:visible}
        .spinner{width:50px;height:50px;border:5px solid rgba(255,255,255,.1);border-radius:50%;border-top-color:#5C2D91;animation:spin 1s linear infinite;margin-bottom:1rem}
        @keyframes spin{to{transform:rotate(360deg)}}
        .order-paid-banner{background:#10b981;color:#fff;padding:1rem;border-radius:8px;margin-bottom:1rem;display:flex;align-items:center;justify-content:center;font-weight:500}
        .order-paid-banner svg{margin-right:.5rem}
    </style>
</head>
<body>
    <?php include '../includes/nav.php'; ?>
    
    <div id="loadingOverlay" class="loading-overlay">
        <div class="spinner"></div>
        <p class="text-white font-medium">Processing payment...</p>
    </div>
    
    <div class="max-w-4xl mx-auto px-4 pt-8 pb-24">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold mb-2 text-white">Complete Your Payment</h1>
            <p class="text-gray-400 max-w-lg mx-auto">Please review your booking details and complete the payment to confirm your tickets.</p>
        </div>
        
        <div id="orderPaidBanner" class="order-paid-banner <?php echo $is_paid ? '' : 'hidden'; ?>">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
            </svg>
            Order Paid - Your booking is confirmed!
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="md:col-span-2">
                <div class="payment-card mb-6">
                    <h2 class="text-xl font-bold mb-4">Booking Details</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div class="ticket-info">
                            <div class="text-xs text-slate-400">Movie</div>
                            <div class="text-sm font-medium text-white"><?php echo $booking['title']; ?></div>
                        </div>
                        <div class="ticket-info">
                            <div class="text-xs text-slate-400">Theater</div>
                            <div class="text-sm font-medium text-white"><?php echo $booking['theater_name']; ?></div>
                        </div>
                        <div class="ticket-info">
                            <div class="text-xs text-slate-400">Date & Time</div>
                            <div class="text-sm font-medium text-white"><?php echo $show_date; ?> at <?php echo $show_time; ?></div>
                        </div>
                        <div class="ticket-info">
                            <div class="text-xs text-slate-400">Seats</div>
                            <div class="text-sm font-medium text-white"><?php echo $seats_text; ?></div>
                        </div>
                    </div>
                    
                    <div class="divider"></div>
                    
                    <div class="flex justify-between items-center">
                        <span class="text-slate-400">Total Amount</span>
                        <span class="text-xl font-bold text-white">₨ <?php echo number_format($booking['total_price'], 2); ?></span>
                    </div>
                </div>
                
                <div class="payment-card">
                    <h2 class="text-xl font-bold mb-4">Payment Method</h2>
                    
                    <div class="space-y-4">
                        <div class="payment-method selected rounded-lg p-4 flex items-center">
                            <div class="w-12 h-12 bg-white rounded-lg flex items-center justify-center mr-4">
                                <img src="https://play-lh.googleusercontent.com/Xh_OlrdkF1UnGCnMN__4z-yXffBAEl0eUDeVDPr4UthOERV4Fll9S-TozSfnlXDFzw" alt="Khalti" class="w-8 h-8" onerror="this.src='https://khalti.s3.ap-south-1.amazonaws.com/KPG/dist/2020.12.22.0.0.0/images/khalti-logo.png'">
                            </div>
                            <div>
                                <h3 class="font-medium text-white">Khalti Digital Wallet</h3>
                                <p class="text-xs text-slate-400">Pay securely using Khalti</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-6">
                        <button id="payWithKhalti" class="w-full btn btn-khalti py-3 px-4 rounded-lg text-sm font-medium" <?php echo $is_paid ? 'disabled' : ''; ?>>
                            <span class="flex items-center justify-center">
                                <?php if ($is_paid): ?>
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                    </svg>
                                    Payment Completed
                                <?php else: ?>
                                    Pay with Khalti
                                <?php endif; ?>
                            </span>
                        </button>
                    </div>
                    
                    <?php if ($is_paid): ?>
                    <div class="mt-4 text-center">
                        <a href="booking_confirmation.php?booking_id=<?php echo $booking_id; ?>" class="btn btn-primary">
                            View Booking Confirmation
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="md:col-span-1">
                <div class="payment-card sticky top-20">
                    <h2 class="text-xl font-bold mb-4">Order Summary</h2>
                    
                    <?php if (!$is_paid): ?>
                    <div class="mb-4 p-3 bg-gray-800/50 rounded-lg text-center">
                        <p class="text-sm text-gray-400 mb-1">Time remaining to complete payment:</p>
                        <div id="countdown" class="text-xl font-bold text-red-400">15:00</div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="space-y-3 mb-4">
                        <div class="flex justify-between">
                            <span class="text-slate-400">Ticket Price:</span>
                            <span class="text-white">₨ <?php echo number_format($booking['total_price'] - 20, 2); ?> × <span id="seatCount"><?php echo count($seats); ?></span></span>
                        </div>
                        
                        <div class="flex justify-between">
                            <span class="text-slate-400">Convenience Fee:</span>
                            <span class="text-white">₨ 20.00</span>
                        </div>
                    </div>
                    
                    <div class="divider"></div>
                    
                    <div class="flex justify-between items-center mb-6">
                        <span class="font-medium">Total:</span>
                        <span class="text-xl font-bold text-white">₨ <?php echo number_format($booking['total_price'], 2); ?></span>
                    </div>
                    
                    <div class="text-xs text-slate-400">
                        <p class="mb-2">By completing this payment, you agree to our <a href="#" class="text-purple-400 hover:underline">Terms of Service</a> and <a href="#" class="text-purple-400 hover:underline">Privacy Policy</a>.</p>
                        <p>All transactions are secure and encrypted.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script>
        let paymentPidx = '';
        let paymentCheckInterval = null;
        let paymentStatusCheckInterval = null;
        
        const loadingOverlay = document.getElementById('loadingOverlay');
        const countdownElement = document.getElementById('countdown');
        const orderPaidBanner = document.getElementById('orderPaidBanner');
        const payWithKhaltiButton = document.getElementById('payWithKhalti');
        
        const isPaid = <?php echo $is_paid ? 'true' : 'false'; ?>;
        
        if (!isPaid) {
            paymentStatusCheckInterval = setInterval(checkPaymentStatusFromDB, 5000);
            checkPaymentStatusFromDB();
        }
        
        function checkPaymentStatusFromDB() {
            fetch('payment.php?booking_id=<?php echo $booking_id; ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=check_payment_status_db'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.is_paid) {
                    clearInterval(paymentStatusCheckInterval);
                    
                    orderPaidBanner.classList.remove('hidden');
                    
                    payWithKhaltiButton.disabled = true;
                    payWithKhaltiButton.innerHTML = `
                        <span class="flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                            Payment Completed
                        </span>
                    `;
                    
                    const paymentCard = payWithKhaltiButton.closest('.payment-card');
                    if (!paymentCard.querySelector('a.btn-primary')) {
                        const confirmationLink = document.createElement('div');
                        confirmationLink.className = 'mt-4 text-center';
                        confirmationLink.innerHTML = `
                            <a href="booking_confirmation.php?booking_id=<?php echo $booking_id; ?>" class="btn btn-primary">
                                View Booking Confirmation
                            </a>
                        `;
                        paymentCard.appendChild(confirmationLink);
                    }
                    
                    if (countdownElement) {
                        const countdownContainer = countdownElement.closest('div.mb-4');
                        if (countdownContainer) {
                            countdownContainer.style.display = 'none';
                        }
                    }
                }
            })
            .catch(error => {
                console.error('Error checking payment status:', error);
            });
        }
        
        if (payWithKhaltiButton) {
            payWithKhaltiButton.addEventListener('click', function() {
                if (!this.disabled) {
                    initiatePayment();
                }
            });
        }
        
        function initiatePayment() {
            loadingOverlay.classList.add('active');
            
            fetch('payment.php?booking_id=<?php echo $booking_id; ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=initiate_payment'
            })
            .then(response => response.json())
            .then(data => {
                loadingOverlay.classList.remove('active');
                
                if (data.success) {
                    paymentPidx = data.pidx;
                    
                    const paymentWindow = window.open(data.payment_url, 'khaltiPayment', 'width=800,height=600');
                    
                    startPaymentStatusCheck();
                } else {
                    alert(data.message || 'Failed to initiate payment. Please try again.');
                }
            })
            .catch(error => {
                loadingOverlay.classList.remove('active');
                alert('An error occurred. Please try again.');
                console.error('Error:', error);
            });
        }
        
        function startPaymentStatusCheck() {
            if (paymentCheckInterval) {
                clearInterval(paymentCheckInterval);
            }
            
            checkPaymentStatus();
            paymentCheckInterval = setInterval(checkPaymentStatus, 5000);
        }
        
        function checkPaymentStatus() {
            if (!paymentPidx) return;
            
            fetch('payment.php?booking_id=<?php echo $booking_id; ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=check_payment_status&pidx=' + paymentPidx
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.status === 'completed') {
                        clearInterval(paymentCheckInterval);
                        
                        setTimeout(() => {
                            window.location.href = 'booking_confirmation.php?booking_id=<?php echo $booking_id; ?>';
                        }, 2000);
                    } else if (data.status === 'pending') {
                        console.log('Payment still pending...');
                    } else if (data.status === 'failed') {
                        clearInterval(paymentCheckInterval);
                        alert('Payment failed. Please try again.');
                    }
                } else {
                    console.error('Error checking payment status:', data.message);
                }
            })
            .catch(error => {
                console.error('Error checking payment status:', error);
            });
        }
        
        <?php if (!$is_paid): ?>
        let timeLeft = <?php echo max(0, ($booking_timestamp + $timeout) - $current_time); ?>;
        
        function updateCountdown() {
            if (!countdownElement) return;
            
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            
            countdownElement.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            
            if (timeLeft <= 0) {
                clearInterval(countdownInterval);
                countdownElement.textContent = '00:00';
                alert('Your booking session has expired. Please try again.');
                window.location.href = 'my_bookings.php';
            } else {
                timeLeft--;
            }
        }
        
        const countdownInterval = setInterval(updateCountdown, 1000);
        updateCountdown();
        <?php endif; ?>
    </script>
</body>
</html>
