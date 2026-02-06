<?php
require_once '../includes/functions.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    redirect('login.php');
}

$currentLang = getLanguage();
$admin = getCurrentAdmin();

// Handle actions
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    markNotificationAsRead($_GET['mark_read']);
    redirect('notifications.php');
}

if (isset($_GET['mark_all_read'])) {
    markAllNotificationsAsRead($admin['id']);
    redirect('notifications.php');
}

// Get all notifications
$notifications = getAllNotifications($admin['id'], 'admin', 100);

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
    <title><?php echo $currentLang === 'en' ? 'Admin Notifications' : 'অ্যাডমিন বিজ্ঞপ্তি'; ?> - Home Service</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }
        
        .admin-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 25%, #f093fb 50%, #f5576c 75%, #4facfe 100%);
            background-size: 400% 400%;
            animation: gradientShift 20s ease infinite;
            position: relative;
            overflow-x: hidden;
        }
        
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .admin-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        
        .admin-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .admin-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
        }
        
        .nav-link {
            color: #4b5563;
            transition: all 0.3s ease;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 500;
            text-decoration: none;
        }
        
        .nav-link:hover {
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
            transform: translateY(-2px);
        }
        
        .logout-btn {
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(238, 90, 82, 0.3);
        }
        
        .admin-avatar {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.2rem;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .notification-item {
            transition: all 0.3s ease;
        }
        
        .notification-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        
        .notification-unread {
            border-left: 4px solid #3b82f6;
            background: linear-gradient(135deg, #eff6ff, #dbeafe);
        }
        
        .notification-read {
            border-left: 4px solid #e5e7eb;
            background: linear-gradient(135deg, #f9fafb, #f3f4f6);
        }
    </style>
</head>
<body class="admin-bg min-h-screen">
    <!-- Header -->
    <header class="admin-header sticky top-0 z-50">
        <nav class="container mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-6">
                    <a href="../index.php" class="text-3xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent uppercase">
                        Home Service
                    </a>
                    <span class="text-gray-700 font-semibold text-lg">
                        <i class="fas fa-bell text-purple-600 mr-2"></i>
                        <?php echo $currentLang === 'en' ? 'Notifications' : 'বিজ্ঞপ্তি'; ?>
                    </span>
                </div>
                
                <div class="flex items-center space-x-4">
                    <a href="dashboard.php" class="nav-link">
                        <i class="fas fa-tachometer-alt"></i>
                        <?php echo $currentLang === 'en' ? 'Dashboard' : 'ড্যাশবোর্ড'; ?>
                    </a>
                    <a href="users.php" class="nav-link">
                        <i class="fas fa-users"></i>
                        <?php echo $currentLang === 'en' ? 'Users' : 'ব্যবহারকারী'; ?>
                    </a>
                    <a href="providers.php" class="nav-link">
                        <i class="fas fa-user-check"></i>
                        <?php echo $currentLang === 'en' ? 'Providers' : 'প্রদানকারী'; ?>
                    </a>
                    <a href="bookings.php" class="nav-link">
                        <i class="fas fa-calendar-alt"></i>
                        <?php echo $currentLang === 'en' ? 'Bookings' : 'বুকিং'; ?>
                    </a>
                    <div class="flex items-center space-x-3">
                        <div class="admin-avatar">
                            <?php echo strtoupper(substr($admin['username'], 0, 1)); ?>
                        </div>
                        <div class="text-right">
                            <div class="font-semibold text-gray-800"><?php echo htmlspecialchars($admin['username']); ?></div>
                            <div class="text-sm text-gray-600"><?php echo ucfirst($admin['role']); ?></div>
                        </div>
                    </div>
                    <a href="?logout=1" class="logout-btn">
                        <i class="fas fa-sign-out-alt mr-2"></i>
                        <?php echo $currentLang === 'en' ? 'Logout' : 'লগআউট'; ?>
                    </a>
                </div>
            </div>
        </nav>
    </header>

    <div class="container mx-auto px-4 py-8 relative z-10">
        <!-- Page Header -->
        <div class="admin-card p-8 mb-8">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-4xl font-bold bg-gradient-to-r from-purple-600 to-blue-600 bg-clip-text text-transparent mb-2">
                        <i class="fas fa-bell mr-3"></i><?php echo $currentLang === 'en' ? 'Admin Notifications' : 'অ্যাডমিন বিজ্ঞপ্তি'; ?>
                    </h1>
                    <p class="text-gray-600 text-lg">
                        <i class="fas fa-info-circle mr-2"></i><?php echo count($notifications); ?> <?php echo $currentLang === 'en' ? 'total notifications' : 'মোট বিজ্ঞপ্তি'; ?>
                    </p>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="?mark_all_read" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-check-double mr-2"></i>
                        <?php echo $currentLang === 'en' ? 'Mark All Read' : 'সব পঠিত চিহ্নিত করুন'; ?>
                    </a>
                    <a href="dashboard.php" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                        <i class="fas fa-arrow-left mr-2"></i>
                        <?php echo $currentLang === 'en' ? 'Back to Dashboard' : 'ড্যাশবোর্ডে ফিরে যান'; ?>
                    </a>
                </div>
            </div>
        </div>

        <!-- Notifications List -->
        <div class="admin-card p-8">
            <?php if (empty($notifications)): ?>
                <div class="text-center py-12">
                    <i class="fas fa-bell-slash text-6xl text-gray-400 mb-6"></i>
                    <h3 class="text-2xl font-semibold text-gray-600 mb-2">
                        <?php echo $currentLang === 'en' ? 'No Notifications' : 'কোন বিজ্ঞপ্তি নেই'; ?>
                    </h3>
                    <p class="text-gray-500">
                        <?php echo $currentLang === 'en' ? 'You\'re all caught up! No new notifications.' : 'আপনি সবকিছু দেখেছেন! কোন নতুন বিজ্ঞপ্তি নেই।'; ?>
                    </p>
                </div>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($notifications as $notification): ?>
                        <div class="notification-item notification-<?php echo $notification['is_read'] ? 'read' : 'unread'; ?> rounded-xl p-6">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-3 mb-3">
                                        <h3 class="font-semibold text-gray-800 text-lg">
                                            <?php echo htmlspecialchars($notification['title']); ?>
                                        </h3>
                                        <?php if (!$notification['is_read']): ?>
                                            <span class="bg-blue-500 text-white text-xs px-2 py-1 rounded-full">
                                                <?php echo $currentLang === 'en' ? 'New' : 'নতুন'; ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-gray-600 mb-3"><?php echo htmlspecialchars($notification['message']); ?></p>
                                    <div class="flex items-center justify-between">
                                        <p class="text-sm text-gray-500">
                                            <i class="fas fa-clock mr-1"></i>
                                            <?php echo formatDateTime($notification['created_at']); ?>
                                        </p>
                                        <?php if (!$notification['is_read']): ?>
                                            <a href="?mark_read=<?php echo $notification['id']; ?>" 
                                               class="bg-blue-600 text-white px-3 py-1 rounded-lg text-sm hover:bg-blue-700 transition-colors">
                                                <i class="fas fa-check mr-1"></i>
                                                <?php echo $currentLang === 'en' ? 'Mark as Read' : 'পঠিত হিসেবে চিহ্নিত করুন'; ?>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

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
        document.querySelectorAll('.admin-card, .notification-item').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(card);
        });
    </script>
</body>
</html>
