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
    session_destroy();
    redirect('../index.php');
}

// Get Stats
$stats = [
    'upcoming' => fetchOne("SELECT COUNT(*) as count FROM bookings WHERE customer_id = ? AND status IN ('pending', 'confirmed')", [$user['id']])['count'],
    'total' => fetchOne("SELECT COUNT(*) as count FROM bookings WHERE customer_id = ?", [$user['id']])['count'],
    'selections' => fetchOne("SELECT COUNT(*) as count FROM customer_provider_selections WHERE customer_id = ? AND status IN ('pending', 'contacted')", [$user['id']])['count'],
    'unread_notifications' => fetchOne("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0", [$user['id']])['count']
];

// Get Upcoming Bookings
$upcomingBookings = fetchAll("
    SELECT b.*, sp.name as provider_name, sc.name as category_name, sc.name_bn as category_name_bn
    FROM bookings b
    JOIN service_providers sp ON b.provider_id = sp.id
    JOIN service_categories sc ON b.category_id = sc.id
    WHERE b.customer_id = ? AND b.status IN ('pending', 'confirmed')
    ORDER BY b.booking_date ASC, b.booking_time ASC
    LIMIT 3
", [$user['id']]);

// Get Recent Selections
$recentSelections = fetchAll("
    SELECT cps.*, sp.name as provider_name, sc.name as category_name, sc.name_bn as category_name_bn
    FROM customer_provider_selections cps
    JOIN service_providers sp ON cps.provider_id = sp.id
    JOIN service_categories sc ON cps.category_id = sc.id
    WHERE cps.customer_id = ? AND cps.status IN ('pending', 'contacted')
    ORDER BY cps.created_at DESC
    LIMIT 3
", [$user['id']]);

// Get Popular Categories
$popularCategories = fetchAll("SELECT * FROM service_categories WHERE is_active = 1 LIMIT 6");

?>
<!DOCTYPE html>
<html lang="<?php echo $currentLang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $currentLang === 'en' ? 'Customer Dashboard' : 'গ্রাহক ড্যাশবোর্ড'; ?> - Home Service</title>
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
                </div>
                
                <div class="hidden md:flex items-center space-x-6">
                    <a href="dashboard.php" class="font-bold text-primary transition-colors">
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
        <div class="max-w-7xl mx-auto">
            
            <!-- Welcome Billboard -->
            <div class="glass-card p-10 mb-10 border-none group">
                <div class="relative z-10 lg:flex items-center justify-between">
                    <div>
                        <div class="inline-flex items-center space-x-2 bg-primary/10 px-4 py-2 rounded-full mb-6">
                            <i class="fas fa-sparkles text-primary animate-pulse"></i>
                            <span class="text-xs font-black uppercase tracking-widest text-primary">Member Since <?php echo date('Y', strtotime($user['created_at'])); ?></span>
                        </div>
                        <h1 class="text-4xl md:text-6xl font-black text-gray-900 mb-6 tracking-tighter">
                            <?php echo $currentLang === 'en' ? 'Welcome back' : 'স্বাগতম'; ?>, 
                            <span class="text-gradient"><?php echo explode(' ', $user['name'])[0]; ?>!</span>
                        </h1>
                        <p class="text-xl text-gray-500 font-medium max-w-xl mb-10 leading-relaxed">
                            Your personalized hub for premium home services. Manage your active bookings and explore new possibilities.
                        </p>
                        <div class="flex flex-wrap gap-4">
                            <a href="../search.php" class="btn-primary py-4 px-10 rounded-2xl flex items-center gap-3">
                                <i class="fas fa-plus"></i>
                                <span>Find New Service</span>
                            </a>
                        </div>
                    </div>
                    <div class="hidden lg:block">
                        <div class="w-64 h-64 bg-gradient-to-br from-primary/20 to-secondary/20 rounded-[50px] flex items-center justify-center transform rotate-6 group-hover:rotate-0 transition-transform duration-700">
                            <i class="fas fa-star text-7xl text-primary opacity-30 transform -rotate-6 group-hover:rotate-0 transition-transform duration-700"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
                <div class="glass-card p-8 group border-b-4 border-indigo-500">
                    <div class="w-14 h-14 bg-indigo-500/10 rounded-2xl flex items-center justify-center text-2xl text-indigo-600 mb-6 group-hover:scale-110 transition-transform">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="text-4xl font-black text-gray-900 mb-1"><?php echo $stats['upcoming']; ?></div>
                    <div class="text-xs font-black text-gray-400 uppercase tracking-[0.2em]"><?php echo $currentLang === 'en' ? 'Upcoming' : 'অপেক্ষমান'; ?></div>
                </div>
                <div class="glass-card p-8 group border-b-4 border-emerald-500">
                    <div class="w-14 h-14 bg-emerald-500/10 rounded-2xl flex items-center justify-center text-2xl text-emerald-600 mb-6 group-hover:scale-110 transition-transform">
                        <i class="fas fa-check-double"></i>
                    </div>
                    <div class="text-4xl font-black text-gray-900 mb-1"><?php echo $stats['total']; ?></div>
                    <div class="text-xs font-black text-gray-400 uppercase tracking-[0.2em]"><?php echo $currentLang === 'en' ? 'Total' : 'মোট'; ?></div>
                </div>
                <div class="glass-card p-8 group border-b-4 border-rose-500">
                    <div class="w-14 h-14 bg-rose-500/10 rounded-2xl flex items-center justify-center text-2xl text-rose-600 mb-6 group-hover:scale-110 transition-transform">
                        <i class="fas fa-heart"></i>
                    </div>
                    <div class="text-4xl font-black text-gray-900 mb-1"><?php echo $stats['selections']; ?></div>
                    <div class="text-xs font-black text-gray-400 uppercase tracking-[0.2em]"><?php echo $currentLang === 'en' ? 'Selections' : 'পছন্দ'; ?></div>
                </div>
                <div class="glass-card p-8 group border-b-4 border-amber-500">
                    <div class="w-14 h-14 bg-amber-500/10 rounded-2xl flex items-center justify-center text-2xl text-amber-600 mb-6 group-hover:scale-110 transition-transform">
                        <i class="fas fa-bell"></i>
                    </div>
                    <div class="text-4xl font-black text-gray-900 mb-1"><?php echo $stats['unread_notifications']; ?></div>
                    <div class="text-xs font-black text-gray-400 uppercase tracking-[0.2em]"><?php echo $currentLang === 'en' ? 'Alerts' : 'বিজ্ঞপ্তি'; ?></div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
                <!-- Left Main Column -->
                <div class="lg:col-span-2 space-y-10">
                    
                    <!-- Upcoming Services -->
                    <section>
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-2xl font-black text-gray-900 tracking-tight"><?php echo $currentLang === 'en' ? 'Upcoming Services' : 'আসন্ন সেবা'; ?></h2>
                            <a href="bookings.php" class="text-sm font-black text-purple-600 hover:underline"><?php echo $currentLang === 'en' ? 'View All' : 'সব দেখুন'; ?></a>
                        </div>
                        
                        <?php if (empty($upcomingBookings)): ?>
                            <div class="customer-card p-8 text-center bg-white">
                                <i class="fas fa-calendar-plus text-4xl text-gray-200 mb-4"></i>
                                <p class="text-gray-500 font-bold"><?php echo $currentLang === 'en' ? 'No services scheduled' : 'কোনো সেবা নির্ধারিত নেই'; ?></p>
                            </div>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($upcomingBookings as $booking): ?>
                                    <div class="customer-card p-6 flex flex-wrap items-center justify-between gap-4 bg-white">
                                        <div class="flex items-center gap-6">
                                            <div class="w-16 h-16 rounded-2xl bg-purple-50 flex items-center justify-center text-purple-600 text-2xl font-black">
                                                <i class="fas fa-tools"></i>
                                            </div>
                                            <div>
                                                <h4 class="font-black text-gray-900"><?php echo $currentLang === 'en' ? $booking['category_name'] : $booking['category_name_bn']; ?></h4>
                                                <div class="text-sm font-bold text-gray-400 flex items-center gap-3">
                                                    <span><i class="fas fa-user-circle mr-1"></i> <?php echo htmlspecialchars($booking['provider_name']); ?></span>
                                                    <span><i class="fas fa-clock mr-1"></i> <?php echo formatDate($booking['booking_date']); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-4">
                                            <span class="px-4 py-1.5 rounded-full bg-green-100 text-green-600 text-[10px] font-black uppercase tracking-widest"><?php echo $booking['status']; ?></span>
                                            <a href="booking_details.php?id=<?php echo $booking['id']; ?>" class="w-10 h-10 rounded-xl bg-gray-100 flex items-center justify-center text-gray-700 hover:bg-indigo-600 hover:text-white transition-all">
                                                <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </section>

                    <!-- Popular Categories -->
                    <style>
                        .category-grid {
                            display: grid;
                            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
                            gap: 1.5rem;
                        }
                        
                        .premium-category-card {
                            background: rgba(255, 255, 255, 0.6);
                            backdrop-filter: blur(15px);
                            border: 1px solid rgba(255, 255, 255, 0.5);
                            border-radius: 2.5rem;
                            padding: 2.5rem 2rem;
                            text-align: center;
                            transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
                            position: relative;
                            overflow: hidden;
                            display: flex;
                            flex-direction: column;
                            align-items: center;
                            text-decoration: none;
                        }
                        
                        .premium-category-card:hover {
                            transform: translateY(-12px) scale(1.03);
                            background: rgba(255, 255, 255, 1);
                            box-shadow: 
                                0 30px 60px -12px rgba(0, 0, 0, 0.15),
                                0 18px 36px -18px rgba(0, 0, 0, 0.1);
                            border-color: var(--card-color);
                        }
                        
                        .cat-icon-box {
                            width: 80px;
                            height: 80px;
                            border-radius: 2rem;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            font-size: 2.25rem;
                            margin-bottom: 1.5rem;
                            background: var(--card-gradient);
                            color: white;
                            box-shadow: 0 15px 30px -5px var(--shadow-color);
                            transition: all 0.5s ease;
                        }
                        
                        .premium-category-card:hover .cat-icon-box {
                            transform: rotate(10deg) scale(1.1);
                        }
                        
                        .cat-title {
                            font-size: 1.1rem;
                            font-weight: 800;
                            color: #1a1a1a;
                            margin-bottom: 0.5rem;
                            letter-spacing: -0.02em;
                        }
                        
                        .cat-badge {
                            font-size: 10px;
                            font-weight: 900;
                            text-transform: uppercase;
                            letter-spacing: 0.1em;
                            padding: 0.4rem 1rem;
                            border-radius: 100px;
                            background: rgba(0,0,0,0.03);
                            color: #64748b;
                        }
                    </style>

                    <section>
                        <div class="flex items-center justify-between mb-8">
                            <div>
                                <h2 class="text-3xl font-black text-gray-900 tracking-tight"><?php echo $currentLang === 'en' ? 'Popular Services' : 'জনপ্রিয় সেবা'; ?></h2>
                                <p class="text-sm font-bold text-gray-400 mt-1 uppercase tracking-widest"><?php echo $currentLang === 'en' ? 'Curated solutions for your home' : 'আপনার বাড়ির জন্য নির্বাচিত সমাধান'; ?></p>
                            </div>
                        </div>

                        <div class="category-grid">
                            <?php
                            $specialCategories = [
                                ['id' => 1, 'name' => 'AC Servicing', 'bn' => 'এসি সার্ভিসিং', 'icon' => 'snowflake', 'grad' => 'linear-gradient(135deg, #0ea5e9, #2563eb)', 'shadow' => 'rgba(14, 165, 233, 0.4)'],
                                ['id' => 2, 'name' => 'Plumbing', 'bn' => 'প্লাম্বিং', 'icon' => 'faucet-drip', 'grad' => 'linear-gradient(135deg, #f43f5e, #e11d48)', 'shadow' => 'rgba(244, 63, 94, 0.4)'],
                                ['id' => 3, 'name' => 'Electrical', 'bn' => 'ইলেকট্রিক্যাল', 'icon' => 'bolt-lightning', 'grad' => 'linear-gradient(135deg, #f59e0b, #d97706)', 'shadow' => 'rgba(245, 158, 11, 0.4)'],
                                ['id' => 4, 'name' => 'Cleaning', 'bn' => 'ক্লিনিং', 'icon' => 'broom', 'grad' => 'linear-gradient(135deg, #10b981, #059669)', 'shadow' => 'rgba(16, 185, 129, 0.4)'],
                                ['id' => 5, 'name' => 'Carpentry', 'bn' => 'কার্পেন্টিং', 'icon' => 'chair', 'grad' => 'linear-gradient(135deg, #8b5cf6, #7c3aed)', 'shadow' => 'rgba(139, 92, 246, 0.4)'],
                                ['id' => 6, 'name' => 'Painting', 'bn' => 'পেইন্টিং', 'icon' => 'paint-roller', 'grad' => 'linear-gradient(135deg, #ec4899, #db2777)', 'shadow' => 'rgba(236, 72, 153, 0.4)']
                            ];
                            
                            foreach ($specialCategories as $cat): ?>
                                <a href="../search.php?category=<?php echo $cat['id']; ?>" class="premium-category-card" style="--card-color: <?php echo $cat['shadow']; ?>;">
                                    <div class="cat-icon-box" style="--card-gradient: <?php echo $cat['grad']; ?>; --shadow-color: <?php echo $cat['shadow']; ?>;">
                                        <i class="fas fa-<?php echo $cat['icon']; ?>"></i>
                                    </div>
                                    <div class="cat-title"><?php echo $currentLang === 'en' ? $cat['name'] : $cat['bn']; ?></div>
                                    <div class="cat-badge">Pro Verified</div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </section>
                </div>

                <!-- Right Sidebar Column -->
                <div class="space-y-10">
                    
                    <!-- Quick Actions -->
                    <section>
                        <h2 class="text-2xl font-black text-gray-900 tracking-tight mb-6"><?php echo $currentLang === 'en' ? 'Command Hub' : 'কমান্ড হাব'; ?></h2>
                        <div class="space-y-4">
                            <a href="profile.php" class="customer-card p-6 flex items-center gap-6 bg-white hover:border-purple-600 transition-colors">
                                <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center"><i class="fas fa-sliders"></i></div>
                                <span class="font-black text-gray-800"><?php echo $currentLang === 'en' ? 'Account Settings' : 'অ্যাকাউন্ট সেটিংস'; ?></span>
                            </a>
                            <a href="payments.php" class="customer-card p-6 flex items-center gap-6 bg-white hover:border-purple-600 transition-colors">
                                <div class="w-12 h-12 rounded-xl bg-green-50 text-green-600 flex items-center justify-center"><i class="fas fa-wallet"></i></div>
                                <span class="font-black text-gray-800"><?php echo $currentLang === 'en' ? 'Payment History' : 'পেমেন্ট হিস্ট্রি'; ?></span>
                            </a>
                            <a href="reviews.php" class="customer-card p-6 flex items-center gap-6 bg-white hover:border-purple-600 transition-colors">
                                <div class="w-12 h-12 rounded-xl bg-pink-50 text-pink-600 flex items-center justify-center"><i class="fas fa-star-half-alt"></i></div>
                                <span class="font-black text-gray-800"><?php echo $currentLang === 'en' ? 'My Reviews' : 'আমার রিভিউ'; ?></span>
                            </a>
                            <a href="service_history.php" class="customer-card p-6 flex items-center gap-6 bg-white hover:border-purple-600 transition-colors">
                                <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center"><i class="fas fa-history"></i></div>
                                <span class="font-black text-gray-800"><?php echo $currentLang === 'en' ? 'Service History' : 'সার্ভিস হিস্ট্রি'; ?></span>
                            </a>
                            <a href="my_work_requests.php" class="customer-card p-6 flex items-center gap-6 bg-white hover:border-purple-600 transition-colors">
                                <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center"><i class="fas fa-broadcast-tower"></i></div>
                                <span class="font-black text-gray-800"><?php echo $currentLang === 'en' ? 'Work Hub' : 'ওয়ার্ক হাব'; ?></span>
                            </a>
                        </div>
                    </section>

                    <!-- Recent Selections -->
                    <section>
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-2xl font-black text-gray-900 tracking-tight"><?php echo $currentLang === 'en' ? 'My Selections' : 'আমার পছন্দ'; ?></h2>
                            <a href="my_selections.php" class="text-sm font-black text-purple-600 hover:underline"><?php echo $currentLang === 'en' ? 'All' : 'সব'; ?></a>
                        </div>
                        <div class="space-y-4">
                            <?php foreach ($recentSelections as $sel): ?>
                                <div class="customer-card p-5 bg-white border border-gray-100">
                                    <div class="flex justify-between items-start mb-2">
                                        <h4 class="font-black text-gray-900"><?php echo htmlspecialchars($sel['provider_name']); ?></h4>
                                        <span class="text-[10px] font-black uppercase text-purple-500 tracking-widest"><?php echo $sel['status']; ?></span>
                                    </div>
                                    <p class="text-xs font-bold text-gray-400 mb-4"><?php echo $currentLang === 'en' ? $sel['category_name'] : $sel['category_name_bn']; ?></p>
                                    <a href="my_selections.php" class="text-xs font-black text-indigo-600 hover:underline">Manage Request →</a>
                                </div>
                            <?php endforeach; ?>
                            <?php if (empty($recentSelections)): ?>
                                <div class="customer-card p-6 text-center bg-gray-50 border-dashed border-2">
                                    <p class="text-xs font-black text-gray-400 uppercase tracking-widest"><?php echo $currentLang === 'en' ? 'No active requests' : 'কোনো সক্রিয় অনুরোধ নেই'; ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>

                    <!-- Logout Button -->
                    <a href="?logout=1" class="logout-btn w-full flex items-center justify-center py-5 text-lg">
                        <i class="fas fa-power-off mr-3"></i> <?php echo $currentLang === 'en' ? 'Secure Sign Out' : 'সিকিউর সাইন আউট'; ?>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-white bg-opacity-95 backdrop-blur-sm border-t border-gray-200 py-12 mt-20 relative z-10">
        <div class="container mx-auto px-4 text-center">
            <div class="flex justify-center gap-8 mb-8 text-gray-400">
                <a href="#" class="hover:text-purple-600"><i class="fab fa-facebook-f"></i></a>
                <a href="#" class="hover:text-purple-600"><i class="fab fa-twitter"></i></a>
                <a href="#" class="hover:text-purple-600"><i class="fab fa-instagram"></i></a>
            </div>
            <p class="text-gray-500 font-bold">&copy; <?php echo date('Y'); ?> Home Service Ecosystem. <?php echo $currentLang === 'en' ? 'All rights reserved.' : 'সর্বস্বত্ব সংরক্ষিত।'; ?></p>
        </div>
    </footer>
</body>
</html>
