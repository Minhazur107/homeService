<?php
require_once '../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('../auth/login.php');
}

$currentLang = getLanguage();
$user = getCurrentUser();

// Handle logout
if (isset($_GET['logout'])) {
    logout();
    redirect('../index.php');
}

// Handle actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $notificationId = (int)$_GET['id'];
    
    switch ($action) {
        case 'read':
            markNotificationAsRead($notificationId);
            break;
            
        case 'read_all':
            markAllNotificationsAsRead($user['id']);
            break;
    }
    
    // Redirect to remove action parameters
    redirect('notifications.php');
}

// Get notifications
$notifications = getAllNotifications($user['id'], 'customer', 50);
$unreadCount = getNotificationCount($user['id'], 'customer');
?>
<!DOCTYPE html>
<html lang="<?php echo $currentLang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $currentLang === 'en' ? 'Notifications' : 'বিজ্ঞপ্তি'; ?> - Home Service</title>
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
                    <a href="notifications.php" class="font-bold text-primary transition-colors border-b-2 border-primary">
                        <i class="fas fa-bell mr-1"></i>
                        <?php echo $currentLang === 'en' ? 'Notifications' : 'বিজ্ঞপ্তি'; ?>
                    </a>
                    <a href="my_selections.php" class="font-bold text-gray-700 hover:text-primary transition-colors">
                        <i class="fas fa-heart mr-1"></i>
                        <?php echo $currentLang === 'en' ? 'Selections' : 'নির্বাচন'; ?>
                    </a>
                    <a href="payments.php" class="font-bold text-gray-700 hover:text-primary transition-colors">
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
        <div class="max-w-4xl mx-auto">
            <!-- Header Card -->
            <div class="glass-card p-10 mb-10">
                <div class="flex flex-col md:flex-row justify-between items-center gap-6">
                    <div>
                        <div class="flex items-center space-x-4 mb-3">
                            <div class="w-12 h-12 bg-primary/10 rounded-xl flex items-center justify-center text-primary">
                                <i class="fas fa-bell text-xl anim-ring"></i>
                            </div>
                            <h1 class="text-4xl font-black text-gray-900 tracking-tighter">
                                <?php echo $currentLang === 'en' ? 'Activity Timeline' : 'বিজ্ঞপ্তি'; ?>
                            </h1>
                        </div>
                        <p class="text-gray-500 font-medium">
                            <?php echo $currentLang === 'en' ? 'Live updates on your service ecosystem' : 'আপনার পরিষেবার লাইভ আপডেট'; ?>
                        </p>
                    </div>
                    <div class="text-right">
                        <div class="text-xs font-black text-gray-400 uppercase tracking-widest mb-1"><?php echo $currentLang === 'en' ? 'Live Feed' : 'মোট বিজ্ঞপ্তি'; ?></div>
                        <div class="text-4xl font-black text-primary leading-none"><?php echo count($notifications); ?></div>
                        <?php if ($unreadCount > 0): ?>
                            <div class="mt-3">
                                <span class="bg-rose-100 text-rose-600 px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest">
                                    <?php echo $unreadCount; ?> <?php echo $currentLang === 'en' ? 'UNREAD' : 'অপঠিত'; ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($unreadCount > 0): ?>
                    <div class="mt-8 pt-8 border-t border-gray-100">
                        <a href="?action=read_all" class="btn-primary py-3 px-8 text-xs inline-flex items-center gap-2">
                            <i class="fas fa-check-double"></i>
                            <span><?php echo $currentLang === 'en' ? 'Clear All Notifications' : 'সব পঠিত হিসাবে চিহ্নিত করুন'; ?></span>
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Notifications List -->
            <?php if (empty($notifications)): ?>
                <div class="glass-card p-20 text-center">
                    <div class="w-32 h-32 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-8 text-gray-200">
                        <i class="fas fa-bell-slash text-5xl"></i>
                    </div>
                    <h3 class="text-2xl font-black text-gray-900 mb-2">
                        <?php echo $currentLang === 'en' ? 'The silence is golden' : 'এখনও কোনো বিজ্ঞপ্তি নেই'; ?>
                    </h3>
                    <p class="text-gray-500 font-medium max-w-md mx-auto leading-relaxed">
                        <?php echo $currentLang === 'en' ? 'Your timeline is empty. We will alert you the moment something important happens with your bookings.' : 'প্রদানকারীরা আপনার বুকিংয়ে সাড়া দিলে আপনি এখানে বিজ্ঞপ্তি দেখতে পাবেন।'; ?>
                    </p>
                </div>
            <?php else: ?>
                <div class="space-y-6 relative before:absolute before:left-8 before:top-4 before:bottom-4 before:w-[2px] before:bg-gray-100">
                    <?php foreach ($notifications as $notification): ?>
                        <div class="glass-card p-8 ml-6 relative group hover:scale-[1.01] transition-all <?php echo !$notification['is_read'] ? 'border-l-4 border-primary' : ''; ?>">
                            <!-- Timeline Dot -->
                            <div class="absolute -left-[30px] top-1/2 -translate-y-1/2 w-3 h-3 rounded-full bg-white border-2 <?php echo !$notification['is_read'] ? 'border-primary' : 'border-gray-300'; ?> z-10 transition-colors"></div>
                            
                            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                                <div class="flex-grow">
                                    <div class="flex items-center space-x-4 mb-3">
                                        <div class="w-10 h-10 rounded-xl flex items-center justify-center text-sm
                                            <?php echo $notification['type'] === 'success' ? 'bg-emerald-100 text-emerald-600' : 
                                                ($notification['type'] === 'error' ? 'bg-rose-100 text-rose-600' : 
                                                ($notification['type'] === 'warning' ? 'bg-amber-100 text-amber-600' : 'bg-primary/10 text-primary')); ?>">
                                            <i class="fas <?php 
                                                echo $notification['type'] === 'success' ? 'fa-check-circle' : 
                                                    ($notification['type'] === 'error' ? 'fa-times-circle' : 
                                                    ($notification['type'] === 'warning' ? 'fa-exclamation-triangle' : 'fa-info-circle')); 
                                            ?>"></i>
                                        </div>
                                        <h3 class="font-black text-gray-900 text-lg">
                                            <?php echo htmlspecialchars($notification['title']); ?>
                                        </h3>
                                        <?php if (!$notification['is_read']): ?>
                                            <span class="bg-primary text-white px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest anim-pulse">
                                                <?php echo $currentLang === 'en' ? 'LIVE' : 'নতুন'; ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <p class="text-gray-600 font-medium leading-relaxed">
                                        <?php echo htmlspecialchars($notification['message']); ?>
                                    </p>
                                </div>
                                
                                <div class="flex items-center gap-6 min-w-fit">
                                    <div class="text-right">
                                        <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1"><?php echo $currentLang === 'en' ? 'Received' : 'সময়'; ?></div>
                                        <div class="text-sm font-bold text-gray-700"><?php echo formatDateTime($notification['created_at']); ?></div>
                                    </div>
                                    
                                    <?php if (!$notification['is_read']): ?>
                                        <a href="?action=read&id=<?php echo $notification['id']; ?>" class="w-12 h-12 bg-primary/10 rounded-2xl flex items-center justify-center text-primary hover:bg-primary hover:text-white transition-all shadow-sm">
                                            <i class="fas fa-check"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="glass-nav py-12 mt-20">
        <div class="container mx-auto px-4 text-center">
            <p class="text-gray-500 font-black text-sm uppercase tracking-widest">&copy; <?php echo date('Y'); ?> HOME SERVICE. ALL RIGHTS RESERVED.</p>
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
        style.textContent = `
            .glass-card.visible { opacity: 1 !important; transform: translateY(0) !important; }
            @keyframes swing {
                0% { transform: rotate(0deg); }
                20% { transform: rotate(15deg); }
                40% { transform: rotate(-15deg); }
                60% { transform: rotate(10deg); }
                80% { transform: rotate(-10deg); }
                100% { transform: rotate(0deg); }
            }
            .anim-ring { animation: swing 2s infinite ease-in-out; transform-origin: top center; }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>