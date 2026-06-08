@extends('layouts.guest')

@section('content')
<div>
    <div class="spm-login-heading mb-8">
        <h1>Pendaftaran Akun</h1>
        <p>Buat akun PesantrenMu baru.</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="form w-100"
          x-data="formValidation"
          @submit="if(!validateAll()) $event.preventDefault()"
          @focusout.debounce.50ms="onBlur($event)"
          @input.debounce.150ms="onInput($event)">
        @csrf

        <div class="fv-row spm-form-field mb-4" data-validate="required">
            <label for="name" class="form-label fw-semibold text-gray-700 fs-7">
                Nama Lengkap
                <span class="text-danger ms-1">*</span>
            </label>
            <input type="text" id="name" name="name" value="{{ old('name') }}"
                   class="form-control form-control-solid form-control-lg @error('name') is-invalid @enderror"
                   required autofocus autocomplete="name">
            @error('name')
                <div class="invalid-feedback d-block fw-semibold">{{ $message }}</div>
            @enderror
        </div>

        <div class="fv-row spm-form-field mb-4" data-validate="required|email">
            <label for="email" class="form-label fw-semibold text-gray-700 fs-7">
                Email
                <span class="text-danger ms-1">*</span>
            </label>
            <input type="email" id="email" name="email" value="{{ old('email') }}"
                   class="form-control form-control-solid form-control-lg @error('email') is-invalid @enderror"
                   required autocomplete="username">
            @error('email')
                <div class="invalid-feedback d-block fw-semibold">{{ $message }}</div>
            @enderror
        </div>

        <div class="fv-row spm-form-field mb-4" data-validate="required|minLength:8">
            <label for="password" class="form-label fw-semibold text-gray-700 fs-7">
                Password
                <span class="text-danger ms-1">*</span>
            </label>
            <div class="position-relative" x-data="{ show: false }">
                <input id="password" name="password"
                       class="form-control form-control-solid form-control-lg pe-12 @error('password') is-invalid @enderror"
                       :type="show ? 'text' : 'password'" required autocomplete="new-password">
                <button type="button" class="btn btn-icon position-absolute top-50 end-0 translate-middle-y me-2" @click="show = !show">
                    <i class="ki-solid fs-2" :class="show ? 'ki-eye-slash' : 'ki-eye'"></i>
                </button>
            </div>
            @error('password')
                <div class="invalid-feedback d-block fw-semibold">{{ $message }}</div>
            @enderror
        </div>

        <div class="fv-row spm-form-field mb-6" data-validate="required|passwordMatch:password">
            <label for="password_confirmation" class="form-label fw-semibold text-gray-700 fs-7">
                Konfirmasi Password
                <span class="text-danger ms-1">*</span>
            </label>
            <div class="position-relative" x-data="{ show: false }">
                <input id="password_confirmation" name="password_confirmation"
                       class="form-control form-control-solid form-control-lg pe-12 @error('password_confirmation') is-invalid @enderror"
                       :type="show ? 'text' : 'password'" required autocomplete="new-password">
                <button type="button" class="btn btn-icon position-absolute top-50 end-0 translate-middle-y me-2" @click="show = !show">
                    <i class="ki-solid fs-2" :class="show ? 'ki-eye-slash' : 'ki-eye'"></i>
                </button>
            </div>
            @error('password_confirmation')
                <div class="invalid-feedback d-block fw-semibold">{{ $message }}</div>
            @enderror
        </div>

        <div class="d-grid mb-4">
            <button type="submit" class="btn btn-primary btn-lg">
                <span class="d-flex align-items-center justify-content-center gap-2">
                    Daftar
                    <i class="ki-solid ki-arrow-right fs-2 text-white"></i>
                </span>
            </button>
        </div>

        <div class="text-center">
            <span class="text-gray-500 fs-7 fw-semibold">Sudah punya akun?</span>
            <a href="{{ route('login') }}" class="fw-semibold fs-7 ms-1 text-primary">Masuk di sini</a>
        </div>
    </form>
</div>
@endsection
