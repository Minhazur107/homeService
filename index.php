<?php
require_once 'includes/functions.php';

$currentLang = getLanguage();
$user = getCurrentUser();
$categories = fetchAll("SELECT * FROM service_categories WHERE is_active = 1 LIMIT 8");

if (isset($_GET['logout'])) {
    session_destroy();
    redirect('index.php');
}
?>
<!DOCTYPE html>
<html lang="<?php echo $currentLang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home Service - Premium Home Services</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Hind+Siliguri:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/ui.css">
    <script src="assets/ui.js"></script>
</head>
<body class="premium-bg">
    <!-- Floating Background Particles -->
    <div class="floating-particles">
        <div class="particle" style="width: 100px; height: 100px; top: 10%; left: 5%; animation-delay: 0s;"></div>
        <div class="particle" style="width: 150px; height: 150px; top: 60%; left: 80%; animation-delay: 2s;"></div>
        <div class="particle" style="width: 80px; height: 80px; top: 40%; left: 40%; animation-delay: 4s;"></div>
        <div class="particle" style="width: 120px; height: 120px; top: 80%; left: 15%; animation-delay: 1s;"></div>
    </div>

    <!-- Navigation -->
    <nav class="glass-nav sticky top-0 z-50">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <a href="index.php" class="text-3xl font-black tracking-tighter text-gradient uppercase">Home Service</a>
            </div>
            
            <div class="hidden md:flex items-center space-x-8">
                <a href="search.php" class="font-bold text-gray-700 hover:text-primary transition-colors"><?php echo t('services'); ?></a>
                <a href="public_reviews.php" class="font-bold text-gray-700 hover:text-primary transition-colors"><?php echo t('reviews'); ?></a>
                
                <div class="flex items-center space-x-2 bg-gray-100 p-1 rounded-xl">
                    <a href="?lang=en" class="px-3 py-1 rounded-lg text-xs font-bold <?php echo $currentLang === 'en' ? 'bg-white shadow-sm text-primary' : 'text-gray-400'; ?>">EN</a>
                    <a href="?lang=bn" class="px-3 py-1 rounded-lg text-xs font-bold <?php echo $currentLang === 'bn' ? 'bg-white shadow-sm text-primary' : 'text-gray-400'; ?>">বাংলা</a>
                </div>

                <!-- Theme Picker -->
                <div class="theme-picker" data-theme-picker>
                    <button class="w-10 h-10 rounded-xl bg-white border border-gray-200 flex items-center justify-center text-primary shadow-sm hover:scale-110 transition-transform" data-toggle>
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

                <?php if (isLoggedIn()): ?>
                    <div class="flex items-center space-x-4">
                        <a href="customer/dashboard.php" class="btn-primary py-2 px-6"><?php echo t('dashboard'); ?></a>
                        <a href="?logout=1" class="text-gray-500 hover:text-danger transition-colors font-bold"><i class="fas fa-sign-out-alt"></i></a>
                    </div>
                <?php else: ?>
                    <a href="auth/login.php" class="btn-primary py-2 px-8"><?php echo t('login'); ?></a>
                <?php endif; ?>
            </div>
            
            <button class="md:hidden text-2xl text-gray-700">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </nav>

    <main class="relative z-10">
        <!-- Hero Section -->
        <section class="container mx-auto px-6 py-20 lg:py-32 flex flex-col lg:flex-row items-center gap-12">
            <div class="flex-1 space-y-8 text-center lg:text-left">
                <div class="inline-flex items-center space-x-2 bg-white/30 backdrop-blur-md px-4 py-2 rounded-full border border-white/40">
                    <span class="flex h-2 w-2 rounded-full bg-primary animate-ping"></span>
                    <span class="text-xs font-black uppercase tracking-widest text-primary"><?php echo $currentLang === 'en' ? 'Verified Specialists' : 'যাচাইকৃত বিশেষজ্ঞ'; ?></span>
                </div>
                <h1 class="text-5xl lg:text-7xl font-black text-gray-900 leading-[1.1] tracking-tighter">
                    Elevating Home <br>
                    <span class="text-gradient">Service Standard.</span>
                </h1>
                <p class="text-xl text-gray-600 font-medium max-w-xl leading-relaxed">
                    <?php echo $currentLang === 'en' ? 'Connect with the city\'s most trusted professionals for all your home needs. Guaranteed quality, every single time.' : 'আপনার বাড়ির সমস্ত প্রয়োজনের জন্য শহরের সবচেয়ে বিশ্বস্ত পেশাদারদের সাথে সংযোগ করুন। প্রতিবার মান নিশ্চিত।'; ?>
                </p>
                
                <div class="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4 pt-4">
                    <a href="search.php" class="btn-primary py-5 px-10 text-lg w-full sm:w-auto text-center shadow-xl shadow-primary/20">
                        <?php echo $currentLang === 'en' ? 'Explore Services' : 'সেবাগুলি দেখুন'; ?>
                    </a>
                    <a href="auth/login.php" class="glass-card py-5 px-10 text-lg w-full sm:w-auto text-center font-black text-gray-800 hover:bg-white transition-colors">
                        <?php echo $currentLang === 'en' ? 'Become a Pro' : 'পেশাদার হিসেবে যোগ দিন'; ?>
                    </a>
                </div>
                
                <div class="flex items-center justify-center lg:justify-start space-x-4 pt-2">
                    <div class="flex -space-x-3">
                        <img src="https://i.pravatar.cc/100?u=1" class="w-10 h-10 rounded-full border-2 border-white shadow-sm" alt="U1">
                        <img src="https://i.pravatar.cc/100?u=2" class="w-10 h-10 rounded-full border-2 border-white shadow-sm" alt="U2">
                        <img src="https://i.pravatar.cc/100?u=3" class="w-10 h-10 rounded-full border-2 border-white shadow-sm" alt="U3">
                    </div>
                    <span class="text-sm font-bold text-gray-500 tracking-tight">50,000+ Happy Customers in Dhaka</span>
                </div>
            </div>
            
            <div class="flex-1 w-full max-w-2xl">
                <div class="glass-card p-4 rotate-3 hover:rotate-0 transition-transform duration-500 shadow-2xl">
                    <img src="https://images.unsplash.com/photo-1581244277943-fe4a9c777189?auto=format&fit=crop&q=80&w=1200" class="rounded-2xl" alt="Service Hero">
                </div>
            </div>
        </section>

        <!-- Search Bar Section -->
        <section class="container mx-auto px-6 py-10">
            <div class="glass-card p-2 md:p-3 max-w-4xl mx-auto flex flex-col md:flex-row gap-2">
                <div class="flex-1 flex items-center px-4 py-4 md:py-0 border-b md:border-b-0 md:border-r border-gray-100">
                    <i class="fas fa-search text-primary mr-3"></i>
                    <input type="text" placeholder="<?php echo $currentLang === 'en' ? 'Which service do you need?' : 'কোন সেবা আপনার প্রয়োজন?'; ?>" class="w-full bg-transparent border-none outline-none font-bold text-gray-700 placeholder-gray-400">
                </div>
                <div class="flex-1 flex items-center px-4 py-4 md:py-0">
                    <i class="fas fa-location-dot text-primary mr-3"></i>
                    <input type="text" placeholder="<?php echo $currentLang === 'en' ? 'Your Location' : 'আপনার অবস্থান'; ?>" class="w-full bg-transparent border-none outline-none font-bold text-gray-700 placeholder-gray-400">
                </div>
                <a href="search.php" class="btn-primary py-4 px-10 rounded-2xl flex items-center justify-center gap-2">
                    <span class="font-extrabold"><?php echo $currentLang === 'en' ? 'Search Now' : 'খুঁজুন'; ?></span>
                    <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </section>

        <!-- Categories Section -->
        <section class="container mx-auto px-6 py-20">
            <div class="text-center space-y-4 mb-16">
                <h2 class="text-4xl lg:text-6xl font-black text-gray-900 tracking-tighter"><?php echo $currentLang === 'en' ? 'Top Rated' : 'সেরা মানের'; ?> <span class="text-gradient">Categories</span></h2>
                <p class="text-gray-500 font-bold uppercase tracking-widest text-xs">Excellence in every interaction</p>
            </div>
            
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-8">
                <?php foreach ($categories as $cat): 
                    $logoPath = "assets/logos/" . strtolower(str_replace(' ', '_', $cat['name'])) . ".png";
                    if (!file_exists($logoPath)) {
                        $name = strtolower($cat['name']);
                        if (strpos($name, 'ac') !== false) $logoPath = "assets/logos/ac.png";
                        elseif (strpos($name, 'plumb') !== false) $logoPath = "assets/logos/plumbing.png";
                        elseif (strpos($name, 'elect') !== false) $logoPath = "assets/logos/electrical.png";
                        elseif (strpos($name, 'clean') !== false) $logoPath = "assets/logos/cleaning.png";
                        elseif (strpos($name, 'carpent') !== false) $logoPath = "assets/logos/carpentry.png";
                        elseif (strpos($name, 'paint') !== false) $logoPath = "assets/logos/painting.png";
                        elseif (strpos($name, 'mov') !== false) $logoPath = "assets/logos/moving.png";
                        elseif (strpos($name, 'garden') !== false) $logoPath = "assets/logos/gardening.png";
                    }
                ?>
                <a href="search.php?category=<?php echo $cat['id']; ?>" class="glass-card group p-8 text-center bg-white/90 hover:bg-white transition-all">
                    <div class="w-24 h-24 mx-auto mb-6 flex items-center justify-center bg-indigo-50/50 rounded-3xl group-hover:bg-primary transition-all duration-300">
                        <?php if (file_exists($logoPath)): ?>
                            <img src="<?php echo $logoPath; ?>" class="w-16 h-16 object-contain group-hover:scale-125 group-hover:brightness-0 group-hover:invert transition-all duration-500" alt="Logo">
                        <?php else: ?>
                            <i class="fas fa-<?php echo $cat['icon'] ?? 'tools'; ?> text-4xl text-primary group-hover:text-white transition-colors"></i>
                        <?php endif; ?>
                    </div>
                    <h3 class="text-xl font-black text-gray-900 leading-tight group-hover:text-primary transition-colors"><?php echo $currentLang === 'en' ? $cat['name'] : $cat['name_bn']; ?></h3>
                    <div class="mt-4 inline-flex items-center space-x-1 text-xs font-black text-gray-400 uppercase tracking-widest">
                        <span>Check Pros</span>
                        <i class="fas fa-arrow-right-long text-primary group-hover:translate-x-1 transition-transform"></i>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Reviews Section -->
        <section class="py-20 bg-gray-900 text-white overflow-hidden relative">
            <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(var(--primary) 1px, transparent 1px); background-size: 40px 40px;"></div>
            
            <div class="container mx-auto px-6 relative z-10 text-center space-y-12">
                <div class="space-y-4">
                    <h2 class="text-4xl lg:text-6xl font-black tracking-tighter">What People <span class="text-primary">Love</span> About Us</h2>
                    <p class="text-gray-400 font-bold max-w-xl mx-auto">Real experiences from verified homeowners who found their perfect service through Home Service.</p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="glass-card bg-white/5 border-white/10 p-10 text-left">
                        <div class="flex text-yellow-400 mb-6">
                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                        </div>
                        <p class="text-lg font-medium italic opacity-80 leading-relaxed mb-8">"Found an amazing AC technician within 10 minutes. The service was professional and the price was exactly as quoted."</p>
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 bg-primary rounded-full flex items-center justify-center font-black">R</div>
                            <div>
                                <h4 class="font-bold">Rahim Ahmed</h4>
                                <p class="text-xs text-gray-500">Dhanmondi, Dhaka</p>
                            </div>
                        </div>
                    </div>
                    <div class="glass-card bg-white/5 border-white/10 p-10 text-left">
                        <div class="flex text-yellow-400 mb-6">
                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                        </div>
                        <p class="text-lg font-medium italic opacity-80 leading-relaxed mb-8">"I've used HOME SERVICE for plumbing and cleaning. The verification badge really gives peace of mind when someone enters your home."</p>
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 bg-primary rounded-full flex items-center justify-center font-black">F</div>
                            <div>
                                <h4 class="font-bold">Farzana Yeasmin</h4>
                                <p class="text-xs text-gray-500">Banani, Dhaka</p>
                            </div>
                        </div>
                    </div>
                    <div class="glass-card bg-white/5 border-white/10 p-10 text-left">
                        <div class="flex text-yellow-400 mb-6">
                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                        </div>
                        <p class="text-lg font-medium italic opacity-80 leading-relaxed mb-8">"Best platform for local specialists. The interface is beautiful and works flawlessly across my phone and laptop."</p>
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 bg-primary rounded-full flex items-center justify-center font-black">H</div>
                            <div>
                                <h4 class="font-bold">Habib Ullah</h4>
                                <p class="text-xs text-gray-500">Uttara, Dhaka</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <a href="public_reviews.php" class="inline-flex items-center space-x-2 text-primary font-black hover:underline text-lg pt-4">
                    <span>View All 2,500+ Reviews</span>
                    <i class="fas fa-chevron-right"></i>
                </a>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="container mx-auto px-6 py-20 lg:py-32">
            <!-- Fixed the background overlap by removing 'glass-card' and applying its properties manually where needed -->
            <div class="relative overflow-hidden rounded-[4rem] bg-gradient-to-br from-primary via-secondary to-primary p-12 lg:p-24 text-center shadow-2xl">
                <!-- Immersive Glass Decorations -->
                <div class="absolute top-0 right-0 w-96 h-96 bg-white/20 rounded-full -translate-y-1/2 translate-x-1/2 blur-[120px]"></div>
                <div class="absolute bottom-0 left-0 w-96 h-96 bg-accent/30 rounded-full translate-y-1/2 -translate-x-1/2 blur-[120px]"></div>
                
                <div class="relative z-10 max-w-4xl mx-auto space-y-12">
                    <div class="space-y-6">
                        <h2 class="text-6xl lg:text-8xl font-black tracking-tighter leading-[0.95] text-white drop-shadow-lg">
                            Ready to Get <br>Started?
                        </h2>
                        <p class="text-xl lg:text-2xl font-bold text-white leading-relaxed max-w-3xl mx-auto">
                            Join over 50,000 satisfied users who rely on Home Service for high-quality, verified home services.
                        </p>
                    </div>

                    <div class="flex flex-col sm:flex-row items-center justify-center gap-6 pt-6">
                        <a href="auth/login.php" class="bg-white text-gray-900 px-12 py-6 rounded-3xl font-black text-xl hover:scale-105 transition-all shadow-2xl w-full sm:w-auto flex items-center justify-center gap-3">
                            <i class="fas fa-user-plus text-primary"></i>
                            <span>Create Customer Account</span>
                        </a>
                        <a href="auth/login.php" class="bg-gray-900 text-white px-12 py-6 rounded-3xl font-black text-xl hover:bg-black hover:scale-105 transition-all shadow-2xl w-full sm:w-auto flex items-center justify-center gap-3">
                            <i class="fas fa-toolbox text-accent"></i>
                            <span>Join as Service Provider</span>
                        </a>
                    </div>

                    <!-- Trust Indicators with Solid Pill Styling -->
                    <div class="pt-10 flex flex-wrap justify-center gap-6">
                        <div class="bg-white/10 backdrop-blur-md border border-white/20 px-6 py-3 rounded-full flex items-center gap-3 text-white font-black text-sm uppercase tracking-widest">
                            <i class="fas fa-shield-check text-green-400"></i>
                            <span>Verified Experts</span>
                        </div>
                        <div class="bg-white/10 backdrop-blur-md border border-white/20 px-6 py-3 rounded-full flex items-center gap-3 text-white font-black text-sm uppercase tracking-widest">
                            <i class="fas fa-clock text-blue-300"></i>
                            <span>24/7 Support</span>
                        </div>
                        <div class="bg-white/10 backdrop-blur-md border border-white/20 px-6 py-3 rounded-full flex items-center gap-3 text-white font-black text-sm uppercase tracking-widest">
                            <i class="fas fa-lock text-yellow-300"></i>
                            <span>Secure Payments</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="bg-white border-t border-gray-100 py-20 relative z-10">
        <div class="container mx-auto px-6 grid grid-cols-1 lg:grid-cols-4 gap-12 text-center lg:text-left">
            <div class="space-y-6">
                <a href="index.php" class="text-4xl font-black tracking-tighter text-gradient uppercase">Home Service</a>
                <p class="text-gray-400 font-bold leading-relaxed">The standard for premium home service management. Built for reliability, designed for speed.</p>
                <div class="flex justify-center lg:justify-start space-x-6 text-2xl text-gray-300">
                    <a href="#" class="hover:text-primary transition-colors"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="hover:text-primary transition-colors"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="hover:text-primary transition-colors"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
            <div class="space-y-6 lg:col-span-1 lg:ml-auto">
                <h4 class="text-sm font-black uppercase tracking-widest text-gray-900">Platform</h4>
                <ul class="space-y-4 font-bold text-gray-400">
                    <li><a href="search.php" class="hover:text-primary transition-colors">Find a Service</a></li>
                    <li><a href="public_reviews.php" class="hover:text-primary transition-colors">Our Reviews</a></li>
                    <li><a href="auth/login.php" class="hover:text-primary transition-colors">Login / Register</a></li>
                </ul>
            </div>
            <div class="space-y-6 lg:col-span-1 lg:ml-auto">
                <h4 class="text-sm font-black uppercase tracking-widest text-gray-900">Support</h4>
                <ul class="space-y-4 font-bold text-gray-400">
                    <li><a href="#" class="hover:text-primary transition-colors">Help Center</a></li>
                    <li><a href="#" class="hover:text-primary transition-colors">Safety Guarantees</a></li>
                    <li><a href="#" class="hover:text-primary transition-colors">Contact Support</a></li>
                </ul>
            </div>
            <div class="space-y-6 lg:col-span-1 lg:ml-auto">
                <h4 class="text-sm font-black uppercase tracking-widest text-gray-900">Legal</h4>
                <ul class="space-y-4 font-bold text-gray-400">
                    <li><a href="#" class="hover:text-primary transition-colors">Terms of Use</a></li>
                    <li><a href="#" class="hover:text-primary transition-colors">Privacy Policy</a></li>
                    <li><a href="#" class="hover:text-primary transition-colors">Cookie Policy</a></li>
                </ul>
            </div>
        </div>
        <div class="container mx-auto px-6 pt-20 text-center">
            <p class="text-xs font-black text-gray-400 uppercase tracking-[0.2em]">&copy; <?php echo date('Y'); ?> Home Service Ecosystem. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
