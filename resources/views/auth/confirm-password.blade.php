@extends('layouts.guest')

@section('content')
<div>
    <div class="spm-login-heading mb-8">
        <h1>Konfirmasi Password</h1>
        <p>Ini adalah area aman aplikasi. Harap konfirmasi password Anda sebelum melanjutkan.</p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" class="form w-100"
          x-data="formValidation"
          @submit="if(!validateAll()) $event.preventDefault()"
          @focusout.debounce.50ms="onBlur($event)">
        @csrf

        <div class="fv-row spm-form-field mb-5" data-validate="required">
            <label for="password" class="form-label fw-semibold text-gray-700 fs-7">
                Password
                <span class="text-danger ms-1">*</span>
            </label>
            <div class="position-relative" x-data="{ show: false }">
                <input id="password" name="password" type="password"
                       class="form-control form-control-solid form-control-lg pe-12 @error('password') is-invalid @enderror"
                       required autocomplete="current-password" autofocus>
                <x-ui.icon-button type="button" icon="eye" label="Tampilkan password" class="position-absolute top-50 end-0 translate-middle-y me-2" @click="show = !show" x-show="!show" />
                <x-ui.icon-button type="button" icon="eye-slash" label="Sembunyikan password" class="position-absolute top-50 end-0 translate-middle-y me-2" @click="show = !show" x-show="show" x-cloak />
            </div>
            @error('password')
                <div class="invalid-feedback d-block fw-semibold">{{ $message }}</div>
            @enderror
        </div>

        <div class="d-grid">
            <x-ui.button type="submit" size="lg">
                <span class="d-flex align-items-center justify-content-center gap-2">
                    Konfirmasi
                    <x-ui.icon name="arrow-right" class="fs-2 text-white" />
                </span>
            </x-ui.button>
        </div>
    </form>
</div>
@endsection
