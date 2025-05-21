<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register - SevenLibrary v6</title>
  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- GSAP -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
  <!-- AOS -->
  <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
  <!-- Lineicons -->
  <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet">
  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <!-- Custom styles -->
  <script>
    tailwind.config = {
      darkMode: 'class',
      theme: {
        extend: {
          colors: {
            primary: '#0a66f4',
            secondary: '#2595f2',
            tertiary: '#4bd0f2',
            sidebar: {
              bg: 'var(--sidebar-bg)',
              hover: 'var(--sidebar-hover)',
              active: 'var(--sidebar-active)',
              text: 'var(--sidebar-text)',
              icon: 'var(--sidebar-icon)',
              border: 'var(--sidebar-border)',
            }
          },
          fontFamily: {
            sans: ['Inter', 'sans-serif'],
          },
        },
      },
    }
  </script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
    
    :root {
      --sidebar-bg: #f8fafc;
      --sidebar-hover: #f1f5f9;
      --sidebar-active: #e2e8f0;
      --sidebar-text: #334155;
      --sidebar-icon: #64748b;
      --sidebar-border: #e2e8f0;
    }
    
    .dark {
      --sidebar-bg: #1e293b;
      --sidebar-hover: #334155;
      --sidebar-active: #475569;
      --sidebar-text: #f8fafc;
      --sidebar-icon: #94a3b8;
      --sidebar-border: #334155;
    }
    
    .preloader {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: #ffffff;
      z-index: 9999;
      display: flex;
      justify-content: center;
      align-items: center;
    }
    
    .dark .preloader {
      background-color: #121212;
    }
    
    .loader {
      position: relative;
      width: 120px;
      height: 120px;
    }
    
    .book {
      position: absolute;
      width: 60px;
      height: 40px;
      background-color: #0a66f4;
      border-radius: 4px;
      transform-origin: center;
    }
    
    .page {
      position: absolute;
      width: 30px;
      height: 40px;
      background-color: white;
      right: 0;
      transform-origin: left center;
    }
    
    .dark .page {
      background-color: #2a2a2a;
    }
    
    .form-container {
      background-color: rgba(255, 255, 255, 0.9);
      backdrop-filter: blur(10px);
    }
    
    .dark .form-container {
      background-color: rgba(31, 41, 55, 0.9);
    }
    
    .floating-label-group {
      position: relative;
    }
    
    .floating-label {
      position: absolute;
      pointer-events: none;
      left: 15px;
      top: 15px;
      transition: 0.2s ease all;
    }
    
    .floating-input:focus ~ .floating-label,
    .floating-input:not(:placeholder-shown) ~ .floating-label {
      top: -10px;
      left: 10px;
      font-size: 12px;
      opacity: 1;
      background: white;
      padding: 0 5px;
    }
    
    .dark .floating-input:focus ~ .floating-label,
    .dark .floating-input:not(:placeholder-shown) ~ .floating-label {
      background: #1f2937;
    }

    .password-toggle {
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      cursor: pointer;
      color: #64748b;
    }

    .password-strength {
      height: 5px;
      transition: all 0.3s ease;
      border-radius: 5px;
    }

    .password-requirements {
      transition: all 0.3s ease;
    }

    .requirement {
      display: flex;
      align-items: center;
      margin-bottom: 4px;
    }

    .requirement i {
      margin-right: 8px;
      font-size: 14px;
    }

    .check-circle {
      color: #10b981;
    }

    .times-circle {
      color: #ef4444;
    }
  </style>
