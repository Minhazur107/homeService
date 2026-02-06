<?php
require_once '../includes/functions.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    redirect('login.php');
}

$currentLang = getLanguage();
$admin = getCurrentAdmin();

// Handle actions
if (isset($_POST['action']) && isset($_POST['provider_id'])) {
    $providerId = (int)$_POST['provider_id'];
    $action = $_POST['action'];
    
    if ($action === 'approve') {
        executeQuery("UPDATE service_providers SET verification_status = 'verified' WHERE id = ?", [$providerId]);
        setFlashMessage('success', 'Provider approved successfully');
    } elseif ($action === 'reject') {
        executeQuery("UPDATE service_providers SET verification_status = 'rejected' WHERE id = ?", [$providerId]);
        setFlashMessage('success', 'Provider rejected successfully');
    } elseif ($action === 'toggle_active') {
        executeQuery("UPDATE service_providers SET is_active = NOT is_active WHERE id = ?", [$providerId]);
        setFlashMessage('success', 'Provider status updated successfully');
    } elseif ($action === 'delete') {
        // Check if provider has any bookings before deleting
        $hasBookings = fetchOne("SELECT COUNT(*) as count FROM bookings WHERE provider_id = ?", [$providerId])['count'];
        if ($hasBookings > 0) {
            setFlashMessage('error', 'Cannot delete provider with existing bookings');
        } else {
            executeQuery("DELETE FROM service_providers WHERE id = ?", [$providerId]);
            setFlashMessage('success', 'Provider deleted successfully');
        }
    }
    
    redirect('providers.php');
}

// Handle add/edit provider
if (isset($_POST['save_provider'])) {
    $providerId = $_POST['provider_id'] ?? null;
    $name = sanitizeInput($_POST['name']);
    $phone = sanitizeInput($_POST['phone']);
    $email = sanitizeInput($_POST['email']);
    $categoryId = (int)$_POST['category_id'];
    $description = sanitizeInput($_POST['description']);
    $serviceAreas = sanitizeInput($_POST['service_areas']);
    $priceMin = $_POST['price_min'] ? (float)$_POST['price_min'] : null;
    $priceMax = $_POST['price_max'] ? (float)$_POST['price_max'] : null;
    $hourlyRate = $_POST['hourly_rate'] ? (float)$_POST['hourly_rate'] : null;
    $availabilityHours = sanitizeInput($_POST['availability_hours']);
    $verificationStatus = $_POST['verification_status'];
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    if (empty($name) || empty($phone) || empty($categoryId)) {
        setFlashMessage('error', 'Name, phone, and category are required');
    } else {
        if ($providerId) {
            // Update existing provider
            executeQuery("UPDATE service_providers SET name = ?, phone = ?, email = ?, category_id = ?, description = ?, service_areas = ?, price_min = ?, price_max = ?, hourly_rate = ?, availability_hours = ?, verification_status = ?, is_active = ? WHERE id = ?", 
                       [$name, $phone, $email, $categoryId, $description, $serviceAreas, $priceMin, $priceMax, $hourlyRate, $availabilityHours, $verificationStatus, $isActive, $providerId]);
            setFlashMessage('success', 'Provider updated successfully');
        } else {
            // Add new provider
            executeQuery("INSERT INTO service_providers (name, phone, email, category_id, description, service_areas, price_min, price_max, hourly_rate, availability_hours, verification_status, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", 
                       [$name, $phone, $email, $categoryId, $description, $serviceAreas, $priceMin, $priceMax, $hourlyRate, $availabilityHours, $verificationStatus, $isActive]);
            setFlashMessage('success', 'Provider added successfully');
        }
        redirect('providers.php');
    }
}

// Get filters
$status = $_GET['status'] ?? '';
$category = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';
$verification = $_GET['verification'] ?? '';

// Build query
$whereConditions = [];
$params = [];

if ($status) {
    $whereConditions[] = "sp.is_active = ?";
    $params[] = $status === 'active' ? 1 : 0;
}

if ($category) {
    $whereConditions[] = "sp.category_id = ?";
    $params[] = $category;
}

