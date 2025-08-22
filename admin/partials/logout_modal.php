<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-3">
            <div class="modal-header border-0">
                <h5 class="modal-title" id="logoutModalLabel">ยืนยันการออกจากระบบ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
            </div>
            <div class="modal-body pt-0">
                <p class="mb-0 text-muted">คุณแน่ใจหรือไม่ว่าต้องการออกจากระบบ?</p>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <form method="post" action="../logout.php" class="m-0"> <!-- ✅ -->
                    <button type="submit" class="btn btn-danger">ใช่, ออกจากระบบ</button>
                </form>
            </div>
        </div>
    </div>
</div>