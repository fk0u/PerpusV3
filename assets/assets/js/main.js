/**
 * E-Library SMKN 7 Samarinda
 * Main JavaScript File
 */

document.addEventListener('DOMContentLoaded', function() {
    // Counter animation for stats
    const stats = document.querySelectorAll('.stat-number');
    
    if (stats.length > 0) {
        const animateCounter = (el) => {
            const target = parseInt(el.textContent.replace(/[^\d]/g, ''));
            const duration = 2000; // 2 seconds
            const step = 30; // Update every 30ms
            const steps = duration / step;
            const increment = target / steps;
            let current = 0;
            
            const timer = setInterval(() => {
                current += increment;
                
                if (current >= target) {
                    el.textContent = el.textContent.includes('+') ? 
                        target.toLocaleString() + '+' : 
                        target.toLocaleString();
                    clearInterval(timer);
                } else {
                    el.textContent = el.textContent.includes('+') ? 
                        Math.floor(current).toLocaleString() + '+' : 
                        Math.floor(current).toLocaleString();
                }
            }, step);
        };
        
        // Intersection Observer for stats animation
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animateCounter(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });
        
        stats.forEach(stat => observer.observe(stat));
    }
    
    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                window.scrollTo({
                    top: targetElement.offsetTop - 80, // Offset for header
                    behavior: 'smooth'
                });
                
                // Close mobile menu if open
                const navLinks = document.querySelector('.nav-links');
                const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
                
                if (navLinks.classList.contains('active')) {
                    navLinks.classList.remove('active');
                    mobileMenuToggle.classList.remove('active');
                }
            }
        });
    });
    
    // Form validation enhancement
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        const inputs = form.querySelectorAll('input, textarea');
        
        inputs.forEach(input => {
            // Add validation styles on blur
            input.addEventListener('blur', function() {
                if (this.checkValidity()) {
                    this.classList.add('is-valid');
                    this.classList.remove('is-invalid');
                } else if (this.value !== '') {
                    this.classList.add('is-invalid');
                    this.classList.remove('is-valid');
                }
            });
            
            // Remove validation styles on focus
            input.addEventListener('focus', function() {
                this.classList.remove('is-invalid');
                this.classList.remove('is-valid');
            });
        });
    });
    
    // Alert auto-dismiss
    const alerts = document.querySelectorAll('.alert');
    
    alerts.forEach(alert => {
        setTimeout(() => {
            // Initialize Bootstrap Alert (assuming Bootstrap's JS is included)
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000); // Auto dismiss after 5 seconds
    });
});