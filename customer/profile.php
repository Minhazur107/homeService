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

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    redirect('../index.php');
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name']);
    $email = sanitizeInput($_POST['email']);
    $location = sanitizeInput($_POST['location']);
    
    // Validation
    if (empty($name)) {
        $error = $currentLang === 'en' ? 'Name is required' : 'নাম প্রয়োজন';
    } elseif ($email && !validateEmail($email)) {
        $error = $currentLang === 'en' ? 'Please enter a valid email address' : 'সঠিক ইমেইল ঠিকানা লিখুন';
    } else {
        // Check if email already exists (if changed)
        if ($email && $email !== $user['email']) {
            $existingEmail = fetchOne("SELECT id FROM users WHERE email = ? AND id != ?", [$email, $user['id']]);
            if ($existingEmail) {
                $error = $currentLang === 'en' ? 'Email address already registered' : 'ইমেইল ঠিকানা ইতিমধ্যে নিবন্ধিত';
            }
        }
        
        if (!$error) {
            // Update user profile
            $sql = "UPDATE users SET name = ?, email = ?, location = ? WHERE id = ?";
            try {
                executeQuery($sql, [$name, $email ?: null, $location, $user['id']]);
                $success = $currentLang === 'en' ? 'Profile updated successfully!' : 'প্রোফাইল সফলভাবে আপডেট হয়েছে!';
                // Refresh user data
                $user = getCurrentUser();
            } catch (Exception $e) {
                $error = $currentLang === 'en' ? 'Update failed. Please try again.' : 'আপডেট ব্যর্থ হয়েছে। আবার চেষ্টা করুন।';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $currentLang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $currentLang === 'en' ? 'My Profile' : 'আমার প্রোফাইল'; ?> - Home Service</title>
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
                    <a href="../index.php" class="text-3xl font-black tracking-tighter text-gradient uppercase">
                        Home Service
                    </a>
                    <!-- Theme Picker -->
                    <div class="theme-picker" data-theme-picker>
                        <button class="w-10 h-10 rounded-xl bg-white border border-gray-200 flex items-center justify-center text-primary shadow-sm hover:scale-110 transition-transform" data-toggle>
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
                    <span class="text-gray-700 font-bold text-lg">
                        <i class="fas fa-user text-primary mr-2"></i>
                        <?php echo $currentLang === 'en' ? 'My Profile' : 'আমার প্রোফাইল'; ?>
                    </span>
                </div>
                
                <div class="flex items-center space-x-4">
                    <a href="dashboard.php" class="font-bold text-gray-700 hover:text-primary transition-colors">
                        <i class="fas fa-tachometer-alt"></i>
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
                        <i class="fas fa-star"></i>
                        <?php echo $currentLang === 'en' ? 'Reviews' : 'পর্যালোচনা'; ?>
                    </a>
                    <a href="notifications.php" class="font-bold text-gray-700 hover:text-primary transition-colors">
                        <i class="fas fa-bell"></i>
                        <?php echo $currentLang === 'en' ? 'Notifications' : 'বিজ্ঞপ্তি'; ?>
                    </a>
                    <a href="my_selections.php" class="font-bold text-gray-700 hover:text-primary transition-colors">
                        <i class="fas fa-heart"></i>
                        <?php echo $currentLang === 'en' ? 'Selections' : 'নির্বাচন'; ?>
                    </a>
                    <a href="payments.php" class="font-bold text-gray-700 hover:text-primary transition-colors">
                        <i class="fas fa-credit-card"></i>
                        <?php echo $currentLang === 'en' ? 'Payments' : 'পেমেন্ট'; ?>
                    </a>
                    <a href="profile.php" class="font-bold text-primary transition-colors border-b-2 border-primary">
                        <i class="fas fa-user"></i>
                        <?php echo $currentLang === 'en' ? 'Profile' : 'প্রোফাইল'; ?>
                    </a>
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-primary to-secondary flex items-center justify-center text-white font-bold shadow-lg">
                            <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                        </div>
                        <div class="text-right hidden sm:block">
                            <div class="font-bold text-gray-800 leading-none"><?php echo htmlspecialchars($user['name']); ?></div>
                            <div class="text-[10px] font-black uppercase text-primary tracking-widest mt-1"><?php echo $currentLang === 'en' ? 'Customer' : 'গ্রাহক'; ?></div>
                        </div>
                    </div>
                    <a href="?logout=true" class="btn-primary py-2 px-4 text-xs">
                        <i class="fas fa-sign-out-alt mr-2"></i>
                        <?php echo $currentLang === 'en' ? 'Logout' : 'লগআউট'; ?>
                    </a>
                </div>
            </div>
        </nav>
    </header>

    <div class="container mx-auto px-4 py-8 relative z-10">
        <div class="max-w-2xl mx-auto">
            <!-- Profile Form -->
            <div class="glass-card p-8">
                <h1 class="text-3xl font-black text-gray-900 mb-6">
                    <i class="fas fa-user-edit mr-3"></i>
                    <?php echo $currentLang === 'en' ? 'Profile Information' : 'প্রোফাইল তথ্য'; ?>
                </h1>

                <?php if ($error): ?>
                    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6 flex items-center">
                        <i class="fas fa-check-circle mr-2"></i>
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-user mr-2 text-purple-600"></i>
                            <?php echo $currentLang === 'en' ? 'Full Name' : 'পূর্ণ নাম'; ?> *
                        </label>
                        <input type="text" name="name" required 
                               value="<?php echo htmlspecialchars($user['name']); ?>"
                               class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent text-black">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-envelope mr-2 text-blue-600"></i>
                            <?php echo $currentLang === 'en' ? 'Email Address' : 'ইমেইল ঠিকানা'; ?>
                        </label>
                        <input type="email" name="email" 
                               value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>"
                               class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent text-black">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-phone mr-2 text-green-600"></i>
                            <?php echo $currentLang === 'en' ? 'Phone Number' : 'ফোন নম্বর'; ?>
                        </label>
                        <input type="tel" value="<?php echo htmlspecialchars($user['phone']); ?>" 
                               class="w-full p-3 border border-gray-300 rounded-lg bg-gray-50 text-black" readonly>
                        <p class="text-sm text-gray-500 mt-1 flex items-center">
                            <i class="fas fa-info-circle mr-1"></i>
                            <?php echo $currentLang === 'en' ? 'Phone number cannot be changed' : 'ফোন নম্বর পরিবর্তন করা যায় না'; ?>
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-map-marker-alt mr-2 text-red-600"></i>
                            <?php echo $currentLang === 'en' ? 'Location' : 'অবস্থান'; ?>
                        </label>
                        <input type="text" name="location" 
                               value="<?php echo htmlspecialchars($user['location'] ?? ''); ?>"
                               placeholder="<?php echo $currentLang === 'en' ? 'Enter your location' : 'আপনার অবস্থান লিখুন'; ?>"
                               class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent text-black">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-language mr-2 text-orange-600"></i>
                            <?php echo $currentLang === 'en' ? 'Language Preference' : 'ভাষার পছন্দ'; ?>
                        </label>
                        <select name="language" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent text-black">
                            <option value="en" <?php echo $user['language'] === 'en' ? 'selected' : ''; ?>>English</option>
                            <option value="bn" <?php echo $user['language'] === 'bn' ? 'selected' : ''; ?>>বাংলা</option>
                        </select>
                    </div>

                    <div class="flex space-x-4 pt-4">
                        <button type="submit" class="flex-1 btn-primary py-3 px-4">
                            <i class="fas fa-save mr-2"></i>
                            <?php echo $currentLang === 'en' ? 'Update Profile' : 'প্রোফাইল আপডেট করুন'; ?>
                        </button>
                        <a href="dashboard.php" class="flex-1 bg-gray-600 text-white py-3 px-4 rounded-lg font-bold hover:bg-gray-700 transition duration-300 text-center">
                            <i class="fas fa-times mr-2"></i>
                            <?php echo $currentLang === 'en' ? 'Cancel' : 'বাতিল'; ?>
                        </a>
                    </div>
                </form>
            </div>

            <!-- Account Information -->
            <div class="glass-card p-8 mt-8">
                <h2 class="text-xl font-bold text-gray-800 mb-6">
                    <i class="fas fa-info-circle text-purple-600 mr-2"></i>
                    <?php echo $currentLang === 'en' ? 'Account Information' : 'অ্যাকাউন্ট তথ্য'; ?>
                </h2>
                
                <div class="space-y-4">
                    <div class="flex justify-between items-center py-3 border-b border-gray-200">
                        <span class="text-gray-600">
                            <i class="fas fa-calendar-plus mr-2 text-purple-600"></i>
                            <?php echo $currentLang === 'en' ? 'Member Since' : 'সদস্য হওয়ার তারিখ'; ?>
                        </span>
                        <span class="font-medium"><?php echo formatDate($user['created_at']); ?></span>
                    </div>
                    
                    <div class="flex justify-between items-center py-3 border-b border-gray-200">
                        <span class="text-gray-600">
                            <i class="fas fa-calendar-check mr-2 text-blue-600"></i>
                            <?php echo $currentLang === 'en' ? 'Total Bookings' : 'মোট বুকিং'; ?>
                        </span>
                        <span class="font-medium">
                            <?php 
                            $totalBookings = fetchOne("SELECT COUNT(*) as count FROM bookings WHERE customer_id = ?", [$user['id']]);
                            echo $totalBookings['count'];
                            ?>
                        </span>
                    </div>
                    
                    <div class="flex justify-between items-center py-3">
                        <span class="text-gray-600">
                            <i class="fas fa-check-circle mr-2 text-green-600"></i>
                            <?php echo $currentLang === 'en' ? 'Completed Services' : 'সম্পন্ন সেবা'; ?>
                        </span>
                        <span class="font-medium">
                            <?php 
                            $completedBookings = fetchOne("SELECT COUNT(*) as count FROM bookings WHERE customer_id = ? AND status = 'completed'", [$user['id']]);
                            echo $completedBookings['count'];
                            ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-white bg-opacity-95 backdrop-blur-sm border-t border-gray-200 py-8 mt-12 relative z-10">
        <div class="container mx-auto px-4 text-center">
            <p class="text-gray-600">&copy; <?php echo date('Y'); ?> Home Service. <?php echo $currentLang === 'en' ? 'All rights reserved.' : 'সর্বস্বত্ব সংরক্ষিত।'; ?></p>
        </div>
    </footer>

    <script src="../assets/ui.js"></script>
    <script>
        // Add smooth scroll behavior
        document.documentElement.style.scrollBehavior = 'smooth';
        
        // Add intersection observer for animation
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);
        
        // Observe all cards
        document.querySelectorAll('.glass-card').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(card);
        });
    </script>
</body>
</html> 