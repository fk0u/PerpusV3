// SweetAlert2 example
document.addEventListener('DOMContentLoaded', function() {
    // You can use SweetAlert here to show alerts
    // Example alert
    if (window.location.search.indexOf('success') !== -1) {
        Swal.fire('Berhasil!', 'Operasi berhasil dilakukan.', 'success');
    }
});
