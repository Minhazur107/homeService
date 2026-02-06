<?php
require_once '../includes/functions.php';

if (!isAdminLoggedIn()) {
	redirect('login.php');
}

$currentLang = getLanguage();
$admin = getCurrentAdmin();

$selectionId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$selection = fetchOne("\n\tSELECT cps.*, u.name as customer_name, sp.name as provider_name, sc.name as category_name, sc.name_bn as category_name_bn\n\tFROM customer_provider_selections cps\n\tJOIN users u ON cps.customer_id = u.id\n\tJOIN service_providers sp ON cps.provider_id = sp.id\n\tJOIN service_categories sc ON cps.category_id = sc.id\n\tWHERE cps.id = ?\n", [$selectionId]);

if (!$selection) {
	setFlashMessage('error', 'Selection not found');
	redirect('bookings.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$serviceType = sanitizeInput($_POST['service_type']);
	$preferredDate = $_POST['preferred_date'];
	$preferredTime = $_POST['preferred_time'];
	$customerAddress = sanitizeInput($_POST['customer_address']);
	$customerNotes = sanitizeInput($_POST['customer_notes']);
	$status = $_POST['status'];
	
	if (empty($preferredDate)) {
		$error = $currentLang === 'en' ? 'Preferred date is required' : 'পছন্দের তারিখ প্রয়োজন';
	} elseif (strtotime($preferredDate) < strtotime('today')) {
		$error = $currentLang === 'en' ? 'Preferred date cannot be in the past' : 'পছন্দের তারিখ অতীত হতে পারে না';
	} elseif (empty($preferredTime)) {
		$error = $currentLang === 'en' ? 'Preferred time is required' : 'পছন্দের সময় প্রয়োজন';
	} elseif (empty($customerAddress)) {
		$error = $currentLang === 'en' ? 'Service address is required' : 'সেবার ঠিকানা প্রয়োজন';
	} elseif (!in_array($status, ['pending', 'contacted', 'accepted', 'rejected', 'expired'])) {
		$error = 'Invalid status';
	} else {
		try {
			executeQuery("\n\t\t\tUPDATE customer_provider_selections\n\t\t\tSET service_type = ?, preferred_date = ?, preferred_time = ?, customer_address = ?, customer_notes = ?, status = ?, updated_at = NOW()\n\t\t\tWHERE id = ?\n\t\t", [
				$serviceType ?: null,
				$preferredDate,
				$preferredTime,
				$customerAddress,
				$customerNotes ?: null,
				$status,
				$selection['id']
			]);
			setFlashMessage('success', 'Selection updated successfully');
			redirect('bookings.php');
		} catch (Exception $e) {
			$error = $currentLang === 'en' ? 'Failed to update selection' : 'নির্বাচন হালনাগাদ ব্যর্থ হয়েছে';
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
    <title><?php echo $currentLang === 'en' ? 'Edit Selection' : 'নির্বাচন সম্পাদনা'; ?> - Home Service Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/ui.css">
    <script src="../assets/ui.js"></script>
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
                    <span class="text-gray-700 font-bold text-lg hidden sm:inline-block border-r border-gray-100 pr-6 mr-6">
                        <i class="fas fa-shield-alt text-primary mr-2"></i>
                        <?php echo $currentLang === 'en' ? 'Admin Control' : 'অ্যাডমিন কন্ট্রোল'; ?>
                    </span>
                </div>
                
                <div class="flex items-center space-x-4">
                    <a href="bookings.php" class="font-bold text-gray-500 hover:text-primary transition-colors flex items-center gap-2">
                        <i class="fas fa-arrow-left"></i>
                        <span><?php echo $currentLang === 'en' ? 'Back' : 'ফিরে যান'; ?></span>
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

    <div class="container mx-auto px-4 py-12 relative z-10">
        <div class="max-w-4xl mx-auto">
            <div class="glass-card p-10">
                <div class="flex items-center justify-between mb-8 border-b border-gray-100 pb-6">
                    <div>
                        <h1 class="text-4xl font-black text-gray-900 tracking-tighter mb-2">
                            <?php echo $currentLang === 'en' ? 'Unlocking Potential' : 'নির্বাচন সম্পাদনা'; ?>
                        </h1>
                        <p class="text-gray-500 font-medium">
                            <?php echo $currentLang === 'en' ? 'Modify selection details and status' : 'নির্বাচনের বিবরণ এবং অবস্থা পরিবর্তন করুন'; ?>
                        </p>
                    </div>
                    <div class="w-16 h-16 bg-primary/10 rounded-2xl flex items-center justify-center text-primary text-3xl shadow-inner">
                        <i class="fas fa-edit"></i>
                    </div>
                </div>

                <?php if ($error): ?>
                    <div class="mb-8 p-5 bg-rose-50 text-rose-600 rounded-2xl border border-rose-100 font-bold flex items-center gap-3 anim-shake">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo $error; ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- Service Type -->
                        <div class="space-y-3">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1"><?php echo $currentLang === 'en' ? 'Service Type' : 'সেবার ধরন'; ?></label>
                            <input type="text" name="service_type" 
                                   value="<?php echo htmlspecialchars($_POST['service_type'] ?? ($selection['service_type'] ?? '')); ?>" 
                                   class="w-full px-5 py-4 rounded-xl bg-gray-50 border-2 border-gray-100 focus:bg-white focus:border-primary focus:outline-none font-bold text-gray-800 transition-all">
                        </div>

                        <!-- Status -->
                        <div class="space-y-3">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1"><?php echo $currentLang === 'en' ? 'Status' : 'অবস্থা'; ?> *</label>
                            <div class="relative">
                                <select name="status" class="w-full px-5 py-4 rounded-xl bg-gray-50 border-2 border-gray-100 focus:bg-white focus:border-primary focus:outline-none font-bold text-gray-800 transition-all appearance-none" required>
                                    <?php foreach (['pending','contacted','accepted','rejected','expired'] as $st): ?>
                                        <option value="<?php echo $st; ?>" <?php echo ($selection['status'] === $st ? 'selected' : ''); ?>>
                                            <?php echo ucfirst($st); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <i class="fas fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                            </div>
                        </div>

                        <!-- Date & Time -->
                        <div class="space-y-3">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1"><?php echo $currentLang === 'en' ? 'Preferred Date' : 'পছন্দের তারিখ'; ?> *</label>
                            <input type="date" name="preferred_date" 
                                   value="<?php echo htmlspecialchars($_POST['preferred_date'] ?? $selection['preferred_date']); ?>" 
                                   min="<?php echo date('Y-m-d'); ?>" 
                                   class="w-full px-5 py-4 rounded-xl bg-gray-50 border-2 border-gray-100 focus:bg-white focus:border-primary focus:outline-none font-bold text-gray-800 transition-all shadow-sm" required>
                        </div>

                        <div class="space-y-3">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1"><?php echo $currentLang === 'en' ? 'Preferred Time' : 'পছন্দের সময়'; ?> *</label>
                            <input type="time" name="preferred_time" 
                                   value="<?php echo htmlspecialchars($_POST['preferred_time'] ?? $selection['preferred_time']); ?>" 
                                   class="w-full px-5 py-4 rounded-xl bg-gray-50 border-2 border-gray-100 focus:bg-white focus:border-primary focus:outline-none font-bold text-gray-800 transition-all shadow-sm" required>
                        </div>
                    </div>

                    <!-- Address & Notes -->
                    <div class="space-y-3">
                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1"><?php echo $currentLang === 'en' ? 'Service Address' : 'সেবার ঠিকানা'; ?> *</label>
                        <div class="relative">
                            <i class="fas fa-map-marker-alt absolute top-5 left-5 text-gray-300"></i>
                            <textarea name="customer_address" rows="3" 
                                      class="w-full pl-12 pr-5 py-4 rounded-xl bg-gray-50 border-2 border-gray-100 focus:bg-white focus:border-primary focus:outline-none font-bold text-gray-800 transition-all" required><?php echo htmlspecialchars($_POST['customer_address'] ?? ($selection['customer_address'] ?? '')); ?></textarea>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1"><?php echo $currentLang === 'en' ? 'Customer Notes' : 'গ্রাহকের নোট'; ?></label>
                        <textarea name="customer_notes" rows="4" 
                                  class="w-full px-5 py-4 rounded-xl bg-gray-50 border-2 border-gray-100 focus:bg-white focus:border-primary focus:outline-none font-bold text-gray-800 transition-all"><?php echo htmlspecialchars($_POST['customer_notes'] ?? ($selection['customer_notes'] ?? '')); ?></textarea>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-end gap-4 pt-4 border-t border-gray-100">
                        <a href="bookings.php" class="px-8 py-4 rounded-xl font-bold text-gray-500 hover:text-rose-500 hover:bg-rose-50 transition-all text-sm">
                            <?php echo $currentLang === 'en' ? 'Cancel' : 'বাতিল'; ?>
                        </a>
                        <button type="submit" class="btn-primary py-4 px-10 flex items-center gap-2 shadow-xl shadow-primary/30">
                            <i class="fas fa-save"></i>
                            <span><?php echo $currentLang === 'en' ? 'Save Changes' : 'পরিবর্তন সংরক্ষণ করুন'; ?></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <footer class="glass-nav py-8 mt-12 bg-white/50 border-t border-gray-100">
        <div class="container mx-auto px-4 text-center">
            <p class="text-gray-500 font-bold text-sm uppercase tracking-widest">&copy; <?php echo date('Y'); ?> Home Service. <?php echo $currentLang === 'en' ? 'All rights reserved.' : 'সর্বস্বত্ব সংরক্ষিত।'; ?></p>
        </div>
    </footer>
</body>
</html> 