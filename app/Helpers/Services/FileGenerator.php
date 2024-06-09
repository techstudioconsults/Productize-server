<?php

/**
 * @author @Intuneteq Tobi Olanitori
 * @version 1.0
 * @since 09-06-2024
 */

namespace App\Helpers\Services;

use Illuminate\Support\Facades\Storage;
use Mpdf\Mpdf;

class FileGenerator
{
    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Generate a CSV file.
     *
     * @param string $fileName
     * @param array $data
     *
     * @return string The path to the generated CSV file.
     */
    public function generateCsv(string $fileName, array $data): string
    {
        $csvContent = '';
        foreach ($data as $csvRow) {
            $csvContent .= implode(',', $csvRow) . "\n";
        }

        $filePath = 'csv/' . $fileName;
        Storage::disk('local')->put($filePath, $csvContent);

        return $filePath;
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Generate a PDF file.
     *
     * @param string $fileName
     * @param string $htmlContent
     * @return string The path to the generated PDF file.
     */
    public function generatePdf(string $fileName, string $htmlContent): string
    {
        $mpdf = new Mpdf();
        $mpdf->WriteHTML($htmlContent);

        $filePath = 'pdf/' . $fileName;
        Storage::disk('local')->put($filePath, $mpdf->Output('', 'S'));

        return $filePath;
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     * 
     * Stream a file to the client for download.
     *
     * @param string $filePath
     * @param string $fileName
     * @param string $mimeType
     * @param bool   $delete Delete the file from storage after stream
     * @return \Illuminate\Http\Response
     */
    public function streamFile(string $filePath, string $fileName, string $mimeType, ?bool $delete = true)
    {
        $headers = [
            "Content-type"        => $mimeType,
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        return response()->stream(function () use ($filePath, $delete) {
            readfile(storage_path('app/' . $filePath));

            if ($delete) {
                Storage::disk('local')->delete($filePath);
            }
        }, 200, $headers);
    }
}
