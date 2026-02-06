<?php
require_once '../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
	redirect('../auth/login.php');
}

$currentLang = getLanguage();
$user = getCurrentUser();

$bookingId = $_GET['id'] ?? 0;

// Fetch booking (pending or confirmed can be edited)
$booking = fetchOne("
	SELECT b.*, sp.name as provider_name, sp.phone as provider_phone, sc.name as category_name, sc.name_bn as category_name_bn
	FROM bookings b
	JOIN service_providers sp ON b.provider_id = sp.id
	JOIN service_categories sc ON b.category_id = sc.id
	WHERE b.id = ? AND b.customer_id = ? AND b.status IN ('pending','confirmed')
", [$bookingId, $user['id']]);

if (!$booking) {
	setFlashMessage('error', $currentLang === 'en' ? 'Booking not found or cannot be edited' : 'বুকিং পাওয়া যায়নি বা সম্পাদনা করা যাবে না');
	redirect('bookings.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$serviceType = sanitizeInput($_POST['service_type']);
	$bookingDate = $_POST['booking_date'];
	$bookingTime = $_POST['booking_time'];
	$customerAddress = sanitizeInput($_POST['customer_address']);
	$notes = sanitizeInput($_POST['notes']);
	
	if (empty($bookingDate)) {
		$error = $currentLang === 'en' ? 'Service date is required' : 'সেবার তারিখ প্রয়োজন';
	} elseif (strtotime($bookingDate) < strtotime('today')) {
		$error = $currentLang === 'en' ? 'Service date cannot be in the past' : 'সেবার তারিখ অতীত হতে পারে না';
	} elseif (empty($bookingTime)) {
		$error = $currentLang === 'en' ? 'Service time is required' : 'সেবার সময় প্রয়োজন';
	} elseif (empty($customerAddress)) {
		$error = $currentLang === 'en' ? 'Service address is required' : 'সেবার ঠিকানা প্রয়োজন';
	} else {
		try {
			executeQuery("
				UPDATE bookings
				SET service_type = ?, booking_date = ?, booking_time = ?, customer_address = ?, notes = ?, updated_at = NOW()
				WHERE id = ? AND customer_id = ?
			", [
				$serviceType ?: null,
				$bookingDate,
				$bookingTime,
				$customerAddress,
				$notes ?: null,
				$booking['id'],
				$user['id']
			]);
			setFlashMessage('success', $currentLang === 'en' ? 'Booking updated successfully!' : 'বুকিং সফলভাবে হালনাগাদ হয়েছে!');
			redirect('booking_details.php?id=' . $booking['id']);
		} catch (Exception $e) {
			$error = $currentLang === 'en' ? 'Failed to update booking. Please try again.' : 'বুকিং হালনাগাদ করতে ব্যর্থ। আবার চেষ্টা করুন।';
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
    <title><?php echo $currentLang === 'en' ? 'Edit Slot' : 'বুকিং সম্পাদনা'; ?> - S24</title>
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
                        S24
                    </a>
                </div>
                <div class="hidden md:flex items-center space-x-6">
                    <a href="booking_details.php?id=<?php echo $booking['id']; ?>" class="font-bold text-gray-700 hover:text-primary transition-colors">
                        <i class="fas fa-arrow-left mr-1"></i>
                        <?php echo $currentLang === 'en' ? 'Back' : 'ফিরে যান'; ?>
                    </a>
                </div>
            </div>
        </nav>
    </header>

    <div class="container mx-auto px-4 py-12 relative z-10">
        <div class="max-w-2xl mx-auto">
            <div class="glass-card p-10 anim-pop-in">
                <div class="flex items-center gap-4 mb-10 pb-6 border-b border-gray-100">
                    <div class="w-12 h-12 bg-primary/10 rounded-xl flex items-center justify-center text-primary">
                        <i class="fas fa-edit text-2xl"></i>
                    </div>
                    <div>
                        <h2 class="text-3xl font-black text-gray-900 tracking-tighter"><?php echo t('Edit Booking'); ?></h2>
                        <p class="text-gray-500 font-medium"><?php echo t('Update your appointment details'); ?></p>
                    </div>
                </div>

                <?php if ($error): ?>
                    <div class="mb-8 p-5 bg-rose-50 text-rose-600 rounded-2xl border border-rose-100 font-bold flex items-center gap-3 anim-shake">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo $error; ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- Service Type -->
                        <div class="space-y-3">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1"><?php echo t('Task Type'); ?></label>
                            <input type="text" name="service_type" value="<?php echo htmlspecialchars($_POST['service_type'] ?? ($booking['service_type'] ?? '')); ?>"
                                   class="w-full px-5 py-4 bg-gray-50 border-2 border-transparent rounded-2xl focus:bg-white focus:border-primary focus:outline-none font-bold text-gray-900 transition-all shadow-inner">
                        </div>

                        <!-- Date -->
                        <div class="space-y-3">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1"><?php echo t('New Date'); ?> *</label>
                            <input type="date" name="booking_date" value="<?php echo htmlspecialchars($_POST['booking_date'] ?? $booking['booking_date']); ?>"
                                   min="<?php echo date('Y-m-d'); ?>" required
                                   class="w-full px-5 py-4 bg-gray-50 border-2 border-transparent rounded-2xl focus:bg-white focus:border-primary focus:outline-none font-bold text-gray-900 transition-all shadow-inner">
                        </div>

                        <!-- Time -->
                        <div class="space-y-3">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1"><?php echo t('New Time'); ?> *</label>
                            <input type="time" name="booking_time" value="<?php echo htmlspecialchars($_POST['booking_time'] ?? $booking['booking_time']); ?>"
                                   required
                                   class="w-full px-5 py-4 bg-gray-50 border-2 border-transparent rounded-2xl focus:bg-white focus:border-primary focus:outline-none font-bold text-gray-900 transition-all shadow-inner">
                        </div>
                        
                        <!-- Address -->
                        <div class="space-y-3 md:col-span-2">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1"><?php echo t('Service Address'); ?> *</label>
                            <textarea name="customer_address" rows="2" required
                                      class="w-full px-5 py-4 bg-gray-50 border-2 border-transparent rounded-2xl focus:bg-white focus:border-primary focus:outline-none font-bold text-gray-900 transition-all shadow-inner"><?php echo htmlspecialchars($_POST['customer_address'] ?? ($booking['customer_address'] ?? '')); ?></textarea>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="space-y-3">
                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1"><?php echo t('Notes for Pro'); ?></label>
                        <textarea name="notes" rows="4"
                                  placeholder="Update any instructions..."
                                  class="w-full px-5 py-4 bg-gray-50 border-2 border-transparent rounded-2xl focus:bg-white focus:border-primary focus:outline-none font-bold text-gray-900 transition-all shadow-inner"><?php echo htmlspecialchars($_POST['notes'] ?? ($booking['notes'] ?? '')); ?></textarea>
                    </div>

                    <!-- Actions -->
                    <div class="pt-10 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-6">
                        <a href="booking_details.php?id=<?php echo $booking['id']; ?>" class="text-xs font-black text-gray-400 hover:text-rose-500 uppercase tracking-widest transition-colors">
                            <i class="fas fa-times mr-2"></i><?php echo t('Discard Changes'); ?>
                        </a>
                        <button type="submit" class="w-full sm:w-auto btn-primary py-4 px-12 text-sm flex items-center justify-center gap-3">
                            <i class="fas fa-save"></i>
                            <span><?php echo t('Commit Updates'); ?></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="glass-nav py-12 mt-20">
        <div class="container mx-auto px-4 text-center">
            <p class="text-gray-500 font-black text-sm uppercase tracking-widest">&copy; <?php echo date('Y'); ?> S24 PREMIUM SOLUTIONS. ALL RIGHTS RESERVED.</p>
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