</head>
<body class="font-sans antialiased bg-gray-50 text-gray-900 dark:bg-gray-900 dark:text-gray-100">
  <?php
  session_start();
  include 'includes/db.php';

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $full_name = $_POST['full_name'];
      $username = $_POST['username'];
      $email = $_POST['email'];
      $class = $_POST['class'];
      $password = $_POST['password'];
      $confirm_password = $_POST['confirm_password'];

      // Validasi Password
      if ($password !== $confirm_password) {
          echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
              Swal.fire({
                title: 'Error!',
                text: 'Password dan Konfirmasi Password tidak cocok.',
                icon: 'error',
                confirmButtonColor: '#0a66f4'
              });
            });
          </script>";
      } else {
          // Tentukan role berdasarkan kode rahasia
          $role = ($class === 'AdSMK7') ? 'admin' : 'student';

          // Hash Password
          $hashed_password = password_hash($password, PASSWORD_BCRYPT);

          // Simpan Data ke Database
          $stmt = $pdo->prepare("INSERT INTO users (full_name, username, email, class, password, role) VALUES (?, ?, ?, ?, ?, ?)");
          if ($stmt->execute([$full_name, $username, $email, $class, $hashed_password, $role])) {
              echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                  Swal.fire({
                    title: 'Berhasil!',
                    text: 'Registrasi berhasil. Silakan login.',
                    icon: 'success',
                    confirmButtonColor: '#0a66f4'
                  }).then((result) => {
                    if (result.isConfirmed) {
                      window.location.href = 'login.php';
                    }
                  });
                });
              </script>";
          } else {
              echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                  Swal.fire({
                    title: 'Error!',
                    text: 'Terjadi kesalahan saat menyimpan data. Coba lagi.',
                    icon: 'error',
                    confirmButtonColor: '#0a66f4'
                  });
                });
              </script>";
          }
      }
  }
  ?>

  <!-- Preloader -->
  <div class="preloader" id="preloader">
    <div class="loader">
      <div class="book">
        <div class="page"></div>
      </div>
    </div>
  </div>

  <!-- Main Content -->
  <div class="min-h-screen flex flex-col">
    <header class="py-4 px-6 bg-white dark:bg-gray-800 shadow-sm">
      <div class="container mx-auto">
        <div class="flex items-center justify-between">
          <a href="index.php" class="flex items-center">
            <span class="text-primary text-2xl font-bold">Seven<span class="text-secondary">Library</span></span>
          </a>
          
          <div class="flex items-center space-x-4">
            <button id="theme-toggle" class="p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700">
              <i class="lni lni-sun dark:hidden text-gray-700"></i>
              <i class="lni lni-night hidden dark:inline-block text-gray-300"></i>
            </button>
            
            <a href="index.php" class="text-gray-700 dark:text-gray-300 hover:text-primary dark:hover:text-primary font-medium">
              <i class="lni lni-arrow-left mr-1"></i> Back to Home
            </a>
          </div>
        </div>
      </div>
    </header>

    <main class="flex-grow flex items-center justify-center p-4 md:p-6 bg-gradient-to-br from-blue-50 to-white dark:from-gray-900 dark:to-gray-800">
      <div class="w-full max-w-2xl" data-aos="fade-up" data-aos-duration="800">
        <div class="form-container rounded-2xl shadow-xl overflow-hidden">
          <div class="p-8">
            <div class="text-center mb-8">
              <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Create an Account</h1>
              <p class="text-gray-600 dark:text-gray-400">Join our library community today</p>
            </div>
            
            <form method="POST" action="" id="registerForm">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Full Name -->
                <div class="floating-label-group">
                  <input type="text" id="full_name" name="full_name" class="floating-input w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-white" placeholder=" " required>
                  <label for="full_name" class="floating-label text-gray-500 dark:text-gray-400">Nama Lengkap</label>
                </div>
                
                <!-- Username -->
                <div class="floating-label-group">
                  <input type="text" id="username" name="username" class="floating-input w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-white" placeholder=" " required>
                  <label for="username" class="floating-label text-gray-500 dark:text-gray-400">Username</label>
                </div>
                
                <!-- Email -->
                <div class="floating-label-group">
                  <input type="email" id="email" name="email" class="floating-input w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-white" placeholder=" " required>
                  <label for="email" class="floating-label text-gray-500 dark:text-gray-400">Email</label>
                </div>
                
                <!-- Class -->
                <div class="floating-label-group">
                  <input type="text" id="class" name="class" class="floating-input w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-white" placeholder=" " required>
                  <label for="class" class="floating-label text-gray-500 dark:text-gray-400">Kelas</label>
                </div>
                
                <!-- Password -->
                <div class="floating-label-group relative">
                  <input type="password" id="password" name="password" class="floating-input w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-white" placeholder=" " required>
                  <label for="password" class="floating-label text-gray-500 dark:text-gray-400">Password</label>
                  <span class="password-toggle" id="togglePassword">
                    <i class="lni lni-eye"></i>
                  </span>
                </div>
                
                <!-- Confirm Password -->
                <div class="floating-label-group relative">
                  <input type="password" id="confirm_password" name="confirm_password" class="floating-input w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-white" placeholder=" " required>
                  <label for="confirm_password" class="floating-label text-gray-500 dark:text-gray-400">Konfirmasi Password</label>
                  <span class="password-toggle" id="toggleConfirmPassword">
                    <i class="lni lni-eye"></i>
                  </span>
                </div>
              </div>

              <!-- Password Strength Indicator -->
              <div class="mt-4">
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                  <div class="password-strength bg-red-500 h-1.5 rounded-full" style="width: 0%"></div>
                </div>
                
                <div class="password-requirements mt-3 text-sm text-gray-600 dark:text-gray-400 hidden">
                  <p class="font-medium mb-2">Password harus memenuhi kriteria berikut:</p>
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                    <div class="requirement" id="req-length">
                      <i class="lni lni-close-circle times-circle"></i>
                      <span>Minimal 8 karakter</span>
                    </div>
                    <div class="requirement" id="req-lowercase">
                      <i class="lni lni-close-circle times-circle"></i>
                      <span>Minimal 1 huruf kecil</span>
                    </div>
                    <div class="requirement" id="req-uppercase">
                      <i class="lni lni-close-circle times-circle"></i>
                      <span>Minimal 1 huruf besar</span>
                    </div>
                    <div class="requirement" id="req-number">
                      <i class="lni lni-close-circle times-circle"></i>
                      <span>Minimal 1 angka</span>
                    </div>
                  </div>
                </div>
              </div>

              <div class="mt-6">
                <div class="flex items-center">
                  <input type="checkbox" id="terms" class="w-4 h-4 text-primary focus:ring-primary border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700" required>
                  <label for="terms" class="ml-2 text-sm text-gray-600 dark:text-gray-400">
                    Saya menyetujui <a href="#" class="text-primary hover:underline">Syarat dan Ketentuan</a> serta <a href="#" class="text-primary hover:underline">Kebijakan Privasi</a>
                  </label>
                </div>
              </div>
              
              <div class="mt-6">
                <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-3 border border-transparent text-base font-medium rounded-lg text-white bg-primary hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-all duration-300 transform hover:-translate-y-1">
                  Daftar Sekarang
                  <i class="lni lni-user ml-2"></i>
                </button>
              </div>
            </form>
            
            <div class="mt-8 text-center">
              <p class="text-sm text-gray-600 dark:text-gray-400">
                Sudah memiliki akun? <a href="login.php" class="text-primary hover:underline">Login</a>
              </p>
            </div>
          </div>
        </div>
      </div>
    </main>

    <footer class="py-4 px-6 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700">
      <div class="container mx-auto">
        <div class="flex flex-col md:flex-row justify-between items-center">
          <p class="text-sm text-gray-600 dark:text-gray-400 mb-4 md:mb-0">
            &copy; 2025 SevenLibrary. All rights reserved.
          </p>
          <div class="flex space-x-6">
            <a href="#" class="text-sm text-gray-600 dark:text-gray-400 hover:text-primary dark:hover:text-primary">Privacy Policy</a>
            <a href="#" class="text-sm text-gray-600 dark:text-gray-400 hover:text-primary dark:hover:text-primary">Terms of Service</a>
            <a href="#" class="text-sm text-gray-600 dark:text-gray-400 hover:text-primary dark:hover:text-primary">Help Center</a>
          </div>
        </div>
      </div>
    </footer>
  </div>

  <!-- JavaScript -->
  <script>
    // Initialize AOS
    AOS.init({
      once: true,
      duration: 800,
      easing: 'ease-out-cubic'
    });
    
    // Preloader
    document.addEventListener('DOMContentLoaded', function() {
      // GSAP Animation for preloader
      const tl = gsap.timeline();
      
      tl.to('.book', {
        duration: 0.8,
        rotation: 15,
        repeat: -1,
        yoyo: true,
        ease: 'power1.inOut'
      });
      
      tl.to('.page', {
        duration: 0.6,
        rotation: -30,
        repeat: -1,
        yoyo: true,
        ease: 'power1.inOut'
      }, 0);
      
      // Hide preloader after page load
      setTimeout(function() {
        gsap.to('#preloader', {
          duration: 0.8,
          opacity: 0,
          onComplete: function() {
            document.getElementById('preloader').style.display = 'none';
          }
        });
      }, 1000);
    });
    
    // Theme Toggle
    const themeToggle = document.getElementById('theme-toggle');
    
    // Check for saved theme preference or use user's system preference
    if (localStorage.getItem('theme') === 'dark' || 
        (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
      document.documentElement.classList.add('dark');
    } else {
      document.documentElement.classList.remove('dark');
    }
    
    // Toggle theme on button click
    themeToggle.addEventListener('click', function() {
      if (document.documentElement.classList.contains('dark')) {
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');
      } else {
        document.documentElement.classList.add('dark');
        localStorage.setItem('theme', 'dark');
      }
    });

    // Password Toggle Visibility
    document.getElementById('togglePassword').addEventListener('click', function() {
      const passwordInput = document.getElementById('password');
      const icon = this.querySelector('i');
      
      if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        icon.classList.remove('lni-eye');
        icon.classList.add('lni-eye-slash');
      } else {
        passwordInput.type = 'password';
        icon.classList.remove('lni-eye-slash');
        icon.classList.add('lni-eye');
      }
    });

    document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
      const confirmPasswordInput = document.getElementById('confirm_password');
      const icon = this.querySelector('i');
      
      if (confirmPasswordInput.type === 'password') {
        confirmPasswordInput.type = 'text';
        icon.classList.remove('lni-eye');
        icon.classList.add('lni-eye-slash');
      } else {
        confirmPasswordInput.type = 'password';
        icon.classList.remove('lni-eye-slash');
        icon.classList.add('lni-eye');
      }
    });

    // Password Strength Checker
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const strengthIndicator = document.querySelector('.password-strength');
    const passwordRequirements = document.querySelector('.password-requirements');
    
    const requirements = {
      length: { element: document.getElementById('req-length'), regex: /.{8,}/ },
      lowercase: { element: document.getElementById('req-lowercase'), regex: /[a-z]/ },
      uppercase: { element: document.getElementById('req-uppercase'), regex: /[A-Z]/ },
      number: { element: document.getElementById('req-number'), regex: /[0-9]/ }
    };

    passwordInput.addEventListener('focus', function() {
      passwordRequirements.classList.remove('hidden');
    });

    passwordInput.addEventListener('input', function() {
      const password = this.value;
      let strength = 0;
      let meetsAllRequirements = true;
      
      // Check each requirement
      for (const [key, requirement] of Object.entries(requirements)) {
        const isValid = requirement.regex.test(password);
        const icon = requirement.element.querySelector('i');
        
        if (isValid) {
          strength += 25;
          icon.classList.remove('lni-close-circle', 'times-circle');
          icon.classList.add('lni-checkmark-circle', 'check-circle');
        } else {
          meetsAllRequirements = false;
          icon.classList.remove('lni-checkmark-circle', 'check-circle');
          icon.classList.add('lni-close-circle', 'times-circle');
        }
      }
      
      // Update strength indicator
      strengthIndicator.style.width = `${strength}%`;
      
      if (strength <= 25) {
        strengthIndicator.classList.remove('bg-yellow-500', 'bg-green-500');
        strengthIndicator.classList.add('bg-red-500');
      } else if (strength <= 75) {
        strengthIndicator.classList.remove('bg-red-500', 'bg-green-500');
        strengthIndicator.classList.add('bg-yellow-500');
      } else {
        strengthIndicator.classList.remove('bg-red-500', 'bg-yellow-500');
        strengthIndicator.classList.add('bg-green-500');
      }
    });

    // Password Confirmation Validation
    confirmPasswordInput.addEventListener('input', function() {
      if (this.value !== passwordInput.value) {
        this.setCustomValidity('Passwords do not match');
      } else {
        this.setCustomValidity('');
      }
    });

    // Form Validation Animation
    const registerForm = document.getElementById('registerForm');
    
    registerForm.addEventListener('submit', function(e) {
      if (!this.checkValidity()) {
        e.preventDefault();
        
        // Shake animation for invalid form
        gsap.to(this, {
          x: [-10, 10, -10, 10, 0],
          duration: 0.5,
          ease: 'power2.inOut'
        });
        
        // Highlight invalid fields
        const invalidFields = this.querySelectorAll(':invalid');
        invalidFields.forEach(field => {
          gsap.to(field, {
            borderColor: '#ef4444',
            duration: 0.3
          });
          
          field.addEventListener('input', function() {
            gsap.to(this, {
              borderColor: '',
              duration: 0.3
            });
          }, { once: true });
        });
      }
    });
  </script>
</body>
</html>