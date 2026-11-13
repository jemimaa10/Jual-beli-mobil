@extends('layouts.app')

@section('title', 'Tidak diizinkan')

@section('content')
    @include('errors._message', ['code' => '403', 'title' => 'Tidak Diizinkan', 'text' => 'Anda tidak punya akses ke halaman ini.'])
@endsection
