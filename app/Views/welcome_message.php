<!DOCTYPE html>
<html lang="id">
<head>
    <link rel="icon" type="image/jpeg" href="<?= base_url('lukisan/dittipidter.png') ?>">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance System</title>
    <style>
        /* Membuat seluruh body center */
        body {
            display: flex;
            flex-direction: column;
            justify-content: center; /* Vertikal */
            align-items: center;     /* Horizontal */
            height: 100vh;           /* 100% tinggi viewport */
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
        }

        /* Gaya untuk judul */
        h1 {
            text-align: center;
            color: #333;
            margin-top: 20px;
        }

        /* Gaya untuk logo */
        img.logo {
            width: 150px; /* sesuaikan ukuran */
            height: auto;
        }
    </style>
</head>
<body>
    <!-- Logo di atas -->
<img src="<?= base_url('lukisan/dittipidter.png') ?>" alt="Logo DITTIPIDTER" class="logo">

    <!-- Judul -->
    <h1>DITTIPIDTER ONLINE Attendance System</h1>
</body>
</html>
