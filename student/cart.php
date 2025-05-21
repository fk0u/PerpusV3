<?php
session_start();
include '../includes/db.php';

// Pastikan pengguna sudah login dan memiliki role 'student'
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Add book to cart
if (isset($_GET['add_to_cart']) && !empty($_GET['add_to_cart'])) {
    $book_id = $_GET['add_to_cart'];
    
    // Check if book exists and has stock
    $stmt = $pdo->prepare("SELECT id, title, stock FROM books WHERE id = ? AND stock > 0");
    $stmt->execute([$book_id]);
    $book = $stmt->fetch();
    
    if ($book) {
        // Check if book is already in cart
        $stmt = $pdo->prepare("SELECT id FROM borrow_cart WHERE user_id = ? AND book_id = ?");
        $stmt->execute([$user_id, $book_id]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            $error_message = 'Buku "' . htmlspecialchars($book['title']) . '" sudah ada di keranjang peminjaman Anda.';
        } else {
            // Add to cart
            $stmt = $pdo->prepare("INSERT INTO borrow_cart (user_id, book_id) VALUES (?, ?)");
            $stmt->execute([$user_id, $book_id]);
            $success_message = 'Buku "' . htmlspecialchars($book['title']) . '" berhasil ditambahkan ke keranjang.';
        }
    } else {
        $error_message = 'Buku tidak tersedia atau stok habis.';
    }
}

// Remove book from cart
if (isset($_GET['remove_from_cart']) && !empty($_GET['remove_from_cart'])) {
    $cart_id = $_GET['remove_from_cart'];
    
    // Verify cart item belongs to user
    $stmt = $pdo->prepare("DELETE FROM borrow_cart WHERE id = ? AND user_id = ?");
    $stmt->execute([$cart_id, $user_id]);
    
    if ($stmt->rowCount() > 0) {
        $success_message = 'Buku berhasil dihapus dari keranjang.';
    } else {
        $error_message = 'Gagal menghapus buku dari keranjang.';
    }
}

