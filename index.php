<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>E-Library SMKN 7 Samarinda - SevenLibrary v6</title>
  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- GSAP -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
  <!-- AOS -->
  <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
  <!-- Lineicons -->
  <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet">
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
    
    .hero-section {
      position: relative;
      overflow: hidden;
      border-radius: 1rem;
    }
    
    .hero-section::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(10, 102, 244, 0.4);
      z-index: 1;
    }
    
    .hero-content {
      position: relative;
      z-index: 2;
    }
    
    .guestbook-form {
      max-height: 0;
      overflow: hidden;
      transition: max-height 0.5s ease-in-out;
    }
    
    .guestbook-form.open {
      max-height: 500px;
    }
  </style>
</head>
<body class="font-sans antialiased bg-gray-50 text-gray-900 dark:bg-gray-900 dark:text-gray-100">
  <!-- Preloader -->
  <div class="preloader" id="preloader">
    <div class="loader">
      <div class="book">
        <div class="page"></div>
      </div>
    </div>
  </div>

  <!-- Header -->
  <header class="bg-white dark:bg-gray-800 shadow-sm">
    <div class="container mx-auto px-4">
      <div class="flex items-center justify-between h-16">
        <div class="flex items-center">
          <a href="index.php" class="flex items-center">
            <span class="text-primary text-xl font-bold">Seven<span class="text-secondary">Library</span></span>
          </a>
        </div>
        
        <div class="flex items-center space-x-4">
          <!-- Theme Toggle -->
          <button id="theme-toggle" class="w-10 h-10 rounded-full flex items-center justify-center text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none">
            <i class="lni lni-sun dark:hidden text-xl"></i>
            <i class="lni lni-night hidden dark:inline-block text-xl"></i>
          </button>
          
          <div class="flex space-x-2">
            <a href="login.php" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 text-sm font-medium transition-all duration-200 hover:scale-105">Login</a>
            <a href="register.php" class="px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 text-sm font-medium transition-all duration-200 hover:scale-105">Register</a>
          </div>
        </div>
      </div>
    </div>
  </header>

  <!-- Main Content -->
  <main class="container mx-auto px-4 py-8">
    <!-- Hero Section -->
    <div class="hero-section bg-cover bg-center h-96 mb-12" style="background-image: url('https://source.unsplash.com/1600x900/?library,books')" data-aos="fade-up" data-aos-duration="1000">
      <div class="hero-content h-full flex flex-col items-center justify-center text-center px-6">
        <h1 class="text-4xl md:text-5xl font-bold text-white mb-4 animate__animated animate__fadeInDown">Welcome to E-Library Seven</h1>
        <p class="text-lg text-white mb-8 max-w-2xl animate__animated animate__fadeInUp">
          Selamat datang di perpustakaan digital kami. Temukan buku-buku terbaik untuk mendukung pembelajaran Anda.
        </p>
        <div class="flex flex-wrap justify-center gap-4">
          <a href="login.php" class="px-6 py-3 bg-primary text-white rounded-full hover:bg-primary/90 text-base font-medium transition-all duration-300 hover:scale-105 shadow-lg">
            <i class="lni lni-user mr-2"></i> Login
          </a>
          <a href="register.php" class="px-6 py-3 bg-white text-primary rounded-full hover:bg-gray-100 text-base font-medium transition-all duration-300 hover:scale-105 shadow-lg">
            <i class="lni lni-pencil mr-2"></i> Register
          </a>
        </div>
      </div>
    </div>
    
    <!-- Features Section -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
      <div class="dashboard-card bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm transition-all duration-300 hover:-translate-y-2 hover:shadow-md" data-aos="fade-up" data-aos-delay="100">
        <div class="flex items-center mb-4">
          <div class="w-12 h-12 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-500 dark:text-blue-400">
            <i class="lni lni-book text-xl"></i>
          </div>
          <h3 class="ml-4 text-lg font-semibold text-gray-800 dark:text-white">Digital Collection</h3>
        </div>
        <p class="text-gray-600 dark:text-gray-300">Akses ribuan buku digital, jurnal, dan materi pembelajaran kapan saja dan di mana saja.</p>
      </div>
      
      <div class="dashboard-card bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm transition-all duration-300 hover:-translate-y-2 hover:shadow-md" data-aos="fade-up" data-aos-delay="200">
        <div class="flex items-center mb-4">
          <div class="w-12 h-12 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center text-green-500 dark:text-green-400">
            <i class="lni lni-laptop text-xl"></i>
          </div>
          <h3 class="ml-4 text-lg font-semibold text-gray-800 dark:text-white">Online Reading</h3>
        </div>
        <p class="text-gray-600 dark:text-gray-300">Baca buku secara online tanpa perlu mengunduh. Nikmati pengalaman membaca yang nyaman.</p>
      </div>
      
      <div class="dashboard-card bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm transition-all duration-300 hover:-translate-y-2 hover:shadow-md" data-aos="fade-up" data-aos-delay="300">
        <div class="flex items-center mb-4">
          <div class="w-12 h-12 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center text-purple-500 dark:text-purple-400">
            <i class="lni lni-users text-xl"></i>
          </div>
          <h3 class="ml-4 text-lg font-semibold text-gray-800 dark:text-white">Forum Diskusi</h3>
        </div>
        <p class="text-gray-600 dark:text-gray-300">Bergabunglah dalam forum diskusi untuk berbagi pengetahuan dan bertukar pikiran dengan pembaca lain.</p>
      </div>
    </div>

    <!-- How It Works Section -->
    <section id="how-it-works" class="py-16 bg-gray-50 dark:bg-gray-900">
      <div class="container mx-auto px-4">
        <div class="text-center mb-12" data-aos="fade-up">
          <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">How It Works</h2>
          <p class="text-gray-600 dark:text-gray-300 max-w-2xl mx-auto">
            Our library system is designed to make borrowing and returning books simple and efficient
          </p>
        </div>
        
        <div class="grid md:grid-cols-3 gap-8">
          <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm" data-aos="fade-up" data-aos-delay="100">
            <div class="w-12 h-12 flex items-center justify-center bg-primary/10 text-primary rounded-full mb-4">
              <span class="text-xl font-bold">1</span>
            </div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-3">Browse & Select</h3>
            <p class="text-gray-600 dark:text-gray-300">
              Search our extensive catalog by title, author, or category to find the perfect book for you.
            </p>
          </div>
          
          <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm" data-aos="fade-up" data-aos-delay="200">
            <div class="w-12 h-12 flex items-center justify-center bg-primary/10 text-primary rounded-full mb-4">
              <span class="text-xl font-bold">2</span>
            </div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-3">Borrow</h3>
            <p class="text-gray-600 dark:text-gray-300">
              Request to borrow the book and receive a unique code to collect it from the library.
            </p>
          </div>
          
          <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm" data-aos="fade-up" data-aos-delay="300">
            <div class="w-12 h-12 flex items-center justify-center bg-primary/10 text-primary rounded-full mb-4">
              <span class="text-xl font-bold">3</span>
            </div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-3">Return</h3>
            <p class="text-gray-600 dark:text-gray-300">
              Return the book within 7 days using your unique return code to complete the process.
            </p>
          </div>
        </div>
      </div>
    </section>

    
    <!-- Buku Tamu Section -->
    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm mb-12" data-aos="fade-up">
      <div class="text-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-2">Buku Tamu</h2>
        <p class="text-gray-600 dark:text-gray-300">Silakan tinggalkan pesan dan kesan Anda tentang perpustakaan kami</p>
      </div>
      
      <div class="flex justify-center mb-4">
        <button id="guestbookButton" class="px-6 py-3 bg-primary text-white rounded-full hover:bg-primary/90 text-base font-medium transition-all duration-300 hover:scale-105 shadow-lg flex items-center">
          <i class="lni lni-pencil-alt mr-2"></i> <span id="buttonText">Isi Buku Tamu</span>
        </button>
      </div>
      
      <div id="guestbookForm" class="guestbook-form max-w-lg mx-auto">
        <form method="POST" action="" class="space-y-4 mt-6">
          <div>
            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nama</label>
            <input type="text" class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-white" id="name" name="name" required>
          </div>
          
          <div>
            <label for="class" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kelas</label>
            <input type="text" class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-white" id="class" name="class">
          </div>
          
          <div>
            <label for="message" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Pesan</label>
            <textarea class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-white" id="message" name="message" rows="4" required></textarea>
          </div>
          
          <div>
            <button type="submit" name="submit_guestbook" class="w-full px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary/90 text-base font-medium transition-all duration-200">
              <i class="lni lni-telegram-original mr-2"></i> Kirim Pesan
            </button>
          </div>
        </form>
      </div>
    </div>
  </main>

  <!-- Footer -->
  <footer class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 py-8">
    <div class="container mx-auto px-4">
      <div class="flex flex-col md:flex-row justify-between items-center">
        <div class="mb-4 md:mb-0">
          <a href="index.php" class="flex items-center">
            <span class="text-primary text-xl font-bold">Seven<span class="text-secondary">Library</span></span>
          </a>
          <p class="text-gray-600 dark:text-gray-400 mt-2">© 2025 SMKN 7 Samarinda. All rights reserved.</p>
        </div>
        
        <div class="flex space-x-6">
          <a href="#" class="text-gray-500 dark:text-gray-400 hover:text-primary dark:hover:text-primary">
            <i class="lni lni-facebook-filled text-xl"></i>
          </a>
          <a href="#" class="text-gray-500 dark:text-gray-400 hover:text-primary dark:hover:text-primary">
            <i class="lni lni-instagram-filled text-xl"></i>
          </a>
          <a href="#" class="text-gray-500 dark:text-gray-400 hover:text-primary dark:hover:text-primary">
            <i class="lni lni-twitter-filled text-xl"></i>
          </a>
          <a href="#" class="text-gray-500 dark:text-gray-400 hover:text-primary dark:hover:text-primary">
            <i class="lni lni-youtube text-xl"></i>
          </a>
        </div>
      </div>
    </div>
  </footer>

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
  
  <?php
  // Process form submission
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_guestbook'])) {
      $name = $_POST['name'];
      $class = $_POST['class'] ?? '';
      $message = $_POST['message'];
      
      // In a real application, you would insert this into your database
      // $stmt = $pdo->prepare("INSERT INTO guestbook (name, class, message) VALUES (?, ?, ?)");
      // $stmt->execute([$name, $class, $message]);
  }
  ?>
  <?php include 'includes/footer.php'; ?>
</body>
</html>