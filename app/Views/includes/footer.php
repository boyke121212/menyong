<!--begin::Footer-->

<footer class="app-footer">
    <!--begin::To the end-->
    <div class="float-end d-none d-sm-inline"> <a href="https://toelve.com" class="text-decoration-none">Powered By
            ToElvE.com</a></div>
    <!--end::To the end-->
    <!--begin::Copyright-->
    <strong>
        Copyright &copy; 2026&nbsp;
        <a href="https://dittipidter-doas.online" class="text-decoration-none">DITTIPIDTER BARESKRIM POLRI</a>
    </strong>
    All rights reserved.
    <!--end::Copyright-->
</footer>
<!--end::Footer-->
<!-- FLASH -->
<?php if (session()->getFlashdata('flashsuccess')) : ?>
    <div class="toast toast-success">
        <?= esc(session()->getFlashdata('flashsuccess')) ?>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('flasherror')) : ?>
    <div class="toast toast-error">
        <?= esc(session()->getFlashdata('flasherror')) ?>
    </div>
<?php endif; ?>

</div>
</body>
<!--end::Body-->
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.37.1/dist/apexcharts.min.js"
    integrity="sha256-+vh8GkaU7C9/wbSLIcwq82tQ2wTf44aOHA8HlBMwRI8=" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/browser/overlayscrollbars.browser.es6.min.js"
    crossorigin="anonymous"></script>
<!--end::Third Party Plugin(OverlayScrollbars)-->
<!--begin::Required Plugin(popperjs for Bootstrap 5)-->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" crossorigin="anonymous">
</script>
<!--end::Required Plugin(popperjs for Bootstrap 5)-->
<!--begin::Required Plugin(Bootstrap 5)-->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.min.js" crossorigin="anonymous"></script>
<!--end::Required Plugin(Bootstrap 5)-->
<!--begin::Required Plugin(AdminLTE)-->
<script src="<?= base_url('template/AdminLTE4/dist/js/adminlte.js') ?>"></script>
<!--end::Required Plugin(AdminLTE)-->
<!--begin::OverlayScrollbars Configure-->
<script>
    const SELECTOR_SIDEBAR_WRAPPER = '.sidebar-wrapper';
    const Default = {
        scrollbarTheme: 'os-theme-light',
        scrollbarAutoHide: 'leave',
        scrollbarClickScroll: true,
    };
    document.addEventListener('DOMContentLoaded', function() {
        const sidebarWrapper = document.querySelector(SELECTOR_SIDEBAR_WRAPPER);
        if (sidebarWrapper && OverlayScrollbarsGlobal?.OverlayScrollbars !== undefined) {
            OverlayScrollbarsGlobal.OverlayScrollbars(sidebarWrapper, {
                scrollbars: {
                    theme: Default.scrollbarTheme,
                    autoHide: Default.scrollbarAutoHide,
                    clickScroll: Default.scrollbarClickScroll,
                },
            });
        }
    });
</script>
<!--end::OverlayScrollbars Configure-->
<!-- OPTIONAL SCRIPTS -->
<!-- apexcharts -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.37.1/dist/apexcharts.min.js"
    integrity="sha256-+vh8GkaU7C9/wbSLIcwq82tQ2wTf44aOHA8HlBMwRI8=" crossorigin="anonymous"></script>



<style>
    /* =========================
   TOAST (KEEP)
   ========================= */
    .toast {
        position: fixed;
        bottom: 24px;
        left: 50%;
        transform: translateX(-50%) translateY(20px);
        min-width: 300px;
        max-width: 420px;
        padding: 14px 18px;
        border-radius: 8px;
        color: #fff;
        font-size: 14px;
        opacity: 0;
        z-index: 9999;
        box-shadow: 0 12px 28px rgba(0, 0, 0, 0.18);
        transition: all 0.35s ease;
    }

    .toast.show {
        opacity: 1;
        transform: translateX(-50%) translateY(0);
    }

    .toast-success {
        background: linear-gradient(135deg, #2ecc71, #27ae60);
    }

    .toast-error {
        background: linear-gradient(135deg, #e74c3c, #c0392b);
    }
</style>

<script>
    const toast = document.querySelector('.toast');
    document.addEventListener('DOMContentLoaded', function() {
        const toasts = document.querySelectorAll('.toast');

        toasts.forEach(function(toast) {
            setTimeout(function() {
                toast.classList.add('show');
            }, 100);

            setTimeout(function() {
                toast.classList.remove('show');
            }, 4000);
        });
    });
</script>