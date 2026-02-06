<?php
require_once '../includes/functions.php';

$currentLang = getLanguage();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name']);
    $email = sanitizeInput($_POST['email']);
    $phone = sanitizeInput($_POST['phone']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $location = sanitizeInput($_POST['location']);
    
    // Validation
    if (empty($name) || empty($phone) || empty($password) || empty($confirmPassword)) {
        $error = $currentLang === 'en' ? 'Please fill in all required fields' : 'সব প্রয়োজনীয় ক্ষেত্র পূরণ করুন';
    } elseif (!validatePhone($phone)) {
        $error = $currentLang === 'en' ? 'Please enter a valid phone number' : 'সঠিক ফোন নম্বর লিখুন';
    } elseif ($email && !validateEmail($email)) {
        $error = $currentLang === 'en' ? 'Please enter a valid email address' : 'সঠিক ইমেইল ঠিকানা লিখুন';
    } elseif (strlen($password) < 6) {
        $error = $currentLang === 'en' ? 'Password must be at least 6 characters long' : 'পাসওয়ার্ড কমপক্ষে ৬ অক্ষরের হতে হবে';
    } elseif ($password !== $confirmPassword) {
        $error = $currentLang === 'en' ? 'Passwords do not match' : 'পাসওয়ার্ড মিলছে না';
    } else {
        // Check if phone already exists
        $existingUser = fetchOne("SELECT id FROM users WHERE phone = ?", [$phone]);
        if ($existingUser) {
            $error = $currentLang === 'en' ? 'Phone number already registered' : 'ফোন নম্বর ইতিমধ্যে নিবন্ধিত';
        } else {
            // Check if email already exists (if provided)
            if ($email) {
                $existingEmail = fetchOne("SELECT id FROM users WHERE email = ?", [$email]);
                if ($existingEmail) {
                    $error = $currentLang === 'en' ? 'Email address already registered' : 'ইমেইল ঠিকানা ইতিমধ্যে নিবন্ধিত';
                }
            }
            
            if (!$error) {
                // Create user account
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $sql = "INSERT INTO users (name, email, phone, password, location, language) VALUES (?, ?, ?, ?, ?, ?)";
                $params = [$name, $email ?: null, $phone, $hashedPassword, $location, $currentLang];
                
                try {
                    executeQuery($sql, $params);
                    $success = $currentLang === 'en' ? 'Account created successfully! You can now login.' : 'অ্যাকাউন্ট সফলভাবে তৈরি হয়েছে! আপনি এখন লগইন করতে পারেন।';
                } catch (Exception $e) {
                    $error = $currentLang === 'en' ? 'Registration failed. Please try again.' : 'নিবন্ধন ব্যর্থ হয়েছে। আবার চেষ্টা করুন।';
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
    <title><?php echo $currentLang === 'en' ? 'Register' : 'নিবন্ধন'; ?> - HOME SERVICE</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/ui.css">
    <script src="../assets/ui.js"></script>
    <style>
        .form-input {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            background: rgba(255, 255, 255, 0.5);
            backdrop-filter: blur(5px);
            border: 2px solid rgba(243, 244, 246, 0.8);
            border-radius: 1rem;
            font-size: 1rem;
            color: #1f2937;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .form-input:focus {
            outline: none;
            background: #ffffff;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px var(--ring);
            transform: translateY(-1px);
        }

        .input-icon {
            position: absolute;
            left: 1.25rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 1.1rem;
            transition: color 0.3s ease;
            pointer-events: none;
        }

        .form-group:focus-within .input-icon {
            color: var(--primary);
        }

        .password-strength {
            height: 4px;
            border-radius: 2px;
            margin-top: 0.5rem;
            overflow: hidden;
            background: #e5e7eb;
            display: flex;
        }

        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: all 0.3s ease;
        }
    </style>
</head>
<body class="premium-bg min-h-screen flex flex-col font-['Plus_Jakarta_Sans']">
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
                <a href="../index.php" class="text-2xl font-black text-gradient uppercase tracking-tighter">
                    HOME SERVICE
                </a>
                
                <div class="flex items-center gap-3">
                    <!-- Theme Picker -->
                    <div class="theme-picker" data-theme-picker>
                        <button class="w-10 h-10 rounded-xl bg-white/50 hover:bg-white border border-white/40 flex items-center justify-center text-primary shadow-sm transition-all group" data-toggle>
                            <i class="fas fa-palette group-hover:rotate-12 transition-transform"></i>
                        </button>
                        <div class="theme-menu hidden p-3 grid grid-cols-4 gap-2 w-48 shadow-2xl origin-top-right">
                            <div class="theme-swatch" style="background: #6d28d9;" data-theme="theme-purple"></div>
                            <div class="theme-swatch" style="background: #10b981;" data-theme="theme-emerald"></div>
                            <div class="theme-swatch" style="background: #e11d48;" data-theme="theme-rose"></div>
                            <div class="theme-swatch" style="background: #f59e0b;" data-theme="theme-amber"></div>
                            <div class="theme-swatch" style="background: #334155;" data-theme="theme-slate"></div>
                            <div class="theme-swatch" style="background: #06b6d4;" data-theme="theme-cyan"></div>
                            <div class="theme-swatch" style="background: #ec4899;" data-theme="theme-pink"></div>
                        </div>
                    </div>

                    <a href="?lang=<?php echo $currentLang === 'en' ? 'bn' : 'en'; ?>" class="px-4 py-2.5 rounded-xl bg-white/50 hover:bg-white border border-white/40 font-bold text-xs text-gray-600 hover:text-primary transition-all uppercase tracking-widest shadow-sm">
                        <?php echo $currentLang === 'en' ? 'BN' : 'EN'; ?>
                    </a>
                </div>
            </div>
        </nav>
    </header>

    <main class="flex-grow flex items-center justify-center py-12 px-4 relative z-10">
        <div class="w-full max-w-[500px]">
            <!-- Header Text -->
            <div class="text-center mb-10">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-3xl bg-gradient-to-br from-primary to-secondary text-white text-3xl mb-6 shadow-2xl shadow-primary/30 rotate-3 hover:rotate-6 transition-transform">
                    <i class="fas fa-user-plus"></i>
                </div>
                <h1 class="text-4xl font-black text-gray-900 mb-2 tracking-tight">
                    <?php echo $currentLang === 'en' ? 'Create Account' : 'অ্যাকাউন্ট তৈরি করুন'; ?>
                </h1>
                <p class="text-gray-500 font-bold">
                    <?php echo $currentLang === 'en' ? 'Join our community of happy customers' : 'আমাদের সুখী গ্রাহকদের কমিউনিটিতে যোগ দিন'; ?>
                </p>
            </div>

            <div class="glass-card p-8 md:p-10">
                <?php if ($error): ?>
                    <div class="p-4 rounded-xl bg-red-50 border border-red-100 text-red-600 font-bold mb-6 flex items-center gap-3 animate-pulse">
                        <i class="fas fa-exclamation-circle text-xl"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="text-center py-8">
                        <div class="w-20 h-20 bg-green-100 text-green-500 rounded-full flex items-center justify-center text-4xl mx-auto mb-6">
                            <i class="fas fa-check"></i>
                        </div>
                        <h3 class="text-xl font-black text-gray-900 mb-2">
                            <?php echo $currentLang === 'en' ? 'Success!' : 'সফল!'; ?>
                        </h3>
                        <p class="text-gray-600 mb-8 font-medium">
                            <?php echo $success; ?>
                        </p>
                        <a href="login.php" class="btn-primary w-full py-3">
                            <?php echo $currentLang === 'en' ? 'Proceed to Login' : 'লগইন করুন'; ?>
                        </a>
                    </div>
                <?php else: ?>
                    <form method="POST" class="space-y-5">
                        <div class="form-group relative">
                            <i class="fas fa-user input-icon"></i>
                            <input type="text" name="name" required class="form-input" 
                                   placeholder="<?php echo $currentLang === 'en' ? 'Full Name' : 'পূর্ণ নাম'; ?>"
                                   value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                        </div>

                        <div class="form-group relative">
                            <i class="fas fa-envelope input-icon"></i>
                            <input type="email" name="email" class="form-input" 
                                   placeholder="<?php echo $currentLang === 'en' ? 'Email Address (Optional)' : 'ইমেইল (ঐচ্ছিক)'; ?>"
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>

                        <div class="form-group relative">
                            <i class="fas fa-phone input-icon"></i>
                            <input type="tel" name="phone" required class="form-input" 
                                   placeholder="<?php echo $currentLang === 'en' ? 'Phone Number' : 'ফোন নম্বর'; ?>"
                                   value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                        </div>

                        <div class="form-group relative">
                            <i class="fas fa-map-marker-alt input-icon"></i>
                            <input type="text" name="location" class="form-input" 
                                   placeholder="<?php echo $currentLang === 'en' ? 'Location (Optional)' : 'অবস্থান (ঐচ্ছিক)'; ?>"
                                   value="<?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <div class="relative">
                                <i class="fas fa-lock input-icon"></i>
                                <input type="password" name="password" required id="password" class="form-input" 
                                       placeholder="<?php echo $currentLang === 'en' ? 'Password' : 'পাসওয়ার্ড'; ?>">
                                <button type="button" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-primary transition-colors" onclick="togglePassword('password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <!-- Password Strength Indicator -->
                            <div class="mt-2 flex gap-1 h-1">
                                <div class="flex-1 bg-gray-200 rounded-full overflow-hidden">
                                    <div class="h-full bg-red-500 w-0 transition-all duration-300" id="strength-1"></div>
                                </div>
                                <div class="flex-1 bg-gray-200 rounded-full overflow-hidden">
                                    <div class="h-full bg-yellow-500 w-0 transition-all duration-300" id="strength-2"></div>
                                </div>
                                <div class="flex-1 bg-gray-200 rounded-full overflow-hidden">
                                    <div class="h-full bg-green-500 w-0 transition-all duration-300" id="strength-3"></div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group relative">
                            <i class="fas fa-shield-alt input-icon"></i>
                            <input type="password" name="confirm_password" required id="confirm_password" class="form-input" 
                                   placeholder="<?php echo $currentLang === 'en' ? 'Confirm Password' : 'পাসওয়ার্ড নিশ্চিত করুন'; ?>">
                        </div>

                        <button type="submit" class="btn-primary w-full py-4 text-lg shadow-xl shadow-primary/20 hover:shadow-primary/40">
                            <?php echo $currentLang === 'en' ? 'Create Account' : 'অ্যাকাউন্ট তৈরি করুন'; ?>
                            <i class="fas fa-arrow-right ml-2"></i>
                        </button>
                    </form>

                    <div class="mt-8 text-center">
                        <p class="text-gray-500 font-medium mb-4">
                            <?php echo $currentLang === 'en' ? 'Already have an account?' : 'ইতিমধ্যে অ্যাকাউন্ট আছে?'; ?>
                            <a href="login.php" class="text-primary font-black hover:underline ml-1">
                                <?php echo $currentLang === 'en' ? 'Login' : 'লগইন'; ?>
                            </a>
                        </p>
                        
                        <div class="relative py-4">
                            <div class="absolute inset-0 flex items-center">
                                <div class="w-full border-t border-gray-200"></div>
                            </div>
                            <div class="relative flex justify-center">
                                <span class="bg-white/80 backdrop-blur px-4 text-xs font-black text-gray-400 uppercase tracking-widest">
                                    <?php echo $currentLang === 'en' ? 'Provider Area' : 'প্রোভাইডার এরিয়া'; ?>
                                </span>
                            </div>
                        </div>

                        <a href="../provider/register.php" class="inline-flex items-center text-sm font-bold text-gray-500 hover:text-primary transition-colors">
                            <i class="fas fa-tools mr-2"></i>
                            <?php echo $currentLang === 'en' ? 'Register as Service Provider' : 'সেবা প্রদানকারী হিসেবে নিবন্ধন'; ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = event.currentTarget.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Password Strength
        document.getElementById('password')?.addEventListener('input', function(e) {
            const val = e.target.value;
            const s1 = document.getElementById('strength-1');
            const s2 = document.getElementById('strength-2');
            const s3 = document.getElementById('strength-3');
            
            let score = 0;
            if(val.length > 0) score++;
            if(val.length >= 6) score++;
            if(val.match(/[0-9]/) && val.match(/[a-zA-Z]/)) score++;
            
            s1.style.width = score >= 1 ? '100%' : '0%';
            s2.style.width = score >= 2 ? '100%' : '0%';
            s3.style.width = score >= 3 ? '100%' : '0%';
        });
    </script>
</body>
</html> 