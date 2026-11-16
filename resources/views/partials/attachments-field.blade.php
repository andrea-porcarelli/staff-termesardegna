{{--
  Widget allegati riusabile (foto + PDF + ZIP). Richiede autenticazione.

  Variabili attese:
    - $dataExpr : espressione JS passata ad attachmentsField(...), es.
          "{ type: 'TempMedia', id: @js($tempMediaId), initial: @js($initial) }"
      Permette ad Alpine di risolvere `id` sia come stringa statica che come
      binding dinamico (es. `comp.temp_media_id` dentro un x-for).
    - $label (opzionale) : etichetta visibile sopra il campo.
--}}
@php
    $label = $label ?? 'Foto e documenti (PDF)';
@endphp
<div class="mb-3" x-data="attachmentsField({{ $dataExpr }})">
    <label class="form-label">
        <i class="bi bi-paperclip me-1"></i>{{ $label }}
    </label>

    <input type="file" class="form-control" multiple
           accept="image/*,application/pdf,.pdf,application/zip,.zip"
           :disabled="uploading"
           @change="handleFiles($event)">
    <small class="form-text text-muted">
        JPG, PNG, GIF, PDF, ZIP — max 10MB per file.
    </small>

    <template x-if="uploading">
        <div class="alert alert-info py-2 mt-2 mb-0">
            <i class="bi bi-hourglass-split me-1"></i>Caricamento in corso…
        </div>
    </template>

    <template x-if="errorMsg">
        <div class="alert alert-danger py-2 mt-2 mb-0" x-text="errorMsg"></div>
    </template>

    <div class="row g-2 mt-2" x-show="items.length > 0" x-cloak>
        <template x-for="m in items" :key="m.id">
            <div class="col-4 col-md-3 col-lg-2">
                <div class="position-relative">
                    <template x-if="m.is_image">
                        <a :href="m.url" target="_blank" rel="noopener">
                            <img :src="m.url" :alt="m.file_name"
                                 class="img-fluid rounded border"
                                 style="width: 100%; height: 100px; object-fit: cover;">
                        </a>
                    </template>
                    <template x-if="!m.is_image">
                        <a :href="m.url" target="_blank" rel="noopener"
                           class="bg-light rounded border d-flex flex-column align-items-center justify-content-center text-decoration-none p-1"
                           style="height: 100px;">
                            <i class="bi bi-file-earmark-pdf"
                               x-show="m.file_type && m.file_type.includes('pdf')"
                               style="font-size: 32px; color: #c82333;"></i>
                            <i class="bi bi-file-earmark-zip"
                               x-show="m.file_type && m.file_type.includes('zip')"
                               style="font-size: 32px; color: #6c757d;"></i>
                            <i class="bi bi-file-earmark"
                               x-show="!m.file_type || (!m.file_type.includes('pdf') && !m.file_type.includes('zip'))"
                               style="font-size: 32px; color: #6c757d;"></i>
                            <small class="text-truncate d-block mt-1"
                                   style="max-width: 100%; font-size: 0.65rem; color: #333;"
                                   x-text="m.file_name"></small>
                        </a>
                    </template>
                    <button type="button"
                            class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1"
                            style="padding: 0.1rem 0.35rem; line-height: 1;"
                            @click.prevent="remove(m.id)"
                            title="Elimina">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
            </div>
        </template>
    </div>
</div>

@once
@push('scripts')
<script>
window.attachmentsField = function ({ type, id, initial }) {
    return {
        type: type,
        id: String(id ?? ''),
        items: Array.isArray(initial) ? initial.map(m => ({ ...m })) : [],
        uploading: false,
        errorMsg: '',

        async handleFiles(e) {
            const files = Array.from(e.target.files || []);
            if (files.length === 0) return;
            if (!this.id) {
                this.errorMsg = 'ID allegato mancante: ricarica la pagina.';
                e.target.value = '';
                return;
            }

            this.uploading = true;
            this.errorMsg = '';
            const token = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

            try {
                for (const f of files) {
                    const fd = new FormData();
                    fd.append('mediable_type', this.type);
                    fd.append('mediable_id', this.id);
                    fd.append('file', f);

                    const res = await fetch('/media', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': token,
                            'Accept': 'application/json',
                        },
                        body: fd,
                    });

                    if (!res.ok) {
                        let msg = 'Errore caricamento (' + res.status + ')';
                        try {
                            const err = await res.json();
                            if (err.message) msg = err.message;
                            if (err.errors) {
                                const firstKey = Object.keys(err.errors)[0];
                                if (firstKey && err.errors[firstKey][0]) {
                                    msg = err.errors[firstKey][0];
                                }
                            }
                        } catch (_) {}
                        throw new Error(msg);
                    }

                    const m = await res.json();
                    this.items.push(m);
                }
            } catch (ex) {
                this.errorMsg = ex.message || 'Errore durante il caricamento';
            } finally {
                this.uploading = false;
                e.target.value = '';
            }
        },

        async remove(mediaId) {
            if (!confirm('Eliminare questo allegato?')) return;
            const token = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
            try {
                const res = await fetch('/media/' + mediaId, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json',
                    },
                });
                if (!res.ok) throw new Error('Errore eliminazione');
                this.items = this.items.filter(m => m.id !== mediaId);
            } catch (ex) {
                this.errorMsg = ex.message || 'Errore eliminazione';
            }
        },
    };
};
</script>
@endpush
@endonce
