<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CreateComponentController extends Controller
{
    public function createFile(Request $request)
    {
        try {
            $path = $request->path;
            $content = $request->content;
            
            // Create directories if they don't exist
            $directory = dirname(base_path($path));
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }
            
            // Write the file
            file_put_contents(base_path($path), $content);
            
            return response()->json([
                'success' => true,
                'message' => 'Arquivo criado com sucesso'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
