<?php
session_start();
include '../includes/db.php';

// Pastikan pengguna sudah login dan memiliki role 'petugas'
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'petugas') {
    header("Location: ../login.php");
    exit();
}

// Tambah Kategori
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];

    $stmt = $pdo->prepare("INSERT INTO category (name, description) VALUES (?, ?)");
    $stmt->execute([$name, $description]);
    
    $success_message = 'Kategori berhasil ditambahkan.';
}

// Update Kategori
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_category'])) {
    $id = $_POST['edit_id'];
    $name = $_POST['name'];
    $description = $_POST['description'];

    $stmt = $pdo->prepare("UPDATE category SET name = ?, description = ? WHERE id = ?");
    $stmt->execute([$name, $description, $id]);
    
    $success_message = 'Kategori berhasil diperbarui.';
}

// Hapus Kategori
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Check if category has books
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM books WHERE category_id = ?");
    $stmt->execute([$id]);
    $book_count = $stmt->fetchColumn();
    
    if ($book_count > 0) {
        $error_message = 'Kategori tidak dapat dihapus karena masih memiliki buku.';
    } else {
        $stmt = $pdo->prepare("DELETE FROM category WHERE id = ?");
        $stmt->execute([$id]);
        
        $success_message = 'Kategori berhasil dihapus.';
    }
}

// Ambil Data Kategori dengan Total Buku
$stmt = $pdo->query("
    SELECT category.*, COUNT(books.id) AS total_books
    FROM category
    LEFT JOIN books ON category.id = books.category_id
    GROUP BY category.id
    ORDER BY category.name ASC
");
$categories = $stmt->fetchAll();

// Ambil Data Kategori untuk Diedit
$edit_category = null;
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM category WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_category = $stmt->fetch();
}

// Generate random colors for chart
function generateRandomColors($count) {
    $colors = [];
    $backgroundColors = [];
    $borderColors = [];
    
    for ($i = 0; $i < $count; $i++) {
        $r = rand(0, 200);
        $g = rand(0, 200);
        $b = rand(0, 200);
        
        $backgroundColors[] = "rgba($r, $g, $b, 0.6)";
        $borderColors[] = "rgba($r, $g, $b, 1)";
    }
    
    $colors['background'] = $backgroundColors;
    $colors['border'] = $borderColors;
    
    return $colors;
}

