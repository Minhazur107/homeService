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

// Get filter parameters
$status = $_GET['status'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Build query
$whereConditions = ["b.customer_id = ?"];
$params = [$user['id']];

if ($status) {
    $whereConditions[] = "b.status = ?";
    $params[] = $status;
}

$whereClause = implode(' AND ', $whereConditions);

// Get total count
$countSql = "SELECT COUNT(*) as count FROM bookings b WHERE $whereClause";
$totalCountResult = fetchOne($countSql, $params);
$totalCount = $totalCountResult ? $totalCountResult['count'] : 0;
$totalPages = ceil($totalCount / $perPage);

// Get bookings
$sql = "SELECT b.*, sp.name as provider_name, sp.phone as provider_phone, 
        sc.name as category_name, sc.name_bn as category_name_bn,
        (SELECT SUM(amount) FROM payments WHERE booking_id = b.id) as total_paid
        FROM bookings b
        JOIN service_providers sp ON b.provider_id = sp.id
        LEFT JOIN service_categories sc ON b.category_id = sc.id
        WHERE $whereClause
        ORDER BY b.created_at DESC
        LIMIT $perPage OFFSET $offset";

$bookings = fetchAll($sql, $params);
?>
<!DOCTYPE html>
<html lang="<?php echo $currentLang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $currentLang === 'en' ? 'My Bookings' : 'আমার বুকিং'; ?> - Home Service</title>
    <link rel="stylesheet" href="../assets/ui.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .status-pending { background: linear-gradient(135deg, #fbbf24, #f59e0b); color: white; }
        .status-confirmed { background: linear-gradient(135deg, #10b981, #059669); color: white; }
        .status-completed { background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; }
        .status-cancelled { background: linear-gradient(135deg, #ef4444, #dc2626); color: white; }
        
        .pagination-btn {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: black;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .pagination-btn:hover { background: rgba(0, 0, 0, 0.05); transform: translateY(-2px); }
        .pagination-btn.active { background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white; border: none; }
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
                    <a href="bookings.php" class="font-bold text-primary transition-colors border-b-2 border-primary">
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
                        <a href="?lang=en<?php echo $status ? '&status='.$status : ''; ?>" class="px-3 py-1 rounded-lg text-xs font-bold <?php echo $currentLang === 'en' ? 'bg-white shadow-sm text-primary' : 'text-gray-400'; ?>">EN</a>
                        <a href="?lang=bn<?php echo $status ? '&status='.$status : ''; ?>" class="px-3 py-1 rounded-lg text-xs font-bold <?php echo $currentLang === 'bn' ? 'bg-white shadow-sm text-primary' : 'text-gray-400'; ?>">বাংলা</a>
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
        <div class="max-w-6xl mx-auto">
            <!-- Summary Card -->
            <div class="glass-card p-8 mb-8">
                <div class="flex flex-col md:flex-row justify-between items-center gap-6">
                    <div>
                        <h1 class="text-4xl font-black text-gray-900 mb-2 tracking-tighter">
                            <?php echo $currentLang === 'en' ? 'My Bookings' : 'আমার বুকিং'; ?>
                        </h1>
                        <p class="text-gray-500 font-medium">
                            <?php echo $currentLang === 'en' ? 'Track and manage your service history' : 'আপনার পরিষেবার ইতিহাস ট্র্যাক এবং পরিচালনা করুন'; ?>
                        </p>
                    </div>
                    <div class="flex items-center gap-4 bg-primary/5 p-4 rounded-2xl border border-primary/10">
                        <div class="w-12 h-12 bg-primary rounded-xl flex items-center justify-center text-white shadow-lg shadow-primary/30">
                            <i class="fas fa-calendar-check text-xl"></i>
                        </div>
                        <div>
                            <div class="text-2xl font-black text-primary leading-none"><?php echo $totalCount; ?></div>
                            <div class="text-xs font-bold text-gray-400 uppercase tracking-widest"><?php echo $currentLang === 'en' ? 'Total' : 'মোট'; ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="glass-card p-6 mb-8">
                <form method="GET" class="flex flex-wrap items-center gap-4">
                    <div class="flex-grow min-w-[200px]">
                        <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2 px-1"><?php echo $currentLang === 'en' ? 'Filter by Status' : 'অবস্থা অনুযায়ী ফিল্টার'; ?></label>
                        <select name="status" onchange="this.form.submit()" class="w-full bg-white border border-gray-200 rounded-xl px-4 py-3 font-bold text-gray-700 focus:ring-2 focus:ring-primary focus:border-transparent transition-all">
                            <option value=""><?php echo $currentLang === 'en' ? 'All Bookings' : 'সব বুকিং'; ?></option>
                            <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>><?php echo $currentLang === 'en' ? 'Pending' : 'অপেক্ষমান'; ?></option>
                            <option value="confirmed" <?php echo $status === 'confirmed' ? 'selected' : ''; ?>><?php echo $currentLang === 'en' ? 'Confirmed' : 'নিশ্চিত'; ?></option>
                            <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>><?php echo $currentLang === 'en' ? 'Completed' : 'সম্পন্ন'; ?></option>
                            <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>><?php echo $currentLang === 'en' ? 'Cancelled' : 'বাতিল'; ?></option>
                        </select>
                    </div>
                    <?php if ($status): ?>
                        <div class="flex items-end h-[68px]">
                            <a href="bookings.php" class="font-bold text-gray-400 hover:text-red-500 transition-colors px-2 pb-2 underline text-xs uppercase tracking-widest">
                                <?php echo $currentLang === 'en' ? 'Clear' : 'মুছুন'; ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Bookings List -->
            <div class="space-y-6">
                <?php if (empty($bookings)): ?>
                    <div class="glass-card p-16 text-center">
                        <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                            <i class="fas fa-calendar-times text-4xl text-gray-300"></i>
                        </div>
                        <h3 class="text-2xl font-black text-gray-900 mb-2"><?php echo $currentLang === 'en' ? 'No Bookings Found' : 'কোনো বুকিং পাওয়া যায়নি'; ?></h3>
                        <p class="text-gray-500 font-medium mb-8"><?php echo $currentLang === 'en' ? "You haven't made any bookings matching this criteria yet." : 'আপনার এই শর্তাবলীর সাথে মিলে এমন কোনো বুকিং নেই।'; ?></p>
                        <a href="../search.php" class="btn-primary py-3 px-8 inline-flex items-center gap-2">
                            <i class="fas fa-search"></i>
                            <span><?php echo $currentLang === 'en' ? 'Find Services' : 'সেবা খুঁজুন'; ?></span>
                        </a>
                    </div>
                <?php else: ?>
                    <?php foreach ($bookings as $booking): ?>
                        <div class="glass-card overflow-hidden group hover:scale-[1.01] transition-transform duration-300">
                            <div class="p-8">
                                <div class="flex flex-col lg:flex-row justify-between gap-8">
                                    <div class="flex-grow">
                                        <div class="flex items-center gap-3 mb-4">
                                            <span class="status-badge status-<?php echo $booking['status']; ?>">
                                                <?php echo t($booking['status']); ?>
                                            </span>
                                            <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">
                                                ID: #<?php echo $booking['id']; ?> • <?php echo formatDate($booking['created_at']); ?>
                                            </span>
                                        </div>
                                        
                                        <h3 class="text-2xl font-black text-gray-900 mb-2">
                                            <?php echo htmlspecialchars($booking['provider_name']); ?>
                                        </h3>
                                        <div class="flex flex-wrap gap-4 text-sm font-bold text-gray-500 mb-6">
                                            <span class="flex items-center gap-2">
                                                <i class="fas fa-tag text-primary"></i>
                                                <?php echo $currentLang === 'en' ? $booking['category_name'] : $booking['category_name_bn']; ?>
                                            </span>
                                            <?php if ($booking['service_type']): ?>
                                                <span class="flex items-center gap-2">
                                                    <i class="fas fa-tools text-primary"></i>
                                                    <?php echo htmlspecialchars($booking['service_type']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-gray-50/50 p-6 rounded-2xl border border-gray-100">
                                            <div class="flex items-start gap-4">
                                                <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-primary shadow-sm">
                                                    <i class="fas fa-clock"></i>
                                                </div>
                                                <div>
                                                    <div class="text-xs font-black text-gray-400 uppercase tracking-widest mb-1"><?php echo $currentLang === 'en' ? 'Scheduled For' : 'সময় নির্ধারণ'; ?></div>
                                                    <div class="font-bold text-gray-800">
                                                        <?php echo formatDate($booking['booking_date']); ?> at <?php echo $booking['booking_time']; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="flex items-start gap-4">
                                                <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-primary shadow-sm">
                                                    <i class="fas fa-map-marker-alt"></i>
                                                </div>
                                                <div>
                                                    <div class="text-xs font-black text-gray-400 uppercase tracking-widest mb-1"><?php echo $currentLang === 'en' ? 'Service Location' : 'সেবার স্থান'; ?></div>
                                                    <div class="font-bold text-gray-800">
                                                        <?php echo htmlspecialchars($booking['customer_address'] ?: 'Not provided'); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="lg:w-72 flex flex-col justify-between border-t lg:border-t-0 lg:border-l border-gray-100 pt-8 lg:pt-0 lg:pl-8">
                                        <div>
                                            <div class="text-xs font-black text-gray-400 uppercase tracking-widest mb-2"><?php echo $currentLang === 'en' ? 'Estimated Price' : 'আনুমানিক মূল্য'; ?></div>
                                            <div class="text-3xl font-black text-primary mb-6">
                                                <?php 
                                                if ($booking['final_price']) {
                                                    echo '৳' . number_format($booking['final_price']);
                                                } elseif ($booking['total_paid'] > 0) {
                                                    echo '৳' . number_format($booking['total_paid']);
                                                } else {
                                                    echo '<span class="text-gray-300 text-2xl">TBD</span>';
                                                }
                                                ?>
                                            </div>
                                        </div>
                                        
                                        <div class="space-y-3">
                                            <a href="booking_details.php?id=<?php echo $booking['id']; ?>" class="w-full btn-primary py-3 flex items-center justify-center gap-2">
                                                <i class="fas fa-eye"></i>
                                                <span><?php echo $currentLang === 'en' ? 'View Details' : 'বিস্তারিত দেখুন'; ?></span>
                                            </a>
                                            <?php if ($booking['status'] === 'completed' && empty(fetchOne("SELECT id FROM reviews WHERE booking_id = ?", [$booking['id']]))): ?>
                                                <a href="review.php?booking_id=<?php echo $booking['id']; ?>" class="w-full bg-amber-500 hover:bg-amber-600 text-white font-bold py-3 rounded-xl flex items-center justify-center gap-2 transition-all">
                                                    <i class="fas fa-star"></i>
                                                    <span><?php echo $currentLang === 'en' ? 'Write Review' : 'রিভিউ লিখুন'; ?></span>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="flex justify-center gap-2 mt-12 mb-8">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <a href="?status=<?php echo $status; ?>&page=<?php echo $i; ?>" 
                                   class="pagination-btn <?php echo $page === $i ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
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

        // Add a class for visible elements
        const style = document.createElement('style');
        style.textContent = '.glass-card.visible { opacity: 1 !important; transform: translateY(0) !important; }';
        document.head.appendChild(style);
    </script>
</body>
</html>
