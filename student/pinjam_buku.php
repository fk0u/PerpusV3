<?php
session_start();
include '../includes/db.php';

// Pastikan pengguna sudah login dan memiliki role 'student'
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

// Ambil book_id dari URL
$book_id = isset($_GET['book_id']) ? (int)$_GET['book_id'] : 0;
$error_message = '';
$success = false;

if ($book_id == 0) {
    $error_message = 'ID buku tidak valid.';
} else {
    // Ambil informasi buku berdasarkan book_id yang diterima dari URL
    $stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
    $stmt->execute([$book_id]);
    $book = $stmt->fetch();

    if (!$book) {
        $error_message = 'Buku tidak ditemukan.';
    }
}

// Ambil informasi user
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT username, email, full_name FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Proses peminjaman buku
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error_message) {
    $borrower_name = $_POST['borrower_name']; // Nama Peminjam
    $borrower_email = $_POST['borrower_email']; // Email Peminjam
    $borrow_date = $_POST['borrow_date']; // Tanggal pinjam
    $return_date = $_POST['return_date']; // Tanggal kembali

    // Generate kode unik untuk peminjaman
    $unique_code = uniqid('borrow_', true);

    // Cek apakah tanggal kembali lebih dari 7 hari
    $max_return_date = date('Y-m-d', strtotime($borrow_date . ' +7 days'));
    if (strtotime($return_date) > strtotime($max_return_date)) {
        $error_message = 'Tanggal pengembalian maksimal 7 hari dari tanggal peminjaman.';
    } else {
        // Simpan peminjaman ke database dengan kode unik dan status 'dipinjam'
        $stmt = $pdo->prepare("INSERT INTO borrow_history (user_id, book_id, borrow_date, return_date, unique_code, status) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$_SESSION['user_id'], $book_id, $borrow_date, $return_date, $unique_code, 'dipinjam'])) {
            $success = true;
            $borrow_id = $pdo->lastInsertId();
        } else {
            $error_message = 'Terjadi kesalahan saat meminjam buku. Coba lagi.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pinjam Buku - SevenLibrary v6</title>
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
            success: '#10b981',
            warning: '#f59e0b',
            danger: '#ef4444',
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
    
    /* Card */
    .dashboard-card {
      border-radius: 0.75rem;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    
    .dashboard-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }
    
    /* Form Styles */
    .form-input {
      @apply w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-white;
    }
    
    .form-label {
      @apply block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1;
    }
    
    .form-group {
      @apply mb-4;
    }
    
    /* Floating labels */
    .form-floating {
      position: relative;
    }
    
    .form-floating input {
      height: 3.5rem;
      padding-top: 1.625rem;
      padding-bottom: 0.625rem;
    }
    
    .form-floating label {
      position: absolute;
      top: 0;
      left: 0;
      height: 100%;
      padding: 1rem 0.75rem;
      pointer-events: none;
      border: 1px solid transparent;
      transform-origin: 0 0;
      transition: opacity .1s ease-in-out, transform .1s ease-in-out;
      color: #6b7280;
    }
    
    .form-floating input:focus ~ label,
    .form-floating input:not(:placeholder-shown) ~ label {
      opacity: .65;
      transform: scale(.85) translateY(-0.5rem) translateX(0.15rem);
    }
    
    .form-floating input:focus {
      box-shadow: none;
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

  <div class="min-h-screen flex flex-col">
    <!-- Header -->
    <header class="bg-white dark:bg-gray-800 shadow-sm">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex">
            <a href="dashboard.php" class="flex items-center text-primary">
              <i class="lni lni-arrow-left text-xl mr-2"></i>
              <span class="font-medium">Kembali ke Dashboard</span>
            </a>
          </div>
          <div class="flex items-center">
            <button id="theme-toggle" class="w-10 h-10 rounded-full flex items-center justify-center text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none">
              <i class="lni lni-sun dark:hidden text-xl"></i>
              <i class="lni lni-night hidden dark:inline-block text-xl"></i>
            </button>
          </div>
        </div>
      </div>
    </header>

    <!-- Main Content -->
    <main class="flex-grow py-10">
      <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <?php if ($success): ?>
          <!-- Success Card -->
          <div class="dashboard-card bg-white dark:bg-gray-800 overflow-hidden" data-aos="fade-up">
            <div class="p-6 sm:p-10">
              <div class="text-center">
                <div class="w-20 h-20 mx-auto bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center text-green-500 dark:text-green-400 mb-6">
                  <i class="lni lni-checkmark-circle text-4xl"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Peminjaman Berhasil!</h2>
                <p class="text-gray-600 dark:text-gray-300 mb-6">Buku berhasil dipinjam. Silakan ambil buku di perpustakaan.</p>
                
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6 mb-6 text-left">
                  <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Detail Peminjaman</h3>
                  <div class="space-y-3">
                    <div class="flex justify-between">
                      <span class="text-gray-500 dark:text-gray-400">Judul Buku:</span>
                      <span class="font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($book['title']); ?></span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-gray-500 dark:text-gray-400">Penulis:</span>
                      <span class="font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($book['author']); ?></span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-gray-500 dark:text-gray-400">Peminjam:</span>
                      <span class="font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($_POST['borrower_name']); ?></span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-gray-500 dark:text-gray-400">Tanggal Pinjam:</span>
                      <span class="font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($_POST['borrow_date']); ?></span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-gray-500 dark:text-gray-400">Tanggal Kembali:</span>
                      <span class="font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($_POST['return_date']); ?></span>
                    </div>
                  </div>
                </div>
                
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-6 mb-6">
                  <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Kode Unik Peminjaman</h3>
                  <div class="bg-white dark:bg-gray-800 rounded-lg border-2 border-dashed border-blue-300 dark:border-blue-700 p-4 mb-4">
                    <p class="text-xl font-mono font-bold text-blue-600 dark:text-blue-400 select-all"><?= htmlspecialchars($unique_code); ?></p>
                  </div>
                  <p class="text-sm text-gray-600 dark:text-gray-300">Simpan kode unik ini untuk proses pengembalian buku.</p>
                </div>
                
                <div class="mt-6 flex flex-col sm:flex-row gap-4 justify-center">
                  <a href="dashboard.php" class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-primary hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                    <i class="lni lni-home mr-2"></i> Kembali ke Dashboard
                  </a>
                  <a href="dashboard.php?tab=riwayat" class="inline-flex items-center justify-center px-6 py-3 border border-gray-300 dark:border-gray-600 text-base font-medium rounded-md shadow-sm text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                    <i class="lni lni-library mr-2"></i> Lihat Riwayat Peminjaman
                  </a>
                </div>
              </div>
            </div>
          </div>
        <?php elseif ($error_message): ?>
          <!-- Error Card -->
          <div class="dashboard-card bg-white dark:bg-gray-800 overflow-hidden" data-aos="fade-up">
            <div class="p-6 sm:p-10">
              <div class="text-center">
                <div class="w-20 h-20 mx-auto bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center text-red-500 dark:text-red-400 mb-6">
                  <i class="lni lni-close text-4xl"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Peminjaman Gagal</h2>
                <p class="text-gray-600 dark:text-gray-300 mb-6"><?= htmlspecialchars($error_message); ?></p>
                
                <div class="mt-6">
                  <a href="dashboard.php" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-primary hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                    <i class="lni lni-home mr-2"></i> Kembali ke Dashboard
                  </a>
                </div>
              </div>
            </div>
          </div>
        <?php else: ?>
          <!-- Borrow Form Card -->
          <div class="dashboard-card bg-white dark:bg-gray-800 overflow-hidden" data-aos="fade-up">
            <div class="p-6 sm:p-10">
              <div class="flex items-center justify-center mb-6">
                <div class="w-16 h-16 bg-primary/20 rounded-full flex items-center justify-center text-primary text-3xl mr-4">
                  <i class="lni lni-book"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Formulir Peminjaman Buku</h2>
              </div>
              
              <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Detail Buku</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Judul Buku</p>
                    <p class="font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($book['title']); ?></p>
                  </div>
                  <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Penulis</p>
                    <p class="font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($book['author']); ?></p>
                  </div>
                  <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Tahun Terbit</p>
                    <p class="font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($book['published_year']); ?></p>
                  </div>
                  <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">ISBN</p>
                    <p class="font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($book['isbn']); ?></p>
                  </div>
                </div>
              </div>
              
              <form method="POST" action="" class="space-y-6">
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-6 mb-4">
                  <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Informasi Peminjam</h3>
                  
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-floating">
                      <input type="text" id="borrower_name" name="borrower_name" class="form-input" placeholder=" " value="<?= htmlspecialchars($user['full_name'] ?? $user['username']); ?>" required>
                      <label for="borrower_name" class="px-4">Nama Peminjam</label>
                    </div>
                    
                    <div class="form-floating">
                      <input type="email" id="borrower_email" name="borrower_email" class="form-input" placeholder=" " value="<?= htmlspecialchars($user['email']); ?>" required>
                      <label for="borrower_email" class="px-4">Email Peminjam</label>
                    </div>
                  </div>
                </div>
                
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-6">
                  <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Tanggal Peminjaman</h3>
                  
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group">
                      <label for="borrow_date" class="form-label">Tanggal Pinjam</label>
                      <input type="date" id="borrow_date" name="borrow_date" class="form-input" value="<?= date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="form-group">
                      <label for="return_date" class="form-label">Tanggal Pengembalian (Maksimal 7 hari)</label>
                      <input type="date" id="return_date" name="return_date" class="form-input" value="<?= date('Y-m-d', strtotime('+7 days')); ?>" required>
                      <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Maksimal 7 hari dari tanggal peminjaman</p>
                    </div>
                  </div>
                </div>
                
                <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-6">
                  <div class="flex items-start">
                    <div class="flex-shrink-0">
                      <i class="lni lni-warning text-yellow-500 dark:text-yellow-400 text-xl"></i>
                    </div>
                    <div class="ml-3">
                      <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-300">Perhatian</h3>
                      <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-200">
                        <p>Dengan mengajukan peminjaman buku ini, Anda setuju untuk:</p>
                        <ul class="list-disc pl-5 mt-1 space-y-1">
                          <li>Mengembalikan buku tepat waktu</li>
                          <li>Menjaga kondisi buku tetap baik</li>
                          <li>Membayar denda jika terlambat mengembalikan</li>
                        </ul>
                      </div>
                    </div>
                  </div>
                </div>
                
                <div class="flex justify-end space-x-4">
                  <a href="dashboard.php" class="px-6 py-3 border border-gray-300 dark:border-gray-600 text-base font-medium rounded-md shadow-sm text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                    Batal
                  </a>
                  <button type="submit" class="px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-primary hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                    Pinjam Buku
                  </button>
                </div>
              </form>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700">
      <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <p class="text-center text-sm text-gray-500 dark:text-gray-400">
          &copy; <?= date('Y'); ?> SevenLibrary v6. All rights reserved.
        </p>
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
    
    <?php if ($success): ?>
    // Show success notification
    Swal.fire({
      icon: 'success',
      title: 'Berhasil!',
      text: 'Buku berhasil dipinjam.',
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 3000
    });
    <?php elseif ($error_message): ?>
    // Show error notification
    Swal.fire({
      icon: 'error',
      title: 'Gagal!',
      text: '<?= $error_message ?>',
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 3000
    });
    <?php endif; ?>
    
    // Date validation
    document.addEventListener('DOMContentLoaded', function() {
      const borrowDateInput = document.getElementById('borrow_date');
      const returnDateInput = document.getElementById('return_date');
      
      if (borrowDateInput && returnDateInput) {
        borrowDateInput.addEventListener('change', function() {
          const borrowDate = new Date(this.value);
          const maxReturnDate = new Date(borrowDate);
          maxReturnDate.setDate(maxReturnDate.getDate() + 7);
          
          // Format maxReturnDate to YYYY-MM-DD
          const year = maxReturnDate.getFullYear();
          const month = String(maxReturnDate.getMonth() + 1).padStart(2, '0');
          const day = String(maxReturnDate.getDate()).padStart(2, '0');
          const formattedMaxDate = `${year}-${month}-${day}`;
          
          returnDateInput.setAttribute('max', formattedMaxDate);
          
          // If current return date is beyond max, reset it
          const currentReturnDate = new Date(returnDateInput.value);
          if (currentReturnDate > maxReturnDate) {
            returnDateInput.value = formattedMaxDate;
          }
        });
      }
    });
  </script>
</body>
</html>