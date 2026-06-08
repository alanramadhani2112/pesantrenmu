@extends('layouts.guest')

@section('content')
<div>
    <div class="spm-login-heading mb-8">
        <h1>Lupa Password</h1>
        <p>Masukkan email Anda dan kami akan mengirimkan tautan untuk mengatur ulang password.</p>
    </div>

    @if (session('status'))
        <div class="alert alert-success fs-7 fw-semibold mb-5" role="alert">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="form w-100"
          x-data="formValidation"
          @submit="if(!validateAll()) $event.preventDefault()"
          @focusout.debounce.50ms="onBlur($event)"
          @input.debounce.150ms="onInput($event)">
        @csrf

        <div class="fv-row spm-form-field mb-6" data-validate="required|email">
            <label for="email" class="form-label fw-semibold text-gray-700 fs-7">
                Email
                <span class="text-danger ms-1">*</span>
            </label>
            <input type="email" id="email" name="email" value="{{ old('email') }}"
                   class="form-control form-control-solid form-control-lg @error('email') is-invalid @enderror"
                   required autofocus autocomplete="email">
            @error('email')
                <div class="invalid-feedback d-block fw-semibold">{{ $message }}</div>
            @enderror
        </div>

        <div class="d-grid mb-4">
            <button type="submit" class="btn btn-primary btn-lg">
                <span class="d-flex align-items-center justify-content-center gap-2">
                    Kirim Tautan Reset
                    <i class="ki-solid ki-arrow-right fs-2 text-white"></i>
                </span>
            </button>
        </div>

        <div class="text-center">
            <a href="{{ route('login') }}" class="fw-semibold fs-7 text-primary">
                <i class="ki-solid ki-arrow-left fs-7 me-1"></i> Kembali ke halaman login
            </a>
        </div>
    </form>
</div>
@endsection
