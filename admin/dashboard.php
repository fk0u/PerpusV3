<?php
session_start();
include '../includes/db.php';

// Pastikan pengguna sudah login dan memiliki role 'admin'
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Statistik
$stmt = $pdo->query("SELECT COUNT(*) AS total_books FROM books");
$total_books = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) AS total_students FROM users WHERE role = 'student'");
$total_students = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) AS total_loans FROM borrow_history");
$total_loans = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) AS total_categories FROM category");
$total_categories = $stmt->fetchColumn();

// Data untuk Chart.js - Peminjaman per bulan
$stmt = $pdo->query("
    SELECT MONTH(borrow_date) AS month, COUNT(*) AS total 
    FROM borrow_history
    GROUP BY month
");
$monthly_loans = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Format data untuk Chart.js
$months = [];
$totals = [];
foreach ($monthly_loans as $data) {
    $months[] = date('F', mktime(0, 0, 0, $data['month'], 1)); // Konversi bulan ke nama
    $totals[] = $data['total'];
}

// Data untuk Chart.js - Buku per kategori
$stmt = $pdo->query("
    SELECT c.name, COUNT(b.id) AS total
    FROM category c
    LEFT JOIN books b ON c.id = b.category_id
    GROUP BY c.id
    ORDER BY total DESC
    LIMIT 5
");
$category_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Format data untuk Chart.js
$category_names = [];
$category_totals = [];
foreach ($category_stats as $data) {
    $category_names[] = $data['name'];
    $category_totals[] = $data['total'];
}

// Get recent activities
$stmt = $pdo->query("
    SELECT bh.id, u.full_name AS borrower, b.title AS book_title, bh.borrow_date, bh.status
    FROM borrow_history bh
    JOIN users u ON bh.user_id = u.id
    JOIN books b ON bh.book_id = b.id
    ORDER BY bh.borrow_date DESC
    LIMIT 5
");
$recent_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get top borrowers
$stmt = $pdo->query("
    SELECT u.full_name, COUNT(bh.id) AS total_borrows
    FROM users u
    JOIN borrow_history bh ON u.id = bh.user_id
    GROUP BY u.id
    ORDER BY total_borrows DESC
    LIMIT 5
");
$top_borrowers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get most borrowed books
$stmt = $pdo->query("
    SELECT b.title, COUNT(bh.id) AS borrow_count
    FROM books b
    JOIN borrow_history bh ON b.id = bh.book_id
    GROUP BY b.id
    ORDER BY borrow_count DESC
    LIMIT 5
");
$popular_books = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Admin - SevenLibrary v6</title>
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
  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
    
    /* Menu Card */
    .menu-card {
      transition: all 0.3s ease;
    }
    
    .menu-card:hover {
      transform: translateY(-5px);
    }
    
    .menu-card:hover .menu-icon {
      transform: scale(1.1);
    }
    
    .menu-icon {
      transition: all 0.3s ease;
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
            <div class="flex-shrink-0 flex items-center">
              <img class="h-8 w-auto" src="https://images.rawpixel.com/image_png_800/czNmcy1wcml2YXRlL3Jhd3BpeGVsX2ltYWdlcy93ZWJzaXRlX2NvbnRlbnQvbHIvam9iNjgyLTI0NS1wLnBuZw.png" alt="Logo">
              <span class="ml-2 text-xl font-bold text-primary">SevenLibrary</span>
            </div>
          </div>
          <div class="flex items-center">
            <div class="dropdown relative ml-3">
              <button class="flex items-center text-sm rounded-full focus:outline-none" id="user-menu-button">
                <span class="sr-only">Open user menu</span>
                <div class="h-8 w-8 rounded-full bg-red-500 text-white flex items-center justify-center">
                  <span class="text-sm font-medium">A</span>
                </div>
                <span class="ml-2 hidden md:block">Admin</span>
                <svg class="ml-1 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
              </button>
              <div class="dropdown-menu origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5 hidden" role="menu" aria-orientation="vertical" aria-labelledby="user-menu-button" tabindex="-1">
                <a href="#" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700" role="menuitem">Profil</a>
                <a href="#" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700" role="menuitem">Pengaturan</a>
                <a href="../logout.php" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700" role="menuitem">Logout</a>
              </div>
            </div>
            <button id="theme-toggle" class="ml-4 w-10 h-10 rounded-full flex items-center justify-center text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none">
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
        <!-- Welcome Section -->
        <div class="bg-gradient-to-r from-primary to-secondary rounded-xl p-6 text-white mb-6" data-aos="fade-up">
          <div class="flex flex-col md:flex-row justify-between items-center">
            <div>
              <h1 class="text-2xl font-bold">Selamat Datang, Admin!</h1>
              <p class="mt-2">Kelola perpustakaan dengan mudah dan efisien.</p>
            </div>
            <div class="mt-4 md:mt-0">
              <p class="text-sm opacity-90">
                <i class="lni lni-calendar mr-2"></i><?= date('l, d F Y'); ?>
              </p>
            </div>
          </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
          <div class="dashboard-card bg-white dark:bg-gray-800 p-6" data-aos="fade-up" data-aos-delay="100">
            <div class="flex items-center">
              <div class="flex-shrink-0 w-12 h-12 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-500 dark:text-blue-400">
                <i class="lni lni-book text-xl"></i>
              </div>
              <div class="ml-4">
                <h2 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Buku</h2>
                <p class="text-3xl font-bold text-gray-900 dark:text-white"><?= $total_books; ?></p>
              </div>
            </div>
            <div class="mt-4">
              <a href="../petugas/manage_books.php" class="text-sm text-primary dark:text-blue-400 hover:underline flex items-center">
                Kelola Buku
                <i class="lni lni-arrow-right ml-1"></i>
              </a>
            </div>
          </div>
          
          <div class="dashboard-card bg-white dark:bg-gray-800 p-6" data-aos="fade-up" data-aos-delay="200">
            <div class="flex items-center">
              <div class="flex-shrink-0 w-12 h-12 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center text-green-500 dark:text-green-400">
                <i class="lni lni-users text-xl"></i>
              </div>
              <div class="ml-4">
                <h2 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Siswa</h2>
                <p class="text-3xl font-bold text-gray-900 dark:text-white"><?= $total_students; ?></p>
              </div>
            </div>
            <div class="mt-4">
              <a href="manage_users.php" class="text-sm text-primary dark:text-blue-400 hover:underline flex items-center">
                Kelola Siswa
                <i class="lni lni-arrow-right ml-1"></i>
              </a>
            </div>
          </div>
          
          <div class="dashboard-card bg-white dark:bg-gray-800 p-6" data-aos="fade-up" data-aos-delay="300">
            <div class="flex items-center">
              <div class="flex-shrink-0 w-12 h-12 rounded-full bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center text-yellow-500 dark:text-yellow-400">
                <i class="lni lni-handshake text-xl"></i>
              </div>
              <div class="ml-4">
                <h2 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Peminjaman</h2>
                <p class="text-3xl font-bold text-gray-900 dark:text-white"><?= $total_loans; ?></p>
              </div>
            </div>
            <div class="mt-4">
              <a href="../petugas/manage_peminjaman.php" class="text-sm text-primary dark:text-blue-400 hover:underline flex items-center">
                Kelola Peminjaman
                <i class="lni lni-arrow-right ml-1"></i>
              </a>
            </div>
          </div>
          
          <div class="dashboard-card bg-white dark:bg-gray-800 p-6" data-aos="fade-up" data-aos-delay="400">
            <div class="flex items-center">
              <div class="flex-shrink-0 w-12 h-12 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center text-purple-500 dark:text-purple-400">
                <i class="lni lni-list text-xl"></i>
              </div>
              <div class="ml-4">
                <h2 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Kategori</h2>
                <p class="text-3xl font-bold text-gray-900 dark:text-white"><?= $total_categories; ?></p>
              </div>
            </div>
            <div class="mt-4">
              <a href="../petugas/manage_category.php" class="text-sm text-primary dark:text-blue-400 hover:underline flex items-center">
                Kelola Kategori
                <i class="lni lni-arrow-right ml-1"></i>
              </a>
            </div>
          </div>
        </div>

        <!-- Main Content Area -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <!-- Charts -->
          <div class="lg:col-span-2">
            <div class="dashboard-card bg-white dark:bg-gray-800 p-6 mb-6" data-aos="fade-up" data-aos-delay="500">
              <h3 class="font-medium text-gray-900 dark:text-white mb-4">Statistik Peminjaman Buku per Bulan</h3>
              <div class="h-64">
                <canvas id="borrowChart"></canvas>
              </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
              <div class="dashboard-card bg-white dark:bg-gray-800 p-6" data-aos="fade-up" data-aos-delay="600">
                <h3 class="font-medium text-gray-900 dark:text-white mb-4">Kategori Buku Terpopuler</h3>
                <div class="h-64">
                  <canvas id="categoryChart"></canvas>
                </div>
              </div>
              
              <div class="dashboard-card bg-white dark:bg-gray-800 p-6" data-aos="fade-up" data-aos-delay="700">
                <h3 class="font-medium text-gray-900 dark:text-white mb-4">Buku Terpopuler</h3>
                <?php if (empty($popular_books)): ?>
                  <div class="flex flex-col items-center justify-center h-64">
                    <div class="w-12 h-12 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center text-gray-400 dark:text-gray-500 mb-2">
                      <i class="lni lni-book text-xl"></i>
                    </div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm">Belum ada data</p>
                  </div>
                <?php else: ?>
                  <div class="space-y-4">
                    <?php foreach ($popular_books as $index => $book): ?>
                      <div class="flex items-center">
                        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-500 dark:text-blue-400">
                          <?= $index + 1; ?>
                        </div>
                        <div class="ml-3 flex-1">
                          <div class="flex justify-between items-center">
                            <p class="text-sm font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($book['title']); ?></p>
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400">
                              <?= $book['borrow_count']; ?>x
                            </span>
                          </div>
                          <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mt-2">
                            <div class="bg-blue-500 h-2 rounded-full" style="width: <?= min(100, ($book['borrow_count'] / $popular_books[0]['borrow_count']) * 100); ?>%"></div>
                          </div>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </div>
            </div>
            
            <!-- Menu Cards -->
            <h2 class="text-xl font-bold mb-4 text-gray-900 dark:text-white" data-aos="fade-up" data-aos-delay="800">Menu Utama</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
              <a href="manage_users.php" class="menu-card dashboard-card bg-white dark:bg-gray-800 p-6 flex items-center" data-aos="fade-up" data-aos-delay="850">
                <div class="menu-icon w-12 h-12 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center text-red-500 dark:text-red-400">
                  <i class="lni lni-users text-xl"></i>
                </div>
                <div class="ml-4">
                  <h3 class="font-medium text-gray-900 dark:text-white">Kelola Pengguna</h3>
                  <p class="text-sm text-gray-500 dark:text-gray-400">Atur akun admin, petugas, dan siswa</p>
                </div>
              </a>
              
              <a href="../petugas/manage_books.php" class="menu-card dashboard-card bg-white dark:bg-gray-800 p-6 flex items-center" data-aos="fade-up" data-aos-delay="900">
                <div class="menu-icon w-12 h-12 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-500 dark:text-blue-400">
                  <i class="lni lni-book-open text-xl"></i>
                </div>
                <div class="ml-4">
                  <h3 class="font-medium text-gray-900 dark:text-white">Kelola Buku</h3>
                  <p class="text-sm text-gray-500 dark:text-gray-400">Tambah, edit, dan hapus data buku</p>
                </div>
              </a>
              
              <a href="../petugas/manage_category.php" class="menu-card dashboard-card bg-white dark:bg-gray-800 p-6 flex items-center" data-aos="fade-up" data-aos-delay="950">
                <div class="menu-icon w-12 h-12 rounded-full bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center text-yellow-500 dark:text-yellow-400">
                  <i class="lni lni-list text-xl"></i>
                </div>
                <div class="ml-4">
                  <h3 class="font-medium text-gray-900 dark:text-white">Kelola Kategori</h3>
                  <p class="text-sm text-gray-500 dark:text-gray-400">Atur kategori buku perpustakaan</p>
                </div>
              </a>
              
              <a href="../petugas/manage_peminjaman.php" class="menu-card dashboard-card bg-white dark:bg-gray-800 p-6 flex items-center" data-aos="fade-up" data-aos-delay="1000">
                <div class="menu-icon w-12 h-12 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center text-purple-500 dark:text-purple-400">
                  <i class="lni lni-handshake text-xl"></i>
                </div>
                <div class="ml-4">
                  <h3 class="font-medium text-gray-900 dark:text-white">Kelola Peminjaman</h3>
                  <p class="text-sm text-gray-500 dark:text-gray-400">Atur peminjaman dan pengembalian buku</p>
                </div>
              </a>
            </div>
          </div>
          
          <!-- Sidebar Content -->
          <div class="lg:col-span-1">
            <!-- Top Borrowers -->
            <div class="dashboard-card bg-white dark:bg-gray-800 p-6 mb-6" data-aos="fade-up" data-aos-delay="1050">
              <h3 class="font-medium text-gray-900 dark:text-white mb-4">Peminjam Teraktif</h3>
              <?php if (empty($top_borrowers)): ?>
                <div class="text-center py-8">
                  <div class="w-16 h-16 mx-auto bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center text-gray-500 dark:text-gray-400 mb-4">
                    <i class="lni lni-users text-2xl"></i>
                  </div>
                  <h3 class="text-gray-500 dark:text-gray-400">Belum ada data peminjam</h3>
                </div>
              <?php else: ?>
                <div class="space-y-4">
                  <?php foreach ($top_borrowers as $index => $borrower): ?>
                    <div class="flex items-center">
                      <div class="flex-shrink-0 w-10 h-10 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center text-green-500 dark:text-green-400">
                        <?= $index + 1; ?>
                      </div>
                      <div class="ml-3 flex-1">
                        <div class="flex justify-between items-center">
                          <p class="text-sm font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($borrower['full_name']); ?></p>
                          <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400">
                            <?= $borrower['total_borrows']; ?> buku
                          </span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mt-2">
                          <div class="bg-green-500 h-2 rounded-full" style="width: <?= min(100, ($borrower['total_borrows'] / $top_borrowers[0]['total_borrows']) * 100); ?>%"></div>
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
            
            <!-- Recent Activities -->
            <div class="dashboard-card bg-white dark:bg-gray-800 p-6" data-aos="fade-up" data-aos-delay="1100">
              <h3 class="font-medium text-gray-900 dark:text-white mb-4">Aktivitas Terbaru</h3>
              <?php if (empty($recent_activities)): ?>
                <div class="text-center py-8">
                  <div class="w-16 h-16 mx-auto bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center text-gray-500 dark:text-gray-400 mb-4">
                    <i class="lni lni-calendar text-2xl"></i>
                  </div>
                  <h3 class="text-gray-500 dark:text-gray-400">Belum ada aktivitas</h3>
                </div>
              <?php else: ?>
                <div class="space-y-4">
                  <?php foreach ($recent_activities as $activity): ?>
                    <div class="flex items-start">
                      <div class="flex-shrink-0 w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-500 dark:text-blue-400">
                        <i class="lni lni-book text-lg"></i>
                      </div>
                      <div class="ml-3">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                          <?= htmlspecialchars($activity['borrower']); ?> 
                          <?= $activity['status'] === 'dipinjam' ? 'meminjam' : 'mengembalikan'; ?> 
                          buku
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                          "<?= htmlspecialchars($activity['book_title']); ?>"
                        </p>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                          <?= date('d M Y, H:i', strtotime($activity['borrow_date'])); ?>
                        </p>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
                <div class="mt-6 text-center">
                  <a href="../petugas/manage_peminjaman.php" class="text-sm text-primary dark:text-blue-400 hover:underline">
                    Lihat semua aktivitas
                  </a>
                </div>
              <?php endif; ?>
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
      
      // User dropdown
      const userMenuButton = document.getElementById('user-menu-button');
      const dropdownMenu = document.querySelector('.dropdown-menu');
      
      userMenuButton.addEventListener('click', function() {
        dropdownMenu.classList.toggle('hidden');
      });
      
      // Close dropdown when clicking outside
      document.addEventListener('click', function(event) {
        if (!userMenuButton.contains(event.target) && !dropdownMenu.contains(event.target)) {
          dropdownMenu.classList.add('hidden');
        }
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
      
      // Update chart colors
      updateChartColors();
    });
    
    // Chart.js - Borrow Chart
    const borrowCtx = document.getElementById('borrowChart').getContext('2d');
    const borrowChart = new Chart(borrowCtx, {
      type: 'bar',
      data: {
        labels: <?= json_encode($months); ?>,
        datasets: [{
          label: 'Jumlah Peminjaman',
          data: <?= json_encode($totals); ?>,
          backgroundColor: 'rgba(10, 102, 244, 0.2)',
          borderColor: 'rgba(10, 102, 244, 1)',
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: true,
            labels: {
              color: document.documentElement.classList.contains('dark') ? '#f8fafc' : '#1e293b'
            }
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              precision: 0,
              color: document.documentElement.classList.contains('dark') ? '#94a3b8' : '#64748b'
            },
            grid: {
              color: document.documentElement.classList.contains('dark') ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.05)'
            }
          },
          x: {
            ticks: {
              color: document.documentElement.classList.contains('dark') ? '#94a3b8' : '#64748b'
            },
            grid: {
              color: document.documentElement.classList.contains('dark') ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.05)'
            }
          }
        }
      }
    });
    
    // Chart.js - Category Chart
    const categoryCtx = document.getElementById('categoryChart').getContext('2d');
    const categoryChart = new Chart(categoryCtx, {
      type: 'pie',
      data: {
        labels: <?= json_encode($category_names); ?>,
        datasets: [{
          data: <?= json_encode($category_totals); ?>,
          backgroundColor: [
            'rgba(10, 102, 244, 0.6)',
            'rgba(16, 185, 129, 0.6)',
            'rgba(245, 158, 11, 0.6)',
            'rgba(239, 68, 68, 0.6)',
            'rgba(139, 92, 246, 0.6)'
          ],
          borderColor: [
            'rgba(10, 102, 244, 1)',
            'rgba(16, 185, 129, 1)',
            'rgba(245, 158, 11, 1)',
            'rgba(239, 68, 68, 1)',
            'rgba(139, 92, 246, 1)'
          ],
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'right',
            labels: {
              color: document.documentElement.classList.contains('dark') ? '#f8fafc' : '#1e293b',
              boxWidth: 15,
              font: {
                size: 10
              }
            }
          }
        }
      }
    });
    
    // Update chart colors when theme changes
    function updateChartColors() {
      const isDark = document.documentElement.classList.contains('dark');
      
      // Update Borrow Chart
      borrowChart.options.plugins.legend.labels.color = isDark ? '#f8fafc' : '#1e293b';
      borrowChart.options.scales.y.ticks.color = isDark ? '#94a3b8' : '#64748b';
      borrowChart.options.scales.x.ticks.color = isDark ? '#94a3b8' : '#64748b';
      borrowChart.options.scales.y.grid.color = isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.05)';
      borrowChart.options.scales.x.grid.color = isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.05)';
      
      // Update Category Chart
      categoryChart.options.plugins.legend.labels.color = isDark ? '#f8fafc' : '#1e293b';
      
      // Update charts
      borrowChart.update();
      categoryChart.update();
    }
  </script>
</body>
</html>