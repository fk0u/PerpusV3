<?php
session_start();
include '../includes/db.php';

// Pastikan pengguna sudah login dan memiliki role 'admin'
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Tambah Pengguna
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $full_name = $_POST['full_name'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    $class = $_POST['class'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    // Check if username or email already exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    $exists = $stmt->fetchColumn();

    if ($exists) {
        $error_message = 'Username atau email sudah digunakan.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (full_name, username, email, role, class, password) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$full_name, $username, $email, $role, $class, $password]);
        
        $success_message = 'Pengguna berhasil ditambahkan.';
    }
}

// Hapus Pengguna
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Check if user has active loans
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM borrow_history WHERE user_id = ? AND status = 'dipinjam'");
    $stmt->execute([$id]);
    $active_loans = $stmt->fetchColumn();
    
    if ($active_loans > 0) {
        $error_message = 'Pengguna tidak dapat dihapus karena masih memiliki peminjaman aktif.';
    } else {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
        
        $success_message = 'Pengguna berhasil dihapus.';
    }
}

// Edit Pengguna
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $id = $_POST['edit_id'];
    $full_name = $_POST['full_name'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    $class = $_POST['class'];
    
    // Check if username or email already exists for other users
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE (username = ? OR email = ?) AND id != ?");
    $stmt->execute([$username, $email, $id]);
    $exists = $stmt->fetchColumn();
    
    if ($exists) {
        $error_message = 'Username atau email sudah digunakan oleh pengguna lain.';
    } else {
        // If password is provided, update it too
        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, username = ?, email = ?, role = ?, class = ?, password = ? WHERE id = ?");
            $stmt->execute([$full_name, $username, $email, $role, $class, $password, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, username = ?, email = ?, role = ?, class = ? WHERE id = ?");
            $stmt->execute([$full_name, $username, $email, $role, $class, $id]);
        }
        
        $success_message = 'Pengguna berhasil diperbarui.';
    }
}

// Ambil Data Pengguna
$stmt = $pdo->query("SELECT * FROM users ORDER BY id DESC");
$users = $stmt->fetchAll();

// Get user statistics
$stmt = $pdo->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
$role_stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Get user for edit if requested
$edit_user = null;
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_user = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kelola Pengguna - SevenLibrary v6</title>
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
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white" data-aos="fade-up">Kelola Pengguna</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400" data-aos="fade-up" data-aos-delay="100">
              Tambah, edit, dan hapus data pengguna perpustakaan
            </p>
          </div>
          <div class="mt-4 md:mt-0" data-aos="fade-up" data-aos-delay="200">
            <button type="button" onclick="openAddModal()" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
              <i class="lni lni-plus mr-2"></i>
              Tambah Pengguna
            </button>
          </div>
        </div>

        <!-- User Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6" data-aos="fade-up" data-aos-delay="300">
          <div class="dashboard-card bg-white dark:bg-gray-800 p-6">
            <div class="flex items-center">
              <div class="flex-shrink-0 w-12 h-12 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center text-red-500 dark:text-red-400">
                <i class="lni lni-shield text-xl"></i>
              </div>
              <div class="ml-4">
                <h2 class="text-sm font-medium text-gray-500 dark:text-gray-400">Admin</h2>
                <p class="text-3xl font-bold text-gray-900 dark:text-white"><?= isset($role_stats['admin']) ? $role_stats['admin'] : 0; ?></p>
              </div>
            </div>
          </div>
          
          <div class="dashboard-card bg-white dark:bg-gray-800 p-6">
            <div class="flex items-center">
              <div class="flex-shrink-0 w-12 h-12 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center text-green-500 dark:text-green-400">
                <i class="lni lni-graduation text-xl"></i>
              </div>
              <div class="ml-4">
                <h2 class="text-sm font-medium text-gray-500 dark:text-gray-400">Siswa</h2>
                <p class="text-3xl font-bold text-gray-900 dark:text-white"><?= isset($role_stats['student']) ? $role_stats['student'] : 0; ?></p>
              </div>
            </div>
          </div>
          
          <div class="dashboard-card bg-white dark:bg-gray-800 p-6">
            <div class="flex items-center">
              <div class="flex-shrink-0 w-12 h-12 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-500 dark:text-blue-400">
                <i class="lni lni-user text-xl"></i>
              </div>
              <div class="ml-4">
                <h2 class="text-sm font-medium text-gray-500 dark:text-gray-400">Petugas</h2>
                <p class="text-3xl font-bold text-gray-900 dark:text-white"><?= isset($role_stats['petugas']) ? $role_stats['petugas'] : 0; ?></p>
              </div>
            </div>
          </div>
        </div>

        <!-- Chart Visualization -->
        <div class="dashboard-card bg-white dark:bg-gray-800 p-6 mb-6" data-aos="fade-up" data-aos-delay="400">
          <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Distribusi Pengguna</h2>
          <div class="h-64">
            <canvas id="userChart"></canvas>
          </div>
        </div>

        <!-- Filters and Search -->
        <div class="dashboard-card bg-white dark:bg-gray-800 p-4 mb-6" data-aos="fade-up" data-aos-delay="500">
          <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
              <label for="role-filter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Filter Role</label>
              <select id="role-filter" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary focus:ring-primary dark:bg-gray-700 dark:text-white sm:text-sm">
                <option value="all">Semua Role</option>
                <option value="admin">Admin</option>
                <option value="student">Siswa</option>
                <option value="petugas">Petugas</option>
              </select>
            </div>
            <div class="w-full md:w-64">
              <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Cari</label>
              <div class="relative rounded-md shadow-sm">
                <input type="text" id="search" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary focus:ring-primary dark:bg-gray-700 dark:text-white sm:text-sm pl-10" placeholder="Cari nama, username, atau email...">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <i class="lni lni-search text-gray-400"></i>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Users Table -->
        <div class="dashboard-card bg-white dark:bg-gray-800 overflow-hidden" data-aos="fade-up" data-aos-delay="600">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
              <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ID</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nama Lengkap</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Username</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Email</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Role</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Kelas</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                </tr>
              </thead>
              <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700" id="users-table-body">
                <?php if (empty($users)): ?>
                  <tr>
                    <td colspan="7" class="px-6 py-12 text-center">
                      <div class="flex flex-col items-center">
                        <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center text-gray-400 dark:text-gray-500 mb-4">
                          <i class="lni lni-users text-2xl"></i>
                        </div>
                        <p class="text-gray-500 dark:text-gray-400 text-lg font-medium">Belum ada data pengguna</p>
                        <p class="text-gray-400 dark:text-gray-500 text-sm mt-1">Tambahkan pengguna baru untuk mulai</p>
                      </div>
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($users as $user): ?>
                    <?php 
                      $role_class = '';
                      $role_icon = '';
                      
                      switch ($user['role']) {
                        case 'admin':
                          $role_class = 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400';
                          $role_icon = 'lni-shield';
                          break;
                        case 'student':
                          $role_class = 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400';
                          $role_icon = 'lni-graduation';
                          break;
                        case 'petugas':
                          $role_class = 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400';
                          $role_icon = 'lni-user';
                          break;
                      }
                    ?>
                    <tr class="user-row" 
                        data-role="<?= $user['role']; ?>" 
                        data-name="<?= htmlspecialchars($user['full_name']); ?>"
                        data-username="<?= htmlspecialchars($user['username']); ?>"
                        data-email="<?= htmlspecialchars($user['email']); ?>">
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"><?= $user['id']; ?></td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"><?= htmlspecialchars($user['full_name']); ?></td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"><?= htmlspecialchars($user['username']); ?></td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400"><?= htmlspecialchars($user['email']); ?></td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $role_class ?>">
                          <i class="<?= $role_icon ?> mr-1"></i>
                          <?= ucfirst($user['role']); ?>
                        </span>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400"><?= htmlspecialchars($user['class']); ?></td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <button onclick="openEditModal(<?= $user['id']; ?>)" class="text-blue-600 dark:text-blue-500 hover:text-blue-900 dark:hover:text-blue-400 mr-3">
                          <i class="lni lni-pencil"></i>
                        </button>
                        <button onclick="confirmDelete(<?= $user['id']; ?>, '<?= htmlspecialchars($user['full_name']); ?>')" class="text-red-600 dark:text-red-500 hover:text-red-900 dark:hover:text-red-400">
                          <i class="lni lni-trash-can"></i>
                        </button>
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

  <!-- Modal Tambah Pengguna -->
  <div id="addUserModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
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
                  Tambah Pengguna Baru
                </h3>
                <div class="mt-4 space-y-4">
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <label for="full_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nama Lengkap <span class="text-red-500">*</span></label>
                      <input type="text" name="full_name" id="full_name" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary focus:ring-primary dark:bg-gray-700 dark:text-white sm:text-sm" required>
                    </div>
                    <div>
                      <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Username <span class="text-red-500">*</span></label>
                      <input type="text" name="username" id="username" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary focus:ring-primary dark:bg-gray-700 dark:text-white sm:text-sm" required>
                    </div>
                  </div>
                  <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email <span class="text-red-500">*</span></label>
                    <input type="email" name="email" id="email" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary focus:ring-primary dark:bg-gray-700 dark:text-white sm:text-sm" required>
                  </div>
                  <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Password <span class="text-red-500">*</span></label>
                    <div class="relative mt-1 rounded-md shadow-sm">
                      <input type="password" name="password" id="password" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary focus:ring-primary dark:bg-gray-700 dark:text-white sm:text-sm pr-10" required>
                      <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                        <button type="button" class="toggle-password text-gray-400 hover:text-gray-500 dark:text-gray-500 dark:hover:text-gray-400 focus:outline-none" data-target="password">
                          <i class="lni lni-eye"></i>
                        </button>
                      </div>
                    </div>
                  </div>
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <label for="role" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Role <span class="text-red-500">*</span></label>
                      <select name="role" id="role" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary focus:ring-primary dark:bg-gray-700 dark:text-white sm:text-sm" required>
                        <option value="admin">Admin</option>
                        <option value="student" selected>Siswa</option>
                        <option value="petugas">Petugas</option>
                      </select>
                    </div>
                    <div>
                      <label for="class" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Kelas <span class="text-red-500">*</span></label>
                      <input type="text" name="class" id="class" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary focus:ring-primary dark:bg-gray-700 dark:text-white sm:text-sm" required>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
            <button type="submit" name="add_user" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary text-base font-medium text-white hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary sm:ml-3 sm:w-auto sm:text-sm">
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

  <!-- Modal Edit Pengguna -->
  <div id="editUserModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
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
                  Edit Pengguna
                </h3>
                <div class="mt-4 space-y-4">
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <label for="edit_full_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nama Lengkap <span class="text-red-500">*</span></label>
                      <input type="text" name="full_name" id="edit_full_name" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary focus:ring-primary dark:bg-gray-700 dark:text-white sm:text-sm" required>
                    </div>
                    <div>
                      <label for="edit_username" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Username <span class="text-red-500">*</span></label>
                      <input type="text" name="username" id="edit_username" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary focus:ring-primary dark:bg-gray-700 dark:text-white sm:text-sm" required>
                    </div>
                  </div>
                  <div>
                    <label for="edit_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email <span class="text-red-500">*</span></label>
                    <input type="email" name="email" id="edit_email" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary focus:ring-primary dark:bg-gray-700 dark:text-white sm:text-sm" required>
                  </div>
                  <div>
                    <label for="edit_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Password <small class="text-gray-500 dark:text-gray-400">(Kosongkan jika tidak ingin mengubah)</small></label>
                    <div class="relative mt-1 rounded-md shadow-sm">
                      <input type="password" name="password" id="edit_password" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary focus:ring-primary dark:bg-gray-700 dark:text-white sm:text-sm pr-10">
                      <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                        <button type="button" class="toggle-password text-gray-400 hover:text-gray-500 dark:text-gray-500 dark:hover:text-gray-400 focus:outline-none" data-target="edit_password">
                          <i class="lni lni-eye"></i>
                        </button>
                      </div>
                    </div>
                  </div>
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <label for="edit_role" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Role <span class="text-red-500">*</span></label>
                      <select name="role" id="edit_role" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary focus:ring-primary dark:bg-gray-700 dark:text-white sm:text-sm" required>
                        <option value="admin">Admin</option>
                        <option value="student">Siswa</option>
                        <option value="petugas">Petugas</option>
                      </select>
                    </div>
                    <div>
                      <label for="edit_class" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Kelas <span class="text-red-500">*</span></label>
                      <input type="text" name="class" id="edit_class" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary focus:ring-primary dark:bg-gray-700 dark:text-white sm:text-sm" required>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
            <button type="submit" name="update_user" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary text-base font-medium text-white hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary sm:ml-3 sm:w-auto sm:text-sm">
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
      
      // Password toggle
      const toggleButtons = document.querySelectorAll('.toggle-password');
      toggleButtons.forEach(button => {
        button.addEventListener('click', function() {
          const targetId = this.getAttribute('data-target');
          const passwordInput = document.getElementById(targetId);
          const icon = this.querySelector('i');
          
          if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            icon.classList.remove('lni-eye');
            icon.classList.add('lni-eye-alt');
          } else {
            passwordInput.type = 'password';
            icon.classList.remove('lni-eye-alt');
            icon.classList.add('lni-eye');
          }
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
      
      // Update chart colors
      updateChartColors();
    });
    
    // Modal functions
    function openAddModal() {
      document.getElementById('addUserModal').classList.remove('hidden');
    }
    
    function closeAddModal() {
      document.getElementById('addUserModal').classList.add('hidden');
    }
    
    function openEditModal(userId) {
      // Fetch user data via AJAX or use the data attributes
      fetch(`get_user.php?id=${userId}`)
        .then(response => response.json())
        .then(user => {
          document.getElementById('edit_id').value = user.id;
          document.getElementById('edit_full_name').value = user.full_name;
          document.getElementById('edit_username').value = user.username;
          document.getElementById('edit_email').value = user.email;
          document.getElementById('edit_role').value = user.role;
          document.getElementById('edit_class').value = user.class;
          
          document.getElementById('editUserModal').classList.remove('hidden');
        })
        .catch(error => {
          console.error('Error fetching user data:', error);
          // Fallback to using data attributes if AJAX fails
          const userRow = document.querySelector(`.user-row[data-id="${userId}"]`);
          if (userRow) {
            document.getElementById('edit_id').value = userId;
            document.getElementById('edit_full_name').value = userRow.getAttribute('data-name');
            document.getElementById('edit_username').value = userRow.getAttribute('data-username');
            document.getElementById('edit_email').value = userRow.getAttribute('data-email');
            document.getElementById('edit_role').value = userRow.getAttribute('data-role');
            document.getElementById('edit_class').value = userRow.getAttribute('data-class') || '';
          }
          
          document.getElementById('editUserModal').classList.remove('hidden');
        });
    }
    
    function closeEditModal() {
      document.getElementById('editUserModal').classList.add('hidden');
    }
    
    function confirmDelete(userId, userName) {
      Swal.fire({
        title: 'Hapus Pengguna?',
        html: `Anda yakin ingin menghapus pengguna <strong>${userName}</strong>?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
      }).then((result) => {
        if (result.isConfirmed) {
          window.location.href = `?delete=${userId}`;
        }
      });
    }
    
    // Filter functionality
    document.addEventListener('DOMContentLoaded', function() {
      const roleFilter = document.getElementById('role-filter');
      const searchInput = document.getElementById('search');
      const userRows = document.querySelectorAll('.user-row');
      
      function filterTable() {
        const roleValue = roleFilter.value;
        const searchValue = searchInput.value.toLowerCase();
        
        userRows.forEach(row => {
          const role = row.getAttribute('data-role');
          const name = row.getAttribute('data-name').toLowerCase();
          const username = row.getAttribute('data-username').toLowerCase();
          const email = row.getAttribute('data-email').toLowerCase();
          
          // Role filter
          let showByRole = true;
          if (roleValue !== 'all') {
            showByRole = role === roleValue;
          }
          
          // Search filter
          let showBySearch = searchValue === '' || 
                            name.includes(searchValue) || 
                            username.includes(searchValue) || 
                            email.includes(searchValue);
          
          // Show/hide row
          if (showByRole && showBySearch) {
            row.style.display = '';
          } else {
            row.style.display = 'none';
          }
        });
      }
      
      roleFilter.addEventListener('change', filterTable);
      searchInput.addEventListener('input', filterTable);
    });
    
    // Chart.js
    const roleLabels = ['Admin', 'Siswa', 'Petugas'];
    const roleCounts = [
      <?= isset($role_stats['admin']) ? $role_stats['admin'] : 0; ?>,
      <?= isset($role_stats['student']) ? $role_stats['student'] : 0; ?>,
      <?= isset($role_stats['petugas']) ? $role_stats['petugas'] : 0; ?>
    ];
    const roleColors = [
      'rgba(239, 68, 68, 0.6)', // Red for Admin
      'rgba(16, 185, 129, 0.6)', // Green for Siswa
      'rgba(10, 102, 244, 0.6)' // Blue for Petugas
    ];
    const roleBorderColors = [
      'rgba(239, 68, 68, 1)',
      'rgba(16, 185, 129, 1)',
      'rgba(10, 102, 244, 1)'
    ];
    
    const ctx = document.getElementById('userChart').getContext('2d');
    const userChart = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: roleLabels,
        datasets: [{
          label: 'Jumlah Pengguna',
          data: roleCounts,
          backgroundColor: roleColors,
          borderColor: roleBorderColors,
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
            text: 'Jumlah Pengguna per Role',
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
    
    // Update chart colors when theme changes
    function updateChartColors() {
      const isDark = document.documentElement.classList.contains('dark');
      
      userChart.options.plugins.title.color = isDark ? '#f8fafc' : '#1e293b';
      userChart.options.scales.y.ticks.color = isDark ? '#94a3b8' : '#64748b';
      userChart.options.scales.x.ticks.color = isDark ? '#94a3b8' : '#64748b';
      userChart.options.scales.y.grid.color = isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.05)';
      userChart.options.scales.x.grid.color = isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.05)';
      
      userChart.update();
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