@extends('layouts.app')

@section('title', 'Sesi berakhir')

@section('content')
    @include('errors._message', ['code' => '419', 'title' => 'Sesi Sudah Berakhir', 'text' => 'Halaman ini terlalu lama terbuka, jadi pengamanannya kedaluwarsa. Muat ulang halaman sebelumnya lalu kirim ulang formulirnya. Data yang belum tersimpan perlu diisi lagi.'])
@endsection
