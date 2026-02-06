<?php
require_once 'includes/functions.php';

$currentLang = getLanguage();
$user = getCurrentUser();

// Get search parameters
$searchQuery = isset($_GET['q']) ? sanitizeInput($_GET['q']) : '';
$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$location = isset($_GET['location']) ? sanitizeInput($_GET['location']) : '';
$minPrice = isset($_GET['min_price']) ? (int)$_GET['min_price'] : 0;
$maxPrice = isset($_GET['max_price']) ? (int)$_GET['max_price'] : 1000000;
$minRating = isset($_GET['rating']) ? (float)$_GET['rating'] : 0;

// Build search query
$sql = "SELECT sp.*, sc.name as category_name, sc.name_bn as category_name_bn, 
        COALESCE(AVG(r.rating), 0) as avg_rating, COUNT(DISTINCT r.id) as review_count,
        COUNT(DISTINCT b.id) as booking_count
        FROM service_providers sp
        LEFT JOIN service_categories sc ON sp.category_id = sc.id
        LEFT JOIN reviews r ON sp.id = r.provider_id
        LEFT JOIN bookings b ON sp.id = b.provider_id
        WHERE sp.verification_status = 'verified' AND sp.is_active = 1";

$params = [];

if ($searchQuery) {
    $sql .= " AND (sp.name LIKE ? OR sp.description LIKE ?)";
    $params[] = "%$searchQuery%";
    $params[] = "%$searchQuery%";
}

if ($categoryId) {
    $sql .= " AND sp.category_id = ?";
    $params[] = $categoryId;
}

if ($location) {
    $sql .= " AND sp.service_areas LIKE ?";
    $params[] = "%$location%";
}

$sql .= " GROUP BY sp.id";

// Apply rating filter
if ($minRating > 0) {
    $sql .= " HAVING avg_rating >= ?";
    $params[] = $minRating;
}

// Apply price filter (for providers with price_min set)
if ($maxPrice < 1000000) {
    $sql .= " AND (sp.price_min <= ? OR sp.price_min IS NULL)";
    $params[] = $maxPrice;
}

$sql .= " ORDER BY avg_rating DESC, review_count DESC";

$providers = fetchAll($sql, $params);

// Apply provider filtering based on existing selections (if logged in)
// Filtering disabled to allow browsing all services
// if (isLoggedIn()) {
//     $providers = getFilteredProviders($user['id'], $providers);
// }

// Get all categories for filter
$categories = fetchAll("SELECT * FROM service_categories WHERE is_active = 1 ORDER BY name");
?>
<!DOCTYPE html>
<html lang="<?php echo $currentLang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $currentLang === 'en' ? 'Search Services' : 'সেবা খুঁজুন'; ?> - S24</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Hind+Siliguri:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/ui.css">
    <script src="assets/ui.js"></script>
