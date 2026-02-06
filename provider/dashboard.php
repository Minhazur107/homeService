<?php
require_once '../includes/functions.php';

// Check if provider is logged in
if (!isProviderLoggedIn()) {
    redirect('login.php');
}

$currentLang = getLanguage();
$provider = getCurrentProvider();

// Get provider's confirmed bookings with customer details
$confirmedBookings = fetchAll("
    SELECT b.*, u.name as customer_name, u.phone as customer_phone, u.location as customer_location,
           sc.name as category_name, sc.name_bn as category_name_bn
    FROM bookings b
    JOIN users u ON b.customer_id = u.id
    LEFT JOIN service_categories sc ON b.category_id = sc.id
    WHERE b.provider_id = ? AND b.status = 'confirmed'
    ORDER BY b.booking_date ASC, b.booking_time ASC
", [$provider['id']]);

// Get provider's reviews
$reviews = fetchAll("
    SELECT r.*, u.name as customer_name, u.phone as customer_phone
    FROM reviews r
    JOIN users u ON r.customer_id = u.id
    WHERE r.provider_id = ? AND r.status = 'approved'
    ORDER BY r.created_at DESC
    LIMIT 10
", [$provider['id']]);

// Get provider's income statistics
$incomeStats = fetchOne("
    SELECT 
        COUNT(*) as total_bookings,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_bookings,
        SUM(CASE WHEN status = 'completed' AND final_price IS NOT NULL THEN final_price ELSE 0 END) as total_income,
        AVG(CASE WHEN status = 'completed' AND final_price IS NOT NULL THEN final_price ELSE NULL END) as avg_booking_value
    FROM bookings 
    WHERE provider_id = ?
", [$provider['id']]);

// Get monthly income for current year
$monthlyIncome = fetchAll("
    SELECT 
        MONTH(updated_at) as month,
        SUM(final_price) as income,
        COUNT(*) as bookings
    FROM bookings 
    WHERE provider_id = ? AND status = 'completed' AND final_price IS NOT NULL 
    AND YEAR(updated_at) = YEAR(CURRENT_DATE())
    GROUP BY MONTH(updated_at)
    ORDER BY month ASC
", [$provider['id']]);

// Compute verified payments and revenue split (admin-verified)
$paymentStats = fetchOne(
    "SELECT SUM(amount) AS gross_amount FROM payments WHERE provider_id = ? AND status = 'verified'",
    [$provider['id']]
);
$grossAmount = (float)($paymentStats['gross_amount'] ?? 0);
$platformRevenue = $grossAmount * 0.20; // 20% platform share
$providerNetIncome = $grossAmount * 0.80; // 80% provider share

// Get recent notifications
$notifications = getUnreadNotifications($provider['id'], 'provider');

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    redirect('../index.php');
}

// Mark notification as read
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    markNotificationAsRead($_GET['mark_read']);
    redirect('dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="<?php echo $currentLang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $currentLang === 'en' ? 'Provider Dashboard' : 'প্রদানকারী ড্যাশবোর্ড'; ?> - Home Service</title>
    <link rel="stylesheet" href="../assets/ui.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
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
                    <span class="text-gray-700 font-bold text-lg hidden sm:inline-block">
                        <i class="fas fa-tools text-primary mr-3"></i>
                        <?php echo $currentLang === 'en' ? 'Provider Dashboard' : 'প্রদানকারী ড্যাশবোর্ড'; ?>
                    </span>
                </div>
                
                <div class="flex items-center space-x-4">
                    <a href="bookings.php" class="nav-link text-gray-600 hover:text-primary transition-colors font-bold">
                        <i class="fas fa-calendar-alt"></i>
                        <?php echo $currentLang === 'en' ? 'Bookings' : 'বুকিং'; ?>
                    </a>
                    <a href="income.php" class="nav-link text-gray-600 hover:text-primary transition-colors font-bold">
                        <i class="fas fa-wallet text-secondary"></i>
                        <?php echo $currentLang === 'en' ? 'Income' : 'আয়'; ?>
                    </a>
                    <div class="flex items-center space-x-3 border-l border-gray-100 pl-4 ml-2">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-primary to-secondary flex items-center justify-center text-white font-bold shadow-lg">
                            <?php echo strtoupper(substr($provider['name'], 0, 1)); ?>
                        </div>
                        <div class="text-right hidden sm:block">
                            <div class="font-bold text-gray-800 leading-none"><?php echo htmlspecialchars($provider['name']); ?></div>
                            <div class="text-[10px] font-black uppercase text-primary tracking-widest mt-1">Certified Partner</div>
                        </div>
                    </div>
                    <a href="?logout=1" class="btn-primary py-2 px-4 text-xs font-bold bg-gradient-to-r from-red-500 to-rose-600 hover:from-red-600 hover:to-rose-700 border-none">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>
        </nav>
    </header>

    <div class="container mx-auto px-4 py-8 relative z-10">
        <!-- Welcome Section -->
        <div class="glass-card p-10 mb-10 overflow-hidden relative group">
            <div class="absolute top-0 right-0 p-10 opacity-10 group-hover:scale-110 transition-transform duration-700">
                 <i class="fas fa-tools text-9xl text-primary"></i>
            </div>
            <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-8">
                <div class="flex flex-col md:flex-row items-center gap-8">
                    <div class="w-24 h-24 rounded-3xl bg-gradient-to-br from-primary to-secondary flex items-center justify-center text-white text-4xl shadow-2xl shadow-primary/30">
                        <i class="fas fa-crown"></i>
                    </div>
                    <div>
                        <h1 class="text-4xl md:text-5xl font-black text-gray-900 mb-2 tracking-tighter">
                            <?php echo $currentLang === 'en' ? 'Welcome,' : 'স্বাগতম,'; ?> 
                            <span class="text-gradient"><?php echo htmlspecialchars($provider['name']); ?>!</span>
                        </h1>
                        <p class="text-xl text-gray-500 font-medium max-w-xl leading-relaxed">
                            <?php echo $currentLang === 'en' ? 'Your home service business is growing. Here is what is happening today.' : 'আপনার হোম সার্ভিস ব্যবসা বাড়ছে। আজ যা ঘটছে তা এখানে।'; ?>
                        </p>
                    </div>
                </div>
                <div class="bg-white/50 backdrop-blur-md p-6 rounded-[2rem] border border-white/50 text-center min-w-[180px]">
                    <div class="text-5xl font-black text-primary leading-none mb-2">
                        <?php echo count($confirmedBookings); ?>
                    </div>
                    <div class="text-xs font-black uppercase tracking-widest text-gray-400">
                        <?php echo $currentLang === 'en' ? 'Active Jobs' : 'সক্রিয় কাজ'; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Income Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">
            <div class="glass-card p-8 group border-b-4 border-indigo-500">
                <div class="w-14 h-14 bg-indigo-500/10 rounded-2xl flex items-center justify-center text-2xl text-indigo-600 mb-6 group-hover:scale-110 transition-transform">
                    <i class="fas fa-calendar"></i>
                </div>
                <div class="text-4xl font-black text-gray-900 mb-1"><?php echo $incomeStats['total_bookings'] ?? 0; ?></div>
                <div class="text-xs font-black text-gray-400 uppercase tracking-[0.2em]"><?php echo $currentLang === 'en' ? 'Total' : 'মোট'; ?></div>
            </div>

            <div class="glass-card p-8 group border-b-4 border-emerald-500">
                <div class="w-14 h-14 bg-emerald-500/10 rounded-2xl flex items-center justify-center text-2xl text-emerald-600 mb-6 group-hover:scale-110 transition-transform">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="text-4xl font-black text-gray-900 mb-1"><?php echo $incomeStats['completed_bookings'] ?? 0; ?></div>
                <div class="text-xs font-black text-gray-400 uppercase tracking-[0.2em]"><?php echo $currentLang === 'en' ? 'Done' : 'সম্পন্ন'; ?></div>
            </div>

            <div class="glass-card p-8 group border-b-4 border-amber-500">
                <div class="w-14 h-14 bg-amber-500/10 rounded-2xl flex items-center justify-center text-2xl text-amber-600 mb-6 group-hover:scale-110 transition-transform">
                    <i class="fas fa-wallet"></i>
                </div>
                <div class="text-2xl font-black text-gray-900 mb-1"><?php echo formatPrice($providerNetIncome); ?></div>
                <div class="text-xs font-black text-gray-400 uppercase tracking-[0.2em]"><?php echo $currentLang === 'en' ? 'Net Income (80%)' : 'নিট আয় (৮০%)'; ?></div>
            </div>

            <div class="glass-card p-8 group border-b-4 border-purple-500">
                <div class="w-14 h-14 bg-purple-500/10 rounded-2xl flex items-center justify-center text-2xl text-purple-600 mb-6 group-hover:scale-110 transition-transform">
                    <i class="fas fa-receipt"></i>
                </div>
                <div class="text-2xl font-black text-gray-900 mb-1"><?php echo formatPrice($platformRevenue); ?></div>
                <div class="text-xs font-black text-gray-400 uppercase tracking-[0.2em]"><?php echo $currentLang === 'en' ? 'Fee (20%)' : 'ফি (২০%)'; ?></div>
            </div>
        </div>

        <!-- Notifications -->
        <?php if (!empty($notifications)): ?>
            <div class="provider-card p-6 mb-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">
                    <i class="fas fa-bell text-purple-600 mr-3"></i>
                    <?php echo $currentLang === 'en' ? 'Recent Notifications' : 'সাম্প্রতিক বিজ্ঞপ্তি'; ?>
                </h2>
                <div class="space-y-4">
                    <?php foreach ($notifications as $notification): ?>
                        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-4 hover:shadow-lg transition-all duration-300">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <h3 class="font-semibold text-gray-800 text-lg"><?php echo htmlspecialchars($notification['title']); ?></h3>
                                    <p class="text-gray-600 mt-2"><?php echo htmlspecialchars($notification['message']); ?></p>
                                    <p class="text-sm text-gray-500 mt-3">
                                        <i class="fas fa-clock mr-1"></i>
                                        <?php echo formatDateTime($notification['created_at']); ?>
                                    </p>
                                </div>
                                <a href="?mark_read=<?php echo $notification['id']; ?>" 
                                   class="action-btn blue text-sm">
                                    <i class="fas fa-check mr-1"></i>
                                    <?php echo $currentLang === 'en' ? 'Mark as Read' : 'পঠিত হিসেবে চিহ্নিত করুন'; ?>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Confirmed Bookings -->
        <div class="glass-card p-8 mb-10">
            <h2 class="text-3xl font-black text-gray-900 mb-8 tracking-tight">
                <i class="fas fa-calendar-check text-primary mr-3"></i>
                <?php echo $currentLang === 'en' ? 'Active Commitments' : 'সক্রিয় প্রতিশ্রুতি'; ?>
            </h2>
            
            <?php if (empty($confirmedBookings)): ?>
                <div class="text-center py-12">
                    <div class="w-24 h-24 bg-gradient-to-br from-purple-100 to-pink-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-calendar-check text-4xl text-purple-500"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-700 mb-2">
                        <?php echo $currentLang === 'en' ? 'No Confirmed Bookings' : 'কোনো নিশ্চিত বুকিং নেই'; ?>
                    </h3>
                    <p class="text-gray-500 max-w-md mx-auto">
                        <?php echo $currentLang === 'en' ? 'You don\'t have any confirmed bookings yet. New bookings will appear here once customers confirm them.' : 'আপনার এখনও কোনো নিশ্চিত বুকিং নেই। নতুন বুকিং এখানে দেখা যাবে যখন গ্রাহকরা সেগুলি নিশ্চিত করবে।'; ?>
                    </p>
                </div>
            <?php else: ?>
                <div class="space-y-6">
                    <?php foreach ($confirmedBookings as $booking): ?>
                        <div class="bg-white/50 backdrop-blur-sm border border-gray-100 rounded-[2rem] p-8 hover:bg-white transition-all duration-300">
                            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                                <!-- Booking Details -->
                                <div>
                                    <h3 class="font-semibold text-gray-800 mb-3">
                                        <?php echo $currentLang === 'en' ? 'Booking Details' : 'বুকিং বিবরণ'; ?>
                                    </h3>
                                    <div class="space-y-2 text-sm text-gray-600">
                                        <div><i class="fas fa-calendar mr-2"></i><?php echo formatDate($booking['booking_date']); ?></div>
                                        <div><i class="fas fa-clock mr-2"></i><?php echo $booking['booking_time']; ?></div>
                                        <div><i class="fas fa-tag mr-2"></i><?php echo $currentLang === 'en' ? $booking['category_name'] : $booking['category_name_bn']; ?></div>
                                        <?php if ($booking['service_type']): ?>
                                            <div><i class="fas fa-tools mr-2"></i><?php echo htmlspecialchars($booking['service_type']); ?></div>
                                        <?php endif; ?>
                                        <?php if ($booking['final_price']): ?>
                                            <div><i class="fas fa-money-bill mr-2"></i><?php echo formatPrice($booking['final_price']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Customer Information -->
                                <div>
                                    <h3 class="font-semibold text-gray-800 mb-3">
                                        <?php echo $currentLang === 'en' ? 'Customer Information' : 'গ্রাহকের তথ্য'; ?>
                                    </h3>
                                    <div class="space-y-2 text-sm text-gray-600">
                                        <div><i class="fas fa-user mr-2"></i><?php echo htmlspecialchars($booking['customer_name']); ?></div>
                                        <div><i class="fas fa-phone mr-2"></i><?php echo htmlspecialchars($booking['customer_phone']); ?></div>
                                        <?php if ($booking['customer_location']): ?>
                                            <div><i class="fas fa-map-marker-alt mr-2"></i><?php echo htmlspecialchars($booking['customer_location']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Customer Address -->
                                <div>
                                    <h3 class="font-semibold text-gray-800 mb-3">
                                        <?php echo $currentLang === 'en' ? 'Service Address' : 'সেবার ঠিকানা'; ?>
                                    </h3>
                                    <div class="text-sm text-gray-600">
                                        <?php if ($booking['customer_address']): ?>
                                            <div class="bg-gray-50 p-3 rounded-lg">
                                                <i class="fas fa-home mr-2"></i>
                                                <?php echo nl2br(htmlspecialchars($booking['customer_address'])); ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="text-gray-400 italic">
                                                <?php echo $currentLang === 'en' ? 'No address provided' : 'কোনো ঠিকানা দেওয়া হয়নি'; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <?php if ($booking['notes']): ?>
                                <div class="mt-4 pt-4 border-t border-gray-200">
                                    <h4 class="font-medium text-gray-800 mb-2">
                                        <?php echo $currentLang === 'en' ? 'Customer Notes' : 'গ্রাহকের নোট'; ?>
                                    </h4>
                                    <p class="text-sm text-gray-600 bg-gray-50 p-3 rounded-lg">
                                        <?php echo htmlspecialchars($booking['notes']); ?>
                                    </p>
                                </div>
                            <?php endif; ?>

                            <!-- Action Buttons -->
                            <div class="mt-4 pt-4 border-t border-gray-200 flex space-x-3">
                                <a href="tel:<?php echo $booking['customer_phone']; ?>" 
                                   class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 text-sm">
                                    <i class="fas fa-phone mr-2"></i><?php echo $currentLang === 'en' ? 'Call Customer' : 'গ্রাহককে কল করুন'; ?>
                                </a>
                                
                                <a href="https://wa.me/<?php echo $booking['customer_phone']; ?>?text=<?php echo urlencode($currentLang === 'en' ? 'Hi, I am calling about your confirmed booking. When would you like me to arrive?' : 'হাই, আমি আপনার নিশ্চিত বুকিং নিয়ে কল করছি। আপনি কখন আমাকে আসতে চান?'); ?>" 
                                   target="_blank"
                                   class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 text-sm">
                                    <i class="fab fa-whatsapp mr-2"></i><?php echo $currentLang === 'en' ? 'WhatsApp' : 'হোয়াটসঅ্যাপ'; ?>
                                </a>
                                
                                <button onclick="markAsCompleted(<?php echo $booking['id']; ?>)" 
                                        class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm">
                                    <i class="fas fa-check mr-2"></i><?php echo $currentLang === 'en' ? 'Mark Completed' : 'সম্পন্ন হিসেবে চিহ্নিত করুন'; ?>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Customer Reviews -->
        <div class="glass-card p-8 mb-10">
            <h2 class="text-3xl font-black text-gray-900 mb-8 tracking-tight">
                <i class="fas fa-star text-primary mr-3"></i>
                <?php echo $currentLang === 'en' ? 'Client Testimonials' : 'গ্রাহক পর্যালোচনা'; ?>
            </h2>
            
            <?php if (empty($reviews)): ?>
                <div class="text-center py-12">
                    <div class="w-24 h-24 bg-gradient-to-br from-yellow-100 to-orange-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-star text-4xl text-yellow-500"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-700 mb-2">
                        <?php echo $currentLang === 'en' ? 'No Reviews Yet' : 'এখনও কোনো পর্যালোচনা নেই'; ?>
                    </h3>
                    <p class="text-gray-500 max-w-md mx-auto">
                        <?php echo $currentLang === 'en' ? 'You haven\'t received any reviews yet. Complete more services to start building your reputation.' : 'আপনি এখনও কোনো পর্যালোচনা পাননি। আপনার খ্যাতি তৈরি করতে আরও পরিষেবা সম্পন্ন করুন।'; ?>
                    </p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <?php foreach ($reviews as $review): ?>
                        <div class="bg-white/50 backdrop-blur-sm border border-gray-100 rounded-[2rem] p-6 hover:bg-white transition-all duration-300 relative group">
                            <div class="absolute top-6 right-6 opacity-5 group-hover:opacity-20 transition-opacity">
                                <i class="fas fa-quote-right text-4xl text-primary"></i>
                            </div>
                            <div class="flex justify-between items-start mb-3">
                                <div class="flex items-center space-x-2">
                                    <div class="flex items-center">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star text-<?php echo $i <= $review['rating'] ? 'yellow' : 'gray'; ?>-400"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <span class="text-sm text-gray-600"><?php echo $review['rating']; ?>/5</span>
                                </div>
                                <span class="text-sm text-gray-500"><?php echo formatDateTime($review['created_at']); ?></span>
                            </div>
                            
                            <?php if ($review['review_text']): ?>
                                <p class="text-gray-700 mb-3"><?php echo htmlspecialchars($review['review_text']); ?></p>
                            <?php endif; ?>
                            
                            <div class="flex justify-between items-center">
                                <p class="text-sm text-gray-600">
                                    <i class="fas fa-user mr-1"></i>
                                    <?php echo htmlspecialchars($review['customer_name']); ?>
                                </p>
                                <?php if ($review['review_photo']): ?>
                                    <a href="../uploads/<?php echo htmlspecialchars($review['review_photo']); ?>" 
                                       target="_blank" class="text-blue-600 hover:text-blue-800 text-sm">
                                        <i class="fas fa-image mr-1"></i>
                                        <?php echo $currentLang === 'en' ? 'View Photo' : 'ছবি দেখুন'; ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Monthly Income Chart -->
        <?php if (!empty($monthlyIncome)): ?>
            <div class="provider-card p-6 mb-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">
                    <i class="fas fa-chart-line text-purple-600 mr-3"></i>
                    <?php echo $currentLang === 'en' ? 'Monthly Income (Current Year)' : 'মাসিক আয় (বর্তমান বছর)'; ?>
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                    <?php 
                    $months = [
                        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'May', 6 => 'Jun',
                        7 => 'Jul', 8 => 'Aug', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'
                    ];
                    $monthsBn = [
                        1 => 'জানু', 2 => 'ফেব্রু', 3 => 'মার্চ', 4 => 'এপ্রিল', 5 => 'মে', 6 => 'জুন',
                        7 => 'জুলাই', 8 => 'আগস্ট', 9 => 'সেপ্ট', 10 => 'অক্টো', 11 => 'নভে', 12 => 'ডিসে'
                    ];
                    
                    $monthlyData = [];
                    foreach ($monthlyIncome as $data) {
                        $monthlyData[$data['month']] = $data;
                    }
                    
                    for ($month = 1; $month <= 12; $month++):
                        $data = $monthlyData[$month] ?? null;
                        $income = $data ? $data['income'] : 0;
                        $bookings = $data ? $data['bookings'] : 0;
                    ?>
                        <div class="monthly-income-card hover:scale-105 transition-transform duration-300">
                            <div class="text-sm opacity-90 mb-2">
                                <?php echo $currentLang === 'en' ? $months[$month] : $monthsBn[$month]; ?>
                            </div>
                            <div class="text-xl font-bold mb-1">
                                <?php echo formatPrice($income); ?>
                            </div>
                            <div class="text-xs opacity-75">
                                <?php echo $bookings; ?> <?php echo $currentLang === 'en' ? 'bookings' : 'বুকিং'; ?>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="bg-white bg-opacity-95 backdrop-blur-sm border-t border-gray-200 py-8 mt-12 relative z-10">
        <div class="container mx-auto px-4 text-center">
            <p class="text-gray-600">&copy; <?php echo date('Y'); ?> Home Service. <?php echo $currentLang === 'en' ? 'All rights reserved.' : 'সর্বস্বত্ব সংরক্ষিত।'; ?></p>
        </div>
    </footer>

    <script>
        function markAsCompleted(bookingId) {
            if (confirm('<?php echo $currentLang === 'en' ? 'Mark this booking as completed?' : 'এই বুকিং সম্পন্ন হিসেবে চিহ্নিত করবেন?'; ?>')) {
                // You can implement AJAX call here to mark as completed
                // For now, redirect to a completion page
                window.location.href = `complete_booking.php?id=${bookingId}`;
            }
        }
    </script>
    <script src="../assets/ui.js"></script>
</body>
</html>