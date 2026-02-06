<?php
require_once '../includes/functions.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    redirect('login.php');
}

$currentLang = getLanguage();
$admin = getCurrentAdmin();

// Get statistics
$totalProviders = fetchOne("SELECT COUNT(*) as count FROM service_providers")['count'];
$pendingProviders = fetchOne("SELECT COUNT(*) as count FROM service_providers WHERE verification_status = 'pending'")['count'];
$totalCustomers = fetchOne("SELECT COUNT(*) as count FROM users")['count'];
$totalBookings = fetchOne("SELECT COUNT(*) as count FROM bookings")['count'];
$pendingBookings = fetchOne("SELECT COUNT(*) as count FROM bookings WHERE status = 'pending'")['count'];
$completedBookings = fetchOne("SELECT COUNT(*) as count FROM bookings WHERE status = 'completed'")['count'];

// Payment statistics
$verifiedGross = fetchOne("SELECT SUM(amount) as total FROM payments WHERE status = 'verified'")['total'] ?? 0;
$pendingPayments = fetchOne("SELECT COUNT(*) as count FROM payments WHERE status = 'pending'")['count'];
$platformRevenueTotal = (float)$verifiedGross * 0.10; // 10% to platform
$providerPayoutsTotal = (float)$verifiedGross * 0.90; // 90% to providers (for reference)

// Admin unread notifications
$notifications = getUnreadNotifications($admin['id'], 'admin');

