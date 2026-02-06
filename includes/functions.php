<?php
session_start();

// Support language switching via ?lang= (e.g. ?lang=bn or ?lang=en)
if (isset($_GET['lang'])) {
    $lang = $_GET['lang'];
    if (in_array($lang, ['en', 'bn'])) {
        setLanguage($lang);
    }
}

require_once dirname(__FILE__) . '/../config/database.php';

// Authentication functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isProviderLoggedIn() {
    return isset($_SESSION['provider_id']);
}

function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

function getCurrentUser() {
    if (isLoggedIn()) {
        return fetchOne("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
    }
    return null;
}

function getCurrentProvider() {
    if (isProviderLoggedIn()) {
        return fetchOne("SELECT * FROM service_providers WHERE id = ?", [$_SESSION['provider_id']]);
    }
    return null;
}

function getCurrentAdmin() {
    if (isAdminLoggedIn()) {
        return fetchOne("SELECT * FROM admins WHERE id = ?", [$_SESSION['admin_id']]);
    }
    return null;
}

// Logout function
function logout() {
    // Clear all session variables
    $_SESSION = array();
    
    // Destroy the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
}

// Language functions
function getLanguage() {
    return $_SESSION['language'] ?? 'en';
}

function setLanguage($lang) {
    $_SESSION['language'] = $lang;
}

function t($key) {
    $translations = [
        'en' => [
            'search' => 'Search',
            'location' => 'Location',
            'price_range' => 'Price Range',
            'join_as_provider' => 'Join as Service Provider',
            'view_profile' => 'View Profile',
            'book_now' => 'Book Now',
            'call' => 'Call',
            'whatsapp' => 'WhatsApp',
            'rating' => 'Rating',
            'verified' => 'Verified',
            'pending' => 'Pending',
            'confirmed' => 'Confirmed',
            'cancelled' => 'Cancelled',
            'completed' => 'Completed',
            'login' => 'Login',
            'register' => 'Register',
            'logout' => 'Logout',
            'dashboard' => 'Dashboard',
            'profile' => 'Profile',
            'bookings' => 'Bookings',
            'reviews' => 'Reviews',
            'settings' => 'Settings',
            'open' => 'Open',
            'in_progress' => 'In Progress',
            'assigned' => 'Assigned',
            'selected' => 'Selected',
            'contacted' => 'Contacted',
            'booked' => 'Booked'
            ,
            'accepted' => 'Accepted',
            'rejected' => 'Rejected',
            'expired' => 'Expired'
            ,
            'verified' => 'Verified'
        ],
        'bn' => [
            'search' => 'অনুসন্ধান',
            'location' => 'অবস্থান',
            'price_range' => 'মূল্য পরিসীমা',
            'join_as_provider' => 'সেবা প্রদানকারী হিসেবে যোগ দিন',
            'view_profile' => 'প্রোফাইল দেখুন',
            'book_now' => 'এখনই বুক করুন',
            'call' => 'কল করুন',
            'whatsapp' => 'হোয়াটসঅ্যাপ',
            'rating' => 'রেটিং',
            'verified' => 'যাচাইকৃত',
            'pending' => 'অপেক্ষমান',
            'confirmed' => 'নিশ্চিত',
            'cancelled' => 'বাতিল',
            'completed' => 'সম্পন্ন',
            'login' => 'লগইন',
            'register' => 'নিবন্ধন',
            'logout' => 'লগআউট',
            'dashboard' => 'ড্যাশবোর্ড',
            'profile' => 'প্রোফাইল',
            'bookings' => 'বুকিং',
            'reviews' => 'পর্যালোচনা',
            'settings' => 'সেটিংস',
            'open' => 'খোলা',
            'in_progress' => 'চলমান',
            'assigned' => 'নিয়োগকৃত',
            'selected' => 'নির্বাচিত',
            'contacted' => 'যোগাযোগ করা হয়েছে',
            'booked' => 'বুক করা হয়েছে',
            'accepted' => 'গৃহীত',
            'rejected' => 'প্রত্যাখ্যাত',
            'expired' => 'মেয়াদোত্তীর্ণ',
            'verified' => 'যাচাইকৃত'
        ]
    ];
    
    $lang = getLanguage();
    return $translations[$lang][$key] ?? $key;
}

// Notification functions
/**
 * Create a notification for a user
 */
function createNotification($userId, $userType, $title, $message, $type = 'general', $relatedId = null) {
    try {
        executeQuery("
            INSERT INTO notifications (user_id, user_type, title, message, type, related_id) 
            VALUES (?, ?, ?, ?, ?, ?)
        ", [$userId, $userType, $title, $message, $type, $relatedId]);
        return true;
    } catch (Exception $e) {
        error_log("Error creating notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Get all notifications for a user
 */
function getAllNotifications($userId, $userType = null, $limit = 50) {
    // Ensure limit is always an integer
    $limit = (int)$limit;
    
    $sql = "SELECT * FROM notifications WHERE user_id = ?";
    $params = [$userId];
    
    if ($userType) {
        $sql .= " AND user_type = ?";
        $params[] = $userType;
    }
    
    $sql .= " ORDER BY created_at DESC";
    
    if ($limit > 0) {
        $sql .= " LIMIT ?";
        $params[] = $limit;
    }
    
    return fetchAll($sql, $params);
}

/**
 * Get unread notifications for a user
 */
function getUnreadNotifications($userId, $userType = null) {
    $sql = "SELECT * FROM notifications WHERE user_id = ? AND is_read = 0";
    $params = [$userId];
    
    if ($userType) {
        $sql .= " AND user_type = ?";
        $params[] = $userType;
    }
    
    $sql .= " ORDER BY created_at DESC";
    return fetchAll($sql, $params);
}

/**
 * Mark notification as read
 */
function markNotificationAsRead($notificationId) {
    executeQuery("UPDATE notifications SET is_read = 1 WHERE id = ?", [$notificationId]);
}

/**
 * Mark all notifications as read for a user
 */
function markAllNotificationsAsRead($userId) {
    executeQuery("UPDATE notifications SET is_read = 1 WHERE user_id = ?", [$userId]);
}

/**
 * Get notification count for a user
 */
function getNotificationCount($userId, $userType = null) {
    $sql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
    $params = [$userId];
    
    if ($userType) {
        $sql .= " AND user_type = ?";
        $params[] = $userType;
    }
    
    return fetchOne($sql, $params)['count'];
}

/**
 * Send booking approval notification to customer
 */
function sendBookingApprovalNotification($bookingId, $customerId, $providerName) {
    $title = "Booking Approved!";
    $message = "Your booking has been approved by $providerName. They will contact you soon to arrange the service.";
    
    return createNotification($customerId, 'customer', $title, $message, 'booking_approved', $bookingId);
}

/**
 * Send booking confirmation notification to provider
 */
function sendBookingConfirmationNotification($bookingId, $providerId, $customerName) {
    $title = "New Booking Confirmed";
    $message = "You have a new confirmed booking from $customerName. Check your dashboard for customer details.";
    
    return createNotification($providerId, 'provider', $title, $message, 'booking_confirmed', $bookingId);
}

// Validation functions
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validatePhone($phone) {
    return preg_match('/^(\+880|880|0)?1[3456789]\d{8}$/', $phone);
}

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// File upload functions
function uploadFile($file, $directory = 'uploads/') {
    if (!file_exists($directory)) {
        mkdir($directory, 0777, true);
    }
    
    $fileName = time() . '_' . basename($file['name']);
    $targetPath = $directory . $fileName;
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return $fileName;
    }
    return false;
}

// Price formatting
function formatPrice($price) {
    return '৳' . number_format($price, 0);
}

// Date formatting
function formatDate($date) {
    return date('d M Y', strtotime($date));
}

function formatDateTime($datetime) {
    return date('d M Y, h:i A', strtotime($datetime));
}

// Rating functions
function getAverageRating($providerId) {
    $result = fetchOne("SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews FROM reviews WHERE provider_id = ? AND status = 'approved'", [$providerId]);
    return [
        'average' => round($result['avg_rating'], 1),
        'total' => $result['total_reviews']
    ];
}

// Redirect function
function redirect($url) {
    header("Location: $url");
    exit();
}

// Flash message functions
function setFlashMessage($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

function getFlashMessage() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// CSRF protection
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Dhaka locations
function getDhakaLocations() {
    return [
        'en' => [
            'Dhanmondi', 'Gulshan', 'Banani', 'Baridhara', 'Mirpur', 'Pallabi', 'Kafrul',
            'Uttara', 'Tongi', 'Gazipur', 'Mohammadpur', 'Adabor', 'Lalbagh', 'Old Dhaka',
            'Badda', 'Rampura', 'Khilgaon', 'Jatrabari', 'Demra', 'Shyampur', 'Motijheel',
            'Paltan', 'Ramna', 'Farmgate', 'Shahbagh', 'New Market', 'Wari', 'Sutrapur',
            'Kotwali', 'Hazaribagh', 'Kamrangirchar', 'Keraniganj', 'Savar', 'Ashulia',
            'Narayanganj', 'Siddhirganj', 'Fatullah', 'Araihazar', 'Sonargaon', 'Bandar'
        ],
        'bn' => [
            'ধানমন্ডি', 'গুলশান', 'বনানী', 'বারিধারা', 'মিরপুর', 'পল্লবী', 'কাফরুল',
            'উত্তরা', 'টঙ্গী', 'গাজীপুর', 'মোহাম্মদপুর', 'আদাবর', 'লালবাগ', 'পুরান ঢাকা',
            'বাড্ডা', 'রামপুরা', 'খিলগাঁও', 'যাত্রাবাড়ী', 'ডেমরা', 'শ্যামপুর', 'মতিঝিল',
            'পল্টন', 'রমনা', 'ফার্মগেট', 'শাহবাগ', 'নিউ মার্কেট', 'ওয়ারী', 'সুত্রাপুর',
            'কোতোয়ালী', 'হাজারীবাগ', 'কামরাঙ্গীরচর', 'কেরানীগঞ্জ', 'সাভার', 'আশুলিয়া',
            'নারায়ণগঞ্জ', 'সিদ্ধিরগঞ্জ', 'ফতুল্লা', 'আড়াইহাজার', 'সোনারগাঁও', 'বন্দর'
        ]
    ];
}

function getLocationName($location, $lang = null) {
    if ($lang === null) {
        $lang = getLanguage();
    }
    $locations = getDhakaLocations();
    return $locations[$lang][array_search($location, $locations['en'])] ?? $location;
}

// Provider Selection and Approval Functions

/**
 * Check if customer has already selected a provider in a specific category
 * @param int $customerId
 * @param int $categoryId
 * @return bool
 */
function hasCustomerSelectedInCategory($customerId, $categoryId) {
    $result = fetchOne("
        SELECT COUNT(*) as count 
        FROM customer_provider_selections 
        WHERE customer_id = ? 
        AND category_id = ? 
        AND status IN ('pending', 'contacted', 'accepted')
    ", [$customerId, $categoryId]);
    
    return $result['count'] > 0;
}

/**
 * Check if a specific selection is admin-approved
 * @param int $selectionId
 * @return bool
 */
function isSelectionApproved($selectionId) {
    $result = fetchOne("
        SELECT admin_approved 
        FROM customer_provider_selections 
        WHERE id = ?
    ", [$selectionId]);
    
    return $result && $result['admin_approved'] == 1;
}

/**
 * Approve a provider selection
 * @param int $selectionId
 * @param int $adminId
 * @return bool
 */
function approveSelection($selectionId, $adminId) {
    try {
        executeQuery("
            UPDATE customer_provider_selections 
            SET admin_approved = TRUE,
                admin_approved_at = NOW(),
                approved_by_admin_id = ?
            WHERE id = ?
        ", [$adminId, $selectionId]);
        
        // Get selection details for notification
        $selection = fetchOne("
            SELECT cps.customer_id, sp.name as provider_name
            FROM customer_provider_selections cps
            JOIN service_providers sp ON cps.provider_id = sp.id
            WHERE cps.id = ?
        ", [$selectionId]);
        
        // Send notification to customer
        if ($selection) {
            createNotification(
                $selection['customer_id'],
                'customer',
                'Provider Selection Approved',
                "Your selection of " . $selection['provider_name'] . " has been approved. You can now contact them.",
                'general',
                $selectionId
            );
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Error approving selection: " . $e->getMessage());
        return false;
    }
}

/**
 * Reject a provider selection
 * @param int $selectionId
 * @param int $adminId
 * @param string $reason
 * @return bool
 */
function rejectSelection($selectionId, $adminId, $reason = '') {
    try {
        executeQuery("
            UPDATE customer_provider_selections 
            SET status = 'rejected',
                updated_at = NOW()
            WHERE id = ?
        ", [$selectionId]);
        
        // Get selection details for notification
        $selection = fetchOne("
            SELECT cps.customer_id, sp.name as provider_name
            FROM customer_provider_selections cps
            JOIN service_providers sp ON cps.provider_id = sp.id
            WHERE cps.id = ?
        ", [$selectionId]);
        
        // Send notification to customer
        if ($selection) {
            $message = "Your selection of " . $selection['provider_name'] . " was not approved.";
            if ($reason) {
                $message .= " Reason: " . $reason;
            }
            
            createNotification(
                $selection['customer_id'],
                'customer',
                'Provider Selection Not Approved',
                $message,
                'general',
                $selectionId
            );
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Error rejecting selection: " . $e->getMessage());
        return false;
    }
}

/**
 * Get providers filtered by customer's existing selections
 * Excludes providers from categories where customer has already made a selection
 * @param int $userId
 * @param array $baseProvider - Base provider array to filter
 * @return array Filtered providers
 */
function getFilteredProviders($userId, $providers) {
    if (!$userId || empty($providers)) {
        return $providers;
    }
    
    // Get categories where customer has existing selections
    $selectedCategories = fetchAll("
        SELECT DISTINCT category_id 
        FROM customer_provider_selections 
        WHERE customer_id = ? 
        AND status IN ('pending', 'contacted', 'accepted')
    ", [$userId]);
    
    $selectedCategoryIds = array_column($selectedCategories, 'category_id');
    
    if (empty($selectedCategoryIds)) {
        return $providers;
    }
    
    // Get the provider IDs that customer has selected
    $selectedProviders = fetchAll("
        SELECT provider_id 
        FROM customer_provider_selections 
        WHERE customer_id = ? 
        AND status IN ('pending', 'contacted', 'accepted')
    ", [$userId]);
    
    $selectedProviderIds = array_column($selectedProviders, 'provider_id');
    
    // Filter providers: exclude those from selected categories except the ones already selected
    return array_filter($providers, function($provider) use ($selectedCategoryIds, $selectedProviderIds) {
        // If this provider is already selected, show it
        if (in_array($provider['id'], $selectedProviderIds)) {
            return true;
        }
        
        // If this provider is in a category where customer has made a selection, hide it
        if (in_array($provider['category_id'], $selectedCategoryIds)) {
            return false;
        }
        
        // Otherwise show it
        return true;
    });
}

/**
 * Get customer's selected provider for a specific category
 * @param int $customerId
 * @param int $categoryId
 * @return array|null
 */
function getCustomerSelectionInCategory($customerId, $categoryId) {
    return fetchOne("
        SELECT cps.*, sp.name as provider_name, sp.phone as provider_phone
        FROM customer_provider_selections cps
        JOIN service_providers sp ON cps.provider_id = sp.id
        WHERE cps.customer_id = ? 
        AND cps.category_id = ? 
        AND cps.status IN ('pending', 'contacted', 'accepted')
        ORDER BY cps.created_at DESC
        LIMIT 1
    ", [$customerId, $categoryId]);
}
?> 