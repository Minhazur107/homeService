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

// Get user's reviews
$reviews = fetchAll("
    SELECT r.*, sp.name as provider_name, sc.name as category_name, sc.name_bn as category_name_bn
    FROM reviews r
    JOIN service_providers sp ON r.provider_id = sp.id
    JOIN service_categories sc ON sp.category_id = sc.id
    WHERE r.customer_id = ?
    ORDER BY r.created_at DESC
", [$user['id']]);

// Stats
$stats = fetchOne("
    SELECT COUNT(*) as total_reviews, AVG(rating) as avg_rating
    FROM reviews WHERE customer_id = ?
", [$user['id']]);
?>
<!DOCTYPE html>
<html lang="<?php echo $currentLang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $currentLang === 'en' ? 'My Reviews' : 'আমার পর্যালোচনা'; ?> - Home Service</title>
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
                    <a href="reviews.php" class="font-bold text-primary transition-colors border-b-2 border-primary">
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
            </div>
        </nav>
    </header>

    <div class="container mx-auto px-4 py-8 relative z-10">
        <div class="max-w-5xl mx-auto">
            <!-- Hero Stats Card -->
            <div class="glass-card mb-10 overflow-hidden">
                <div class="p-10 flex flex-col md:flex-row justify-between items-center gap-8 bg-gradient-to-br from-primary/5 to-secondary/5">
                    <div>
                        <div class="flex items-center space-x-4 mb-3">
                            <div class="w-14 h-14 bg-primary rounded-2xl flex items-center justify-center text-white shadow-xl shadow-primary/20">
                                <i class="fas fa-star text-2xl"></i>
                            </div>
                            <h1 class="text-4xl font-black text-gray-900 tracking-tighter">
                                <?php echo $currentLang === 'en' ? 'Service Reviews' : 'আমার পর্যালোচনা'; ?>
                            </h1>
                        </div>
                        <p class="text-gray-500 font-medium text-lg leading-relaxed">
                            <?php echo $currentLang === 'en' ? 'Your contribution to our quality ecosystem' : 'আমাদের গুণমান ইকোসিস্টেমে আপনার অবদান'; ?>
                        </p>
                    </div>
                    
                    <div class="flex items-center gap-10">
                        <div class="text-center">
                            <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1"><?php echo $currentLang === 'en' ? 'Submissions' : 'মোট'; ?></div>
                            <div class="text-4xl font-black text-gray-900 leading-none"><?php echo $stats['total_reviews']; ?></div>
                        </div>
                        <div class="text-center pl-10 border-l border-gray-200">
                            <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1"><?php echo $currentLang === 'en' ? 'Avg Rating' : 'গড় রেটিং'; ?></div>
                            <div class="flex items-center gap-2">
                                <span class="text-4xl font-black text-primary leading-none"><?php echo number_format($stats['avg_rating'], 1); ?></span>
                                <i class="fas fa-star text-primary text-xs"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (empty($reviews)): ?>
                <div class="glass-card p-20 text-center">
                    <div class="w-32 h-32 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-8 text-gray-200">
                        <i class="fas fa-comment-slash text-5xl"></i>
                    </div>
                    <h3 class="text-2xl font-black text-gray-900 mb-3">
                        <?php echo $currentLang === 'en' ? 'No Reviews Yet' : 'এখনও কোনো পর্যালোচনা নেই'; ?>
                    </h3>
                    <p class="text-gray-500 font-medium max-w-md mx-auto leading-relaxed mb-10">
                        <?php echo $currentLang === 'en' ? 'Your voice matters. Review completed services to help the community.' : 'আপনি এখনও কোনো পর্যালোচনা জমা দেননি। পর্যালোচনা দিতে একটি সেবা সম্পন্ন করুন।'; ?>
                    </p>
                    <a href="bookings.php" class="btn-primary py-4 px-10 inline-flex items-center gap-3">
                        <i class="fas fa-search"></i>
                        <span><?php echo $currentLang === 'en' ? 'Review a Service' : 'সেবা খুঁজুন'; ?></span>
                    </a>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <?php foreach ($reviews as $review): ?>
                        <div class="glass-card p-10 group hover:scale-[1.02] transition-all relative">
                            <!-- Status Pin -->
                            <div class="absolute top-6 right-6">
                                <span class="bg-gray-100 text-gray-500 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest group-hover:bg-primary group-hover:text-white transition-colors">
                                    <?php echo t($review['status']); ?>
                                </span>
                            </div>

                            <div class="flex items-center gap-6 mb-8">
                                <div class="w-16 h-16 bg-primary/10 rounded-2xl flex items-center justify-center text-primary text-2xl font-black">
                                    <?php echo strtoupper(substr($review['provider_name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <h3 class="text-lg font-black text-gray-900 pr-12"><?php echo htmlspecialchars($review['provider_name']); ?></h3>
                                    <p class="text-[10px] font-black text-primary uppercase tracking-widest mt-1">
                                        <?php echo $currentLang === 'en' ? $review['category_name'] : $review['category_name_bn']; ?>
                                    </p>
                                </div>
                            </div>
                            
                            <div class="bg-gray-50 rounded-2xl p-6 mb-8 border border-gray-100 italic font-medium text-gray-700 leading-relaxed min-h-[100px]">
                                "<?php echo htmlspecialchars($review['review_text'] ?: 'No comment left.'); ?>"
                            </div>
                            
                            <div class="flex items-center justify-between border-t border-gray-100 pt-6">
                                <div class="flex items-center gap-2">
                                    <div class="flex text-amber-400 text-sm">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="<?php echo $i <= $review['rating'] ? 'fas' : 'far'; ?> fa-star"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <span class="font-black text-gray-900"><?php echo $review['rating']; ?>/5</span>
                                </div>
                                <div class="text-right">
                                    <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1"><?php echo $currentLang === 'en' ? 'Dated' : 'তারিখ'; ?></div>
                                    <div class="text-xs font-bold text-gray-700"><?php echo formatDate($review['created_at']); ?></div>
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

        const style = document.createElement('style');
        style.textContent = '.glass-card.visible { opacity: 1 !important; transform: translateY(0) !important; }';
        document.head.appendChild(style);
    </script>
</body>
</html>