<?php
require_once '../includes/functions.php';

// Check if provider is logged in
if (!isProviderLoggedIn()) {
    redirect('../auth/login.php');
}

$currentLang = getLanguage();
$provider = getCurrentProvider();

// Handle logout
if (isset($_GET['logout'])) {
    logout();
    redirect('../index.php');
}

// Get provider income statistics
$incomeStats = fetchOne("
    SELECT 
        COUNT(*) as total_earnings,
        SUM(provider_earnings) as total_income,
        SUM(platform_fee) as total_platform_fees,
        SUM(total_amount) as total_processed_amount,
        AVG(provider_earnings) as average_earning
    FROM provider_income 
    WHERE provider_id = ?
", [$provider['id']]);

// Get recent income transactions
$recentIncome = fetchAll("
    SELECT pi.*, p.method, p.transaction_id, u.name as customer_name, b.service_type, b.booking_date
    FROM provider_income pi
    JOIN payments p ON pi.payment_id = p.id
    JOIN users u ON p.customer_id = u.id
    JOIN bookings b ON pi.booking_id = b.id
    WHERE pi.provider_id = ?
    ORDER BY pi.created_at DESC
    LIMIT 20
", [$provider['id']]);

// Get monthly income breakdown
$monthlyIncome = fetchAll("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as transactions,
        SUM(provider_earnings) as monthly_income,
        SUM(platform_fee) as monthly_platform_fees
    FROM provider_income 
    WHERE provider_id = ?
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month DESC
    LIMIT 12
", [$provider['id']]);

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="<?php echo $currentLang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $currentLang === 'en' ? 'Your Income' : 'আপনার আয়'; ?> - HOME SERVICE</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/ui.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
        }
        
        .provider-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }
        
        .floating-elements {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
        }
        
        .floating-element {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            animation: float 6s ease-in-out infinite;
        }
        
        .floating-element:nth-child(1) {
            width: 80px;
            height: 80px;
            top: 20%;
            left: 10%;
            animation-delay: 0s;
        }
        
        .floating-element:nth-child(2) {
            width: 120px;
            height: 120px;
            top: 60%;
            right: 15%;
            animation-delay: 2s;
        }
        
        .floating-element:nth-child(3) {
            width: 60px;
            height: 60px;
            bottom: 20%;
            left: 20%;
            animation-delay: 4s;
        }
        
        .floating-element:nth-child(4) {
            width: 100px;
            height: 100px;
            top: 10%;
            right: 30%;
            animation-delay: 1s;
        }
        
        .floating-element:nth-child(5) {
            width: 70px;
            height: 70px;
            bottom: 40%;
            right: 5%;
            animation-delay: 3s;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }
        
        .provider-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        
        .provider-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .provider-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
        }
        
        .nav-link {
            color: black;
            transition: all 0.3s ease;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 500;
            text-decoration: none;
        }
        
        .nav-link:hover {
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
            transform: translateY(-2px);
        }
        
        .logout-btn {
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(238, 90, 82, 0.3);
        }
        
        .income-card {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 8px 32px rgba(16, 185, 129, 0.3);
        }
        
        .stats-card {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 12px;
            padding: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
    </style>
</head>
<body class="provider-bg min-h-screen">
    <div class="floating-elements">
        <div class="floating-element"></div>
        <div class="floating-element"></div>
        <div class="floating-element"></div>
        <div class="floating-element"></div>
        <div class="floating-element"></div>
    </div>
    
    <header class="provider-header sticky top-0 z-50">
        <nav class="container mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-6">
                    <a href="dashboard.php" class="text-3xl font-bold bg-gradient-to-r from-purple-600 to-blue-600 bg-clip-text text-transparent">
                        <i class="fas fa-home mr-2"></i>HOME SERVICE
                    </a>
                    <div class="flex items-center space-x-1">
                        <i class="fas fa-hand-holding-usd text-green-600"></i>
                        <span class="text-xl font-semibold text-gray-800"><?php echo $currentLang === 'en' ? 'Your Income' : 'আপনার আয়'; ?></span>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <!-- Dashboard -->
                    <a href="dashboard.php" class="flex items-center text-black hover:text-gray-800 px-3 py-2 rounded-lg hover:bg-gray-50 transition-colors">
                        <i class="fas fa-tachometer-alt mr-2"></i>
                        <span><?php echo $currentLang === 'en' ? 'Dashboard' : 'ড্যাশবোর্ড'; ?></span>
                    </a>
                    
                    <!-- Bookings -->
                    <a href="bookings.php" class="flex items-center text-black hover:text-gray-800 px-3 py-2 rounded-lg hover:bg-gray-50 transition-colors">
                        <i class="fas fa-calendar-alt mr-2"></i>
                        <span><?php echo $currentLang === 'en' ? 'Bookings' : 'বুকিং'; ?></span>
                    </a>
                    
                    <!-- Language Toggle -->
                    <a href="?lang=<?php echo $currentLang === 'en' ? 'bn' : 'en'; ?>" class="text-black hover:text-gray-800 px-3 py-2 rounded-lg hover:bg-gray-50 transition-colors">
                        <?php echo $currentLang === 'en' ? 'বাংলা' : 'EN'; ?>
                    </a>
                    
                    <!-- Profile -->
                    <a href="profile.php" class="text-black hover:text-gray-800 px-3 py-2 rounded-lg hover:bg-gray-50 transition-colors">
                        <?php echo $currentLang === 'en' ? 'Profile' : 'প্রোফাইল'; ?>
                    </a>
                    
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 bg-gradient-to-r from-green-500 to-emerald-500 rounded-full flex items-center justify-center text-white font-semibold">
                            <?php echo strtoupper(substr($provider['name'], 0, 1)); ?>
                        </div>
                        <span class="text-gray-700 font-medium"><?php echo htmlspecialchars($provider['name']); ?></span>
                    </div>
                    <a href="?logout=1" class="logout-btn">
                        <i class="fas fa-sign-out-alt mr-2"></i><?php echo $currentLang === 'en' ? 'Logout' : 'লগআউট'; ?>
                    </a>
                </div>
            </div>
        </nav>
    </header>
    
    <div class="container mx-auto px-4 py-8 relative z-10">
        <?php if ($flash): ?>
            <div class="provider-card p-4 mb-6">
                <div class="flex items-center space-x-3">
                    <i class="fas <?php echo $flash['type'] === 'success' ? 'fa-check-circle text-green-600' : 'fa-exclamation-circle text-red-600'; ?> text-xl"></i>
                    <span class="<?php echo $flash['type'] === 'success' ? 'text-green-700' : 'text-red-700'; ?> font-medium">
                        <?php echo htmlspecialchars($flash['message']); ?>
                    </span>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="max-w-7xl mx-auto">
            <!-- Income Overview -->
            <div class="income-card mb-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold mb-2">
                            <i class="fas fa-hand-holding-usd mr-3"></i><?php echo $currentLang === 'en' ? 'Your Total Income' : 'আপনার মোট আয়'; ?>
                        </h1>
                        <p class="text-green-100 text-lg">
                            <?php echo $currentLang === 'en' ? '80% of verified payments' : 'যাচাইকৃত পেমেন্টের ৮০%'; ?>
                        </p>
                    </div>
                    <div class="text-right">
                        <div class="text-4xl font-bold">৳<?php echo number_format($incomeStats['total_income'] ?? 0, 2); ?></div>
                        <div class="text-green-100">
                            <?php echo $incomeStats['total_earnings'] ?? 0; ?> <?php echo $currentLang === 'en' ? 'transactions' : 'লেনদেন'; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Income Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="provider-card p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm"><?php echo $currentLang === 'en' ? 'Total Earnings' : 'মোট আয়'; ?></p>
                            <p class="text-2xl font-bold text-green-600">৳<?php echo number_format($incomeStats['total_income'] ?? 0, 2); ?></p>
                        </div>
                        <div class="bg-green-100 p-3 rounded-full">
                            <i class="fas fa-hand-holding-usd text-green-600 text-xl"></i>
                        </div>
                    </div>
                </div>
                
                <div class="provider-card p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm"><?php echo $currentLang === 'en' ? 'Platform Fees' : 'প্ল্যাটফর্ম ফি'; ?></p>
                            <p class="text-2xl font-bold text-blue-600">৳<?php echo number_format($incomeStats['total_platform_fees'] ?? 0, 2); ?></p>
                        </div>
                        <div class="bg-blue-100 p-3 rounded-full">
                            <i class="fas fa-percentage text-blue-600 text-xl"></i>
                        </div>
                    </div>
                </div>
                
                <div class="provider-card p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm"><?php echo $currentLang === 'en' ? 'Average Per Job' : 'গড় প্রতি কাজ'; ?></p>
                            <p class="text-2xl font-bold text-purple-600">৳<?php echo number_format($incomeStats['average_earning'] ?? 0, 2); ?></p>
                        </div>
                        <div class="bg-purple-100 p-3 rounded-full">
                            <i class="fas fa-chart-line text-purple-600 text-xl"></i>
                        </div>
                    </div>
                </div>
                
                <div class="provider-card p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm"><?php echo $currentLang === 'en' ? 'Total Processed' : 'মোট প্রক্রিয়াকৃত'; ?></p>
                            <p class="text-2xl font-bold text-orange-600">৳<?php echo number_format($incomeStats['total_processed_amount'] ?? 0, 2); ?></p>
                        </div>
                        <div class="bg-orange-100 p-3 rounded-full">
                            <i class="fas fa-calculator text-orange-600 text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Recent Income Transactions -->
                <div class="provider-card p-8">
                    <div class="flex items-center space-x-4 mb-6">
                        <div class="w-12 h-12 bg-gradient-to-r from-green-500 to-emerald-500 rounded-xl flex items-center justify-center">
                            <i class="fas fa-history text-white text-xl"></i>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-gray-800"><?php echo $currentLang === 'en' ? 'Recent Earnings' : 'সাম্প্রতিক আয়'; ?></h2>
                            <p class="text-gray-600"><?php echo $currentLang === 'en' ? 'Your latest income transactions' : 'আপনার সাম্প্রতিক আয় লেনদেন'; ?></p>
                        </div>
                    </div>
                    
                    <?php if (empty($recentIncome)): ?>
                        <div class="text-center py-12">
                            <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                                <i class="fas fa-hand-holding-usd text-4xl text-gray-300"></i>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-800 mb-3">
                                <?php echo $currentLang === 'en' ? 'No Earnings Yet' : 'এখনও কোন আয় নেই'; ?>
                            </h3>
                            <p class="text-gray-500">
                                <?php echo $currentLang === 'en' ? 'Complete services and get payments verified to see your earnings here.' : 'পরিষেবা সম্পন্ন করুন এবং পেমেন্ট যাচাই করুন আপনার আয় এখানে দেখতে।'; ?>
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($recentIncome as $income): ?>
                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                    <div class="flex items-center space-x-4">
                                        <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                            <i class="fas fa-dollar-sign text-green-600"></i>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-800">
                                                <?php echo htmlspecialchars($income['customer_name']); ?>
                                            </p>
                                            <p class="text-sm text-gray-600">
                                                <?php echo htmlspecialchars($income['service_type']); ?> • 
                                                <?php echo date('M d, Y', strtotime($income['booking_date'])); ?>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-semibold text-green-600">৳<?php echo number_format($income['provider_earnings'], 2); ?></p>
                                        <p class="text-xs text-gray-500">
                                            <?php echo date('M d, Y', strtotime($income['created_at'])); ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Monthly Breakdown -->
                <div class="provider-card p-8">
                    <div class="flex items-center space-x-4 mb-6">
                        <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-indigo-500 rounded-xl flex items-center justify-center">
                            <i class="fas fa-chart-bar text-white text-xl"></i>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-gray-800"><?php echo $currentLang === 'en' ? 'Monthly Breakdown' : 'মাসিক বিভাজন'; ?></h2>
                            <p class="text-gray-600"><?php echo $currentLang === 'en' ? 'Your income by month' : 'মাস অনুযায়ী আপনার আয়'; ?></p>
                        </div>
                    </div>
                    
                    <?php if (empty($monthlyIncome)): ?>
                        <div class="text-center py-12">
                            <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                                <i class="fas fa-chart-bar text-4xl text-gray-300"></i>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-800 mb-3">
                                <?php echo $currentLang === 'en' ? 'No Monthly Data' : 'কোন মাসিক তথ্য নেই'; ?>
                            </h3>
                            <p class="text-gray-500">
                                <?php echo $currentLang === 'en' ? 'Monthly breakdown will appear here once you have earnings.' : 'আপনার আয় থাকলে মাসিক বিভাজন এখানে দেখা যাবে।'; ?>
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($monthlyIncome as $month): ?>
                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                    <div>
                                        <p class="font-medium text-gray-800">
                                            <?php echo date('F Y', strtotime($month['month'] . '-01')); ?>
                                        </p>
                                        <p class="text-sm text-gray-600">
                                            <?php echo $month['transactions']; ?> <?php echo $currentLang === 'en' ? 'transactions' : 'লেনদেন'; ?>
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-semibold text-blue-600">৳<?php echo number_format($month['monthly_income'], 2); ?></p>
                                        <p class="text-xs text-gray-500">
                                            <?php echo $currentLang === 'en' ? 'Platform fee' : 'প্ল্যাটফর্ম ফি'; ?>: ৳<?php echo number_format($month['monthly_platform_fees'], 2); ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Income Information -->
            <div class="provider-card p-8 mt-8">
                <div class="flex items-center space-x-4 mb-6">
                    <div class="w-12 h-12 bg-gradient-to-r from-purple-500 to-pink-500 rounded-xl flex items-center justify-center">
                        <i class="fas fa-info-circle text-white text-xl"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800"><?php echo $currentLang === 'en' ? 'How It Works' : 'কিভাবে কাজ করে'; ?></h2>
                        <p class="text-gray-600"><?php echo $currentLang === 'en' ? 'Understanding your income calculation' : 'আপনার আয় গণনা বুঝুন'; ?></p>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="text-center p-6 bg-blue-50 rounded-lg border border-blue-200">
                        <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-check-circle text-blue-600 text-2xl"></i>
                        </div>
                        <h3 class="font-semibold text-gray-800 mb-2"><?php echo $currentLang === 'en' ? '1. Service Completed' : '১. পরিষেবা সম্পন্ন'; ?></h3>
                        <p class="text-gray-600 text-sm">
                            <?php echo $currentLang === 'en' ? 'Complete the service for your customer' : 'আপনার গ্রাহকের জন্য পরিষেবা সম্পন্ন করুন'; ?>
                        </p>
                    </div>
                    
                    <div class="text-center p-6 bg-green-50 rounded-lg border border-green-200">
                        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-credit-card text-green-600 text-2xl"></i>
                        </div>
                        <h3 class="font-semibold text-gray-800 mb-2"><?php echo $currentLang === 'en' ? '2. Payment Verified' : '২. পেমেন্ট যাচাইকৃত'; ?></h3>
                        <p class="text-gray-600 text-sm">
                            <?php echo $currentLang === 'en' ? 'Admin verifies the customer payment' : 'অ্যাডমিন গ্রাহকের পেমেন্ট যাচাই করেন'; ?>
                        </p>
                    </div>
                    
                    <div class="text-center p-6 bg-purple-50 rounded-lg border border-purple-200">
                        <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-hand-holding-usd text-purple-600 text-2xl"></i>
                        </div>
                        <h3 class="font-semibold text-gray-800 mb-2"><?php echo $currentLang === 'en' ? '3. You Get 80%' : '৩. আপনি ৮০% পান'; ?></h3>
                        <p class="text-gray-600 text-sm">
                            <?php echo $currentLang === 'en' ? '80% of payment goes to you, 20% platform fee' : 'পেমেন্টের ৮০% আপনার কাছে যায়, ২০% প্ল্যাটফর্ম ফি'; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <footer class="bg-white bg-opacity-95 backdrop-blur-sm border-t border-gray-200 py-8 mt-12 relative z-10">
        <div class="container mx-auto px-4 text-center">
            <p class="text-gray-600">&copy; 2024 HOME SERVICE. <?php echo $currentLang === 'en' ? 'All rights reserved.' : 'সর্বস্বত্ব সংরক্ষিত।'; ?></p>
        </div>
    </footer>
</body>
</html>
