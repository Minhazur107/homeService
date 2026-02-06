<?php
require_once '../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('../auth/login.php');
}

$currentLang = getLanguage();
$user = getCurrentUser();

$bookingId = $_GET['booking_id'] ?? 0;

// Get booking details
$booking = fetchOne("
    SELECT b.*, sp.name as provider_name, sp.id as provider_id, sc.name as category_name, sc.name_bn as category_name_bn
    FROM bookings b
    JOIN service_providers sp ON b.provider_id = sp.id
    JOIN service_categories sc ON b.category_id = sc.id
    WHERE b.id = ? AND b.customer_id = ? AND b.status = 'completed'
", [$bookingId, $user['id']]);

if (!$booking) {
    setFlashMessage('error', $currentLang === 'en' ? 'Booking not found or cannot be reviewed' : 'বুকিং পাওয়া যায়নি বা পর্যালোচনা করা যায় না');
    redirect('dashboard.php');
}

// Check if review already exists
$existingReview = fetchOne("SELECT id FROM reviews WHERE booking_id = ?", [$bookingId]);
if ($existingReview) {
    setFlashMessage('error', $currentLang === 'en' ? 'Review already submitted for this booking' : 'এই বুকিংয়ের জন্য ইতিমধ্যে পর্যালোচনা জমা দেওয়া হয়েছে');
    redirect('dashboard.php');
}

$error = '';
$success = '';

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = intval($_POST['rating']);
    $reviewText = sanitizeInput($_POST['review_text']);
    
    if ($rating < 1 || $rating > 5) {
        $error = $currentLang === 'en' ? 'Please select a valid rating' : 'সঠিক রেটিং নির্বাচন করুন';
    } else {
        // Handle photo upload
        $reviewPhoto = '';
        if (isset($_FILES['review_photo']) && $_FILES['review_photo']['error'] === UPLOAD_ERR_OK) {
            $reviewPhoto = uploadFile($_FILES['review_photo'], '../uploads/reviews/');
        }
        
        // Insert review
        $sql = "INSERT INTO reviews (booking_id, customer_id, provider_id, rating, review_text, review_photo, status) VALUES (?, ?, ?, ?, ?, ?, 'approved')";
        try {
            executeQuery($sql, [$bookingId, $user['id'], $booking['provider_id'], $rating, $reviewText, $reviewPhoto]);
            $success = $currentLang === 'en' ? 'Review submitted successfully! Your feedback matters.' : 'পর্যালোচনা সফলভাবে জমা হয়েছে! আপনার মতামত আমাদের জন্য গুরুত্বপূর্ণ।';
        } catch (Exception $e) {
            $error = $currentLang === 'en' ? 'Failed to submit review. Please try again.' : 'পর্যালোচনা জমা দিতে ব্যর্থ। আবার চেষ্টা করুন।';
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
    <title><?php echo $currentLang === 'en' ? 'Review Experience' : 'পর্যালোচনা জমা দিন'; ?> - S24</title>
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
                        S24
                    </a>
                </div>
                
                <div class="hidden md:flex items-center space-x-6">
                    <a href="dashboard.php" class="font-bold text-gray-700 hover:text-primary transition-colors">
                        <i class="fas fa-home mr-1"></i>
                        <?php echo $currentLang === 'en' ? 'Dashboard' : 'ড্যাশবোর্ড'; ?>
                    </a>
                    <a href="reviews.php" class="font-bold text-gray-700 hover:text-primary transition-colors">
                        <i class="fas fa-star mr-1"></i>
                        <?php echo $currentLang === 'en' ? 'My Reviews' : 'আমার রিভিউ'; ?>
                    </a>
                    
                    <div class="flex items-center space-x-3 ml-2 pl-4 border-l border-gray-100">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-primary to-secondary flex items-center justify-center text-white font-bold shadow-lg shadow-primary/20">
                            <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                        </div>
                        <div class="text-right hidden lg:block">
                            <div class="font-bold text-gray-800 leading-none"><?php echo htmlspecialchars($user['name']); ?></div>
                            <div class="text-[10px] font-black uppercase text-primary tracking-widest mt-1">Reviewer</div>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <div class="container mx-auto px-4 py-12 relative z-10">
        <div class="max-w-2xl mx-auto">
            <?php if ($success): ?>
                <div class="glass-card p-12 text-center anim-pop-in">
                    <div class="w-24 h-24 bg-emerald-100 rounded-full flex items-center justify-center mx-auto mb-8 text-emerald-500 shadow-lg shadow-emerald-500/10">
                        <i class="fas fa-check text-4xl"></i>
                    </div>
                    <h3 class="text-3xl font-black text-gray-900 mb-4"><?php echo $currentLang === 'en' ? 'Feedback Received' : 'মতামত গ্রহণ করা হয়েছে'; ?></h3>
                    <p class="text-gray-500 font-medium mb-10 leading-relaxed"><?php echo $success; ?></p>
                    <a href="dashboard.php" class="btn-primary py-4 px-10 inline-flex items-center gap-2">
                        <i class="fas fa-home"></i>
                        <span><?php echo $currentLang === 'en' ? 'Return Home' : 'হোমে ফিরে যান'; ?></span>
                    </a>
                </div>
            <?php else: ?>
                <!-- Main Review Layout -->
                <div class="glass-card overflow-hidden">
                    <!-- Top Section -->
                    <div class="p-8 bg-gradient-to-br from-primary/5 to-secondary/5 border-b border-gray-100">
                        <div class="flex items-center gap-6">
                            <div class="w-16 h-16 bg-white rounded-2xl flex items-center justify-center text-primary text-3xl font-black shadow-xl shadow-primary/5">
                                <?php echo strtoupper(substr($booking['provider_name'], 0, 1)); ?>
                            </div>
                            <div>
                                <h2 class="text-2xl font-black text-gray-900 tracking-tighter"><?php echo htmlspecialchars($booking['provider_name']); ?></h2>
                                <p class="text-gray-500 font-medium text-sm">
                                    <?php echo t($booking['category_name']); ?> • <?php echo formatDate($booking['booking_date']); ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="p-10">
                        <?php if ($error): ?>
                            <div class="mb-8 p-5 bg-rose-50 text-rose-600 rounded-2xl border border-rose-100 font-bold flex items-center gap-3 anim-shake">
                                <i class="fas fa-exclamation-circle"></i>
                                <span><?php echo $error; ?></span>
                            </div>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data" class="space-y-10">
                            <!-- Rating Stars -->
                            <div class="text-center space-y-4">
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-4"><?php echo $currentLang === 'en' ? 'Overall Satisfaction' : 'সামগ্রিক সন্তুষ্টি'; ?> *</label>
                                <div class="flex justify-center flex-row-reverse gap-4">
                                    <?php for ($i = 5; $i >= 1; $i--): ?>
                                        <input type="radio" name="rating" id="star-<?php echo $i; ?>" value="<?php echo $i; ?>" required class="peer hidden">
                                        <label for="star-<?php echo $i; ?>" class="text-4xl text-gray-200 cursor-pointer transition-all hover:scale-125 hover:text-amber-400 peer-checked:text-amber-400 peer-checked:scale-110">
                                            <i class="fas fa-star"></i>
                                        </label>
                                    <?php endfor; ?>
                                </div>
                                <p class="text-[10px] font-black text-amber-500/60 uppercase tracking-widest mt-4">Tap a star to rate</p>
                            </div>

                            <!-- Review Text -->
                            <div class="space-y-4">
                                <label class="text-xs font-black text-gray-400 uppercase tracking-widest ml-1"><?php echo $currentLang === 'en' ? 'Written Testimony' : 'আপনার মতামত'; ?></label>
                                <div class="relative group">
                                    <i class="fas fa-quote-left absolute left-4 top-4 text-gray-300 group-focus-within:text-primary transition-colors"></i>
                                    <textarea name="review_text" rows="5" 
                                              placeholder="<?php echo $currentLang === 'en' ? 'What made your experience special?' : 'আপনার অভিজ্ঞতা সম্পর্কে লিখুন...'; ?>"
                                              class="w-full pl-12 pr-6 py-5 bg-gray-50 border-2 border-transparent rounded-2xl focus:bg-white focus:border-primary focus:outline-none font-bold text-gray-900 transition-all shadow-inner"></textarea>
                                </div>
                            </div>

                            <!-- Photo Upload -->
                            <div class="p-6 bg-gray-50/50 rounded-2xl border-2 border-dashed border-gray-200 group hover:border-primary/40 transition-all">
                                <label class="text-xs font-black text-gray-400 uppercase tracking-widest mb-4 block"><?php echo $currentLang === 'en' ? 'Evidence (Optional)' : 'ছবি (ঐচ্ছিক)'; ?></label>
                                <div class="relative flex flex-col items-center">
                                    <input type="file" name="review_photo" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                                    <div class="w-14 h-14 bg-white rounded-2xl flex items-center justify-center text-gray-400 mb-3 shadow-sm group-hover:text-primary transition-colors">
                                        <i class="fas fa-camera text-2xl"></i>
                                    </div>
                                    <p class="text-sm font-bold text-gray-400 group-hover:text-primary transition-colors">Upload service photo</p>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="pt-6 border-t border-gray-100 flex flex-col sm:flex-row gap-4">
                                <button type="submit" class="flex-1 btn-primary py-5 text-sm flex items-center justify-center gap-3">
                                    <i class="fas fa-paper-plane"></i>
                                    <span><?php echo $currentLang === 'en' ? 'Publish Review' : 'রিভিউ পাবলিশ করুন'; ?></span>
                                </button>
                                <a href="dashboard.php" class="sm:w-32 bg-gray-100 text-gray-500 font-black text-[10px] uppercase tracking-widest flex items-center justify-center rounded-2xl hover:bg-rose-50 hover:text-rose-500 transition-all">
                                    <?php echo $currentLang === 'en' ? 'Dismiss' : 'বাতিল'; ?>
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="glass-nav py-12 mt-20">
        <div class="container mx-auto px-4 text-center">
            <p class="text-gray-500 font-black text-sm uppercase tracking-widest">&copy; <?php echo date('Y'); ?> S24 PREMIUM SOLUTIONS. ALL RIGHTS RESERVED.</p>
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

        // Preview local file name
        document.querySelector('input[type="file"]').addEventListener('change', function(e) {
            const fileName = e.target.files[0] ? e.target.files[0].name : "Upload service photo";
            this.parentElement.querySelector('p').textContent = fileName;
        });
    </script>
</body>
</html>