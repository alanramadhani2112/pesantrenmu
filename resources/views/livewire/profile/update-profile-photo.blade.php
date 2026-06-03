<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public $photo;
    public int $photoKey = 0;

    public function mount(): void
    {
        //
    }

    public function getPhotoUrlProperty(): string
    {
        $path = Auth::user()->profile_photo_path;
        return $path ? asset('storage/' . $path) : '';
    }

    public function getInitialsProperty(): string
    {
        $name = Auth::user()->name ?? 'U';
        $parts = explode(' ', trim($name));
        return count($parts) > 1
            ? strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1))
            : strtoupper(substr($parts[0], 0, 2));
    }

    public function updatedPhoto(): void
    {
        $this->validate([
            'photo' => ['image', 'max:2048'],
        ], [
            'photo.image' => 'File harus berupa gambar.',
            'photo.max' => 'Ukuran foto maksimal 2MB.',
        ]);

        $user = Auth::user();

        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
        }

        $path = $this->photo->store('profile-photos', 'public');
        $user->update(['profile_photo_path' => $path]);

        $this->photoKey++;
        $this->dispatch('profile-photo-updated');
    }

    public function removePhoto(): void
    {
        $user = Auth::user();

        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
            $user->update(['profile_photo_path' => null]);
        }

        $this->photoKey++;
        $this->dispatch('profile-photo-updated');
    }
}; ?>

<section>
    <header>
        <h2 class="fs-4 fw-semibold text-gray-900">Foto Profil</h2>
        <p class="mt-1 text-gray-600 fs-7">Unggah foto profil Anda. Maksimal 2MB (JPG/PNG).</p>
    </header>

    <div class="mt-4" wire:key="photo-{{ $photoKey }}"
        x-data="{
            preview: '{{ $this->photoUrl }}',
            changed: false,
            get isEmpty() { return !this.preview; },
        }"
        x-init="
            $watch('$wire.photoKey', () => {
                $nextTick(() => { preview = ''; changed = false; });
            });
        ">
        {{-- Metronic image-input — exact docs pattern --}}
        <div class="image-input image-input-circle image-input-outline"
            :class="{ 'image-input-empty': isEmpty, 'image-input-changed': changed }"
            style="background-color: #f5f8fa;">

            {{-- Preview wrapper — shows photo or empty --}}
            <div class="image-input-wrapper w-125px h-125px"
                :style="preview ? 'background-image:url(' + preview + ')' : ''">
            </div>

            {{-- Change button (pencil, overlaps top-right) --}}
            <label class="btn btn-icon btn-circle btn-color-muted btn-active-color-primary w-25px h-25px bg-body shadow"
                data-kt-image-input-action="change"
                title="Ganti foto">
                <i class="ki-solid ki-pencil fs-6"></i>
                <input type="file"
                    wire:model="photo"
                    accept=".png, .jpg, .jpeg"
                    x-on:change="
                        const file = $event.target.files[0];
                        if (file) {
                            preview = URL.createObjectURL(file);
                            changed = true;
                        }
                    " />
                <input type="hidden" name="avatar_remove" value="0" />
            </label>

            {{-- Cancel button (bottom-right, shown when file selected but not saved) --}}
            <span class="btn btn-icon btn-circle btn-color-muted btn-active-color-primary w-25px h-25px bg-body shadow"
                data-kt-image-input-action="cancel"
                title="Batal"
                x-on:click="changed = false; preview = '{{ $this->photoUrl }}'; $wire.photo = null;">
                <i class="ki-solid ki-cross fs-3"></i>
            </span>

            {{-- Remove button (bottom-right, shown when photo exists) --}}
            <span class="btn btn-icon btn-circle btn-color-muted btn-active-color-primary w-25px h-25px bg-body shadow"
                data-kt-image-input-action="remove"
                title="Hapus foto"
                x-on:click="$wire.removePhoto()">
                <i class="ki-solid ki-cross fs-3"></i>
            </span>
        </div>
    </div>
</section>
