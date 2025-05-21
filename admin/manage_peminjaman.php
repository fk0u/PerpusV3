<?php
session_start();
include '../includes/db.php';

// Pastikan pengguna sudah login dan memiliki role 'petugas'
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Ambil data peminjaman dengan informasi pengembalian dan kondisi buku
$stmt = $pdo->query("
    SELECT bh.id, u.full_name AS borrower, b.title AS book_title, bh.borrow_date, bh.return_date, 
           bh.status, bh.return_condition, bh.return_notes, rr.unique_code AS return_code, rr.request_date
    FROM borrow_history bh
    JOIN users u ON bh.user_id = u.id
    JOIN books b ON bh.book_id = b.id
    LEFT JOIN return_requests rr ON bh.id = rr.borrow_history_id
    ORDER BY 
        CASE 
            WHEN rr.request_date IS NOT NULL AND bh.return_date IS NULL THEN 1
            WHEN bh.status = 'dipinjam' THEN 2
            ELSE 3
        END,
        bh.borrow_date DESC
");
$loans = $stmt->fetchAll();

// Proses pengembalian buku jika tombol "Telah Dikembalikan" ditekan
if (isset($_GET['return_book'])) {
    $borrow_history_id = $_GET['return_book'];
    
    // Get book_id to update stock
    $stmt = $pdo->prepare("SELECT book_id FROM borrow_history WHERE id = ?");
    $stmt->execute([$borrow_history_id]);
    $book_data = $stmt->fetch();
    
    if ($book_data) {
        // Begin transaction
        $pdo->beginTransaction();
        
        try {
            // Tandai buku sebagai sudah dikembalikan
            $stmt = $pdo->prepare("UPDATE borrow_history SET return_date = NOW(), status = 'dikembalikan', return_condition = 'baik' WHERE id = ? AND return_date IS NULL");
            $stmt->execute([$borrow_history_id]);
            
            // Update book stock (increment by 1)
            $stmt = $pdo->prepare("UPDATE books SET stock = stock + 1 WHERE id = ?");
            $stmt->execute([$book_data['book_id']]);
            
            // Commit transaction
            $pdo->commit();
            $success_message = 'Buku berhasil dikembalikan.';
        } catch (Exception $e) {
            // Rollback transaction on error
            $pdo->rollBack();
            $error_message = 'Terjadi kesalahan saat mengembalikan buku: ' . $e->getMessage();
        }
    } else {
        $error_message = 'Data peminjaman tidak ditemukan.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kelola Peminjaman - SevenLibrary v6</title>
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
    <main class="flex-grow py-6">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
          <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white" data-aos="fade-up">Kelola Peminjaman</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400" data-aos="fade-up" data-aos-delay="100">
              Kelola semua peminjaman dan pengembalian buku perpustakaan
            </p>
          </div>
          <div class="mt-4 md:mt-0" data-aos="fade-up" data-aos-delay="200">
            <a href="verifikasi_return.php" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
              <i class="lni lni-checkmark mr-2"></i>
              Verifikasi Pengembalian
            </a>
          </div>
        </div>

        <!-- Filters and Search -->
        <div class="dashboard-card bg-white dark:bg-gray-800 p-4 mb-6" data-aos="fade-up" data-aos-delay="300">
          <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex flex-col sm:flex-row gap-4">
              <div>
                <label for="status-filter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Filter Status</label>
                <select id="status-filter" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary focus:ring-primary dark:bg-gray-700 dark:text-white sm:text-sm">
                  <option value="all">Semua Status</option>
                  <option value="dipinjam">Dipinjam</option>
                  <option value="dikembalikan">Dikembalikan</option>
                  <option value="pending_return">Menunggu Pengembalian</option>
                </select>
              </div>
              <div>
                <label for="condition-filter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Filter Kondisi</label>
                <select id="condition-filter" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary focus:ring-primary dark:bg-gray-700 dark:text-white sm:text-sm">
                  <option value="all">Semua Kondisi</option>
                  <option value="baik">Baik</option>
                  <option value="rusak_ringan">Rusak Ringan</option>
                  <option value="rusak_berat">Rusak Berat</option>
                  <option value="hilang">Hilang</option>
                </select>
              </div>
            </div>
            <div class="w-full md:w-64">
              <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Cari</label>
              <div class="relative rounded-md shadow-sm">
                <input type="text" id="search" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary focus:ring-primary dark:bg-gray-700 dark:text-white sm:text-sm pl-10" placeholder="Cari peminjam atau buku...">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <i class="lni lni-search text-gray-400"></i>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Loans Table -->
        <div class="dashboard-card bg-white dark:bg-gray-800 overflow-hidden" data-aos="fade-up" data-aos-delay="400">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
              <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ID</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nama Peminjam</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Judul Buku</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tanggal Pinjam</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tanggal Kembali</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Kondisi</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Kode Pengembalian</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                </tr>
              </thead>
              <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700" id="loans-table-body">
                <?php if (empty($loans)): ?>
                  <tr>
                    <td colspan="9" class="px-6 py-12 text-center">
                      <div class="flex flex-col items-center">
                        <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center text-gray-400 dark:text-gray-500 mb-4">
                          <i class="lni lni-book text-2xl"></i>
                        </div>
                        <p class="text-gray-500 dark:text-gray-400 text-lg font-medium">Belum ada data peminjaman</p>
                        <p class="text-gray-400 dark:text-gray-500 text-sm mt-1">Data peminjaman akan muncul di sini</p>
                      </div>
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($loans as $loan): ?>
                    <?php 
                      $status_class = '';
                      $status_text = '';
                      
                      if ($loan['return_date']) {
                        $status_class = 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400';
                        $status_text = 'Dikembalikan';
                      } elseif ($loan['return_code']) {
                        $status_class = 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400';
                        $status_text = 'Menunggu Pengembalian';
                      } else {
                        $status_class = 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400';
                        $status_text = 'Dipinjam';
                      }
                      
                      $condition_class = '';
                      $condition_text = '';
                      
                      if ($loan['return_condition'] === 'baik') {
                        $condition_class = 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400';
                        $condition_text = 'Baik';
                      } elseif ($loan['return_condition'] === 'rusak_ringan') {
                        $condition_class = 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400';
                        $condition_text = 'Rusak Ringan';
                      } elseif ($loan['return_condition'] === 'rusak_berat') {
                        $condition_class = 'bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-400';
                        $condition_text = 'Rusak Berat';
                      } elseif ($loan['return_condition'] === 'hilang') {
                        $condition_class = 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400';
                        $condition_text = 'Hilang';
                      } else {
                        $condition_class = 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-400';
                        $condition_text = 'Belum Dikembalikan';
                      }
                    ?>
                    <tr class="loan-row" 
                        data-status="<?= $loan['status'] ?? 'dipinjam'; ?>" 
                        data-pending="<?= $loan['return_code'] && !$loan['return_date'] ? 'true' : 'false'; ?>"
                        data-condition="<?= $loan['return_condition'] ?? ''; ?>">
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"><?= $loan['id']; ?></td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"><?= htmlspecialchars($loan['borrower']); ?></td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"><?= htmlspecialchars($loan['book_title']); ?></td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400"><?= date('d/m/Y', strtotime($loan['borrow_date'])); ?></td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                        <?= $loan['return_date'] ? date('d/m/Y', strtotime($loan['return_date'])) : '<span class="text-yellow-500 dark:text-yellow-400">Belum Dikembalikan</span>'; ?>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $status_class ?>">
                          <?= $status_text ?>
                        </span>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $condition_class ?>">
                          <?= $condition_text ?>
                        </span>
                        <?php if ($loan['return_notes']): ?>
                          <button class="ml-1 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 notes-tooltip" data-notes="<?= htmlspecialchars($loan['return_notes']); ?>">
                            <i class="lni lni-information"></i>
                          </button>
                        <?php endif; ?>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                        <?php if ($loan['return_code']): ?>
                          <span class="font-mono text-xs bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400 px-2 py-1 rounded">
                            <?= htmlspecialchars($loan['return_code']); ?>
                          </span>
                        <?php else: ?>
                          <span class="text-gray-400 dark:text-gray-500">Tidak Ada</span>
                        <?php endif; ?>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <?php if (!$loan['return_date'] && !$loan['return_code']): ?>
                          <a href="?return_book=<?= $loan['id']; ?>" class="text-yellow-600 dark:text-yellow-500 hover:text-yellow-900 dark:hover:text-yellow-400 mr-3">
                            <span class="bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400 px-2 py-1 rounded text-xs">
                              Telah Dikembalikan
                            </span>
                          </a>
                        <?php elseif ($loan['return_code'] && !$loan['return_date']): ?>
                          <a href="verifikasi_return.php?unique_code=<?= $loan['return_code']; ?>" class="text-blue-600 dark:text-blue-500 hover:text-blue-900 dark:hover:text-blue-400">
                            <span class="bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400 px-2 py-1 rounded text-xs">
                              Verifikasi Kode
                            </span>
                          </a>
                        <?php else: ?>
                          <span class="text-green-600 dark:text-green-500">
                            <span class="bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 px-2 py-1 rounded text-xs">
                              Terverifikasi
                            </span>
                          </span>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
          
          <!-- Pagination -->
          <div class="bg-white dark:bg-gray-800 px-4 py-3 flex items-center justify-between border-t border-gray-200 dark:border-gray-700 sm:px-6">
            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
              <div>
                <p class="text-sm text-gray-700 dark:text-gray-300">
                  Menampilkan <span class="font-medium"><?= count($loans) ?></span> dari <span class="font-medium"><?= count($loans) ?></span> data
                </p>
              </div>
              <div>
                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                  <a href="#" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-500 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600">
                    <span class="sr-only">Previous</span>
                    <i class="lni lni-chevron-left"></i>
                  </a>
                  <a href="#" class="relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600">1</a>
                  <a href="#" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-500 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600">
                    <span class="sr-only">Next</span>
                    <i class="lni lni-chevron-right"></i>
                  </a>
                </nav>
              </div>
            </div>
          </div>
        </div>
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
      
      // Initialize tooltips for notes
      const notesTooltips = document.querySelectorAll('.notes-tooltip');
      notesTooltips.forEach(tooltip => {
        tooltip.addEventListener('mouseenter', function() {
          const notes = this.getAttribute('data-notes');
          Swal.fire({
            title: 'Catatan',
            text: notes,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000
          });
        });
      });
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
    
    // Filter functionality
    document.addEventListener('DOMContentLoaded', function() {
      const statusFilter = document.getElementById('status-filter');
      const conditionFilter = document.getElementById('condition-filter');
      const searchInput = document.getElementById('search');
      const loanRows = document.querySelectorAll('.loan-row');
      
      function filterTable() {
        const statusValue = statusFilter.value;
        const conditionValue = conditionFilter.value;
        const searchValue = searchInput.value.toLowerCase();
        
        loanRows.forEach(row => {
          const status = row.getAttribute('data-status');
          const isPending = row.getAttribute('data-pending') === 'true';
          const condition = row.getAttribute('data-condition');
          const borrowerName = row.children[1].textContent.toLowerCase();
          const bookTitle = row.children[2].textContent.toLowerCase();
          
          // Status filter
          let showByStatus = true;
          if (statusValue === 'dipinjam') {
            showByStatus = status === 'dipinjam' && !isPending;
          } else if (statusValue === 'dikembalikan') {
            showByStatus = status === 'dikembalikan';
          } else if (statusValue === 'pending_return') {
            showByStatus = isPending;
          }
          
          // Condition filter
          let showByCondition = true;
          if (conditionValue !== 'all') {
            showByCondition = condition === conditionValue;
          }
          
          // Search filter
          let showBySearch = searchValue === '' || 
                            borrowerName.includes(searchValue) || 
                            bookTitle.includes(searchValue);
          
          // Show/hide row
          if (showByStatus && showByCondition && showBySearch) {
            row.style.display = '';
          } else {
            row.style.display = 'none';
          }
        });
      }
      
      statusFilter.addEventListener('change', filterTable);
      conditionFilter.addEventListener('change', filterTable);
      searchInput.addEventListener('input', filterTable);
    });
    
    <?php if (isset($success_message)): ?>
    // Show success notification
    Swal.fire({
      icon: 'success',
      title: 'Berhasil!',
      text: '<?= $success_message ?>',
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 3000
    });
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
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