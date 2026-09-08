@extends('layouts.app')

@section('title', 'Restore Data')

@section('content')

<div class="container fade-in">

    <div class="page-header">
        <div class="page-header-main">
            <div class="page-header-icon icon-accent-purple">
                @include('partials.icon', ['name' => 'restock'])
            </div>
            <div>
                <h1>Restore Data</h1>
                <p>Pulihkan data dari file backup JSON.</p>
            </div>
        </div>
    </div>

    @if ($errors->any())
        <div class="error-box">
            <strong>Terjadi kesalahan:</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="form-card form-card-featured">

        <div class="form-card-icon icon-accent-purple">
            @include('partials.icon', ['name' => 'restock'])
        </div>

        <h2 class="form-card-title">Restore dari Backup</h2>
        <p class="form-card-subtitle">Pilih file backup JSON yang pernah Anda unduh.</p>

        <div class="status-banner status-banner-warning" style="margin-bottom: 20px;">
            @include('partials.icon', ['name' => 'warning', 'class' => 'icon-sm'])
            Restore akan MENAMBAHKAN data ke database saat ini, bukan menimpa.
        </div>

        <form action="/data/restore" method="POST" enctype="multipart/form-data">

            @csrf

            <div class="form-group">
                <label for="backup_file">File Backup (.json)</label>
                <div class="file-input-wrapper">
                    @include('partials.icon', ['name' => 'database', 'class' => 'icon-sm file-input-icon'])
                    <input type="file" id="backup_file" name="backup_file" accept="application/json,.json" required>
                </div>
            </div>

            <div class="form-actions">
                <a href="/data" class="btn-secondary">Kembali</a>
                <button type="submit" class="btn-primary">
                    @include('partials.icon', ['name' => 'restock', 'class' => 'icon-sm'])
                    Restore Data
                </button>
            </div>

        </form>

    </div>

</div>

@endsection
