@extends('layouts.guest')

@section('content')
<div>
    <div class="spm-login-heading mb-8">
        <h1>Verifikasi Email</h1>
        <p>Sebelum melanjutkan, silakan periksa email Anda untuk tautan verifikasi.</p>
    </div>

    @if (session('status') === 'verification-link-sent')
        <div class="alert alert-success fs-7 fw-semibold mb-5" role="alert">
            Tautan verifikasi baru telah dikirim ke alamat email yang Anda gunakan saat mendaftar.
        </div>
    @endif

    <div class="text-center mb-4">
        <p class="text-gray-500 fs-7 fw-semibold mb-0">
            Jika Anda tidak menerima email, klik tombol di bawah untuk mengirim ulang.
        </p>
    </div>

    <form method="POST" action="{{ route('verification.send') }}" class="mb-4">
        @csrf
        <div class="d-grid">
            <x-ui.button type="submit" size="lg">
                <span class="d-flex align-items-center justify-content-center gap-2">
                    Kirim Ulang Email Verifikasi
                    <x-ui.icon name="sms" class="fs-2 text-white" />
                </span>
            </x-ui.button>
        </div>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="d-grid">
        @csrf
        <x-ui.button type="submit" variant="light" size="lg">Keluar</x-ui.button>
    </form>
</div>
@endsection