</head>
<body class="premium-bg">
    <!-- Floating Background Particles -->
    <div class="floating-particles">
        <div class="particle" style="width: 100px; height: 100px; top: 10%; left: 5%; animation-delay: 0s;"></div>
        <div class="particle" style="width: 150px; height: 150px; top: 60%; left: 80%; animation-delay: 2s;"></div>
        <div class="particle" style="width: 80px; height: 80px; top: 40%; left: 40%; animation-delay: 4s;"></div>
    </div>

    <!-- Navigation -->
    <nav class="glass-nav sticky top-0 z-50">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <a href="index.php" class="text-3xl font-black tracking-tighter text-gradient">S24</a>
            </div>
            
            <div class="hidden md:flex items-center space-x-8">
                <a href="index.php" class="font-bold text-gray-700 hover:text-primary transition-colors"><?php echo t('home'); ?></a>
                <a href="search.php" class="font-bold text-primary transition-colors"><?php echo t('services'); ?></a>
                <a href="public_reviews.php" class="font-bold text-gray-700 hover:text-primary transition-colors"><?php echo t('reviews'); ?></a>
                
                <div class="flex items-center space-x-2 bg-gray-100 p-1 rounded-xl">
                    <a href="?lang=en&<?php echo http_build_query(array_diff_key($_GET, ['lang' => ''])); ?>" class="px-3 py-1 rounded-lg text-xs font-bold <?php echo $currentLang === 'en' ? 'bg-white shadow-sm text-primary' : 'text-gray-400'; ?>">EN</a>
                    <a href="?lang=bn&<?php echo http_build_query(array_diff_key($_GET, ['lang' => ''])); ?>" class="px-3 py-1 rounded-lg text-xs font-bold <?php echo $currentLang === 'bn' ? 'bg-white shadow-sm text-primary' : 'text-gray-400'; ?>">বাংলা</a>
                </div>

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

                <?php if (isLoggedIn()): ?>
                    <div class="flex items-center space-x-4">
                        <a href="customer/dashboard.php" class="btn-primary py-2 px-6"><?php echo t('dashboard'); ?></a>
                        <a href="?logout=1" class="text-gray-500 hover:text-danger transition-colors font-bold"><i class="fas fa-sign-out-alt"></i></a>
                    </div>
                <?php else: ?>
                    <a href="auth/login.php" class="btn-primary py-2 px-8"><?php echo t('login'); ?></a>
                <?php endif; ?>
            </div>
            
            <button class="md:hidden text-2xl text-gray-700">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </nav>

    <main class="relative z-10">
        <!-- Search Hero -->
        <section class="container mx-auto px-6 py-12">
            <div class="glass-card p-8 mb-8">
                <h1 class="text-4xl font-black text-gray-900 mb-6 text-center">
                    <?php echo $currentLang === 'en' ? 'Find Your Perfect Service' : 'আপনার নিখুঁত সেবা খুঁজুন'; ?>
                </h1>
                
                <form method="GET" class="max-w-4xl mx-auto">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <input type="text" name="q" value="<?php echo htmlspecialchars($searchQuery); ?>" 
                               placeholder="<?php echo $currentLang === 'en' ? 'Search services...' : 'সেবা খুঁজুন...'; ?>" 
                               class="px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-primary focus:outline-none transition-colors">
                        
                        <select name="category" class="px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-primary focus:outline-none transition-colors">
                            <option value=""><?php echo $currentLang === 'en' ? 'All Categories' : 'সব বিভাগ'; ?></option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo $categoryId == $cat['id'] ? 'selected' : ''; ?>>
                                    <?php echo $currentLang === 'en' ? $cat['name'] : $cat['name_bn']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <input type="text" name="location" value="<?php echo htmlspecialchars($location); ?>" 
                               placeholder="<?php echo $currentLang === 'en' ? 'Location' : 'অবস্থান'; ?>" 
                               class="px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-primary focus:outline-none transition-colors">
                        
                        <button type="submit" class="btn-primary py-3">
                            <i class="fas fa-search mr-2"></i>
                            <?php echo $currentLang === 'en' ? 'Search' : 'খুঁজুন'; ?>
                        </button>
                    </div>
                    
                    <!-- Advanced Filters -->
                    <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2"><?php echo $currentLang === 'en' ? 'Max Price (৳)' : 'সর্বোচ্চ মূল্য (৳)'; ?></label>
                            <input type="number" name="max_price" value="<?php echo $maxPrice < 1000000 ? $maxPrice : ''; ?>" 
                                   placeholder="1000" 
                                   class="w-full px-4 py-2 rounded-xl border-2 border-gray-200 focus:border-primary focus:outline-none transition-colors">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2"><?php echo $currentLang === 'en' ? 'Min Rating' : 'সর্বনিম্ন রেটিং'; ?></label>
                            <select name="rating" class="w-full px-4 py-2 rounded-xl border-2 border-gray-200 focus:border-primary focus:outline-none transition-colors">
                                <option value="0"><?php echo $currentLang === 'en' ? 'Any Rating' : 'যেকোনো রেটিং'; ?></option>
                                <option value="4" <?php echo $minRating == 4 ? 'selected' : ''; ?>>4+ ⭐</option>
                                <option value="3" <?php echo $minRating == 3 ? 'selected' : ''; ?>>3+ ⭐</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Results -->
            <div class="mb-6 flex justify-between items-center">
                <h2 class="text-2xl font-black text-gray-900">
                    <?php echo count($providers); ?> <?php echo $currentLang === 'en' ? 'Providers Found' : 'প্রদানকারী পাওয়া গেছে'; ?>
                </h2>
            </div>

            <?php if (empty($providers)): ?>
                <div class="glass-card p-12 text-center">
                    <i class="fas fa-search text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-2xl font-bold text-gray-700 mb-2">
                        <?php echo $currentLang === 'en' ? 'No providers found' : 'কোনো প্রদানকারী পাওয়া যায়নি'; ?>
                    </h3>
                    <p class="text-gray-500">
                        <?php echo $currentLang === 'en' ? 'Try adjusting your filters or search criteria' : 'আপনার ফিল্টার বা অনুসন্ধান মানদণ্ড সমন্বয় করার চেষ্টা করুন'; ?>
                    </p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($providers as $provider): ?>
                        <div class="glass-card p-6 group">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex items-center space-x-3">
                                    <?php if ($provider['profile_picture']): ?>
                                        <img src="uploads/profiles/<?php echo $provider['profile_picture']; ?>" 
                                             alt="<?php echo htmlspecialchars($provider['name']); ?>"
                                             class="w-16 h-16 rounded-2xl object-cover border-2 border-white shadow-lg">
                                    <?php else: ?>
                                        <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-primary to-secondary flex items-center justify-center text-white text-2xl font-black shadow-lg">
                                            <?php echo strtoupper(substr($provider['name'], 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <h3 class="font-black text-gray-900 text-lg leading-tight"><?php echo htmlspecialchars($provider['name']); ?></h3>
                                        <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">
                                            <?php echo $currentLang === 'en' ? $provider['category_name'] : $provider['category_name_bn']; ?>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <p class="text-gray-600 text-sm mb-4 line-clamp-2">
                                <?php echo htmlspecialchars(substr($provider['description'], 0, 100)) . '...'; ?>
                            </p>

                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center space-x-2">
                                    <div class="flex items-center">
                                        <?php
                                        $rating = round($provider['avg_rating'], 1);
                                        for ($i = 1; $i <= 5; $i++) {
                                            if ($i <= $rating) {
                                                echo '<i class="fas fa-star text-amber-400"></i>';
                                            } else {
                                                echo '<i class="far fa-star text-gray-300"></i>';
                                            }
                                        }
                                        ?>
                                    </div>
                                    <span class="text-sm font-bold text-gray-700">
                                        <?php echo number_format($rating, 1); ?> (<?php echo $provider['review_count']; ?>)
                                    </span>
                                </div>
                                <span class="text-xs font-bold text-gray-500">
                                    <?php echo $provider['booking_count']; ?> <?php echo $currentLang === 'en' ? 'bookings' : 'বুকিং'; ?>
                                </span>
                            </div>

                            <?php if ($provider['price_min'] && $provider['price_max']): ?>
                                <div class="mb-4 px-3 py-2 bg-gray-50 rounded-xl">
                                    <span class="text-xs font-bold text-gray-500 uppercase"><?php echo $currentLang === 'en' ? 'Price Range' : 'মূল্য সীমা'; ?></span>
                                    <div class="font-black text-primary text-lg">
                                        ৳<?php echo number_format($provider['price_min']); ?> - ৳<?php echo number_format($provider['price_max']); ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <a href="customer/select_provider.php?id=<?php echo $provider['id']; ?>" 
                               class="btn-primary w-full text-center py-3 text-sm">
                                <?php echo $currentLang === 'en' ? 'View Profile' : 'প্রোফাইল দেখুন'; ?>
                                <i class="fas fa-arrow-right ml-2"></i>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <!-- Footer -->
    <footer class="glass-card mt-20 py-12">
        <div class="container mx-auto px-6 text-center">
            <div class="text-3xl font-black text-gradient mb-4">S24</div>
            <p class="text-gray-600 mb-8"><?php echo $currentLang === 'en' ? 'Your trusted home service directory' : 'আপনার বিশ্বস্ত হোম সার্ভিস ডিরেক্টরি'; ?></p>
            <div class="flex justify-center space-x-6">
                <a href="#" class="text-gray-500 hover:text-primary transition-colors"><i class="fab fa-facebook text-2xl"></i></a>
                <a href="#" class="text-gray-500 hover:text-primary transition-colors"><i class="fab fa-twitter text-2xl"></i></a>
                <a href="#" class="text-gray-500 hover:text-primary transition-colors"><i class="fab fa-instagram text-2xl"></i></a>
            </div>
            <p class="text-gray-500 text-sm mt-8">© 2026 S24. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
