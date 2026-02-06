<?php
require_once '../includes/functions.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    redirect('login.php');
}

$currentLang = getLanguage();
$admin = getCurrentAdmin();

// Handle actions
if (isset($_POST['action']) && isset($_POST['user_id'])) {
    $userId = (int)$_POST['user_id'];
    $action = $_POST['action'];
    
    if ($action === 'delete') {
        // Check if user has any bookings before deleting
        $hasBookings = fetchOne("SELECT COUNT(*) as count FROM bookings WHERE customer_id = ?", [$userId])['count'];
        if ($hasBookings > 0) {
            setFlashMessage('error', 'Cannot delete user with existing bookings');
        } else {
            executeQuery("DELETE FROM users WHERE id = ?", [$userId]);
            setFlashMessage('success', 'User deleted successfully');
        }
    }
    
    redirect('users.php');
}

// Handle add/edit user
if (isset($_POST['save_user'])) {
    $userId = $_POST['user_id'] ?? null;
    $name = sanitizeInput($_POST['name']);
    $email = sanitizeInput($_POST['email']);
    $phone = sanitizeInput($_POST['phone']);
    $location = sanitizeInput($_POST['location']);
    $language = $_POST['language'];
    $password = $_POST['password'];
    
    if (empty($name) || empty($phone)) {
        setFlashMessage('error', 'Name and phone are required');
    } else {
        if ($userId) {
            // Update existing user
            if (!empty($password)) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                executeQuery("UPDATE users SET name = ?, email = ?, phone = ?, location = ?, language = ?, password = ? WHERE id = ?", 
                           [$name, $email, $phone, $location, $language, $hashedPassword, $userId]);
            } else {
                executeQuery("UPDATE users SET name = ?, email = ?, phone = ?, location = ?, language = ? WHERE id = ?", 
                           [$name, $email, $phone, $location, $language, $userId]);
            }
            setFlashMessage('success', 'User updated successfully');
        } else {
            // Add new user
            if (empty($password)) {
                setFlashMessage('error', 'Password is required for new users');
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                executeQuery("INSERT INTO users (name, email, phone, location, language, password) VALUES (?, ?, ?, ?, ?, ?)", 
                           [$name, $email, $phone, $location, $language, $hashedPassword]);
                setFlashMessage('success', 'User added successfully');
            }
        }
        redirect('users.php');
    }
}

// Get filters
$search = $_GET['search'] ?? '';
$location = $_GET['location'] ?? '';
$language = $_GET['language'] ?? '';

// Build query
$whereConditions = [];
$params = [];

if ($search) {
    $whereConditions[] = "(u.name LIKE ? OR u.phone LIKE ? OR u.email LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($location) {
    $whereConditions[] = "u.location = ?";
    $params[] = $location;
}

