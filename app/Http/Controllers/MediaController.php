<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\EquipmentComponent;
use App\Models\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    private const ALLOWED_TYPES = [
        'TempMedia',
        Equipment::class,
        EquipmentComponent::class,
    ];

    public function store(Request $request): JsonResponse
    {
        abort_unless(Auth::check(), 401);

        $data = $request->validate([
            'mediable_type' => 'required|string',
            'mediable_id' => 'required|string',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png,gif,zip|max:10240',
            'description' => 'nullable|string|max:1000',
        ], [
            'file.required' => 'Seleziona un file',
            'file.mimes' => 'Sono accettati solo PDF, immagini (JPG, PNG, GIF) e ZIP',
            'file.max' => 'Il file non può superare i 10MB',
        ]);

        abort_unless(in_array($data['mediable_type'], self::ALLOWED_TYPES, true), 422, 'mediable_type non ammesso');

        $file = $request->file('file');
        $filePath = $file->store('media', 'public');

        $media = Media::create([
            'mediable_type' => $data['mediable_type'],
            'mediable_id' => $data['mediable_id'],
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $filePath,
            'file_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'description' => $data['description'] ?? null,
        ]);

        return response()->json([
            'id' => $media->id,
            'file_name' => $media->file_name,
            'file_type' => $media->file_type,
            'file_size' => $media->file_size,
            'is_image' => str_contains($media->file_type, 'image'),
            'url' => Storage::url($media->file_path),
        ]);
    }

    public function destroy(Media $media): JsonResponse
    {
        abort_unless(Auth::check(), 401);

        Storage::disk('public')->delete($media->file_path);
        $media->delete();

        return response()->json(['ok' => true]);
    }
}
