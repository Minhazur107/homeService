<?php
require_once '../includes/functions.php';

// Check if provider is logged in
if (!isProviderLoggedIn()) {
    redirect('../auth/login.php');
}

$currentLang = getLanguage();
$provider = getCurrentProvider();

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
$whereConditions = ["b.provider_id = ?"];
$params = [$provider['id']];

if ($status) {
    $whereConditions[] = "b.status = ?";
    $params[] = $status;
}

$whereClause = implode(' AND ', $whereConditions);

// Get total count
$countSql = "SELECT COUNT(*) as count FROM bookings b WHERE $whereClause";
$totalCount = fetchOne($countSql, $params)['count'];
$totalPages = ceil($totalCount / $perPage);

// Get bookings
$sql = "SELECT b.*, u.name as customer_name, u.phone as customer_phone, 
        sc.name as category_name, sc.name_bn as category_name_bn
        FROM bookings b
        JOIN users u ON b.customer_id = u.id
        JOIN service_categories sc ON b.category_id = sc.id
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
                </div>
                
                <div class="flex items-center space-x-6">
                    <a href="dashboard.php" class="font-bold text-gray-700 hover:text-primary transition-colors flex items-center gap-2">
                        <i class="fas fa-arrow-left"></i>
                        <span><?php echo $currentLang === 'en' ? 'Dashboard' : 'ড্যাশবোর্ড'; ?></span>
                    </a>
                    <div class="flex items-center space-x-3 border-l border-gray-100 pl-4 ml-2">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-primary to-secondary flex items-center justify-center text-white font-bold shadow-lg">
                            <?php echo strtoupper(substr($provider['name'], 0, 1)); ?>
                        </div>
                        <div class="text-right hidden sm:block">
                            <div class="font-bold text-gray-800 leading-none"><?php echo htmlspecialchars($provider['name']); ?></div>
                            <div class="text-[10px] font-black uppercase text-primary tracking-widest mt-1">Provider</div>
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
        <div class="max-w-6xl mx-auto">
            <!-- Page Header Card -->
            <div class="glass-card p-10 mb-8 overflow-hidden relative">
                <div class="absolute top-0 right-0 w-64 h-64 bg-primary/5 rounded-full blur-3xl -mr-16 -mt-16 pointer-events-none"></div>
                
                <div class="flex flex-col md:flex-row justify-between items-center gap-6 relative z-10">
                    <div>
                        <h1 class="text-4xl font-black text-gray-900 tracking-tighter mb-2">
                            <?php echo $currentLang === 'en' ? 'My Bookings' : 'আমার বুকিং'; ?>
                        </h1>
                        <p class="text-gray-500 font-medium text-lg">
                            <?php echo $currentLang === 'en' ? 'Manage your service appointments' : 'আপনার সেবার অ্যাপয়েন্টমেন্ট পরিচালনা করুন'; ?>
                        </p>
                    </div>
                    <div class="flex items-center gap-6">
                        <div class="text-right">
                            <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1"><?php echo $currentLang === 'en' ? 'Total' : 'মোট'; ?></div>
                            <div class="text-4xl font-black text-primary leading-none"><?php echo $totalCount; ?></div>
                        </div>
                        <div class="w-16 h-16 bg-gradient-to-br from-primary to-secondary rounded-2xl flex items-center justify-center text-white text-3xl shadow-xl shadow-primary/30">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="glass-card p-6 mb-8">
                <form method="GET" class="flex flex-col md:flex-row gap-4 items-end">
                    <div class="flex-1 w-full">
                        <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">
                            <?php echo $currentLang === 'en' ? 'Filter by Status' : 'স্ট্যাটাস অনুযায়ী ফিল্টার'; ?>
                        </label>
                        <div class="relative">
                            <select name="status" class="w-full px-5 py-3 rounded-xl bg-gray-50 border-2 border-gray-100 focus:bg-white focus:border-primary focus:outline-none font-bold text-gray-800 transition-all appearance-none cursor-pointer">
                                <option value=""><?php echo $currentLang === 'en' ? 'All Status' : 'সব স্ট্যাটাস'; ?></option>
                                <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>><?php echo t('pending'); ?></option>
                                <option value="confirmed" <?php echo $status === 'confirmed' ? 'selected' : ''; ?>><?php echo t('confirmed'); ?></option>
                                <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>><?php echo t('completed'); ?></option>
                                <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>><?php echo t('cancelled'); ?></option>
                            </select>
                            <i class="fas fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                        </div>
                    </div>
                    <button type="submit" class="btn-primary py-3.5 px-8 flex items-center gap-2 shadow-lg shadow-primary/25 w-full md:w-auto justify-center">
                        <i class="fas fa-filter"></i>
                        <span><?php echo $currentLang === 'en' ? 'Apply Filter' : 'ফিল্টার প্রয়োগ করুন'; ?></span>
                    </button>
                </form>
            </div>

            <!-- Bookings List -->
            <?php if (empty($bookings)): ?>
                <div class="glass-card p-16 text-center">
                    <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6 text-gray-300">
                        <i class="fas fa-calendar-times text-4xl"></i>
                    </div>
                    <h3 class="text-2xl font-black text-gray-800 mb-2">
                        <?php echo $currentLang === 'en' ? 'No bookings found' : 'কোনো বুকিং পাওয়া যায়নি'; ?>
                    </h3>
                    <p class="text-gray-500 font-medium">
                        <?php echo $currentLang === 'en' ? 'You don\'t have any bookings yet.' : 'আপনার এখনও কোনো বুকিং নেই।'; ?>
                    </p>
                </div>
            <?php else: ?>
                <div class="space-y-6">
                    <?php foreach ($bookings as $booking): ?>
                        <div class="glass-card p-8 hover:border-primary/30 transition-all group relative overflow-hidden">
                            <!-- Status Badge -->
                            <div class="absolute top-0 right-0 p-6 pointer-events-none">
                                <span class="px-4 py-2 rounded-xl text-xs font-black uppercase tracking-widest shadow-sm
                                    <?php echo $booking['status'] === 'completed' ? 'bg-emerald-100 text-emerald-600' : 
                                        ($booking['status'] === 'confirmed' ? 'bg-blue-100 text-blue-600' : 
                                        ($booking['status'] === 'pending' ? 'bg-amber-100 text-amber-600' : 'bg-rose-100 text-rose-600')); ?>">
                                    <?php echo t($booking['status']); ?>
                                </span>
                            </div>

                            <div class="flex flex-col lg:flex-row justify-between gap-8">
                                <!-- Customer Info & Booking Details -->
                                <div class="flex-1">
                                    <div class="flex items-start gap-4 mb-6">
                                        <div class="w-14 h-14 bg-gray-100 rounded-2xl flex items-center justify-center text-gray-500 text-xl font-black shrink-0">
                                            <?php echo strtoupper(substr($booking['customer_name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <h3 class="text-xl font-black text-gray-900 leading-tight mb-1">
                                                <?php echo htmlspecialchars($booking['customer_name']); ?>
                                            </h3>
                                            <div class="flex items-center gap-3 text-xs font-bold text-gray-400 uppercase tracking-wider">
                                                <span>#<?php echo $booking['id']; ?></span>
                                                <span class="w-1 h-1 rounded-full bg-gray-300"></span>
                                                <span><?php echo $currentLang === 'en' ? $booking['category_name'] : $booking['category_name_bn']; ?></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                        <!-- Time & Service -->
                                        <div class="space-y-3">
                                            <div class="flex items-center gap-3 text-gray-600">
                                                <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center text-blue-500 shrink-0">
                                                    <i class="fas fa-calendar-alt text-xs"></i>
                                                </div>
                                                <span class="font-bold text-sm"><?php echo formatDate($booking['booking_date']); ?></span>
                                            </div>
                                            <div class="flex items-center gap-3 text-gray-600">
                                                <div class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-500 shrink-0">
                                                    <i class="fas fa-clock text-xs"></i>
                                                </div>
                                                <span class="font-bold text-sm"><?php echo date('h:i A', strtotime($booking['booking_time'])); ?></span>
                                            </div>
                                            <?php if ($booking['service_type']): ?>
                                                <div class="flex items-center gap-3 text-gray-600">
                                                    <div class="w-8 h-8 rounded-lg bg-purple-50 flex items-center justify-center text-purple-500 shrink-0">
                                                        <i class="fas fa-tools text-xs"></i>
                                                    </div>
                                                    <span class="font-medium text-sm"><?php echo htmlspecialchars($booking['service_type']); ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Financials & Created -->
                                        <div class="space-y-3">
                                            <?php if ($booking['final_price']): ?>
                                                <div class="flex items-center gap-3 text-emerald-600">
                                                    <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center text-emerald-500 shrink-0">
                                                        <i class="fas fa-tag text-xs"></i>
                                                    </div>
                                                    <span class="font-black text-sm"><?php echo formatPrice($booking['final_price']); ?></span>
                                                </div>
                                            <?php endif; ?>
                                            <div class="flex items-center gap-3 text-gray-400">
                                                <div class="w-8 h-8 rounded-lg bg-gray-50 flex items-center justify-center text-gray-400 shrink-0">
                                                    <i class="fas fa-history text-xs"></i>
                                                </div>
                                                <span class="font-medium text-xs">Created: <?php echo formatDateTime($booking['created_at']); ?></span>
                                            </div>
                                        </div>
                                    </div>

                                    <?php if ($booking['cancellation_reason']): ?>
                                        <div class="bg-rose-50 border border-rose-100 rounded-xl p-4 mb-4 flex items-start gap-3">
                                            <i class="fas fa-exclamation-circle text-rose-500 mt-1"></i>
                                            <div>
                                                <div class="text-xs font-black text-rose-500 uppercase tracking-widest mb-1">Cancellation Reason</div>
                                                <p class="text-sm font-medium text-rose-700 leading-relaxed"><?php echo htmlspecialchars($booking['cancellation_reason']); ?></p>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($booking['notes']): ?>
                                        <div class="bg-gray-50 border border-gray-100 rounded-xl p-4 flex items-start gap-3">
                                            <i class="fas fa-sticky-note text-gray-400 mt-1"></i>
                                            <div>
                                                <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Notes</div>
                                                <p class="text-sm font-medium text-gray-600 leading-relaxed italic">"<?php echo htmlspecialchars($booking['notes']); ?>"</p>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Actions Column -->
                                <div class="flex flex-row lg:flex-col gap-3 justify-end lg:justify-start lg:w-48 shrink-0 border-t lg:border-t-0 lg:border-l border-gray-100 pt-6 lg:pt-0 lg:pl-8 mt-4 lg:mt-0">
                                    <div class="grid grid-cols-2 gap-2 w-full">
                                        <a href="tel:<?php echo $booking['customer_phone']; ?>" 
                                           class="bg-gray-900 text-white h-12 rounded-xl flex items-center justify-center hover:bg-gray-800 transition-all shadow-lg shadow-gray-900/10">
                                            <i class="fas fa-phone"></i>
                                        </a>
                                        <a href="https://wa.me/<?php echo $booking['customer_phone']; ?>" target="_blank"
                                           class="bg-emerald-500 text-white h-12 rounded-xl flex items-center justify-center hover:bg-emerald-600 transition-all shadow-lg shadow-emerald-500/20">
                                            <i class="fab fa-whatsapp text-lg"></i>
                                        </a>
                                    </div>
                                    
                                    <?php if ($booking['status'] === 'pending'): ?>
                                        <a href="accept_booking.php?id=<?php echo $booking['id']; ?>" 
                                           class="btn-primary w-full py-3 flex items-center justify-center gap-2 text-xs">
                                            <i class="fas fa-check"></i>
                                            <span>Accept</span>
                                        </a>
                                        <a href="reject_booking.php?id=<?php echo $booking['id']; ?>" 
                                           class="bg-rose-50 text-rose-600 w-full py-3 rounded-xl flex items-center justify-center gap-2 text-xs font-black uppercase tracking-wider hover:bg-rose-500 hover:text-white transition-all">
                                            <i class="fas fa-times"></i>
                                            <span>Decline</span>
                                        </a>
                                    <?php elseif ($booking['status'] === 'confirmed'): ?>
                                        <a href="complete_booking.php?id=<?php echo $booking['id']; ?>" 
                                           class="bg-emerald-500 text-white w-full py-3 rounded-xl flex items-center justify-center gap-2 text-xs font-black uppercase tracking-wider hover:bg-emerald-600 transition-all shadow-lg shadow-emerald-500/20">
                                            <i class="fas fa-check-double"></i>
                                            <span>Complete</span>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <a href="booking_details.php?id=<?php echo $booking['id']; ?>" 
                                       class="bg-white border-2 border-gray-100 text-gray-600 w-full py-3 rounded-xl flex items-center justify-center gap-2 text-xs font-black uppercase tracking-wider hover:border-primary hover:text-primary transition-all">
                                        <i class="fas fa-eye"></i>
                                        <span>Details</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="flex justify-center mt-10">
                        <div class="glass-card p-2 flex gap-2">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>&status=<?php echo $status; ?>" 
                                   class="w-10 h-10 rounded-lg flex items-center justify-center bg-gray-50 text-gray-600 hover:bg-primary hover:text-white transition-all font-bold">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <a href="?page=<?php echo $i; ?>&status=<?php echo $status; ?>" 
                                   class="w-10 h-10 rounded-lg flex items-center justify-center font-bold transition-all <?php echo $i === $page ? 'bg-primary text-white shadow-lg shadow-primary/30' : 'bg-gray-50 text-gray-600 hover:bg-primary hover:text-white'; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&status=<?php echo $status; ?>" 
                                   class="w-10 h-10 rounded-lg flex items-center justify-center bg-gray-50 text-gray-600 hover:bg-primary hover:text-white transition-all font-bold">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
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