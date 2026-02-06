<?php
require_once '../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('../auth/login.php');
}

$currentLang = getLanguage();
$user = getCurrentUser();

$bookingId = $_GET['id'] ?? 0;

// Get booking details
$booking = fetchOne("
    SELECT b.*, sp.name as provider_name, sp.phone as provider_phone, sc.name as category_name, sc.name_bn as category_name_bn
    FROM bookings b
    JOIN service_providers sp ON b.provider_id = sp.id
    JOIN service_categories sc ON b.category_id = sc.id
    WHERE b.id = ? AND b.customer_id = ? AND b.status IN ('pending','confirmed')
", [$bookingId, $user['id']]);

if (!$booking) {
    setFlashMessage('error', $currentLang === 'en' ? 'Booking not found or cannot be cancelled' : 'বুকিং পাওয়া যায়নি বা বাতিল করা যায় না');
    redirect('dashboard.php');
}

$error = '';
$success = '';

// Handle cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reason = sanitizeInput($_POST['reason']);
    
    if (empty($reason)) {
        $error = $currentLang === 'en' ? 'Please provide a cancellation reason' : 'বাতিলকরণের কারণ দিন';
    } else {
        // Calculate cancellation fee if applicable
        $cancellationFee = 0;
        $bookingDateTime = strtotime($booking['booking_date'] . ' ' . $booking['booking_time']);
        $currentTime = time();
        $hoursDifference = ($bookingDateTime - $currentTime) / 3600;
        
        // Get platform settings
        $minBookingHours = fetchOne("SELECT setting_value FROM platform_settings WHERE setting_key = 'min_booking_hours'")['setting_value'] ?? 2;
        $cancellationFeePercentage = fetchOne("SELECT setting_value FROM platform_settings WHERE setting_key = 'cancellation_fee_percentage'")['setting_value'] ?? 10;
        
        if ($hoursDifference < $minBookingHours) {
            $cancellationFee = ($booking['final_price'] ?? 0) * ($cancellationFeePercentage / 100);
        }
        
        // Update booking
        $sql = "UPDATE bookings SET status = 'cancelled', cancellation_reason = ?, cancellation_fee = ? WHERE id = ?";
        try {
            executeQuery($sql, [$reason, $cancellationFee, $bookingId]);
            $success = $currentLang === 'en' ? 'Booking abort successful.' : 'বুকিং সফলভাবে বাতিল হয়েছে।';
            
            if ($cancellationFee > 0) {
                $success .= ' ' . ($currentLang === 'en' ? 'Fee applied: ' : 'ফি প্রয়োগ করা হয়েছে: ') . formatPrice($cancellationFee);
            }
            setFlashMessage('success', $success);
            redirect('dashboard.php');
        } catch (Exception $e) {
            $error = $currentLang === 'en' ? 'Command failed. Try again.' : 'বাতিল করতে ব্যর্থ। আবার চেষ্টা করুন।';
        }
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    logout();
    redirect('../index.php');
}
?>
<!DOCTYPE html>
<html lang="<?php echo $currentLang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $currentLang === 'en' ? 'Abort Mission' : 'বুকিং বাতিল করুন'; ?> - HOME SERVICE</title>
    <link rel="stylesheet" href="../assets/ui.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="premium-bg min-h-screen">
    <!-- Floating Background Particles -->
    <div class="floating-particles">
        <div class="particle" style="width: 100px; height: 100px; top: 10%; left: 5%; animation-delay: 0s;"></div>
        <div class="particle" style="width: 150px; height: 150px; top: 60%; left: 80%; animation-delay: 2s;"></div>
        <div class="particle" style="width: 80px; height: 80px; top: 40%; left: 40%; animation-delay: 4s;"></div>
    </div>
    
    <!-- Header -->
    <header class="glass-nav sticky top-0 z-50">
        <nav class="container mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-6">
                    <a href="../index.php" class="text-3xl font-black text-gradient">
                        HOME SERVICE
                    </a>
                </div>
                <div class="hidden md:flex items-center space-x-6">
                    <a href="dashboard.php" class="font-bold text-gray-700 hover:text-primary transition-colors">
                        <i class="fas fa-home mr-1"></i>
                        <?php echo $currentLang === 'en' ? 'Dashboard' : 'ড্যাশবোর্ড'; ?>
                    </a>
                </div>
            </div>
        </nav>
    </header>

    <div class="container mx-auto px-4 py-12 relative z-10 text-center">
        <div class="max-w-xl mx-auto">
            <!-- Warning Card -->
            <div class="glass-card p-12 anim-pop-in">
                <div class="w-20 h-20 bg-rose-50 rounded-2xl flex items-center justify-center text-rose-500 text-4xl mx-auto mb-8 shadow-xl shadow-rose-500/10">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                
                <h1 class="text-4xl font-black text-gray-900 tracking-tighter mb-4"><?php echo t('Heads Up!'); ?></h1>
                <p class="text-gray-500 font-medium mb-10 italic"><?php echo $currentLang === 'en' ? 'Are you sure you want to terminate this booking? This protocol is irreversible.' : 'আপনি কি নিশ্চিত যে আপনি এই বুকিং বাতিল করতে চান? এটি পূর্বাবস্থায় ফেরানো যায় না।'; ?></p>

                <!-- Data Summary -->
                <div class="bg-gray-50/50 p-6 rounded-2xl border border-gray-100 mb-10 text-left space-y-4">
                    <div class="flex justify-between items-center text-xs">
                        <span class="font-black text-gray-400 uppercase tracking-widest">Provider</span>
                        <span class="font-black text-gray-900"><?php echo htmlspecialchars($booking['provider_name']); ?></span>
                    </div>
                    <div class="flex justify-between items-center text-xs">
                        <span class="font-black text-gray-400 uppercase tracking-widest">Service</span>
                        <span class="font-black text-gray-900"><?php echo t($booking['category_name']); ?></span>
                    </div>
                    <div class="flex justify-between items-center text-xs">
                        <span class="font-black text-gray-400 uppercase tracking-widest">Scheduled For</span>
                        <span class="font-black text-gray-900"><?php echo formatDate($booking['booking_date']); ?></span>
                    </div>
                </div>

                <?php if ($error): ?>
                    <div class="mb-8 p-4 bg-rose-50 text-rose-600 rounded-xl border border-rose-100 font-bold text-sm">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-8">
                    <div class="space-y-3 text-left">
                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1"><?php echo t('Reason for termination'); ?> *</label>
                        <textarea name="reason" required rows="3" 
                                  placeholder="Briefly explain why you are cancelling..."
                                  class="w-full px-6 py-4 bg-gray-50 border-2 border-transparent rounded-2xl focus:bg-white focus:border-rose-500 focus:outline-none font-bold text-gray-900 transition-all shadow-inner"></textarea>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-4">
                        <button type="submit" class="flex-1 bg-gray-900 text-white py-5 rounded-2xl font-black uppercase tracking-widest text-[10px] hover:bg-rose-600 transition-all shadow-xl shadow-gray-900/10">
                            Confirm Abort
                        </button>
                        <a href="dashboard.php" class="flex-1 bg-gray-100 text-gray-800 py-5 rounded-2xl font-black uppercase tracking-widest text-[10px] hover:bg-gray-200 transition-all">
                            Keep Booking
                        </a>
                    </div>
                </form>

                <!-- Support Line -->
                <div class="mt-8 pt-8 border-t border-gray-100">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Questions? <a href="#" class="text-primary hover:underline">Contact Support Protocol</a></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="glass-nav py-12 mt-20">
        <div class="container mx-auto px-4 text-center">
            <p class="text-gray-500 font-black text-sm uppercase tracking-widest">&copy; <?php echo date('Y'); ?> HOME SERVICE PREMIUM SOLUTIONS. ALL RIGHTS RESERVED.</p>
        </div>
    </footer>

    <script src="../assets/ui.js"></script>
    <script>
        // Observe glass cards for animation
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.glass-card').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'all 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275)';
            observer.observe(card);
        });

        const style = document.createElement('style');
        style.textContent = '.glass-card.visible { opacity: 1 !important; transform: translateY(0) !important; }';
        document.head.appendChild(style);
    </script>
</body>
</html>