// Process borrowing (checkout)
if (isset($_POST['checkout'])) {
    // Get all books in cart
    $stmt = $pdo->prepare("
        SELECT bc.id as cart_id, bc.book_id, b.title, b.stock
        FROM borrow_cart bc
        JOIN books b ON bc.book_id = b.id
        WHERE bc.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $cart_items = $stmt->fetchAll();
    
    if (count($cart_items) > 0) {
        // Check if user has reached maximum allowed books
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as active_loans
            FROM borrow_history
            WHERE user_id = ? AND status = 'dipinjam'
        ");
        $stmt->execute([$user_id]);
        $active_loans = $stmt->fetch()['active_loans'];
        
        $max_allowed_books = 5; // Maximum books a user can borrow at once
        
        if ($active_loans + count($cart_items) > $max_allowed_books) {
            $error_message = 'Anda hanya dapat meminjam maksimal ' . $max_allowed_books . ' buku. Anda sudah meminjam ' . $active_loans . ' buku.';
        } else {
            // Begin transaction
            $pdo->beginTransaction();
            
            try {
                $borrow_date = date('Y-m-d');
                $return_date = date('Y-m-d', strtotime('+7 days')); // Default 7 days loan period
                
                // Process each book in cart
                foreach ($cart_items as $item) {
                    // Check if book still has stock
                    if ($item['stock'] <= 0) {
                        throw new Exception('Buku "' . htmlspecialchars($item['title']) . '" tidak tersedia lagi.');
                    }
                    
                    // Generate unique code for borrowing
                    $unique_code = uniqid('borrow_', true);
                    
                    // Insert into borrow_history
                    $stmt = $pdo->prepare("
                        INSERT INTO borrow_history (user_id, book_id, borrow_date, return_date, unique_code, status)
                        VALUES (?, ?, ?, ?, ?, 'dipinjam')
                    ");
                    $stmt->execute([$user_id, $item['book_id'], $borrow_date, $return_date, $unique_code]);
                    
                    // Decrease book stock
                    $stmt = $pdo->prepare("UPDATE books SET stock = stock - 1 WHERE id = ?");
                    $stmt->execute([$item['book_id']]);
                    
                    // Remove from cart
                    $stmt = $pdo->prepare("DELETE FROM borrow_cart WHERE id = ?");
                    $stmt->execute([$item['cart_id']]);
                }
                
                // Commit transaction
                $pdo->commit();
                $success_message = 'Peminjaman buku berhasil diproses. Silakan ambil buku di perpustakaan.';
                
                // Redirect to history page
                header("Location: dashboard.php?tab=riwayat&success=1");
                exit();
            } catch (Exception $e) {
                // Rollback transaction on error
                $pdo->rollBack();
                $error_message = 'Terjadi kesalahan saat memproses peminjaman: ' . $e->getMessage();
            }
        }
    } else {
        $error_message = 'Keranjang peminjaman kosong.';
    }
}

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

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Keranjang Peminjaman - SevenLibrary v6</title>
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
    
    /* Book card */
    .book-card {
      transition: transform 0.3s ease;
    }
    
    .book-card:hover {
      transform: translateY(-8px);
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
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
          <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white" data-aos="fade-up">Keranjang Peminjaman</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400" data-aos="fade-up" data-aos-delay="100">
              Kelola buku yang ingin Anda pinjam
            </p>
          </div>
          <div class="mt-4 md:mt-0" data-aos="fade-up" data-aos-delay="200">
            <a href="dashboard.php?tab=daftar-buku" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
              <i class="lni lni-book mr-2"></i>
              Tambah Buku Lainnya
            </a>
          </div>
        </div>

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

        <?php if (!empty($error_message)): ?>
          <div class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 p-4 mb-6" data-aos="fade-up">
            <div class="flex">
              <div class="flex-shrink-0">
                <i class="lni lni-warning text-red-500 dark:text-red-400"></i>
              </div>
              <div class="ml-3">
                <p class="text-sm text-red-700 dark:text-red-300"><?= htmlspecialchars($error_message); ?></p>
              </div>
            </div>
          </div>
        <?php endif; ?>

        <!-- Cart Items -->
        <div class="dashboard-card bg-white dark:bg-gray-800 overflow-hidden" data-aos="fade-up" data-aos-delay="300">
          <?php if (count($cart_items) > 0): ?>
            <div class="p-6">
              <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Buku dalam Keranjang (<?= count($cart_items); ?>)</h2>
              
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
                    <a href="?remove_from_cart=<?= $item['cart_id']; ?>" class="inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md text-red-700 dark:text-red-300 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/30 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
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
                  <form method="POST" action="">
                    <button type="submit" name="checkout" class="w-full sm:w-auto px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-primary hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                      Proses Peminjaman
                    </button>
                  </form>
                </div>
              </div>
            </div>
          <?php else: ?>
            <div class="p-6 text-center">
              <div class="w-20 h-20 mx-auto bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center text-gray-400 dark:text-gray-500 mb-4">
                <i class="lni lni-cart text-3xl"></i>
              </div>
              <h2 class="text-xl font-medium text-gray-900 dark:text-white mb-2">Keranjang Kosong</h2>
              <p class="text-gray-500 dark:text-gray-400 mb-6">Anda belum menambahkan buku ke keranjang peminjaman.</p>
              <a href="dashboard.php?tab=daftar-buku" class="inline-flex items-center px-4 py-2 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-primary hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                <i class="lni lni-book mr-2"></i> Jelajahi Buku
              </a>
            </div>
          <?php endif; ?>
        </div>
        
        <!-- Borrowing Guidelines -->
        <div class="dashboard-card bg-white dark:bg-gray-800 p-6 mt-6" data-aos="fade-up" data-aos-delay="400">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Panduan Peminjaman</h2>
          
          <div class="space-y-4">
            <div class="flex">
              <div class="flex-shrink-0">
                <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-500 dark:text-blue-400">
                  <span class="text-sm font-medium">1</span>
                </div>
              </div>
              <div class="ml-4">
                <h3 class="text-base font-medium text-gray-900 dark:text-white">Pilih Buku</h3>
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
                <h3 class="text-base font-medium text-gray-900 dark:text-white">Proses Peminjaman</h3>
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
                <h3 class="text-base font-medium text-gray-900 dark:text-white">Ambil Buku</h3>
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
                <h3 class="text-base font-medium text-gray-900 dark:text-white">Kembalikan Tepat Waktu</h3>
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
                <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-300">Perhatian</h3>
                <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-200">
                  <p>Anda hanya dapat meminjam maksimal 5 buku dalam satu waktu. Periode peminjaman adalah 7 hari.</p>
                </div>
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
    
    <?php if (!empty($success_message)): ?>
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
    
    <?php if (!empty($error_message)): ?>
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