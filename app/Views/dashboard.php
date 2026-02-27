<div class="center-content">
    <img
        src="<?= base_url('lukisan/logodit.webp') ?>"
        alt="Bareskrim Polri"
        class="center-logo"
        width="220"
        height="220"
        fetchpriority="high"
        decoding="async">

    <div class="center-text">
        <p>BARESKRIM POLRI</p>
        <p>Dittipidter Online Attendance System </p>
        <p>D.O.A.S</p>
    </div>
</div>
<style>
    /* container tengah halaman */
    .center-content {
        height: calc(100vh - 120px);
        /* sesuaikan dengan header */
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
    }

    /* gambar */
    .center-logo {
        max-width: 220px;
        width: 100%;
        height: auto;
        margin-bottom: 16px;
    }

    /* tulisan bawah */
    .center-text {
        font-size: 22px;
        font-weight: 700;
        letter-spacing: 2px;
        color: #0b2545;
        /* biru gelap, bisa ganti */
    }

    .center-logo {
        filter: drop-shadow(0 4px 8px rgba(0, 0, 0, .15));

    }
</style>