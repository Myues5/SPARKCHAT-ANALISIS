<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;

class AgentCsatExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, ShouldAutoSize
{
    private Collection $rows;
    private array $filters;

    public string $fileName;

    public function __construct(array $data, array $filters = [])
    {
        $this->rows = collect($data);
        $this->filters = $filters;
        $this->fileName = 'Agent_CSAT_Report_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
    }

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            '#',
            'Customer Name',
            'Agent Name',
            'Date',
            'First Response Time',
            'Average Response Time',
            'Resolved Time',
        ];
    }

    public function map($row): array
    {
        static $no = 0;
        $no++;
        return [
            $no,
            $row['customer_name'] ?? '',
            $row['agent_name'] ?? '',
            $row['date'] ?? '',
            $row['first_response_time'] ?? '',
            $row['average_response_time'] ?? '',
            $row['resolved_time'] ?? '',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Header style
        $sheet->getStyle('A1:G1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '003366']],
            'fill' => [
                'fillType' => 'solid',
                'color' => ['rgb' => '87CEEB']
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => 'thin',
                    'color' => ['rgb' => '5DADE2']
                ]
            ]
        ]);

        $highestRow = $sheet->getHighestRow();
        if ($highestRow > 1) {
            $sheet->getStyle('A2:G' . $highestRow)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => 'thin',
                        'color' => ['rgb' => 'CCCCCC']
                    ]
                ]
            ]);
        }

        // Freeze
        $sheet->freezePane('A2');

        return [
            1 => ['font' => ['size' => 12]]
        ];
    }

    public function title(): string
    {
        return 'CSAT Report';
    }
}