$colors = generateRandomColors(count($categories));
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kelola Kategori - SevenLibrary v6</title>
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
    
    /* Category Card */
    .category-card {
      transition: all 0.3s ease;
    }
    
    .category-card:hover {
      transform: translateY(-5px);
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
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white" data-aos="fade-up">Kelola Kategori</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400" data-aos="fade-up" data-aos-delay="100">
              Tambah, edit, dan hapus kategori buku perpustakaan
            </p>
          </div>
          <div class="mt-4 md:mt-0" data-aos="fade-up" data-aos-delay="200">
            <button type="button" onclick="openAddModal()" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
              <i class="lni lni-plus mr-2"></i>
              Tambah Kategori
            </button>
          </div>
        </div>

        <!-- Chart Visualization -->
        <div class="dashboard-card bg-white dark:bg-gray-800 p-6 mb-6" data-aos="fade-up" data-aos-delay="300">
          <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Visualisasi Total Buku per Kategori</h2>
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2">
              <div class="h-64">
                <canvas id="categoryChart"></canvas>
              </div>
            </div>
            <div>
              <div class="h-64">
                <canvas id="categoryPieChart"></canvas>
              </div>
            </div>
          </div>
        </div>

        <!-- Category Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6" data-aos="fade-up" data-aos-delay="400">
          <?php foreach ($categories as $index => $category): ?>
            <div class="category-card dashboard-card bg-white dark:bg-gray-800 overflow-hidden">
              <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($category['name']); ?></h3>
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400">
                  <?= $category['total_books']; ?> Buku
                </span>
              </div>
              <div class="px-6 py-4">
                <p class="text-sm text-gray-500 dark:text-gray-400 line-clamp-3"><?= htmlspecialchars($category['description']); ?></p>
              </div>
              <div class="px-6 py-3 bg-gray-50 dark:bg-gray-700 flex justify-end">
                <button onclick="openEditModal(<?= $category['id']; ?>, '<?= htmlspecialchars(addslashes($category['name'])); ?>', '<?= htmlspecialchars(addslashes($category['description'])); ?>')" class="text-blue-600 dark:text-blue-500 hover:text-blue-900 dark:hover:text-blue-400 mr-3">
                  <i class="lni lni-pencil"></i>
                </button>
                <?php if ($category['total_books'] == 0): ?>
                  <button onclick="confirmDelete(<?= $category['id']; ?>, '<?= htmlspecialchars($category['name']); ?>')" class="text-red-600 dark:text-red-500 hover:text-red-900 dark:hover:text-red-400">
                    <i class="lni lni-trash-can"></i>
                  </button>
                <?php else: ?>
                  <button class="text-gray-400 dark:text-gray-500 cursor-not-allowed" title="Kategori ini memiliki buku dan tidak dapat dihapus">
                    <i class="lni lni-trash-can"></i>
                  </button>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
          
          <?php if (empty($categories)): ?>
            <div class="col-span-full">
              <div class="dashboard-card bg-white dark:bg-gray-800 p-12 text-center">
                <div class="w-16 h-16 mx-auto bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center text-gray-400 dark:text-gray-500 mb-4">
                  <i class="lni lni-list text-2xl"></i>
                </div>
                <p class="text-gray-500 dark:text-gray-400 text-lg font-medium">Belum ada kategori</p>
                <p class="text-gray-400 dark:text-gray-500 text-sm mt-1">Tambahkan kategori baru untuk mulai</p>
                <button onclick="openAddModal()" class="mt-4 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                  <i class="lni lni-plus mr-2"></i>
                  Tambah Kategori
                </button>
              </div>
            </div>
          <?php endif; ?>
        </div>

        <!-- Categories Table -->
        <div class="dashboard-card bg-white dark:bg-gray-800 overflow-hidden" data-aos="fade-up" data-aos-delay="500">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
              <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ID</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase  class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nama Kategori</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Deskripsi</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Total Buku</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                </tr>
              </thead>
              <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                <?php if (empty($categories)): ?>
                  <tr>
                    <td colspan="5" class="px-6 py-12 text-center">
                      <div class="flex flex-col items-center">
                        <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center text-gray-400 dark:text-gray-500 mb-4">
                          <i class="lni lni-list text-2xl"></i>
                        </div>
                        <p class="text-gray-500 dark:text-gray-400 text-lg font-medium">Belum ada kategori</p>
                        <p class="text-gray-400 dark:text-gray-500 text-sm mt-1">Tambahkan kategori baru untuk mulai</p>
                      </div>
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($categories as $category): ?>
                    <tr>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"><?= $category['id']; ?></td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"><?= htmlspecialchars($category['name']); ?></td>
                      <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                        <div class="line-clamp-2"><?= htmlspecialchars($category['description']); ?></div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400">
                          <?= $category['total_books']; ?> Buku
                        </span>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <button onclick="openEditModal(<?= $category['id']; ?>, '<?= htmlspecialchars(addslashes($category['name'])); ?>', '<?= htmlspecialchars(addslashes($category['description'])); ?>')" class="text-blue-600 dark:text-blue-500 hover:text-blue-900 dark:hover:text-blue-400 mr-3">
                          <i class="lni lni-pencil"></i>
                        </button>
                        <?php if ($category['total_books'] == 0): ?>
                          <button onclick="confirmDelete(<?= $category['id']; ?>, '<?= htmlspecialchars($category['name']); ?>')" class="text-red-600 dark:text-red-500 hover:text-red-900 dark:hover:text-red-400">
                            <i class="lni lni-trash-can"></i>
                          </button>
                        <?php else: ?>
                          <button class="text-gray-400 dark:text-gray-500 cursor-not-allowed" title="Kategori ini memiliki buku dan tidak dapat dihapus">
                            <i class="lni lni-trash-can"></i>
                          </button>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
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

  <!-- Modal Tambah Kategori -->
  <div id="addCategoryModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <div class="fixed inset-0 transition-opacity" aria-hidden="true">
        <div class="absolute inset-0 bg-gray-500 dark:bg-gray-900 opacity-75"></div>
      </div>
      <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
      <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
        <form method="POST" action="">
          <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <div class="sm:flex sm:items-start">
              <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                  Tambah Kategori Baru
                </h3>
                <div class="mt-4 space-y-4">
                  <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nama Kategori <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="name" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary focus:ring-primary dark:bg-gray-700 dark:text-white sm:text-sm" required>
                  </div>
                  <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Deskripsi <span class="text-red-500">*</span></label>
                    <textarea name="description" id="description" rows="4" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary focus:ring-primary dark:bg-gray-700 dark:text-white sm:text-sm" required></textarea>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
            <button type="submit" name="add_category" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary text-base font-medium text-white hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary sm:ml-3 sm:w-auto sm:text-sm">
              Simpan
            </button>
            <button type="button" onclick="closeAddModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
              Batal
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal Edit Kategori -->
  <div id="editCategoryModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <div class="fixed inset-0 transition-opacity" aria-hidden="true">
        <div class="absolute inset-0 bg-gray-500 dark:bg-gray-900 opacity-75"></div>
      </div>
      <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
      <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
        <form method="POST" action="">
          <input type="hidden" name="edit_id" id="edit_id">
          <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <div class="sm:flex sm:items-start">
              <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                  Edit Kategori
                </h3>
                <div class="mt-4 space-y-4">
                  <div>
                    <label for="edit_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nama Kategori <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="edit_name" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary focus:ring-primary dark:bg-gray-700 dark:text-white sm:text-sm" required>
                  </div>
                  <div>
                    <label for="edit_description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Deskripsi <span class="text-red-500">*</span></label>
                    <textarea name="description" id="edit_description" rows="4" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary focus:ring-primary dark:bg-gray-700 dark:text-white sm:text-sm" required></textarea>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
            <button type="submit" name="update_category" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary text-base font-medium text-white hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary sm:ml-3 sm:w-auto sm:text-sm">
              Simpan Perubahan
            </button>
            <button type="button" onclick="closeEditModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
              Batal
            </button>
          </div>
        </form>
      </div>
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
    
    // Modal functions
    function openAddModal() {
      document.getElementById('addCategoryModal').classList.remove('hidden');
    }
    
    function closeAddModal() {
      document.getElementById('addCategoryModal').classList.add('hidden');
    }
    
    function openEditModal(id, name, description) {
      document.getElementById('edit_id').value = id;
      document.getElementById('edit_name').value = name;
      document.getElementById('edit_description').value = description;
      document.getElementById('editCategoryModal').classList.remove('hidden');
    }
    
    function closeEditModal() {
      document.getElementById('editCategoryModal').classList.add('hidden');
    }
    
    function confirmDelete(categoryId, categoryName) {
      Swal.fire({
        title: 'Hapus Kategori?',
        html: `Anda yakin ingin menghapus kategori <strong>${categoryName}</strong>?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
      }).then((result) => {
        if (result.isConfirmed) {
          window.location.href = `?delete=${categoryId}`;
        }
      });
    }
    
    // Chart.js
    const categoryNames = <?= json_encode(array_column($categories, 'name')) ?>;
    const bookCounts = <?= json_encode(array_column($categories, 'total_books')) ?>;
    const backgroundColors = <?= json_encode($colors['background']) ?>;
    const borderColors = <?= json_encode($colors['border']) ?>;
    
    // Bar Chart
    const ctx = document.getElementById('categoryChart').getContext('2d');
    const categoryChart = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: categoryNames,
        datasets: [{
          label: 'Total Buku',
          data: bookCounts,
          backgroundColor: backgroundColors,
          borderColor: borderColors,
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: false,
            labels: {
              color: document.documentElement.classList.contains('dark') ? '#f8fafc' : '#1e293b'
            }
          },
          title: {
            display: true,
            text: 'Total Buku per Kategori',
            color: document.documentElement.classList.contains('dark') ? '#f8fafc' : '#1e293b',
            font: {
              size: 16
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
    
    // Pie Chart
    const ctxPie = document.getElementById('categoryPieChart').getContext('2d');
    const categoryPieChart = new Chart(ctxPie, {
      type: 'pie',
      data: {
        labels: categoryNames,
        datasets: [{
          data: bookCounts,
          backgroundColor: backgroundColors,
          borderColor: borderColors,
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
          },
          title: {
            display: true,
            text: 'Distribusi Buku',
            color: document.documentElement.classList.contains('dark') ? '#f8fafc' : '#1e293b',
            font: {
              size: 16
            }
          }
        }
      }
    });
    
    // Update chart colors when theme changes
    function updateChartColors() {
      const isDark = document.documentElement.classList.contains('dark');
      
      // Update Bar Chart
      categoryChart.options.plugins.title.color = isDark ? '#f8fafc' : '#1e293b';
      categoryChart.options.scales.y.ticks.color = isDark ? '#94a3b8' : '#64748b';
      categoryChart.options.scales.x.ticks.color = isDark ? '#94a3b8' : '#64748b';
      categoryChart.options.scales.y.grid.color = isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.05)';
      categoryChart.options.scales.x.grid.color = isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.05)';
      
      // Update Pie Chart
      categoryPieChart.options.plugins.legend.labels.color = isDark ? '#f8fafc' : '#1e293b';
      categoryPieChart.options.plugins.title.color = isDark ? '#f8fafc' : '#1e293b';
      
      // Update charts
      categoryChart.update();
      categoryPieChart.update();
    }
    
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