if ($language) {
    $whereConditions[] = "u.language = ?";
    $params[] = $language;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get users
$users = fetchAll("
    SELECT u.*, 
           (SELECT COUNT(*) FROM bookings WHERE customer_id = u.id) as total_bookings,
           (SELECT COUNT(*) FROM reviews WHERE customer_id = u.id) as total_reviews
    FROM users u
    $whereClause
    ORDER BY u.created_at DESC
", $params);

// Get locations for filter
$locations = fetchAll("SELECT DISTINCT location FROM users WHERE location IS NOT NULL AND location != '' ORDER BY location");

// Handle logout
if (isset($_GET['logout'])) {
    logout();
    redirect('../index.php');
}

// Get user for editing
$editUser = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $editUser = fetchOne("SELECT * FROM users WHERE id = ?", [$_GET['edit']]);
}
?>
<!DOCTYPE html>
<html lang="<?php echo $currentLang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $currentLang === 'en' ? 'Manage Users' : 'ব্যবহারকারী পরিচালনা'; ?> - Home Service Admin</title>
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
                        <i class="fas fa-users text-purple-600 mr-3"></i>
                        <?php echo $currentLang === 'en' ? 'Manage Users' : 'ব্যবহারকারী পরিচালনা'; ?>
                    </h1>
                    <p class="text-gray-600">
                        <?php echo $currentLang === 'en' ? 'Total users and customer management' : 'মোট ব্যবহারকারী এবং গ্রাহক পরিচালনা'; ?>
                    </p>
                </div>
                <div class="text-right">
                    <div class="text-3xl font-bold text-purple-600"><?php echo count($users); ?></div>
                    <div class="text-gray-600">
                        <?php echo $currentLang === 'en' ? 'Total Users' : 'মোট ব্যবহারকারী'; ?>
                    </div>
                </div>
            </div>
            <div class="flex justify-end mt-4">
                <button onclick="openAddUserModal()" class="btn-primary">
                    <i class="fas fa-plus mr-2"></i><?php echo $currentLang === 'en' ? 'Add User' : 'ব্যবহারকারী যোগ করুন'; ?>
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
            <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
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
                        <?php echo $currentLang === 'en' ? 'Location' : 'অবস্থান'; ?>
                    </label>
                    <select name="location" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        <option value=""><?php echo $currentLang === 'en' ? 'All Locations' : 'সব অবস্থান'; ?></option>
                        <?php foreach ($locations as $loc): ?>
                            <option value="<?php echo htmlspecialchars($loc['location']); ?>" <?php echo $location === $loc['location'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($loc['location']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="flex items-end">
                    <button type="submit" class="w-full bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700">
                        <i class="fas fa-search mr-2"></i><?php echo $currentLang === 'en' ? 'Filter' : 'ফিল্টার'; ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- Users Table -->
        <div class="glass-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <?php echo $currentLang === 'en' ? 'User Info' : 'ব্যবহারকারী তথ্য'; ?>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <?php echo $currentLang === 'en' ? 'Contact' : 'যোগাযোগ'; ?>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <?php echo $currentLang === 'en' ? 'Stats' : 'পরিসংখ্যান'; ?>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <?php echo $currentLang === 'en' ? 'Actions' : 'কর্ম'; ?>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                                    <i class="fas fa-users text-4xl mb-4 block"></i>
                                    <?php echo $currentLang === 'en' ? 'No users found' : 'কোনো ব্যবহারকারী পাওয়া যায়নি'; ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-12 w-12">
                                                <div class="h-12 w-12 rounded-full bg-purple-100 flex items-center justify-center">
                                                    <i class="fas fa-user text-purple-600 text-xl"></i>
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($user['name']); ?>
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    <?php echo $currentLang === 'en' ? 'ID' : 'আইডি'; ?>: <?php echo $user['id']; ?>
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    <i class="fas fa-globe mr-1"></i><?php echo $user['language'] === 'en' ? 'English' : 'বাংলা'; ?>
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    <?php echo $currentLang === 'en' ? 'Joined' : 'যোগদান'; ?>: <?php echo formatDate($user['created_at']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <div class="space-y-1">
                                            <div><i class="fas fa-phone mr-2"></i><?php echo htmlspecialchars($user['phone']); ?></div>
                                            <?php if ($user['email']): ?>
                                                <div><i class="fas fa-envelope mr-2"></i><?php echo htmlspecialchars($user['email']); ?></div>
                                            <?php endif; ?>
                                            <?php if ($user['location']): ?>
                                                <div><i class="fas fa-map-marker-alt mr-2"></i><?php echo htmlspecialchars($user['location']); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <div class="space-y-1">
                                            <div><i class="fas fa-calendar mr-2"></i><?php echo $user['total_bookings']; ?> <?php echo $currentLang === 'en' ? 'bookings' : 'বুকিং'; ?></div>
                                            <div><i class="fas fa-star mr-2"></i><?php echo $user['total_reviews']; ?> <?php echo $currentLang === 'en' ? 'reviews' : 'পর্যালোচনা'; ?></div>
                                        </div>
                                    </td>
                                    
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <a href="?edit=<?php echo $user['id']; ?>" 
                                               class="text-blue-600 hover:text-blue-900">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <button type="submit" class="text-red-600 hover:text-red-900"
                                                        onclick="return confirm('<?php echo $currentLang === 'en' ? 'Delete this user? This action cannot be undone.' : 'এই ব্যবহারকারী মুছে ফেলবেন? এই কর্মটি অপরিবর্তনীয়।'; ?>')">
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

    <!-- Add/Edit User Modal -->
    <div id="userModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    <?php echo $editUser ? ($currentLang === 'en' ? 'Edit User' : 'ব্যবহারকারী সম্পাদনা করুন') : ($currentLang === 'en' ? 'Add New User' : 'নতুন ব্যবহারকারী যোগ করুন'); ?>
                </h3>
                
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="user_id" value="<?php echo $editUser ? $editUser['id'] : ''; ?>">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <?php echo $currentLang === 'en' ? 'Name' : 'নাম'; ?> *
                        </label>
                        <input type="text" name="name" value="<?php echo $editUser ? htmlspecialchars($editUser['name']) : ''; ?>" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <?php echo $currentLang === 'en' ? 'Phone' : 'ফোন'; ?> *
                        </label>
                        <input type="text" name="phone" value="<?php echo $editUser ? htmlspecialchars($editUser['phone']) : ''; ?>" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <?php echo $currentLang === 'en' ? 'Email' : 'ইমেইল'; ?>
                        </label>
                        <input type="email" name="email" value="<?php echo $editUser ? htmlspecialchars($editUser['email']) : ''; ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <?php echo $currentLang === 'en' ? 'Location' : 'অবস্থান'; ?>
                        </label>
                        <input type="text" name="location" value="<?php echo $editUser ? htmlspecialchars($editUser['location']) : ''; ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <?php echo $currentLang === 'en' ? 'Language' : 'ভাষা'; ?>
                        </label>
                        <select name="language" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            <option value="en" <?php echo ($editUser && $editUser['language'] === 'en') ? 'selected' : ''; ?>>English</option>
                            <option value="bn" <?php echo ($editUser && $editUser['language'] === 'bn') ? 'selected' : ''; ?>>বাংলা</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <?php echo $currentLang === 'en' ? 'Password' : 'পাসওয়ার্ড'; ?> <?php echo $editUser ? '(' . ($currentLang === 'en' ? 'leave blank to keep current' : 'বর্তমান রাখতে খালি রাখুন') . ')' : ''; ?>
                        </label>
                        <input type="password" name="password" <?php echo $editUser ? '' : 'required'; ?>
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    </div>
                    
                    <div class="flex space-x-3 pt-4">
                        <button type="submit" name="save_user" class="flex-1 bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700">
                            <?php echo $currentLang === 'en' ? 'Save' : 'সংরক্ষণ করুন'; ?>
                        </button>
                        <button type="button" onclick="closeUserModal()" class="flex-1 bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400">
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
        function openAddUserModal() {
            document.getElementById('userModal').classList.remove('hidden');
        }
        
        function closeUserModal() {
            document.getElementById('userModal').classList.add('hidden');
        }
        
        // Close modal if clicking outside
        document.getElementById('userModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeUserModal();
            }
        });
        
        // Auto-open modal if editing
        <?php if ($editUser): ?>
        document.addEventListener('DOMContentLoaded', function() {
            openAddUserModal();
        });
        <?php endif; ?>
    </script>
</body>
</html> 