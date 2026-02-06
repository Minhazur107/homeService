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

$bookingId = $_GET['id'] ?? 0;

// Get booking details
$booking = fetchOne("
    SELECT b.*, sp.name as provider_name, sp.phone as provider_phone, sp.email as provider_email, 
           sc.name as category_name, sc.name_bn as category_name_bn
    FROM bookings b
    JOIN service_providers sp ON b.provider_id = sp.id
    JOIN service_categories sc ON b.category_id = sc.id
    WHERE b.id = ? AND b.customer_id = ?
", [$bookingId, $user['id']]);

if (!$booking) {
    setFlashMessage('error', $currentLang === 'en' ? 'Booking not found' : 'বুকিং পাওয়া যায়নি');
    redirect('dashboard.php');
}

// Get review if exists
$review = fetchOne("SELECT * FROM reviews WHERE booking_id = ?", [$bookingId]);
$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="<?php echo $currentLang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $currentLang === 'en' ? 'Booking Details' : 'বুকিং বিবরণ'; ?> - HOME SERVICE</title>
    <link rel="stylesheet" href="../assets/ui.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .status-pending { background: linear-gradient(135deg, #fbbf24, #f59e0b); color: white; }
        .status-confirmed { background: linear-gradient(135deg, #10b981, #059669); color: white; }
        .status-completed { background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; }
        .status-cancelled { background: linear-gradient(135deg, #ef4444, #dc2626); color: white; }
        
        .timeline-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-top: 6px;
        }
    </style>
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
                    <a href="profile.php" class="font-bold text-gray-700 hover:text-primary transition-colors">
                        <i class="fas fa-user mr-1"></i>
                        <?php echo $currentLang === 'en' ? 'Profile' : 'প্রোফাইল'; ?>
                    </a>
                    
                    <div class="flex items-center space-x-2 bg-gray-100 p-1 rounded-xl">
                        <a href="?lang=en&id=<?php echo $bookingId; ?>" class="px-3 py-1 rounded-lg text-xs font-bold <?php echo $currentLang === 'en' ? 'bg-white shadow-sm text-primary' : 'text-gray-400'; ?>">EN</a>
                        <a href="?lang=bn&id=<?php echo $bookingId; ?>" class="px-3 py-1 rounded-lg text-xs font-bold <?php echo $currentLang === 'bn' ? 'bg-white shadow-sm text-primary' : 'text-gray-400'; ?>">বাংলা</a>
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
        <?php if ($flash): ?>
            <div class="glass-card p-4 mb-6 border-l-4 <?php echo $flash['type'] === 'success' ? 'border-green-500' : 'border-red-500'; ?> bg-white/80">
                <div class="flex items-center space-x-3">
                    <i class="fas <?php echo $flash['type'] === 'success' ? 'fa-check-circle text-green-500' : 'fa-exclamation-circle text-red-500'; ?> text-xl"></i>
                    <span class="font-bold text-gray-800">
                        <?php echo htmlspecialchars($flash['message']); ?>
                    </span>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="max-w-6xl mx-auto">
            <!-- Booking Status Header -->
            <div class="glass-card p-8 mb-8">
                <div class="flex flex-col md:flex-row justify-between items-center gap-6">
                    <div>
                        <h1 class="text-4xl font-black text-gray-900 mb-2 tracking-tighter">
                            <?php echo $currentLang === 'en' ? 'Booking Details' : 'বুকিং বিবরণ'; ?>
                        </h1>
                        <p class="text-gray-500 font-medium">
                            <?php echo $currentLang === 'en' ? 'Booking ID' : 'বুকিং আইডি'; ?>: <span class="text-primary font-black">#<?php echo $booking['id']; ?></span>
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-4">
                        <span class="status-badge 
                            <?php echo $booking['status'] === 'completed' ? 'status-completed' : 
                                ($booking['status'] === 'confirmed' ? 'status-confirmed' : 
                                ($booking['status'] === 'pending' ? 'status-pending' : 'status-cancelled')); ?>">
                            <?php echo t($booking['status']); ?>
                        </span>
                        <?php if ($booking['status'] === 'pending' || $booking['status'] === 'confirmed'): ?>
                            <a href="edit_booking.php?id=<?php echo $booking['id']; ?>" class="btn-primary py-2 px-6">
                                <i class="fas fa-edit mr-2"></i><?php echo $currentLang === 'en' ? 'Edit' : 'সম্পাদনা'; ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Service Information -->
                <div class="lg:col-span-2 space-y-8">
                    <div class="glass-card p-8">
                        <div class="flex items-center space-x-4 mb-8">
                            <div class="w-12 h-12 bg-primary/10 rounded-xl flex items-center justify-center text-primary">
                                <i class="fas fa-tools text-xl"></i>
                            </div>
                            <div>
                                <h2 class="text-2xl font-black text-gray-900"><?php echo $currentLang === 'en' ? 'Service Information' : 'সেবার তথ্য'; ?></h2>
                                <p class="text-sm font-bold text-gray-400 uppercase tracking-widest"><?php echo $currentLang === 'en' ? 'Complete service details' : 'সম্পূর্ণ সেবার বিবরণ'; ?></p>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="bg-gray-50/50 rounded-2xl p-6 border border-gray-100">
                                <div class="text-xs font-black text-gray-400 uppercase tracking-widest mb-2"><?php echo $currentLang === 'en' ? 'Service Category' : 'সেবা বিভাগ'; ?></div>
                                <div class="font-bold text-gray-800 text-lg"><?php echo $currentLang === 'en' ? $booking['category_name'] : $booking['category_name_bn']; ?></div>
                            </div>
                            
                            <?php if ($booking['service_type']): ?>
                                <div class="bg-gray-50/50 rounded-2xl p-6 border border-gray-100">
                                    <div class="text-xs font-black text-gray-400 uppercase tracking-widest mb-2"><?php echo $currentLang === 'en' ? 'Service Type' : 'সেবার ধরন'; ?></div>
                                    <div class="font-bold text-gray-800 text-lg"><?php echo htmlspecialchars($booking['service_type']); ?></div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="bg-gray-50/50 rounded-2xl p-6 border border-gray-100">
                                <div class="text-xs font-black text-gray-400 uppercase tracking-widest mb-2"><?php echo $currentLang === 'en' ? 'Service Date' : 'সেবার তারিখ'; ?></div>
                                <div class="font-bold text-gray-800 text-lg"><?php echo formatDate($booking['booking_date']); ?></div>
                            </div>
                            
                            <div class="bg-gray-50/50 rounded-2xl p-6 border border-gray-100">
                                <div class="text-xs font-black text-gray-400 uppercase tracking-widest mb-2"><?php echo $currentLang === 'en' ? 'Service Time' : 'সেবার সময়'; ?></div>
                                <div class="font-bold text-gray-800 text-lg"><?php echo date('h:i A', strtotime($booking['booking_time'])); ?></div>
                            </div>
                            
                            <?php if ($booking['final_price']): ?>
                                <div class="bg-primary/5 rounded-2xl p-6 border border-primary/10">
                                    <div class="text-xs font-black text-primary/60 uppercase tracking-widest mb-2"><?php echo $currentLang === 'en' ? 'Final Price' : 'চূড়ান্ত মূল্য'; ?></div>
                                    <div class="font-black text-2xl text-primary">৳<?php echo number_format($booking['final_price']); ?></div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($booking['cancellation_fee'] > 0): ?>
                                <div class="bg-red-50 rounded-2xl p-6 border border-red-100">
                                    <div class="text-xs font-black text-red-400 uppercase tracking-widest mb-2"><?php echo $currentLang === 'en' ? 'Cancellation Fee' : 'বাতিলকরণ ফি'; ?></div>
                                    <div class="font-black text-xl text-red-600">৳<?php echo number_format($booking['cancellation_fee']); ?></div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($booking['notes']): ?>
                            <div class="mt-8 p-6 bg-primary/5 rounded-2xl border border-primary/10">
                                <h3 class="font-black text-primary uppercase text-xs tracking-widest mb-4 flex items-center">
                                    <i class="fas fa-sticky-note mr-2"></i><?php echo $currentLang === 'en' ? 'Customer Notes' : 'গ্রাহকের নোট'; ?>
                                </h3>
                                <p class="text-gray-700 font-medium"><?php echo htmlspecialchars($booking['notes']); ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($booking['cancellation_reason']): ?>
                            <div class="mt-8 p-6 bg-red-50 rounded-2xl border border-red-100">
                                <h3 class="font-black text-red-600 uppercase text-xs tracking-widest mb-4 flex items-center">
                                    <i class="fas fa-times-circle mr-2"></i><?php echo $currentLang === 'en' ? 'Cancellation Reason' : 'বাতিলকরণের কারণ'; ?>
                                </h3>
                                <p class="text-red-700 font-medium"><?php echo htmlspecialchars($booking['cancellation_reason']); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Review Section -->
                    <div class="glass-card p-8">
                        <div class="flex items-center space-x-4 mb-8">
                            <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center text-amber-500">
                                <i class="fas fa-star text-xl"></i>
                            </div>
                            <div>
                                <h2 class="text-2xl font-black text-gray-900"><?php echo $currentLang === 'en' ? 'Review' : 'পর্যালোচনা'; ?></h2>
                                <p class="text-sm font-bold text-gray-400 uppercase tracking-widest"><?php echo $currentLang === 'en' ? 'Share your experience' : 'আপনার অভিজ্ঞতা শেয়ার করুন'; ?></p>
                            </div>
                        </div>
                        
                        <?php if ($review): ?>
                            <div class="bg-amber-50/50 border border-amber-100 rounded-2xl p-8">
                                <div class="flex flex-wrap items-center gap-6 mb-6">
                                    <div class="flex items-center text-amber-400 text-xl">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="<?php echo $i <= $review['rating'] ? 'fas' : 'far'; ?> fa-star"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <span class="text-2xl font-black text-gray-800"><?php echo $review['rating']; ?>/5</span>
                                    <span class="px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest
                                        <?php echo $review['status'] === 'approved' ? 'bg-green-100 text-green-600' : 
                                            ($review['status'] === 'pending' ? 'bg-amber-100 text-amber-600' : 'bg-red-100 text-red-600'); ?>">
                                        <?php echo t($review['status']); ?>
                                    </span>
                                </div>
                                
                                <?php if ($review['review_text']): ?>
                                    <p class="text-gray-700 text-lg italic leading-relaxed mb-6">"<?php echo htmlspecialchars($review['review_text']); ?>"</p>
                                <?php endif; ?>
                                
                                <?php if ($review['review_photo']): ?>
                                    <div class="w-48 h-48 rounded-2xl overflow-hidden shadow-xl mb-6">
                                        <img src="../uploads/reviews/<?php echo $review['review_photo']; ?>" 
                                             alt="Review Photo" class="w-full h-full object-cover">
                                    </div>
                                <?php endif; ?>
                                
                                <div class="text-xs font-bold text-gray-400 flex items-center gap-2">
                                    <i class="fas fa-clock"></i>
                                    <?php echo formatDateTime($review['created_at']); ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-12">
                                <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-6 text-gray-200">
                                    <i class="fas fa-star text-4xl"></i>
                                </div>
                                <h3 class="text-2xl font-black text-gray-800 mb-3">
                                    <?php echo $currentLang === 'en' ? 'No Review Yet' : 'এখনও কোন পর্যালোচনা নেই'; ?>
                                </h3>
                                <p class="text-gray-500 font-medium mb-8 max-w-md mx-auto">
                                    <?php echo $currentLang === 'en' ? 'Share your experience with this service provider to help others make informed decisions.' : 'অন্যান্যদের সঠিক সিদ্ধান্ত নিতে সাহায্য করার জন্য এই সেবা প্রদানকারীর সাথে আপনার অভিজ্ঞতা শেয়ার করুন।'; ?>
                                </p>
                                <a href="review.php?booking_id=<?php echo $booking['id']; ?>" class="btn-primary py-4 px-10 inline-flex items-center gap-2">
                                    <i class="fas fa-pen"></i>
                                    <span><?php echo $currentLang === 'en' ? 'Write a Review' : 'পর্যালোচনা লিখুন'; ?></span>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Right Sidebar -->
                <div class="lg:col-span-1 space-y-8">
                    <!-- Provider Information -->
                    <div class="glass-card p-8">
                        <div class="flex items-center space-x-4 mb-8">
                            <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center text-emerald-500">
                                <i class="fas fa-user-tie text-xl"></i>
                            </div>
                            <div>
                                <h2 class="text-xl font-black text-gray-900"><?php echo $currentLang === 'en' ? 'Service Provider' : 'সেবা প্রদানকারী'; ?></h2>
                                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest"><?php echo $currentLang === 'en' ? 'Contact details' : 'যোগাযোগের তথ্য'; ?></p>
                            </div>
                        </div>
                        
                        <div class="space-y-6">
                            <div class="text-center p-8 bg-emerald-50/50 rounded-2xl border border-emerald-100 mb-8">
                                <h3 class="font-black text-xl text-gray-900 mb-1"><?php echo htmlspecialchars($booking['provider_name']); ?></h3>
                                <p class="text-xs font-black text-emerald-600 uppercase tracking-widest">
                                    <?php echo $currentLang === 'en' ? $booking['category_name'] : $booking['category_name_bn']; ?>
                                </p>
                            </div>
                            
                            <div class="space-y-3">
                                <a href="tel:<?php echo $booking['provider_phone']; ?>" class="w-full flex items-center p-4 bg-white border border-gray-100 rounded-2xl hover:border-emerald-500 hover:shadow-lg transition-all group">
                                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center mr-4 group-hover:scale-110 transition-transform">
                                        <i class="fas fa-phone"></i>
                                    </div>
                                    <span class="font-bold text-gray-800"><?php echo $booking['provider_phone']; ?></span>
                                </a>
                                
                                <?php if ($booking['provider_email']): ?>
                                    <a href="mailto:<?php echo $booking['provider_email']; ?>" class="w-full flex items-center p-4 bg-white border border-gray-100 rounded-2xl hover:border-blue-500 hover:shadow-lg transition-all group">
                                        <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center mr-4 group-hover:scale-110 transition-transform">
                                            <i class="fas fa-envelope"></i>
                                        </div>
                                        <span class="font-bold text-gray-800 break-all"><?php echo $booking['provider_email']; ?></span>
                                    </a>
                                <?php endif; ?>
                                
                                <a href="https://wa.me/<?php echo $booking['provider_phone']; ?>" target="_blank" class="w-full flex items-center p-4 bg-white border border-gray-100 rounded-2xl hover:border-green-500 hover:shadow-lg transition-all group">
                                    <div class="w-10 h-10 rounded-xl bg-green-50 text-green-600 flex items-center justify-center mr-4 group-hover:scale-110 transition-transform">
                                        <i class="fab fa-whatsapp"></i>
                                    </div>
                                    <span class="font-bold text-gray-800"><?php echo $currentLang === 'en' ? 'WhatsApp Chat' : 'হোয়াটসঅ্যাপ মেসেজ'; ?></span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Booking Timeline -->
                    <div class="glass-card p-8">
                        <div class="flex items-center space-x-4 mb-8">
                            <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center text-indigo-500">
                                <i class="fas fa-stream text-xl"></i>
                            </div>
                            <div>
                                <h2 class="text-xl font-black text-gray-900"><?php echo $currentLang === 'en' ? 'Timeline' : 'সময়রেখা'; ?></h2>
                                <p class="text-xs font-black text-gray-400 uppercase tracking-widest"><?php echo $currentLang === 'en' ? 'Booking history' : 'বুকিং এর ইতিহাস'; ?></p>
                            </div>
                        </div>
                        
                        <div class="space-y-8 relative before:absolute before:left-[5px] before:top-2 before:bottom-2 before:w-[2px] before:bg-gray-100">
                            <div class="flex items-start space-x-6 relative">
                                <div class="timeline-dot bg-indigo-500 z-10 shadow-lg shadow-indigo-500/30"></div>
                                <div class="flex-1">
                                    <p class="font-black text-gray-800 text-sm">
                                        <?php echo $currentLang === 'en' ? 'Booking Created' : 'বুকিং তৈরি হয়েছে'; ?>
                                    </p>
                                    <p class="text-xs font-bold text-gray-400 mt-1"><?php echo formatDateTime($booking['created_at']); ?></p>
                                </div>
                            </div>
                            
                            <?php if ($booking['status'] !== 'pending'): ?>
                                <div class="flex items-start space-x-6 relative">
                                    <div class="timeline-dot bg-primary z-10 shadow-lg shadow-primary/30"></div>
                                    <div class="flex-1">
                                        <p class="font-black text-gray-800 text-sm">
                                            <?php echo $currentLang === 'en' ? 'Booking Confirmed' : 'বুকিং নিশ্চিত হয়েছে'; ?>
                                        </p>
                                        <p class="text-xs font-bold text-gray-400 mt-1"><?php echo formatDateTime($booking['updated_at']); ?></p>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($booking['status'] === 'completed'): ?>
                                <div class="flex items-start space-x-6 relative">
                                    <div class="timeline-dot bg-green-500 z-10 shadow-lg shadow-green-500/30"></div>
                                    <div class="flex-1">
                                        <p class="font-black text-gray-800 text-sm">
                                            <?php echo $currentLang === 'en' ? 'Service Completed' : 'সেবা সম্পন্ন হয়েছে'; ?>
                                        </p>
                                        <p class="text-xs font-bold text-gray-400 mt-1"><?php echo formatDateTime($booking['updated_at']); ?></p>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($booking['status'] === 'cancelled'): ?>
                                <div class="flex items-start space-x-6 relative">
                                    <div class="timeline-dot bg-red-500 z-10 shadow-lg shadow-red-500/30"></div>
                                    <div class="flex-1">
                                        <p class="font-black text-gray-800 text-sm">
                                            <?php echo $currentLang === 'en' ? 'Booking Cancelled' : 'বুকিং বাতিল হয়েছে'; ?>
                                        </p>
                                        <p class="text-xs font-bold text-gray-400 mt-1"><?php echo formatDateTime($booking['updated_at']); ?></p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <footer class="glass-nav py-8 mt-12">
        <div class="container mx-auto px-4 text-center">
            <p class="text-gray-500 font-bold">&copy; <?php echo date('Y'); ?> HOME SERVICE. All rights reserved.</p>
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