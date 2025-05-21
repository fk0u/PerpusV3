<?php
session_start();
include '../includes/db.php';

// Pastikan pengguna sudah login dan memiliki role 'student'
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Ambil informasi pengguna
$stmt = $pdo->prepare("SELECT username, email, full_name FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Ambil statistik
$stmt = $pdo->query("SELECT COUNT(*) AS total_books FROM books");
$total_books = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) AS total_visitors FROM users WHERE role = 'student'");
$total_visitors = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) AS total_loans FROM borrow_history");
$total_loans = $stmt->fetchColumn();

// Ambil daftar buku dengan informasi stok
$stmt = $pdo->query("SELECT *, (stock > 0) as is_available FROM books ORDER BY title ASC");
$books = $stmt->fetchAll();

// Ambil 5 buku terbaru dengan informasi stok
$stmt = $pdo->query("SELECT *, (stock > 0) as is_available FROM books ORDER BY created_at DESC LIMIT 5");
$latest_books = $stmt->fetchAll();

// Ambil riwayat peminjaman
$stmt = $pdo->prepare("SELECT bh.id, b.title AS book_title, bh.borrow_date, bh.return_date, bh.unique_code, bh.status, 
                       bh.return_condition, bh.return_notes, rr.unique_code AS return_code, rr.request_date
                       FROM borrow_history bh
                       JOIN books b ON bh.book_id = b.id
                       LEFT JOIN return_requests rr ON bh.id = rr.borrow_history_id
                       WHERE bh.user_id = ?
                       ORDER BY bh.borrow_date DESC");
$stmt->execute([$_SESSION['user_id']]);
$borrow_history = $stmt->fetchAll();

// Hitung buku yang sedang dipinjam
$active_loans = 0;
$returned_books = 0;
$pending_returns = 0;

foreach ($borrow_history as $history) {
    if ($history['status'] === 'dipinjam' && !$history['return_code']) {
        $active_loans++;
    } elseif ($history['status'] === 'dipinjam' && $history['return_code']) {
        $pending_returns++;
    } else {
        $returned_books++;
    }
}

// Ambil jumlah buku dalam keranjang
$stmt = $pdo->prepare("SELECT COUNT(*) AS cart_count FROM borrow_cart WHERE user_id = ?");
$stmt->execute([$user_id]);
$cart_count = $stmt->fetchColumn();

// Cek apakah ada pesan sukses dari halaman lain
$success_message = '';
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $success_message = 'Peminjaman buku berhasil diproses!';
}
if (isset($_GET['return_success']) && $_GET['return_success'] == 1) {
    $success_message = 'Pengajuan pengembalian buku berhasil!';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Dashboard - SevenLibrary v6</title>
  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- GSAP -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
  <!-- AOS -->
  <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
  <!-- Lineicons -->
  <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet">
  <!-- ApexCharts -->
  <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
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
    
    /* Sidebar */
    .sidebar {
      width: 280px;
      transition: all 0.3s ease;
    }
    
    .sidebar.collapsed {
      width: 80px;
    }
    
    .sidebar-item {
      display: flex;
      align-items: center;
      padding: 0.75rem 1rem;
      border-radius: 0.5rem;
      transition: all 0.2s ease;
      cursor: pointer;
    }
    
    .sidebar-item:hover {
      background-color: var(--sidebar-hover);
    }
    
    .sidebar-item.active {
      background-color: var(--sidebar-active);
    }
    
    .sidebar-icon {
      width: 1.5rem;
      height: 1.5rem;
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--sidebar-icon);
    }
    
    .sidebar-text {
      margin-left: 0.75rem;
      color: var(--sidebar-text);
      transition: opacity 0.3s ease;
    }
    
    .sidebar.collapsed .sidebar-text {
      opacity: 0;
      width: 0;
      height: 0;
      overflow: hidden;
    }
    
    .sidebar-divider {
      height: 1px;
      background-color: var(--sidebar-border);
      margin: 0.75rem 0;
    }
    
    /* Header */
    .header {
      height: 70px;
      border-bottom: 1px solid var(--sidebar-border);
    }
    
    /* Content */
    .content {
      transition: margin-left 0.3s ease;
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
    
    /* Book card */
    .book-card {
      transition: transform 0.3s ease;
    }
    
    .book-card:hover {
      transform: translateY(-8px);
    }
    
    /* User dropdown */
    .user-dropdown {
      position: absolute;
      top: 100%;
      right: 0;
      width: 200px;
      background-color: white;
      border-radius: 0.5rem;
      box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
      z-index: 50;
      opacity: 0;
      visibility: hidden;
      transform: translateY(10px);
      transition: all 0.2s ease;
    }
    
    .dark .user-dropdown {
      background-color: #1e293b;
    }
    
    .user-dropdown.show {
      opacity: 1;
      visibility: visible;
      transform: translateY(0);
    }
    
    /* Tab content */
    .tab-content {
      display: none;
    }
    
    .tab-content.active {
      display: block;
      animation: fadeIn 0.5s ease forwards;
    }
    
    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(10px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    
    /* Progress bar */
    .progress-ring {
      transform: rotate(-90deg);
    }
    
    .progress-ring__circle {
      stroke-dasharray: 283;
      transition: stroke-dashoffset 0.5s ease;
    }
    
    /* Cart badge */
    .cart-badge {
      position: absolute;
      top: -8px;
      right: -8px;
      background-color: #ef4444;
      color: white;
      border-radius: 9999px;
      font-size: 0.75rem;
      font-weight: 600;
      width: 18px;
      height: 18px;
      display: flex;
      align-items: center;
      justify-content: center;
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

  <div class="flex h-screen overflow-hidden">
    <!-- Sidebar -->
    <aside id="sidebar" class="sidebar bg-sidebar-bg dark:bg-sidebar-bg border-r border-sidebar-border dark:border-sidebar-border">
      <div class="h-full flex flex-col">
        <!-- Logo -->
        <div class="h-16 flex items-center justify-center px-4">
          <a href="../index.php" class="flex items-center">
            <span class="text-primary text-xl font-bold">Seven<span class="text-secondary">Library</span></span>
          </a>
          <button id="sidebar-toggle" class="ml-auto text-sidebar-icon hover:text-primary">
            <i class="lni lni-chevron-left"></i>
          </button>
        </div>
        
        <!-- Navigation -->
        <nav class="flex-1 px-4 py-4 overflow-y-auto">
          <ul class="space-y-2">
            <li>
              <a href="#" class="sidebar-item active" data-tab="beranda">
                <span class="sidebar-icon"><i class="lni lni-dashboard"></i></span>
                <span class="sidebar-text font-medium">Beranda</span>
              </a>
            </li>
            <li>
              <a href="#" class="sidebar-item" data-tab="daftar-buku">
                <span class="sidebar-icon"><i class="lni lni-book"></i></span>
                <span class="sidebar-text">Daftar Buku</span>
              </a>
            </li>
            <li>
              <a href="#" class="sidebar-item" data-tab="keranjang">
                <span class="sidebar-icon relative">
                  <i class="lni lni-cart"></i>
                  <?php if ($cart_count > 0): ?>
                    <span class="cart-badge"><?= $cart_count; ?></span>
                  <?php endif; ?>
                </span>
                <span class="sidebar-text">Keranjang</span>
              </a>
            </li>
            <li>
              <a href="#" class="sidebar-item" data-tab="riwayat">
                <span class="sidebar-icon"><i class="lni lni-library"></i></span>
                <span class="sidebar-text">Riwayat Peminjaman</span>
              </a>
            </li>
            <li>
              <a href="#" class="sidebar-item" data-tab="akun">
                <span class="sidebar-icon"><i class="lni lni-user"></i></span>
                <span class="sidebar-text">Akun</span>
              </a>
            </li>
            
            <div class="sidebar-divider"></div>
            
            <li>
              <a href="#" class="sidebar-item">
                <span class="sidebar-icon"><i class="lni lni-question-circle"></i></span>
                <span class="sidebar-text">Bantuan</span>
              </a>
            </li>
            <li>
              <a href="../logout.php" class="sidebar-item">
                <span class="sidebar-icon"><i class="lni lni-exit"></i></span>
                <span class="sidebar-text">Logout</span>
              </a>
            </li>
          </ul>
        </nav>
        
        <!-- Footer -->
        <div class="p-4 border-t border-sidebar-border dark:border-sidebar-border">
          <div class="flex items-center">
            <div class="w-8 h-8 rounded-full bg-primary/20 flex items-center justify-center text-primary">
              <i class="lni lni-user"></i>
            </div>
            <div class="ml-3 sidebar-text">
              <p class="text-sm font-medium"><?= htmlspecialchars($user['username']); ?></p>
              <p class="text-xs text-gray-500 dark:text-gray-400">Siswa</p>
            </div>
          </div>
        </div>
      </div>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden">
      <!-- Header -->
      <header class="header bg-white dark:bg-gray-800 px-6 flex items-center justify-between">
        <div class="flex items-center">
          <button id="mobile-sidebar-toggle" class="mr-4 text-gray-500 dark:text-gray-400 md:hidden">
            <i class="lni lni-menu text-xl"></i>
          </button>
          <h1 class="text-xl font-semibold text-gray-800 dark:text-white">Dashboard Siswa</h1>
        </div>
        
        <div class="flex items-center space-x-4">
          <!-- Search -->
          <div class="hidden md:block relative">
            <input type="text" placeholder="Cari buku..." class="w-64 px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-white text-sm">
            <button class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 dark:text-gray-500">
              <i class="lni lni-search-alt"></i>
            </button>
          </div>
          
          <!-- Cart Button -->
          <div class="relative">
            <button id="cart-btn" class="w-10 h-10 rounded-full flex items-center justify-center text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none" data-tab-trigger="keranjang">
              <i class="lni lni-cart text-xl"></i>
              <?php if ($cart_count > 0): ?>
                <span class="cart-badge"><?= $cart_count; ?></span>
              <?php endif; ?>
            </button>
          </div>
          
          <!-- Theme Toggle -->
          <button id="theme-toggle" class="w-10 h-10 rounded-full flex items-center justify-center text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none">
            <i class="lni lni-sun dark:hidden text-xl"></i>
            <i class="lni lni-night hidden dark:inline-block text-xl"></i>
          </button>
          
          <!-- User Menu -->
          <div class="relative">
            <button id="user-menu-btn" class="flex items-center focus:outline-none">
              <div class="w-8 h-8 rounded-full bg-primary/20 flex items-center justify-center text-primary">
                <i class="lni lni-user"></i>
              </div>
              <span class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300 hidden md:inline-block"><?= htmlspecialchars($user['username']); ?></span>
              <i class="lni lni-chevron-down text-xs ml-1 text-gray-500 dark:text-gray-400 hidden md:inline-block"></i>
            </button>
            
            <div id="user-dropdown" class="user-dropdown">
              <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <p class="text-sm font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($user['username']); ?></p>
                <p class="text-xs text-gray-500 dark:text-gray-400"><?= htmlspecialchars($user['email']); ?></p>
              </div>
              <div class="p-2">
                <a href="#" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md" data-tab-trigger="akun">
                  <i class="lni lni-user mr-2"></i> Profil
                </a>
                <a href="../logout.php" class="block px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md">
                  <i class="lni lni-exit mr-2"></i> Logout
                </a>
              </div>
            </div>
          </div>
        </div>
      </header>

      <!-- Main Content -->
      <main class="flex-1 overflow-y-auto p-6 bg-gray-50 dark:bg-gray-900">
        <?php if (!empty($success_message)): ?>
          <div class="bg-green-50 dark:bg-green-900/20 border-l-4 border-green-500 p-4 mb-6" data-aos="fade-up">
            <div class="flex">
              <div class="flex-shrink-0">
                <i class="lni lni-checkmark-circle text-green-500 dark:text-green-400"></i>
              </div>
              <div class="ml-3">
                <p class="text-sm text-green-700 dark:text-green-300"><?= htmlspecialchars($success_message); ?></p>
              </div>
            </div>
          </div>
        <?php endif; ?>
        
        <!-- Tab Content -->
        <div id="tab-contents">
          <!-- Tab Beranda -->
          <div id="beranda" class="tab-content active">
            <!-- Welcome Banner -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 mb-6 shadow-sm" data-aos="fade-up">
              <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div class="mb-4 md:mb-0">
                  <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Selamat datang, <?= htmlspecialchars($user['username']); ?>!</h2>
                  <p class="text-gray-600 dark:text-gray-300 mt-1">Selamat datang di E-Library SMKN 7 Samarinda. Anda dapat menjelajahi koleksi buku kami dan mengelola akun Anda di sini.</p>
                </div>
                <div class="flex space-x-3">
                  <a href="#" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 text-sm font-medium" data-tab-trigger="daftar-buku">Pinjam Buku</a>
                  <a href="#" class="px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 text-sm font-medium" data-tab-trigger="riwayat">Riwayat Peminjaman</a>
                </div>
              </div>
            </div>
            
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
              <div class="dashboard-card bg-white dark:bg-gray-800 p-6" data-aos="fade-up" data-aos-delay="100">
                <div class="flex items-center">
                  <div class="w-12 h-12 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-500 dark:text-blue-400">
                    <i class="lni lni-book text-xl"></i>
                  </div>
                  <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Buku</h3>
                    <p class="text-2xl font-bold text-gray-800 dark:text-white"><?= $total_books; ?></p>
                  </div>
                </div>
              </div>
              
              <div class="dashboard-card bg-white dark:bg-gray-800 p-6" data-aos="fade-up" data-aos-delay="200">
                <div class="flex items-center">
                  <div class="w-12 h-12 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center text-green-500 dark:text-green-400">
                    <i class="lni lni-users text-xl"></i>
                  </div>
                  <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Pengunjung</h3>
                    <p class="text-2xl font-bold text-gray-800 dark:text-white"><?= $total_visitors; ?></p>
                  </div>
                </div>
              </div>
              
              <div class="dashboard-card bg-white dark:bg-gray-800 p-6" data-aos="fade-up" data-aos-delay="300">
                <div class="flex items-center">
                  <div class="w-12 h-12 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center text-purple-500 dark:text-purple-400">
                    <i class="lni lni-library text-xl"></i>
                  </div>
                  <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Peminjaman</h3>
                    <p class="text-2xl font-bold text-gray-800 dark:text-white"><?= $total_loans; ?></p>
                  </div>
                </div>
              </div>
              
              <div class="dashboard-card bg-white dark:bg-gray-800 p-6" data-aos="fade-up" data-aos-delay="400">
                <div class="flex items-center">
                  <div class="w-12 h-12 rounded-full bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center text-yellow-500 dark:text-yellow-400">
                    <i class="lni lni-cart text-xl"></i>
                  </div>
                  <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Keranjang Anda</h3>
                    <p class="text-2xl font-bold text-gray-800 dark:text-white"><?= $cart_count; ?> Buku</p>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Buku Terbaru -->
            <div class="dashboard-card bg-white dark:bg-gray-800 p-6 mb-6" data-aos="fade-up" data-aos-delay="500">
              <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Buku Terbaru</h3>
                <a href="#" class="text-sm text-primary hover:underline" data-tab-trigger="daftar-buku">Lihat Semua</a>
              </div>
              
              <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-6">
                <?php if (count($latest_books) > 0): ?>
                  <?php foreach ($latest_books as $book): ?>
                    <div class="book-card bg-gray-50 dark:bg-gray-700 rounded-lg overflow-hidden">
                      <div class="aspect-[3/4] bg-gray-200 dark:bg-gray-600 flex items-center justify-center">
                        <i class="lni lni-book text-4xl text-gray-400 dark:text-gray-500"></i>
                      </div>
                      <div class="p-4">
                        <h4 class="font-medium text-gray-900 dark:text-white text-sm mb-1 truncate"><?= htmlspecialchars($book['title']); ?></h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-2"><?= htmlspecialchars($book['author']); ?></p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Tahun: <?= htmlspecialchars($book['published_year']); ?></p>
                        <div class="flex items-center justify-between mb-2">
                          <span class="text-xs <?= $book['is_available'] ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' ?>">
                            <?= $book['is_available'] ? 'Tersedia (' . $book['stock'] . ')' : 'Stok Habis' ?>
                          </span>
                        </div>
                        <?php if ($book['is_available']): ?>
                          <form method="GET" action="cart.php">
                            <input type="hidden" name="add_to_cart" value="<?= $book['id']; ?>">
                            <button type="submit" class="block w-full text-center py-1.5 bg-primary text-white text-xs rounded-md hover:bg-primary/90 transition-colors">
                              <i class="lni lni-cart mr-1"></i> Tambah ke Keranjang
                            </button>
                          </form>
                        <?php else: ?>
                          <button disabled class="block w-full text-center py-1.5 bg-gray-300 dark:bg-gray-600 text-gray-500 dark:text-gray-400 text-xs rounded-md cursor-not-allowed">
                            Stok Habis
                          </button>
                        <?php endif; ?>
                      </div>
                    </div>
                  <?php endforeach; ?>
                <?php else: ?>
                  <div class="col-span-full text-center py-8">
                    <div class="w-16 h-16 mx-auto bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mb-4">
                      <i class="lni lni-book text-gray-400 dark:text-gray-500 text-2xl"></i>
                    </div>
                    <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Tidak ada buku terbaru</h4>
                    <p class="text-gray-500 dark:text-gray-400">Buku terbaru akan ditampilkan di sini.</p>
                  </div>
                <?php endif; ?>
              </div>
            </div>
            
            <!-- Riwayat Peminjaman Terbaru -->
            <div class="dashboard-card bg-white dark:bg-gray-800 p-6" data-aos="fade-up" data-aos-delay="600">
              <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Riwayat Peminjaman Terbaru</h3>
                <a href="#" class="text-sm text-primary hover:underline" data-tab-trigger="riwayat">Lihat Semua</a>
              </div>
              
              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                  <thead>
                    <tr>
                      <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Judul Buku</th>
                      <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tanggal Pinjam</th>
                      <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tanggal Kembali</th>
                      <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <?php if (count($borrow_history) > 0): ?>
                      <?php 
                      // Limit to 5 most recent entries
                      $recent_history = array_slice($borrow_history, 0, 5);
                      foreach ($recent_history as $history): 
                        $status_class = '';
                        $status_text = '';
                        
                        if ($history['return_date']) {
                          $status_class = 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400';
                          $status_text = 'Dikembalikan';
                        } elseif ($history['return_code']) {
                          $status_class = 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400';
                          $status_text = 'Menunggu Pengembalian';
                        } else {
                          $status_class = 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400';
                          $status_text = 'Dipinjam';
                        }
                      ?>
                        <tr>
                          <td class="px-4 py-3 whitespace-nowrap">
                            <p class="text-sm font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($history['book_title']); ?></p>
                          </td>
                          <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            <?= date('d/m/Y', strtotime($history['borrow_date'])); ?>
                          </td>
                          <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            <?= $history['return_date'] ? date('d/m/Y', strtotime($history['return_date'])) : 'Belum dikembalikan'; ?>
                          </td>
                          <td class="px-4 py-3 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs rounded-full <?= $status_class ?>">
                              <?= $status_text ?>
                            </span>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    <?php else: ?>
                      <tr>
                        <td colspan="4" class="px-4 py-8 text-center">
                          <div class="w-16 h-16 mx-auto bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mb-4">
                            <i class="lni lni-library text-gray-400 dark:text-gray-500 text-2xl"></i>
                          </div>
                          <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Belum ada riwayat peminjaman</h4>
                          <p class="text-gray-500 dark:text-gray-400">Riwayat peminjaman buku akan muncul di sini.</p>
                        </td>
                      </tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
          
          <!-- Tab Daftar Buku -->
          <div id="daftar-buku" class="tab-content">
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 mb-6 shadow-sm" data-aos="fade-up">
              <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-4">Daftar Buku</h2>
              <p class="text-gray-600 dark:text-gray-300">Berikut adalah daftar buku yang tersedia di E-Library SMKN 7 Samarinda:</p>
              
              <!-- Search and Filter -->
              <div class="mt-6 flex flex-col md:flex-row gap-4">
                <div class="relative flex-grow">
                  <input type="text" id="search-books" placeholder="Cari judul, penulis, atau ISBN..." class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-white text-sm">
                  <button class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 dark:text-gray-500">
                    <i class="lni lni-search-alt"></i>
                  </button>
                </div>
                <select id="category-filter" class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-white text-sm">
                  <option value="">Semua Kategori</option>
                  <?php
                  // Get categories from database
                  $stmt = $pdo->query("SELECT id, name FROM category ORDER BY name ASC");
                  $categories = $stmt->fetchAll();
                  foreach ($categories as $category) {
                    echo '<option value="' . $category['id'] . '">' . htmlspecialchars($category['name']) . '</option>';
                  }
                  ?>
                </select>
                <select id="availability-filter" class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-white text-sm">
                  <option value="">Semua Status</option>
                  <option value="available">Tersedia</option>
                  <option value="unavailable">Stok Habis</option>
                </select>
              </div>
            </div>
            
            <!-- Book List -->
            <div class="dashboard-card bg-white dark:bg-gray-800 p-6" data-aos="fade-up" data-aos-delay="100">
              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700" id="books-table">
                  <thead>
                    <tr>
                      <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Judul Buku</th>
                      <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Penulis</th>
                      <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tahun Terbit</th>
                      <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ISBN</th>
                      <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Stok</th>
                      <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Aksi</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <?php if (count($books) > 0): ?>
                      <?php foreach ($books as $book): ?>
                        <tr class="book-row" data-category="<?= $book['category_id']; ?>" data-availability="<?= $book['is_available'] ? 'available' : 'unavailable'; ?>">
                          <td class="px-4 py-3 whitespace-nowrap">
                            <p class="text-sm font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($book['title']); ?></p>
                          </td>
                          <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            <?= htmlspecialchars($book['author']); ?>
                          </td>
                          <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            <?= htmlspecialchars($book['published_year']); ?>
                          </td>
                          <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            <?= htmlspecialchars($book['isbn']); ?>
                          </td>
                          <td class="px-4 py-3 whitespace-nowrap">
                            <?php if ($book['is_available']): ?>
                              <span class="px-2 py-1 text-xs rounded-full bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400">
                                Tersedia (<?= $book['stock']; ?>)
                              </span>
                            <?php else: ?>
                              <span class="px-2 py-1 text-xs rounded-full bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400">
                                Stok Habis
                              </span>
                            <?php endif; ?>
                          </td>
                          <td class="px-4 py-3 whitespace-nowrap">
                            <?php if ($book['is_available']): ?>
                              <form method="GET" action="cart.php">
                                <input type="hidden" name="add_to_cart" value="<?= $book['id']; ?>">
                                <button type="submit" class="px-3 py-1 bg-primary text-white text-xs rounded-md hover:bg-primary/90 transition-colors">
                                  <i class="lni lni-cart mr-1"></i> Tambah ke Keranjang
                                </button>
                              </form>
                            <?php else: ?>
                              <button disabled class="px-3 py-1 bg-gray-300 dark:bg-gray-600 text-gray-500 dark:text-gray-400 text-xs rounded-md cursor-not-allowed">
                                Stok Habis
                              </button>
                            <?php endif; ?>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    <?php else: ?>
                      <tr>
                        <td colspan="6" class="px-4 py-8 text-center">
                          <div class="w-16 h-16 mx-auto bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mb-4">
                            <i class="lni lni-book text-gray-400 dark:text-gray-500 text-2xl"></i>
                          </div>
                          <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Tidak ada buku tersedia</h4>
                          <p class="text-gray-500 dark:text-gray-400">Buku akan ditampilkan di sini ketika tersedia.</p>
                        </td>
                      </tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
              
              <!-- Pagination -->
              <div class="mt-6 flex justify-between items-center">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                  Menampilkan <span class="font-medium"><?= count($books); ?></span> buku
                </p>
                <div class="flex space-x-1">
                  <button class="px-3 py-1 rounded border border-gray-300 dark:border-gray-600 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50" disabled>
                    <i class="lni lni-chevron-left"></i>
                  </button>
                  <button class="px-3 py-1 rounded bg-primary text-white hover:bg-primary/90">1</button>
                  <button class="px-3 py-1 rounded border border-gray-300 dark:border-gray-600 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700">
                    <i class="lni lni-chevron-right"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Tab Keranjang -->
          <div id="keranjang" class="tab-content">
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 mb-6 shadow-sm" data-aos="fade-up">
              <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-4">Keranjang Peminjaman</h2>
              <p class="text-gray-600 dark:text-gray-300">Kelola buku yang ingin Anda pinjam:</p>
            </div>
            
            <!-- Cart Items -->
            <div class="dashboard-card bg-white dark:bg-gray-800 p-6" data-aos="fade-up" data-aos-delay="100">
              <?php
              // Get cart items
              $stmt = $pdo->prepare("
                  SELECT bc.id as cart_id, b.id as book_id, b.title, b.author, b.published_year, c.name as category_name
                  FROM borrow_cart bc
                  JOIN books b ON bc.book_id = b.id
                  LEFT JOIN category c ON b.category_id = c.id
                  WHERE bc.user_id = ?
                  ORDER BY bc.added_at DESC
              ");
              $stmt->execute([$user_id]);
              $cart_items = $stmt->fetchAll();
              ?>
              
              <?php if (count($cart_items) > 0): ?>
                <div class="space-y-4">
                  <?php foreach ($cart_items as $item): ?>
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                      <div class="flex items-start sm:items-center mb-4 sm:mb-0">
                        <div class="w-12 h-12 bg-gray-100 dark:bg-gray-700 rounded-md flex items-center justify-center text-gray-500 dark:text-gray-400 mr-4">
                          <i class="lni lni-book text-xl"></i>
                        </div>
                        <div>
                          <h3 class="text-base font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($item['title']); ?></h3>
                          <p class="text-sm text-gray-500 dark:text-gray-400">
                            <?= htmlspecialchars($item['author']); ?> • <?= htmlspecialchars($item['category_name']); ?> • <?= htmlspecialchars($item['published_year']); ?>
                          </p>
                        </div>
                      </div>
                      <a href="cart.php?remove_from_cart=<?= $item['cart_id']; ?>" class="inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md text-red-700 dark:text-red-300 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/30 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                        <i class="lni lni-trash-can mr-1"></i> Hapus
                      </a>
                    </div>
                  <?php endforeach; ?>
                </div>
                
                <div class="mt-8 bg-gray-50 dark:bg-gray-700 p-6 rounded-lg">
                  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                    <div class="mb-4 sm:mb-0">
                      <p class="text-sm text-gray-500 dark:text-gray-400">Total Buku</p>
                      <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= count($cart_items); ?> Buku</p>
                      <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Periode peminjaman: 7 hari</p>
                    </div>
                    <form method="POST" action="cart.php">
                      <button type="submit" name="checkout" class="w-full sm:w-auto px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-primary hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                        Proses Peminjaman
                      </button>
                    </form>
                  </div>
                </div>
              <?php else: ?>
                <div class="text-center py-12">
                  <div class="w-20 h-20 mx-auto bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center text-gray-400 dark:text-gray-500 mb-4">
                    <i class="lni lni-cart text-3xl"></i>
                  </div>
                  <h3 class="text-xl font-medium text-gray-900 dark:text-white mb-2">Keranjang Kosong</h3>
                  <p class="text-gray-500 dark:text-gray-400 mb-6">Anda belum menambahkan buku ke keranjang peminjaman.</p>
                  <a href="#" class="inline-flex items-center px-4 py-2 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-primary hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary" data-tab-trigger="daftar-buku">
                    <i class="lni lni-book mr-2"></i> Jelajahi Buku
                  </a>
                </div>
              <?php endif; ?>
            </div>
            
            <!-- Borrowing Guidelines -->
            <div class="dashboard-card bg-white dark:bg-gray-800 p-6 mt-6" data-aos="fade-up" data-aos-delay="200">
              <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Panduan Peminjaman</h3>
              
              <div class="space-y-4">
                <div class="flex">
                  <div class="flex-shrink-0">
                    <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-500 dark:text-blue-400">
                      <span class="text-sm font-medium">1</span>
                    </div>
                  </div>
                  <div class="ml-4">
                    <h4 class="text-base font-medium text-gray-900 dark:text-white">Pilih Buku</h4>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Tambahkan buku yang ingin Anda pinjam ke keranjang.</p>
                  </div>
                </div>
                
                <div class="flex">
                  <div class="flex-shrink-0">
                    <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-500 dark:text-blue-400">
                      <span class="text-sm font-medium">2</span>
                    </div>
                  </div>
                  <div class="ml-4">
                    <h4 class="text-base font-medium text-gray-900 dark:text-white">Proses Peminjaman</h4>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Klik tombol "Proses Peminjaman" untuk menyelesaikan proses.</p>
                  </div>
                </div>
                
                <div class="flex">
                  <div class="flex-shrink-0">
                    <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-500 dark:text-blue-400">
                      <span class="text-sm font-medium">3</span>
                    </div>
                  </div>
                  <div class="ml-4">
                    <h4 class="text-base font-medium text-gray-900 dark:text-white">Ambil Buku</h4>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Kunjungi perpustakaan untuk mengambil buku yang Anda pinjam.</p>
                  </div>
                </div>
                
                <div class="flex">
                  <div class="flex-shrink-0">
                    <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-500 dark:text-blue-400">
                      <span class="text-sm font-medium">4</span>
                    </div>
                  </div>
                  <div class="ml-4">
                    <h4 class="text-base font-medium text-gray-900 dark:text-white">Kembalikan Tepat Waktu</h4>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Kembalikan buku sebelum tanggal jatuh tempo untuk menghindari denda.</p>
                  </div>
                </div>
              </div>
              
              <div class="mt-6 bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg">
                <div class="flex">
                  <div class="flex-shrink-0">
                    <i class="lni lni-warning text-yellow-500 dark:text-yellow-400"></i>
                  </div>
                  <div class="ml-3">
                    <h4 class="text-sm font-medium text-yellow-800 dark:text-yellow-300">Perhatian</h4>
                    <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-200">
                      <p>Anda hanya dapat meminjam maksimal 5 buku dalam satu waktu. Periode peminjaman adalah 7 hari.</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Tab Riwayat Peminjaman -->
          <div id="riwayat" class="tab-content">
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 mb-6 shadow-sm" data-aos="fade-up">
              <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-4">Riwayat Peminjaman Buku</h2>
              <p class="text-gray-600 dark:text-gray-300">Berikut adalah riwayat peminjaman buku Anda:</p>
            </div>
            
            <!-- Borrowing Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
              <div class="dashboard-card bg-white dark:bg-gray-800 p-6" data-aos="fade-up" data-aos-delay="100">
                <div class="flex items-center">
                  <div class="w-12 h-12 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-500 dark:text-blue-400">
                    <i class="lni lni-book text-xl"></i>
                  </div>
                  <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Dipinjam</h3>
                    <p class="text-2xl font-bold text-gray-800 dark:text-white"><?= count($borrow_history); ?></p>
                  </div>
                </div>
              </div>
              
              <div class="dashboard-card bg-white dark:bg-gray-800 p-6" data-aos="fade-up" data-aos-delay="200">
                <div class="flex items-center">
                  <div class="w-12 h-12 rounded-full bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center text-yellow-500 dark:text-yellow-400">
                    <i class="lni lni-library text-xl"></i>
                  </div>
                  <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Sedang Dipinjam</h3>
                    <p class="text-2xl font-bold text-gray-800 dark:text-white"><?= $active_loans; ?></p>
                  </div>
                </div>
              </div>
              
              <div class="dashboard-card bg-white dark:bg-gray-800 p-6" data-aos="fade-up" data-aos-delay="300">
                <div class="flex items-center">
                  <div class="w-12 h-12 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-500 dark:text-blue-400">
                    <i class="lni lni-reload text-xl"></i>
                  </div>
                  <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Menunggu Pengembalian</h3>
                    <p class="text-2xl font-bold text-gray-800 dark:text-white"><?= $pending_returns; ?></p>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Borrowing History Table -->
            <div class="dashboard-card bg-white dark:bg-gray-800 p-6" data-aos="fade-up" data-aos-delay="400">
              <div class="mb-4 flex flex-col md:flex-row gap-4">
                <select id="history-status-filter" class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-white text-sm">
                  <option value="all">Semua Status</option>
                  <option value="dipinjam">Dipinjam</option>
                  <option value="pending">Menunggu Pengembalian</option>
                  <option value="dikembalikan">Dikem balikan</option>
                </select>
                <div class="relative flex-grow">
                  <input type="text" id="search-history" placeholder="Cari judul buku..." class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-white text-sm">
                  <button class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 dark:text-gray-500">
                    <i class="lni lni-search-alt"></i>
                  </button>
                </div>
              </div>
              
              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700" id="history-table">
                  <thead>
                    <tr>
                      <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Judul Buku</th>
                      <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tanggal Pinjam</th>
                      <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tanggal Kembali</th>
                      <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Kode Pengembalian</th>
                      <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                      <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Kondisi</th>
                      <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Aksi</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <?php if (count($borrow_history) > 0): ?>
                      <?php foreach ($borrow_history as $history): 
                        $status_class = '';
                        $status_text = '';
                        $status_filter = '';
                        
                        if ($history['return_date']) {
                          $status_class = 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400';
                          $status_text = 'Dikembalikan';
                          $status_filter = 'dikembalikan';
                        } elseif ($history['return_code']) {
                          $status_class = 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400';
                          $status_text = 'Menunggu Pengembalian';
                          $status_filter = 'pending';
                        } else {
                          $status_class = 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400';
                          $status_text = 'Dipinjam';
                          $status_filter = 'dipinjam';
                        }
                      ?>
                        <tr class="history-row" data-status="<?= $status_filter; ?>">
                          <td class="px-4 py-3 whitespace-nowrap">
                            <p class="text-sm font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($history['book_title']); ?></p>
                          </td>
                          <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            <?= date('d/m/Y', strtotime($history['borrow_date'])); ?>
                          </td>
                          <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            <?= $history['return_date'] ? date('d/m/Y', strtotime($history['return_date'])) : 'Belum dikembalikan'; ?>
                          </td>
                          <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            <?= $history['return_code'] ? htmlspecialchars($history['return_code']) : '-'; ?>
                          </td>
                          <td class="px-4 py-3 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs rounded-full <?= $status_class ?>">
                              <?= $status_text ?>
                            </span>
                          </td>
                          <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            <?php if ($history['return_condition']): ?>
                              <?= htmlspecialchars(ucfirst($history['return_condition'])); ?>
                              <?php if ($history['return_notes']): ?>
                                <span class="text-xs text-gray-400 dark:text-gray-500 block">
                                  <?= htmlspecialchars($history['return_notes']); ?>
                                </span>
                              <?php endif; ?>
                            <?php else: ?>
                              -
                            <?php endif; ?>
                          </td>
                          <td class="px-4 py-3 whitespace-nowrap">
                            <?php if ($history['status'] === 'dipinjam' && !$history['return_code']): ?>
                              <form method="POST" action="ajukan_pengembalian.php">
                                <input type="hidden" name="borrow_id" value="<?= $history['id']; ?>">
                                <button type="submit" class="px-3 py-1 bg-warning text-white text-xs rounded-md hover:bg-warning/90 transition-colors">
                                  Ajukan Pengembalian
                                </button>
                              </form>
                            <?php elseif ($history['return_code'] && !$history['return_date']): ?>
                              <div class="text-xs text-blue-600 dark:text-blue-400">
                                <i class="lni lni-reload mr-1"></i> Menunggu Verifikasi
                              </div>
                            <?php else: ?>
                              <span class="text-xs text-gray-500 dark:text-gray-400">Selesai</span>
                            <?php endif; ?>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    <?php else: ?>
                      <tr>
                        <td colspan="7" class="px-4 py-8 text-center">
                          <div class="w-16 h-16 mx-auto bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mb-4">
                            <i class="lni lni-library text-gray-400 dark:text-gray-500 text-2xl"></i>
                          </div>
                          <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Belum ada riwayat peminjaman</h4>
                          <p class="text-gray-500 dark:text-gray-400">Riwayat peminjaman buku akan muncul di sini.</p>
                          <a href="#" class="mt-4 inline-block px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 text-sm font-medium" data-tab-trigger="daftar-buku">
                            Pinjam Buku Sekarang
                          </a>
                        </td>
                      </tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
            
            <!-- Return Process Guide -->
            <div class="dashboard-card bg-white dark:bg-gray-800 p-6 mt-6" data-aos="fade-up" data-aos-delay="500">
              <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Panduan Pengembalian Buku</h3>
              
              <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                  <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-500 dark:text-blue-400 mb-4">
                    <i class="lni lni-reload"></i>
                  </div>
                  <h4 class="text-base font-medium text-gray-900 dark:text-white mb-2">1. Ajukan Pengembalian</h4>
                  <p class="text-sm text-gray-500 dark:text-gray-400">Klik tombol "Ajukan Pengembalian" pada buku yang ingin Anda kembalikan.</p>
                </div>
                
                <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                  <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-500 dark:text-blue-400 mb-4">
                    <i class="lni lni-ticket-alt"></i>
                  </div>
                  <h4 class="text-base font-medium text-gray-900 dark:text-white mb-2">2. Dapatkan Kode Unik</h4>
                  <p class="text-sm text-gray-500 dark:text-gray-400">Sistem akan memberikan kode unik pengembalian yang harus Anda tunjukkan kepada petugas.</p>
                </div>
                
                <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                  <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-500 dark:text-blue-400 mb-4">
                    <i class="lni lni-checkmark-circle"></i>
                  </div>
                  <h4 class="text-base font-medium text-gray-900 dark:text-white mb-2">3. Verifikasi Petugas</h4>
                  <p class="text-sm text-gray-500 dark:text-gray-400">Kunjungi perpustakaan dan tunjukkan kode unik kepada petugas untuk verifikasi pengembalian.</p>
                </div>
              </div>
              
              <div class="mt-6 bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                <div class="flex">
                  <div class="flex-shrink-0">
                    <i class="lni lni-information text-blue-500 dark:text-blue-400"></i>
                  </div>
                  <div class="ml-3">
                    <h4 class="text-sm font-medium text-blue-800 dark:text-blue-300">Informasi</h4>
                    <div class="mt-2 text-sm text-blue-700 dark:text-blue-200">
                      <p>Pastikan buku dalam kondisi baik saat dikembalikan. Kerusakan pada buku dapat dikenakan biaya penggantian.</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Tab Akun -->
          <div id="akun" class="tab-content">
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 mb-6 shadow-sm" data-aos="fade-up">
              <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-4">Pengaturan Akun</h2>
              <p class="text-gray-600 dark:text-gray-300">Kelola informasi akun Anda di sini.</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
              <!-- Profile Card -->
              <div class="md:col-span-1">
                <div class="dashboard-card bg-white dark:bg-gray-800 p-6" data-aos="fade-up" data-aos-delay="100">
                  <div class="text-center">
                    <div class="w-24 h-24 mx-auto rounded-full bg-primary/20 flex items-center justify-center text-primary text-4xl mb-4">
                      <i class="lni lni-user"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 dark:text-white"><?= htmlspecialchars($user['username']); ?></h3>
                    <p class="text-gray-500 dark:text-gray-400 mt-1"><?= htmlspecialchars($user['email']); ?></p>
                    <div class="mt-4 py-2 px-4 bg-gray-100 dark:bg-gray-700 rounded-lg">
                      <p class="text-sm text-gray-500 dark:text-gray-400">Role: <span class="font-medium text-gray-800 dark:text-white">Siswa</span></p>
                    </div>
                  </div>
                  
                  <!-- Borrowing Stats -->
                  <div class="mt-6 space-y-3">
                    <div class="flex justify-between items-center">
                      <span class="text-sm text-gray-500 dark:text-gray-400">Total Peminjaman</span>
                      <span class="text-sm font-medium text-gray-800 dark:text-white"><?= count($borrow_history); ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                      <span class="text-sm text-gray-500 dark:text-gray-400">Sedang Dipinjam</span>
                      <span class="text-sm font-medium text-gray-800 dark:text-white"><?= $active_loans; ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                      <span class="text-sm text-gray-500 dark:text-gray-400">Menunggu Pengembalian</span>
                      <span class="text-sm font-medium text-gray-800 dark:text-white"><?= $pending_returns; ?></span>
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- Account Form -->
              <div class="md:col-span-2">
                <div class="dashboard-card bg-white dark:bg-gray-800 p-6" data-aos="fade-up" data-aos-delay="200">
                  <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Perbarui Informasi Akun</h3>
                  
                  <form method="POST" action="update_account.php" class="space-y-4">
                    <div>
                      <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Username</label>
                      <input type="text" id="username" name="username" value="<?= htmlspecialchars($user['username']); ?>" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-white" required>
                    </div>
                    
                    <div>
                      <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                      <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']); ?>" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-white" required>
                    </div>
                    
                    <div>
                      <label for="full_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nama Lengkap</label>
                      <input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($user['full_name']); ?>" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-white" required>
                    </div>
                    
                    <div>
                      <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Password Baru</label>
                      <input type="password" id="password" name="password" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-white">
                      <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Isi hanya jika ingin mengganti password.</p>
                    </div>
                    
                    <div>
                      <label for="confirm_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Konfirmasi Password Baru</label>
                      <input type="password" id="confirm_password" name="confirm_password" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-white">
                    </div>
                    
                    <div class="pt-4">
                      <button type="submit" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
                        Perbarui Akun
                      </button>
                    </div>
                  </form>
                </div>
                
                <!-- Notification Settings -->
                <div class="dashboard-card bg-white dark:bg-gray-800 p-6 mt-6" data-aos="fade-up" data-aos-delay="300">
                  <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Pengaturan Notifikasi</h3>
                  
                  <form method="POST" action="update_notifications.php" class="space-y-4">
                    <div class="flex items-center justify-between">
                      <div>
                        <h4 class="text-sm font-medium text-gray-900 dark:text-white">Notifikasi Email</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Terima notifikasi melalui email</p>
                      </div>
                      <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="email_notifications" value="1" class="sr-only peer" checked>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary/20 dark:peer-focus:ring-primary/30 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary"></div>
                      </label>
                    </div>
                    
                    <div class="flex items-center justify-between">
                      <div>
                        <h4 class="text-sm font-medium text-gray-900 dark:text-white">Pengingat Pengembalian</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Terima pengingat sebelum tanggal jatuh tempo</p>
                      </div>
                      <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="reminder_notifications" value="1" class="sr-only peer" checked>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary/20 dark:peer-focus:ring-primary/30 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary"></div>
                      </label>
                    </div>
                    
                    <div class="pt-4">
                      <button type="submit" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
                        Simpan Pengaturan
                      </button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>
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
      
      // Show success message if exists
      <?php if (!empty($success_message)): ?>
      setTimeout(function() {
        Swal.fire({
          icon: 'success',
          title: 'Berhasil!',
          text: '<?= $success_message ?>',
          confirmButtonColor: '#0a66f4'
        });
      }, 1500);
      <?php endif; ?>
    });
    
    // Sidebar Toggle
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const mobileSidebarToggle = document.getElementById('mobile-sidebar-toggle');
    
    sidebarToggle.addEventListener('click', function() {
      sidebar.classList.toggle('collapsed');
      
      if (sidebar.classList.contains('collapsed')) {
        sidebarToggle.innerHTML = '<i class="lni lni-chevron-right"></i>';
      } else {
        sidebarToggle.innerHTML = '<i class="lni lni-chevron-left"></i>';
      }
    });
    
    mobileSidebarToggle.addEventListener('click', function() {
      sidebar.classList.toggle('hidden');
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
    
    // User Dropdown
    const userMenuBtn = document.getElementById('user-menu-btn');
    const userDropdown = document.getElementById('user-dropdown');
    
    userMenuBtn.addEventListener('click', function(e) {
      e.stopPropagation();
      userDropdown.classList.toggle('show');
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function() {
      userDropdown.classList.remove('show');
    });
    
    // Prevent dropdown from closing when clicking inside
    userDropdown.addEventListener('click', function(e) {
      e.stopPropagation();
    });
    
    // Tab Navigation
    const tabLinks = document.querySelectorAll('[data-tab]');
    const tabContents = document.querySelectorAll('.tab-content');
    const tabTriggers = document.querySelectorAll('[data-tab-trigger]');
    
    function activateTab(tabId) {
      // Hide all tab contents
      tabContents.forEach(content => {
        content.classList.remove('active');
      });
      
      // Show the selected tab content
      document.getElementById(tabId).classList.add('active');
      
      // Update active state on sidebar links
      tabLinks.forEach(link => {
        if (link.getAttribute('data-tab') === tabId) {
          link.classList.add('active');
        } else {
          link.classList.remove('active');
        }
      });
    }
    
    // Add click event to tab links
    tabLinks.forEach(link => {
      link.addEventListener('click', function(e) {
        e.preventDefault();
        const tabId = this.getAttribute('data-tab');
        activateTab(tabId);
      });
    });
    
    // Add click event to tab triggers
    tabTriggers.forEach(trigger => {
      trigger.addEventListener('click', function(e) {
        e.preventDefault();
        const tabId = this.getAttribute('data-tab-trigger');
        activateTab(tabId);
        
        // Close user dropdown if open
        userDropdown.classList.remove('show');
      });
    });
    
    // Book search functionality
    document.getElementById('search-books').addEventListener('keyup', function() {
      const searchValue = this.value.toLowerCase();
      const table = document.getElementById('books-table');
      const rows = table.getElementsByClassName('book-row');
      
      for (let i = 0; i < rows.length; i++) {
        const titleCell = rows[i].getElementsByTagName('td')[0];
        const authorCell = rows[i].getElementsByTagName('td')[1];
        const isbnCell = rows[i].getElementsByTagName('td')[3];
        
        if (titleCell && authorCell && isbnCell) {
          const titleText = titleCell.textContent || titleCell.innerText;
          const authorText = authorCell.textContent || authorCell.innerText;
          const isbnText = isbnCell.textContent || isbnCell.innerText;
          
          if (
            titleText.toLowerCase().indexOf(searchValue) > -1 ||
            authorText.toLowerCase().indexOf(searchValue) > -1 ||
            isbnText.toLowerCase().indexOf(searchValue) > -1
          ) {
            rows[i].style.display = '';
          } else {
            rows[i].style.display = 'none';
          }
        }
      }
    });
    
    // Category filter
    document.getElementById('category-filter').addEventListener('change', function() {
      const categoryValue = this.value;
      const availabilityValue = document.getElementById('availability-filter').value;
      filterBooks(categoryValue, availabilityValue);
    });
    
    // Availability filter
    document.getElementById('availability-filter').addEventListener('change', function() {
      const availabilityValue = this.value;
      const categoryValue = document.getElementById('category-filter').value;
      filterBooks(categoryValue, availabilityValue);
    });
    
    function filterBooks(category, availability) {
      const rows = document.getElementsByClassName('book-row');
      
      for (let i = 0; i < rows.length; i++) {
        let showRow = true;
        
        if (category && rows[i].getAttribute('data-category') !== category) {
          showRow = false;
        }
        
        if (availability && rows[i].getAttribute('data-availability') !== availability) {
          showRow = false;
        }
        
        rows[i].style.display = showRow ? '' : 'none';
      }
    }
    
    // History search functionality
    document.getElementById('search-history').addEventListener('keyup', function() {
      const searchValue = this.value.toLowerCase();
      const table = document.getElementById('history-table');
      const rows = table.getElementsByClassName('history-row');
      
      for (let i = 0; i < rows.length; i++) {
        const titleCell = rows[i].getElementsByTagName('td')[0];
        
        if (titleCell) {
          const titleText = titleCell.textContent || titleCell.innerText;
          
          if (titleText.toLowerCase().indexOf(searchValue) > -1) {
            rows[i].style.display = '';
          } else {
            rows[i].style.display = 'none';
          }
        }
      }
    });
    
    // History status filter
    document.getElementById('history-status-filter').addEventListener('change', function() {
      const statusValue = this.value;
      const rows = document.getElementsByClassName('history-row');
      
      for (let i = 0; i < rows.length; i++) {
        if (statusValue === 'all' || rows[i].getAttribute('data-status') === statusValue) {
          rows[i].style.display = '';
        } else {
          rows[i].style.display = 'none';
        }
      }
    });
  </script>
</body>
</html>