<?php
require_once '../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

$currentLang = getLanguage();
$user = getCurrentUser();

// Get user's active selections
$selections = fetchAll("
    SELECT cps.*, sp.name as provider_name, sp.phone as provider_phone, sp.email as provider_email,
           sc.name as category_name, sc.name_bn as category_name_bn
    FROM customer_provider_selections cps
    JOIN service_providers sp ON cps.provider_id = sp.id
    JOIN service_categories sc ON cps.category_id = sc.id
    WHERE cps.customer_id = ? AND cps.status IN ('pending', 'contacted')
    ORDER BY cps.created_at DESC
", [$user['id']]);

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
    <title><?php echo $currentLang === 'en' ? 'My Selections' : 'আমার নির্বাচন'; ?> - Home Service</title>
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
                    <a href="notifications.php" class="font-bold text-gray-700 hover:text-primary transition-colors">
                        <i class="fas fa-bell mr-1"></i>
                        <?php echo $currentLang === 'en' ? 'Notifications' : 'বিজ্ঞপ্তি'; ?>
                    </a>
                    <a href="my_selections.php" class="font-bold text-primary transition-colors border-b-2 border-primary">
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
        <div class="max-w-6xl mx-auto">
            <!-- Header Card -->
            <div class="glass-card p-10 mb-10 overflow-hidden">
                <div class="flex flex-col md:flex-row justify-between items-center gap-8 relative z-10">
                    <div>
                        <div class="flex items-center space-x-4 mb-3">
                            <div class="w-14 h-14 bg-primary rounded-2xl flex items-center justify-center text-white shadow-xl shadow-primary/20">
                                <i class="fas fa-heart text-2xl"></i>
                            </div>
                            <h1 class="text-4xl font-black text-gray-900 tracking-tighter">
                                <?php echo $currentLang === 'en' ? 'Elite Selections' : 'আমার নির্বাচন'; ?>
                            </h1>
                        </div>
                        <p class="text-gray-500 font-medium text-lg leading-relaxed">
                            <?php echo $currentLang === 'en' ? 'Curated list of your preferred service specialists' : 'আপনার প্রিয় পরিষেবা প্রদানকারীদের পরিচালনা করুন'; ?>
                        </p>
                    </div>
                    
                    <div class="flex items-center gap-8">
                        <div class="text-center">
                            <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1"><?php echo $currentLang === 'en' ? 'Active' : 'সক্রিয়'; ?></div>
                            <div class="text-4xl font-black text-primary leading-none"><?php echo count($selections); ?></div>
                        </div>
                        <a href="../search.php" class="btn-primary py-4 px-8 text-sm flex items-center gap-2">
                            <i class="fas fa-plus"></i>
                            <span><?php echo $currentLang === 'en' ? 'Select New' : 'নতুন নির্বাচন'; ?></span>
                        </a>
                    </div>
                </div>
            </div>

            <?php if (empty($selections)): ?>
                <div class="glass-card p-20 text-center">
                    <div class="w-32 h-32 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-8 text-gray-200">
                        <i class="fas fa-heart-broken text-5xl"></i>
                    </div>
                    <h3 class="text-2xl font-black text-gray-900 mb-3">
                        <?php echo $currentLang === 'en' ? 'No One Here Yet' : 'এখনও কোনো নির্বাচন নেই'; ?>
                    </h3>
                    <p class="text-gray-500 font-medium max-w-md mx-auto leading-relaxed mb-10">
                        <?php echo $currentLang === 'en' ? 'Browse our verified service professional catalog and select the talent you want to work with.' : 'আপনি এখনও কোনো প্রদানকারী নির্বাচন করেননি। পরিষেবা অনুসন্ধান করে শুরু করুন।'; ?>
                    </p>
                    <a href="../search.php" class="btn-primary py-4 px-10 inline-flex items-center gap-3">
                        <i class="fas fa-search"></i>
                        <span><?php echo $currentLang === 'en' ? 'Explore Professionals' : 'প্রদানকারী খুঁজুন'; ?></span>
                    </a>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 gap-8">
                    <?php foreach ($selections as $selection): ?>
                        <div class="glass-card p-8 group hover:scale-[1.01] transition-all">
                            <div class="flex flex-col lg:flex-row justify-between gap-8">
                                <!-- Provider Quick Info -->
                                <div class="flex items-center gap-6 lg:w-1/3">
                                    <div class="w-20 h-20 bg-primary/10 rounded-3xl flex items-center justify-center text-primary text-3xl font-black shadow-inner">
                                        <?php echo strtoupper(substr($selection['provider_name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <h3 class="text-2xl font-black text-gray-900 mb-1"><?php echo htmlspecialchars($selection['provider_name']); ?></h3>
                                        <div class="flex items-center gap-2">
                                            <span class="px-3 py-1 rounded-lg bg-gray-100 text-[10px] font-black text-gray-500 uppercase tracking-widest border border-gray-200">
                                                <?php echo $currentLang === 'en' ? $selection['category_name'] : $selection['category_name_bn']; ?>
                                            </span>
                                            <span class="text-xs font-bold text-gray-400">• <?php echo formatDate($selection['created_at']); ?></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Status and Details -->
                                <div class="flex-grow grid grid-cols-2 md:grid-cols-3 gap-6">
                                    <div class="bg-gray-50/50 p-5 rounded-2xl border border-gray-100">
                                        <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2"><?php echo $currentLang === 'en' ? 'Selection Status' : 'অবস্থা'; ?></div>
                                        <div class="flex items-center gap-2">
                                            <div class="w-2 h-2 rounded-full <?php echo $selection['status'] === 'pending' ? 'bg-amber-400' : 'bg-emerald-400'; ?> anim-pulse"></div>
                                            <span class="font-bold text-gray-800"><?php echo t($selection['status']); ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="bg-gray-50/50 p-5 rounded-2xl border border-gray-100">
                                        <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2"><?php echo $currentLang === 'en' ? 'Admin Approval' : 'অনুমোদন'; ?></div>
                                        <?php if (isset($selection['admin_approved']) && $selection['admin_approved']): ?>
                                            <div class="flex items-center gap-2 text-emerald-600 font-black">
                                                <i class="fas fa-check-circle"></i>
                                                <span><?php echo $currentLang === 'en' ? 'Approved' : 'অনুমোদিত'; ?></span>
                                            </div>
                                        <?php else: ?>
                                            <div class="flex items-center gap-2 text-amber-500 font-black italic">
                                                <i class="fas fa-hourglass-half"></i>
                                                <span><?php echo $currentLang === 'en' ? 'Waiting' : 'অপেক্ষমান'; ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="col-span-2 md:col-span-1 bg-gray-50/50 p-5 rounded-2xl border border-gray-100">
                                        <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1"><?php echo $currentLang === 'en' ? 'Service Window' : 'সময়'; ?></div>
                                        <div class="font-bold text-gray-800"><?php echo formatDate($selection['preferred_date']); ?></div>
                                        <div class="text-xs font-medium text-gray-500"><?php echo $selection['preferred_time']; ?></div>
                                    </div>
                                </div>

                                <!-- Action Section -->
                                <div class="lg:w-72 flex flex-col justify-center gap-3">
                                    <?php $isApproved = isset($selection['admin_approved']) && $selection['admin_approved']; ?>
                                    <?php if ($isApproved): ?>
                                        <a href="tel:<?php echo $selection['provider_phone']; ?>" class="w-full btn-primary py-4 flex items-center justify-center gap-2">
                                            <i class="fas fa-phone-alt"></i>
                                            <span><?php echo $currentLang === 'en' ? 'Direct Call' : 'কল করুন'; ?></span>
                                        </a>
                                        <a href="https://wa.me/<?php echo $selection['provider_phone']; ?>" target="_blank" class="w-full bg-emerald-500 hover:bg-emerald-600 text-white font-bold py-4 rounded-xl flex items-center justify-center gap-2 shadow-lg shadow-emerald-500/20 transition-all">
                                            <i class="fab fa-whatsapp text-lg"></i>
                                            <span>WhatsApp</span>
                                        </a>
                                    <?php else: ?>
                                        <button class="w-full bg-gray-100 text-gray-400 font-bold py-4 rounded-xl flex items-center justify-center gap-2 cursor-not-allowed opacity-60">
                                            <i class="fas fa-lock"></i>
                                            <span><?php echo $currentLang === 'en' ? 'Call Locked' : 'কল লকড'; ?></span>
                                        </button>
                                        <div class="text-[10px] font-black text-amber-500 text-center uppercase tracking-tighter">
                                            <i class="fas fa-shield-alt mr-1"></i>
                                            <?php echo $currentLang === 'en' ? 'Awaiting Security Protocol' : 'অ্যাডমিন অনুমোদনের প্রয়োজন'; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Detailed Accordion Info -->
                            <div class="mt-8 pt-8 border-t border-gray-100 grid grid-cols-1 md:grid-cols-2 gap-8">
                                <div>
                                    <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3"><?php echo $currentLang === 'en' ? 'Service Requirements' : 'সেবার বিবরণ'; ?></div>
                                    <p class="text-gray-700 font-medium leading-relaxed italic">
                                        "<?php echo htmlspecialchars($selection['customer_notes'] ?: 'No additional notes provided.'); ?>"
                                    </p>
                                </div>
                                <div class="bg-primary/5 p-6 rounded-2xl border border-primary/10">
                                    <div class="flex items-start gap-4">
                                        <div class="w-10 h-10 bg-primary/10 rounded-xl flex items-center justify-center text-primary">
                                            <i class="fas fa-map-marker-alt"></i>
                                        </div>
                                        <div>
                                            <div class="text-[10px] font-black text-primary/60 uppercase tracking-widest mb-1"><?php echo $currentLang === 'en' ? 'Deployment Location' : 'সেবার অবস্থান'; ?></div>
                                            <div class="text-sm font-bold text-gray-800 leading-relaxed"><?php echo htmlspecialchars($selection['customer_address'] ?: 'N/A'); ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?php if ($selection['status'] === 'pending' && $isApproved): ?>
                                <div class="mt-6">
                                    <button onclick="markAsContacted(<?php echo $selection['id']; ?>)" class="text-xs font-black text-primary hover:underline uppercase tracking-widest">
                                        <i class="fas fa-check-circle mr-1"></i>
                                        <?php echo $currentLang === 'en' ? 'Register Contact' : 'যোগাযোগ করা হয়েছে হিসেবে চিহ্নিত করুন'; ?>
                                    </button>
                                </div>
                            <?php endif; ?>
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
        function markAsContacted(selectionId) {
            if (confirm('<?php echo $currentLang === 'en' ? 'Mark this selection as contacted?' : 'এই নির্বাচন যোগাযোগ করা হয়েছে হিসেবে চিহ্নিত করবেন?'; ?>')) {
                window.location.href = `update_selection_status.php?id=${selectionId}&status=contacted`;
            }
        }

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