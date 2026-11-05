<div x-data="{ preview: null, previewName: '' }">
    <div class="mb-3">
        <label class="form-label"><i class="bi bi-camera me-1"></i>Foto</label>

        {{-- Upload Area --}}
        <div class="mb-2">
            <input type="file" wire:model="files" class="form-control @error('files.*') is-invalid @enderror" multiple accept="image/*"
                   x-on:livewire-upload-start="window.dispatchEvent(new CustomEvent('media-busy-start'))"
                   x-on:livewire-upload-cancel="window.dispatchEvent(new CustomEvent('media-busy-end'))"
                   x-on:livewire-upload-error="window.dispatchEvent(new CustomEvent('media-busy-end'))">
            @error('files.*')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div wire:loading wire:target="files, uploadFiles">
            <div class="alert alert-info py-2 mb-2">
                <i class="bi bi-hourglass-split me-1"></i>Caricamento...
            </div>
        </div>

        {{-- Lightbox in-app per le immagini --}}
        <template x-if="preview">
            <div class="position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center p-3"
                 style="background: rgba(0,0,0,0.9); z-index: 2000;"
                 @click.self="preview = null"
                 @keydown.escape.window="preview = null">
                <button type="button" class="btn btn-light position-absolute top-0 end-0 m-3"
                        @click="preview = null" aria-label="Chiudi">
                    <i class="bi bi-x-lg"></i>
                </button>
                <div class="text-center">
                    <img :src="preview" :alt="previewName"
                         style="max-height: 85vh; max-width: 95vw; object-fit: contain;"
                         class="rounded shadow">
                    <div class="text-white-50 small mt-2" x-text="previewName"></div>
                </div>
            </div>
        </template>

        {{-- Uploaded Media Grid --}}
        @if($media->count() > 0)
            <div class="row g-2 mt-2">
                @foreach($media as $item)
                    <div class="col-4">
                        <div class="position-relative">
                            @if(str_contains($item->file_type, 'image'))
                                <img src="{{ Storage::url($item->file_path) }}"
                                     class="img-fluid rounded"
                                     style="width: 100%; height: 100px; object-fit: cover; cursor: zoom-in;"
                                     alt="{{ $item->file_name }}"
                                     title="Clicca per ingrandire"
                                     @click="preview = '{{ Storage::url($item->file_path) }}'; previewName = @js($item->file_name)">
                            @else
                                <a href="{{ Storage::url($item->file_path) }}" target="_blank" rel="noopener"
                                   class="bg-light rounded d-flex align-items-center justify-content-center text-decoration-none"
                                   style="height: 100px;" title="Apri {{ $item->file_name }}">
                                    <i class="bi bi-file-earmark" style="font-size: 32px; color: #6c757d;"></i>
                                </a>
                            @endif
                            <button type="button" wire:click="deleteMedia({{ $item->id }})" class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1" style="padding: 0.25rem 0.5rem;" onclick="return confirm('Eliminare questa foto?')">
                                <i class="bi bi-x"></i>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

</div>
