<?php
require_once '../includes/functions.php';

if (!isLoggedIn()) {
	redirect('../auth/login.php');
}

$user = getCurrentUser();
$currentLang = getLanguage();

// Get booking ID from URL
$bookingId = (int)($_GET['booking_id'] ?? 0);

if (!$bookingId) {
	setFlashMessage('error', $currentLang === 'en' ? 'Invalid booking ID' : 'অবৈধ বুকিং আইডি');
	redirect('payments.php');
}

// Get booking details with budget information from selection
$booking = fetchOne("
    SELECT b.*, sp.name as provider_name, sp.phone as provider_phone, sp.email as provider_email,
           sc.name as category_name, sc.name_bn as category_name_bn,
           cps.budget_min, cps.budget_max
    FROM bookings b
    JOIN service_providers sp ON b.provider_id = sp.id
    LEFT JOIN service_categories sc ON sp.category_id = sc.id
    LEFT JOIN customer_provider_selections cps ON 
        cps.customer_id = b.customer_id AND 
        cps.provider_id = b.provider_id AND 
        cps.service_type = b.service_type AND 
        cps.preferred_date = b.booking_date AND 
        cps.preferred_time = b.booking_time
    WHERE b.id = ? AND b.customer_id = ? AND b.status = 'completed'
", [$bookingId, $user['id']]);

if (!$booking) {
	setFlashMessage('error', $currentLang === 'en' ? 'Booking not found or not eligible for payment' : 'বুকিং পাওয়া যায়নি বা পেমেন্টের জন্য উপযুক্ত নয়');
	redirect('payments.php');
}

// Check if payment already exists
$existingPayment = fetchOne("SELECT id FROM payments WHERE booking_id = ?", [$bookingId]);
if ($existingPayment) {
	setFlashMessage('error', $currentLang === 'en' ? 'Payment already submitted for this booking' : 'এই বুকিংয়ের জন্য পেমেন্ট ইতিমধ্যে জমা হয়েছে');
	redirect('payments.php');
}

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
    <title><?php echo $currentLang === 'en' ? 'Settle Payment' : 'পেমেন্ট জমা দিন'; ?> - Home Service</title>
    <link rel="stylesheet" href="../assets/ui.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .payment-method-card input:checked + .method-content {
            border-color: var(--primary);
            background: var(--primary);
            color: white;
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .payment-method-card input:checked + .method-content i {
            color: white;
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fadeIn {
            animation: fadeIn 0.4s ease-out forwards;
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
                    <a href="payments.php" class="font-bold text-gray-700 hover:text-primary transition-colors">
                        <i class="fas fa-credit-card mr-1"></i>
                        <?php echo $currentLang === 'en' ? 'Payments' : 'পেমেন্ট'; ?>
                    </a>
                    
                    <div class="flex items-center space-x-2 bg-gray-100 p-1 rounded-xl">
                        <a href="?lang=en&booking_id=<?php echo $bookingId; ?>" class="px-3 py-1 rounded-lg text-xs font-bold <?php echo $currentLang === 'en' ? 'bg-white shadow-sm text-primary' : 'text-gray-400'; ?>">EN</a>
                        <a href="?lang=bn&booking_id=<?php echo $bookingId; ?>" class="px-3 py-1 rounded-lg text-xs font-bold <?php echo $currentLang === 'bn' ? 'bg-white shadow-sm text-primary' : 'text-gray-400'; ?>">বাংলা</a>
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
            <div class="glass-card p-4 mb-6 border-l-4 <?php echo $flash['type'] === 'success' ? 'border-green-500' : 'border-red-500'; ?> bg-white/80 max-w-4xl mx-auto">
                <div class="flex items-center space-x-3">
                    <i class="fas <?php echo $flash['type'] === 'success' ? 'fa-check-circle text-green-500' : 'fa-exclamation-circle text-red-500'; ?> text-xl"></i>
                    <span class="font-bold text-gray-800">
                        <?php echo htmlspecialchars($flash['message']); ?>
                    </span>
                </div>
            </div>
		<?php endif; ?>

        <div class="max-w-4xl mx-auto flex flex-col gap-8">
            <!-- Service Summary Card -->
            <div class="glass-card overflow-hidden">
                <div class="p-8 bg-gradient-to-br from-primary/5 to-secondary/5 border-b border-gray-100 flex flex-col md:flex-row justify-between items-center gap-6">
                    <div class="flex items-center gap-6">
                        <div class="w-16 h-16 bg-primary rounded-2xl flex items-center justify-center text-white shadow-xl shadow-primary/20">
                            <i class="fas fa-receipt text-2xl"></i>
                        </div>
                        <div>
                            <h2 class="text-3xl font-black text-gray-900 tracking-tighter"><?php echo $currentLang === 'en' ? 'Invoice Summary' : 'পরিষেবার বিবরণ'; ?></h2>
                            <p class="text-gray-500 font-medium">#<?php echo $booking['id']; ?> • <?php echo htmlspecialchars($booking['service_type']); ?></p>
                        </div>
                    </div>
                    <div class="bg-white/80 px-6 py-2 rounded-2xl border border-white/50 shadow-sm">
                        <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest block text-center mb-1"><?php echo $currentLang === 'en' ? 'Session' : 'তারিখ'; ?></span>
                        <span class="font-bold text-gray-800"><?php echo formatDate($booking['booking_date']); ?></span>
                    </div>
                </div>
                
                <div class="p-8 grid grid-cols-1 md:grid-cols-2 gap-10">
                    <div class="space-y-6">
                        <div>
                            <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3"><?php echo $currentLang === 'en' ? 'Service Professional' : 'প্রদানকারীর তথ্য'; ?></h3>
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 bg-gray-100 rounded-xl flex items-center justify-center text-gray-400 font-bold">
                                    <?php echo strtoupper(substr($booking['provider_name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <div class="font-bold text-gray-900"><?php echo htmlspecialchars($booking['provider_name']); ?></div>
                                    <div class="text-xs font-medium text-gray-500"><?php echo htmlspecialchars($booking['provider_phone']); ?></div>
                                </div>
                            </div>
                        </div>
                        <div>
                            <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3"><?php echo $currentLang === 'en' ? 'Location' : 'অবস্থান'; ?></h3>
                            <p class="text-sm font-bold text-gray-700 leading-relaxed"><i class="fas fa-map-marker-alt text-primary/40 mr-2"></i><?php echo htmlspecialchars($booking['customer_address'] ?? 'N/A'); ?></p>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50/50 p-6 rounded-2xl border border-gray-100">
                        <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-4"><?php echo $currentLang === 'en' ? 'Cost Allocation' : 'বাজেটের পরিসর'; ?></h3>
                        <div class="space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-black text-gray-500"><?php echo $currentLang === 'en' ? 'Agreed Budget' : 'বাজেট'; ?></span>
                                <span class="font-black text-gray-900">
                                    <?php if($booking['budget_min']): ?>
                                        ৳<?php echo number_format($booking['budget_min']); ?> - ৳<?php echo number_format($booking['budget_max']); ?>
                                    <?php else: ?>
                                        ৳300 - ৳500 (Est.)
                                    <?php endif; ?>
                                </span>
                            </div>
                            <div class="flex justify-between items-center text-primary pt-4 border-t border-primary/10">
                                <span class="text-sm font-black uppercase tracking-widest"><?php echo $currentLang === 'en' ? 'Standard Rate' : 'ঘণ্টায় হার'; ?></span>
                                <span class="text-xl font-black">৳350 / hr</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Enhanced Payment Form -->
            <div class="glass-card p-10">
                <form action="submit_payment.php" method="post" enctype="multipart/form-data" class="space-y-10">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                    
                    <!-- Amount Input -->
                    <div class="space-y-4">
                        <label class="text-xs font-black text-gray-400 uppercase tracking-widest"><?php echo $currentLang === 'en' ? 'Settlement Amount' : 'পেমেন্টের পরিমাণ'; ?> *</label>
                        <div class="relative group">
                            <span class="absolute left-6 top-1/2 -translate-y-1/2 text-2xl font-black text-gray-300 group-focus-within:text-primary transition-colors">৳</span>
                            <input type="number" name="amount" 
                                   min="<?php echo $booking['budget_min'] ?: 100; ?>" 
                                   max="<?php echo $booking['budget_max'] ?: 10000; ?>" 
                                   step="50" required 
                                   placeholder="0.00"
                                   class="w-full pl-12 pr-6 py-6 bg-gray-50 border-2 border-transparent rounded-2xl focus:bg-white focus:border-primary focus:outline-none text-3xl font-black text-gray-900 transition-all shadow-inner">
                        </div>
                        <p class="text-[10px] font-black text-primary/60 uppercase tracking-widest text-right">
                            <?php echo $currentLang === 'en' ? 'Secure Transaction Protocol' : 'সুরক্ষিত লেনদেন'; ?>
                        </p>
                    </div>

                    <!-- Payment Method Selection -->
                    <div class="space-y-6">
                        <label class="text-xs font-black text-gray-400 uppercase tracking-widest"><?php echo $currentLang === 'en' ? 'Select Gateway' : 'পেমেন্ট পদ্ধতি'; ?> *</label>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <label class="payment-method-card cursor-pointer">
                                <input type="radio" name="method" value="bkash" required class="hidden" onchange="togglePaymentInfo('bkash')">
                                <div class="method-content p-6 rounded-2xl border-2 border-gray-100 bg-white flex flex-col items-center gap-4 transition-all">
                                    <div class="w-14 h-14 bg-pink-50 rounded-xl flex items-center justify-center text-pink-500">
                                        <i class="fas fa-mobile-alt text-2xl"></i>
                                    </div>
                                    <div class="text-center">
                                        <div class="font-black text-gray-900">bKash</div>
                                        <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Mobile Wallet</div>
                                    </div>
                                </div>
                            </label>
                            
                            <label class="payment-method-card cursor-pointer">
                                <input type="radio" name="method" value="nagad" required class="hidden" onchange="togglePaymentInfo('nagad')">
                                <div class="method-content p-6 rounded-2xl border-2 border-gray-100 bg-white flex flex-col items-center gap-4 transition-all">
                                    <div class="w-14 h-14 bg-orange-50 rounded-xl flex items-center justify-center text-orange-500">
                                        <i class="fas fa-wallet text-2xl"></i>
                                    </div>
                                    <div class="text-center">
                                        <div class="font-black text-gray-900">Nagad</div>
                                        <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Digital Cash</div>
                                    </div>
                                </div>
                            </label>
                            
                            <label class="payment-method-card cursor-pointer">
                                <input type="radio" name="method" value="bank" required class="hidden" onchange="togglePaymentInfo('bank')">
                                <div class="method-content p-6 rounded-2xl border-2 border-gray-100 bg-white flex flex-col items-center gap-4 transition-all">
                                    <div class="w-14 h-14 bg-indigo-50 rounded-xl flex items-center justify-center text-indigo-500">
                                        <i class="fas fa-university text-2xl"></i>
                                    </div>
                                    <div class="text-center">
                                        <div class="font-black text-gray-900"><?php echo $currentLang === 'en' ? 'Bank Transfer' : 'ব্যাংক ট্রান্সফার'; ?></div>
                                        <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Direct Wire</div>
                                    </div>
                                </div>
                            </label>
                        </div>

                        <!-- Payment Instructions Box -->
                        <div id="payment-instructions" class="hidden">
                            <!-- bKash Info -->
                            <div id="info-bkash" class="method-info hidden animate-fadeIn">
                                <div class="bg-pink-50 border border-pink-100 rounded-2xl p-6 flex flex-col md:flex-row items-center justify-between gap-4">
                                    <div class="flex items-center gap-4">
                                        <div class="w-12 h-12 bg-white rounded-xl flex items-center justify-center text-pink-500 shadow-sm">
                                            <i class="fas fa-phone-alt"></i>
                                        </div>
                                        <div>
                                            <p class="text-[10px] font-black text-pink-400 uppercase tracking-widest leading-none mb-1">bKash Cashout Number</p>
                                            <p class="text-xl font-black text-gray-900 tracking-wider">017XXXXXXXX</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">Instructions</p>
                                        <p class="text-xs font-medium text-gray-600">Please cash out to the above number and provide Transaction ID below.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Nagad Info -->
                            <div id="info-nagad" class="method-info hidden animate-fadeIn">
                                <div class="bg-orange-50 border border-orange-100 rounded-2xl p-6 flex flex-col md:flex-row items-center justify-between gap-4">
                                    <div class="flex items-center gap-4">
                                        <div class="w-12 h-12 bg-white rounded-xl flex items-center justify-center text-orange-500 shadow-sm">
                                            <i class="fas fa-phone-alt"></i>
                                        </div>
                                        <div>
                                            <p class="text-[10px] font-black text-orange-400 uppercase tracking-widest leading-none mb-1">Nagad Cashout Number</p>
                                            <p class="text-xl font-black text-gray-900 tracking-wider">018XXXXXXXX</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">Instructions</p>
                                        <p class="text-xs font-medium text-gray-600">Please cash out to the above number and provide Transaction ID below.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Bank Info -->
                            <div id="info-bank" class="method-info hidden animate-fadeIn">
                                <div class="bg-indigo-50 border border-indigo-100 rounded-2xl p-6">
                                    <div class="flex items-center gap-4 mb-4">
                                        <div class="w-12 h-12 bg-white rounded-xl flex items-center justify-center text-indigo-500 shadow-sm">
                                            <i class="fas fa-university"></i>
                                        </div>
                                        <div>
                                            <p class="text-[10px] font-black text-indigo-400 uppercase tracking-widest leading-none mb-1">Bank Account Details</p>
                                            <p class="text-lg font-black text-gray-900">Netherlands-Bangla Bank Ltd (DBBL)</p>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div class="bg-white/60 p-3 rounded-xl">
                                            <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">Account Name</p>
                                            <p class="text-sm font-black text-gray-800 uppercase">HOME SERVICE SOLUTIONS</p>
                                        </div>
                                        <div class="bg-white/60 p-3 rounded-xl">
                                            <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">Account Number</p>
                                            <p class="text-sm font-black text-gray-800 tracking-wider">122.101.XXXXXXX</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Transaction ID & Proof -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="space-y-4">
                            <label class="text-xs font-black text-gray-400 uppercase tracking-widest"><?php echo $currentLang === 'en' ? 'Ref Transaction ID' : 'লেনদেন আইডি'; ?></label>
                            <input type="text" name="transaction_id" 
                                   placeholder="TRX-XXXX-XXXX"
                                   class="w-full px-5 py-4 bg-gray-50 border-2 border-transparent rounded-2xl focus:bg-white focus:border-primary focus:outline-none font-bold text-gray-900 transition-all shadow-inner">
                        </div>
                        
                        <div class="space-y-4">
                            <label class="text-xs font-black text-gray-400 uppercase tracking-widest"><?php echo $currentLang === 'en' ? 'Upload Receipt' : 'পেমেন্টের প্রমাণ'; ?></label>
                            <div class="relative">
                                <input type="file" name="payment_proof" accept="image/*,.pdf" 
                                       class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                                <div class="w-full px-5 py-4 bg-gray-50 border-2 border-dashed border-gray-200 rounded-2xl flex items-center gap-3">
                                    <i class="fas fa-cloud-upload-alt text-primary/40"></i>
                                    <span class="text-sm font-bold text-gray-400"><?php echo $currentLang === 'en' ? 'Select screenshot...' : 'স্ক্রিনশট নির্বাচন করুন...'; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Final Action -->
                    <div class="pt-10 border-t border-gray-100 flex flex-col md:flex-row items-center justify-between gap-6">
                        <div class="flex items-center gap-3 text-gray-400">
                            <i class="fas fa-info-circle"></i>
                            <p class="text-[10px] font-bold uppercase tracking-widest max-w-[250px] italic">
                                <?php echo $currentLang === 'en' ? 'By submitting, you confirm the service has been completed to your satisfaction.' : 'জমা দেওয়ার মাধ্যমে আপনি নিশ্চিত করছেন যে পরিষেবাটি আপনার সন্তুষ্টি অনুযায়ী সম্পন্ন হয়েছে।'; ?>
                            </p>
                        </div>
                        <button type="submit" class="w-full md:w-auto btn-primary py-5 px-16 text-sm flex items-center justify-center gap-3">
                            <i class="fas fa-lock"></i>
                            <span><?php echo $currentLang === 'en' ? 'Confirm & Process' : 'পেমেন্ট জমা দিন'; ?></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
	
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

        // File name display
        document.querySelector('input[type="file"]').addEventListener('change', function(e) {
            const fileName = e.target.files[0] ? e.target.files[0].name : "Select screenshot...";
            e.target.nextElementSibling.querySelector('span').textContent = fileName;
        });

        // Payment Info Toggle
        function togglePaymentInfo(method) {
            const container = document.getElementById('payment-instructions');
            const allInfos = document.querySelectorAll('.method-info');
            
            container.classList.remove('hidden');
            allInfos.forEach(info => info.classList.add('hidden'));
            
            const targetInfo = document.getElementById('info-' + method);
            if (targetInfo) {
                targetInfo.classList.remove('hidden');
            }
        }
    </script>
</body>
</html>
