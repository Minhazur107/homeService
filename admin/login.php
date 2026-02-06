<?php
require_once '../includes/functions.php';

$currentLang = getLanguage();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = $currentLang === 'en' ? 'Please fill in all fields' : 'সব ক্ষেত্র পূরণ করুন';
    } else {
        $admin = fetchOne("SELECT * FROM admins WHERE username = ? AND is_active = 1", [$username]);
        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_role'] = $admin['role'];
            setFlashMessage('success', $currentLang === 'en' ? 'Login successful!' : 'লগইন সফল!');
            redirect('dashboard.php');
        } else {
            $error = $currentLang === 'en' ? 'Invalid username or password' : 'ভুল ব্যবহারকারী নাম বা পাসওয়ার্ড';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $currentLang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $currentLang === 'en' ? 'Admin Access' : 'অ্যাডমিন প্রবেশ'; ?> - Home Service</title>
    <link rel="stylesheet" href="../assets/ui.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        
        .admin-container {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 255, 255, 0.4);
            border-radius: 2.5rem;
            box-shadow: 
                0 40px 100px rgba(0, 0, 0, 0.15),
                0 0 0 1px rgba(255, 255, 255, 0.5);
            position: relative;
            overflow: hidden;
        }
        
        .admin-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, var(--primary), var(--secondary), var(--accent));
            background-size: 200% 100%;
            animation: shimmer 4s linear infinite;
        }
        
        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }
        
        .form-input {
            background: rgba(255, 255, 255, 0.7);
            border: 2px solid rgba(0, 0, 0, 0.05);
            border-radius: 1.25rem;
            padding: 1.25rem 1.25rem 1.25rem 3.5rem;
            font-size: 1rem;
            color: #1a1a1a;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            backdrop-filter: blur(10px);
            font-weight: 600;
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px var(--ring);
            background: rgba(255, 255, 255, 1);
            transform: translateY(-2px);
        }
        
        .input-icon {
            position: absolute;
            left: 1.25rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary);
            font-size: 1.2rem;
            z-index: 10;
        }
        
        .btn-admin {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 1.25rem 2rem;
            border-radius: 1.25rem;
            font-weight: 800;
            font-size: 1.1rem;
            border: none;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 15px 35px -5px var(--ring);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .btn-admin:hover {
            transform: translateY(-4px);
            box-shadow: 0 25px 45px -10px var(--ring);
            filter: brightness(1.1);
        }
        
        .admin-icon-circle {
            width: 5rem;
            height: 5rem;
            border-radius: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            margin: 0 auto 1.5rem;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            box-shadow: 0 20px 40px -10px var(--ring);
            transform: rotate(-5deg);
        }

        .language-toggle {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 1.25rem;
            padding: 0.75rem 1.5rem;
            color: #1e293b;
            font-weight: 800;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 1px;
            transition: all 0.3s ease;
        }

        .language-toggle:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
        }
    </style>
</head>
<body class="premium-bg min-h-screen p-4 flex items-center justify-center">
    <!-- Floating Particles -->
    <div class="floating-particles">
        <div class="particle" style="width: 100px; height: 100px; top: 10%; left: 5%; animation-delay: 0s;"></div>
        <div class="particle" style="width: 150px; height: 150px; top: 60%; left: 80%; animation-delay: 2s;"></div>
        <div class="particle" style="width: 80px; height: 80px; top: 40%; left: 40%; animation-delay: 4s;"></div>
        <div class="particle" style="width: 120px; height: 120px; top: 80%; left: 15%; animation-delay: 1s;"></div>
    </div>

    <div class="max-w-md w-full relative z-10 py-12">
        <!-- Header -->
        <div class="text-center mb-10">
            <a href="../index.php" class="inline-block mb-8">
                <div class="text-5xl font-black text-gradient tracking-tighter uppercase">
                    Home Service
                </div>
            </a>
            <div class="admin-icon-circle">
                <i class="fas fa-shield-halved"></i>
            </div>
            <h1 class="text-4xl font-black text-gray-900 mb-3 tracking-tighter">
                <?php echo $currentLang === 'en' ? 'Central Console' : 'সেন্ট্রাল কনসোল'; ?>
            </h1>
            <p class="text-gray-500 font-bold uppercase text-[10px] tracking-[0.2em] opacity-80">
                <?php echo $currentLang === 'en' ? 'Higher Level Protocol Access' : 'উচ্চ পর্যায়ের প্রটোকল অ্যাক্সেস'; ?>
            </p>
        </div>

        <!-- Login Card -->
        <div class="admin-container p-10">
            <?php if ($error): ?>
                <div class="mb-6 p-4 bg-rose-50 border-l-4 border-rose-500 text-rose-600 rounded-xl flex items-center gap-3 anim-shake">
                    <i class="fas fa-circle-exclamation text-lg"></i>
                    <span class="font-bold text-sm"><?php echo $error; ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-8">
                <!-- Username -->
                <div class="space-y-3">
                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">
                        <?php echo $currentLang === 'en' ? 'Admin ID' : 'অ্যাডমিন আইডি'; ?>
                    </label>
                    <div class="relative">
                        <i class="fas fa-fingerprint input-icon"></i>
                        <input type="text" name="username" required 
                               placeholder="Enter your console ID"
                               class="form-input w-full">
                    </div>
                </div>

                <!-- Password -->
                <div class="space-y-3">
                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">
                        <?php echo $currentLang === 'en' ? 'Secure Protocol' : 'সিকিউর প্রটোকল'; ?>
                    </label>
                    <div class="relative">
                        <i class="fas fa-key input-icon"></i>
                        <input type="password" name="password" required 
                               placeholder="********"
                               class="form-input w-full pr-12" id="admin-password">
                        <button type="button" class="absolute right-5 top-1/2 transform -translate-y-1/2 text-gray-300 hover:text-primary transition-colors" id="toggle-admin-password">
                            <i class="fas fa-eye text-lg" id="admin-password-icon"></i>
                        </button>
                    </div>
                </div>

                <!-- Action Button -->
                <button type="submit" class="btn-admin w-full mt-4">
                    <span><?php echo $currentLang === 'en' ? 'Initiate Access' : 'প্রবেশ করুন'; ?></span>
                    <i class="fas fa-arrow-right-long ml-3"></i>
                </button>
            </form>

            <div class="mt-10 pt-8 border-t border-gray-100/50 text-center">
                <a href="../index.php" class="text-[10px] font-black text-gray-400 hover:text-primary transition-colors uppercase tracking-widest flex items-center justify-center gap-2">
                    <i class="fas fa-house-chimney"></i>
                    <?php echo $currentLang === 'en' ? 'Return to Base' : 'মূল পাতায় ফিরুন'; ?>
                </a>
            </div>
        </div>

        <!-- Language Toggle & Theme Picker -->
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
        // Password visibility toggle logic
        const toggleBtn = document.getElementById('toggle-admin-password');
        const passInput = document.getElementById('admin-password');
        const passIcon = document.getElementById('admin-password-icon');

        toggleBtn.addEventListener('click', () => {
            const isPass = passInput.type === 'password';
            passInput.type = isPass ? 'text' : 'password';
            passIcon.classList.toggle('fa-eye');
            passIcon.classList.toggle('fa-eye-slash');
        });

        // Initialize animations for elements
        window.addEventListener('load', () => {
            document.querySelector('.admin-container').classList.add('anim-pop-in');
        });
    </script>
</body>
</html>