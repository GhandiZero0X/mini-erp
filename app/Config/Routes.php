<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// Halaman untuk tamu: yang sudah login diarahkan ke dashboard (UC-01 A5)
$routes->group('', ['filter' => 'guest'], static function ($routes) {
    $routes->get('login', 'Auth::login');
    $routes->post('login', 'Auth::attempt');
});

// Logout wajib POST + token CSRF. Tanpa filter auth agar sesi yang sudah berakhir
// tetap diarahkan ke login tanpa pesan kesalahan (UC-02 A1).
$routes->post('logout', 'Auth::logout');

// Halaman internal: wajib login (FR-03). Belum login -> /login (UC-01 A4).
$routes->group('', ['filter' => 'auth'], static function ($routes) {
    $routes->get('/', 'Dashboard::index');
    $routes->get('dashboard', 'Dashboard::index');
});

// UC-21 Kelola Akun Pengguna: hanya Admin (fitur 'users', level W). Selain Admin -> VR-17.
$routes->group('users', ['filter' => 'auth:users,W'], static function ($routes) {
    $routes->get('', 'Users::index');
    $routes->get('new', 'Users::new');
    $routes->post('', 'Users::create');
    $routes->get('(:num)/edit', 'Users::edit/$1');
    $routes->post('(:num)', 'Users::update/$1');
    $routes->post('(:num)/toggle', 'Users::toggle/$1');
    $routes->get('(:num)/reset-password', 'Users::resetForm/$1');
    $routes->post('(:num)/reset-password', 'Users::resetPassword/$1');
    $routes->post('(:num)/unlock', 'Users::unlock/$1');
});

// UC-03 Kategori (Master Data). Tabel 2.3: Admin = ubah (W), Purchasing & Sales = hanya lihat (R).
// Dua grup dipisah agar hak akses tiap rute eksplisit.
$routes->group('categories', ['filter' => 'auth:master_data'], static function ($routes) {
    $routes->get('', 'Categories::index');
});
$routes->group('categories', ['filter' => 'auth:master_data,W'], static function ($routes) {
    $routes->get('new', 'Categories::new');
    $routes->post('', 'Categories::create');
    $routes->get('(:num)/edit', 'Categories::edit/$1');
    $routes->post('(:num)', 'Categories::update/$1');
    $routes->post('(:num)/delete', 'Categories::delete/$1');
});

// UC-04 Produk (Master Data). Admin = ubah (W), Purchasing & Sales = hanya lihat (R).
$routes->group('products', ['filter' => 'auth:master_data'], static function ($routes) {
    $routes->get('', 'Products::index');
});
$routes->group('products', ['filter' => 'auth:master_data,W'], static function ($routes) {
    $routes->get('new', 'Products::new');
    $routes->post('', 'Products::create');
    $routes->get('(:num)/edit', 'Products::edit/$1');
    $routes->post('(:num)', 'Products::update/$1');
    $routes->post('(:num)/delete', 'Products::delete/$1');
});

// UC-05 Supplier (Master Data). Admin = ubah (W), Purchasing & Sales = hanya lihat (R).
$routes->group('suppliers', ['filter' => 'auth:master_data'], static function ($routes) {
    $routes->get('', 'Suppliers::index');
});
$routes->group('suppliers', ['filter' => 'auth:master_data,W'], static function ($routes) {
    $routes->get('new', 'Suppliers::new');
    $routes->post('', 'Suppliers::create');
    $routes->get('(:num)/edit', 'Suppliers::edit/$1');
    $routes->post('(:num)', 'Suppliers::update/$1');
    $routes->post('(:num)/delete', 'Suppliers::delete/$1');
});

// UC-06 Customer (Master Data). Admin = ubah (W), Purchasing & Sales = hanya lihat (R).
$routes->group('customers', ['filter' => 'auth:master_data'], static function ($routes) {
    $routes->get('', 'Customers::index');
});
$routes->group('customers', ['filter' => 'auth:master_data,W'], static function ($routes) {
    $routes->get('new', 'Customers::new');
    $routes->post('', 'Customers::create');
    $routes->get('(:num)/edit', 'Customers::edit/$1');
    $routes->post('(:num)', 'Customers::update/$1');
    $routes->post('(:num)/delete', 'Customers::delete/$1');
});