<?php
require_once '../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('../auth/login.php');
}

$currentLang = getLanguage();
$user = getCurrentUser();

$error = '';
$success = '';

// Get provider ID from URL
$providerId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Support alternate query param from search page
if (!$providerId && isset($_GET['provider_id'])) {
	$providerId = (int)$_GET['provider_id'];
}

if (!$providerId) {
    redirect('dashboard.php');
}

// Get provider details
$provider = fetchOne("
    SELECT sp.*, sc.name as category_name, sc.name_bn as category_name_bn
    FROM service_providers sp
    LEFT JOIN service_categories sc ON sp.category_id = sc.id
    WHERE sp.id = ? AND sp.is_active = 1
", [$providerId]);

if (!$provider) {
    redirect('dashboard.php');
}

// Check if customer has an approved selection for this provider
$existingSelection = fetchOne("
    SELECT * FROM customer_provider_selections 
    WHERE customer_id = ? AND provider_id = ? AND status IN ('pending', 'contacted', 'accepted')
", [$user['id'], $providerId]);
$isApproved = $existingSelection && $existingSelection['admin_approved'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $serviceType = sanitizeInput($_POST['service_type']);
    $preferredDate = $_POST['preferred_date'];
    $preferredTime = $_POST['preferred_time'];
    $customerAddress = sanitizeInput($_POST['customer_address']);
    $customerNotes = sanitizeInput($_POST['customer_notes']);
    $budgetMin = $_POST['budget_min'] ? (float)$_POST['budget_min'] : null;
    $budgetMax = $_POST['budget_max'] ? (float)$_POST['budget_max'] : null;
    
    // Validation
    if (empty($serviceType)) {
        $error = $currentLang === 'en' ? 'Service type is required' : 'সেবার ধরন প্রয়োজন';
    } elseif (empty($preferredDate)) {
        $error = $currentLang === 'en' ? 'Preferred date is required' : 'পছন্দের তারিখ প্রয়োজন';
    } elseif (strtotime($preferredDate) < strtotime('today')) {
        $error = $currentLang === 'en' ? 'Preferred date cannot be in the past' : 'পছন্দের তারিখ অতীত হতে পারে না';
    } elseif (empty($customerAddress)) {
        $error = $currentLang === 'en' ? 'Service address is required' : 'সেবার ঠিকানা প্রয়োজন';
    } elseif ($budgetMin && $budgetMax && $budgetMin > $budgetMax) {
        $error = $currentLang === 'en' ? 'Minimum budget cannot be greater than maximum budget' : 'ন্যূনতম বাজেট সর্বোচ্চ বাজেটের চেয়ে বেশি হতে পারে না';
    } else {
        try {
			// Create selection (allow multiple selections for same provider)
			executeQuery("
				INSERT INTO customer_provider_selections 
				(customer_id, provider_id, category_id, service_type, preferred_date, preferred_time, 
				 customer_address, customer_notes, budget_min, budget_max, status) 
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
			", [
				$user['id'], $providerId, $provider['category_id'], $serviceType, 
				$preferredDate, $preferredTime, $customerAddress, $customerNotes, 
				$budgetMin, $budgetMax
			]);
			
            setFlashMessage('success', $currentLang === 'en' ? 'Selection request submitted! Redirecting to tracking...' : 'অনুরোধ জমা দেওয়া হয়েছে! ট্র্যাকিং-এ রিডাইরেক্ট করা হচ্ছে...');
 			redirect('my_selections.php');
		} catch (Exception $e) {
			$error = $currentLang === 'en' ? 'Failed to submit request. Please try again.' : 'অনুরোধ জমা দিতে ব্যর্থ। আবার চেষ্টা করুন।';
		}
    }
}

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
    <title><?php echo $currentLang === 'en' ? 'Select Provider' : 'প্রদানকারী নির্বাচন করুন'; ?> - HOME SERVICE</title>
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
                    <a href="../index.php" class="text-3xl font-black text-gradient">
                        HOME SERVICE
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
                    <a href="my_selections.php" class="font-bold text-gray-700 hover:text-primary transition-colors">
                        <i class="fas fa-heart mr-1"></i>
                        <?php echo $currentLang === 'en' ? 'Selections' : 'নির্বাচন'; ?>
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
        <div class="max-w-6xl mx-auto flex flex-col lg:flex-row gap-10">
            <!-- Left: Provider Profile Card -->
            <div class="lg:w-1/3">
                <div class="glass-card sticky top-28 overflow-hidden">
                    <div class="p-8 text-center bg-gradient-to-br from-primary/5 to-secondary/5 border-b border-gray-100">
                        <div class="relative inline-block mb-6">
                            <?php if ($provider['profile_picture']): ?>
                                <img src="../uploads/<?php echo htmlspecialchars($provider['profile_picture']); ?>" alt="Provider" class="w-32 h-32 rounded-3xl object-cover shadow-2xl border-4 border-white">
                            <?php else: ?>
                                <div class="w-32 h-32 bg-primary/10 rounded-3xl flex items-center justify-center border-4 border-white shadow-xl">
                                    <i class="fas fa-user-tie text-primary text-5xl"></i>
                                </div>
                            <?php endif; ?>
                            <div class="absolute -bottom-2 -right-2 w-10 h-10 bg-emerald-500 rounded-2xl flex items-center justify-center text-white border-4 border-white shadow-lg anim-pulse">
                                <i class="fas fa-check text-xs"></i>
                            </div>
                        </div>
                        
                        <h1 class="text-3xl font-black text-gray-900 tracking-tighter mb-1"><?php echo htmlspecialchars($provider['name']); ?></h1>
                        <p class="text-primary font-black text-[10px] uppercase tracking-widest bg-primary/10 px-4 py-1.5 rounded-full inline-block">
                            <?php echo t($provider['category_name']); ?>
                        </p>
                    </div>
                    
                    <div class="p-8 space-y-8">
                        <?php if ($provider['description']): ?>
                            <div>
                                <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3"><?php echo t('About Provider'); ?></h3>
                                <p class="text-gray-600 font-medium leading-relaxed italic text-sm">"<?php echo htmlspecialchars($provider['description']); ?>"</p>
                            </div>
                        <?php endif; ?>

                        <div class="grid grid-cols-1 gap-4">
                            <div class="flex items-center gap-4 bg-gray-50/50 p-4 rounded-2xl border border-gray-100 transition-all hover:bg-white group">
                                <div class="w-10 h-10 rounded-xl bg-white flex items-center justify-center text-primary shadow-sm group-hover:scale-110 transition-transform">
                                    <i class="fas fa-map-marked-alt"></i>
                                </div>
                                <div>
                                    <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest"><?php echo t('Areas'); ?></div>
                                    <div class="text-xs font-bold text-gray-800"><?php echo htmlspecialchars($provider['service_areas'] ?: 'Full City'); ?></div>
                                </div>
                            </div>

                            <div class="flex items-center gap-4 bg-primary/5 p-4 rounded-2xl border border-primary/10 transition-all hover:bg-white group">
                                <div class="w-10 h-10 rounded-xl bg-primary flex items-center justify-center text-white shadow-lg group-hover:scale-110 transition-transform">
                                    <i class="fas fa-coins"></i>
                                </div>
                                <div>
                                    <div class="text-[10px] font-black text-primary/60 uppercase tracking-widest"><?php echo t('Hourly Rate'); ?></div>
                                    <div class="text-xl font-black text-primary">৳<?php echo number_format($provider['hourly_rate']); ?></div>
                                </div>
                            </div>
                        </div>

                        <?php if (!$isApproved): ?>
                            <div class="bg-amber-50 rounded-2xl p-6 border border-amber-100 flex gap-4">
                                <div class="w-10 h-10 bg-amber-100 rounded-xl flex items-center justify-center text-amber-600 shrink-0">
                                    <i class="fas fa-shield-alt"></i>
                                </div>
                                <div>
                                    <h4 class="text-xs font-black text-amber-900 uppercase tracking-widest mb-1"><?php echo t('Secure Booking'); ?></h4>
                                    <p class="text-[10px] font-bold text-amber-700 leading-relaxed uppercase tracking-tighter">
                                        <?php echo $currentLang === 'en' 
                                            ? 'Contact info unlocked only after selection approval' 
                                            : 'অনুমোদনের আগে তথ্য গোপনীয় থাকবে'; ?>
                                    </p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right: Booking Form -->
            <div class="lg:w-2/3">
                <div class="glass-card p-10">
                    <div class="flex items-center gap-4 mb-10 pb-6 border-b border-gray-100">
                        <div class="w-12 h-12 bg-primary/10 rounded-xl flex items-center justify-center text-primary">
                            <i class="fas fa-calendar-check text-2xl"></i>
                        </div>
                        <div>
                            <h2 class="text-3xl font-black text-gray-900 tracking-tighter"><?php echo t('Request This Pro'); ?></h2>
                            <p class="text-gray-500 font-medium"><?php echo t('Configure your service package below'); ?></p>
                        </div>
                    </div>

                    <?php if ($error): ?>
                        <div class="mb-8 p-5 bg-rose-50 text-rose-600 rounded-2xl border border-rose-100 font-bold flex items-center gap-3 anim-shake">
                            <i class="fas fa-exclamation-circle"></i>
                            <span><?php echo $error; ?></span>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="space-y-8">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div class="space-y-3">
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1"><?php echo t('Specific Task Type'); ?> *</label>
                                <div class="relative group">
                                    <i class="fas fa-tools absolute left-4 top-4 text-gray-300 group-focus-within:text-primary transition-colors"></i>
                                    <input type="text" name="service_type" value="<?php echo htmlspecialchars($_POST['service_type'] ?? ''); ?>" required
                                           placeholder="e.g., Deep Clean, Leak Fix" 
                                           class="w-full pl-12 pr-4 py-4 bg-gray-50 border-2 border-transparent rounded-2xl focus:bg-white focus:border-primary focus:outline-none font-bold text-gray-900 transition-all shadow-inner">
                                </div>
                            </div>
                            
                            <div class="space-y-3">
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1"><?php echo t('Deployment Date'); ?> *</label>
                                <div class="relative group">
                                    <i class="fas fa-calendar-day absolute left-4 top-4 text-gray-300 group-focus-within:text-primary transition-colors"></i>
                                    <input type="date" name="preferred_date" value="<?php echo htmlspecialchars($_POST['preferred_date'] ?? date('Y-m-d')); ?>" 
                                           min="<?php echo date('Y-m-d'); ?>" required
                                           class="w-full pl-12 pr-4 py-4 bg-gray-50 border-2 border-transparent rounded-2xl focus:bg-white focus:border-primary focus:outline-none font-bold text-gray-900 transition-all shadow-inner">
                                </div>
                            </div>
                            
                            <div class="space-y-3">
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1"><?php echo t('Start Time'); ?> *</label>
                                <div class="relative group">
                                    <i class="fas fa-clock absolute left-4 top-4 text-gray-300 group-focus-within:text-primary transition-colors"></i>
                                    <input type="time" name="preferred_time" value="<?php echo htmlspecialchars($_POST['preferred_time'] ?? '10:00'); ?>" required
                                           class="w-full pl-12 pr-4 py-4 bg-gray-50 border-2 border-transparent rounded-2xl focus:bg-white focus:border-primary focus:outline-none font-bold text-gray-900 transition-all shadow-inner">
                                </div>
                            </div>
                            
                            <div class="space-y-3">
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1"><?php echo t('Budget Expectations'); ?></label>
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="relative">
                                        <input type="number" name="budget_min" placeholder="Min" value="<?php echo htmlspecialchars($_POST['budget_min'] ?? ''); ?>"
                                               class="w-full px-4 py-4 bg-gray-50 border-2 border-transparent rounded-2xl focus:bg-white focus:border-primary focus:outline-none font-bold text-gray-900 transition-all shadow-inner">
                                    </div>
                                    <div class="relative">
                                        <input type="number" name="budget_max" placeholder="Max" value="<?php echo htmlspecialchars($_POST['budget_max'] ?? ''); ?>"
                                               class="w-full px-4 py-4 bg-gray-50 border-2 border-transparent rounded-2xl focus:bg-white focus:border-primary focus:outline-none font-bold text-gray-900 transition-all shadow-inner">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="space-y-3">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1"><?php echo t('Service Deployment Address'); ?> *</label>
                            <div class="relative group">
                                <i class="fas fa-map-pin absolute left-4 top-4 text-gray-300 group-focus-within:text-primary transition-colors"></i>
                                <textarea name="customer_address" rows="1" required
                                          placeholder="Enter full street address"
                                          class="w-full pl-12 pr-4 py-4 bg-gray-50 border-2 border-transparent rounded-2xl focus:bg-white focus:border-primary focus:outline-none font-bold text-gray-900 transition-all shadow-inner min-h-[58px]"><?php echo htmlspecialchars($_POST['customer_address'] ?? ''); ?></textarea>
                            </div>
                        </div>
                        
                        <div class="space-y-3">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1"><?php echo t('Additional Context / Notes'); ?></label>
                            <div class="relative group">
                                <i class="fas fa-pen-nib absolute left-4 top-4 text-gray-300 group-focus-within:text-primary transition-colors"></i>
                                <textarea name="customer_notes" rows="4" 
                                          placeholder="Share any special instructions..."
                                          class="w-full pl-12 pr-4 py-4 bg-gray-50 border-2 border-transparent rounded-2xl focus:bg-white focus:border-primary focus:outline-none font-bold text-gray-900 transition-all shadow-inner"><?php echo htmlspecialchars($_POST['customer_notes'] ?? ''); ?></textarea>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between pt-6 border-t border-gray-100">
                            <a href="dashboard.php" class="text-xs font-black text-gray-400 hover:text-rose-500 uppercase tracking-widest transition-colors">
                                <i class="fas fa-times mr-1"></i> <?php echo t('Abort Selection'); ?>
                            </a>
                            <button type="submit" class="btn-primary py-4 px-12 text-sm flex items-center gap-3">
                                <i class="fas fa-shield-alt"></i>
                                <span><?php echo t('Initiate Selection'); ?></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="glass-nav py-12 mt-20">
        <div class="container mx-auto px-4 text-center">
            <p class="text-gray-500 font-black text-sm uppercase tracking-widest">&copy; <?php echo date('Y'); ?> HOME SERVICE PREMIUM SOLUTIONS. ALL RIGHTS RESERVED.</p>
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