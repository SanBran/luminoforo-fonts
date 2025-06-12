<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;

class FontController extends Controller
{
    public function getFonts() 
    {
        $sourcePath = base_path('resources/fonts');

        $publicUrlPath = 'fonts';

        $categories = [];

        if (!File::exists($sourcePath)) {
            return response()->json($categories);
        }

        foreach (File::directories($sourcePath) as $categoryPath) {
            $categoryName = basename($categoryPath);

            $subCategoryPaths = File::directories($categoryPath);

            if (empty($subCategoryPaths)) {
                continue; 
            }

            $categories[$categoryName] = [];

            foreach ($subCategoryPaths as $subCategoryPath) {
                $subCategoryName = basename($subCategoryPath);
                $categories[$categoryName][$subCategoryName] = [];

                foreach (File::files($subCategoryPath) as $file) {
                    if (in_array($file->getExtension(), ['ttf', 'otf', 'woff', 'woff2'])) {
                        $fontName = pathinfo($file->getFilename(), PATHINFO_FILENAME);
                        $styleName = preg_replace('/\s+|\d+/', '', $fontName);

                        $categories[$categoryName][$subCategoryName][] = [
                            'name' => $fontName,
                            'url' => asset("{$publicUrlPath}/{$categoryName}/{$subCategoryName}/{$file->getFilename()}"),
                            'styleName' => $styleName
                        ];
                    }
                }
            }
        }

        return response()->json($categories);
    }
}