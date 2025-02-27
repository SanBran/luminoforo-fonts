<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;

class FontController extends Controller
{
    public function getFonts() 
    {
        $fontsPath = public_path('fonts');
        $categories = [];

        // Recorrer todas las carpetas dentro de fonts/
        foreach (File::directories($fontsPath) as $categoryPath) {
            $categoryName = basename($categoryPath);
            $categories[$categoryName] = [];

            // Recorrer subcategorías dentro de la categoría actual
            foreach (File::directories($categoryPath) as $subCategoryPath) {
                $subCategoryName = basename($subCategoryPath);
                $categories[$categoryName][$subCategoryName] = [];

                // Obtener archivos de fuentes dentro de la subcategoría
                foreach (File::files($subCategoryPath) as $file) {
                    if (in_array($file->getExtension(), ['ttf', 'otf', 'woff', 'woff2'])) {
                        $fontName = pathinfo($file->getFilename(), PATHINFO_FILENAME);
                        $styleName = preg_replace('/\s+|\d+/', '', $fontName);

                        $categories[$categoryName][$subCategoryName][] = [
                            'name' => $fontName,
                            'url' => asset("fonts/{$categoryName}/{$subCategoryName}/{$file->getFilename()}"),
                            'styleName' => $styleName
                        ];
                    }
                }
            }
        }

        return response()->json($categories);
    }
}
