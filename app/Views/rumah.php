<?php $session = session(); ?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" href="<?= base_url('template/logodit.webp') ?>" type="image/png">
    <title>D.O.A.S - LOGIN</title>

    <!-- Custom fonts for this template-->
    <link href="<?= base_url('template/vendor/fontawesome-free/css/all.min.css" rel="stylesheet') ?>" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="<?= base_url('template/css/sb-admin-2.min.css') ?>" rel="stylesheet">

</head>

<body class="bg-gradient-dark">


    <div class="container d-flex align-items-center justify-content-center" style="min-height:100vh;">

        <div class="row justify-content-center w-100">

            <div class="col-xl-8 col-lg-9 col-md-11" style="max-width:1100px;">

                <div class="card o-hidden border-0 shadow-lg">

                    <div class="card-body p-0">
                        <div class="row g-0">

                            <!-- LEFT BRANDING (DESKTOP ONLY) -->
                            <div class="col-lg-6 d-none d-lg-flex align-items-center justify-content-center">
                                <div class="text-center px-4">

                                    <img src="<?= base_url('template/logodit.webp') ?>" alt="Logo DITTIPIDTER"
                                        class="img-fluid mb-0" style="max-height:220px; display:block; margin:auto;">

                                    <h4 class="fw-bold text-uppercase mt-0 mb-0"
                                        style="letter-spacing:1.4px; line-height:1;">
                                        D.O.A.S
                                    </h4>

                                    <hr class="my-1 mx-auto" style="width:65px; opacity:0.3;">

                                    <p class="text-muted mb-0" style="font-size:0.95rem; line-height:1.15;">
                                        Dittipidter Online Attendance System
                                    </p>

                                </div>
                            </div>

                            <!-- RIGHT LOGIN -->
                            <div class="col-lg-6 d-flex flex-column">

                                <!-- MOBILE LOGO (TOP) -->
                                <div class="d-lg-none text-center pt-4">

                                    <img src="<?= base_url('template/logodit.webp') ?>" alt="Logo DITTIPIDTER"
                                        class="img-fluid mb-0" style="max-height:120px; display:block; margin:auto;">

                                    <h6 class="fw-bold text-uppercase mt-0 mb-0"
                                        style="letter-spacing:1px; line-height:1;">
                                        D.O.A.S
                                    </h6>

                                    <p class="text-muted mb-2" style="font-size:0.85rem; line-height:1.15;">
                                        Dittipidter Online Attendance System
                                    </p>

                                    <hr class="my-2 mx-auto" style="width:55px; opacity:0.3;">
                                </div>

                                <!-- LOGIN FORM (OPTICAL CENTER - TURUN SEDIKIT) -->
                                <div class="d-flex flex-column justify-content-center flex-grow-1 px-5"
                                    style="transform: translateY(20px);">

                                    <div class="text-center mb-4">
                                        <h1 class="h4 fw-bold text-gray-900">
                                            Backend Login
                                        </h1>
                                    </div>

                                    <form action="<?= site_url('login'); ?>" method="post">
                                        <div class="form-group mb-3">
                                            <input type="text" class="form-control form-control-user" name="username"
                                                placeholder="Username" required>
                                        </div>

                                        <div class="form-group mb-4">
                                            <input type="password" class="form-control form-control-user"
                                                name="password" placeholder="Password" required>
                                        </div>

                                        <button type="submit" class="btn btn-primary btn-user btn-block fw-bold">
                                            Login
                                        </button>

                                    </form>

                                    <!-- ALERT -->
                                    <?php
                                    $toastMessage = null;
                                    $toastType = 'danger';

                                    if ($session->getFlashdata('flashfail')) {
                                        $toastMessage = $session->getFlashdata('flashfail');
                                        $toastType = 'danger';
                                    } elseif ($session->getFlashdata('flahok')) {
                                        $toastMessage = $session->getFlashdata('flahok');
                                        $toastType = 'success';
                                    } elseif ($session->getFlashdata('flahgagalotent')) {
                                        $toastMessage = $session->getFlashdata('flahgagalotent');
                                        $toastType = 'danger';
                                    }
                                    ?>



                                </div>

                            </div>

                        </div>
                    </div>

                </div>

            </div>

        </div>

    </div>

    <?php if ($toastMessage): ?>
    <div id="androidToast" class="android-toast android-toast-<?= $toastType ?>">
        <?= esc($toastMessage) ?>
    </div>
    <?php endif; ?>

    <!-- Bootstrap core JavaScript-->
    <script src="<?= base_url('template/vendor/jquery/jquery.min.js') ?>"></script>
    <script src="<?= base_url('template/vendor/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>

    <!-- Core plugin JavaScript-->
    <script src="<?= base_url('template/vendor/jquery-easing/jquery.easing.min.js') ?>"></script>

    <!-- Custom scripts for all pages-->
    <script src="<?= base_url('template/js/sb-admin-2.min.js') ?>"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var toast = document.getElementById('androidToast');
        if (toast) {
            // munculin
            setTimeout(function() {
                toast.classList.add('show');
            }, 100);

            // ilang otomatis
            setTimeout(function() {
                toast.classList.remove('show');
            }, 4000);
        }
    });
    </script>

</body>

</html>
<style>
.android-toast {
    position: fixed;
    left: 50%;
    bottom: -100px;
    /* awal di bawah layar */
    transform: translateX(-50%);
    min-width: 280px;
    max-width: 90%;
    padding: 14px 20px;
    border-radius: 8px;
    color: #fff;
    font-size: 14px;
    text-align: center;
    z-index: 99999;
    opacity: 0;
    transition: all 0.35s ease;
}

/* warna */
.android-toast-success {
    background: #2e7d32;
    /* hijau android */
}

.android-toast-danger {
    background: #d32f2f;
    /* merah android */
}

/* kondisi muncul */
.android-toast.show {
    bottom: 30px;
    /* naik ke atas */
    opacity: 1;
}
</style>