<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ExcelExportService
{
    /**
     * Generate Excel report with blue header and formatted table
     */
    public function generateAgentCsatReport($data, $filters = [], $agentId = null)
    {
        $suffix = $agentId ? ('_' . preg_replace('/[^A-Za-z0-9_-]/', '', $agentId)) : '';
        $filename = 'Agent_CSAT_Report' . $suffix . '_' . Carbon::now()->format('Y-m-d_H-i-s') . '.xls';
        // Pre-initialize HTML (avoids static analysis warning) and then log
        $html = "\xEF\xBB\xBF"; // UTF-8 BOM required by some Excel versions
        Log::info('Generating Export (enhanced)', ['data_count' => count($data), 'filters' => $filters, 'filename' => $filename]);

        // Build rich HTML so Excel can render formatting.
        $html .= '<!DOCTYPE html><html><head><meta charset="UTF-8">';
        $html .= '<style>body{font-family:Arial,sans-serif;font-size:12px;}table{border-collapse:collapse;width:100%;}th,td{border:1px solid #ccc;padding:6px;}th{background:#87CEEB;color:#003366;text-align:left;}tbody tr:nth-child(even){background:#f9f9f9;} .number{text-align:center;font-weight:bold;}</style>';
        $html .= '</head><body>';
        $html .= '<h2>Agent CSAT Response Report</h2>';
        $html .= '<p>Generated: ' . Carbon::now()->format('Y-m-d H:i:s') . '</p>';
        if (!empty($filters)) {
            $html .= '<p>Filters: ';
            $parts = [];
            foreach ($filters as $k => $v) {
                if ($v === '' || $v === null)
                    continue;
                $parts[] = htmlspecialchars($k) . '=' . htmlspecialchars($v);
            }
            $html .= implode(', ', $parts) . '</p>';
        }
        $html .= '<table><thead><tr>';
        $columns = [
            'No' => 'number',
            'Customer Name' => '',
            'Agent Name' => '',
            'Date' => '',
            'First Response Time' => '',
            'Average Response Time' => '',
            'Resolved Time' => ''
        ];
        foreach ($columns as $label => $cls) {
            $html .= '<th>' . $label . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        $i = 1;
        foreach ($data as $row) {
            $html .= '<tr>';
            $html .= '<td class="number">' . $i++ . '</td>';
            $html .= '<td>' . htmlspecialchars($row['customer_name'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($row['agent_name'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($row['date'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($row['first_response_time'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($row['average_response_time'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($row['resolved_time'] ?? '') . '</td>';
            $html .= '</tr>';
        }
        if (count($data) === 0) {
            $html .= '<tr><td colspan="7" style="text-align:center;">No data available</td></tr>';
        }
        $html .= '</tbody></table>';
        $html .= '</body></html>';

        return response($html)
            ->header('Content-Type', 'application/vnd.ms-excel; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Cache-Control', 'max-age=0');
    }

    /**
     * Generate Review Log Excel report with rating breakdown
     */
    public function generateReviewLogReport($data, $filters = [])
    {
        $filename = 'Review_Log_Report_' . Carbon::now()->format('Y-m-d_H-i-s') . '.xls';

        Log::info('Generating Review Log Export', [
            'data_count' => count($data),
            'filters' => $filters,
            'filename' => $filename
        ]);

        // Build HTML for Excel compatibility
        $html = "\xEF\xBB\xBF"; // UTF-8 BOM
        $html .= '<!DOCTYPE html><html><head><meta charset="UTF-8">';
        $html .= '<style>
            body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; }
            .header {
                background: linear-gradient(135deg, #e8f5e8 0%, #c8e6c9 100%);
                padding: 20px;
                border-radius: 10px;
                margin-bottom: 25px;
                border: 2px solid #81c784;
            }
            .header h1 { color: #2e7d32; margin: 0 0 10px 0; font-size: 24px; }
            .header .info { color: #388e3c; margin: 5px 0; font-size: 14px; }
            table { border-collapse: collapse; width: 100%; margin-top: 10px; }
            th {
                background: linear-gradient(135deg, #87ceeb 0%, #4fc3f7 100%);
                color: #0d47a1;
                padding: 12px 8px;
                border: 1px solid #42a5f5;
                text-align: center;
                font-weight: bold;
                font-size: 12px;
                text-transform: uppercase;
            }
            td {
                padding: 8px;
                border: 1px solid #ddd;
                text-align: center;
                font-size: 11px;
            }
            .text-left { text-align: left; }
            .rating-5 { background-color: #c8e6c9; color: #2e7d32; font-weight: bold; }
            .rating-4 { background-color: #dcedc8; color: #33691e; }
            .rating-3 { background-color: #fff3e0; color: #ef6c00; }
            .rating-2 { background-color: #ffecb3; color: #f57c00; }
            .rating-1 { background-color: #ffcdd2; color: #d32f2f; font-weight: bold; }
            tbody tr:nth-child(even) { background-color: #f9f9f9; }
            .summary { margin-top: 20px; padding: 15px; background: #f5f5f5; border-radius: 5px; }
        </style>';
        $html .= '</head><body>';

        // Header section
        $html .= '<div class="header">';
        $html .= '<h1>📊 Review Log Report</h1>';
        $html .= '<div class="info">📅 Generated on: ' . Carbon::now()->format('F d, Y \a\t H:i:s') . '</div>';
        $html .= '<div class="info">📈 Total Records: ' . number_format(count($data)) . '</div>';

        if (!empty($filters['search'])) {
            $html .= '<div class="info">🔍 Search Filter: ' . htmlspecialchars($filters['search']) . '</div>';
        }
        if (!empty($filters['date_from'])) {
            $html .= '<div class="info">📅 Date From: ' . Carbon::parse($filters['date_from'])->format('F d, Y') . '</div>';
        }
        if (!empty($filters['date_to'])) {
            $html .= '<div class="info">📅 Date To: ' . Carbon::parse($filters['date_to'])->format('F d, Y') . '</div>';
        }
        $html .= '</div>';

        // Table
        $html .= '<table>';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th style="width: 5%;">No</th>';
        $html .= '<th style="width: 25%;">Agent Name</th>';
        $html .= '<th style="width: 12%;">Date</th>';
        $html .= '<th style="width: 8%;">Total Reviews</th>';
        $html .= '<th style="width: 10%;">Avg Rating</th>';
        $html .= '<th style="width: 8%;">Sangat Puas (5⭐)</th>';
        $html .= '<th style="width: 8%;">Puas (4⭐)</th>';
        $html .= '<th style="width: 8%;">Datar (3⭐)</th>';
        $html .= '<th style="width: 8%;">Sedih (2⭐)</th>';
        $html .= '<th style="width: 8%;">Marah (1⭐)</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        $totalReviews = 0;
        $totalSangatPuas = 0;
        $totalPuas = 0;
        $totalDatar = 0;
        $totalSedih = 0;
        $totalMarah = 0;

        foreach ($data as $item) {
            $ratingClass = 'rating-' . ceil($item['average_rating']);

            $html .= '<tr>';
            $html .= '<td>' . $item['id'] . '</td>';
            $html .= '<td class="text-left">' . htmlspecialchars($item['agent_name']) . '</td>';
            $html .= '<td>' . htmlspecialchars($item['date']) . '</td>';
            $html .= '<td><strong>' . $item['total_ratings'] . '</strong></td>';
            $html .= '<td class="' . $ratingClass . '">' . $item['average_rating'] . '</td>';
            $html .= '<td class="rating-5">' . $item['sangat_puas'] . '</td>';
            $html .= '<td class="rating-4">' . $item['puas'] . '</td>';
            $html .= '<td class="rating-3">' . $item['datar'] . '</td>';
            $html .= '<td class="rating-2">' . $item['sedih'] . '</td>';
            $html .= '<td class="rating-1">' . $item['marah'] . '</td>';
            $html .= '</tr>';

            // Accumulate totals
            $totalReviews += $item['total_ratings'];
            $totalSangatPuas += $item['sangat_puas'];
            $totalPuas += $item['puas'];
            $totalDatar += $item['datar'];
            $totalSedih += $item['sedih'];
            $totalMarah += $item['marah'];
        }

        // Add summary row
        $overallAverage = $totalReviews > 0 ? round((($totalSangatPuas * 5) + ($totalPuas * 4) + ($totalDatar * 3) + ($totalSedih * 2) + ($totalMarah * 1)) / $totalReviews, 2) : 0;

        $html .= '<tr style="background-color: #e3f2fd; font-weight: bold; border-top: 2px solid #1976d2;">';
        $html .= '<td colspan="3">TOTAL SUMMARY</td>';
        $html .= '<td><strong>' . $totalReviews . '</strong></td>';
        $html .= '<td class="rating-' . ceil($overallAverage) . '">' . $overallAverage . '</td>';
        $html .= '<td class="rating-5">' . $totalSangatPuas . '</td>';
        $html .= '<td class="rating-4">' . $totalPuas . '</td>';
        $html .= '<td class="rating-3">' . $totalDatar . '</td>';
        $html .= '<td class="rating-2">' . $totalSedih . '</td>';
        $html .= '<td class="rating-1">' . $totalMarah . '</td>';
        $html .= '</tr>';

        if (count($data) === 0) {
            $html .= '<tr><td colspan="10" style="text-align:center; padding: 20px;">No review data available for the selected period</td></tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';

        // Add summary statistics
        if ($totalReviews > 0) {
            $html .= '<div class="summary">';
            $html .= '<h3>📊 Summary Statistics</h3>';
            $html .= '<p><strong>Total Reviews:</strong> ' . number_format($totalReviews) . '</p>';
            $html .= '<p><strong>Overall Average Rating:</strong> ' . $overallAverage . '/5.0</p>';
            $html .= '<p><strong>Satisfaction Distribution:</strong></p>';
            $html .= '<ul>';
            $html .= '<li>Sangat Puas (5⭐): ' . $totalSangatPuas . ' (' . round(($totalSangatPuas / $totalReviews) * 100, 1) . '%)</li>';
            $html .= '<li>Puas (4⭐): ' . $totalPuas . ' (' . round(($totalPuas / $totalReviews) * 100, 1) . '%)</li>';
            $html .= '<li>Datar (3⭐): ' . $totalDatar . ' (' . round(($totalDatar / $totalReviews) * 100, 1) . '%)</li>';
            $html .= '<li>Sedih (2⭐): ' . $totalSedih . ' (' . round(($totalSedih / $totalReviews) * 100, 1) . '%)</li>';
            $html .= '<li>Marah (1⭐): ' . $totalMarah . ' (' . round(($totalMarah / $totalReviews) * 100, 1) . '%)</li>';
            $html .= '</ul>';
            $html .= '</div>';
        }

        $html .= '</body></html>';

        return response($html)
            ->header('Content-Type', 'application/vnd.ms-excel; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Cache-Control', 'max-age=0');
    }

    /**
     * Generate Message Log Excel report with conversation details
     */
    public function generateMessageLogReport(array $data, array $filters = [])
    {
        $filename = 'Message_Log_Report_' . Carbon::now()->format('Y-m-d_H-i-s') . '.xls';

        // Build HTML for Excel compatibility
        $html = "\xEF\xBB\xBF"; // UTF-8 BOM
        $html .= '<!DOCTYPE html><html><head><meta charset="UTF-8">';
        $html .= '<style>
        body {
                font-family: Arial, sans-serif;
                font-size: 12px;
                margin: 30px;
                background-color: #f8f9fa;
            }
            .header {
                background: linear-gradient(135deg, #e8f5e8 0%, #c8e6c9 100%);
                padding: 25px;
                border-radius: 12px;
                margin: 30px 0;
                border: 2px solid #81c784;
                box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            }
            .header h1 {
                color: #2e7d32;
                margin: 0 0 12px 0;
                font-size: 26px;
                font-weight: bold;
                text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
            }
            .header .info {
                color: #388e3c;
                margin: 8px 0;
                font-size: 14px;
                font-weight: 500;
            }
            .data-table {
                border-collapse: collapse;
                width: 100%;
                margin: 20px 0;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                border-radius: 8px;
                overflow: hidden;
            }
            th {
                background: linear-gradient(135deg, #87ceeb 0%, #4fc3f7 100%);
                color: #0d47a1;
                padding: 14px 10px;
                border: 2px solid #42a5f5;
                text-align: center;
                font-weight: bold;
                font-size: 13px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                text-shadow: 1px 1px 2px rgba(255,255,255,0.3);
            }
            td {
                padding: 12px 10px;
                border: 1px solid #e0e0e0;
                vertical-align: top;
                font-size: 11px;
                max-width: 300px;
                white-space: pre-wrap;
                line-height: 1.5;
            }
            .text-left { text-align: left; }
            .number-cell {
                text-align: center;
                font-weight: bold;
                background-color: #f0f8ff;
            }
            .agent-cell {
                font-weight: 500;
                color: #1976d2;
            }
            .date-cell {
                text-align: center;
                color: #424242;
            }
            .message-cell {
                font-family: "Courier New", Courier, monospace;
                color: #333;
                background-color: #fafafa;
            }
            .rating-good {
                background-color: #c8e6c9;
                color: #2e7d32;
                font-weight: bold;
            }
            .rating-neutral {
                background-color: #fff3e0;
                color: #ef6c00;
            }
            .rating-bad {
                background-color: #ffcdd2;
                color: #d32f2f;
                font-weight: bold;
            }
            tbody tr:nth-child(even) { background-color: #f5f5f5; }
            tbody tr:nth-child(odd) { background-color: #ffffff; }
            tbody tr:hover { background-color: #e8f4fd; }
            .summary {
                margin: 30px 0;
                padding: 20px;
                background: linear-gradient(135deg, #f5f5f5 0%, #e8e8e8 100%);
                border-radius: 8px;
                border: 1px solid #ddd;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            }
            .summary h3 {
                color: #2e7d32;
                font-size: 18px;
                margin: 0 0 15px 0;
            }
            .summary p, .summary ul {
                color: #424242;
                font-size: 13px;
                line-height: 1.6;
            }
            .footer {
                margin: 30px 0 20px 0;
                padding: 15px;
                background: linear-gradient(135deg, #f5f5f5 0%, #e8e8e8 100%);
                border-radius: 8px;
                text-align: center;
                font-size: 12px;
                color: #666;
                border: 1px solid #ddd;
            }
    </style>';
        $html .= '</head><body>';

        // Header section
        $html .= '<div class="header">';
        $html .= '<h1>💬 Message Log Report</h1>';
        $html .= '<div class="info">📅 Generated on: ' . Carbon::now()->format('F d, Y \a\t H:i:s') . '</div>';
        $html .= '<div class="info">📈 Total Records: ' . number_format(count($data)) . '</div>';
        if (!empty($filters['search'])) {
            $html .= '<div class="info">🔍 Search Filter: ' . htmlspecialchars($filters['search']) . '</div>';
        }
        if (!empty($filters['date_from'])) {
            $html .= '<div class="info">📅 Date From: ' . Carbon::parse($filters['date_from'])->format('F d, Y') . '</div>';
        }
        if (!empty($filters['date_to'])) {
            $html .= '<div class="info">📅 Date To: ' . Carbon::parse($filters['date_to'])->format('F d, Y') . '</div>';
        }
        $html .= '</div>';

        // Table
        $html .= '<table class="data-table">';
        $html .= '<thead><tr>';
        $html .= '<th>No</th><th>Date</th><th>Agent Name</th><th>Customer Messages</th><th>Customer Service Messages</th><th>CSAT Rating</th>';
        $html .= '</tr></thead><tbody>';

        // Definisi ratingCounts pakai lowercase key
        $ratingCounts = [
            'sangat puas' => 0,
            'puas' => 0,
            'datar' => 0,
            'sedih' => 0,
            'marah' => 0,
            'unknown' => 0
        ];

        foreach ($data as $item) {
            $csatRatingKey = strtolower($item['csat_rating'] ?? 'unknown');
            if (!isset($ratingCounts[$csatRatingKey])) {
                $csatRatingKey = 'unknown';
            }
            $ratingCounts[$csatRatingKey]++;

            $ratingClass = match ($csatRatingKey) {
                'sangat puas', 'puas' => 'rating-good',
                'datar' => 'rating-neutral',
                'sedih', 'marah' => 'rating-bad',
                default => ''
            };

            $html .= '<tr>';
            $html .= '<td class="number-cell">' . htmlspecialchars($item['id']) . '</td>';
            $html .= '<td class="date-cell">' . htmlspecialchars($item['date']) . '</td>';
            $html .= '<td class="agent-cell text-left">' . htmlspecialchars($item['agent_name']) . '</td>';
            $html .= '<td class="message-cell text-left">' . htmlspecialchars($item['customer']) . '</td>';
            $html .= '<td class="message-cell text-left">' . htmlspecialchars($item['customer_service']) . '</td>';
            $html .= '<td class="' . $ratingClass . '">' . htmlspecialchars(ucwords($csatRatingKey)) . '</td>';
            $html .= '</tr>';
        }

        if (count($data) === 0) {
            $html .= '<tr><td colspan="6" style="text-align:center; padding: 20px;">No message data available for the selected period</td></tr>';
        }

        $html .= '</tbody></table>';

        // Summary section
        if (count($data) > 0) {
            $html .= '<div class="summary">';
            $html .= '<h3>📊 Summary Statistics</h3>';
            $html .= '<p><strong>Total Conversations:</strong> ' . number_format(count($data)) . '</p>';
            $html .= '<p><strong>Rating Distribution:</strong></p><ul>';
            foreach ($ratingCounts as $key => $count) {
                if ($count > 0) {
                    $percentage = round(($count / count($data)) * 100, 1);
                    $html .= '<li>' . ucwords($key) . ': ' . $count . ' (' . $percentage . '%)</li>';
                }
            }
            $html .= '</ul></div>';
        }
        return response($html)
            ->header('Content-Type', 'application/vnd.ms-excel; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Cache-Control', 'max-age=0');
    }

    /**
     * Generate Conversation Analysis (Customer Review Log) Excel
     */
    public function generateConversationAnalysisReport(array $data, array $filters = [])
    {
        // Build filename based on sentiment filter
        $sent = strtolower($filters['ca_sentiment'] ?? 'all');
        $nameMap = [
            'all' => 'All Review Log',
            'positif' => 'Positif Review Log',
            'netral' => 'Netral Review Log',
            'negatif' => 'Negatif Review Log',
        ];
        $baseName = $nameMap[$sent] ?? 'Review Log';
        $df = trim((string)($filters['date_from'] ?? ''));
        $dt = trim((string)($filters['date_to'] ?? ''));
        $dateSuffix = '';
        if ($df !== '' && $dt !== '') {
            $dateSuffix = '_' . preg_replace('/[^0-9\-]/', '', $df) . '_to_' . preg_replace('/[^0-9\-]/', '', $dt);
        } elseif ($df !== '') {
            $dateSuffix = '_' . preg_replace('/[^0-9\-]/', '', $df);
        } elseif ($dt !== '') {
            $dateSuffix = '_' . preg_replace('/[^0-9\-]/', '', $dt);
        }
        $filename = $baseName . $dateSuffix . '_' . Carbon::now()->format('Y-m-d_H-i-s') . '.xls';

        $html = "\xEF\xBB\xBF"; // UTF-8 BOM
        $html .= '<!DOCTYPE html><html><head><meta charset="UTF-8">';
        $html .= '<style>
            body{font-family:Calibri, Arial, sans-serif;font-size:11pt;margin:20px}
            .header{background:#f1f8e9;border:1px solid #c5e1a5;padding:15px;border-radius:8px;margin-bottom:12px}
            table{border-collapse:collapse;width:100%;table-layout:fixed}
            thead th{background:#f2f2f2;color:#000;font-weight:700}
            th,td{border:1px solid #cfcfcf;padding:6px;text-align:left;vertical-align:middle}
            tbody tr:nth-child(even){background:#fbfbfb}
            .t-center{text-align:center}
            .sent-neg{color:#d32f2f;font-weight:bold}
            .sent-pos{color:#2e7d32;font-weight:bold}
            .sent-neu{color:#616161;font-weight:bold}
            .wrap-cell{white-space:normal;word-wrap:break-word;word-break:break-word}
            /* Optional column sizing for readability */
            .col-customer{width:16%}
            .col-agent{width:16%}
            .col-labels{width:20%}
            .col-room{width:9%}
            .col-msg{width:20%}
            .col-reason{width:19%}
        </style>';
        $html .= '</head><body>';
        $html .= '<div class="header">'
            . '<h2 style="margin:0">Customer Review Log</h2>'
            . '<div>Generated: ' . Carbon::now()->format('Y-m-d H:i:s') . '</div>'
            . (!empty($filters['ca_sentiment']) ? '<div>Filter: ' . htmlspecialchars($filters['ca_sentiment']) . '</div>' : '')
            . (!empty($filters['date_from']) ? '<div>Date From: ' . htmlspecialchars($filters['date_from']) . '</div>' : '')
            . (!empty($filters['date_to']) ? '<div>Date To: ' . htmlspecialchars($filters['date_to']) . '</div>' : '')
            . (!empty($filters['ca_label']) ? '<div>Labels: ' . htmlspecialchars($filters['ca_label']) . '</div>' : '')
            . '</div>';

        $html .= '<table><thead><tr>';
        $headers = [
            ['Customer', 'col-customer'],
            ['Customer Service', 'col-agent'],
            ['Labels', 'col-labels'],
            ['Room ID', 'col-room'],
            ['Pesan', 'col-msg'],
            ['Alasan', 'col-reason'],
        ];
        foreach ($headers as [$label, $cls]) {
            $html .= '<th class="' . $cls . '">' . $label . '</th>';
        }
        $html .= '</tr></thead><tbody>';

        foreach ($data as $row) {
            $labels = collect([$row['sentimen'], $row['kategori'], $row['product']])
                ->filter(fn($val) => !empty($val) && $val !== '-')
                ->implode(', ');
            $html .= '<tr>';
            $html .= '<td class="col-customer">' . htmlspecialchars($row['customer_name'] ?? '-') . '</td>';
            $html .= '<td class="col-agent">' . htmlspecialchars($row['agent_name'] ?? '-') . '</td>';
            $html .= '<td class="col-labels">' . htmlspecialchars($labels ?: '-') . '</td>';
            $html .= '<td class="col-room" style="mso-number-format:\'@\';">' . htmlspecialchars($row['room_id'] ?? '-') . '</td>';
            $html .= '<td class="wrap-cell col-msg">' . htmlspecialchars($row['pesan'] ?? '-') . '</td>';
            $html .= '<td class="wrap-cell col-reason">' . htmlspecialchars($row['alasan'] ?? '-') . '</td>';
            $html .= '</tr>';
        }
        if (count($data) === 0) {
            $html .= '<tr><td colspan="6" style="text-align:center">No data</td></tr>';
        }
        $html .= '</tbody></table>';
        $html .= '</body></html>';

        return response($html)
            ->header('Content-Type', 'application/vnd.ms-excel; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Cache-Control', 'max-age=0');
    }

    /**
     * Generate formatted template for import
     */
    public function generateTemplate()
    {
        // Use HTML format for template as well
        $content = $this->generateCSVTemplate();

        $filename = 'Agent_CSAT_Template_' . Carbon::now()->format('Y-m-d') . '.xls';

        return response($content)
            ->header('Content-Type', 'application/vnd.ms-excel')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Cache-Control', 'max-age=0');
    }

    private function generateExcelHTML($data, $filters)
    {
        $html = '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { background-color: #E3F2FD; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
                .header h1 { color: #1976D2; margin: 0; font-size: 24px; }
                .header .info { color: #424242; margin: 5px 0; font-size: 14px; }
                table { border-collapse: collapse; width: 100%; margin-top: 10px; }
                .table-header th {
                    background-color: #87CEEB !important;
                    color: #000080;
                    padding: 12px;
                    border: 1px solid #5DADE2;
                    text-align: left;
                    font-weight: bold;
                    font-size: 14px;
                }
                tbody tr:nth-child(even) { background-color: #F8F9FA; }
                tbody tr:nth-child(odd) { background-color: #FFFFFF; }
                td {
                    padding: 10px;
                    border: 1px solid #DEE2E6;
                    vertical-align: top;
                    font-size: 12px;
                }
            </style>
        </head>
        <body>';

        // Header section
        $html .= '<div class="header">';
        $html .= '<h1>📊 Agent CSAT Response Report</h1>';
        $html .= '<div class="info">📅 Generated on: ' . Carbon::now()->format('F d, Y \a\t H:i:s') . '</div>';
        $html .= '<div class="info">📈 Total Records: ' . number_format(count($data)) . '</div>';

        if (!empty($filters['search'])) {
            $html .= '<div class="info">🔍 Search Filter: ' . htmlspecialchars($filters['search']) . '</div>';
        }
        if (!empty($filters['date_from'])) {
            $html .= '<div class="info">📅 Date From: ' . Carbon::parse($filters['date_from'])->format('F d, Y') . '</div>';
        }
        if (!empty($filters['date_to'])) {
            $html .= '<div class="info">📅 Date To: ' . Carbon::parse($filters['date_to'])->format('F d, Y') . '</div>';
        }
        $html .= '</div>';

        // Table
        $html .= '<table>';
        $html .= '<thead>';
        $html .= '<tr class="table-header">';
        $html .= '<th style="width: 5%;">#</th>';
        $html .= '<th style="width: 30%;">👤 Customer Name</th>';
        $html .= '<th style="width: 25%;">🎧 Agent Name</th>';
        $html .= '<th style="width: 15%;">📅 Date</th>';
        $html .= '<th style="width: 15%;">⏱ First Response Time</th>';
        $html .= '<th style="width: 15%;">≈ Avg Response Time</th>';
        $html .= '<th style="width: 15%;">🏁 Resolved Time</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        $no = 1;
        foreach ($data as $item) {
            $html .= '<tr>';
            $html .= '<td style="text-align: center;">' . $no++ . '</td>';
            $html .= '<td>' . htmlspecialchars($item['customer_name']) . '</td>';
            $html .= '<td>' . htmlspecialchars($item['agent_name']) . '</td>';
            $html .= '<td>' . htmlspecialchars($item['date']) . '</td>';
            $html .= '<td>' . htmlspecialchars($item['first_response_time'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($item['average_response_time'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($item['resolved_time'] ?? '') . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</body></html>';

        return $html;
    }

    private function generateTemplateHTML()
    {
        $html = '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { background-color: #E3F2FD; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
                .header h1 { color: #1976D2; margin: 0; font-size: 24px; }
                .header .info { color: #424242; margin: 5px 0; font-size: 14px; }
                table { border-collapse: collapse; width: 100%; margin-top: 10px; }
                .table-header th {
                    background-color: #87CEEB !important;
                    color: #000080;
                    padding: 12px;
                    border: 1px solid #5DADE2;
                    text-align: left;
                    font-weight: bold;
                }
                .instructions {
                    margin-top: 20px;
                    background-color: #FFF3CD;
                    padding: 15px;
                    border-radius: 5px;
                    border-left: 4px solid #FFC107;
                }
            </style>
        </head>
        <body>';

        // Header
        $html .= '<div class="header">';
        $html .= '<h1>📝 Agent CSAT Data Import Template</h1>';
        $html .= '<div class="info">📅 Generated on: ' . Carbon::now()->format('F d, Y \a\t H:i:s') . '</div>';
        $html .= '<div class="info">📋 Use this template to import CSAT data</div>';
        $html .= '</div>';

        // Table with empty rows
        $html .= '<table>';
        $html .= '<thead>';
        $html .= '<tr class="table-header">';
        $html .= '<th>Customer Name</th>';
        $html .= '<th>Agent Name</th>';
        $html .= '<th>Date</th>';
        $html .= '<th>First Response Time</th>';
        $html .= '<th>Average Response Time</th>';
        $html .= '<th>Resolved Time</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        // Create 50 empty rows for user to fill
        for ($i = 1; $i <= 50; $i++) {
            $html .= '<tr>';
            $html .= '<td>&nbsp;</td>';
            $html .= '<td>&nbsp;</td>';
            $html .= '<td>&nbsp;</td>';
            $html .= '<td>&nbsp;</td>';
            $html .= '<td>&nbsp;</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';

        // Instructions
        $html .= '<div class="instructions">';
        $html .= '<h3>📋 Import Instructions</h3>';
        $html .= '<ul>';
        $html .= '<li>Fill the template with your CSAT data</li>';
        $html .= '<li>Date format: DD MMM YYYY (e.g., 01 Aug 2025)</li>';
        $html .= '<li>Time format: HH:MM:SS (e.g., 10:30:45)</li>';
        $html .= '<li>Save as Excel file (.xlsx)</li>';
        $html .= '<li>Upload using the Import function</li>';
        $html .= '</ul>';
        $html .= '</div>';

        $html .= '</body></html>';

        return $html;
    }

    /**
     * Generate styled CSV template for import
     */
    private function generateCSVTemplate()
    {
        $html = '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
            <style>
                body {
                    font-family: Arial, sans-serif;
                    margin: 20px;
                    background-color: #f8f9fa;
                }
                .template-header {
                    background: linear-gradient(135deg, #e8f5e8 0%, #c8e6c9 100%);
                    padding: 20px;
                    border-radius: 10px;
                    margin-bottom: 25px;
                    border: 2px solid #81c784;
                    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
                }
                .template-header h1 {
                    color: #2e7d32;
                    margin: 0 0 10px 0;
                    font-size: 28px;
                    font-weight: bold;
                    text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
                }
                .template-header .info {
                    color: #388e3c;
                    margin: 8px 0;
                    font-size: 14px;
                    font-weight: 500;
                }
                .data-table {
                    border-collapse: collapse;
                    width: 100%;
                    margin-top: 15px;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                    border-radius: 8px;
                    overflow: hidden;
                }
                .header-row th {
                    background: linear-gradient(135deg, #87ceeb 0%, #4fc3f7 100%);
                    color: #0d47a1;
                    padding: 16px 12px;
                    border: 2px solid #42a5f5;
                    text-align: center;
                    font-weight: bold;
                    font-size: 14px;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                    text-shadow: 1px 1px 2px rgba(255,255,255,0.3);
                }
                .data-row:nth-child(even) {
                    background-color: #f5f5f5;
                }
                .data-row:nth-child(odd) {
                    background-color: #ffffff;
                }
                .data-row td {
                    padding: 12px 10px;
                    border: 1px solid #e0e0e0;
                    vertical-align: middle;
                    font-size: 13px;
                }
                .sample-row {
                    background-color: #fff3e0 !important;
                    font-style: italic;
                }
                .empty-row {
                    background-color: #fafafa;
                }
                .instructions {
                    margin-top: 25px;
                    padding: 20px;
                    background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%);
                    border-left: 5px solid #ff9800;
                    border-radius: 8px;
                }
            </style>
        </head>
        <body>';

        // Header section
        $html .= '<div class="template-header">';
        $html .= '<h1>📝 Agent CSAT Response Template</h1>';
        $html .= '<div class="info">📅 Generated on: ' . Carbon::now()->format('F d, Y') . '</div>';
        $html .= '<div class="info">📋 Instructions: Fill this template with your data and import back to the system</div>';
        $html .= '<div class="info">⚠️ Please maintain the column order and format</div>';
        $html .= '</div>';

        // Table with headers
        $html .= '<table class="data-table">';
        $html .= '<thead>';
        $html .= '<tr class="header-row">';
        $html .= '<th style="width: 8%;">No</th>';
        $html .= '<th style="width: 30%;">👤 Customer Name</th>';
        $html .= '<th style="width: 25%;">🎧 Agent Name</th>';
        $html .= '<th style="width: 15%;">📅 Date</th>';
        $html .= '<th style="width: 11%;">⏰ Requested</th>';
        $html .= '<th style="width: 11%;">✅ Responded</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        // Sample data rows (3 examples)
        $sampleData = [
            ['1', 'Customer Example 1', 'Agent Example', '01 Aug 2025', '10:00:00', '10:05:00'],
            ['2', 'Customer Example 2', 'Agent Example', '01 Aug 2025', '10:15:00', '10:20:00'],
            ['3', 'Customer Example 3', 'Agent Example', '01 Aug 2025', '10:30:00', '10:35:00']
        ];

        foreach ($sampleData as $sample) {
            $html .= '<tr class="data-row sample-row">';
            foreach ($sample as $cell) {
                $html .= '<td>' . $cell . '</td>';
            }
            $html .= '</tr>';
        }

        // Empty rows for user input
        for ($i = 4; $i <= 50; $i++) {
            $html .= '<tr class="data-row empty-row">';
            $html .= '<td>' . $i . '</td>';
            $html .= '<td>&nbsp;</td>';
            $html .= '<td>&nbsp;</td>';
            $html .= '<td>&nbsp;</td>';
            $html .= '<td>&nbsp;</td>';
            $html .= '<td>&nbsp;</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';

        // Instructions
        $html .= '<div class="instructions">';
        $html .= '<h3>📋 Import Instructions</h3>';
        $html .= '<ul>';
        $html .= '<li><strong>Customer Name:</strong> Enter full customer name</li>';
        $html .= '<li><strong>Agent Name:</strong> Enter agent/CS staff name</li>';
        $html .= '<li><strong>Date format:</strong> DD MMM YYYY (e.g., 01 Aug 2025)</li>';
        $html .= '<li><strong>Time format:</strong> HH:MM:SS (e.g., 10:30:45)</li>';
        $html .= '<li><strong>Save as:</strong> Excel file (.xls or .xlsx)</li>';
        $html .= '<li><strong>Upload:</strong> Use the Import function in the dashboard</li>';
        $html .= '</ul>';
        $html .= '</div>';

        $html .= '</body></html>';

        return $html;
    }
}
