<footer class="footer text-center py-3">
    <small>© 2025 ระบบร้องเรียนและข้อเสนอแนะ</small>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    // toggle sidebar
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        if (sidebar) sidebar.classList.toggle('hide');
    }
    // เปิด modal logout เมื่อคลิกเมนู "ออกจากระบบ"
    document.addEventListener('DOMContentLoaded', () => {
        const btn = document.getElementById('btn-logout');
        if (!btn) return;
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const modal = new bootstrap.Modal(document.getElementById('logoutModal'));
            modal.show();
        });
    });
</script>