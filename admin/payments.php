<?php
require_once '../includes/functions.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    redirect('login.php');
}

$currentLang = getLanguage();
$admin = getCurrentAdmin();

// Handle payment actions
if (isset($_POST['action']) && isset($_POST['payment_id'])) {
    $paymentId = (int)$_POST['payment_id'];
    $action = $_POST['action'];
    
    if ($action === 'verify') {
        $payment = fetchOne("
            SELECT p.*, b.customer_id, b.provider_id, u.name as customer_name, sp.name as provider_name
            FROM payments p
            JOIN bookings b ON p.booking_id = b.id
            JOIN users u ON p.customer_id = u.id
            JOIN service_providers sp ON p.provider_id = sp.id
            WHERE p.id = ?
        ", [$paymentId]);
        
        if ($payment) {
            // Calculate income distribution (20% platform, 80% provider)
            $totalAmount = (float)$payment['amount'];
            $platformFee = $totalAmount * 0.20; // 20% platform fee
            $providerEarnings = $totalAmount * 0.80; // 80% provider earnings
            
            try {
                // Begin transaction
                beginTransaction();
                
                // Update payment status to verified
                executeQueryInTransaction("UPDATE payments SET status = 'verified' WHERE id = ?", [$paymentId]);
                
                // Record provider income
                executeQueryInTransaction("
                    INSERT INTO provider_income (provider_id, payment_id, booking_id, total_amount, platform_fee, provider_earnings, status) 
                    VALUES (?, ?, ?, ?, ?, ?, 'pending')
                ", [
                    $payment['provider_id'], 
                    $payment['id'], 
                    $payment['booking_id'], 
                    $totalAmount, 
                    $platformFee, 
                    $providerEarnings
                ]);
                
                // Record platform revenue
                executeQueryInTransaction("
                    INSERT INTO platform_revenue (payment_id, booking_id, total_amount, platform_fee, provider_earnings) 
                    VALUES (?, ?, ?, ?, ?)
                ", [
                    $payment['id'], 
                    $payment['booking_id'], 
                    $totalAmount, 
                    $platformFee, 
                    $providerEarnings
                ]);
                
                // Commit transaction
                commit();
                
                // Send notification to customer
                createNotification(
                    $payment['customer_id'], 
                    'customer', 
                    'Payment Verified', 
                    "Your payment of ৳{$totalAmount} for booking #{$payment['booking_id']} has been verified.", 
                    'payment_verified', 
                    $payment['booking_id']
                );
                
                // Send notification to provider with earnings info
                createNotification(
                    $payment['provider_id'], 
                    'provider', 
                    'Payment Received', 
                    "Payment of ৳{$totalAmount} for booking #{$payment['booking_id']} has been verified. Your earnings: ৳{$providerEarnings} (80% of total).", 
                    'payment_received', 
                    $payment['booking_id']
                );
                
                setFlashMessage('success', "Payment verified successfully. Platform fee: ৳{$platformFee} (20%), Provider earnings: ৳{$providerEarnings} (80%).");
                
            } catch (Exception $e) {
                // Rollback transaction on error
                rollBack();
                setFlashMessage('error', 'Error processing payment verification. Please try again.');
            }
        }
    } elseif ($action === 'reject') {
        $payment = fetchOne("
            SELECT p.*, b.customer_id, b.provider_id, u.name as customer_name, sp.name as provider_name
            FROM payments p
            JOIN bookings b ON p.booking_id = b.id
            JOIN users u ON p.customer_id = u.id
            JOIN service_providers sp ON p.provider_id = sp.id
            WHERE p.id = ?
        ", [$paymentId]);
        
        if ($payment) {
            executeQuery("UPDATE payments SET status = 'rejected' WHERE id = ?", [$paymentId]);
            
            // Send notification to customer
            createNotification(
                $payment['customer_id'], 
                'customer', 
                'Payment Rejected', 
                "Your payment of ৳{$payment['amount']} for booking #{$payment['booking_id']} has been rejected. Please contact support.", 
                'payment_rejected', 
                $payment['booking_id']
            );
            
            setFlashMessage('success', 'Payment rejected successfully. Customer notified.');
        }
    }
}

