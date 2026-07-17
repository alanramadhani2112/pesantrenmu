@extends('layouts.guest')

@section('content')
<div>
    <div class="spm-login-heading mb-8">
        <h1>Atur Ulang Password</h1>
        <p>Masukkan password baru untuk akun Anda.</p>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger fs-7 fw-semibold mb-5" role="alert">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('password.update') }}" class="form w-100"
          x-data="formValidation"
          @submit="if(!validateAll()) $event.preventDefault()"
          @focusout.debounce.50ms="onBlur($event)"
          @input.debounce.150ms="onInput($event)">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="fv-row spm-form-field mb-4" data-validate="required|email">
            <label for="email" class="form-label fw-semibold text-gray-700 fs-7">
                Email
                <span class="text-danger ms-1">*</span>
            </label>
            <input type="email" id="email" name="email" value="{{ old('email', $request->email) }}"
                   class="form-control form-control-solid form-control-lg @error('email') is-invalid @enderror"
                   required autofocus autocomplete="username">
            @error('email')
                <div class="invalid-feedback d-block fw-semibold">{{ $message }}</div>
            @enderror
        </div>

        <div class="fv-row spm-form-field mb-4" data-validate="required|min:8">
            <label for="password" class="form-label fw-semibold text-gray-700 fs-7">
                Password Baru
                <span class="text-danger ms-1">*</span>
            </label>
            <div class="position-relative" x-data="{ show: false }">
                <input id="password" name="password"
                       class="form-control form-control-solid form-control-lg pe-12 @error('password') is-invalid @enderror"
                       :type="show ? 'text' : 'password'" required autocomplete="new-password">
                <x-ui.icon-button type="button" icon="eye" label="Tampilkan password" class="position-absolute top-50 end-0 translate-middle-y me-2" @click="show = !show" x-show="!show" />
                <x-ui.icon-button type="button" icon="eye-slash" label="Sembunyikan password" class="position-absolute top-50 end-0 translate-middle-y me-2" @click="show = !show" x-show="show" x-cloak />
            </div>
            @error('password')
                <div class="invalid-feedback d-block fw-semibold">{{ $message }}</div>
            @enderror
        </div>

        <div class="fv-row spm-form-field mb-5" data-validate="required|same:password">
            <label for="password_confirmation" class="form-label fw-semibold text-gray-700 fs-7">
                Konfirmasi Password
                <span class="text-danger ms-1">*</span>
            </label>
            <div class="position-relative" x-data="{ show: false }">
                <input id="password_confirmation" name="password_confirmation"
                       class="form-control form-control-solid form-control-lg pe-12 @error('password_confirmation') is-invalid @enderror"
                       :type="show ? 'text' : 'password'" required autocomplete="new-password">
                <x-ui.icon-button type="button" icon="eye" label="Tampilkan konfirmasi password" class="position-absolute top-50 end-0 translate-middle-y me-2" @click="show = !show" x-show="!show" />
                <x-ui.icon-button type="button" icon="eye-slash" label="Sembunyikan konfirmasi password" class="position-absolute top-50 end-0 translate-middle-y me-2" @click="show = !show" x-show="show" x-cloak />
            </div>
            @error('password_confirmation')
                <div class="invalid-feedback d-block fw-semibold">{{ $message }}</div>
            @enderror
        </div>

        <div class="d-grid mb-4">
            <x-ui.button type="submit" size="lg">
                <span class="d-flex align-items-center justify-content-center gap-2">
                    Atur Ulang Password
                    <x-ui.icon name="arrow-right" class="fs-2 text-white" />
                </span>
            </x-ui.button>
        </div>
    </form>
</div>
@endsection
