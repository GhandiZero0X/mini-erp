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

// UC-07, UC-08 Purchase Order. Lihat = Purchasing & Gudang (R); buat/setujui = Purchasing (W).
$routes->group('purchase-orders', ['filter' => 'auth:purchase_order'], static function ($routes) {
    $routes->get('', 'PurchaseOrders::index');
    $routes->get('(:num)', 'PurchaseOrders::show/$1');
});
$routes->group('purchase-orders', ['filter' => 'auth:purchase_order,W'], static function ($routes) {
    $routes->get('new', 'PurchaseOrders::new');
    $routes->post('', 'PurchaseOrders::create');
    $routes->post('(:num)/approve', 'PurchaseOrders::approve/$1');
    $routes->post('(:num)/cancel',  'PurchaseOrders::cancel/$1');
});

// UC-09 Goods Receipt: hanya Staf Gudang (fitur 'goods_receipt'). Angka pada new/(:num) = id PO yang diterima.
$routes->group('goods-receipts', ['filter' => 'auth:goods_receipt'], static function ($routes) {
    $routes->get('', 'GoodsReceipts::index');
    $routes->get('(:num)', 'GoodsReceipts::show/$1');
});
$routes->group('goods-receipts', ['filter' => 'auth:goods_receipt,W'], static function ($routes) {
    $routes->get('new/(:num)', 'GoodsReceipts::new/$1');
    $routes->post('new/(:num)', 'GoodsReceipts::create/$1');
    $routes->post('(:num)/cancel', 'GoodsReceipts::cancel/$1'); 
});

// UC-11, UC-14 Sales Order. Lihat = Sales & Gudang; buat/batal = Sales (W).
$routes->group('sales-orders', ['filter' => 'auth:sales_order'], static function ($routes) {
    $routes->get('', 'SalesOrders::index');
    $routes->get('(:num)', 'SalesOrders::show/$1');
});
$routes->group('sales-orders', ['filter' => 'auth:sales_order,W'], static function ($routes) {
    $routes->get('new', 'SalesOrders::new');
    $routes->post('', 'SalesOrders::create');
    $routes->post('(:num)/cancel', 'SalesOrders::cancel/$1');
});

// UC-13, UC-14 Delivery. Hanya Staf Gudang. Angka di new/(:num) = id SO.
$routes->group('deliveries', ['filter' => 'auth:delivery'], static function ($routes) {
    $routes->get('', 'Deliveries::index');
    $routes->get('(:num)', 'Deliveries::show/$1');
});
$routes->group('deliveries', ['filter' => 'auth:delivery,W'], static function ($routes) {
    $routes->get('new/(:num)', 'Deliveries::new/$1');
    $routes->post('new/(:num)', 'Deliveries::create/$1');
    $routes->post('(:num)/cancel', 'Deliveries::cancel/$1');
});