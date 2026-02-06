<?php
require_once '../includes/functions.php';

if (!isLoggedIn()) {
	redirect('../auth/login.php');
}

$user = getCurrentUser();
$currentLang = getLanguage();

// Get customer's payments
$payments = fetchAll("SELECT p.*, b.booking_date, b.booking_time, b.service_type, sp.name as provider_name
		FROM payments p
		JOIN bookings b ON p.booking_id = b.id
		JOIN service_providers sp ON p.provider_id = sp.id
		WHERE p.customer_id = ?
		ORDER BY p.created_at DESC", [$user['id']]);

// Get completed bookings that need payment
$completedBookingsNeedingPayment = fetchAll("
    SELECT b.*, sp.name as provider_name, sp.phone as provider_phone, sp.email as provider_email,
           sc.name as category_name, sc.name_bn as category_name_bn
    FROM bookings b
    JOIN service_providers sp ON b.provider_id = sp.id
    LEFT JOIN service_categories sc ON sp.category_id = sc.id
    WHERE b.customer_id = ? AND b.status = 'completed' 
    AND NOT EXISTS (
        SELECT 1 FROM payments p WHERE p.booking_id = b.id
    )
    ORDER BY b.updated_at DESC
", [$user['id']]);

// Get payment statistics
$paymentStats = fetchOne("
    SELECT 
        COUNT(*) as total_payments,
        SUM(CASE WHEN status = 'verified' THEN 1 ELSE 0 END) as verified_payments,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_payments,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_payments,
        SUM(CASE WHEN status = 'verified' THEN amount ELSE 0 END) as total_verified_amount,
        SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as total_pending_amount,
        SUM(amount) as total_amount
    FROM payments 
    WHERE customer_id = ?
", [$user['id']]);

// Get payment required notifications
$paymentRequiredNotifications = fetchAll("
    SELECT * FROM notifications 
    WHERE user_id = ? AND user_type = 'customer' AND type = 'payment_required' AND is_read = 0
    ORDER BY created_at DESC
", [$user['id']]);

$flash = getFlashMessage();

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
	    <title><?php echo $currentLang === 'en' ? 'My Payments' : 'আমার পেমেন্ট'; ?> - Home Service</title>
    <link rel="stylesheet" href="../assets/ui.css">
	<script src="https://cdn.tailwindcss.com"></script>
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
	<style>
		.status-badge {
			padding: 0.5rem 1rem;
			border-radius: 2rem;
			font-size: 0.75rem;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: 0.05em;
		}
		
		.status-verified { background: linear-gradient(135deg, #10b981, #059669); color: white; }
		.status-pending { background: linear-gradient(135deg, #f59e0b, #d97706); color: white; }
		.status-rejected { background: linear-gradient(135deg, #ef4444, #dc2626); color: white; }
	</style>
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
                    <a href="../index.php" class="text-3xl font-black text-gradient uppercase">
                        Home Service
                    </a>
                    <!-- Theme Picker -->
                    <div class="theme-picker" data-theme-picker>
                        <button class="w-10 h-10 rounded-xl bg-white/80 border border-white/40 flex items-center justify-center text-primary shadow-sm hover:scale-110 transition-transform" data-toggle>
                            <i class="fas fa-palette"></i>
                        </button>
                        <div class="theme-menu hidden p-3 grid grid-cols-4 gap-2 w-48 shadow-2xl">
                            <div class="theme-swatch" style="background: #6d28d9;" data-theme="theme-purple"></div>
                            <div class="theme-swatch" style="background: #10b981;" data-theme="theme-emerald"></div>
                            <div class="theme-swatch" style="background: #e11d48;" data-theme="theme-rose"></div>
                            <div class="theme-swatch" style="background: #f59e0b;" data-theme="theme-amber"></div>
                            <div class="theme-swatch" style="background: #334155;" data-theme="theme-slate"></div>
                            <div class="theme-swatch" style="background: #06b6d4;" data-theme="theme-cyan"></div>
                            <div class="theme-swatch" style="background: #ec4899;" data-theme="theme-pink"></div>
                        </div>
                    </div>
                </div>
                
                <div class="hidden md:flex items-center space-x-6">
                    <a href="dashboard.php" class="font-bold text-gray-700 hover:text-primary transition-colors">
                        <i class="fas fa-home mr-1"></i>
                        <?php echo $currentLang === 'en' ? 'Dashboard' : 'ড্যাশবোর্ড'; ?>
                    </a>
                    <a href="bookings.php" class="font-bold text-gray-700 hover:text-primary transition-colors">
                        <i class="fas fa-calendar-alt mr-1"></i>
                        <?php echo $currentLang === 'en' ? 'Bookings' : 'বুকিং'; ?>
                    </a>
                    <a href="service_history.php" class="font-bold text-gray-700 hover:text-primary transition-colors">
                        <i class="fas fa-history mr-1"></i>
                        <?php echo $currentLang === 'en' ? 'History' : 'ইতিহাস'; ?>
                    </a>
                    <a href="reviews.php" class="font-bold text-gray-700 hover:text-primary transition-colors">
                        <i class="fas fa-star mr-1"></i>
                        <?php echo $currentLang === 'en' ? 'Reviews' : 'রিভিউ'; ?>
                    </a>
                    <a href="notifications.php" class="font-bold text-gray-700 hover:text-primary transition-colors">
                        <i class="fas fa-bell mr-1"></i>
                        <?php echo $currentLang === 'en' ? 'Notifications' : 'বিজ্ঞপ্তি'; ?>
                    </a>
                    <a href="my_selections.php" class="font-bold text-gray-700 hover:text-primary transition-colors">
                        <i class="fas fa-heart mr-1"></i>
                        <?php echo $currentLang === 'en' ? 'Selections' : 'নির্বাচন'; ?>
                    </a>
                    <a href="payments.php" class="font-bold text-primary transition-colors border-b-2 border-primary">
                        <i class="fas fa-credit-card mr-1"></i>
                        <?php echo $currentLang === 'en' ? 'Payments' : 'পেমেন্ট'; ?>
                    </a>
                    <a href="profile.php" class="font-bold text-gray-700 hover:text-primary transition-colors">
                        <i class="fas fa-user mr-1"></i>
                        <?php echo $currentLang === 'en' ? 'Profile' : 'প্রোফাইল'; ?>
                    </a>
                    
                    <div class="flex items-center space-x-2 bg-gray-100 p-1 rounded-xl">
                        <a href="?lang=en" class="px-3 py-1 rounded-lg text-xs font-bold <?php echo $currentLang === 'en' ? 'bg-white shadow-sm text-primary' : 'text-gray-400'; ?>">EN</a>
                        <a href="?lang=bn" class="px-3 py-1 rounded-lg text-xs font-bold <?php echo $currentLang === 'bn' ? 'bg-white shadow-sm text-primary' : 'text-gray-400'; ?>">বাংলা</a>
                    </div>

                    <div class="flex items-center space-x-3 ml-2 pl-4 border-l border-gray-100">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-primary to-secondary flex items-center justify-center text-white font-bold shadow-lg shadow-primary/20">
                            <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                        </div>
                        <div class="text-right hidden lg:block">
                            <div class="font-bold text-gray-800 leading-none"><?php echo htmlspecialchars($user['name']); ?></div>
                            <div class="text-[10px] font-black uppercase text-primary tracking-widest mt-1">Prime Customer</div>
                        </div>
                    </div>
                    
                    <a href="?logout=1" class="btn-primary py-2 px-4 text-xs">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
                
                <!-- Mobile Menu Button -->
                <button class="md:hidden text-2xl text-gray-700" id="mobileMenuBtn">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </nav>
    </header>
	
	<div class="container mx-auto px-4 py-8 relative z-10">
		<?php if ($flash): ?>
            <div class="glass-card p-4 mb-6 border-l-4 <?php echo $flash['type'] === 'success' ? 'border-green-500' : 'border-red-500'; ?> bg-white/80">
                <div class="flex items-center space-x-3">
                    <i class="fas <?php echo $flash['type'] === 'success' ? 'fa-check-circle text-green-500' : 'fa-exclamation-circle text-red-500'; ?> text-xl"></i>
                    <span class="font-bold text-gray-800">
                        <?php echo htmlspecialchars($flash['message']); ?>
                    </span>
                </div>
            </div>
		<?php endif; ?>
		
		<!-- Payment Statistics -->
		<div class="glass-card p-10 mb-10">
			<div class="flex flex-col md:flex-row justify-between items-center gap-6 mb-10">
				<div>
					<h1 class="text-4xl font-black text-gray-900 mb-2 tracking-tighter">
                        <?php echo $currentLang === 'en' ? 'Payment Analytics' : 'পেমেন্ট পরিসংখ্যান'; ?>
                    </h1>
					<p class="text-gray-500 font-medium"><?php echo $currentLang === 'en' ? 'Overview of your personal service economy' : 'আপনার ব্যক্তিগত সেবা অর্থনীতির ওভারভিউ'; ?></p>
				</div>
                <div class="flex items-center gap-4 bg-primary/5 p-5 rounded-2xl border border-primary/10">
                    <div class="w-14 h-14 bg-primary rounded-xl flex items-center justify-center text-white shadow-lg shadow-primary/30">
                        <i class="fas fa-chart-line text-2xl"></i>
                    </div>
                    <div>
                        <div class="text-3xl font-black text-primary leading-none">৳<?php echo number_format($paymentStats['total_amount'] ?? 0); ?></div>
                        <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mt-1"><?php echo $currentLang === 'en' ? 'Volume' : 'পরিমাণ'; ?></div>
                    </div>
                </div>
			</div>
			
			<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
				<div class="bg-emerald-50/50 rounded-2x p-6 border border-emerald-100 flex items-center justify-between">
					<div>
						<p class="text-[10px] font-black text-emerald-600 uppercase tracking-widest mb-1"><?php echo $currentLang === 'en' ? 'Total' : 'মোট'; ?></p>
						<p class="text-2xl font-black text-emerald-900"><?php echo $paymentStats['total_payments'] ?? 0; ?></p>
					</div>
					<div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center text-emerald-600">
						<i class="fas fa-credit-card"></i>
					</div>
				</div>
				
				<div class="bg-blue-50/50 rounded-2xl p-6 border border-blue-100 flex items-center justify-between">
					<div>
						<p class="text-[10px] font-black text-blue-600 uppercase tracking-widest mb-1"><?php echo $currentLang === 'en' ? 'Verified' : 'যাচাইকৃত'; ?></p>
						<p class="text-2xl font-black text-blue-900"><?php echo $paymentStats['verified_payments'] ?? 0; ?></p>
					</div>
					<div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center text-blue-600">
						<i class="fas fa-check-circle"></i>
					</div>
				</div>
				
				<div class="bg-amber-50/50 rounded-2xl p-6 border border-amber-100 flex items-center justify-between">
					<div>
						<p class="text-[10px] font-black text-amber-600 uppercase tracking-widest mb-1"><?php echo $currentLang === 'en' ? 'Pending' : 'অপেক্ষমান'; ?></p>
						<p class="text-2xl font-black text-amber-900"><?php echo $paymentStats['pending_payments'] ?? 0; ?></p>
					</div>
					<div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center text-amber-600">
						<i class="fas fa-clock"></i>
					</div>
				</div>
				
				<div class="bg-rose-50/50 rounded-2xl p-6 border border-rose-100 flex items-center justify-between">
					<div>
						<p class="text-[10px] font-black text-rose-600 uppercase tracking-widest mb-1"><?php echo $currentLang === 'en' ? 'Volume' : 'পরিমাণ'; ?></p>
						<p class="text-2xl font-black text-rose-900">৳<?php echo number_format($paymentStats['total_verified_amount'] ?? 0); ?></p>
					</div>
					<div class="w-12 h-12 bg-rose-100 rounded-xl flex items-center justify-center text-rose-600">
						<i class="fas fa-coins"></i>
					</div>
				</div>
			</div>
			
			<!-- Payment Required Notifications -->
			<?php if (!empty($paymentRequiredNotifications)): ?>
				<div class="mt-8 p-6 bg-amber-50 rounded-2xl border border-amber-100">
					<div class="flex items-center space-x-3 mb-6">
						<i class="fas fa-exclamation-triangle text-amber-600 text-xl"></i>
						<h3 class="text-lg font-black text-amber-900">
							<?php echo $currentLang === 'en' ? 'Dues Outstanding' : 'পেমেন্ট প্রয়োজন বিজ্ঞপ্তি'; ?>
						</h3>
					</div>
					<div class="space-y-3">
						<?php foreach ($paymentRequiredNotifications as $notification): ?>
							<div class="flex items-center justify-between p-4 bg-white/80 rounded-xl border border-amber-50">
								<div class="flex items-center space-x-4">
									<div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center text-amber-600">
                                        <i class="fas fa-bell animate-swing"></i>
                                    </div>
									<div>
										<p class="font-black text-gray-900 text-sm"><?php echo htmlspecialchars($notification['title']); ?></p>
										<p class="text-xs font-medium text-gray-500"><?php echo htmlspecialchars($notification['message']); ?></p>
									</div>
								</div>
								<div class="text-right">
									<p class="text-[10px] font-black text-gray-400 mb-1"><?php echo formatDate($notification['created_at']); ?></p>
									<a href="notifications.php" class="text-xs font-black text-amber-600 hover:underline uppercase tracking-widest">
										<?php echo $currentLang === 'en' ? 'Pay Now' : 'দেখুন'; ?>
									</a>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>
		</div>
		
		<!-- Completed Services Needing Payment -->
		<?php if (!empty($completedBookingsNeedingPayment)): ?>
			<div class="glass-card p-8 mb-10 border-l-8 border-amber-500">
				<div class="flex items-center space-x-4 mb-8">
					<div class="w-12 h-12 bg-amber-500 rounded-xl flex items-center justify-center text-white shadow-lg shadow-amber-500/30">
						<i class="fas fa-exclamation-circle text-xl"></i>
					</div>
					<div>
						<h2 class="text-2xl font-black text-gray-900"><?php echo $currentLang === 'en' ? 'Action Required: Unpaid Services' : 'পেমেন্ট প্রয়োজন'; ?></h2>
						<p class="text-xs font-bold text-gray-400 uppercase tracking-widest"><?php echo $currentLang === 'en' ? 'Complete services awaiting secure payment' : 'সম্পন্ন পরিষেবা পেমেন্টের অপেক্ষায়'; ?></p>
					</div>
				</div>
				
				<div class="grid gap-6">
					<?php foreach ($completedBookingsNeedingPayment as $booking): ?>
						<div class="glass-card p-6 bg-white/50 hover:bg-white transition-all">
							<div class="flex flex-col lg:flex-row justify-between lg:items-center gap-6">
								<div class="flex items-center gap-6">
									<div class="w-16 h-16 rounded-2xl bg-amber-100 flex items-center justify-center text-amber-600 text-2xl font-black">
										<i class="fas fa-clock"></i>
									</div>
									<div>
                                        <div class="flex items-center gap-3 mb-1">
										    <h3 class="text-xl font-black text-gray-900">#<?php echo $booking['id']; ?></h3>
                                            <span class="text-[10px] font-black bg-amber-100 text-amber-600 px-3 py-1 rounded-full uppercase tracking-tighter">Due</span>
                                        </div>
										<p class="text-sm font-bold text-gray-500 uppercase tracking-widest">
											<?php echo $currentLang === 'en' ? $booking['category_name'] : $booking['category_name_bn']; ?> • 
											<?php echo htmlspecialchars($booking['service_type']); ?>
										</p>
									</div>
								</div>
								
								<div class="grid grid-cols-2 md:grid-cols-3 gap-8">
									<div>
										<div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1"><?php echo $currentLang === 'en' ? 'Provider' : 'প্রদানকারী'; ?></div>
										<div class="font-bold text-gray-800"><?php echo htmlspecialchars($booking['provider_name']); ?></div>
									</div>
									<div>
										<div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1"><?php echo $currentLang === 'en' ? 'Completed' : 'সম্পন্ন'; ?></div>
										<div class="font-bold text-gray-800"><?php echo formatDate($booking['updated_at']); ?></div>
									</div>
									<div class="hidden md:block">
										<div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1"><?php echo $currentLang === 'en' ? 'Location' : 'অবস্থান'; ?></div>
										<div class="font-bold text-gray-800 break-all"><?php echo htmlspecialchars(substr($booking['customer_address'] ?? 'N/A', 0, 30)); ?>...</div>
									</div>
								</div>
								
								<div class="flex items-center gap-3">
									<a href="payment_form.php?booking_id=<?php echo $booking['id']; ?>" class="btn-primary py-3 px-8 text-sm flex items-center gap-2">
										<i class="fas fa-credit-card"></i>
										<span><?php echo $currentLang === 'en' ? 'Pay Now' : 'পেমেন্ট করুন'; ?></span>
									</a>
									<a href="booking_details.php?id=<?php echo $booking['id']; ?>" class="w-11 h-11 bg-gray-100 rounded-xl flex items-center justify-center text-gray-700 hover:bg-primary hover:text-white transition-all">
										<i class="fas fa-eye"></i>
									</a>
								</div>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endif; ?>
		
		<!-- Payment History -->
		<div class="glass-card p-10">
			<div class="flex flex-col md:flex-row justify-between items-center gap-6 mb-10">
				<div class="flex items-center space-x-4">
					<div class="w-12 h-12 bg-primary/10 rounded-xl flex items-center justify-center text-primary">
						<i class="fas fa-receipt text-2xl"></i>
					</div>
					<div>
						<h2 class="text-3xl font-black text-gray-900 tracking-tighter"><?php echo $currentLang === 'en' ? 'Transaction History' : 'আমার পেমেন্ট ইতিহাস'; ?></h2>
						<p class="text-gray-500 font-medium"><?php echo $currentLang === 'en' ? 'Historical record of all service settlements' : 'আপনার সব পেমেন্ট লেনদেন দেখুন'; ?></p>
					</div>
				</div>
			</div>
			
			<?php if (empty($payments)): ?>
				<div class="py-20 text-center">
					<div class="w-24 h-24 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-6 text-gray-200">
						<i class="fas fa-credit-card text-4xl"></i>
					</div>
					<h3 class="text-2xl font-black text-gray-900 mb-2"><?php echo $currentLang === 'en' ? 'No Transactions Recorded' : 'কোন পেমেন্ট পাওয়া যায়নি'; ?></h3>
					<p class="text-gray-500 font-medium"><?php echo $currentLang === 'en' ? 'Your financial history is clean. No payments found.' : 'আপনি এখনও কোন পেমেন্ট করেননি।'; ?></p>
				</div>
			<?php else: ?>
				<div class="grid gap-6">
					<?php foreach ($payments as $payment): ?>
						<div class="glass-card p-8 group hover:scale-[1.01] transition-transform">
							<div class="flex flex-col lg:flex-row justify-between gap-8">
								<div class="flex-grow">
									<div class="flex items-center gap-4 mb-6">
										<div class="w-14 h-14 bg-gray-50 rounded-2xl flex items-center justify-center text-primary border border-gray-100">
											<i class="fas fa-file-invoice-dollar text-2xl"></i>
										</div>
										<div>
                                            <div class="flex items-center gap-3">
											    <h3 class="text-xl font-black text-gray-900 pr-3 border-r border-gray-200"><?php echo $currentLang === 'en' ? 'TRX' : 'লেনদেন'; ?> #<?php echo $payment['id']; ?></h3>
                                                <span class="status-badge <?php echo $payment['status'] === 'verified' ? 'status-verified' : ($payment['status'] === 'pending' ? 'status-pending' : 'status-rejected'); ?>">
                                                    <?php echo $currentLang === 'en' ? ucfirst($payment['status']) : ($payment['status'] === 'pending' ? 'অপেক্ষমান' : ($payment['status'] === 'verified' ? 'যাচাইকৃত' : 'প্রত্যাখ্যাত')); ?>
                                                </span>
                                            </div>
											<p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mt-1">
												<?php echo $currentLang === 'en' ? 'Linked with Booking' : 'বুকিং এর সাথে যুক্ত'; ?> #<?php echo $payment['booking_id']; ?> • <?php echo htmlspecialchars($payment['service_type']); ?>
											</p>
										</div>
									</div>
									
									<div class="grid grid-cols-2 md:grid-cols-4 gap-6">
										<div class="bg-gray-50/50 p-4 rounded-xl border border-gray-100">
											<div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1"><?php echo $currentLang === 'en' ? 'Recipient' : 'প্রদানকারী'; ?></div>
											<div class="font-bold text-gray-800"><?php echo htmlspecialchars($payment['provider_name']); ?></div>
										</div>
										
										<div class="bg-gray-50/50 p-4 rounded-xl border border-gray-100">
											<div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1"><?php echo $currentLang === 'en' ? 'Settled On' : 'জমা হয়েছে'; ?></div>
											<div class="font-bold text-gray-800"><?php echo formatDate($payment['created_at']); ?></div>
										</div>
										
										<div class="bg-gray-50/50 p-4 rounded-xl border border-gray-100">
											<div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1"><?php echo $currentLang === 'en' ? 'Method' : 'পদ্ধতি'; ?></div>
											<div class="font-bold text-gray-800 capitalize"><?php echo htmlspecialchars($payment['method']); ?></div>
										</div>

                                        <div class="bg-primary/5 p-4 rounded-xl border border-primary/10">
											<div class="text-[10px] font-black text-primary/60 uppercase tracking-widest mb-1"><?php echo $currentLang === 'en' ? 'Amount' : 'পরিমাণ'; ?></div>
											<div class="font-black text-xl text-primary">৳<?php echo number_format($payment['amount']); ?></div>
										</div>
									</div>
								</div>
								
								<div class="lg:w-64 flex flex-col justify-center border-t lg:border-t-0 lg:border-l border-gray-100 pt-6 lg:pt-0 lg:pl-8 space-y-3">
									<?php if ($payment['proof_file']): ?>
										<a href="../uploads/payments/<?php echo htmlspecialchars($payment['proof_file']); ?>" target="_blank" class="w-full btn-primary py-3 flex items-center justify-center gap-2">
											<i class="fas fa-receipt"></i>
											<span><?php echo $currentLang === 'en' ? 'View Slip' : 'প্রমাণ দেখুন'; ?></span>
										</a>
									<?php endif; ?>
									<a href="booking_details.php?id=<?php echo $payment['booking_id']; ?>" class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-3 rounded-xl flex items-center justify-center gap-2 transition-all">
										<i class="fas fa-external-link-alt"></i>
										<span><?php echo $currentLang === 'en' ? 'Booking' : 'বুকিং দেখুন'; ?></span>
									</a>
								</div>
							</div>
                            
							<?php if ($payment['transaction_id']): ?>
								<div class="mt-6 flex items-center gap-3 bg-gray-900 rounded-xl px-4 py-2 w-fit">
									<span class="text-[10px] text-white text-gray-500 uppercase tracking-widest"><?php echo $currentLang === 'en' ? 'TXID' : 'লেনদেন আইডি'; ?></span>
									<code class="text-xs text-white font-black"><?php echo htmlspecialchars($payment['transaction_id']); ?></code>
								</div>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
	
	<footer class="glass-nav py-8 mt-12">
        <div class="container mx-auto px-4 text-center">
            <p class="text-gray-500 font-bold">&copy; <?php echo date('Y'); ?> Home Service. All rights reserved.</p>
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