// Get recent pending providers
$recentPendingProviders = fetchAll("
    SELECT sp.*, sc.name as category_name, sc.name_bn as category_name_bn
    FROM service_providers sp
    JOIN service_categories sc ON sp.category_id = sc.id
    WHERE sp.verification_status = 'pending'
    ORDER BY sp.created_at DESC
    LIMIT 5
");

// Get recent bookings
$recentBookings = fetchAll("
    SELECT b.*, u.name as customer_name, sp.name as provider_name, sc.name as category_name, sc.name_bn as category_name_bn
    FROM bookings b
    JOIN users u ON b.customer_id = u.id
    JOIN service_providers sp ON b.provider_id = sp.id
    JOIN service_categories sc ON b.category_id = sc.id
    ORDER BY b.created_at DESC
    LIMIT 10
");

// Handle logout
if (isset($_GET['logout'])) {
    logout();
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
    <title><?php echo $currentLang === 'en' ? 'Admin Dashboard' : 'অ্যাডমিন ড্যাশবোর্ড'; ?> - Home Service</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/ui.css">
    <script src="../assets/ui.js"></script>
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
                    <span class="text-gray-700 font-bold text-lg hidden sm:inline-block border-r border-gray-100 pr-6 mr-6">
                        <i class="fas fa-shield-alt text-primary mr-2"></i>
                        <?php echo $currentLang === 'en' ? 'Admin Control' : 'অ্যাডমিন কন্ট্রোল'; ?>
                    </span>

                    <div class="hidden lg:flex items-center space-x-6">
                        <a href="dashboard.php" class="font-bold <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'text-primary' : 'text-gray-600 hover:text-primary'; ?> transition-colors flex items-center gap-2">
                            <i class="fas fa-grid-horizontal text-sm"></i>
                            <span><?php echo $currentLang === 'en' ? 'Dashboard' : 'ড্যাশবোর্ড'; ?></span>
                        </a>
                        <a href="users.php" class="font-bold <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'text-primary' : 'text-gray-600 hover:text-primary'; ?> transition-colors flex items-center gap-2">
                            <i class="fas fa-users text-sm text-center w-5"></i>
                            <span><?php echo $currentLang === 'en' ? 'Users' : 'ব্যবহারকারী'; ?></span>
                        </a>
                        <a href="providers.php" class="font-bold <?php echo basename($_SERVER['PHP_SELF']) == 'providers.php' ? 'text-primary' : 'text-gray-600 hover:text-primary'; ?> transition-colors flex items-center gap-2">
                            <i class="fas fa-users-gear text-sm"></i>
                            <span><?php echo $currentLang === 'en' ? 'Providers' : 'প্রদানকারী'; ?></span>
                        </a>
                        <a href="bookings.php" class="font-bold <?php echo basename($_SERVER['PHP_SELF']) == 'bookings.php' ? 'text-primary' : 'text-gray-600 hover:text-primary'; ?> transition-colors flex items-center gap-2">
                            <i class="fas fa-calendar-check text-sm"></i>
                            <span><?php echo $currentLang === 'en' ? 'Bookings' : 'বুকিং'; ?></span>
                        </a>
                        <a href="reviews.php" class="font-bold <?php echo basename($_SERVER['PHP_SELF']) == 'reviews.php' ? 'text-primary' : 'text-gray-600 hover:text-primary'; ?> transition-colors flex items-center gap-2">
                            <i class="fas fa-star-half-stroke text-sm"></i>
                            <span><?php echo $currentLang === 'en' ? 'Reviews' : 'রিভিউ'; ?></span>
                        </a>
                        <a href="payments.php" class="font-bold <?php echo basename($_SERVER['PHP_SELF']) == 'payments.php' ? 'text-primary' : 'text-gray-600 hover:text-primary'; ?> transition-colors flex items-center gap-2">
                            <i class="fas fa-credit-card text-sm"></i>
                            <span><?php echo $currentLang === 'en' ? 'Payments' : 'পেমেন্ট'; ?></span>
                        </a>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <a href="notifications.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'notifications.php' ? 'text-primary' : 'text-gray-600 hover:text-primary'; ?> transition-all relative font-bold">
                        <i class="fas fa-bell"></i>
                        <?php if (isset($notifications) && count($notifications) > 0): ?>
                            <span class="absolute -top-1 -right-1 bg-rose-500 text-white text-[10px] rounded-full h-4 w-4 flex items-center justify-center border-2 border-white">
                                <?php echo count($notifications); ?>
                            </span>
                        <?php endif; ?>
                    </a>
                    <div class="flex items-center space-x-3 border-l border-gray-100 pl-4 ml-2">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-primary to-secondary flex items-center justify-center text-white font-bold shadow-lg">
                            <?php echo strtoupper(substr($admin['username'], 0, 1)); ?>
                        </div>
                        <div class="text-right hidden sm:block">
                            <div class="font-bold text-gray-800 leading-none"><?php echo htmlspecialchars($admin['username']); ?></div>
                            <div class="text-[10px] font-black uppercase text-primary tracking-widest mt-1">Super Admin</div>
                        </div>
                    </div>
                    <a href="?logout=1" class="btn-primary py-2 px-4 text-xs font-bold">
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
                 <i class="fas fa-user-shield text-9xl text-primary"></i>
            </div>
            <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-8">
                <div class="flex items-center space-x-8">
                    <div class="w-24 h-24 rounded-3xl bg-gradient-to-br from-primary to-secondary flex items-center justify-center text-white text-4xl shadow-2xl shadow-primary/30">
                        <i class="fas fa-crown"></i>
                    </div>
                    <div>
                        <h1 class="text-4xl md:text-5xl font-black text-gray-900 mb-2 tracking-tighter">
                            <?php echo $currentLang === 'en' ? 'Command Center,' : 'স্বাগতম,'; ?> 
                            <span class="text-gradient"><?php echo htmlspecialchars($admin['username']); ?>!</span>
                        </h1>
                        <p class="text-xl text-gray-500 font-medium max-w-xl leading-relaxed">
                            <?php echo $currentLang === 'en' ? 'The platform is running smoothly. Here is a high-level overview.' : 'প্ল্যাটফর্মটি মসৃণভাবে চলছে। এখানে একটি উচ্চ-স্তরের ওভারভিউ রয়েছে।'; ?>
                        </p>
                    </div>
                </div>
                <div class="bg-white/50 backdrop-blur-md p-6 rounded-[2rem] border border-white/50 text-right min-w-[200px]">
                    <div class="text-2xl font-black text-gray-900 leading-none mb-1">
                        <?php echo date('l'); ?>
                    </div>
                    <div class="text-xs font-black uppercase tracking-widest text-primary mb-3">
                        <?php echo date('F j, Y'); ?>
                    </div>
                    <div class="text-lg font-bold text-gray-500">
                        <?php echo date('g:i A'); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notifications Section -->
        <?php if (!empty($notifications)): ?>
            <div class="admin-card p-6 mb-8">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-2xl font-bold text-gray-800">
                        <i class="fas fa-bell text-purple-600 mr-3"></i>
                        <?php echo $currentLang === 'en' ? 'Recent Notifications' : 'সাম্প্রতিক বিজ্ঞপ্তি'; ?>
                    </h2>
                    <span class="bg-red-500 text-white text-sm font-bold px-3 py-1 rounded-full">
                        <?php echo count($notifications); ?> <?php echo $currentLang === 'en' ? 'New' : 'নতুন'; ?>
                    </span>
                </div>
                <div class="space-y-4">
                    <?php foreach (array_slice($notifications, 0, 5) as $notification): ?>
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
                                   class="bg-blue-600 text-white px-3 py-1 rounded-lg text-sm hover:bg-blue-700 transition-colors">
                                    <i class="fas fa-check mr-1"></i>
                                    <?php echo $currentLang === 'en' ? 'Mark Read' : 'পঠিত চিহ্নিত করুন'; ?>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="mt-4 text-center">
                    <a href="notifications.php" class="text-blue-600 hover:text-blue-700 font-medium">
                        <?php echo $currentLang === 'en' ? 'View All Notifications' : 'সব বিজ্ঞপ্তি দেখুন'; ?>
                        <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <!-- Statistics Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-10">
            <div class="glass-card p-8 group border-b-4 border-indigo-500">
                <div class="w-14 h-14 bg-indigo-500/10 rounded-2xl flex items-center justify-center text-2xl text-indigo-600 mb-6 group-hover:scale-110 transition-transform">
                    <i class="fas fa-users"></i>
                </div>
                <div class="text-4xl font-black text-gray-900 mb-1"><?php echo number_format($totalCustomers); ?></div>
                <div class="text-xs font-black text-gray-400 uppercase tracking-[0.2em]"><?php echo $currentLang === 'en' ? 'Users' : 'ব্যবহারকারী'; ?></div>
            </div>

            <div class="glass-card p-8 group border-b-4 border-emerald-500">
                <div class="w-14 h-14 bg-emerald-500/10 rounded-2xl flex items-center justify-center text-2xl text-emerald-600 mb-6 group-hover:scale-110 transition-transform">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="text-4xl font-black text-gray-900 mb-1"><?php echo number_format($totalProviders); ?></div>
                <div class="text-xs font-black text-gray-400 uppercase tracking-[0.2em]"><?php echo $currentLang === 'en' ? 'Providers' : 'প্রদানকারী'; ?></div>
            </div>

            <div class="glass-card p-8 group border-b-4 border-amber-500">
                <div class="w-14 h-14 bg-amber-500/10 rounded-2xl flex items-center justify-center text-2xl text-amber-600 mb-6 group-hover:scale-110 transition-transform">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="text-4xl font-black text-gray-900 mb-1"><?php echo number_format($pendingProviders); ?></div>
                <div class="text-xs font-black text-gray-400 uppercase tracking-[0.2em]"><?php echo $currentLang === 'en' ? 'Pending' : 'অপেক্ষমাণ'; ?></div>
            </div>

            <div class="glass-card p-8 group border-b-4 border-purple-500">
                <div class="w-14 h-14 bg-purple-500/10 rounded-2xl flex items-center justify-center text-2xl text-purple-600 mb-6 group-hover:scale-110 transition-transform">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="text-4xl font-black text-gray-900 mb-1"><?php echo number_format($totalBookings); ?></div>
                <div class="text-xs font-black text-gray-400 uppercase tracking-[0.2em]"><?php echo $currentLang === 'en' ? 'Bookings' : 'বুকিং'; ?></div>
            </div>

            <div class="glass-card p-8 group border-b-4 border-rose-500">
                <div class="w-14 h-14 bg-rose-500/10 rounded-2xl flex items-center justify-center text-2xl text-rose-600 mb-6 group-hover:scale-110 transition-transform">
                    <i class="fas fa-credit-card"></i>
                </div>
                <div class="text-4xl font-black text-gray-900 mb-1"><?php echo number_format($pendingPayments); ?></div>
                <div class="text-xs font-black text-gray-400 uppercase tracking-[0.2em]"><?php echo $currentLang === 'en' ? 'Due Pay' : 'বকেয়া'; ?></div>
            </div>
        </div>

        <!-- Revenue Section -->
        <div class="glass-card p-8 mb-10 overflow-hidden relative">
            <div class="absolute top-0 right-0 w-64 h-64 bg-emerald-500/5 rounded-full -mr-32 -mt-32"></div>
            <div class="relative z-10 flex flex-col md:flex-row justify-between items-center gap-8">
                <div>
                    <h2 class="text-3xl font-black text-gray-900 mb-2">
                        <i class="fas fa-chart-line text-emerald-500 mr-3"></i>
                        <?php echo $currentLang === 'en' ? 'Portfolio Performance' : 'প্ল্যাটফর্ম রাজস্ব'; ?>
                    </h2>
                    <p class="text-gray-500 font-medium"><?php echo $currentLang === 'en' ? 'Overall platform financial health' : 'প্ল্যাটফর্মের সামগ্রিক আর্থিক অবস্থা'; ?></p>
                </div>
                <div class="flex gap-4">
                    <div class="bg-emerald-500/10 border border-emerald-500/20 p-6 rounded-[2rem] text-center min-w-[200px]">
                        <div class="text-3xl font-black text-emerald-600 leading-none mb-2">
                             ৳<?php echo number_format($platformRevenueTotal, 2); ?>
                        </div>
                        <div class="text-xs font-black uppercase tracking-widest text-emerald-600/70">
                            <?php echo $currentLang === 'en' ? 'Platform Fee' : 'প্ল্যাটফর্ম ফি'; ?>
                        </div>
                    </div>
                    <div class="bg-primary/10 border border-primary/20 p-6 rounded-[2rem] text-center min-w-[200px]">
                        <div class="text-3xl font-black text-primary leading-none mb-2">
                             ৳<?php echo number_format($verifiedGross, 2); ?>
                        </div>
                        <div class="text-xs font-black uppercase tracking-widest text-primary/70">
                            <?php echo $currentLang === 'en' ? 'Gross Volume' : 'মোট লেনদেন'; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Recent Pending Providers -->
            <div class="table-container">
                <div class="table-header">
                    <i class="fas fa-clock mr-2"></i>
                    <?php echo $currentLang === 'en' ? 'Recent Pending Providers' : 'সাম্প্রতিক অপেক্ষমাণ প্রদানকারী'; ?>
                </div>
                <div class="p-6">
                    <?php if (empty($recentPendingProviders)): ?>
                        <div class="text-center py-8">
                            <i class="fas fa-check-circle text-4xl text-green-500 mb-4"></i>
                            <p class="text-gray-600">
                                <?php echo $currentLang === 'en' ? 'No pending providers' : 'কোন অপেক্ষমাণ প্রদানকারী নেই'; ?>
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($recentPendingProviders as $provider): ?>
                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-pink-500 rounded-full flex items-center justify-center text-white font-semibold">
                                            <?php echo strtoupper(substr($provider['name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <div class="font-semibold text-gray-800"><?php echo htmlspecialchars($provider['name']); ?></div>
                                            <div class="text-sm text-gray-600">
                                                <?php echo $currentLang === 'en' ? $provider['category_name'] : $provider['category_name_bn']; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <span class="status-badge status-pending">
                                            <?php echo $currentLang === 'en' ? 'Pending' : 'অপেক্ষমাণ'; ?>
                                        </span>
                                        <a href="providers.php?id=<?php echo $provider['id']; ?>" class="text-blue-600 hover:text-blue-700">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-4 text-center">
                            <a href="providers.php?status=pending" class="text-blue-600 hover:text-blue-700 font-medium">
                                <?php echo $currentLang === 'en' ? 'View All Pending' : 'সব অপেক্ষমাণ দেখুন'; ?>
                                <i class="fas fa-arrow-right ml-1"></i>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Bookings -->
            <div class="table-container">
                <div class="table-header">
                    <i class="fas fa-calendar-alt mr-2"></i>
                    <?php echo $currentLang === 'en' ? 'Recent Bookings' : 'সাম্প্রতিক বুকিং'; ?>
                </div>
                <div class="p-6">
                    <?php if (empty($recentBookings)): ?>
                        <div class="text-center py-8">
                            <i class="fas fa-calendar-times text-4xl text-gray-400 mb-4"></i>
                            <p class="text-gray-600">
                                <?php echo $currentLang === 'en' ? 'No recent bookings' : 'কোন সাম্প্রতিক বুকিং নেই'; ?>
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($recentBookings as $booking): ?>
                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-full flex items-center justify-center text-white font-semibold">
                                            <?php echo strtoupper(substr($booking['customer_name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <div class="font-semibold text-gray-800"><?php echo htmlspecialchars($booking['customer_name']); ?></div>
                                            <div class="text-sm text-gray-600">
                                                <?php echo htmlspecialchars($booking['provider_name']); ?> - 
                                                <?php echo $currentLang === 'en' ? $booking['category_name'] : $booking['category_name_bn']; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <span class="status-badge status-<?php echo $booking['status']; ?>">
                                            <?php echo ucfirst($booking['status']); ?>
                                        </span>
                                        <a href="bookings.php?id=<?php echo $booking['id']; ?>" class="text-blue-600 hover:text-blue-700">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-4 text-center">
                            <a href="bookings.php" class="text-blue-600 hover:text-blue-700 font-medium">
                                <?php echo $currentLang === 'en' ? 'View All Bookings' : 'সব বুকিং দেখুন'; ?>
                                <i class="fas fa-arrow-right ml-1"></i>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/ui.js"></script>
    <script>
        // Add smooth scroll behavior
        document.documentElement.style.scrollBehavior = 'smooth';
        
        // Add intersection observer for animation
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);
        
        // Observe all cards
        document.querySelectorAll('.glass-card').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(card);
        });
    </script>
</body>
</html>