<?php
require_once 'includes/functions.php';

$currentLang = getLanguage();

// Get all reviews
$reviews = fetchAll("
    SELECT r.*, u.name as customer_name, sp.name as provider_name, 
           sc.name as category_name, sc.name_bn as category_name_bn
    FROM reviews r
    JOIN users u ON r.customer_id = u.id
    JOIN service_providers sp ON r.provider_id = sp.id
    JOIN service_categories sc ON sp.category_id = sc.id
    ORDER BY r.created_at DESC
    LIMIT 50
");

// Get stats
$stats = fetchOne("
    SELECT COUNT(*) as total_reviews, AVG(rating) as avg_rating
    FROM reviews
");
?>
<!DOCTYPE html>
<html lang="<?php echo $currentLang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $currentLang === 'en' ? 'Customer Reviews' : 'গ্রাহক পর্যালোচনা'; ?> - S24</title>
    <link rel="stylesheet" href="assets/ui.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="premium-bg min-h-screen">
    <header class="glass-nav sticky top-0 z-50">
        <nav class="container mx-auto px-6 py-4 flex justify-between items-center">
            <a href="index.php" class="text-3xl font-black text-gradient">S24</a>
            <div class="flex items-center space-x-6">
                <a href="search.php" class="font-bold text-gray-700 hover:text-primary transition-colors"><?php echo $currentLang === 'en' ? 'Services' : 'সেবা'; ?></a>
                <a href="auth/login.php" class="btn-primary py-2 px-6"><?php echo $currentLang === 'en' ? 'Login' : 'লগইন'; ?></a>
            </div>
        </nav>
    </header>

    <main class="container mx-auto px-6 py-12">
        <div class="max-w-4xl mx-auto">
            <div class="text-center mb-16 space-y-4">
                <h1 class="text-5xl font-black text-gray-900 tracking-tighter"><?php echo $currentLang === 'en' ? 'Real Stories from' : 'বাস্তব অভিজ্ঞতা'; ?> <span class="text-gradient">Real People</span></h1>
                <p class="text-gray-500 font-bold uppercase tracking-widest text-xs"><?php echo $stats['total_reviews']; ?> Verified Reviews • <?php echo number_format($stats['avg_rating'], 1); ?> Average Rating</p>
            </div>

            <div class="grid gap-8">
                <?php foreach ($reviews as $review): ?>
                    <div class="glass-card p-10 hover:scale-[1.02] transition-transform duration-300">
                        <div class="flex justify-between items-start mb-6">
                            <div class="flex items-center space-x-4">
                                <div class="w-12 h-12 bg-primary rounded-full flex items-center justify-center text-white font-black text-xl">
                                    <?php echo strtoupper(substr($review['customer_name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <h3 class="font-black text-gray-900"><?php echo htmlspecialchars($review['customer_name']); ?></h3>
                                    <p class="text-xs font-bold text-gray-400 uppercase"><?php echo $currentLang === 'en' ? 'Verified Customer' : 'যাচাইকৃত গ্রাহক'; ?></p>
                                </div>
                            </div>
                            <div class="flex text-amber-400">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="<?php echo $i <= $review['rating'] ? 'fas' : 'far'; ?> fa-star"></i>
                                <?php endfor; ?>
                            </div>
                        </div>

                        <p class="text-lg text-gray-700 italic leading-relaxed mb-8">"<?php echo htmlspecialchars($review['review_text']); ?>"</p>

                        <div class="flex items-center justify-between pt-6 border-t border-gray-100">
                            <div class="flex items-center space-x-3">
                                <span class="text-xs font-black text-gray-400 uppercase tracking-widest"><?php echo $currentLang === 'en' ? 'Service by' : 'সেবা প্রদানকারী'; ?></span>
                                <span class="px-3 py-1 rounded-lg bg-primary/5 text-primary text-xs font-black"><?php echo htmlspecialchars($review['provider_name']); ?></span>
                            </div>
                            <span class="text-xs font-bold text-gray-400"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <footer class="py-12 text-center text-gray-400 font-bold text-sm">
        <p>&copy; 2026 S24. Built with excellence.</p>
    </footer>
</body>
</html>
