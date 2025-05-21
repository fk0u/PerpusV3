<?php
session_start();
include '../includes/db.php';

// Pastikan pengguna sudah login dan memiliki role 'petugas'
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'petugas') {
    header("Location: ../login.php");
    exit();
}

$success = false;
$error_message = '';
$return_details = null;

// Proses verifikasi kode pengembalian
if (isset($_GET['unique_code']) || (isset($_POST['unique_code']) && !empty($_POST['unique_code']))) {
    $unique_code = isset($_GET['unique_code']) ? $_GET['unique_code'] : $_POST['unique_code'];
    
    // Cari permintaan pengembalian berdasarkan kode unik
    $stmt = $pdo->prepare("
        SELECT rr.id, rr.borrow_history_id, rr.unique_code, rr.request_date, 
               bh.user_id, bh.book_id, bh.borrow_date, bh.return_date, bh.status,
               u.full_name AS borrower_name, b.title AS book_title
        FROM return_requests rr
        JOIN borrow_history bh ON rr.borrow_history_id = bh.id
        JOIN users u ON bh.user_id = u.id
        JOIN books b ON bh.book_id = b.id
        WHERE rr.unique_code = ?
    ");
    $stmt->execute([$unique_code]);
    $return_details = $stmt->fetch();
    
    if (!$return_details) {
        $error_message = 'Kode pengembalian tidak valid atau tidak ditemukan.';
    } elseif ($return_details['return_date'] !== null) {
        $error_message = 'Buku ini sudah dikembalikan sebelumnya.';
    }
}

// Proses konfirmasi pengembalian
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_return']) && isset($_POST['borrow_id'])) {
    $borrow_id = $_POST['borrow_id'];
    $condition = isset($_POST['book_condition']) ? $_POST['book_condition'] : 'baik';
    $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
    
    // Update status peminjaman menjadi dikembalikan
    $stmt = $pdo->prepare("
        UPDATE borrow_history 
        SET return_date = NOW(), status = 'dikembalikan', return_condition = ?, return_notes = ? 
        WHERE id = ?
    ");
    
    if ($stmt->execute([$condition, $notes, $borrow_id])) {
        $success = true;
    } else {
        $error_message = 'Terjadi kesalahan saat memproses pengembalian buku.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Verifikasi Pengembalian - SevenLibrary v6</title>
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
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Pengembalian Berhasil!</h2>
                <p class="text-gray-600 dark:text-gray-300 mb-6">Buku telah berhasil dikembalikan ke perpustakaan.</p>
                
                <div class="mt-6 flex flex-col sm:flex-row gap-4 justify-center">
                  <a href="dashboard.php" class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-primary hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                    <i class="lni lni-home mr-2"></i> Kembali ke Dashboard
                  </a>
                  <a href="manage_peminjaman.php" class="inline-flex items-center justify-center px-6 py-3 border border-gray-300 dark:border-gray-600 text-base font-medium rounded-md shadow-sm text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                    <i class="lni lni-library mr-2"></i> Kelola Peminjaman
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
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Verifikasi Gagal</h2>
                <p class="text-gray-600 dark:text-gray-300 mb-6"><?= htmlspecialchars($error_message); ?></p>
                
                <div class="mt-6">
                  <a href="verifikasi_return.php" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-primary hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                    <i class="lni lni-reload mr-2"></i> Coba Lagi
                  </a>
                </div>
              </div>
            </div>
          </div>
        <?php elseif ($return_details): ?>
          <!-- Return Details Card -->
          <div class="dashboard-card bg-white dark:bg-gray-800 overflow-hidden" data-aos="fade-up">
            <div class="p-6 sm:p-10">
              <div class="flex items-center justify-center mb-6">
                <div class="w-16 h-16 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center text-blue-500 dark:text-blue-400 text-3xl mr-4">
                  <i class="lni lni-book-return"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Verifikasi Pengembalian Buku</h2>
              </div>
              
              <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Detail Peminjaman</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Judul Buku</p>
                    <p class="font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($return_details['book_title']); ?></p>
                  </div>
                  <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Peminjam</p>
                    <p class="font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($return_details['borrower_name']); ?></p>
                  </div>
                  <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Tanggal Pinjam</p>
                    <p class="font-medium text-gray-900 dark:text-white"><?= date('d/m/Y', strtotime($return_details['borrow_date'])); ?></p>
                  </div>
                  <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Tanggal Pengajuan Kembali</p>
                    <p class="font-medium text-gray-900 dark:text-white"><?= date('d/m/Y', strtotime($return_details['request_date'])); ?></p>
                  </div>
                </div>
              </div>
              
              <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Kode Pengembalian</h3>
                <div class="bg-white dark:bg-gray-800 rounded-lg border-2 border-dashed border-blue-300 dark:border-blue-700 p-4 mb-4">
                  <p class="text-xl font-mono font-bold text-blue-600 dark:text-blue-400 select-all"><?= htmlspecialchars($return_details['unique_code']); ?></p>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-300">Kode ini telah diverifikasi dan cocok dengan data peminjaman.</p>
              </div>
              
              <form method="POST" action="" class="space-y-6">
                <input type="hidden" name="borrow_id" value="<?= $return_details['borrow_history_id']; ?>">
                <input type="hidden" name="unique_code" value="<?= $return_details['unique_code']; ?>">
                
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
                  <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Kondisi Buku</h3>
                  
                  <div class="form-group">
                    <label for="book_condition" class="form-label">Kondisi Buku Saat Dikembalikan</label>
                    <select id="book_condition" name="book_condition" class="form-input">
                      <option value="baik">Baik (Tidak ada kerusakan)</option>
                      <option value="rusak_ringan">Rusak Ringan (Sedikit sobek/kotor)</option>
                      <option value="rusak_berat">Rusak Berat (Halaman hilang/sampul rusak)</option>
                      <option value="hilang">Hilang (Buku tidak dikembalikan)</option>
                    </select>
                  </div>
                  
                  <div class="form-group">
                    <label for="notes" class="form-label">Catatan Tambahan</label>
                    <textarea id="notes" name="notes" rows="3" class="form-input" placeholder="Tambahkan catatan tentang kondisi buku jika diperlukan..."></textarea>
                  </div>
                </div>
                
                <div class="flex justify-end space-x-4">
                  <a href="manage_peminjaman.php" class="px-6 py-3 border border-gray-300 dark:border-gray-600 text-base font-medium rounded-md shadow-sm text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                    Batal
                  </a>
                  <button type="submit" name="confirm_return" class="px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-primary hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                    Konfirmasi Pengembalian
                  </button>
                </div>
              </form>
            </div>
          </div>
        <?php else: ?>
          <!-- Verification Form Card -->
          <div class="dashboard-card bg-white dark:bg-gray-800 overflow-hidden" data-aos="fade-up">
            <div class="p-6 sm:p-10">
              <div class="flex items-center justify-center mb-6">
                <div class="w-16 h-16 bg-primary/20 rounded-full flex items-center justify-center text-primary text-3xl mr-4">
                  <i class="lni lni-book-return"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Verifikasi Pengembalian Buku</h2>
              </div>
              
              <p class="text-center text-gray-600 dark:text-gray-300 mb-8">
                Masukkan kode pengembalian yang diberikan oleh siswa untuk memverifikasi pengembalian buku.
              </p>
              
              <form method="POST" action="" class="max-w-md mx-auto">
                <div class="form-group">
                  <label for="unique_code" class="form-label">Kode Pengembalian</label>
                  <input type="text" id="unique_code" name="unique_code" class="form-input text-center font-mono text-lg" placeholder="Masukkan kode pengembalian" required>
                </div>
                
                <div class="mt-8 flex justify-center">
                  <button type="submit" class="px-8 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-primary hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                    Verifikasi Kode
                  </button>
                </div>
              </form>
              
              <div class="mt-8 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-6">
                <div class="flex items-start">
                  <div class="flex-shrink-0">
                    <i class="lni lni-information text-yellow-500 dark:text-yellow-400 text-xl"></i>
                  </div>
                  <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-300">Informasi</h3>
                    <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-200">
                      <p>Kode pengembalian adalah kode unik yang diberikan kepada siswa saat mereka mengajukan pengembalian buku. Kode ini digunakan untuk memverifikasi bahwa buku yang dikembalikan adalah buku yang benar.</p>
                    </div>
                  </div>
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
      text: 'Buku telah berhasil dikembalikan.',
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
    
    // Book condition change handler
    document.addEventListener('DOMContentLoaded', function() {
      const conditionSelect = document.getElementById('book_condition');
      const notesTextarea = document.getElementById('notes');
      
      if (conditionSelect && notesTextarea) {
        conditionSelect.addEventListener('change', function() {
          if (this.value === 'rusak_ringan' || this.value === 'rusak_berat' || this.value === 'hilang') {
            notesTextarea.setAttribute('required', 'required');
            notesTextarea.classList.add('border-yellow-500');
            
            if (this.value === 'rusak_ringan') {
              notesTextarea.placeholder = 'Jelaskan kerusakan ringan pada buku...';
            } else if (this.value === 'rusak_berat') {
              notesTextarea.placeholder = 'Jelaskan kerusakan berat pada buku...';
            } else if (this.value === 'hilang') {
              notesTextarea.placeholder = 'Berikan keterangan tentang buku yang hilang...';
            }
          } else {
            notesTextarea.removeAttribute('required');
            notesTextarea.classList.remove('border-yellow-500');
            notesTextarea.placeholder = 'Tambahkan catatan tentang kondisi buku jika diperlukan...';
          }
        });
      }
    });
  </script>
</body>
</html>