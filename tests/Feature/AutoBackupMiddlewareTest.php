<?php

use App\Services\AutoBackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;

uses(RefreshDatabase::class);

test('middleware menjalankan auto backup saat request aplikasi dibuka', function () {
    $this->mock(AutoBackupService::class, function (MockInterface $mock) {
        $mock->shouldReceive('run')
            ->once()
            ->andReturn([]);
    });

    $this->get('/')->assertSuccessful();
});

test('middleware tetap meneruskan request walaupun auto backup gagal', function () {
    $this->mock(AutoBackupService::class, function (MockInterface $mock) {
        $mock->shouldReceive('run')
            ->once()
            ->andThrow(new RuntimeException('Backup gagal'));
    });

    $this->get('/')->assertSuccessful();
});
