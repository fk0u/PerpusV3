<?php
session_start();
include '../includes/db.php';

// Pastikan pengguna sudah login dan memiliki role 'petugas'
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'petugas') {
    header("Location: ../login.php");
    exit();
}

// Tambah Buku
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_book'])) {
    $title = $_POST['title'];
    $author = $_POST['author'];
    $published_year = $_POST['published_year'];
    $isbn = $_POST['isbn'];
    $category_id = $_POST['category_id'];
    $stock = $_POST['stock'];

    $stmt = $pdo->prepare("INSERT INTO books (title, author, published_year, isbn, category_id, stock) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$title, $author, $published_year, $isbn, $category_id, $stock]);
    
    $success_message = 'Buku berhasil ditambahkan.';
}

// Hapus Buku
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM books WHERE id = ?");
    $stmt->execute([$id]);
    
    $success_message = 'Buku berhasil dihapus.';
}

// Edit Buku
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_book'])) {
    $id = $_POST['edit_id'];
    $title = $_POST['title'];
    $author = $_POST['author'];
    $published_year = $_POST['published_year'];
    $isbn = $_POST['isbn'];
    $category_id = $_POST['category_id'];
    $stock = $_POST['stock'];

    $stmt = $pdo->prepare("UPDATE books SET title = ?, author = ?, published_year = ?, isbn = ?, category_id = ?, stock = ? WHERE id = ?");
    $stmt->execute([$title, $author, $published_year, $isbn, $category_id, $stock, $id]);

    $success_message = 'Buku berhasil diperbarui.';
}

// Ambil Data Buku
$stmt = $pdo->query("
    SELECT books.*, category.name AS category_name
    FROM books
    LEFT JOIN category ON books.category_id = category.id
    ORDER BY books.id DESC
");
$books = $stmt->fetchAll();

// Get book for edit if requested
$edit_book = null;
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_book = $stmt->fetch();
}

