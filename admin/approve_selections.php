<?php
require_once '../includes/functions.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    redirect('login.php');
}

$currentLang = getLanguage();
$admin = getCurrentAdmin();

// Handle approve/reject actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['selection_id'])) {
    $selectionId = (int)$_POST['selection_id'];
    $action = $_POST['action'];
    
    if ($action === 'approve') {
        if (approveSelection($selectionId, $admin['id'])) {
            setFlashMessage('success', 'Provider selection approved successfully');
        } else {
            setFlashMessage('error', 'Failed to approve selection');
        }
    } elseif ($action === 'reject') {
        $reason = $_POST['reason'] ?? '';
        if (rejectSelection($selectionId, $admin['id'], $reason)) {
            setFlashMessage('success', 'Provider selection rejected');
        } else {
            setFlashMessage('error', 'Failed to reject selection');
        }
    }
    
    redirect('approve_selections.php');
}

// Get filter parameters
$status = $_GET['status'] ?? 'pending';
$search = $_GET['search'] ?? '';

// Build query conditions
$whereConditions = [];
$params = [];

if ($status === 'pending') {
    $whereConditions[] = "(cps.admin_approved = FALSE OR cps.admin_approved IS NULL)";
    $whereConditions[] = "cps.status IN ('pending', 'contacted')";
} elseif ($status === 'approved') {
    $whereConditions[] = "cps.admin_approved = TRUE";
} elseif ($status === 'all') {
    // No filter
} else {
    $whereConditions[] = "cps.status = ?";
    $params[] = $status;
}

