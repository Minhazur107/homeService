document.addEventListener('DOMContentLoaded', function() {
    // Theme Switching
    const themePicker = document.getElementById('themePicker');
    const themeMenu = document.getElementById('themeMenu');
    const themeSwatches = document.querySelectorAll('.theme-swatch');
    const body = document.body;
    const mobileMenuButton = document.getElementById('mobileMenuButton');
    const mobileMenu = document.getElementById('mobileMenu');
    
    // Load saved theme from localStorage
    const savedTheme = localStorage.getItem('s24_theme') || 'theme-purple';
    body.className = body.className.split(' ').filter(cls => !cls.startsWith('theme-')).join(' ');
    body.classList.add(savedTheme);

    // Toggle theme menu
    if (themePicker) {
        themePicker.addEventListener('click', function(e) {
            e.stopPropagation();
            themeMenu.classList.toggle('hidden');
        });
    }

    // Close theme menu when clicking outside
    document.addEventListener('click', function() {
        if (themeMenu && !themeMenu.classList.contains('hidden')) {
            themeMenu.classList.add('hidden');
        }
    });

    // Prevent theme menu from closing when clicking inside
    if (themeMenu) {
        themeMenu.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }

    // Theme switching
    themeSwatches.forEach(swatch => {
        swatch.addEventListener('click', function() {
            const theme = this.getAttribute('data-theme');
            // Remove all theme classes
            body.className = body.className.split(' ').filter(cls => !cls.startsWith('theme-')).join(' ');
            // Add selected theme class
            body.classList.add(theme);
            // Save to localStorage
            localStorage.setItem('s24_theme', theme);
            // Close menu
            if (themeMenu) themeMenu.classList.add('hidden');
            
            // Dispatch custom event for any theme change listeners
            document.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme } }));
        });
    });

    // Mobile menu toggle
    if (mobileMenuButton && mobileMenu) {
        mobileMenuButton.addEventListener('click', function() {
            mobileMenu.classList.toggle('hidden');
            this.querySelector('i').classList.toggle('fa-bars');
            this.querySelector('i').classList.toggle('fa-times');
        });
    }

    // Close mobile menu when clicking on a link
    const mobileLinks = document.querySelectorAll('#mobileMenu a');
    mobileLinks.forEach(link => {
        link.addEventListener('click', () => {
            if (mobileMenu) mobileMenu.classList.add('hidden');
            if (mobileMenuButton) {
                mobileMenuButton.querySelector('i').classList.add('fa-bars');
                mobileMenuButton.querySelector('i').classList.remove('fa-times');
            }
        });
    });

    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                window.scrollTo({
                    top: targetElement.offsetTop - 80, // Adjust for fixed header
                    behavior: 'smooth'
                });
            }
        });
    });

    // Add active class to current nav link
    const sections = document.querySelectorAll('section[id]');
    const navLinks = document.querySelectorAll('nav a[href^="#"]');
    
    function highlightNav() {
        let current = '';
        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            const sectionHeight = section.clientHeight;
            if (pageYOffset >= (sectionTop - 100)) {
                current = '#' + section.getAttribute('id');
            }
        });

        navLinks.forEach(link => {
            link.classList.remove('bg-white/20');
            if (link.getAttribute('href') === current) {
                link.classList.add('bg-white/20');
            }
        });
    }

    window.addEventListener('scroll', highlightNav);
    highlightNav(); // Run once on page load

    // Form validation example
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });

    // Initialize tooltips
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    tooltipElements.forEach(el => {
        el.addEventListener('mouseenter', showTooltip);
        el.addEventListener('mouseleave', hideTooltip);
    });

    function showTooltip(e) {
        const tooltip = document.createElement('div');
        tooltip.className = 'tooltip';
        tooltip.textContent = this.getAttribute('data-tooltip');
        document.body.appendChild(tooltip);
        
        const rect = this.getBoundingClientRect();
        tooltip.style.top = (rect.top - tooltip.offsetHeight - 10) + 'px';
        tooltip.style.left = (rect.left + (this.offsetWidth / 2) - (tooltip.offsetWidth / 2)) + 'px';
        
        this.tooltip = tooltip;
    }

    function hideTooltip() {
        if (this.tooltip) {
            this.tooltip.remove();
            this.tooltip = null;
        }
    }

    // Add animation on scroll
    const animateOnScroll = () => {
        const elements = document.querySelectorAll('.animate-on-scroll');
        elements.forEach(element => {
            const elementTop = element.getBoundingClientRect().top;
            const windowHeight = window.innerHeight;
            
            if (elementTop < windowHeight - 100) {
                element.classList.add('fade-in');
            }
        });
    };

    window.addEventListener('scroll', animateOnScroll);
    animateOnScroll(); // Run once on page load
});

// Utility function to debounce events
function debounce(func, wait = 20, immediate = true) {
    let timeout;
    return function() {
        const context = this, args = arguments;
        const later = function() {
            timeout = null;
            if (!immediate) func.apply(context, args);
        };
        const callNow = immediate && !timeout;
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
        if (callNow) func.apply(context, args);
    };
}
