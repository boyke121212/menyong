<?php

namespace Config;

use CodeIgniter\Config\Services;
use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes = Services::routes();

// Load system routes FIRST
if (file_exists(SYSTEMPATH . 'Config/Routes.php')) {
    require SYSTEMPATH . 'Config/Routes.php';
}

// 🔒 WAJIB: MATIKAN AUTO ROUTING
$routes->setAutoRoute(false);

$routes->get('/', 'Dashboard::index', ['filter' => 'auth']);

$routes->get('asktoin', 'Boyke::asktoin');
$routes->post('login', 'AuthController::login');


$routes->get('testdb', function () {
    return response()->setJSON(['ok' => true]);
});


$routes->group('/', ['filter' => 'auth'], function ($routes) {
    $routes->get('logout', 'Boyke::logout');
    $routes->get('lukisan/(:any)', 'Lukisan::show/$1');
    $routes->get('usnmanajemen', 'Usnmanajemen::getuser');
    $routes->post('logoutuser', 'Usnmanajemen::logoutuser');
    $routes->get('tambahuser', 'Usnmanajemen::adduser');
    $routes->post('simpanuser', 'Usnmanajemen::simpan');
    $routes->post('updateuser', 'Usnmanajemen::updateuser');
    $routes->post('hapususer', 'Usnmanajemen::hapususer');
    $routes->post('hapususerbanyak', 'Usnmanajemen::hapususerbanyak');
    $routes->get('apadoas', 'Usnmanajemen::apadoas');
    $routes->get('login_log', 'Usnmanajemen::login_log');
    $routes->post('login_log/data', 'Usnmanajemen::datalog');
    $routes->get('login_log/export', 'Usnmanajemen::export');
    $routes->get('anggaran', 'Api\Anggaran::index');
    $routes->post('anggaran/buat', 'Api\Anggaran::buatTahun');
    $routes->post('anggaran/save', 'Api\Anggaran::saveDetail');
    $routes->post('anggaran/delete', 'Api\Anggaran::deleteTahun');
    $routes->post('anggaran/resetDetail', 'Api\Anggaran::resetDetail');
    $routes->get('anggaran/detail', 'Api\Anggaran::detail');
    // $routes->match(['get', 'post'], 'anggaran/detail', 'Api\Anggaran::detail');

    $routes->post('anggaran/exportDetail', 'Api\Anggaran::exportDetail');

    $routes->get('logout_log', 'Usnmanajemen::logout_log');
    $routes->post('logout_log/data', 'Usnmanajemen::datatable');
    $routes->get('logout_log/export', 'Usnmanajemen::exportlogout');
    $routes->get('user_management_log', 'Usnmanajemen::user_management_log');
    $routes->post('user_management_log/data', 'Usnmanajemen::userManagementLogData');
    $routes->get('user_management_log/export', 'Usnmanajemen::exportUserManagementLog');
    $routes->get('log_kantor', 'Usnmanajemen::log_kantor');
    $routes->post('log_kantor/data', 'Usnmanajemen::logKantorData');
    $routes->get('log_kantor/export', 'Usnmanajemen::exportLogKantor');
    $routes->get('log_berita', 'Usnmanajemen::log_berita');
    $routes->post('log_berita/data', 'Usnmanajemen::logBeritaData');
    $routes->get('log_berita/export', 'Usnmanajemen::exportLogBerita');
    $routes->get('log_anggaran', 'Usnmanajemen::log_anggaran');
    $routes->post('log_anggaran/data', 'Usnmanajemen::logAnggaranData');
    $routes->get('log_anggaran/export', 'Usnmanajemen::exportLogAnggaran');
    $routes->get('log_about', 'Usnmanajemen::log_about');
    $routes->post('log_about/data', 'Usnmanajemen::logAboutData');
    $routes->get('log_about/export', 'Usnmanajemen::exportLogAbout');

    $routes->get('kantor', 'Usnmanajemen::kantor');
    $routes->post('simpandoas', 'Doas::simpandoas');
    $routes->post('simpankantor', 'Doas::simpankantor');
    $routes->get('user/edit', 'Usnmanajemen::edit');
    $routes->get('doas/pdf/(:any)', 'Doas::tampilpdf/$1');


    $routes->get('tampilfoto/(:any)', 'Tampilfoto::show/$1');
    $routes->post('upload-photo', 'FileController::uploadPhoto');
    $routes->post('generate-pdf', 'FileController::generatePDF');
    $routes->get('dashboard', 'Dashboard::index');
    $routes->post('export-excel', 'ExcelController::export');
    $routes->post('import-excel', 'ExcelController::import');
    $routes->get('absensi/laporan', 'Absensi::laporan');
    $routes->post('absensi/ajaxList', 'Absensi::ajaxList');
    $routes->post('absensi/ajaxRekap', 'Absensi::ajaxRekap');
    $routes->post('absensi/export', 'Absensi::export');
    $routes->post('absensi/grafik', 'Absensi::grafik');
    // halaman utama berita
    $routes->get('berita', 'Berita::index');
    $routes->get('berita/add', 'Berita::add');
    $routes->post('berita/save', 'Berita::save');

    // datatables server side (AJAX + CSRF)
    $routes->post('berita/getData', 'Berita::getData');

    // delete berita (AJAX + CSRF)
    $routes->post('berita/delete', 'Berita::delete');
    $routes->get('berita/edit/(:num)', 'Berita::edit/$1');
    $routes->post('berita/update', 'Berita::update');
    $routes->get('berita/pdf/(:any)', 'Berita::tampilPdf/$1');
});


$routes->post('cekdata', 'Api\Cekdata::login');
$routes->group('ajax', ['filter' => 'auth'], function ($routes) {

    $routes->get('user', 'User::index');
    $routes->post('user/datatables', 'User::datatables');
});

$routes->get('tampilberita/(:any)', 'TampilBerita::show/$1');

$routes->post('api/refresh-token', 'Api\Auth::refreshToken');
$routes->group('api', ['filter' => 'mobileauth'], function ($routes) {
    $routes->post('auth-check', 'Api\Cekdata::authcheck');
    $routes->post('ceka', 'Api\Cekdata::ceka');
    $routes->post('cekabsen', 'Api\Cekdata::cekabsen');
    $routes->post('ambil_absen', 'Api\Cekdata::ambil_absen');
    $routes->post('absen', 'Api\Cekdata::absen');
    $routes->post('pulang', 'Api\Cekdata::pulang');
    $routes->post('getdoas', 'Api\Cekdata::getdoas');
    $routes->post('sejarah', 'Api\Cekdata::sejarah');
    $routes->post('berita', 'Api\Cekdata::berita');
    $routes->get('media/berita/(:any)', 'Media::berita/$1');
    $routes->get('media/pdf/(:any)', 'Media::pdf/$1');
    $routes->get('media/absensi', 'Media::absensi');
});
$routes->get('tes', function () {
    return 'ROUTE HIDUP';
});
