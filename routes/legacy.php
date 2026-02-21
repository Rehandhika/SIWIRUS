<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Legacy Redirects
|--------------------------------------------------------------------------
|
| Handles redirection for old URLs to new structure.
|
*/

Route::redirect('/login', '/admin/masuk');
Route::redirect('/admin/login', '/admin/masuk');
Route::redirect('/dashboard', '/admin/beranda');
Route::redirect('/attendance', '/admin/absensi');
Route::redirect('/schedule', '/admin/jadwal');
Route::redirect('/cashier', '/admin/kasir/pos');
Route::redirect('/admin/kasir', '/admin/kasir/pos');
Route::redirect('/stock', '/admin/stok');
Route::redirect('/purchase', '/admin/pembelian');
Route::redirect('/leave', '/admin/cuti');
Route::redirect('/swap', '/admin/tukar-jadwal');
Route::redirect('/penalties', '/admin/penalti');
Route::redirect('/reports', '/admin/laporan');
Route::redirect('/users', '/admin/pengguna');
Route::redirect('/roles', '/admin/peran');
