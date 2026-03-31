// public/js/admin.js
document.addEventListener('DOMContentLoaded', function () {
  // Sidebar toggle (mobile)
  const sidebarToggleBtn = document.getElementById('sidebarToggleBtn');
  const sidebar = document.getElementById('sidebar');
  if (sidebarToggleBtn && sidebar) {
    sidebarToggleBtn.addEventListener('click', () => sidebar.classList.toggle('show'));
  }
  const sidebarToggle2 = document.getElementById('sidebarToggle');
  if (sidebarToggle2 && sidebar) {
    sidebarToggle2.addEventListener('click', () => sidebar.classList.remove('show'));
  }

  // Confirm delete
  document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', function (e) {
      e.preventDefault();
      const msg  = this.dataset.confirm || 'Tem certeza?';
      const href = this.href || this.dataset.href;
      Swal.fire({
        title: 'Confirmar ação',
        text: msg,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sim, confirmar',
        cancelButtonText: 'Cancelar'
      }).then(result => {
        if (result.isConfirmed) window.location.href = href;
      });
    });
  });

  // Auto-hide alerts
  const alerts = document.querySelectorAll('.alert.fade.show');
  alerts.forEach(a => {
    setTimeout(() => {
      const bsAlert = bootstrap.Alert.getOrCreateInstance(a);
      if (bsAlert) bsAlert.close();
    }, 5000);
  });

  // Toast from SweetAlert for flash messages already shown
  const swalToast = document.querySelector('[data-swal-toast]');
  if (swalToast) {
    Swal.fire({
      toast: true, position: 'top-end', showConfirmButton: false,
      timer: 3000, timerProgressBar: true,
      icon: swalToast.dataset.swalType || 'success',
      title: swalToast.dataset.swalMsg
    });
  }
});
