<?php
require_once '../includes/functions.php';

$currentLang = getLanguage();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = sanitizeInput($_POST['phone']);
    $password = $_POST['password'];
    
    if (empty($phone) || empty($password)) {
        $error = $currentLang === 'en' ? 'Please fill in all fields' : 'সব ক্ষেত্র পূরণ করুন';
    } else {
        // Check if user exists
        $user = fetchOne("SELECT * FROM users WHERE phone = ?", [$phone]);
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = 'customer';
            setFlashMessage('success', $currentLang === 'en' ? 'Login successful!' : 'লগইন সফল!');
            redirect('../customer/dashboard.php');
        } else {
            // Check if it's a service provider
            $provider = fetchOne("SELECT * FROM service_providers WHERE phone = ? AND verification_status = 'verified' AND is_active = 1", [$phone]);
            
            if ($provider && password_verify($password, $provider['password'])) {
                $_SESSION['provider_id'] = $provider['id'];
                $_SESSION['user_role'] = 'provider';
                setFlashMessage('success', $currentLang === 'en' ? 'Login successful!' : 'লগইন সফল!');
                redirect('../provider/dashboard.php');
            } else {
                $error = $currentLang === 'en' ? 'Invalid phone number or password' : 'ভুল ফোন নম্বর বা পাসওয়ার্ড';
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
    <title><?php echo $currentLang === 'en' ? 'Nexus Access' : 'প্রবেশদ্বার'; ?> - Home Service</title>
    <link rel="stylesheet" href="../assets/ui.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Plus Jakarta Sans', sans-serif; }
        .login-container {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 255, 255, 0.4);
            border-radius: 2.5rem;
            box-shadow: 0 40px 100px rgba(0, 0, 0, 0.1), 0 0 0 1px rgba(255, 255, 255, 0.5);
            position: relative;
            overflow: hidden;
        }
        .login-container::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0; height: 6px;
            background: linear-gradient(90deg, var(--primary), var(--secondary), var(--accent));
            background-size: 200% 100%; animation: shimmer 4s linear infinite;
        }
        @keyframes shimmer { 0% { background-position: -200% 0; } 100% { background-position: 200% 0; } }
        .form-input {
            background: rgba(255, 255, 255, 0.7); border: 2px solid rgba(0, 0, 0, 0.05);
            border-radius: 1.25rem; padding: 1.25rem 1.25rem 1.25rem 3.5rem; font-size: 1rem;
            color: #1a1a1a; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            backdrop-filter: blur(10px); font-weight: 600;
        }
        .form-input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 4px var(--ring); background: #fff; transform: translateY(-2px); }
        .input-icon { position: absolute; left: 1.25rem; top: 50%; transform: translateY(-50%); color: var(--primary); font-size: 1.2rem; z-index: 10; }
        .hero-logo { background: linear-gradient(135deg, var(--primary), var(--secondary), var(--accent)); -webkit-background-clip: text; background-clip: text; color: transparent; font-weight: 900; letter-spacing: -2px; }
        .btn-auth { background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); color: white; padding: 1.25rem; border-radius: 1.25rem; font-weight: 800; font-size: 1rem; border: none; cursor: pointer; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); box-shadow: 0 15px 35px -5px var(--ring); display: flex; align-items: center; justify-content: center; gap: 10px; width: 100%; text-transform: uppercase; letter-spacing: 1px; }
        .btn-auth:hover { transform: translateY(-4px); box-shadow: 0 25px 45px -10px var(--ring); filter: brightness(1.1); }
        .language-toggle { background: rgba(0, 0, 0, 0.05); backdrop-filter: blur(15px); border: 1px solid rgba(255, 255, 255, 0.3); border-radius: 1.25rem; padding: 0.75rem 1.5rem; color: #1e293b; font-weight: 800; font-size: 10px; text-transform: uppercase; letter-spacing: 1px; transition: all 0.3s ease; }
        .language-toggle:hover { background: rgba(255, 255, 255, 0.5); transform: translateY(-2px); }
    </style>
</head>
<body class="premium-bg min-h-screen p-4 flex items-center justify-center">
    <!-- Background Particles -->
    <div class="floating-particles">
        <div class="particle" style="width: 100px; height: 100px; top: 10%; left: 5%; animation-delay: 0s;"></div>
        <div class="particle" style="width: 150px; height: 150px; top: 60%; left: 80%; animation-delay: 2s;"></div>
        <div class="particle" style="width: 80px; height: 80px; top: 40%; left: 40%; animation-delay: 4s;"></div>
    </div>

    <div class="max-w-md w-full relative z-10 py-8">
        <!-- Brand Header -->
        <div class="text-center mb-10">
            <a href="../index.php" class="inline-block mb-6">
                <h1 class="text-6xl hero-logo uppercase">Home Service</h1>
            </a>
            <h2 class="text-3xl font-black text-gray-900 tracking-tighter mb-2">
                <?php echo $currentLang === 'en' ? 'Welcome Back' : 'স্বাগতম'; ?>
            </h2>
            <p class="text-gray-500 font-bold uppercase text-[10px] tracking-[0.2em] opacity-80">
                <?php echo $currentLang === 'en' ? 'Secure Login Protocol' : 'সুরক্ষিত লগইন প্রটোকল'; ?>
            </p>
        </div>

        <!-- Auth Card -->
        <div class="login-container p-10">
            <?php if ($error): ?>
                <div class="mb-8 p-4 bg-rose-50 border-l-4 border-rose-500 text-rose-600 rounded-xl flex items-center gap-3 anim-shake">
                    <i class="fas fa-circle-exclamation text-lg"></i>
                    <span class="font-bold text-sm"><?php echo $error; ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-8">
                <!-- Phone -->
                <div class="space-y-3">
                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">
                        <?php echo $currentLang === 'en' ? 'Phone Number' : 'ফোন নম্বর'; ?>
                    </label>
                    <div class="relative">
                        <i class="fas fa-phone input-icon"></i>
                        <input type="tel" name="phone" required placeholder="01XXX XXXXXX" class="form-input w-full">
                    </div>
                </div>

                <!-- Password -->
                <div class="space-y-3">
                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">
                        <?php echo $currentLang === 'en' ? 'Access Key' : 'পাসওয়ার্ড'; ?>
                    </label>
                    <div class="relative">
                        <i class="fas fa-shield-keyhole input-icon"></i>
                        <input type="password" name="password" required placeholder="********" class="form-input w-full pr-12" id="password">
                        <button type="button" class="absolute right-5 top-1/2 transform -translate-y-1/2 text-gray-300 hover:text-primary transition-colors" id="toggle-password">
                            <i class="fas fa-eye text-lg" id="password-icon"></i>
                        </button>
                    </div>
                </div>

                <!-- Submit -->
                <button type="submit" class="btn-auth">
                    <span><?php echo $currentLang === 'en' ? 'Initiate Log In' : 'লগইন করুন'; ?></span>
                    <i class="fas fa-arrow-right-long"></i>
                </button>
            </form>

            <!-- Supplemental Links -->
            <div class="mt-10 space-y-6 pt-6 border-t border-gray-100/50">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest"><?php echo t('New here?'); ?></span>
                    <a href="register.php" class="text-[10px] font-black text-primary uppercase tracking-widest hover:underline"><?php echo t('Create Account'); ?></a>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <a href="../provider/register.php" class="bg-emerald-50 text-emerald-600 p-4 rounded-2xl flex flex-col items-center justify-center gap-2 hover:bg-emerald-100 transition-colors group">
                        <i class="fas fa-briefcase text-lg group-hover:scale-110 transition-transform"></i>
                        <span class="text-[8px] font-black uppercase tracking-widest"><?php echo $currentLang === 'en' ? 'Join as Pro' : 'প্রদানকারী নিবন্ধন'; ?></span>
                    </a>
                    <a href="../admin/login.php" class="bg-gray-50 text-gray-600 p-4 rounded-2xl flex flex-col items-center justify-center gap-2 hover:bg-gray-100 transition-colors group">
                        <i class="fas fa-user-gear text-lg group-hover:scale-110 transition-transform"></i>
                        <span class="text-[8px] font-black uppercase tracking-widest"><?php echo $currentLang === 'en' ? 'Console' : 'অ্যাডমিন কনসোল'; ?></span>
                    </a>
                </div>

                <a href="../index.php" class="text-[10px] font-black text-gray-400 hover:text-primary transition-colors uppercase tracking-widest flex items-center justify-center gap-2 pt-4">
                    <i class="fas fa-house-chimney"></i>
                    <?php echo t('back_to_home'); ?>
                </a>
            </div>
        </div>

        <!-- Global Settings -->
        <div class="flex justify-center items-center gap-4 mt-10">
            <!-- Theme Picker -->
            <div class="theme-picker" data-theme-picker>
                <button type="button" class="language-toggle flex items-center" data-toggle>
                    <i class="fas fa-palette mr-2 text-primary"></i>
                    <span><?php echo $currentLang === 'en' ? 'Theme' : 'থিম'; ?></span>
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

            <a href="?lang=<?php echo $currentLang === 'en' ? 'bn' : 'en'; ?>" class="language-toggle flex items-center">
                <i class="fas fa-globe-asia mr-2 text-primary"></i>
                <span><?php echo $currentLang === 'en' ? 'Bangla' : 'English'; ?></span>
            </a>
        </div>
    </div>

    <script src="../assets/ui.js"></script>
    <script>
        const passInput = document.getElementById('password');
        const passIcon = document.getElementById('password-icon');
        document.getElementById('toggle-password').addEventListener('click', () => {
            const isPass = passInput.type === 'password';
            passInput.type = isPass ? 'text' : 'password';
            passIcon.classList.toggle('fa-eye');
            passIcon.classList.toggle('fa-eye-slash');
        });
    </script>
</body>
</html>
