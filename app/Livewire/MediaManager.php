<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Media;
use Illuminate\Support\Facades\Storage;

class MediaManager extends Component
{
    use WithFileUploads;

    public $mediableType;
    public $mediableId;
    public $files = [];
    public $description = '';

    protected $listeners = ['refreshMedia' => '$refresh'];

    public function mount($mediableType, $mediableId)
    {
        $this->mediableType = $mediableType;
        $this->mediableId = (int) $mediableId;
    }

    public function updatedFiles()
    {
        $this->validate([
            'files.*' => 'required|file|mimes:pdf,jpg,jpeg,png,gif,zip|max:10240',
        ], [
            'files.*.required' => 'Seleziona almeno un file',
            'files.*.file' => 'Il file non è valido',
            'files.*.mimes' => 'Sono accettati solo file PDF, immagini (JPG, PNG, GIF) e ZIP',
            'files.*.max' => 'Il file non può superare i 10MB',
        ]);
    }

    public function uploadFiles()
    {
        $this->validate([
            'files.*' => 'required|file|mimes:pdf,jpg,jpeg,png,gif,zip|max:10240',
        ], [
            'files.*.required' => 'Seleziona almeno un file',
            'files.*.file' => 'Il file non è valido',
            'files.*.mimes' => 'Sono accettati solo file PDF, immagini (JPG, PNG, GIF) e ZIP',
            'files.*.max' => 'Il file non può superare i 10MB',
        ]);

        foreach ($this->files as $file) {
            $fileName = $file->getClientOriginalName();
            $filePath = $file->store('media', 'public');

            // Supporta sia mediable reali che temporanei (TempMedia)
            Media::create([
                'mediable_type' => $this->mediableType,
                'mediable_id' => $this->mediableId,
                'file_name' => $fileName,
                'file_path' => $filePath,
                'file_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'description' => $this->description,
            ]);
        }

        $this->reset(['files', 'description']);
        session()->flash('success', 'File caricati con successo!');
    }

    public function deleteMedia($mediaId)
    {
        $media = Media::find($mediaId);

        // Supporta eliminazione sia per mediable reali che temporanei
        if ($media && $media->mediable_type === $this->mediableType && $media->mediable_id == $this->mediableId) {
            Storage::disk('public')->delete($media->file_path);
            $media->delete();

            session()->flash('success', 'File eliminato con successo!');
        }
    }

    public function downloadMedia($mediaId)
    {
        $media = Media::find($mediaId);

        // Supporta download sia per mediable reali che temporanei
        if ($media && $media->mediable_type === $this->mediableType && $media->mediable_id == $this->mediableId) {
            return Storage::disk('public')->download($media->file_path, $media->file_name);
        }
    }

    public function render()
    {
        // Supporta recupero sia per mediable reali che temporanei
        $media = Media::where('mediable_type', $this->mediableType)
            ->where('mediable_id', $this->mediableId)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('livewire.media-manager', [
            'media' => $media,
        ]);
    }
}