// Get all payments with related information
$payments = fetchAll("
    SELECT p.*, b.service_type, b.status as booking_status, b.created_at as booking_date,
           u.name as customer_name, u.phone as customer_phone, u.email as customer_email,
           sp.name as provider_name, sp.phone as provider_phone, sp.email as provider_email
    FROM payments p
    JOIN bookings b ON p.booking_id = b.id
    JOIN users u ON p.customer_id = u.id
    JOIN service_providers sp ON p.provider_id = sp.id
    ORDER BY p.created_at DESC
");

// Get platform revenue statistics
$platformStats = fetchOne("
    SELECT 
        COUNT(*) as total_verified_payments,
        SUM(platform_fee) as total_platform_revenue,
        SUM(provider_earnings) as total_provider_earnings,
        SUM(total_amount) as total_processed_amount
    FROM platform_revenue
");

// Get recent platform revenue
$recentRevenue = fetchAll("
    SELECT pr.*, p.method, u.name as customer_name, sp.name as provider_name
    FROM platform_revenue pr
    JOIN payments p ON pr.payment_id = p.id
    JOIN users u ON p.customer_id = u.id
    JOIN service_providers sp ON p.provider_id = sp.id
    ORDER BY pr.created_at DESC
    LIMIT 10
");

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
    <title><?php echo $currentLang === 'en' ? 'Payment Management' : 'পেমেন্ট ব্যবস্থাপনা'; ?> - S24</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/ui.css">
    <script src="../assets/ui.js"></script>
    <style>
        .payment-item {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .payment-item:hover {
            transform: translateY(-5px);
        }
        
        .status-pending {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.1), rgba(245, 158, 11, 0.05));
            border-left: 4px solid #f59e0b;
        }
        
        .status-verified {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(16, 185, 129, 0.05));
            border-left: 4px solid #10b981;
        }
        
        .status-rejected {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(239, 68, 68, 0.05));
            border-left: 4px solid #ef4444;
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
                        S24
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
                    <span class="text-gray-700 font-bold text-lg hidden sm:inline-block border-r border-gray-100 pr-6 mr-6">
                        <i class="fas fa-shield-alt text-primary mr-2"></i>
                        <?php echo $currentLang === 'en' ? 'Admin Control' : 'অ্যাডমিন কন্ট্রোল'; ?>
                    </span>

                    <div class="hidden lg:flex items-center space-x-6">
                        <a href="dashboard.php" class="font-bold <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'text-primary' : 'text-gray-600 hover:text-primary'; ?> transition-colors flex items-center gap-2">
                            <i class="fas fa-grid-horizontal text-sm"></i>
                            <span><?php echo $currentLang === 'en' ? 'Dashboard' : 'ড্যাশবোর্ড'; ?></span>
                        </a>
                        <a href="users.php" class="font-bold <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'text-primary' : 'text-gray-600 hover:text-primary'; ?> transition-colors flex items-center gap-2">
                            <i class="fas fa-users text-sm text-center w-5"></i>
                            <span><?php echo $currentLang === 'en' ? 'Users' : 'ব্যবহারকারী'; ?></span>
                        </a>
                        <a href="providers.php" class="font-bold <?php echo basename($_SERVER['PHP_SELF']) == 'providers.php' ? 'text-primary' : 'text-gray-600 hover:text-primary'; ?> transition-colors flex items-center gap-2">
                            <i class="fas fa-users-gear text-sm"></i>
                            <span><?php echo $currentLang === 'en' ? 'Providers' : 'প্রদানকারী'; ?></span>
                        </a>
                        <a href="bookings.php" class="font-bold <?php echo basename($_SERVER['PHP_SELF']) == 'bookings.php' ? 'text-primary' : 'text-gray-600 hover:text-primary'; ?> transition-colors flex items-center gap-2">
                            <i class="fas fa-calendar-check text-sm"></i>
                            <span><?php echo $currentLang === 'en' ? 'Bookings' : 'বুকিং'; ?></span>
                        </a>
                        <a href="reviews.php" class="font-bold <?php echo basename($_SERVER['PHP_SELF']) == 'reviews.php' ? 'text-primary' : 'text-gray-600 hover:text-primary'; ?> transition-colors flex items-center gap-2">
                            <i class="fas fa-star-half-stroke text-sm"></i>
                            <span><?php echo $currentLang === 'en' ? 'Reviews' : 'রিভিউ'; ?></span>
                        </a>
                        <a href="payments.php" class="font-bold <?php echo basename($_SERVER['PHP_SELF']) == 'payments.php' ? 'text-primary' : 'text-gray-600 hover:text-primary'; ?> transition-colors flex items-center gap-2">
                            <i class="fas fa-credit-card text-sm"></i>
                            <span><?php echo $currentLang === 'en' ? 'Payments' : 'পেমেন্ট'; ?></span>
                        </a>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <a href="notifications.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'notifications.php' ? 'text-primary' : 'text-gray-600 hover:text-primary'; ?> transition-all relative font-bold">
                        <i class="fas fa-bell"></i>
                        <?php 
                        $unreadAdminNotifs = getUnreadNotifications($admin['id'], 'admin');
                        if (count($unreadAdminNotifs) > 0): 
                        ?>
                            <span class="absolute -top-1 -right-1 bg-rose-500 text-white text-[10px] rounded-full h-4 w-4 flex items-center justify-center border-2 border-white">
                                <?php echo count($unreadAdminNotifs); ?>
                            </span>
                        <?php endif; ?>
                    </a>
                    <div class="flex items-center space-x-3 border-l border-gray-100 pl-4 ml-2">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-primary to-secondary flex items-center justify-center text-white font-bold shadow-lg">
                            <?php echo strtoupper(substr($admin['username'], 0, 1)); ?>
                        </div>
                        <div class="text-right hidden sm:block">
                            <div class="font-bold text-gray-800 leading-none"><?php echo htmlspecialchars($admin['username']); ?></div>
                            <div class="text-[10px] font-black uppercase text-primary tracking-widest mt-1">Super Admin</div>
                        </div>
                    </div>
                    <a href="?logout=1" class="btn-primary py-2 px-4 text-xs font-bold">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>
        </nav>
    </header>

    <div class="container mx-auto px-4 py-8 relative z-10">
        <!-- Page Header -->
        <div class="glass-card p-8 mb-8">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-4xl font-bold text-gradient mb-2">
                        <i class="fas fa-credit-card mr-3"></i><?php echo $currentLang === 'en' ? 'Payment Management' : 'পেমেন্ট ব্যবস্থাপনা'; ?>
                    </h1>
                    <p class="text-gray-600 text-lg">
                        <i class="fas fa-info-circle mr-2"></i><?php echo count($payments); ?> <?php echo $currentLang === 'en' ? 'total payments' : 'মোট পেমেন্ট'; ?>
                    </p>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="dashboard.php" class="btn-primary">
                        <i class="fas fa-arrow-left mr-2"></i>
                        <?php echo $currentLang === 'en' ? 'Back to Dashboard' : 'ড্যাশবোর্ডে ফিরে যান'; ?>
                    </a>
                </div>
            </div>
        </div>

        <!-- Payment Statistics -->
        <?php
        $pendingPayments = array_filter($payments, function($p) { return $p['status'] === 'pending'; });
        $verifiedPayments = array_filter($payments, function($p) { return $p['status'] === 'verified'; });
        $rejectedPayments = array_filter($payments, function($p) { return $p['status'] === 'rejected'; });
        $totalAmount = array_sum(array_column($payments, 'amount'));
        $verifiedAmount = array_sum(array_column($verifiedPayments, 'amount'));
        ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="glass-card p-6 border-l-4 border-blue-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-bold uppercase tracking-wider mb-1"><?php echo $currentLang === 'en' ? 'Total Payments' : 'মোট পেমেন্ট'; ?></p>
                        <p class="text-3xl font-black text-gray-800"><?php echo count($payments); ?></p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-blue-50 flex items-center justify-center text-blue-600 shadow-inner">
                        <i class="fas fa-credit-card text-xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="glass-card p-6 border-l-4 border-amber-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-bold uppercase tracking-wider mb-1"><?php echo $currentLang === 'en' ? 'Pending' : 'অপেক্ষমান'; ?></p>
                        <p class="text-3xl font-black text-amber-600"><?php echo count($pendingPayments); ?></p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-amber-50 flex items-center justify-center text-amber-600 shadow-inner">
                        <i class="fas fa-clock text-xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="glass-card p-6 border-l-4 border-emerald-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-bold uppercase tracking-wider mb-1"><?php echo $currentLang === 'en' ? 'Verified' : 'যাচাইকৃত'; ?></p>
                        <p class="text-3xl font-black text-emerald-600"><?php echo count($verifiedPayments); ?></p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 flex items-center justify-center text-emerald-600 shadow-inner">
                        <i class="fas fa-check-circle text-xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="glass-card p-6 border-l-4 border-primary">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-bold uppercase tracking-wider mb-1"><?php echo $currentLang === 'en' ? 'Total Revenue' : 'মোট পরিমাণ'; ?></p>
                        <p class="text-3xl font-black text-primary">৳<?php echo number_format($verifiedAmount, 2); ?></p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-primary/5 flex items-center justify-center text-primary shadow-inner">
                        <i class="fas fa-money-bill-wave text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Platform Revenue Statistics -->
        <div class="glass-card p-8 mb-8">
            <div class="flex items-center space-x-4 mb-8">
                <div class="w-14 h-14 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-2xl flex items-center justify-center shadow-lg shadow-emerald-200 effect-glow">
                    <i class="fas fa-chart-line text-white text-2xl"></i>
                </div>
                <div>
                    <h2 class="text-2xl font-black text-gray-800 tracking-tight"><?php echo $currentLang === 'en' ? 'Platform Revenue Analysis' : 'প্ল্যাটফর্ম রাজস্ব বিশ্লেষণ'; ?></h2>
                    <p class="text-gray-600 font-medium"><?php echo $currentLang === 'en' ? 'Real-time financial performance oversight' : 'রিয়েল-টাইম আর্থিক কর্মক্ষমতা তদারকি'; ?></p>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-blue-50/50 rounded-2xl p-6 border border-blue-100/50">
                    <p class="text-blue-600 text-xs font-bold uppercase tracking-widest mb-2"><?php echo $currentLang === 'en' ? 'Verified Units' : 'যাচাইকৃত ইউনিট'; ?></p>
                    <p class="text-3xl font-black text-blue-900"><?php echo $platformStats['total_verified_payments'] ?? 0; ?></p>
                </div>
                
                <div class="bg-emerald-50/50 rounded-2xl p-6 border border-emerald-100/50">
                    <p class="text-emerald-600 text-xs font-bold uppercase tracking-widest mb-2"><?php echo $currentLang === 'en' ? 'Net Platform Fee' : 'নেট প্ল্যাটফর্ম ফি'; ?></p>
                    <p class="text-3xl font-black text-emerald-900">৳<?php echo number_format($platformStats['total_platform_revenue'] ?? 0, 2); ?></p>
                </div>
                
                <div class="bg-purple-50/50 rounded-2xl p-6 border border-purple-100/50">
                    <p class="text-purple-600 text-xs font-bold uppercase tracking-widest mb-2"><?php echo $currentLang === 'en' ? 'Paid to Providers' : 'প্রদানকারী আয়'; ?></p>
                    <p class="text-3xl font-black text-purple-900">৳<?php echo number_format($platformStats['total_provider_earnings'] ?? 0, 2); ?></p>
                </div>
                
                <div class="bg-amber-50/50 rounded-2xl p-6 border border-amber-100/50">
                    <p class="text-amber-600 text-xs font-bold uppercase tracking-widest mb-2"><?php echo $currentLang === 'en' ? 'Gross Transaction' : 'মোট প্রক্রিয়াকৃত'; ?></p>
                    <p class="text-3xl font-black text-amber-900">৳<?php echo number_format($platformStats['total_processed_amount'] ?? 0, 2); ?></p>
                </div>
            </div>
            
            <?php if (!empty($recentRevenue)): ?>
                <div class="mt-8">
                    <h3 class="text-lg font-bold text-gray-800 mb-4"><?php echo $currentLang === 'en' ? 'Recent Distributions' : 'সাম্প্রতিক বিতরণ'; ?></h3>
                    <div class="space-y-3">
                        <?php foreach ($recentRevenue as $revenue): ?>
                            <div class="flex items-center justify-between p-4 bg-white/50 border border-gray-100 rounded-2xl hover:bg-white transition-colors">
                                <div class="flex items-center space-x-4">
                                    <div class="w-10 h-10 bg-emerald-100/50 text-emerald-600 rounded-xl flex items-center justify-center">
                                        <i class="fas fa-file-invoice-dollar"></i>
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-gray-800 mt-1">#TXN-<?php echo $revenue['payment_id']; ?></span>
                                            <span class="text-xs font-bold text-gray-400">|</span>
                                            <span class="text-sm font-medium text-gray-600"><?php echo htmlspecialchars($revenue['customer_name']); ?></span>
                                        </div>
                                        <p class="text-xs text-gray-500 font-medium">To: <?php echo htmlspecialchars($revenue['provider_name']); ?></p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="font-black text-emerald-600">৳<?php echo number_format($revenue['platform_fee'], 2); ?></p>
                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-tighter"><?php echo date('d M, Y', strtotime($revenue['created_at'])); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Payments List -->
        <div class="glass-card p-8">
            <h2 class="text-2xl font-black text-gray-800 mb-8 flex items-center gap-3">
                <i class="fas fa-list-ul text-primary"></i>
                <?php echo $currentLang === 'en' ? 'Transaction Ledger' : 'লেনদেন লেজার'; ?>
            </h2>
            
            <?php if (empty($payments)): ?>
                <div class="text-center py-20 bg-gray-50/50 rounded-3xl border-2 border-dashed border-gray-200">
                    <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center mx-auto mb-6 shadow-sm">
                        <i class="fas fa-credit-card text-3xl text-gray-300"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-500">
                        <?php echo $currentLang === 'en' ? 'Vault is Empty' : 'ভল্ট খালি'; ?>
                    </h3>
                    <p class="text-gray-400 mt-2 font-medium">
                        <?php echo $currentLang === 'en' ? 'No transactions recorded yet.' : 'এখনও কোনো লেনদেন রেকর্ড করা হয়নি।'; ?>
                    </p>
                </div>
            <?php else: ?>
                <div class="space-y-6">
                    <?php foreach ($payments as $payment): ?>
                        <div class="payment-item glass-card status-<?php echo $payment['status']; ?> p-6">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <div class="flex items-center gap-4 mb-6">
                                        <h3 class="text-xl font-black text-gray-800">
                                            PAY-<?php echo str_pad($payment['id'], 6, '0', STR_PAD_LEFT); ?>
                                        </h3>
                                        <div class="h-1.5 w-1.5 rounded-full bg-gray-300"></div>
                                        <span class="px-4 py-1 rounded-full text-[10px] font-black uppercase tracking-widest
                                            <?php echo $payment['status'] === 'pending' ? 'bg-amber-100 text-amber-700' : 
                                                  ($payment['status'] === 'verified' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'); ?>">
                                            <?php echo $payment['status']; ?>
                                        </span>
                                        <div class="ml-auto text-3xl font-black text-primary">
                                            ৳<?php echo number_format($payment['amount'], 2); ?>
                                        </div>
                                    </div>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                                        <div class="bg-white/40 p-4 rounded-2xl border border-white/60">
                                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3"><?php echo $currentLang === 'en' ? 'Parties Involved' : 'পক্ষসমূহ'; ?></p>
                                            <div class="flex flex-col gap-3">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 text-xs">
                                                        <i class="fas fa-user"></i>
                                                    </div>
                                                    <div>
                                                        <p class="text-sm font-bold text-gray-800 leading-none mb-1"><?php echo htmlspecialchars($payment['customer_name']); ?></p>
                                                        <p class="text-[10px] font-medium text-gray-500 underline"><?php echo htmlspecialchars($payment['customer_phone']); ?></p>
                                                    </div>
                                                </div>
                                                <div class="h-4 border-l-2 border-dashed border-gray-200 ml-4"></div>
                                                <div class="flex items-center gap-3">
                                                    <div class="w-8 h-8 rounded-full bg-purple-100 flex items-center justify-center text-purple-600 text-xs shadow-sm">
                                                        <i class="fas fa-user-tie"></i>
                                                    </div>
                                                    <div>
                                                        <p class="text-sm font-bold text-gray-800 leading-none mb-1"><?php echo htmlspecialchars($payment['provider_name']); ?></p>
                                                        <p class="text-[10px] font-medium text-gray-500"><?php echo htmlspecialchars($payment['provider_phone']); ?></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="bg-white/40 p-4 rounded-2xl border border-white/60">
                                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3"><?php echo $currentLang === 'en' ? 'Service Specs' : 'পরিষেবা বিবরণ'; ?></p>
                                            <div class="space-y-3">
                                                <div class="flex justify-between items-center bg-white/60 p-2 rounded-xl">
                                                    <span class="text-[10px] font-bold text-gray-500 uppercase">Booking ID</span>
                                                    <span class="text-xs font-black text-gray-800">#BK-<?php echo $payment['booking_id']; ?></span>
                                                </div>
                                                <div class="flex justify-between items-center bg-white/60 p-2 rounded-xl">
                                                    <span class="text-[10px] font-bold text-gray-500 uppercase">Category</span>
                                                    <span class="text-xs font-black text-primary"><?php echo htmlspecialchars($payment['service_type']); ?></span>
                                                </div>
                                                <div class="flex justify-between items-center bg-white/60 p-2 rounded-xl">
                                                    <span class="text-[10px] font-bold text-gray-500 uppercase">Method</span>
                                                    <span class="text-xs font-black text-gray-800"><?php echo strtoupper($payment['method'] ?? 'N/A'); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="bg-white/40 p-4 rounded-2xl border border-white/60 flex flex-col justify-between">
                                            <div>
                                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3"><?php echo $currentLang === 'en' ? 'Evidence' : 'প্রমাণ'; ?></p>
                                                <?php if ($payment['proof_file']): ?>
                                                    <a href="../uploads/payments/<?php echo htmlspecialchars($payment['proof_file']); ?>" 
                                                       target="_blank" class="flex items-center gap-3 p-3 bg-primary/5 hover:bg-primary/10 rounded-2xl transition-all group border border-primary/10">
                                                        <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-primary shadow-sm group-hover:scale-110 transition-transform">
                                                            <i class="fas fa-image"></i>
                                                        </div>
                                                        <div class="flex-1">
                                                            <p class="text-[10px] font-black text-primary uppercase">Transaction Proof</p>
                                                            <p class="text-xs font-bold text-gray-500 flex items-center gap-1">Open in Viewer <i class="fas fa-arrow-right text-[8px]"></i></p>
                                                        </div>
                                                    </a>
                                                <?php else: ?>
                                                    <div class="flex items-center gap-3 p-3 bg-gray-100 rounded-2xl border border-gray-200 grayscale opacity-60">
                                                        <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-gray-400">
                                                            <i class="fas fa-image-slash"></i>
                                                        </div>
                                                        <p class="text-[10px] font-black text-gray-400 uppercase">No File Provided</p>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="mt-4 flex items-center justify-between text-[10px] font-bold text-gray-400">
                                                <span><i class="fas fa-calendar-alt mr-1"></i> <?php echo date('M d, Y', strtotime($payment['created_at'])); ?></span>
                                                <span><i class="fas fa-clock mr-1"></i> <?php echo date('h:i A', strtotime($payment['created_at'])); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="flex flex-col gap-3 ml-6 min-w-[140px]">
                                    <?php if ($payment['status'] === 'pending'): ?>
                                        <form method="POST">
                                            <input type="hidden" name="payment_id" value="<?php echo $payment['id']; ?>">
                                            <input type="hidden" name="action" value="verify">
                                            <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white p-3 rounded-2xl font-black text-xs transition-all shadow-lg shadow-emerald-200 active:scale-95 flex items-center justify-center gap-2">
                                                <i class="fas fa-check-circle"></i> VERIFY
                                            </button>
                                        </form>
                                        
                                        <form method="POST">
                                            <input type="hidden" name="payment_id" value="<?php echo $payment['id']; ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="w-full bg-white hover:bg-rose-50 text-rose-600 border border-rose-100 p-3 rounded-2xl font-black text-xs transition-all active:scale-95 flex items-center justify-center gap-2">
                                                <i class="fas fa-times-circle"></i> REJECT
                                            </button>
                                        </form>
                                    <?php elseif ($payment['status'] === 'verified'): ?>
                                        <div class="bg-emerald-50 text-emerald-700 p-4 rounded-2xl border border-emerald-100 flex flex-col items-center gap-2 text-center">
                                            <i class="fas fa-shield-check text-xl"></i>
                                            <p class="text-[10px] font-black uppercase tracking-widest">Secured</p>
                                        </div>
                                    <?php elseif ($payment['status'] === 'rejected'): ?>
                                        <div class="bg-rose-50 text-rose-700 p-4 rounded-2xl border border-rose-100 flex flex-col items-center gap-2 text-center">
                                            <i class="fas fa-ban text-xl"></i>
                                            <p class="text-[10px] font-black uppercase tracking-widest">Rejected</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Enhanced UI logic from ui.js will handle the theme picker
        // Inline observer for smooth entry
        const entryObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('active');
                }
            });
        }, { threshold: 0.1 });
        
        document.querySelectorAll('.glass-card').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(10px)';
            card.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
            entryObserver.observe(card);
            
            // Add custom active class for styles
            const style = document.createElement('style');
            style.textContent = `
                .glass-card.active {
                    opacity: 1 !important;
                    transform: translateY(0) !important;
                }
            `;
            document.head.appendChild(style);
        });
    <script>
        // Enhanced UI logic for smooth entry animations
        const entryObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('active');
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });
        
        document.querySelectorAll('.glass-card, .payment-item').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
            entryObserver.observe(card);
        });

        // Add smooth scroll behavior
        document.documentElement.style.scrollBehavior = 'smooth';
    </script>
</body>
</html> 