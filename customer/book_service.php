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
$providerId = $_GET['provider_id'] ?? 0;

if (!$providerId) {
    redirect('../search.php');
}

// Get provider details
$provider = fetchOne("
    SELECT sp.*, sc.name as category_name, sc.name_bn as category_name_bn
    FROM service_providers sp
    JOIN service_categories sc ON sp.category_id = sc.id
    WHERE sp.id = ? AND sp.verification_status = 'verified' AND sp.is_active = 1
", [$providerId]);

if (!$provider) {
    redirect('../search.php');
}

// Handle booking submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $serviceType = sanitizeInput($_POST['service_type']);
    $bookingDate = $_POST['booking_date'];
    $bookingTime = $_POST['booking_time'];
    $notes = sanitizeInput($_POST['notes']);
    
    // Validation
    if (empty($serviceType) || empty($bookingDate) || empty($bookingTime)) {
        $error = $currentLang === 'en' ? 'Please fill in all required fields' : 'সব প্রয়োজনীয় ক্ষেত্র পূরণ করুন';
    } elseif (strtotime($bookingDate) < strtotime(date('Y-m-d'))) {
        $error = $currentLang === 'en' ? 'Booking date cannot be in the past' : 'বুকিং তারিখ অতীত হতে পারে না';
    } else {
        // Check if the date/time is available (basic check)
        $existingBooking = fetchOne("
            SELECT id FROM bookings 
            WHERE provider_id = ? AND booking_date = ? AND booking_time = ? AND status IN ('pending', 'confirmed')
        ", [$providerId, $bookingDate, $bookingTime]);
        
        if ($existingBooking) {
            $error = $currentLang === 'en' ? 'This time slot is already booked. Please choose another time.' : 'এই সময় স্লট ইতিমধ্যে বুক করা আছে। অন্য সময় বেছে নিন।';
        } else {
            // Create booking
            $sql = "INSERT INTO bookings (customer_id, provider_id, category_id, service_type, booking_date, booking_time, notes, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')";
            $params = [$user['id'], $providerId, $provider['category_id'], $serviceType, $bookingDate, $bookingTime, $notes];
            
            try {
                executeQuery($sql, $params);
                $success = $currentLang === 'en' ? 'Booking request sent successfully! Redirecting...' : 'বুকিং অনুরোধ সফলভাবে পাঠানো হয়েছে! রিডাইরেক্ট করা হচ্ছে...';
                setFlashMessage('success', $success);
                redirect('bookings.php');
            } catch (Exception $e) {
                $error = $currentLang === 'en' ? 'Booking failed. Please try again.' : 'বুকিং ব্যর্থ হয়েছে। আবার চেষ্টা করুন।';
            }
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
    <title><?php echo $currentLang === 'en' ? 'Secure Booking' : 'সেবা বুক করুন'; ?> - HOME SERVICE</title>
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
                    <a href="bookings.php" class="font-bold text-gray-700 hover:text-primary transition-colors">
                        <i class="fas fa-calendar-alt mr-1"></i>
                        <?php echo $currentLang === 'en' ? 'My Bookings' : 'আমার বুকিং'; ?>
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
        <div class="max-w-5xl mx-auto flex flex-col lg:flex-row gap-10">
            <!-- Provider Sidebar -->
            <div class="lg:w-1/3">
                <div class="glass-card sticky top-28 overflow-hidden">
                    <div class="p-8 text-center bg-gradient-to-br from-primary/5 to-secondary/5 border-b border-gray-100">
                        <div class="relative inline-block mb-6">
                            <?php if ($provider['profile_picture']): ?>
                                <img src="../uploads/profiles/<?php echo $provider['profile_picture']; ?>" alt="Provider" class="w-24 h-24 rounded-3xl object-cover shadow-xl border-4 border-white">
                            <?php else: ?>
                                <div class="w-24 h-24 bg-primary/10 rounded-3xl flex items-center justify-center border-4 border-white shadow-lg">
                                    <i class="fas fa-user-tie text-primary text-3xl"></i>
                                </div>
                            <?php endif; ?>
                            <div class="absolute -bottom-2 -right-2 w-8 h-8 bg-emerald-500 rounded-xl flex items-center justify-center text-white border-2 border-white shadow-md">
                                <i class="fas fa-check text-[10px]"></i>
                            </div>
                        </div>
                        
                        <h1 class="text-2xl font-black text-gray-900 tracking-tighter mb-1"><?php echo htmlspecialchars($provider['name']); ?></h1>
                        <p class="text-[10px] font-black uppercase tracking-widest text-primary bg-primary/10 px-3 py-1 rounded-full inline-block">
                            <?php echo t($provider['category_name']); ?>
                        </p>
                    </div>

                    <div class="p-8 space-y-6">
                        <div class="flex items-center gap-4 text-sm font-bold text-gray-600">
                            <div class="w-10 h-10 rounded-xl bg-gray-50 flex items-center justify-center text-primary shadow-sm"><i class="fas fa-map-marker-alt"></i></div>
                            <span><?php echo htmlspecialchars($provider['service_areas']); ?></span>
                        </div>
                        <div class="flex items-center gap-4 text-sm font-bold text-gray-600">
                            <div class="w-10 h-10 rounded-xl bg-gray-50 flex items-center justify-center text-primary shadow-sm"><i class="fas fa-tag"></i></div>
                            <span>৳<?php echo number_format($provider['price_min']); ?> - ৳<?php echo number_format($provider['price_max']); ?></span>
                        </div>
                        <?php if ($provider['hourly_rate']): ?>
                            <div class="flex items-center gap-4 text-sm font-bold text-gray-600">
                                <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary shadow-sm"><i class="fas fa-clock"></i></div>
                                <span>৳<?php echo number_format($provider['hourly_rate']); ?> / hr</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="p-8 pt-0 flex gap-3">
                        <a href="tel:<?php echo $provider['phone']; ?>" class="flex-1 bg-emerald-500 hover:bg-emerald-600 text-white font-black text-[10px] uppercase tracking-widest py-4 rounded-2xl flex items-center justify-center gap-2 transition-all shadow-lg shadow-emerald-500/20">
                            <i class="fas fa-phone-alt"></i>
                            <span>Call</span>
                        </a>
                        <a href="https://wa.me/<?php echo $provider['phone']; ?>" class="flex-1 bg-gray-900 text-white font-black text-[10px] uppercase tracking-widest py-4 rounded-2xl flex items-center justify-center gap-2 hover:bg-black transition-all">
                            <i class="fab fa-whatsapp"></i>
                            <span>Chat</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Booking Main Content -->
            <div class="lg:w-2/3">
                <div class="glass-card p-10">
                    <div class="flex items-center gap-4 mb-10 pb-6 border-b border-gray-100">
                        <div class="w-12 h-12 bg-primary/10 rounded-xl flex items-center justify-center text-primary">
                            <i class="fas fa-calendar-check text-2xl"></i>
                        </div>
                        <div>
                            <h2 class="text-3xl font-black text-gray-900 tracking-tighter"><?php echo t('Direct Booking'); ?></h2>
                            <p class="text-gray-500 font-medium"><?php echo t('Secure your professional slot instantly'); ?></p>
                        </div>
                    </div>

                    <?php if ($error): ?>
                        <div class="mb-8 p-5 bg-rose-50 text-rose-600 rounded-2xl border border-rose-100 font-bold flex items-center gap-3 anim-shake">
                            <i class="fas fa-exclamation-circle"></i>
                            <span><?php echo $error; ?></span>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="space-y-10">
                        <!-- Service Type -->
                        <div class="space-y-3">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1"><?php echo t('Define Your Task'); ?> *</label>
                            <div class="relative group">
                                <i class="fas fa-tools absolute left-5 top-1/2 -translate-y-1/2 text-gray-300 group-focus-within:text-primary transition-colors"></i>
                                <input type="text" name="service_type" required 
                                       placeholder="e.g., Deep Kitchen Clean, AC Maintenance"
                                       class="w-full pl-14 pr-6 py-5 bg-gray-50 border-2 border-transparent rounded-2xl focus:bg-white focus:border-primary focus:outline-none font-bold text-gray-900 transition-all shadow-inner">
                            </div>
                        </div>

                        <!-- Date & Time Row -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div class="space-y-3">
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1"><?php echo t('Appointment Date'); ?> *</label>
                                <div class="relative group">
                                    <i class="fas fa-calendar-alt absolute left-5 top-1/2 -translate-y-1/2 text-gray-300 group-focus-within:text-primary transition-colors"></i>
                                    <input type="date" name="booking_date" required 
                                           min="<?php echo date('Y-m-d'); ?>"
                                           class="w-full pl-14 pr-6 py-5 bg-gray-50 border-2 border-transparent rounded-2xl focus:bg-white focus:border-primary focus:outline-none font-bold text-gray-900 transition-all shadow-inner">
                                </div>
                            </div>

                            <div class="space-y-3">
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1"><?php echo t('Preferred Window'); ?> *</label>
                                <div class="relative group">
                                    <i class="fas fa-clock absolute left-5 top-1/2 -translate-y-1/2 text-gray-300 group-focus-within:text-primary transition-colors"></i>
                                    <select name="booking_time" required class="w-full pl-14 pr-6 py-5 bg-gray-50 border-2 border-transparent rounded-2xl focus:bg-white focus:border-primary focus:outline-none font-bold text-gray-900 transition-all shadow-inner appearance-none">
                                        <option value="">Select time</option>
                                        <option value="09:00:00">09:00 AM</option>
                                        <option value="11:00:00">11:00 AM</option>
                                        <option value="13:00:00">01:00 PM</option>
                                        <option value="15:00:00">03:00 PM</option>
                                        <option value="17:00:00">05:00 PM</option>
                                        <option value="19:00:00">07:00 PM</option>
                                    </select>
                                    <i class="fas fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 text-gray-300 pointer-events-none"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Notes Area -->
                        <div class="space-y-3">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1"><?php echo t('Mission Brief / Instructions'); ?></label>
                            <div class="relative group">
                                <i class="fas fa-pen-nib absolute left-5 top-5 text-gray-300 group-focus-within:text-primary transition-colors"></i>
                                <textarea name="notes" rows="5" 
                                          placeholder="Specify tools needed, parking info, or entry codes..."
                                          class="w-full pl-14 pr-6 py-5 bg-gray-50 border-2 border-transparent rounded-2xl focus:bg-white focus:border-primary focus:outline-none font-bold text-gray-900 transition-all shadow-inner"></textarea>
                            </div>
                        </div>

                        <!-- Info Box -->
                        <div class="p-6 bg-primary/5 rounded-2xl border border-primary/10 flex gap-4">
                            <div class="w-10 h-10 bg-primary/10 rounded-xl flex items-center justify-center text-primary shrink-0">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <div class="text-xs font-medium text-gray-600 leading-relaxed uppercase tracking-tighter">
                                <p><?php echo $currentLang === 'en' ? 'Your booking request triggers a professional verification protocol. Confirmations are typically dispatched within 60 minutes.' : 'আপনার অনুরোধ প্রদানকারীর কাছে পাঠানো হবে। ৬০ মিনিটের মধ্যে উত্তর পাওয়া যাবে।'; ?></p>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="pt-6 border-t border-gray-100 flex flex-col sm:flex-row gap-6 items-center">
                            <button type="submit" class="w-full sm:flex-1 btn-primary py-5 text-sm flex items-center justify-center gap-3">
                                <i class="fas fa-check-circle"></i>
                                <span><?php echo t('Execute Booking'); ?></span>
                            </button>
                            <a href="../search.php" class="text-xs font-black text-gray-400 hover:text-rose-500 uppercase tracking-widest transition-colors">
                                <i class="fas fa-times mr-2"></i><?php echo t('Cancel'); ?>
                            </a>
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