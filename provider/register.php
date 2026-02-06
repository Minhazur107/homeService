<?php
require_once '../includes/functions.php';

$currentLang = getLanguage();
$error = '';
$success = '';

// Get service categories
$categories = fetchAll("SELECT * FROM service_categories WHERE is_active = 1");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name']);
    $email = sanitizeInput($_POST['email']);
    $phone = sanitizeInput($_POST['phone']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $categoryId = $_POST['category_id'];
    $description = sanitizeInput($_POST['description']);
    $serviceAreas = sanitizeInput($_POST['service_areas']);
    $priceMin = $_POST['price_min'];
    $priceMax = $_POST['price_max'];
    $hourlyRate = $_POST['hourly_rate'];
    $availabilityHours = sanitizeInput($_POST['availability_hours']);
    
    // Validation
    if (empty($name) || empty($phone) || empty($password) || empty($confirmPassword) || empty($categoryId)) {
        $error = $currentLang === 'en' ? 'Please fill in all required fields' : 'সব প্রয়োজনীয় ক্ষেত্র পূরণ করুন';
    } elseif (!validatePhone($phone)) {
        $error = $currentLang === 'en' ? 'Please enter a valid phone number' : 'সঠিক ফোন নম্বর লিখুন';
    } elseif ($email && !validateEmail($email)) {
        $error = $currentLang === 'en' ? 'Please enter a valid email address' : 'সঠিক ইমেইল ঠিকানা লিখুন';
    } elseif (strlen($password) < 6) {
        $error = $currentLang === 'en' ? 'Password must be at least 6 characters long' : 'পাসওয়ার্ড কমপক্ষে ৬ অক্ষরের হতে হবে';
    } elseif ($password !== $confirmPassword) {
        $error = $currentLang === 'en' ? 'Passwords do not match' : 'পাসওয়ার্ড মিলছে না';
    } elseif ($priceMin && $priceMax && $priceMin > $priceMax) {
        $error = $currentLang === 'en' ? 'Minimum price cannot be greater than maximum price' : 'ন্যূনতম মূল্য সর্বোচ্চ মূল্যের চেয়ে বেশি হতে পারে না';
    } else {
        // Check if phone already exists
        $existingProvider = fetchOne("SELECT id FROM service_providers WHERE phone = ?", [$phone]);
        if ($existingProvider) {
            $error = $currentLang === 'en' ? 'Phone number already registered' : 'ফোন নম্বর ইতিমধ্যে নিবন্ধিত';
        } else {
            // Check if email already exists (if provided)
            if ($email) {
                $existingEmail = fetchOne("SELECT id FROM service_providers WHERE email = ?", [$email]);
                if ($existingEmail) {
                    $error = $currentLang === 'en' ? 'Email address already registered' : 'ইমেইল ঠিকানা ইতিমধ্যে নিবন্ধিত';
                }
            }
            
            if (!$error) {
                // Handle file uploads
                $nidDocument = '';
                $licenseDocument = '';
                $certificateDocument = '';
                $profilePicture = '';
                
                // Upload NID document
                if (isset($_FILES['nid_document']) && $_FILES['nid_document']['error'] === UPLOAD_ERR_OK) {
                    $nidDocument = uploadFile($_FILES['nid_document'], '../uploads/documents/');
                    if (!$nidDocument) {
                        $error = $currentLang === 'en' ? 'Failed to upload NID document' : 'এনআইডি নথি আপলোড করতে ব্যর্থ';
                    }
                }
                
                // Upload license document (optional)
                if (!$error && isset($_FILES['license_document']) && $_FILES['license_document']['error'] === UPLOAD_ERR_OK) {
                    $licenseDocument = uploadFile($_FILES['license_document'], '../uploads/documents/');
                }
                
                // Upload certificate document (optional)
                if (!$error && isset($_FILES['certificate_document']) && $_FILES['certificate_document']['error'] === UPLOAD_ERR_OK) {
                    $certificateDocument = uploadFile($_FILES['certificate_document'], '../uploads/documents/');
                }
                
                // Upload profile picture (optional)
                if (!$error && isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                    $profilePicture = uploadFile($_FILES['profile_picture'], '../uploads/profiles/');
                }
                
                if (!$error) {
                    // Create provider account
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $sql = "INSERT INTO service_providers (name, email, phone, password, category_id, description, service_areas, price_min, price_max, hourly_rate, availability_hours, nid_document, license_document, certificate_document, profile_picture, language) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $params = [
                        $name, $email ?: null, $phone, $hashedPassword, $categoryId, $description, 
                        $serviceAreas, $priceMin ?: null, $priceMax ?: null, $hourlyRate ?: null, 
                        $availabilityHours, $nidDocument, $licenseDocument ?: null, 
                        $certificateDocument ?: null, $profilePicture ?: null, $currentLang
                    ];
                    
                    try {
                        executeQuery($sql, $params);
                        $success = $currentLang === 'en' ? 'Registration submitted successfully! Your account will be reviewed by admin.' : 'নিবন্ধন সফলভাবে জমা হয়েছে! অ্যাডমিন আপনার অ্যাকাউন্টটি পর্যালোচনা করবেন।';
                    } catch (Exception $e) {
                        $error = $currentLang === 'en' ? 'Registration failed. Please try again.' : 'নিবন্ধন ব্যর্থ হয়েছে। আবার চেষ্টা করুন।';
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $currentLang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $currentLang === 'en' ? 'Pro Registration' : 'প্রদানকারী নিবন্ধন'; ?> - S24</title>
    <link rel="stylesheet" href="../assets/ui.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Plus Jakarta Sans', sans-serif; }
        .glass-card-form {
            background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.4); border-radius: 2rem;
            box-shadow: 0 40px 100px rgba(0, 0, 0, 0.1); transition: all 0.4s ease;
        }
        .form-section-header { position: relative; padding-left: 1.5rem; }
        .form-section-header::before {
            content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 4px;
            background: linear-gradient(to bottom, var(--primary), var(--secondary)); border-radius: 4px;
        }
        .input-premium {
            background: rgba(255, 255, 255, 0.6); border: 1.5px solid rgba(0,0,0,0.05);
            border-radius: 1rem; padding: 1rem 1rem 1rem 3rem; font-size: 0.95rem; color: #1a1a1a;
            transition: all 0.3s ease; font-weight: 600;
        }
        .input-premium:focus {
            outline: none; border-color: var(--primary); background: #fff; box-shadow: 0 0 0 4px var(--ring); transform: translateY(-2px);
        }
        .input-icon-left { position: absolute; left: 1.25rem; top: 50%; transform: translateY(-50%); color: var(--primary); font-size: 1.1rem; }
        .file-drop-area {
            border: 2px dashed rgba(var(--primary-rgb), 0.3); background: rgba(var(--primary-rgb), 0.02);
            border-radius: 1rem; padding: 1.5rem; text-align: center; cursor: pointer; transition: all 0.3s ease;
        }
        .file-drop-area:hover { border-color: var(--primary); background: rgba(var(--primary-rgb), 0.05); }
        .btn-premium {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: #fff; padding: 1.25rem; border-radius: 1rem; font-weight: 800; font-size: 1rem;
            border: none; cursor: pointer; transition: all 0.4s ease; width: 100%;
            box-shadow: 0 15px 35px -5px var(--ring);
        }
        .btn-premium:hover { transform: translateY(-4px); box-shadow: 0 25px 45px -10px var(--ring); filter: brightness(1.1); }
    </style>
</head>
<body class="premium-bg min-h-screen py-12">
    <!-- Floating Particles -->
    <div class="floating-particles">
        <div class="particle" style="width: 100px; height: 100px; top: 5%; left: 5%; animation-delay: 0s;"></div>
        <div class="particle" style="width: 150px; height: 150px; top: 65%; left: 85%; animation-delay: 2s;"></div>
        <div class="particle" style="width: 80px; height: 80px; top: 35%; left: 45%; animation-delay: 4s;"></div>
    </div>

    <div class="max-w-4xl mx-auto px-6 relative z-10">
        <!-- Brand -->
        <div class="text-center mb-12">
            <a href="../index.php" class="inline-block mb-6">
                <span class="text-5xl font-black text-gradient tracking-tighter">S24</span>
            </a>
            <h1 class="text-4xl font-black text-gray-900 tracking-tighter mb-4">
                <?php echo $currentLang === 'en' ? 'Professional Onboarding' : 'পেশাদার অনবোর্ডিং'; ?>
            </h1>
            <p class="text-gray-500 font-bold uppercase text-[10px] tracking-[0.2em] opacity-80">
                <?php echo $currentLang === 'en' ? 'Register your strategic service business' : 'আপনার কৌশলগত সেবা ব্যবসা নিবন্ধন করুন'; ?>
            </p>
        </div>

        <?php if ($error): ?>
            <div class="mb-8 p-5 bg-rose-50 text-rose-600 rounded-2xl border-l-4 border-rose-500 font-bold flex items-center gap-4 anim-shake shadow-sm">
                <i class="fas fa-circle-exclamation text-xl"></i>
                <span class="text-sm"><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="mb-8 p-10 glass-card-form text-center anim-pop-in">
                <div class="w-20 h-20 bg-emerald-50 text-emerald-500 rounded-3xl flex items-center justify-center text-3xl mx-auto mb-6 shadow-xl shadow-emerald-500/10">
                    <i class="fas fa-check-double"></i>
                </div>
                <h3 class="text-2xl font-black text-gray-900 mb-4"><?php echo t('Protocol Initiated'); ?></h3>
                <p class="text-gray-500 font-medium mb-10"><?php echo $success; ?></p>
                <a href="../auth/login.php" class="btn-premium inline-flex items-center justify-center gap-3 max-w-xs mx-auto">
                    <span><?php echo t('Go to Dashboard'); ?></span>
                    <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        <?php else: ?>
            <form method="POST" enctype="multipart/form-data" class="space-y-10">
                <!-- System Identity -->
                <div class="glass-card-form p-10">
                    <div class="form-section-header mb-8">
                        <h3 class="text-xl font-black text-gray-900 tracking-tight"><?php echo $currentLang === 'en' ? 'Account Identity' : 'অ্যাকাউন্ট পরিচয়'; ?></h3>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mt-1"><?php echo $currentLang === 'en' ? 'Step 01: Global Access Details' : 'ধাপ ০১: গ্লোবাল অ্যাক্সেস তথ্য'; ?></p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="space-y-3">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Legal Full Name *</label>
                            <div class="relative">
                                <i class="fas fa-user-tie input-icon-left"></i>
                                <input type="text" name="name" required value="<?php echo $_POST['name'] ?? ''; ?>" class="input-premium w-full" placeholder="John Doe">
                            </div>
                        </div>

                        <div class="space-y-3">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Primary Phone *</label>
                            <div class="relative">
                                <i class="fas fa-phone-office input-icon-left"></i>
                                <input type="tel" name="phone" required value="<?php echo $_POST['phone'] ?? ''; ?>" class="input-premium w-full" placeholder="01XXX XXXXXX">
                            </div>
                        </div>

                        <div class="space-y-3">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Email Address (Secure)</label>
                            <div class="relative">
                                <i class="fas fa-envelope-open-text input-icon-left"></i>
                                <input type="email" name="email" value="<?php echo $_POST['email'] ?? ''; ?>" class="input-premium w-full" placeholder="pro@s24.com">
                            </div>
                        </div>

                        <div class="space-y-3">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Profile Visual</label>
                            <input type="file" name="profile_picture" accept="image/*" class="text-xs file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-[10px] file:font-black file:bg-primary/10 file:text-primary hover:file:bg-primary/20">
                        </div>
                    </div>
                </div>

                <!-- Strategic Operations -->
                <div class="glass-card-form p-10">
                    <div class="form-section-header mb-8">
                        <h3 class="text-xl font-black text-gray-900 tracking-tight"><?php echo $currentLang === 'en' ? 'Operational Parameters' : 'অপারেশনাল প্যারামিটার'; ?></h3>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mt-1"><?php echo $currentLang === 'en' ? 'Step 02: Service Strategy' : 'ধাপ ০২: সার্ভিস স্ট্র্যাটেজি'; ?></p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="space-y-3">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Service Discipline *</label>
                            <div class="relative">
                                <i class="fas fa-briefcase input-icon-left"></i>
                                <select name="category_id" required class="input-premium w-full appearance-none">
                                    <option value=""><?php echo t('select_category'); ?></option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>" <?php echo (($_POST['category_id'] ?? '') == $cat['id']) ? 'selected' : ''; ?>>
                                            <?php echo $currentLang === 'en' ? $cat['name'] : $cat['name_bn']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Operational Areas</label>
                            <div class="relative">
                                <i class="fas fa-map-location-dot input-icon-left"></i>
                                <input type="text" name="service_areas" value="<?php echo $_POST['service_areas'] ?? ''; ?>" class="input-premium w-full" placeholder="Dhanmondi, Gulshan 2...">
                            </div>
                        </div>

                        <div class="space-y-3">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Min Rate (৳)</label>
                            <input type="number" name="price_min" value="<?php echo $_POST['price_min'] ?? ''; ?>" class="input-premium w-full" placeholder="500">
                        </div>

                        <div class="space-y-3">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Max Rate (৳)</label>
                            <input type="number" name="price_max" value="<?php echo $_POST['price_max'] ?? ''; ?>" class="input-premium w-full" placeholder="5000">
                        </div>
                    </div>

                    <div class="mt-8 space-y-3">
                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Value Proposition (Bio)</label>
                        <textarea name="description" rows="3" class="input-premium w-full py-4 px-6" style="padding-left: 1.5rem;" placeholder="Briefly describe your high-level service standards..."><?php echo $_POST['description'] ?? ''; ?></textarea>
                    </div>
                </div>

                <!-- Secure Clearance -->
                <div class="glass-card-form p-10">
                    <div class="form-section-header mb-8">
                        <h3 class="text-xl font-black text-gray-900 tracking-tight"><?php echo $currentLang === 'en' ? 'Security Clearance' : 'সিকিউরিটি ক্লিয়ারেন্স'; ?></h3>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mt-1"><?php echo $currentLang === 'en' ? 'Step 03: Verification & Access' : 'ধাপ ০৩: ভেরিফিকেশন ও অ্যাক্সেস'; ?></p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                        <div class="space-y-3 col-span-2">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">NID Document (PDF/Image) *</label>
                            <input type="file" name="nid_document" required accept=".pdf,.jpg,.jpeg,.png" class="text-xs file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-[10px] file:font-black file:bg-indigo-50 file:text-indigo-600">
                        </div>

                        <div class="space-y-3">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Access Key *</label>
                            <div class="relative">
                                <i class="fas fa-lock-hashtag input-icon-left"></i>
                                <input type="password" name="password" required class="input-premium w-full" placeholder="********">
                            </div>
                        </div>

                        <div class="space-y-3">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Confirm Key *</label>
                            <div class="relative">
                                <i class="fas fa-shield-check input-icon-left"></i>
                                <input type="password" name="confirm_password" required class="input-premium w-full" placeholder="********">
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn-premium flex items-center justify-center gap-4">
                        <span><?php echo t('Execute Registration'); ?></span>
                        <i class="fas fa-rocket"></i>
                    </button>
                    
                    <p class="mt-8 text-center text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                        Already joined? <a href="../auth/login.php" class="text-primary hover:underline ml-1">Initiate Access Protocol</a>
                    </p>
                </div>
            </form>
        <?php endif; ?>

        <!-- Theme Picker -->
        <div class="flex justify-center items-center gap-4 mt-12">
            <div class="theme-picker" data-theme-picker>
                <button type="button" class="w-12 h-12 rounded-2xl bg-white border border-gray-100 flex items-center justify-center text-primary shadow-xl hover:scale-110 transition-transform" data-toggle>
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
            
            <a href="?lang=<?php echo $currentLang === 'en' ? 'bn' : 'en'; ?>" class="w-12 h-12 rounded-2xl bg-white border border-gray-100 flex items-center justify-center text-primary shadow-xl hover:scale-110 transition-transform font-bold text-[10px]">
                <?php echo $currentLang === 'en' ? 'BN' : 'EN'; ?>
            </a>
        </div>
    </div>

    <script src="../assets/ui.js"></script>
</body>
</html>