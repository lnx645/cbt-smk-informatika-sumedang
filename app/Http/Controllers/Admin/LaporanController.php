<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\LaporanExportService;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LaporanController extends Controller
{
    public function __construct(private readonly LaporanExportService $laporan)
    {
    }

    public function index(): InertiaResponse
    {
        return Inertia::render('admin/Laporan/Index', [
            'counts' => $this->laporan->counts(),
        ]);
    }

    public function exportXlsx(): BinaryFileResponse
    {
        $path = tempnam(sys_get_temp_dir(), 'laporan-') . '.xlsx';

        $writer = new Writer(new Options);
        $writer->openToFile($path);

        $headerStyle = (new Style)->withFontBold(true)->withBackgroundColor('DDEBF7');

        $datasets = $this->laporan->datasets();

        foreach ($datasets as $index => $dataset) {
            if ($index > 0) {
                $writer->addNewSheetAndMakeItCurrent();
            }
            $sheetName = $dataset['title'];
            $writer->getCurrentSheet()->setName($this->namaSheet($sheetName));

            $writer->addRow(Row::fromValuesWithStyle($dataset['headers'], $headerStyle));

            foreach ($dataset['rows'] as $row) {
                $writer->addRow(Row::fromValues($row));
            }
        }

        $writer->close();

        return response()->download($path, $this->namaFile('xlsx'), [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    private function namaSheet(string $name): string
    {
        // Excel: nama sheet maksimal 31 karakter, tidak boleh mengandung : \ / ? * [ ]
        $name = preg_replace('/[:\\\\\/\?\*\[\]]/', '-', $name);

        return mb_substr($name, 0, 31);
    }

    private function namaFile(string $ext): string
    {
        return 'laporan-data-kelas-digital-' . date('Y-m-d') . '.' . $ext;
    }
}