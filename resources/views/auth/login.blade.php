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
                <x-ui.icon name="sms" class="fs-2 text-gray-500 position-absolute top-50 translate-middle-y ms-4" />
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
                <x-ui.icon name="lock-2" class="fs-2 text-gray-500 position-absolute top-50 translate-middle-y ms-4" />
                <input
                    data-ui-input="metronic"
                    id="password"
                    name="password"
                    class="form-control form-control-solid form-control-lg ps-12 pe-12 @error('password') is-invalid @enderror"
                    :type="show ? 'text' : 'password'"
                    required
                    autocomplete="current-password"
                >
                <x-ui.icon-button type="button" icon="eye" label="Tampilkan password" class="position-absolute top-50 end-0 translate-middle-y me-2" @click="show = !show" x-show="!show" />
                <x-ui.icon-button type="button" icon="eye-slash" label="Sembunyikan password" class="position-absolute top-50 end-0 translate-middle-y me-2" @click="show = !show" x-show="show" x-cloak />
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
            <x-ui.button type="submit" size="lg">
                <span class="indicator-label d-flex align-items-center justify-content-center gap-2">
                    Masuk
                    <x-ui.icon name="arrow-right" class="fs-2 text-white" />
                </span>
            </x-ui.button>
        </div>
    </form>

    @if(config('sso.enabled'))
    <div class="separator separator-content my-8">
        <span class="text-gray-500 fw-semibold fs-7">Atau</span>
    </div>

    <div class="d-grid">
        <x-ui.button :href="route('sso.preflight')" variant="light" size="lg" class="btn-flex btn-sso-muhammadiyah">
            <img src="{{ asset('images/brand/logo-horizontal.svg') }}"
                 alt="Login via Muhammadiyah ID"
                 loading="lazy"
                 class="h-30px object-fit-contain">
        </x-ui.button>
    </div>
    @endif
</div>
@endsection