// Get categories for dropdowns
$stmt = $pdo->query("SELECT id, name FROM category ORDER BY name ASC");
$categories = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kelola Buku - SevenLibrary v6</title>
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
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white" data-aos="fade-up">Kelola Buku</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400" data-aos="fade-up" data-aos-delay="100">
              Tambah, edit, dan hapus data buku perpustakaan
            </p>
          </div>
          <div class="mt-4 md:mt-0" data-aos="fade-up" data-aos-delay="200">
            <button type="button" onclick="openAddModal()" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
              <i class="lni lni-plus mr-2"></i>
              Tambah Buku
            </button>
          </div>
        </div>

        <!-- Filters and Search -->
        <div class="dashboard-card bg-white dark:bg-gray-800 p-4 mb-6" data-aos="fade-up" data-aos-delay="300">
          <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex flex-col sm:flex-row gap-4">
              <div>
                <label for="category-filter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Filter Kategori</label>
                <select id="category-filter" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary focus:ring-primary dark:bg-gray-700 dark:text-white sm:text-sm">
                  <option value="all">Semua Kategori</option>
                  <?php foreach ($categories as $category): ?>
                    <option value="<?= $category['id']; ?>"><?= htmlspecialchars($category['name']); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div>
                <label for="stock-filter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Filter Stok</label>
                <select id="stock-filter" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary focus:ring-primary dark:bg-gray-700 dark:text-white sm:text-sm">
                  <option value="all">Semua Stok</option>
                  <option value="available">Tersedia</option>
                  <option value="low">Stok Menipis (< 5)</option>
                  <option value="out">Habis</option>
                </select>
              </div>
            </div>
            <div class="w-full md:w-64">
              <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Cari</label>
              <div class="relative rounded-md shadow-sm">
                <input type="text" id="search" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary focus:ring-primary dark:bg-gray-700 dark:text-white sm:text-sm pl-10" placeholder="Cari judul, penulis, atau ISBN...">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <i class="lni lni-search text-gray-400"></i>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Books Table -->
        <div class="dashboard-card bg-white dark:bg-gray-800 overflow-hidden" data-aos="fade-up" data-aos-delay="400">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
              <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ID</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Judul Buku</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Kategori</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Penulis</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tahun</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ISBN</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Stok</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                </tr>
              </thead>
              <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700" id="books-table-body">
                <?php if (empty($books)): ?>
                  <tr>
                    <td colspan="8" class="px-6 py-12 text-center">
                      <div class="flex flex-col items-center">
                        <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center text-gray-400 dark:text-gray-500 mb-4">
                          <i class="lni lni-book text-2xl"></i>
                        </div>
                        <p class="text-gray-500 dark:text-gray-400 text-lg font-medium">Belum ada data buku</p>
                        <p class="text-gray-400 dark:text-gray-500 text-sm mt-1">Tambahkan buku baru untuk mulai</p>
                      </div>
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($books as $book): ?>
                    <?php 
                      $stock_class = '';
                      
                      if ($book['stock'] <= 0) {
                        $stock_class = 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400';
                      } elseif ($book['stock'] < 5) {
                        $stock_class = 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400';
                      } else {
                        $stock_class = 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400';
                      }
                    ?>
                    <tr class="book-row" 
                        data-category="<?= $book['category_id']; ?>" 
                        data-stock="<?= $book['stock']; ?>"
                        data-title="<?= htmlspecialchars($book['title']); ?>"
                        data-author="<?= htmlspecialchars($book['author']); ?>"
                        data-isbn="<?= htmlspecialchars($book['isbn']); ?>">
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"><?= $book['id']; ?></td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"><?= htmlspecialchars($book['title']); ?></td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400">
                          <?= htmlspecialchars($book['category_name']); ?>
                        </span>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"><?= htmlspecialchars($book['author']); ?></td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400"><?= htmlspecialchars($book['published_year']); ?></td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400"><?= htmlspecialchars($book['isbn']); ?></td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $stock_class ?>">
                          <?= $book['stock']; ?>
                        </span>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <button onclick="openEditModal(<?= $book['id']; ?>)" class="text-blue-600 dark:text-blue-500 hover:text-blue-900 dark:hover:text-blue-400 mr-3">
                          <i class="lni lni-pencil"></i>
                        </button>
                        <button onclick="confirmDelete(<?= $book['id']; ?>, '<?= htmlspecialchars($book['title']); ?>')" class="text-red-600 dark:text-red-500 hover:text-red-900 dark:hover:text-red-400">
                          <i class="lni lni-trash-can"></i>
                        </button>
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
                  Menampilkan <span class="font-medium"><?= count($books) ?></span> dari <span class="font-medium"><?= count($books) ?></span> data
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

  <!-- Modal Tambah Buku -->
  <div id="addBookModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
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
                  Tambah Buku Baru
                </h3>
                <div class="mt-4 space-y-4">
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Judul Buku <span class="text-red-500">*</span></label>
                      <input type="text" name="title" id="title" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary focus:ring-primary dark:bg-gray-700 dark:text-white sm:text-sm" required>
                    </div>
                    <div>
                      <label for="category_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Kategori <span class="text-red-500">*</span></label>
                      <select name="category_id" id="category_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary focus:ring-primary dark:bg-gray-700 dark:text-white sm:text-sm" required>
                        <?php foreach ($categories as $category): ?>
                          <option value="<?= $category['id']; ?>"><?= htmlspecialchars($category['name']); ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>
                  <div>
                    <label for="author" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Penulis <span class="text-red-500">*</span></label>
                    <input type="text" name="author" id="author" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary focus:ring-primary dark:bg-gray-700 dark:text-white sm:text-sm" required>
                  </div>
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <label for="published_year" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tahun Terbit <span class="text-red-500">*</span></label>
                      <input type="number" name="published_year" id="published_year" min="1900" max="<?= date('Y'); ?>" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary focus:ring-primary dark:bg-gray-700 dark:text-white sm:text-sm" required>
                    </div>
                    <div>
                      <label for="isbn" class="block text-sm font-medium text-gray-700 dark:text-gray-300">ISBN <span class="text-red-500">*</span></label>
                      <input type="text" name="isbn" id="isbn" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary focus:ring-primary dark:bg-gray-700 dark:text-white sm:text-sm" required>
                    </div>
                  </div>
                  <div>
                    <label for="stock" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Stok Buku <span class="text-red-500">*</span></label>
                    <input type="number" name="stock" id="stock" min="0" value="1" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary focus:ring-primary dark:bg-gray-700 dark:text-white sm:text-sm" required>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
            <button type="submit" name="add_book" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary text-base font-medium text-white hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary sm:ml-3 sm:w-auto sm:text-sm">
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

  <!-- Modal Edit Buku -->
  <div id="editBookModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
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
                  Edit Buku
                </h3>
                <div class="mt-4 space-y-4">
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <label for="edit_title" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Judul Buku <span class="text-red-500">*</span></label>
                      <input type="text" name="title" id="edit_title" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary focus:ring-primary dark:bg-gray-700 dark:text-white sm:text-sm" required>
                    </div>
                    <div>
                      <label for="edit_category_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Kategori <span class="text-red-500">*</span></label>
                      <select name="category_id" id="edit_category_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary focus:ring-primary dark:bg-gray-700 dark:text-white sm:text-sm" required>
                        <?php foreach ($categories as $category): ?>
                          <option value="<?= $category['id']; ?>"><?= htmlspecialchars($category['name']); ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>
                  <div>
                    <label for="edit_author" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Penulis <span class="text-red-500">*</span></label>
                    <input type="text" name="author" id="edit_author" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary focus:ring-primary dark:bg-gray-700 dark:text-white sm:text-sm" required>
                  </div>
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <label for="edit_published_year" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tahun Terbit <span class="text-red-500">*</span></label>
                      <input type="number" name="published_year" id="edit_published_year" min="1900" max="<?= date('Y'); ?>" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary focus:ring-primary dark:bg-gray-700 dark:text-white sm:text-sm" required>
                    </div>
                    <div>
                      <label for="edit_isbn" class="block text-sm font-medium text-gray-700 dark:text-gray-300">ISBN <span class="text-red-500">*</span></label>
                      <input type="text" name="isbn" id="edit_isbn" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary focus:ring-primary dark:bg-gray-700 dark:text-white sm:text-sm" required>
                    </div>
                  </div>
                  <div>
                    <label for="edit_stock" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Stok Buku <span class="text-red-500">*</span></label>
                    <input type="number" name="stock" id="edit_stock" min="0" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary focus:ring-primary dark:bg-gray-700 dark:text-white sm:text-sm" required>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
            <button type="submit" name="update_book" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary text-base font-medium text-white hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary sm:ml-3 sm:w-auto sm:text-sm">
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
    });
    
    // Modal functions
    function openAddModal() {
      document.getElementById('addBookModal').classList.remove('hidden');
    }
    
    function closeAddModal() {
      document.getElementById('addBookModal').classList.add('hidden');
    }
    
    function openEditModal(bookId) {
      // Fetch book data via AJAX or use the data attributes
      const bookRow = document.querySelector(`.book-row[data-id="${bookId}"]`);
      
      // For this example, we'll use a direct fetch to the server
      fetch(`get_book.php?id=${bookId}`)
        .then(response => response.json())
        .then(book => {
          document.getElementById('edit_id').value = book.id;
          document.getElementById('edit_title').value = book.title;
          document.getElementById('edit_category_id').value = book.category_id;
          document.getElementById('edit_author').value = book.author;
          document.getElementById('edit_published_year').value = book.published_year;
          document.getElementById('edit_isbn').value = book.isbn;
          document.getElementById('edit_stock').value = book.stock;
          
          document.getElementById('editBookModal').classList.remove('hidden');
        })
        .catch(error => {
          console.error('Error fetching book data:', error);
          // Fallback to using data attributes if AJAX fails
          if (bookRow) {
            document.getElementById('edit_id').value = bookId;
            document.getElementById('edit_title').value = bookRow.getAttribute('data-title');
            // Set other fields from data attributes
          }
          
          document.getElementById('editBookModal').classList.remove('hidden');
        });
    }
    
    function closeEditModal() {
      document.getElementById('editBookModal').classList.add('hidden');
    }
    
    function confirmDelete(bookId, bookTitle) {
      Swal.fire({
        title: 'Hapus Buku?',
        html: `Anda yakin ingin menghapus buku <strong>${bookTitle}</strong>?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
      }).then((result) => {
        if (result.isConfirmed) {
          window.location.href = `?delete=${bookId}`;
        }
      });
    }
    
    // Filter functionality
    document.addEventListener('DOMContentLoaded', function() {
      const categoryFilter = document.getElementById('category-filter');
      const stockFilter = document.getElementById('stock-filter');
      const searchInput = document.getElementById('search');
      const bookRows = document.querySelectorAll('.book-row');
      
      function filterTable() {
        const categoryValue = categoryFilter.value;
        const stockValue = stockFilter.value;
        const searchValue = searchInput.value.toLowerCase();
        
        bookRows.forEach(row => {
          const category = row.getAttribute('data-category');
          const stock = parseInt(row.getAttribute('data-stock'));
          const title = row.getAttribute('data-title').toLowerCase();
          const author = row.getAttribute('data-author').toLowerCase();
          const isbn = row.getAttribute('data-isbn').toLowerCase();
          
          // Category filter
          let showByCategory = true;
          if (categoryValue !== 'all') {
            showByCategory = category === categoryValue;
          }
          
          // Stock filter
          let showByStock = true;
          if (stockValue === 'available') {
            showByStock = stock > 0;
          } else if (stockValue === 'low') {
            showByStock = stock > 0 && stock < 5;
          } else if (stockValue === 'out') {
            showByStock = stock <= 0;
          }
          
          // Search filter
          let showBySearch = searchValue === '' || 
                            title.includes(searchValue) || 
                            author.includes(searchValue) || 
                            isbn.includes(searchValue);
          
          // Show/hide row
          if (showByCategory && showByStock && showBySearch) {
            row.style.display = '';
          } else {
            row.style.display = 'none';
          }
        });
      }
      
      categoryFilter.addEventListener('change', filterTable);
      stockFilter.addEventListener('change', filterTable);
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