<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - SevenLibrary v6</title>
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
  </style>
</head>
<body class="font-sans antialiased bg-gray-50 text-gray-900 dark:bg-gray-900 dark:text-gray-100">
  <?php
  session_start();
  include 'includes/db.php';

  if ($_SERVER['REQUEST_METHOD'] == 'POST') {
      $username = $_POST['username'];
      $password = $_POST['password'];

      // Query untuk mendapatkan data pengguna berdasarkan username
      $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
      $stmt->execute([$username]);
      $user = $stmt->fetch();

      // Verifikasi password dan set session
      if ($user && password_verify($password, $user['password'])) {
          $_SESSION['user_id'] = $user['id'];
          $_SESSION['role'] = $user['role'];

          // Arahkan berdasarkan role
          if ($user['role'] == 'student') {
              header("Location: student/dashboard.php");
          } elseif ($user['role'] == 'admin') {
              header("Location: admin/dashboard.php");
          } elseif ($user['role'] == 'petugas') {
              header("Location: petugas/dashboard.php");
          }
          exit();
      } else {
          echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
              Swal.fire({
                title: 'Login gagal!',
                text: 'Username atau password salah.',
                icon: 'error',
                confirmButtonColor: '#0a66f4'
              });
            });
          </script>";
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
      <div class="w-full max-w-md" data-aos="fade-up" data-aos-duration="800">
        <div class="form-container rounded-2xl shadow-xl overflow-hidden">
          <div class="p-8">
            <div class="text-center mb-8">
              <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Welcome Back</h1>
              <p class="text-gray-600 dark:text-gray-400">Sign in to your account to continue</p>
            </div>
            
            <form method="POST" action="">
              <div class="space-y-6">
                <div class="floating-label-group">
                  <input type="text" id="username" name="username" class="floating-input w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-white" placeholder=" " required>
                  <label for="username" class="floating-label text-gray-500 dark:text-gray-400">Username</label>
                </div>
                
                <div class="floating-label-group">
                  <input type="password" id="password" name="password" class="floating-input w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-white" placeholder=" " required>
                  <label for="password" class="floating-label text-gray-500 dark:text-gray-400">Password</label>
                </div>
                
                <div class="flex items-center justify-between">
                  <div class="flex items-center">
                    <input type="checkbox" id="remember" class="w-4 h-4 text-primary focus:ring-primary border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700">
                    <label for="remember" class="ml-2 text-sm text-gray-600 dark:text-gray-400">Remember me</label>
                  </div>
                  
                  <a href="#" class="text-sm text-primary hover:underline">Forgot password?</a>
                </div>
                
                <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-3 border border-transparent text-base font-medium rounded-lg text-white bg-primary hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                  Sign In
                  <i class="lni lni-arrow-right ml-2"></i>
                </button>
              </div>
            </form>
            
            <div class="mt-8 text-center">
              <p class="text-sm text-gray-600 dark:text-gray-400">
                Don't have an account? <a href="register.php" class="text-primary hover:underline">Register</a>
              </p>
            </div>
            
            <div class="mt-6">
              <div class="relative">
                <div class="absolute inset-0 flex items-center">
                  <div class="w-full border-t border-gray-300 dark:border-gray-600"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                  <span class="px-2 bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400">Or continue with</span>
                </div>
              </div>
              
              <div class="mt-6 grid grid-cols-2 gap-3">
                <a href="#" class="w-full inline-flex justify-center py-2 px-4 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm bg-white dark:bg-gray-700 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600">
                  <i class="lni lni-google text-lg"></i>
                </a>
                
                <a href="#" class="w-full inline-flex justify-center py-2 px-4 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm bg-white dark:bg-gray-700 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600">
                  <i class="lni lni-facebook-filled text-lg"></i>
                </a>
              </div>
            </div>
          </div>
        </div>
        
        <div class="mt-8 text-center">
          <a href="guest-book.php" class="text-primary hover:underline">
            <i class="lni lni-book mr-1"></i> Sign Guest Book
          </a>
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
    
    // Guestbook Form Toggle
    const guestbookButton = document.getElementById('guestbookButton');
    const guestbookForm = document.getElementById('guestbookForm');
    const buttonText = document.getElementById('buttonText');
    
    guestbookButton.addEventListener('click', () => {
      guestbookForm.classList.toggle('open');
      
      if (guestbookForm.classList.contains('open')) {
        buttonText.textContent = 'Tutup Form';
        gsap.to(guestbookForm, {
          duration: 0.5,
          maxHeight: 500,
          ease: 'power2.out'
        });
      } else {
        buttonText.textContent = 'Isi Buku Tamu';
        gsap.to(guestbookForm, {
          duration: 0.5,
          maxHeight: 0,
          ease: 'power2.out'
        });
      }
    });
    
    <?php
    // Show alert if form was submitted successfully
    if (isset($_POST['submit_guestbook'])) {
      echo "
      setTimeout(function() {
        const alertBox = document.createElement('div');
        alertBox.className = 'fixed top-4 right-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded z-50';
        alertBox.innerHTML = '<div class=\"flex items-center\"><i class=\"lni lni-checkmark-circle mr-2\"></i><span>Pesan berhasil ditambahkan ke Buku Tamu!</span></div>';
        document.body.appendChild(alertBox);
        
        setTimeout(function() {
          alertBox.remove();
        }, 5000);
      }, 500);
      ";
    }
    ?>
  </script>