if ($search) {
    $whereConditions[] = "(u.name LIKE ? OR sp.name LIKE ? OR cps.service_type LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get selections
$selections = fetchAll("
    SELECT cps.*, 
           u.name as customer_name, u.email as customer_email, u.phone as customer_phone,
           sp.name as provider_name, sp.phone as provider_phone,
           sc.name as category_name, sc.name_bn as category_name_bn,
           a.username as approved_by
    FROM customer_provider_selections cps
    JOIN users u ON cps.customer_id = u.id
    JOIN service_providers sp ON cps.provider_id = sp.id
    JOIN service_categories sc ON cps.category_id = sc.id
    LEFT JOIN admins a ON cps.approved_by_admin_id = a.id
    $whereClause
    ORDER BY cps.created_at DESC
", $params);

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
    <title><?php echo $currentLang === 'en' ? 'Approve Provider Selections' : 'প্রদানকারী নির্বাচন অনুমোদন'; ?> - Home Service Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/ui.css">
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm sticky top-0 z-50">
        <nav class="container mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-6">
                    <a href="../index.php" class="text-3xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent uppercase">
                        Home Service
                    </a>
                    <span class="text-gray-700 font-semibold text-lg">
                        <i class="fas fa-shield-alt text-purple-600 mr-3"></i>
                        <?php echo $currentLang === 'en' ? 'Admin - Approve Selections' : 'অ্যাডমিন - নির্বাচন অনুমোদন'; ?>
                    </span>
                </div>
                
                <div class="flex items-center space-x-4">
                    <a href="dashboard.php" class="text-gray-700 hover:text-purple-600 px-3 py-2 rounded-lg hover:bg-gray-50 transition-colors">
                        <i class="fas fa-arrow-left mr-2"></i><?php echo $currentLang === 'en' ? 'Dashboard' : 'ড্যাশবোর্ড'; ?>
                    </a>
                    <a href="providers.php" class="text-gray-700 hover:text-purple-600 px-3 py-2 rounded-lg hover:bg-gray-50 transition-colors">
                        <i class="fas fa-users mr-2"></i><?php echo $currentLang === 'en' ? 'Providers' : 'প্রদানকারী'; ?>
                    </a>
                    <a href="?logout=1" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors">
                        <i class="fas fa-sign-out-alt mr-2"></i><?php echo $currentLang === 'en' ? 'Logout' : 'লগআউট'; ?>
                    </a>
                </div>
            </div>
        </nav>
    </header>

    <div class="container mx-auto px-4 py-8">
        <!-- Page Header -->
        <div class="bg-white rounded-xl shadow-md p-6 mb-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800 mb-2">
                        <i class="fas fa-check-circle text-green-600 mr-3"></i>
                        <?php echo $currentLang === 'en' ? 'Provider Selection Approvals' : 'প্রদানকারী নির্বাচন অনুমোদন'; ?>
                    </h1>
                    <p class="text-gray-600">
                        <?php echo $currentLang === 'en' ? 'Review and approve customer provider selections' : 'গ্রাহক প্রদানকারী নির্বাচন পর্যালোচনা এবং অনুমোদন করুন'; ?>
                    </p>
                </div>
                <div class="text-right">
                    <div class="text-3xl font-bold text-purple-600"><?php echo count($selections); ?></div>
                    <div class="text-gray-600">
                        <?php echo $currentLang === 'en' ? 'Total Selections' : 'মোট নির্বাচন'; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Flash Messages -->
        <?php $flash = getFlashMessage(); if ($flash): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo $flash['type'] === 'success' ? 'bg-green-100 text-green-700 border border-green-300' : 'bg-red-100 text-red-700 border border-red-300'; ?>">
                <div class="flex items-center">
                    <i class="fas <?php echo $flash['type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mr-3 text-xl"></i>
                    <span class="font-medium"><?php echo htmlspecialchars($flash['message']); ?></span>
                </div>
            </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-md p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">
                <i class="fas fa-filter text-purple-600 mr-2"></i>
                <?php echo $currentLang === 'en' ? 'Filter Selections' : 'নির্বাচন ফিল্টার করুন'; ?>
            </h3>
            <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <?php echo $currentLang === 'en' ? 'Status' : 'অবস্থা'; ?>
                    </label>
                    <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>
                            <?php echo $currentLang === 'en' ? 'Pending Approval' : 'অনুমোদনের অপেক্ষায়'; ?>
                        </option>
                        <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>
                            <?php echo $currentLang === 'en' ? 'Approved' : 'অনুমোদিত'; ?>
                        </option>
                        <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>
                            <?php echo $currentLang === 'en' ? 'Rejected' : 'প্রত্যাখ্যাত'; ?>
                        </option>
                        <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>
                            <?php echo $currentLang === 'en' ? 'All' : 'সব'; ?>
                        </option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <?php echo $currentLang === 'en' ? 'Search' : 'অনুসন্ধান'; ?>
                    </label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="<?php echo $currentLang === 'en' ? 'Customer, provider, service...' : 'গ্রাহক, প্রদানকারী, পরিষেবা...'; ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                </div>
                
                <div class="flex items-end">
                    <button type="submit" class="w-full bg-purple-600 text-white py-2 px-6 rounded-lg hover:bg-purple-700 transition-colors font-semibold">
                        <i class="fas fa-search mr-2"></i><?php echo $currentLang === 'en' ? 'Filter' : 'ফিল্টার'; ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- Selections List -->
        <?php if (empty($selections)): ?>
            <div class="bg-white rounded-xl shadow-md p-12 text-center">
                <div class="w-24 h-24 bg-gradient-to-br from-purple-100 to-pink-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-inbox text-4xl text-purple-500"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-700 mb-2">
                    <?php echo $currentLang === 'en' ? 'No Selections Found' : 'কোনো নির্বাচন পাওয়া যায়নি'; ?>
                </h3>
                <p class="text-gray-500">
                    <?php echo $currentLang === 'en' ? 'Try adjusting your filters' : 'আপনার ফিল্টার সামঞ্জস্য করুন'; ?>
                </p>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($selections as $selection): ?>
                    <div class="bg-white rounded-xl shadow-md p-6 hover:shadow-lg transition-shadow">
                        <div class="flex justify-between items-start mb-4">
                            <div class="flex items-center space-x-3">
                                <?php if ($selection['admin_approved']): ?>
                                    <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-semibold">
                                        <i class="fas fa-check-circle mr-1"></i><?php echo $currentLang === 'en' ? 'Approved' : 'অনুমোদিত'; ?>
                                    </span>
                                <?php else: ?>
                                    <span class="px-3 py-1 bg-yellow-100 text-yellow-700 rounded-full text-sm font-semibold animate-pulse">
                                        <i class="fas fa-clock mr-1"></i><?php echo $currentLang === 'en' ? 'Pending' : 'অপেক্ষমান'; ?>
                                    </span>
                                <?php endif; ?>
                                <span class="text-sm text-gray-500">
                                    <?php echo formatDateTime($selection['created_at']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-4">
                            <!-- Customer Info -->
                            <div class="border-l-4 border-blue-500 pl-4">
                                <h4 class="font-semibold text-gray-800 mb-2">
                                    <i class="fas fa-user text-blue-500 mr-2"></i>
                                    <?php echo $currentLang === 'en' ? 'Customer' : 'গ্রাহক'; ?>
                                </h4>
                                <p class="text-gray-700 font-medium"><?php echo htmlspecialchars($selection['customer_name']); ?></p>
                                <p class="text-sm text-gray-600"><i class="fas fa-phone mr-1"></i><?php echo htmlspecialchars($selection['customer_phone']); ?></p>
                                <p class="text-sm text-gray-600"><i class="fas fa-envelope mr-1"></i><?php echo htmlspecialchars($selection['customer_email']); ?></p>
                            </div>
                            
                            <!-- Provider Info -->
                            <div class="border-l-4 border-purple-500 pl-4">
                                <h4 class="font-semibold text-gray-800 mb-2">
                                    <i class="fas fa-user-tie text-purple-500 mr-2"></i>
                                    <?php echo $currentLang === 'en' ? 'Provider' : 'প্রদানকারী'; ?>
                                </h4>
                                <p class="text-gray-700 font-medium"><?php echo htmlspecialchars($selection['provider_name']); ?></p>
                                <p class="text-sm text-gray-600"><i class="fas fa-phone mr-1"></i><?php echo htmlspecialchars($selection['provider_phone']); ?></p>
                                <p class="text-sm text-gray-600"><i class="fas fa-tag mr-1"></i><?php echo $currentLang === 'en' ? $selection['category_name'] : $selection['category_name_bn']; ?></p>
                            </div>
                            
                            <!-- Service Info -->
                            <div class="border-l-4 border-green-500 pl-4">
                                <h4 class="font-semibold text-gray-800 mb-2">
                                    <i class="fas fa-tools text-green-500 mr-2"></i>
                                    <?php echo $currentLang === 'en' ? 'Service' : 'সেবা'; ?>
                                </h4>
                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($selection['service_type']); ?></p>
                                <p class="text-sm text-gray-600"><i class="fas fa-calendar mr-1"></i><?php echo formatDate($selection['preferred_date']); ?></p>
                                <p class="text-sm text-gray-600"><i class="fas fa-clock mr-1"></i><?php echo $selection['preferred_time']; ?></p>
                            </div>
                        </div>
                        
                        <?php if ($selection['customer_notes']): ?>
                            <div class="bg-gray-50 rounded-lg p-3 mb-4">
                                <p class="text-sm text-gray-700">
                                    <i class="fas fa-comment mr-2"></i><strong><?php echo $currentLang === 'en' ? 'Notes:' : 'নোট:'; ?></strong> 
                                    <?php echo htmlspecialchars($selection['customer_notes']); ?>
                                </p>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Action Buttons -->
                        <?php if (!$selection['admin_approved']): ?>
                            <div class="flex space-x-3 pt-4 border-t border-gray-200">
                                <form method="POST" class="inline">
                                    <input type="hidden" name="selection_id" value="<?php echo $selection['id']; ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" 
                                            onclick="return confirm('<?php echo $currentLang === 'en' ? 'Approve this selection?' : 'এই নির্বাচন অনুমোদন করবেন?'; ?>')"
                                            class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition-colors font-semibold">
                                        <i class="fas fa-check mr-2"></i><?php echo $currentLang === 'en' ? 'Approve' : 'অনুমোদন করুন'; ?>
                                    </button>
                                </form>
                                
                                <button onclick="showRejectDialog(<?php echo $selection['id']; ?>)" 
                                        class="bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700 transition-colors font-semibold">
                                    <i class="fas fa-times mr-2"></i><?php echo $currentLang === 'en' ? 'Reject' : 'প্রত্যাখ্যান'; ?>
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="pt-4 border-t border-gray-200">
                                <span class="text-sm text-gray-600">
                                    <i class="fas fa-user-shield mr-2"></i>
                                    <?php echo $currentLang === 'en' ? 'Approved by:' : 'অনুমোদনকারী:'; ?> 
                                    <strong><?php echo htmlspecialchars($selection['approved_by'] ?? 'Admin'); ?></strong>
                                    on <?php echo formatDateTime($selection['admin_approved_at']); ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Reject Dialog -->
    <div id="rejectDialog" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-xl p-6 max-w-md w-full mx-4">
            <h3 class="text-xl font-bold text-gray-800 mb-4">
                <?php echo $currentLang === 'en' ? 'Reject Selection' : 'নির্বাচন প্রত্যাখ্যান'; ?>
            </h3>
            <form method="POST" id="rejectForm">
                <input type="hidden" name="selection_id" id="reject_selection_id">
                <input type="hidden" name="action" value="reject">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <?php echo $currentLang === 'en' ? 'Reason (Optional)' : 'কারণ (ঐচ্ছিক)'; ?>
                    </label>
                    <textarea name="reason" rows="3" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                              placeholder="<?php echo $currentLang === 'en' ? 'Why is this selection being rejected?' : 'কেন এই নির্বাচন প্রত্যাখ্যান করা হচ্ছে?'; ?>"></textarea>
                </div>
                <div class="flex space-x-3">
                    <button type="submit" class="flex-1 bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors font-semibold">
                        <?php echo $currentLang === 'en' ? 'Confirm Reject' : 'প্রত্যাখ্যান নিশ্চিত করুন'; ?>
                    </button>
                    <button type="button" onclick="hideRejectDialog()" class="flex-1 bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition-colors font-semibold">
                        <?php echo $currentLang === 'en' ? 'Cancel' : 'বাতিল'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showRejectDialog(selectionId) {
            document.getElementById('reject_selection_id').value = selectionId;
            document.getElementById('rejectDialog').classList.remove('hidden');
        }
        
        function hideRejectDialog() {
            document.getElementById('rejectDialog').classList.add('hidden');
        }
    </script>
</body>
</html>
