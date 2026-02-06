<?php
require_once '../includes/functions.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    redirect('login.php');
}

$currentLang = getLanguage();
$admin = getCurrentAdmin();

// Handle actions
if (isset($_POST['action']) && isset($_POST['review_id'])) {
    $reviewId = (int)$_POST['review_id'];
    $action = $_POST['action'];
    
    if ($action === 'delete') {
        executeQuery("DELETE FROM reviews WHERE id = ?", [$reviewId]);
        setFlashMessage('success', 'Review deleted successfully');
    }
    
    redirect('reviews.php');
}

// Get filters
$rating = $_GET['rating'] ?? '';
$search = $_GET['search'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query
$whereConditions = [];
$params = [];

if ($rating) {
    $whereConditions[] = "r.rating = ?";
    $params[] = $rating;
}

if ($search) {
    $whereConditions[] = "(u.name LIKE ? OR sp.name LIKE ? OR r.review_text LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($date_from) {
    $whereConditions[] = "r.created_at >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $whereConditions[] = "r.created_at <= ?";
    $params[] = $date_to;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get reviews
$reviews = fetchAll("
    SELECT r.*, u.name as customer_name, u.phone as customer_phone, 
           sp.name as provider_name, sp.phone as provider_phone,
           b.service_type, b.booking_date
    FROM reviews r
    JOIN users u ON r.customer_id = u.id
    JOIN service_providers sp ON r.provider_id = sp.id
    LEFT JOIN bookings b ON r.booking_id = b.id
    $whereClause
    ORDER BY r.created_at DESC
", $params);

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
    <title><?php echo $currentLang === 'en' ? 'Manage Reviews' : 'পর্যালোচনা পরিচালনা'; ?> - Home Service Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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
                        <?php 
                        $unreadAdminNotifs = getUnreadNotifications($admin['id'], 'admin');
                        if (count($unreadAdminNotifs) > 0): 
                        ?>
                            <span class="absolute -top-1 -right-1 bg-rose-500 text-white text-[10px] rounded-full h-4 w-4 flex items-center justify-center border-2 border-white">
                                <?php echo count($unreadAdminNotifs); ?>
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
        <!-- Page Header -->
        <div class="glass-card p-8 mb-8">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-4xl font-bold bg-gradient-to-r from-purple-600 to-blue-600 bg-clip-text text-transparent mb-2">
                        <i class="fas fa-star mr-3"></i><?php echo $currentLang === 'en' ? 'Manage Reviews' : 'পর্যালোচনা পরিচালনা'; ?>
                    </h1>
                    <p class="text-gray-600 text-lg">
                        <i class="fas fa-chart-line mr-2"></i><?php echo count($reviews); ?> <?php echo $currentLang === 'en' ? 'total reviews' : 'মোট পর্যালোচনা'; ?>
                    </p>
                </div>
                <div class="text-right">
                    <div class="text-3xl font-bold text-purple-600">
                        <i class="fas fa-star"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Flash Messages -->
        <?php $flash = getFlashMessage(); if ($flash): ?>
            <div class="glass-card p-6 mb-8">
                <div class="flex items-center <?php echo $flash['type'] === 'success' ? 'text-green-700' : 'text-red-700'; ?>">
                    <i class="fas <?php echo $flash['type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mr-3 text-xl"></i>
                    <span class="font-medium"><?php echo htmlspecialchars($flash['message']); ?></span>
                </div>
            </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="glass-card p-6 mb-8">
            <h3 class="text-xl font-bold text-gray-800 mb-6">
                <i class="fas fa-filter mr-2 text-purple-600"></i><?php echo $currentLang === 'en' ? 'Filter Reviews' : 'পর্যালোচনা ফিল্টার'; ?>
            </h3>
            <form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-search mr-1"></i><?php echo $currentLang === 'en' ? 'Search' : 'অনুসন্ধান'; ?>
                    </label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="<?php echo $currentLang === 'en' ? 'Customer, provider, review...' : 'গ্রাহক, প্রদানকারী, পর্যালোচনা...'; ?>"
                           class="w-full px-4 py-2 rounded-xl bg-gray-50 border border-gray-200 focus:outline-none focus:ring-2 focus:ring-primary/50 transition-all">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-star mr-1"></i><?php echo $currentLang === 'en' ? 'Rating' : 'রেটিং'; ?>
                    </label>
                    <select name="rating" class="w-full px-4 py-2 rounded-xl bg-gray-50 border border-gray-200 focus:outline-none focus:ring-2 focus:ring-primary/50 transition-all">
                        <option value=""><?php echo $currentLang === 'en' ? 'All Ratings' : 'সব রেটিং'; ?></option>
                        <option value="5" <?php echo $rating === '5' ? 'selected' : ''; ?>>5 <?php echo $currentLang === 'en' ? 'Stars' : 'তারকা'; ?></option>
                        <option value="4" <?php echo $rating === '4' ? 'selected' : ''; ?>>4 <?php echo $currentLang === 'en' ? 'Stars' : 'তারকা'; ?></option>
                        <option value="3" <?php echo $rating === '3' ? 'selected' : ''; ?>>3 <?php echo $currentLang === 'en' ? 'Stars' : 'তারকা'; ?></option>
                        <option value="2" <?php echo $rating === '2' ? 'selected' : ''; ?>>2 <?php echo $currentLang === 'en' ? 'Stars' : 'তারকা'; ?></option>
                        <option value="1" <?php echo $rating === '1' ? 'selected' : ''; ?>>1 <?php echo $currentLang === 'en' ? 'Star' : 'তারকা'; ?></option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-calendar mr-1"></i><?php echo $currentLang === 'en' ? 'From Date' : 'শুরুর তারিখ'; ?>
                    </label>
                    <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>"
                           class="w-full px-4 py-2 rounded-xl bg-gray-50 border border-gray-200 focus:outline-none focus:ring-2 focus:ring-primary/50 transition-all">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-calendar mr-1"></i><?php echo $currentLang === 'en' ? 'To Date' : 'শেষের তারিখ'; ?>
                    </label>
                    <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>"
                           class="w-full px-4 py-2 rounded-xl bg-gray-50 border border-gray-200 focus:outline-none focus:ring-2 focus:ring-primary/50 transition-all">
                </div>
                
                <div class="flex items-end">
                    <button type="submit" class="btn-primary w-full py-2.5">
                        <i class="fas fa-search mr-2"></i><?php echo $currentLang === 'en' ? 'Filter' : 'ফিল্টার'; ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- Reviews List -->
        <div class="glass-card overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-2xl font-bold text-gray-800">
                    <i class="fas fa-list mr-3 text-purple-600"></i><?php echo $currentLang === 'en' ? 'All Reviews' : 'সব পর্যালোচনা'; ?>
                </h3>
            </div>
            
            <?php if (empty($reviews)): ?>
                <div class="p-12 text-center">
                    <i class="fas fa-star text-6xl text-gray-300 mb-6"></i>
                    <p class="text-gray-500 text-lg">
                        <?php echo $currentLang === 'en' ? 'No reviews found' : 'কোনো পর্যালোচনা পাওয়া যায়নি'; ?>
                    </p>
                </div>
            <?php else: ?>
                <div class="p-6 space-y-6">
                    <?php foreach ($reviews as $review): ?>
                        <div class="glass-card p-6 bg-white/50 border border-white/60 hover:border-primary/30 transition-all">
                            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                                <!-- Rating -->
                                <div class="flex items-center">
                                    <div class="text-2xl mr-3 text-amber-400">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'text-amber-400' : 'text-gray-300'; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <span class="text-lg font-bold text-gray-800"><?php echo $review['rating']; ?>/5</span>
                                </div>
                                
                                <!-- Customer Info -->
                                <div>
                                    <h4 class="font-bold text-gray-900 mb-2 text-lg">
                                        <i class="fas fa-user mr-2 text-blue-600"></i><?php echo htmlspecialchars($review['customer_name']); ?>
                                    </h4>
                                    <p class="text-sm font-medium text-gray-600">
                                        <i class="fas fa-phone mr-2"></i><?php echo htmlspecialchars($review['customer_phone']); ?>
                                    </p>
                                </div>
                                
                                <!-- Provider Info -->
                                <div>
                                    <h4 class="font-bold text-gray-900 mb-2 text-lg">
                                        <i class="fas fa-user-check mr-2 text-emerald-600"></i><?php echo htmlspecialchars($review['provider_name']); ?>
                                    </h4>
                                    <p class="text-sm font-medium text-gray-600">
                                        <i class="fas fa-phone mr-2"></i><?php echo htmlspecialchars($review['provider_phone']); ?>
                                    </p>
                                </div>
                                
                                <!-- Review Details -->
                                <div>
                                    <p class="text-sm font-medium text-gray-600 mb-2">
                                        <i class="fas fa-calendar mr-2"></i><?php echo formatDate($review['created_at']); ?>
                                    </p>
                                    <?php if (isset($review['service_type']) && $review['service_type']): ?>
                                        <p class="text-sm font-medium text-gray-600">
                                            <i class="fas fa-tools mr-2"></i><?php echo htmlspecialchars($review['service_type']); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Review Text -->
                            <div class="mt-4 pt-4 border-t border-gray-100">
                                <p class="text-gray-700 text-lg italic leading-relaxed">
                                    <i class="fas fa-quote-left mr-2 text-primary/40"></i><?php echo htmlspecialchars($review['review_text']); ?>
                                </p>
                            </div>
                            
                            <!-- Actions -->
                            <div class="mt-4 flex justify-end">
                                <form method="POST" onsubmit="return confirm('<?php echo $currentLang === 'en' ? 'Delete this review?' : 'এই পর্যালোচনা মুছে ফেলবেন?'; ?>')">
                                    <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <button type="submit" class="flex items-center px-4 py-2 bg-white border border-rose-200 text-rose-500 rounded-xl shadow-sm hover:bg-rose-50 hover:border-rose-300 hover:-translate-y-1 transition-all font-black text-xs uppercase tracking-wider">
                                        <i class="fas fa-trash mr-2"></i><?php echo $currentLang === 'en' ? 'Delete' : 'মুছুন'; ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="glass-nav py-8 mt-12 bg-white/50 border-t border-gray-100">
        <div class="container mx-auto px-4 text-center">
            <p class="text-gray-500 font-bold text-sm uppercase tracking-widest">&copy; <?php echo date('Y'); ?> Home Service. <?php echo $currentLang === 'en' ? 'All rights reserved.' : 'সর্বস্বত্ব সংরক্ষিত।'; ?></p>
        </div>
    </footer>
</body>
</html> 