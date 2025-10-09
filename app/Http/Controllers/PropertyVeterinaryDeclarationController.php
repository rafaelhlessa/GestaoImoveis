<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\PropertyVeterinaryDeclaration;
use App\Models\PropertyVeterinaryDeclarationBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Spatie\LaravelPdf\Facades\Pdf;

class PropertyVeterinaryDeclarationController extends Controller
{
    public function store(Property $property, Request $request)
    {
        $data = $request->validate([
            'entries' => 'required|array|min:1',
            'entries.*.species' => 'required|string|max:255',
            'entries.*.age_band' => 'required|string|max:255',
            'entries.*.qty_males' => 'nullable|integer|min:0',
            'entries.*.qty_females' => 'nullable|integer|min:0',
            'note' => 'nullable|string|max:500',
        ]);

        // Create batch for this declaration (group of entries)
        $batch = PropertyVeterinaryDeclarationBatch::create([
            'property_id' => $property->id,
            'created_by' => Auth::id(),
            'note' => $data['note'] ?? null,
        ]);

        $created = [];
        foreach ($data['entries'] as $entry) {
            $created[] = PropertyVeterinaryDeclaration::create([
                'property_id' => $property->id,
                'batch_id' => $batch->id,
                'species' => $entry['species'],
                'age_band' => $entry['age_band'],
                'qty_males' => (int)($entry['qty_males'] ?? 0),
                'qty_females' => (int)($entry['qty_females'] ?? 0),
                'created_by' => Auth::id(),
            ]);
        }

        return response()->json(['success' => true, 'batch' => $batch, 'entries' => $created]);
    }

    public function index(Property $property)
    {
        $batches = PropertyVeterinaryDeclarationBatch::where('property_id', $property->id)
            ->orderBy('created_at', 'desc')
            ->with(['entries' => function($q){
                $q->orderBy('created_at');
            }])
            ->get();

        return response()->json($batches);
    }

    public function show(Property $property, PropertyVeterinaryDeclaration $declaration)
    {
        abort_if($declaration->property_id !== $property->id, 404);
        return response()->json($declaration);
    }

    public function generatePdf(Property $property)
    {
        $entries = PropertyVeterinaryDeclaration::where('property_id', $property->id)->get();

        $html = View::make('pdf.veterinary_declaration', [
            'property' => $property,
            'entries' => $entries,
        ])->render();

        $fileName = 'veterinaria_declaracao_property_'.$property->id.'_'.now()->format('Ymd_His').'.pdf';
        $path = 'declarations/'.$fileName;

        // Ensure directory exists
        if (!Storage::disk('public')->exists('declarations')) {
            Storage::disk('public')->makeDirectory('declarations');
        }

        // Generate and save PDF using spatie/laravel-pdf (chromium)
        Pdf::html($html)
            ->margins(10, 10, 10, 10)
            ->save(Storage::disk('public')->path($path));

        return response()->json([
            'success' => true,
            'url' => asset('storage/' . $path),
            'path' => $path,
        ]);
    }

    public function generateSinglePdf(Property $property, PropertyVeterinaryDeclaration $declaration)
    {
        abort_if($declaration->property_id !== $property->id, 404);

        $entries = collect([$declaration]);

        $html = View::make('pdf.veterinary_declaration', [
            'property' => $property,
            'entries' => $entries,
        ])->render();

        if (!Storage::disk('public')->exists('declarations')) {
            Storage::disk('public')->makeDirectory('declarations');
        }

        $fileName = 'veterinaria_declaracao_'.$declaration->id.'_prop_'.$property->id.'_'.now()->format('Ymd_His').'.pdf';
        $path = 'declarations/'.$fileName;

        Pdf::html($html)
            ->margins(10, 10, 10, 10)
            ->save(Storage::disk('public')->path($path));

        return response()->json([
            'success' => true,
            'url' => asset('storage/' . $path),
            'path' => $path,
        ]);
    }

    public function generateBatchPdf(Property $property, PropertyVeterinaryDeclarationBatch $batch)
    {
        abort_if($batch->property_id !== $property->id, 404);

        $entries = $batch->entries()->get();

        $html = View::make('pdf.veterinary_declaration', [
            'property' => $property,
            'entries' => $entries,
        ])->render();

        if (!Storage::disk('public')->exists('declarations')) {
            Storage::disk('public')->makeDirectory('declarations');
        }

        $fileName = 'veterinaria_declaracao_batch_'.$batch->id.'_prop_'.$property->id.'_'.now()->format('Ymd_His').'.pdf';
        $path = 'declarations/'.$fileName;

        Pdf::html($html)
            ->margins(10, 10, 10, 10)
            ->save(Storage::disk('public')->path($path));

        return response()->json([
            'success' => true,
            'url' => asset('storage/' . $path),
            'path' => $path,
        ]);
    }
}
