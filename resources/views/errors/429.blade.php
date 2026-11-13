@extends('layouts.app')

@section('title', 'Terlalu banyak permintaan')

@section('content')
    @include('errors._message', ['code' => '429', 'title' => 'Terlalu Banyak Permintaan', 'text' => 'Mohon tunggu sebentar lalu coba lagi.'])
@endsection
