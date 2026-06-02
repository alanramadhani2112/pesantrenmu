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
        x-data="{ preview: '{{ $this->photoUrl }}', changed: false }"
        x-init="
            $watch('$wire.photoKey', () => {
                $nextTick(() => { preview = ''; changed = false; });
            });
        ">
        <div class="d-flex align-items-center gap-4">
            {{-- Preview: photo or initials fallback --}}
            <div class="flex-shrink-0">
                <div class="symbol symbol-100px symbol-circle overflow-hidden">
                    <template x-if="!changed && preview">
                        <img :src="preview" alt="Foto profil" class="w-100 h-100 object-fit-cover" />
                    </template>
                    <template x-if="changed || !preview">
                        <span class="symbol-label fs-2qx fw-bold"
                            style="background-color: #e4e6ef; color: #5e6278;">
                            {{ $this->initials }}
                        </span>
                    </template>
                </div>
            </div>

            {{-- Upload controls --}}
            <div class="d-flex flex-column gap-2">
                <label class="btn btn-sm btn-light-primary" style="cursor: pointer;">
                    <i class="ki-duotone ki-pencil fs-6 me-1">
                        <span class="path1"></span><span class="path2"></span>
                    </i>
                    {{ $this->photoUrl ? 'Ganti Foto' : 'Unggah Foto' }}
                    <input type="file"
                        wire:model="photo"
                        accept="image/*"
                        class="d-none"
                        x-on:change="
                            const file = $event.target.files[0];
                            if (file) {
                                preview = URL.createObjectURL(file);
                                changed = true;
                            }
                        " />
                </label>

                @if ($this->photoUrl)
                <button type="button"
                    class="btn btn-sm btn-light-danger"
                    wire:click="removePhoto"
                    wire:confirm="Hapus foto profil?">
                    <i class="ki-duotone ki-cross fs-6 me-1">
                        <span class="path1"></span><span class="path2"></span>
                    </i>
                    Hapus Foto
                </button>
                @endif
            </div>
        </div>

        @error('photo')
        <div class="text-danger fs-8 mt-2">{{ $message }}</div>
        @enderror

        <div wire:loading wire:target="photo" class="mt-2">
            <span class="text-muted fs-8">
                <span class="spinner-border spinner-border-sm me-1"></span>
                Mengunggah...
            </span>
        </div>
    </div>
</section>
