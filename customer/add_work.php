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

// Get service categories
$categories = fetchAll("SELECT * FROM service_categories WHERE is_active = 1 ORDER BY name");

// Get Dhaka locations
$locations = getDhakaLocations();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_id = (int)$_POST['category_id'];
    $location = sanitizeInput($_POST['location']);
    $description = sanitizeInput($_POST['description']);
    $budget_min = (float)$_POST['budget_min'];
    $budget_max = (float)$_POST['budget_max'];
    $preferred_date = $_POST['preferred_date'];
    $contact_phone = sanitizeInput($_POST['contact_phone']);
    
    // Validation
    if (empty($category_id) || empty($location) || empty($description)) {
        $error = $currentLang === 'en' ? 'Please fill in all required fields' : 'সব প্রয়োজনীয় ক্ষেত্র পূরণ করুন';
    } elseif ($budget_min > $budget_max) {
        $error = $currentLang === 'en' ? 'Minimum budget cannot be greater than maximum budget' : 'ন্যূনতম বাজেট সর্বোচ্চ বাজেটের চেয়ে বেশি হতে পারে না';
    } elseif ($preferred_date < date('Y-m-d')) {
        $error = $currentLang === 'en' ? 'Preferred date cannot be in the past' : 'পছন্দের তারিখ অতীতে হতে পারে না';
    } else {
        // Insert work request
        $sql = "INSERT INTO work_requests (customer_id, category_id, location, description, budget_min, budget_max, preferred_date, contact_phone, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'open')";
        
        if (executeQuery($sql, [$user['id'], $category_id, $location, $description, $budget_min, $budget_max, $preferred_date, $contact_phone])) {
            $success = $currentLang === 'en' ? 'Work request posted successfully! Redirecting...' : 'কাজের অনুরোধ সফলভাবে পোস্ট হয়েছে! রিডাইরেক্ট করা হচ্ছে...';
            setFlashMessage('success', $success);
            redirect('my_work_requests.php');
        } else {
            $error = $currentLang === 'en' ? 'Failed to post work request. Please try again.' : 'কাজের অনুরোধ পোস্ট করতে ব্যর্থ। আবার চেষ্টা করুন।';
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
    <title><?php echo $currentLang === 'en' ? 'Broadcast Mission' : 'কাজের অনুরোধ যোগ করুন'; ?> - HOME SERVICE</title>
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
                </div>
                <div class="hidden md:flex items-center space-x-6">
                    <a href="dashboard.php" class="font-bold text-gray-700 hover:text-primary transition-colors">
                        <i class="fas fa-home mr-1"></i>
                        <?php echo $currentLang === 'en' ? 'Dashboard' : 'ড্যাশবোর্ড'; ?>
                    </a>
                    <a href="my_work_requests.php" class="font-bold text-gray-700 hover:text-primary transition-colors">
                        <i class="fas fa-list mr-1"></i>
                        <?php echo $currentLang === 'en' ? 'My Hub' : 'আমার হাব'; ?>
                    </a>
                </div>
            </div>
        </nav>
    </header>

    <div class="container mx-auto px-4 py-12 relative z-10">
        <div class="max-w-3xl mx-auto">
            <!-- Form Card -->
            <div class="glass-card p-10 md:p-16 anim-pop-in">
                <div class="flex items-center gap-6 mb-12 pb-8 border-b border-gray-100">
                    <div class="w-16 h-16 bg-primary/10 rounded-2xl flex items-center justify-center text-primary text-3xl shadow-xl shadow-primary/5">
                        <i class="fas fa-paper-plane"></i>
                    </div>
                    <div>
                        <h1 class="text-4xl font-black text-gray-900 tracking-tighter mb-2"><?php echo t('Post a Work Request'); ?></h1>
                        <p class="text-gray-500 font-medium"><?php echo t('Broadcast your mission to verified professionals'); ?></p>
                    </div>
                </div>

                <?php if ($error || $success): ?>
                    <div class="mb-10 p-5 bg-<?php echo $success ? 'emerald' : 'rose'; ?>-50 text-<?php echo $success ? 'emerald' : 'rose'; ?>-600 rounded-2xl border border-<?php echo $success ? 'emerald' : 'rose'; ?>-100 font-bold flex items-center gap-3 anim-shake">
                        <i class="fas <?php echo $success ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                        <span><?php echo $error ?: $success; ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-10">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                        <!-- Category -->
                        <div class="space-y-3">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1"><?php echo t('Mission Category'); ?> *</label>
                            <div class="relative group">
                                <i class="fas fa-th-large absolute left-5 top-1/2 -translate-y-1/2 text-gray-300 group-focus-within:text-primary transition-colors"></i>
                                <select name="category_id" required class="w-full pl-14 pr-6 py-5 bg-gray-50 border-2 border-transparent rounded-2xl focus:bg-white focus:border-primary focus:outline-none font-bold text-gray-900 transition-all shadow-inner appearance-none">
                                    <option value=""><?php echo t('Select sector'); ?></option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>">
                                            <?php echo t($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <i class="fas fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 text-gray-300 pointer-events-none"></i>
                            </div>
                        </div>

                        <!-- Location -->
                        <div class="space-y-3">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1"><?php echo t('Deployment Area'); ?> *</label>
                            <div class="relative group">
                                <i class="fas fa-map-marker-alt absolute left-5 top-1/2 -translate-y-1/2 text-gray-300 group-focus-within:text-primary transition-colors"></i>
                                <select name="location" required class="w-full pl-14 pr-6 py-5 bg-gray-50 border-2 border-transparent rounded-2xl focus:bg-white focus:border-primary focus:outline-none font-bold text-gray-900 transition-all shadow-inner appearance-none">
                                    <option value=""><?php echo t('Select zone'); ?></option>
                                    <?php foreach ($locations[$currentLang] as $location): ?>
                                        <option value="<?php echo $locations['en'][array_search($location, $locations[$currentLang])]; ?>">
                                            <?php echo $location; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <i class="fas fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 text-gray-300 pointer-events-none"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="space-y-3">
                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1"><?php echo t('Mission Brief / Details'); ?> *</label>
                        <div class="relative group">
                            <i class="fas fa-align-left absolute left-5 top-5 text-gray-300 group-focus-within:text-primary transition-colors"></i>
                            <textarea name="description" required rows="5" 
                                      placeholder="Provide details about the work, tools required, and expectations..."
                                      class="w-full pl-14 pr-6 py-5 bg-gray-50 border-2 border-transparent rounded-2xl focus:bg-white focus:border-primary focus:outline-none font-bold text-gray-900 transition-all shadow-inner"></textarea>
                        </div>
                    </div>

                    <!-- Budget & Date -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                        <div class="space-y-3">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1"><?php echo t('Min Budget'); ?> (৳)</label>
                            <input type="number" name="budget_min" placeholder="1000" class="w-full px-5 py-4 bg-gray-50 border-2 border-transparent rounded-2xl focus:bg-white focus:border-primary focus:outline-none font-bold text-gray-900 transition-all shadow-inner">
                        </div>
                        <div class="space-y-3">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1"><?php echo t('Max Budget'); ?> (৳)</label>
                            <input type="number" name="budget_max" placeholder="5000" class="w-full px-5 py-4 bg-gray-50 border-2 border-transparent rounded-2xl focus:bg-white focus:border-primary focus:outline-none font-bold text-gray-900 transition-all shadow-inner">
                        </div>
                        <div class="space-y-3">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1"><?php echo t('Target Date'); ?></label>
                            <input type="date" name="preferred_date" min="<?php echo date('Y-m-d'); ?>" class="w-full px-5 py-4 bg-gray-50 border-2 border-transparent rounded-2xl focus:bg-white focus:border-primary focus:outline-none font-bold text-gray-900 transition-all shadow-inner">
                        </div>
                    </div>

                    <!-- Contact -->
                    <div class="space-y-3">
                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1"><?php echo t('Secure Contact Number'); ?></label>
                        <div class="relative group">
                            <i class="fas fa-phone absolute left-5 top-1/2 -translate-y-1/2 text-gray-300 group-focus-within:text-primary transition-colors"></i>
                            <input type="tel" name="contact_phone" value="<?php echo htmlspecialchars($user['phone']); ?>"
                                   class="w-full pl-14 pr-6 py-5 bg-gray-50 border-2 border-transparent rounded-2xl focus:bg-white focus:border-primary focus:outline-none font-bold text-gray-900 transition-all shadow-inner">
                        </div>
                    </div>

                    <!-- Submit -->
                    <div class="pt-8 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-6">
                        <div class="flex items-center gap-3 text-emerald-500 bg-emerald-50 px-4 py-2 rounded-xl">
                            <i class="fas fa-shield-check"></i>
                            <span class="text-[10px] font-black uppercase tracking-widest">Safe & Secure Broadcast</span>
                        </div>
                        <button type="submit" class="w-full sm:w-auto btn-primary py-5 px-16 text-sm flex items-center justify-center gap-3">
                            <i class="fas fa-satellite-dish"></i>
                            <span><?php echo t('Broadcast Mission'); ?></span>
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Protocol Box -->
            <div class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="glass-card p-6 border-none bg-primary/5 text-center">
                    <i class="fas fa-user-shield text-primary mb-4 text-xl"></i>
                    <h4 class="text-[10px] font-black uppercase tracking-widest text-gray-900 mb-2">Verified Only</h4>
                    <p class="text-[10px] font-bold text-gray-400 leading-relaxed uppercase">Requests are visible only to verified background-checked pro's.</p>
                </div>
                <div class="glass-card p-6 border-none bg-emerald-500/5 text-center">
                    <i class="fas fa-gavel text-emerald-500 mb-4 text-xl"></i>
                    <h4 class="text-[10px] font-black uppercase tracking-widest text-gray-900 mb-2">Competitive Bidding</h4>
                    <p class="text-[10px] font-bold text-gray-400 leading-relaxed uppercase">Receive various quotes and choose the best fit for your budget.</p>
                </div>
                <div class="glass-card p-6 border-none bg-amber-500/5 text-center">
                    <i class="fas fa-headset text-amber-500 mb-4 text-xl"></i>
                    <h4 class="text-[10px] font-black uppercase tracking-widest text-gray-900 mb-2">24/7 Support</h4>
                    <p class="text-[10px] font-bold text-gray-400 leading-relaxed uppercase">Our team monitors every mission to ensure premium service quality.</p>
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