if ($verification) {
    $whereConditions[] = "sp.verification_status = ?";
    $params[] = $verification;
}

if ($search) {
    $whereConditions[] = "(sp.name LIKE ? OR sp.phone LIKE ? OR sp.email LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get providers
$providers = fetchAll("
    SELECT sp.*, sc.name as category_name, sc.name_bn as category_name_bn,
           (SELECT COUNT(*) FROM bookings WHERE provider_id = sp.id) as total_bookings,
           (SELECT COUNT(*) FROM reviews WHERE provider_id = sp.id AND status = 'approved') as total_reviews
    FROM service_providers sp
    LEFT JOIN service_categories sc ON sp.category_id = sc.id
    $whereClause
    ORDER BY sp.created_at DESC
", $params);

// Get categories for filter
$categories = fetchAll("SELECT id, name, name_bn FROM service_categories WHERE is_active = 1");

// Handle logout
if (isset($_GET['logout'])) {
    logout();
    redirect('../index.php');
}

// Get provider for editing
$editProvider = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $editProvider = fetchOne("SELECT * FROM service_providers WHERE id = ?", [$_GET['edit']]);
}
?>
<!DOCTYPE html>
<html lang="<?php echo $currentLang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $currentLang === 'en' ? 'Manage Providers' : 'প্রদানকারী পরিচালনা'; ?> - Home Service Admin</title>
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
        <div class="glass-card p-6 mb-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800 mb-2">
                        <i class="fas fa-user-check text-purple-600 mr-3"></i>
                        <?php echo $currentLang === 'en' ? 'Manage Service Providers' : 'সেবা প্রদানকারী পরিচালনা'; ?>
                    </h1>
                    <p class="text-gray-600">
                        <?php echo $currentLang === 'en' ? 'Total providers and pending verification management' : 'মোট প্রদানকারী এবং অপেক্ষমান যাচাইকরণ পরিচালনা'; ?>
                    </p>
                </div>
                <div class="text-right">
                    <div class="text-3xl font-bold text-purple-600"><?php echo count($providers); ?></div>
                    <div class="text-gray-600">
                        <?php echo $currentLang === 'en' ? 'Total Providers' : 'মোট প্রদানকারী'; ?>
                    </div>
                </div>
            </div>
            <div class="flex justify-end mt-4">
                <button onclick="openAddProviderModal()" class="btn-primary">
                    <i class="fas fa-plus mr-2"></i><?php echo $currentLang === 'en' ? 'Add Provider' : 'প্রদানকারী যোগ করুন'; ?>
                </button>
            </div>
        </div>

        <!-- Flash Messages -->
        <?php $flash = getFlashMessage(); if ($flash): ?>
            <div class="glass-card p-4 mb-6 <?php echo $flash['type'] === 'success' ? 'border-l-4 border-green-500' : 'border-l-4 border-red-500'; ?>">
                <div class="flex items-center">
                    <i class="fas <?php echo $flash['type'] === 'success' ? 'fa-check-circle text-green-500' : 'fa-exclamation-circle text-red-500'; ?> mr-3 text-xl"></i>
                    <span class="<?php echo $flash['type'] === 'success' ? 'text-green-700' : 'text-red-700'; ?> font-medium">
                        <?php echo htmlspecialchars($flash['message']); ?>
                    </span>
                </div>
            </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="glass-card p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">
                <i class="fas fa-filter text-purple-600 mr-2"></i>
                <?php echo $currentLang === 'en' ? 'Filter Providers' : 'প্রদানকারী ফিল্টার করুন'; ?>
            </h3>
            <form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <?php echo $currentLang === 'en' ? 'Search' : 'অনুসন্ধান'; ?>
                    </label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="<?php echo $currentLang === 'en' ? 'Name, phone, email...' : 'নাম, ফোন, ইমেইল...'; ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <?php echo $currentLang === 'en' ? 'Category' : 'বিভাগ'; ?>
                    </label>
                    <select name="category" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        <option value=""><?php echo $currentLang === 'en' ? 'All Categories' : 'সব বিভাগ'; ?></option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo $currentLang === 'en' ? $cat['name'] : $cat['name_bn']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <?php echo $currentLang === 'en' ? 'Status' : 'অবস্থা'; ?>
                    </label>
                    <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        <option value=""><?php echo $currentLang === 'en' ? 'All Status' : 'সব অবস্থা'; ?></option>
                        <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>
                            <?php echo $currentLang === 'en' ? 'Active' : 'সক্রিয়'; ?>
                        </option>
                        <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>
                            <?php echo $currentLang === 'en' ? 'Inactive' : 'নিষ্ক্রিয়'; ?>
                        </option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <?php echo $currentLang === 'en' ? 'Verification' : 'যাচাইকরণ'; ?>
                    </label>
                    <select name="verification" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        <option value=""><?php echo $currentLang === 'en' ? 'All' : 'সব'; ?></option>
                        <option value="pending" <?php echo $verification === 'pending' ? 'selected' : ''; ?>>
                            <?php echo $currentLang === 'en' ? 'Pending' : 'অপেক্ষমান'; ?>
                        </option>
                        <option value="verified" <?php echo $verification === 'verified' ? 'selected' : ''; ?>>
                            <?php echo $currentLang === 'en' ? 'Verified' : 'যাচাইকৃত'; ?>
                        </option>
                        <option value="rejected" <?php echo $verification === 'rejected' ? 'selected' : ''; ?>>
                            <?php echo $currentLang === 'en' ? 'Rejected' : 'প্রত্যাখ্যান'; ?>
                        </option>
                    </select>
                </div>
                
                <div class="flex items-end">
                    <button type="submit" class="btn-primary w-full">
                        <i class="fas fa-search mr-2"></i><?php echo $currentLang === 'en' ? 'Filter' : 'ফিল্টার'; ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- Providers Table -->
        <div class="glass-card overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800">
                    <i class="fas fa-list text-purple-600 mr-2"></i>
                    <?php echo $currentLang === 'en' ? 'Service Providers List' : 'সেবা প্রদানকারীদের তালিকা'; ?>
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gradient-to-r from-purple-50 to-pink-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                <i class="fas fa-user mr-2"></i><?php echo $currentLang === 'en' ? 'Provider' : 'প্রদানকারী'; ?>
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                <i class="fas fa-tag mr-2"></i><?php echo $currentLang === 'en' ? 'Category' : 'বিভাগ'; ?>
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                <i class="fas fa-phone mr-2"></i><?php echo $currentLang === 'en' ? 'Contact' : 'যোগাযোগ'; ?>
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                <i class="fas fa-chart-bar mr-2"></i><?php echo $currentLang === 'en' ? 'Stats' : 'পরিসংখ্যান'; ?>
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                <i class="fas fa-info-circle mr-2"></i><?php echo $currentLang === 'en' ? 'Status' : 'অবস্থা'; ?>
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                <i class="fas fa-cogs mr-2"></i><?php echo $currentLang === 'en' ? 'Actions' : 'কর্ম'; ?>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($providers)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                    <div class="w-24 h-24 bg-gradient-to-br from-purple-100 to-pink-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                        <i class="fas fa-users text-4xl text-purple-500"></i>
                                    </div>
                                    <h3 class="text-lg font-semibold text-gray-700 mb-2">
                                        <?php echo $currentLang === 'en' ? 'No Providers Found' : 'কোনো প্রদানকারী পাওয়া যায়নি'; ?>
                                    </h3>
                                    <p class="text-gray-500">
                                        <?php echo $currentLang === 'en' ? 'Try adjusting your filters or add a new provider.' : 'আপনার ফিল্টারগুলি সামঞ্জস্য করুন বা নতুন প্রদানকারী যোগ করুন।'; ?>
                                    </p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($providers as $provider): ?>
                                <tr class="hover:bg-gradient-to-r hover:from-purple-50 hover:to-pink-50 transition-all duration-300">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-12 w-12">
                                                <?php if ($provider['profile_picture']): ?>
                                                    <img class="h-12 w-12 rounded-full object-cover border-2 border-purple-200" 
                                                         src="../uploads/<?php echo htmlspecialchars($provider['profile_picture']); ?>" 
                                                         alt="<?php echo htmlspecialchars($provider['name']); ?>">
                                                <?php else: ?>
                                                    <div class="h-12 w-12 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center">
                                                        <i class="fas fa-user text-white text-xl"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-semibold text-gray-900">
                                                    <?php echo htmlspecialchars($provider['name']); ?>
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    <i class="fas fa-id-card mr-1"></i><?php echo $currentLang === 'en' ? 'ID' : 'আইডি'; ?>: <?php echo $provider['id']; ?>
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    <i class="fas fa-calendar-plus mr-1"></i><?php echo $currentLang === 'en' ? 'Joined' : 'যোগদান'; ?>: <?php echo formatDate($provider['created_at']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-purple-100 text-purple-800">
                                            <i class="fas fa-tag mr-1"></i>
                                            <?php echo $currentLang === 'en' ? $provider['category_name'] : $provider['category_name_bn']; ?>
                                        </span>
                                    </td>
                                    
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <div class="space-y-1">
                                            <div class="flex items-center">
                                                <i class="fas fa-phone text-purple-500 mr-2"></i>
                                                <span><?php echo htmlspecialchars($provider['phone']); ?></span>
                                            </div>
                                            <?php if ($provider['email']): ?>
                                                <div class="flex items-center">
                                                    <i class="fas fa-envelope text-purple-500 mr-2"></i>
                                                    <span><?php echo htmlspecialchars($provider['email']); ?></span>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($provider['service_areas']): ?>
                                                <div class="flex items-center">
                                                    <i class="fas fa-map-marker-alt text-purple-500 mr-2"></i>
                                                    <span><?php echo htmlspecialchars($provider['service_areas']); ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <div class="space-y-2">
                                            <div class="flex items-center">
                                                <i class="fas fa-calendar text-blue-500 mr-2"></i>
                                                <span class="font-medium"><?php echo $provider['total_bookings']; ?></span>
                                                <span class="ml-1"><?php echo $currentLang === 'en' ? 'bookings' : 'বুকিং'; ?></span>
                                            </div>
                                            <div class="flex items-center">
                                                <i class="fas fa-star text-yellow-500 mr-2"></i>
                                                <span class="font-medium"><?php echo $provider['total_reviews']; ?></span>
                                                <span class="ml-1"><?php echo $currentLang === 'en' ? 'reviews' : 'পর্যালোচনা'; ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="space-y-2">
                                            <span class="status-badge status-<?php echo $provider['verification_status']; ?>">
                                                <?php echo t($provider['verification_status']); ?>
                                            </span>
                                            <span class="status-badge status-<?php echo $provider['is_active'] ? 'active' : 'inactive'; ?>">
                                                <?php echo $provider['is_active'] ? ($currentLang === 'en' ? 'Active' : 'সক্রিয়') : ($currentLang === 'en' ? 'Inactive' : 'নিষ্ক্রিয়'); ?>
                                            </span>
                                        </div>
                                    </td>
                                    
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <a href="view_provider.php?id=<?php echo $provider['id']; ?>" 
                                               class="btn-success" title="<?php echo $currentLang === 'en' ? 'View Details' : 'বিস্তারিত দেখুন'; ?>">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            
                                            <a href="?edit=<?php echo $provider['id']; ?>" 
                                               class="btn-warning" title="<?php echo $currentLang === 'en' ? 'Edit Provider' : 'প্রদানকারী সম্পাদনা করুন'; ?>">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            
                                            <?php if ($provider['verification_status'] === 'pending'): ?>
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="provider_id" value="<?php echo $provider['id']; ?>">
                                                    <input type="hidden" name="action" value="approve">
                                                    <button type="submit" class="btn-success" 
                                                            onclick="return confirm('<?php echo $currentLang === 'en' ? 'Approve this provider?' : 'এই প্রদানকারীকে অনুমোদন করবেন?'; ?>')"
                                                            title="<?php echo $currentLang === 'en' ? 'Approve Provider' : 'প্রদানকারী অনুমোদন করুন'; ?>">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                                
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="provider_id" value="<?php echo $provider['id']; ?>">
                                                    <input type="hidden" name="action" value="reject">
                                                    <button type="submit" class="btn-danger"
                                                            onclick="return confirm('<?php echo $currentLang === 'en' ? 'Reject this provider?' : 'এই প্রদানকারীকে প্রত্যাখ্যান করবেন?'; ?>')"
                                                            title="<?php echo $currentLang === 'en' ? 'Reject Provider' : 'প্রদানকারী প্রত্যাখ্যান করুন'; ?>">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="provider_id" value="<?php echo $provider['id']; ?>">
                                                <input type="hidden" name="action" value="toggle_active">
                                                <button type="submit" class="<?php echo $provider['is_active'] ? 'btn-danger' : 'btn-success'; ?>"
                                                        onclick="return confirm('<?php echo $currentLang === 'en' ? 'Change provider status?' : 'প্রদানকারীর অবস্থা পরিবর্তন করবেন?'; ?>')"
                                                        title="<?php echo $currentLang === 'en' ? 'Toggle Status' : 'অবস্থা পরিবর্তন করুন'; ?>">
                                                    <i class="fas fa-<?php echo $provider['is_active'] ? 'ban' : 'check-circle'; ?>"></i>
                                                </button>
                                            </form>
                                            
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="provider_id" value="<?php echo $provider['id']; ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <button type="submit" class="btn-danger"
                                                        onclick="return confirm('<?php echo $currentLang === 'en' ? 'Delete this provider? This action cannot be undone.' : 'এই প্রদানকারী মুছে ফেলবেন? এই কর্মটি অপরিবর্তনীয়।'; ?>')"
                                                        title="<?php echo $currentLang === 'en' ? 'Delete Provider' : 'প্রদানকারী মুছুন'; ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-white bg-opacity-95 backdrop-blur-sm border-t border-gray-200 py-8 mt-12 relative z-10">
        <div class="container mx-auto px-4 text-center">
            <p class="text-gray-600">&copy; <?php echo date('Y'); ?> Home Service. <?php echo $currentLang === 'en' ? 'All rights reserved.' : 'সর্বস্বত্ব সংরক্ষিত।'; ?></p>
        </div>
    </footer>

    <!-- Add/Edit Provider Modal -->
    <div id="providerModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    <?php echo $editProvider ? ($currentLang === 'en' ? 'Edit Provider' : 'প্রদানকারী সম্পাদনা করুন') : ($currentLang === 'en' ? 'Add New Provider' : 'নতুন প্রদানকারী যোগ করুন'); ?>
                </h3>
                
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="provider_id" value="<?php echo $editProvider ? $editProvider['id'] : ''; ?>">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <?php echo $currentLang === 'en' ? 'Name' : 'নাম'; ?> *
                            </label>
                            <input type="text" name="name" value="<?php echo $editProvider ? htmlspecialchars($editProvider['name']) : ''; ?>" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <?php echo $currentLang === 'en' ? 'Phone' : 'ফোন'; ?> *
                            </label>
                            <input type="text" name="phone" value="<?php echo $editProvider ? htmlspecialchars($editProvider['phone']) : ''; ?>" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <?php echo $currentLang === 'en' ? 'Email' : 'ইমেইল'; ?>
                            </label>
                            <input type="email" name="email" value="<?php echo $editProvider ? htmlspecialchars($editProvider['email']) : ''; ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <?php echo $currentLang === 'en' ? 'Category' : 'বিভাগ'; ?> *
                            </label>
                            <select name="category_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                <option value=""><?php echo $currentLang === 'en' ? 'Select Category' : 'বিভাগ নির্বাচন করুন'; ?></option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo ($editProvider && $editProvider['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                        <?php echo $currentLang === 'en' ? $cat['name'] : $cat['name_bn']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <?php echo $currentLang === 'en' ? 'Description' : 'বিবরণ'; ?>
                        </label>
                        <textarea name="description" rows="3" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"><?php echo $editProvider ? htmlspecialchars($editProvider['description']) : ''; ?></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <?php echo $currentLang === 'en' ? 'Service Areas' : 'সেবা এলাকা'; ?>
                        </label>
                        <input type="text" name="service_areas" value="<?php echo $editProvider ? htmlspecialchars($editProvider['service_areas']) : ''; ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <?php echo $currentLang === 'en' ? 'Min Price' : 'ন্যূনতম মূল্য'; ?>
                            </label>
                            <input type="number" step="0.01" name="price_min" value="<?php echo $editProvider ? $editProvider['price_min'] : ''; ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <?php echo $currentLang === 'en' ? 'Max Price' : 'সর্বোচ্চ মূল্য'; ?>
                            </label>
                            <input type="number" step="0.01" name="price_max" value="<?php echo $editProvider ? $editProvider['price_max'] : ''; ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <?php echo $currentLang === 'en' ? 'Hourly Rate' : 'ঘণ্টার হার'; ?>
                            </label>
                            <input type="number" step="0.01" name="hourly_rate" value="<?php echo $editProvider ? $editProvider['hourly_rate'] : ''; ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <?php echo $currentLang === 'en' ? 'Availability Hours' : 'উপলব্ধতার সময়'; ?>
                        </label>
                        <input type="text" name="availability_hours" value="<?php echo $editProvider ? htmlspecialchars($editProvider['availability_hours']) : ''; ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <?php echo $currentLang === 'en' ? 'Verification Status' : 'যাচাইকরণের অবস্থা'; ?>
                            </label>
                            <select name="verification_status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                <option value="pending" <?php echo ($editProvider && $editProvider['verification_status'] === 'pending') ? 'selected' : ''; ?>>
                                    <?php echo $currentLang === 'en' ? 'Pending' : 'অপেক্ষমান'; ?>
                                </option>
                                <option value="verified" <?php echo ($editProvider && $editProvider['verification_status'] === 'verified') ? 'selected' : ''; ?>>
                                    <?php echo $currentLang === 'en' ? 'Verified' : 'যাচাইকৃত'; ?>
                                </option>
                                <option value="rejected" <?php echo ($editProvider && $editProvider['verification_status'] === 'rejected') ? 'selected' : ''; ?>>
                                    <?php echo $currentLang === 'en' ? 'Rejected' : 'প্রত্যাখ্যান'; ?>
                                </option>
                            </select>
                        </div>
                        
                        <div class="flex items-center">
                            <input type="checkbox" name="is_active" id="is_active" value="1" 
                                   <?php echo ($editProvider && $editProvider['is_active']) ? 'checked' : ''; ?>
                                   class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded">
                            <label for="is_active" class="ml-2 block text-sm text-gray-900">
                                <?php echo $currentLang === 'en' ? 'Active' : 'সক্রিয়'; ?>
                            </label>
                        </div>
                    </div>
                    
                    <div class="flex space-x-3 pt-4">
                        <button type="submit" name="save_provider" class="flex-1 bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700">
                            <?php echo $currentLang === 'en' ? 'Save' : 'সংরক্ষণ করুন'; ?>
                        </button>
                        <button type="button" onclick="closeProviderModal()" class="flex-1 bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400">
                            <?php echo $currentLang === 'en' ? 'Cancel' : 'বাতিল'; ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-8 mt-12">
        <div class="container mx-auto px-4 text-center">
            <p>&copy; <?php echo date('Y'); ?> Home Service. <?php echo $currentLang === 'en' ? 'All rights reserved.' : 'সর্বস্বত্ব সংরক্ষিত।'; ?></p>
        </div>
    </footer>

    <script>
        function openAddProviderModal() {
            document.getElementById('providerModal').classList.remove('hidden');
        }
        
        function closeProviderModal() {
            document.getElementById('providerModal').classList.add('hidden');
        }
        
        // Close modal if clicking outside
        document.getElementById('providerModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeProviderModal();
            }
        });
        
        // Auto-open modal if editing
        <?php if ($editProvider): ?>
        document.addEventListener('DOMContentLoaded', function() {
            openAddProviderModal();
        });
        <?php endif; ?>
    </script>
</body>
</html> 