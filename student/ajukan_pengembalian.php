<?php
session_start();
include '../includes/db.php';

// Pastikan pengguna sudah login dan memiliki role 'student'
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$success = false;
$error_message = '';
$unique_code = '';

if (isset($_POST['borrow_id'])) {
    $borrow_id = $_POST['borrow_id'];
    $user_id = $_SESSION['user_id'];
    
    // Cek apakah peminjaman ada dan milik user yang login
    $stmt = $pdo->prepare("SELECT * FROM borrow_history WHERE id = ? AND user_id = ? AND status = 'dipinjam'");
    $stmt->execute([$borrow_id, $user_id]);
    $borrow = $stmt->fetch();
    
    if (!$borrow) {
        $error_message = 'Data peminjaman tidak ditemukan atau buku sudah dikembalikan.';
    } else {
        // Cek apakah sudah ada pengajuan pengembalian sebelumnya
        $stmt = $pdo->prepare("SELECT * FROM return_requests WHERE borrow_history_id = ?");
        $stmt->execute([$borrow_id]);
        $existing_request = $stmt->fetch();
        
        if ($existing_request) {
            $unique_code = $existing_request['unique_code'];
            $success = true;
        } else {
            // Generate unique code for return request
            $unique_code = uniqid('return_', true); // Generate unique ID
            
            // Insert return request into database
            $stmt = $pdo->prepare("INSERT INTO return_requests (borrow_history_id, user_id, unique_code, request_date) VALUES (?, ?, ?, NOW())");
            
            if ($stmt->execute([$borrow_id, $user_id, $unique_code])) {
                $success = true;
            } else {
                $error_message = 'Terjadi kesalahan saat mengajukan pengembalian. Silakan coba lagi.';
            }
        }
    }
} else {
    $error_message = 'ID peminjaman tidak ditemukan.';
}

// Get book details for display
if (isset($borrow_id)) {
    $stmt = $pdo->prepare("SELECT b.title, b.author, bh.borrow_date, bh.return_date 
                          FROM borrow_history bh 
                          JOIN books b ON bh.book_id = b.id 
                          WHERE bh.id = ?");
    $stmt->execute([$borrow_id]);
    $book_details = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pengajuan Pengembalian - SevenLibrary v6</title>
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
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Pengajuan Pengembalian Berhasil!</h2>
                <p class="text-gray-600 dark:text-gray-300 mb-6">Permintaan pengembalian buku Anda telah berhasil diajukan.</p>
                
                <?php if (isset($book_details)): ?>
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6 mb-6 text-left">
                  <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Detail Buku</h3>
                  <div class="space-y-3">
                    <div class="flex justify-between">
                      <span class="text-gray-500 dark:text-gray-400">Judul:</span>
                      <span class="font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($book_details['title'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-gray-500 dark:text-gray-400">Penulis:</span>
                      <span class="font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($book_details['author'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-gray-500 dark:text-gray-400">Tanggal Pinjam:</span>
                      <span class="font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($book_details['borrow_date'] ?? 'N/A'); ?></span>
                    </div>
                  </div>
                </div>
                <?php endif; ?>
                
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-6 mb-6">
                  <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Kode Unik Pengembalian</h3>
                  <div class="bg-white dark:bg-gray-800 rounded-lg border-2 border-dashed border-blue-300 dark:border-blue-700 p-4 mb-4">
                    <p class="text-xl font-mono font-bold text-blue-600 dark:text-blue-400 select-all"><?= htmlspecialchars($unique_code); ?></p>
                  </div>
                  <p class="text-sm text-gray-600 dark:text-gray-300">Berikan kode unik ini kepada petugas perpustakaan untuk menyelesaikan proses pengembalian buku.</p>
                </div>
                
                <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-6 mb-6">
                  <div class="flex items-start">
                    <div class="flex-shrink-0">
                      <i class="lni lni-warning text-yellow-500 dark:text-yellow-400 text-xl"></i>
                    </div>
                    <div class="ml-3">
                      <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-300">Penting!</h3>
                      <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-200">
                        <p>Pastikan Anda memberikan kode unik kepada petugas di perpustakaan untuk menyelesaikan pengembalian buku.</p>
                      </div>
                    </div>
                  </div>
                </div>
                
                <div class="mt-6">
                  <a href="dashboard.php" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-primary hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                    <i class="lni lni-home mr-2"></i> Kembali ke Dashboard
                  </a>
                </div>
              </div>
            </div>
          </div>
        <?php else: ?>
          <!-- Error Card -->
          <div class="dashboard-card bg-white dark:bg-gray-800 overflow-hidden" data-aos="fade-up">
            <div class="p-6 sm:p-10">
              <div class="text-center">
                <div class="w-20 h-20 mx-auto bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center text-red-500 dark:text-red-400 mb-6">
                  <i class="lni lni-close text-4xl"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Pengajuan Gagal</h2>
                <p class="text-gray-600 dark:text-gray-300 mb-6"><?= htmlspecialchars($error_message); ?></p>
                
                <div class="mt-6">
                  <a href="dashboard.php" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-primary hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                    <i class="lni lni-home mr-2"></i> Kembali ke Dashboard
                  </a>
                </div>
              </div>
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
      text: 'Pengajuan pengembalian buku berhasil diproses.',
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
  </script>
</body>
</html>