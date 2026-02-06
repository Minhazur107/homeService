<?php
require_once '../includes/functions.php';

if (!isLoggedIn()) {
	redirect('../auth/login.php');
}

$user = getCurrentUser();
$currentLang = getLanguage();

// Get customer's completed service history
$serviceHistory = fetchAll("SELECT b.*, sp.name as provider_name, sp.phone as provider_phone, sp.email as provider_email, 
		sc.name as category_name, sc.name_bn as category_name_bn,
        COALESCE((SELECT SUM(amount) FROM payments WHERE booking_id = b.id AND status = 'verified'), 0) as verified_payment_amount
		FROM bookings b
		JOIN service_providers sp ON b.provider_id = sp.id
		JOIN service_categories sc ON sp.category_id = sc.id
		WHERE b.customer_id = ? AND b.status = 'completed'
		ORDER BY b.booking_date DESC, b.booking_time DESC", [$user['id']]);

$flash = getFlashMessage();

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
    <title><?php echo $currentLang === 'en' ? 'Service History' : 'পরিষেবার ইতিহাস'; ?> - Home Service</title>
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
                    <a href="service_history.php" class="font-bold text-primary transition-colors border-b-2 border-primary">
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
                    
                    <div class="flex items-center space-x-3 ml-2 pl-4 border-l border-gray-100">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-primary to-secondary flex items-center justify-center text-white font-bold shadow-lg shadow-primary/20">
                            <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <div class="container mx-auto px-4 py-12 relative z-10">
        <!-- Page Intro -->
        <div class="max-w-6xl mx-auto mb-16 text-center">
            <div class="inline-flex items-center gap-2 bg-primary/10 px-4 py-2 rounded-full mb-6 anim-pop-in">
                <i class="fas fa-shield-check text-primary text-xs"></i>
                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-primary">Service Protocol History</span>
            </div>
            <h1 class="text-5xl md:text-7xl font-black text-gray-900 tracking-tighter mb-6"><?php echo $currentLang === 'en' ? 'Completed' : 'সম্পন্ন'; ?> <span class="text-gradient">Vault</span></h1>
            <p class="text-gray-500 font-medium max-w-2xl mx-auto leading-relaxed italic uppercase text-xs tracking-widest">
                <?php echo $currentLang === 'en' ? 'A historical record of all successfully executed missions and professional interactions.' : 'সফলভাবে সম্পন্ন হওয়া সকল কাজের একটি ঐতিহাসিক রেকর্ড।'; ?>
            </p>
        </div>

        <?php if ($flash): ?>
            <div class="max-w-4xl mx-auto mb-10 anim-pop-in">
                <div class="glass-card p-4 border-l-4 <?php echo $flash['type'] === 'success' ? 'border-emerald-500' : 'border-rose-500'; ?> bg-white/80">
                    <div class="flex items-center gap-3">
                        <i class="fas <?php echo $flash['type'] === 'success' ? 'fa-check-circle text-emerald-500' : 'fa-exclamation-circle text-rose-500'; ?> text-xl"></i>
                        <span class="font-bold text-gray-800"><?php echo htmlspecialchars($flash['message']); ?></span>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="max-w-5xl mx-auto">
            <?php if (empty($serviceHistory)): ?>
                <div class="glass-card p-20 text-center border-dashed border-2 anim-pop-in">
                    <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-8 text-gray-200">
                        <i class="fas fa-archive text-4xl"></i>
                    </div>
                    <h3 class="text-2xl font-black text-gray-900 mb-2"><?php echo t('Vault Empty'); ?></h3>
                    <p class="text-gray-500 font-medium mb-10 uppercase text-[10px] tracking-widest"><?php echo t('No records found in historical database'); ?></p>
                    <a href="../search.php" class="btn-primary py-4 px-10 rounded-2xl inline-flex items-center gap-3">
                        <i class="fas fa-plus"></i>
                        <span>Start New Session</span>
                    </a>
                </div>
            <?php else: ?>
                <div class="space-y-8">
                    <?php foreach ($serviceHistory as $service): ?>
                        <div class="glass-card overflow-hidden group hover:border-primary/30 transition-all anim-pop-in">
                            <div class="flex flex-col md:flex-row divide-y md:divide-y-0 md:divide-x divide-gray-100">
                                <!-- Service Info -->
                                <div class="p-8 md:w-2/3">
                                    <div class="flex items-center gap-4 mb-6">
                                        <div class="w-12 h-12 bg-primary/10 rounded-xl flex items-center justify-center text-primary group-hover:scale-110 transition-transform">
                                            <i class="fas fa-tools"></i>
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-3">
                                                <h3 class="text-xl font-black text-gray-900 tracking-tight"><?php echo t($service['category_name']); ?></h3>
                                                <span class="text-[10px] font-black uppercase text-emerald-500 bg-emerald-50 px-2 py-1 rounded">Archived</span>
                                            </div>
                                            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mt-1">ID-REF: #<?php echo $service['id']; ?></p>
                                        </div>
                                    </div>
                                    
                                    <div class="grid grid-cols-2 gap-8 mb-8">
                                        <div>
                                            <span class="text-[8px] font-black text-gray-400 uppercase tracking-widest block mb-2">Professional</span>
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center text-[10px] font-black text-gray-500">
                                                    <?php echo strtoupper(substr($service['provider_name'], 0, 1)); ?>
                                                </div>
                                                <span class="font-bold text-gray-800 text-sm"><?php echo htmlspecialchars($service['provider_name']); ?></span>
                                            </div>
                                        </div>
                                        <div>
                                            <span class="text-[8px] font-black text-gray-400 uppercase tracking-widest block mb-1">Execution Day</span>
                                            <span class="font-black text-gray-800 text-sm"><?php echo formatDate($service['booking_date']); ?></span>
                                            <span class="block text-[10px] text-gray-400 font-bold"><?php echo $service['booking_time']; ?></span>
                                        </div>
                                    </div>

                                    <?php if (!empty($service['notes'])): ?>
                                        <div class="bg-gray-50/50 p-4 rounded-xl border border-gray-100 mb-8 italic">
                                            <p class="text-xs font-medium text-gray-500 leading-relaxed">"<?php echo htmlspecialchars($service['notes']); ?>"</p>
                                        </div>
                                    <?php endif; ?>

                                    <div class="flex flex-wrap gap-3">
                                        <a href="booking_details.php?id=<?php echo $service['id']; ?>" class="btn-primary py-3 px-6 text-[10px] flex items-center gap-2">
                                            <i class="fas fa-file-invoice"></i>
                                            <span>Full Protocol</span>
                                        </a>
                                        <a href="tel:<?php echo htmlspecialchars($service['provider_phone']); ?>" class="w-10 h-10 bg-gray-900 rounded-xl flex items-center justify-center text-white hover:bg-emerald-600 transition-all">
                                            <i class="fas fa-phone-alt"></i>
                                        </a>
                                    </div>
                                </div>

                                <!-- Financials -->
                                <div class="p-8 md:w-1/3 bg-gray-50/30 flex flex-col justify-center items-center text-center">
                                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Settlement Total</span>
                                    <div class="text-4xl font-black text-gray-900 mb-4 tracking-tighter">
                                        ৳<?php echo number_format($service['verified_payment_amount'] > 0 ? $service['verified_payment_amount'] : ($service['final_price'] ?? 0)); ?>
                                    </div>
                                    <div class="inline-flex items-center gap-2 text-[10px] font-black text-emerald-600 uppercase tracking-widest bg-emerald-50 px-4 py-2 rounded-xl">
                                        <i class="fas fa-check-double"></i>
                                        <span>Payment Verified</span>
                                    </div>
                                    <div class="mt-8 pt-8 border-t border-gray-100 w-full">
                                        <span class="text-[8px] font-black text-gray-300 uppercase tracking-widest block mb-1">Archived At</span>
                                        <span class="text-[10px] font-bold text-gray-400"><?php echo formatDateTime($service['updated_at']); ?></span>
                                    </div>
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
            <p class="text-gray-500 font-black text-sm uppercase tracking-widest">&copy; <?php echo date('Y'); ?> HOME SERVICE SOLUTIONS. ALL RIGHTS RESERVED.</p>
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
