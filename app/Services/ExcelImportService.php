<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ExcelImportService
{
    public function validateAndImportFile(Request $request)
    {
        try {
            $request->validate([
                'import_file' => 'required|file|mimes:xlsx,xls,csv|max:10240', // 10MB max
            ]);

            $file = $request->file('import_file');
            $path = $file->getRealPath();

            // Read the file content
            $data = $this->readExcelFile($path, $file->getClientOriginalExtension());

            if (empty($data)) {
                return [
                    'success' => false,
                    'message' => 'File is empty or has no valid data rows.'
                ];
            }

            // Validate and process data
            $validatedData = $this->validateImportData($data);

            if (!$validatedData['success']) {
                return $validatedData;
            }

            // Here you would typically save to database
            // For now, we'll just return success with data info

            return [
                'success' => true,
                'message' => 'File imported successfully!',
                'records_count' => count($validatedData['data']),
                'data' => $validatedData['data']
            ];
        } catch (\Exception $e) {
            Log::error('Excel Import Error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Error importing file: ' . $e->getMessage()
            ];
        }
    }

    private function readExcelFile($path, $extension)
    {
        $data = [];

        if (in_array($extension, ['csv'])) {
            // Read CSV file
            if (($handle = fopen($path, "r")) !== FALSE) {
                $lineNumber = 0;
                $headers = null;

                while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    $lineNumber++;

                    // Skip metadata rows and find the header row
                    if ($lineNumber <= 10 && $row[0] && (
                        strpos($row[0], 'Agent CSAT') !== false ||
                        strpos($row[0], 'Generated') !== false ||
                        strpos($row[0], 'Total Records') !== false ||
                        strpos($row[0], 'Search Filter') !== false ||
                        strpos($row[0], 'Date From') !== false ||
                        strpos($row[0], 'Date To') !== false ||
                        empty(trim($row[0]))
                    )) {
                        continue;
                    }

                    // Check if this is the header row
                    if (!$headers && count($row) >= 5 && (
                        strtolower($row[0]) === 'no' ||
                        strtolower($row[1]) === 'customer name' ||
                        strpos(strtolower($row[1]), 'customer') !== false
                    )) {
                        $headers = $row;
                        continue;
                    }

                    // Process data rows
                    if ($headers && count($row) >= 6) {
                        // Skip empty rows or rows with just numbers
                        if (empty(trim($row[1])) && empty(trim($row[2]))) {
                            continue;
                        }

                        $data[] = [
                            'customer_name' => trim($row[1] ?? ''), // Column 1: Customer Name
                            'agent_name' => trim($row[2] ?? ''),    // Column 2: Agent Name
                            'date' => trim($row[3] ?? ''),          // Column 3: Date
                            'requested_at' => trim($row[4] ?? ''),  // Column 4: Time Requested
                            'responded_at' => trim($row[5] ?? ''),  // Column 5: Time Responded
                        ];
                    }
                }
                fclose($handle);
            }
        } else {
            // For Excel files (.xlsx, .xls), try to read as HTML-based Excel format
            $content = file_get_contents($path);

            // If file starts with HTML tags, it's likely our HTML-based Excel format
            if (strpos($content, '<html>') !== false || strpos($content, '<table>') !== false) {
                $data = $this->parseHTMLExcel($content);
            } else {
                // For binary Excel files, provide a simple fallback
                // In production, you should use PhpSpreadsheet library
                Log::warning('Binary Excel file detected. Consider using PhpSpreadsheet for better support.');

                // Try to extract some basic data (this is a very simplified approach)
                $data = $this->parseSimpleExcel($path);
            }
        }

        return $data;
    }

    private function parseHTMLExcel($content)
    {
        $data = [];

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($content);
        libxml_clear_errors();

        $tables = $dom->getElementsByTagName('table');
        if ($tables->length > 0) {
            $table = $tables->item(0);
            $rows = $table->getElementsByTagName('tr');

            $headerSkipped = false;
            foreach ($rows as $row) {
                // ✅ pastikan $row adalah DOMElement
                if (!$row instanceof \DOMElement) {
                    continue;
                }

                $cells = $row->getElementsByTagName('td');
                if ($cells->length >= 5) {
                    // Skip header row
                    if (!$headerSkipped) {
                        $headerSkipped = true;
                        continue;
                    }

                    $rowData = [];
                    for ($i = 0; $i < 5; $i++) {
                        $cellValue = $cells->item($i) ? trim($cells->item($i)->textContent) : '';
                        $rowData[] = $cellValue;
                    }

                    if (!empty($rowData[0]) || !empty($rowData[1])) {
                        $data[] = [
                            'customer_name' => $rowData[0],
                            'agent_name'    => $rowData[1],
                            'date'          => $rowData[2],
                            'requested_at'  => $rowData[3],
                            'responded_at'  => $rowData[4],
                        ];
                    }
                }
            }
        }

        return $data;
    }

    private function parseSimpleExcel($path)
    {
        // This is a very basic fallback for binary Excel files
        // In production, use PhpSpreadsheet library for proper Excel reading
        $data = [];

        try {
            // Try to read file content and extract text
            $content = file_get_contents($path);

            // This is a very simplified approach and may not work for all Excel files
            // For production use, implement PhpSpreadsheet
            Log::info('Attempting basic Excel parsing. Results may be limited.');
        } catch (\Exception $e) {
            Log::error('Excel parsing error: ' . $e->getMessage());
        }

        return $data;
    }

    private function validateImportData($data)
    {
        $validatedData = [];
        $errors = [];
        $rowNumber = 1;

        foreach ($data as $row) {
            $rowNumber++;
            $rowErrors = [];

            // Validate customer name
            if (empty($row['customer_name'])) {
                $rowErrors[] = "Customer name is required";
            }

            // Validate agent name
            if (empty($row['agent_name'])) {
                $rowErrors[] = "Agent name is required";
            }

            // Validate date
            if (!empty($row['date'])) {
                try {
                    $date = Carbon::createFromFormat('d M Y', $row['date']);
                    $row['date'] = $date->format('d M Y');
                } catch (\Exception $e) {
                    $rowErrors[] = "Invalid date format. Use: DD MMM YYYY (e.g., 01 Aug 2025)";
                }
            } else {
                $rowErrors[] = "Date is required";
            }

            // Validate requested time
            if (!empty($row['requested_at'])) {
                if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $row['requested_at'])) {
                    $rowErrors[] = "Invalid requested time format. Use: HH:MM:SS";
                }
            } else {
                $rowErrors[] = "Requested time is required";
            }

            // Validate responded time
            if (!empty($row['responded_at'])) {
                if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $row['responded_at'])) {
                    $rowErrors[] = "Invalid responded time format. Use: HH:MM:SS";
                }
            } else {
                $rowErrors[] = "Responded time is required";
            }

            if (!empty($rowErrors)) {
                $errors[] = "Row {$rowNumber}: " . implode(', ', $rowErrors);
            } else {
                $validatedData[] = $row;
            }
        }

        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => 'Validation errors found: ' . implode(' | ', $errors)
            ];
        }

        return [
            'success' => true,
            'data' => $validatedData
        ];
    }
}
