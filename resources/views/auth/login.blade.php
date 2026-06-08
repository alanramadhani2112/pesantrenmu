@extends('layouts.guest')

@section('content')
<div>
    <div class="spm-login-heading">
        <h1>Masuk ke PesantrenMu</h1>
        <p>Gunakan akun PesantrenMu yang sudah terdaftar.</p>
    </div>

    @if (session('status'))
        <div class="alert alert-success fs-7 fw-semibold mb-5" role="alert">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="form w-100"
          x-data="formValidation"
          @submit="if(!validateAll()) $event.preventDefault()"
          @focusout.debounce.50ms="onBlur($event)"
          @input.debounce.150ms="onInput($event)">
        @csrf

        <div data-ui-form-field="metronic" class="fv-row spm-form-field" data-validate="required|email">
            <label for="email" class="form-label fw-semibold text-gray-700 fs-7">
                Email
                <span class="text-danger ms-1">*</span>
            </label>
            <div class="position-relative">
                <i class="ki-solid ki-sms fs-2 text-gray-500 position-absolute top-50 translate-middle-y ms-4"></i>
                <input
                    data-ui-input="metronic"
                    type="email"
                    id="email"
                    name="email"
                    value="{{ old('email') }}"
                    class="form-control form-control-solid form-control-lg ps-12 @error('email') is-invalid @enderror"
                    required
                    autofocus
                    autocomplete="username"
                >
            </div>
            @error('email')
                <div class="invalid-feedback d-block fw-semibold">
                    <div>{{ $message }}</div>
                </div>
            @enderror
        </div>

        <div data-ui-form-field="metronic" class="fv-row spm-form-field mb-5" data-validate="required">
            <label for="password" class="form-label fw-semibold text-gray-700 fs-7">
                Password
                <span class="text-danger ms-1">*</span>
            </label>
            <div class="position-relative" x-data="{ show: false }">
                <i class="ki-solid ki-lock-2 fs-2 text-gray-500 position-absolute top-50 translate-middle-y ms-4"></i>
                <input
                    data-ui-input="metronic"
                    id="password"
                    name="password"
                    class="form-control form-control-solid form-control-lg ps-12 pe-12 @error('password') is-invalid @enderror"
                    :type="show ? 'text' : 'password'"
                    required
                    autocomplete="current-password"
                >
                <button
                    type="button"
                    class="btn btn-light btn-sm btn-icon btn-active-light-primary position-absolute top-50 end-0 translate-middle-y me-2"
                    @click="show = !show"
                    aria-label="Tampilkan password"
                >
                    <i class="ki-solid ki-eye fs-2 text-gray-500" x-show="!show"></i>
                    <i class="ki-solid ki-eye-slash fs-2 text-gray-500" x-show="show" x-cloak></i>
                </button>
            </div>
            @error('password')
                <div class="invalid-feedback d-block fw-semibold">
                    <div>{{ $message }}</div>
                </div>
            @enderror
        </div>

        <div class="spm-login-links">
            <span>Hubungi admin jika butuh bantuan.</span>
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}">Lupa password?</a>
            @endif
        </div>

        <div class="d-grid">
            <button type="submit" class="btn btn-primary btn-lg">
                <span class="indicator-label d-flex align-items-center justify-content-center gap-2">
                    Masuk
                    <i class="ki-solid ki-arrow-right fs-2 text-white"></i>
                </span>
            </button>
        </div>
    </form>

    @if(config('sso.enabled'))
    <div class="separator separator-content my-8">
        <span class="text-gray-500 fw-semibold fs-7">Atau</span>
    </div>

    <div class="d-grid">
        <a href="{{ route('sso.preflight') }}"
           class="btn btn-flex btn-lg btn-sso-muhammadiyah fw-semibold">
             <img src="{{ asset('images/brand/logo-horizontal.svg') }}"
                  alt="Login via Muhammadiyah ID"
                  loading="lazy"
                  class="h-30px object-fit-contain">
        </a>
    </div>
    @endif
</div>
@endsection
