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

// Handle delete request
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $work_id = (int)$_GET['delete'];
    $work_request = fetchOne("SELECT * FROM work_requests WHERE id = ? AND customer_id = ?", [$work_id, $user['id']]);
    
    if ($work_request && $work_request['status'] === 'open') {
        if (executeQuery("DELETE FROM work_requests WHERE id = ?", [$work_id])) {
            $success = $currentLang === 'en' ? 'Work request deleted successfully' : 'কাজের অনুরোধ সফলভাবে মুছে ফেলা হয়েছে';
        } else {
            $error = $currentLang === 'en' ? 'Failed to delete work request' : 'কাজের অনুরোধ মুছতে ব্যর্থ';
        }
    } else {
        $error = $currentLang === 'en' ? 'Work request not found or cannot be deleted' : 'কাজের অনুরোধ পাওয়া যায়নি বা মুছে ফেলা যাবে না';
    }
}

// Get user's work requests
$workRequests = fetchAll("
    SELECT wr.*, sc.name as category_name, sc.name_bn as category_name_bn,
           (SELECT COUNT(*) FROM work_bids WHERE work_request_id = wr.id) as bid_count
    FROM work_requests wr
    JOIN service_categories sc ON wr.category_id = sc.id
    WHERE wr.customer_id = ?
    ORDER BY wr.created_at DESC
", [$user['id']]);

// Get work assignments
$workAssignments = fetchAll("
    SELECT wa.*, sp.name as provider_name, sp.phone as provider_phone,
           sc.name as category_name, sc.name_bn as category_name_bn
    FROM work_assignments wa
    JOIN service_providers sp ON wa.provider_id = sp.id
    JOIN work_requests wr ON wa.work_request_id = wr.id
    JOIN service_categories sc ON wr.category_id = sc.id
    WHERE wa.customer_id = ?
    ORDER BY wa.created_at DESC
", [$user['id']]);

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
    <title><?php echo $currentLang === 'en' ? 'Work Hub' : 'আমার কাজের অনুরোধ'; ?> - S24</title>
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
                    <a href="add_work.php" class="btn-primary py-2 px-6 text-xs">
                        <i class="fas fa-plus mr-2"></i>
                        <?php echo $currentLang === 'en' ? 'New Request' : 'নতুন রিকুয়েস্ট'; ?>
                    </a>
                </div>
            </div>
        </nav>
    </header>

    <div class="container mx-auto px-4 py-12 relative z-10">
        <!-- Page Intro -->
        <div class="max-w-7xl mx-auto mb-12">
            <h1 class="text-4xl md:text-5xl font-black text-gray-900 tracking-tighter mb-4">
                <?php echo $currentLang === 'en' ? 'Professional' : 'পেশাদারী'; ?> <span class="text-gradient">Work Hub</span>
            </h1>
            <p class="text-gray-500 font-medium max-w-2xl leading-relaxed italic">
                <?php echo $currentLang === 'en' ? 'Manage your open market requests, review incoming bids, and track assigned missions in real-time.' : 'আপনার ওপেন রিকুয়েস্টগুলি পরিচালনা করুন, বিডগুলি রিভিউ করুন এবং নির্ধারিত কাজগুলি ট্র্যাক করুন।'; ?>
            </p>
        </div>

        <?php if ($error || $success): ?>
            <div class="max-w-7xl mx-auto mb-8 anim-pop-in">
                <div class="glass-card p-4 border-l-4 <?php echo $success ? 'border-emerald-500' : 'border-rose-500'; ?> bg-white/80">
                    <div class="flex items-center gap-3">
                        <i class="fas <?php echo $success ? 'fa-check-circle text-emerald-500' : 'fa-exclamation-circle text-rose-500'; ?> text-xl"></i>
                        <span class="font-bold text-gray-800"><?php echo $error ?: $success; ?></span>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-2 gap-12">
            
            <!-- Open Requests -->
            <section class="space-y-8">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary">
                            <i class="fas fa-broadcast-tower"></i>
                        </div>
                        <h2 class="text-2xl font-black text-gray-900 tracking-tight"><?php echo $currentLang === 'en' ? 'Active Broadcats' : 'সক্রিয় প্রচার'; ?></h2>
                    </div>
                    <span class="text-[10px] font-black uppercase text-gray-400 tracking-[0.2em]"><?php echo count($workRequests); ?> Total</span>
                </div>

                <?php if (empty($workRequests)): ?>
                    <div class="glass-card p-12 text-center border-dashed border-2">
                        <h4 class="text-gray-400 font-black uppercase tracking-widest text-sm mb-6"><?php echo t('Silent Channels'); ?></h4>
                        <a href="add_work.php" class="btn-primary py-4 px-10 rounded-2xl inline-flex items-center gap-3">
                            <i class="fas fa-paper-plane"></i>
                            <span>Post First Request</span>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="space-y-6">
                        <?php foreach ($workRequests as $work): ?>
                            <div class="glass-card p-8 group hover:border-primary/30 transition-all anim-pop-in">
                                <div class="flex justify-between items-start mb-6">
                                    <div>
                                        <div class="flex items-center gap-3 mb-2">
                                            <h3 class="text-xl font-black text-gray-900 leading-tight"><?php echo t($work['category_name']); ?></h3>
                                            <span class="px-2 py-1 bg-primary/10 text-primary text-[8px] font-black uppercase tracking-widest rounded"><?php echo $work['status']; ?></span>
                                        </div>
                                        <p class="text-gray-500 text-sm font-medium line-clamp-2 italic mb-4">"<?php echo htmlspecialchars($work['description']); ?>"</p>
                                    </div>
                                    <?php if ($work['bid_count'] > 0): ?>
                                        <div class="bg-amber-500 text-white px-3 py-1 rounded-xl text-xs font-black shadow-lg shadow-amber-500/20 anim-pulse">
                                            <?php echo $work['bid_count']; ?> BIDS
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="grid grid-cols-2 gap-4 mb-8">
                                    <div class="flex items-center gap-3 text-xs font-bold text-gray-400 bg-gray-50/50 p-3 rounded-xl border border-gray-100">
                                        <i class="fas fa-map-marker-alt text-primary/40"></i>
                                        <span class="truncate"><?php echo htmlspecialchars($work['location']); ?></span>
                                    </div>
                                    <div class="flex items-center gap-3 text-xs font-bold text-gray-400 bg-gray-50/50 p-3 rounded-xl border border-gray-100">
                                        <i class="fas fa-calendar-alt text-primary/40"></i>
                                        <span><?php echo formatDate($work['preferred_date']); ?></span>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between pt-6 border-t border-gray-100">
                                    <div class="flex flex-col">
                                        <span class="text-[8px] font-black text-gray-400 uppercase tracking-widest">Budget Bracket</span>
                                        <span class="text-lg font-black text-gray-900">৳<?php echo number_format($work['budget_min']); ?> - <?php echo number_format($work['budget_max']); ?></span>
                                    </div>
                                    <div class="flex gap-2">
                                        <?php if ($work['status'] === 'open'): ?>
                                            <a href="view_bids.php?work_id=<?php echo $work['id']; ?>" class="w-12 h-12 bg-primary rounded-xl flex items-center justify-center text-white shadow-lg shadow-primary/20 hover:scale-110 transition-transform">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit_work.php?id=<?php echo $work['id']; ?>" class="w-12 h-12 bg-gray-900 rounded-xl flex items-center justify-center text-white hover:scale-110 transition-transform">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="?delete=<?php echo $work['id']; ?>" onclick="return confirm('Archive this request?')" class="w-12 h-12 bg-rose-50 text-rose-500 rounded-xl flex items-center justify-center hover:bg-rose-500 hover:text-white transition-all">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="work_details.php?id=<?php echo $work['id']; ?>" class="btn-primary py-3 px-6 text-xs"><?php echo t('Details'); ?></a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Assignments -->
            <section class="space-y-8">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-500/10 flex items-center justify-center text-emerald-600">
                            <i class="fas fa-handshake"></i>
                        </div>
                        <h2 class="text-2xl font-black text-gray-900 tracking-tight"><?php echo $currentLang === 'en' ? 'Locked Contracts' : 'চুক্তিভিত্তিক কাজ'; ?></h2>
                    </div>
                    <span class="text-[10px] font-black uppercase text-gray-400 tracking-[0.2em]"><?php echo count($workAssignments); ?> Active</span>
                </div>

                <?php if (empty($workAssignments)): ?>
                    <div class="glass-card p-12 text-center bg-gray-50/30">
                        <p class="text-gray-400 font-bold italic"><?php echo $currentLang === 'en' ? 'No confirmed assignments yet.' : 'এখনও কোনো নির্ধারিত কাজ নেই।'; ?></p>
                    </div>
                <?php else: ?>
                    <div class="space-y-6">
                        <?php foreach ($workAssignments as $assignment): ?>
                            <div class="glass-card p-8 bg-gradient-to-br from-white to-gray-50/50 border-emerald-500/20 group anim-pop-in">
                                <div class="flex items-center gap-5 mb-8">
                                    <div class="w-14 h-14 bg-white rounded-2xl flex items-center justify-center text-emerald-500 text-2xl font-black shadow-lg shadow-emerald-500/5">
                                        <?php echo strtoupper(substr($assignment['provider_name'], 0, 1)); ?>
                                    </div>
                                    <div class="flex-1">
                                        <h4 class="text-lg font-black text-gray-900"><?php echo htmlspecialchars($assignment['provider_name']); ?></h4>
                                        <div class="flex items-center gap-3">
                                            <span class="text-[10px] font-black uppercase tracking-widest text-emerald-600"><?php echo t($assignment['category_name']); ?></span>
                                            <span class="w-1 h-1 bg-gray-300 rounded-full"></span>
                                            <span class="text-[10px] font-black uppercase tracking-widest text-gray-400"><?php echo $assignment['status']; ?></span>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-[8px] font-black text-gray-400 uppercase tracking-widest block mb-1">Contract Sum</span>
                                        <span class="text-xl font-black text-emerald-600">৳<?php echo number_format($assignment['final_amount']); ?></span>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between gap-4">
                                    <div class="flex gap-3">
                                        <a href="tel:<?php echo $assignment['provider_phone']; ?>" class="w-12 h-12 rounded-xl bg-gray-900 flex items-center justify-center text-white hover:bg-emerald-600 transition-all shadow-xl shadow-gray-900/10">
                                            <i class="fas fa-phone-alt"></i>
                                        </a>
                                        <a href="https://wa.me/<?php echo $assignment['provider_phone']; ?>" class="w-12 h-12 rounded-xl bg-emerald-500 flex items-center justify-center text-white shadow-xl shadow-emerald-500/10 hover:scale-110 transition-transform">
                                            <i class="fab fa-whatsapp"></i>
                                        </a>
                                    </div>
                                    <a href="assignment_details.php?id=<?php echo $assignment['id']; ?>" class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-800 font-black text-[10px] uppercase tracking-[0.2em] py-4 rounded-xl text-center transition-all">
                                        View Mission Protocol
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
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
    </script>
</body>
</html>