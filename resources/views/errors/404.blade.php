@extends('layouts.app')

@section('title', 'Halaman tidak ditemukan')

@section('content')
    @include('errors._message', ['code' => '404', 'title' => 'Halaman Tidak Ditemukan', 'text' => 'Alamat yang Anda buka tidak ada atau mobilnya sudah dihapus. Coba kembali ke beranda dan cari mobil dari sana.'])
@endsection
