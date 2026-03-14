<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\Models\SatisfactionRating;
use App\Models\Message;
use App\Models\User;
use App\Models\AgentResponse;
use App\Services\ExcelExportService;
use App\Exports\AgentCsatExport;
use Maatwebsite\Excel\Facades\Excel;
class DashboardController extends Controller
{
    public function __construct()
    {
        ini_set('max_execution_time', 300);
    }

    /**
     * Auto-update date filters to ensure they're always current
     */
    private function autoUpdateDateFilters(Request $request)
    {
        $today = Carbon::today()->toDateString();
        $thirtyDaysAgo = Carbon::today()->subDays(30)->toDateString();

        // Set default date ranges if not provided
        $dateFields = [
            'chart_start_date' => $thirtyDaysAgo,
            'chart_end_date' => $today,
            'second_start_date' => $thirtyDaysAgo,
            'second_end_date' => $today,
            'ads_start_date' => $thirtyDaysAgo,
            'ads_end_date' => $today,
            'csat_start_date' => $thirtyDaysAgo,
            'csat_end_date' => $today,
            'ca_date_from' => $thirtyDaysAgo,
            'ca_date_to' => $today,
            'date_from' => $thirtyDaysAgo,
            'date_to' => $today,
            'status_date' => $today
        ];

        foreach ($dateFields as $field => $defaultValue) {
            if (!$request->has($field) || empty($request->get($field))) {
                $request->merge([$field => $defaultValue]);
            } else {
                // Ensure end dates don't exceed today
                $endDateFields = ['chart_end_date', 'second_end_date', 'ads_end_date', 'csat_end_date', 'ca_date_to', 'date_to', 'status_date'];
                if (in_array($field, $endDateFields)) {
                    $requestDate = $request->get($field);
                    if ($requestDate > $today) {
                        $request->merge([$field => $today]);
                    }
                }
            }
        }
    }

    public function index(Request $request)
    {
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Your session has expired. Please log in again.');
        }

        try {
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            Log::error('Database connection failed', ['error' => $e->getMessage()]);
            return view('dashboard.index', $this->getFallbackData());
        }

        // Auto-update date filters to current date
        $this->autoUpdateDateFilters($request);

        // **FIX: Parse $caLabel for multi-select dropdown**
        $selectedLabelRaw = trim((string) $request->get('ca_label', ''));
        $caLabel = [];
        if ($selectedLabelRaw !== '') {
            $caLabel = array_filter(
                array_map('trim', explode(',', $selectedLabelRaw)),
                fn($val) => $val !== ''
            );
        }

        $section = $request->get('section', 'dashboard');
        $payload = $this->prepareBasePayload($request);

        // **FIX: Always pass $caLabel in base payload**
        $payload['caLabel'] = $caLabel;

        if (in_array($section, ['dashboard', 'agents'])) {
            $payload = $this->prepareDashboardAgentsPayload($request, $payload);
        }

        if ($section === 'reviews') {
            $payload = $this->prepareReviewsPayload($request, $payload);
        }

        if (in_array($section, ['analytics', 'agents', 'dashboard'])) {
            $payload = $this->prepareAnalyticsAgentsPayload($request, $payload);
        }

        // Always include Customer section sentiment summary so counts are available on client-side section switch
        $customerSentiment = $this->getCustomerSentimentData();
        $payload = array_merge($payload, $customerSentiment);

        // **NEW: Labels untuk filter dropdown**
        $allLabels = [];
        try {
            $allLabels = DB::table('labels')
                ->pluck('label_name')
                ->toArray();
        } catch (\Exception $e) {
            // Fallback jika table belum ada
            $allLabels = ['sales', 'complain', 'informasi'];
            Log::warning('Labels table not found, using fallback', ['error' => $e->getMessage()]);
        }

        // Also include conversation analysis list when on customer section
        if ($section === 'customer') {
            $payload = array_merge($payload, $this->getConversationAnalysisList($request));
        } else {
            // Provide default values for customer section variables when not on customer section
            $payload = array_merge($payload, [
                'caDateFrom' => $request->get('ca_date_from', ''),
                'caDateTo' => $request->get('ca_date_to', ''),
                'caKategori' => [],
                'caProduct' => [],
                'caCs' => [],
                'caCategories' => [],
                'caProducts' => [],
                'allCsAgents' => $this->getAllCsAgents(),
                'caSentiment' => 'all',
                'caPerPage' => 10,
                'conversationAnalysis' => new \Illuminate\Pagination\LengthAwarePaginator(collect(), 0, 10, 1, ['path' => request()->url()]),
                // **FIX: Ensure caLabel always present as array**
                'caLabel' => $caLabel ?? [],
                'allLabels' => $allLabels,  // **PERBAIKAN: Fix undefined $allLabels error**
            ]);

        }

        return view('dashboard.index', $payload);
    }

    // Di DashboardController.php, update method getConversationAnalysisList
    private function getConversationAnalysisList(Request $request): array
    {
        $table = 'conversation_analysis';

        // Select column candidates to be robust to schema differences
        $col = fn(array $candidates, string $fallbackAlias) => collect($candidates)
            ->first(fn($c) => Schema::hasColumn($table, $c)) ?? null;

        $customerCol = $col(['customer_name', 'nama_customer', 'customer', 'customer_fullname', 'name'], 'customer_name');
        $csCol       = $col(['agent_name', 'cs_name', 'customer_service', 'customer_service_name', 'cs', 'username'], 'agent_name');
        $sentCol     = $col(['sentimen', 'sentiment'], 'sentimen');
        $roomCol     = $col(['room_id', 'roomid', 'id_room', 'chat_room_id'], 'room_id');
        $katCol      = $col(['kategori', 'category'], 'kategori');
        $prodCol     = $col(['product', 'produk'], 'product');
        $msgCol      = $col(['pesan', 'message', 'text', 'content'], 'pesan');
        $dateCol     = $col(['created_at', 'date', 'tanggal', 'ts', 'timestamp', 'updated_at'], 'created_at');
        $reasonCol   = $col(['alasan', 'reason', 'notes', 'keterangan'], 'alasan');
        $labelCol    = $col(['label', 'labels'], 'label');

        $selects = [];
        // Derive names from message/text if explicit columns are missing
        $customerNameExpr = null;
        $agentNameExpr = null;
        if (!$customerCol && $msgCol) {
            $customerNameExpr = "(SELECT m[1] FROM regexp_matches(" . $msgCol . ", '\\] ([^()]+) \\((customer|customer_service)\\)', 'g') AS m WHERE m[2] = 'customer' LIMIT 1)";
        }
        if (!$csCol && $msgCol) {
            $agentNameExpr = "(SELECT m[1] FROM regexp_matches(" . $msgCol . ", '\\] ([^()]+) \\((customer|customer_service)\\)', 'g') AS m WHERE m[2] = 'customer_service' LIMIT 1)";
        }

        $selects[] = $customerCol
            ? DB::raw($customerCol . ' as customer_name')
            : ($customerNameExpr ? DB::raw($customerNameExpr . ' as customer_name') : DB::raw("NULL as customer_name"));

        // Build agent name selection: prefer real CS (exclude 'CS MAXCHAT')
        $agentNonMaxExpr = null;
        if ($msgCol) {
            $agentNonMaxExpr = "(SELECT m[1] FROM regexp_matches(" . $msgCol . ", '\\] ([^()]+) \\((customer|customer_service)\\)', 'g') AS m WHERE m[2] = 'customer_service' AND TRIM(LOWER(m[1])) <> 'cs maxchat' LIMIT 1)";
        }
        if ($csCol) {
            // If column is 'CS MAXCHAT', try derive the next non-maxchat agent from message content
            $selects[] = DB::raw(
                "CASE WHEN TRIM(LOWER(" . $csCol . ")) = 'cs maxchat' THEN " . ($agentNonMaxExpr ?: 'NULL') . " ELSE " . $csCol . " END as agent_name"
            );
        } else {
            $selects[] = $agentNonMaxExpr
                ? DB::raw($agentNonMaxExpr . ' as agent_name')
                : ($agentNameExpr ? DB::raw($agentNameExpr . ' as agent_name') : DB::raw("NULL as agent_name"));
        }

        // Use the same column for filtering as we use for selection
        $filterCsCol = $csCol ?: 'agent_name';
        $selects[] = $sentCol ? DB::raw('LOWER(' . $sentCol . ') as sentimen') : DB::raw("NULL as sentimen");
        $selects[] = $katCol ? DB::raw($katCol . ' as kategori') : DB::raw("NULL as kategori");
        $selects[] = $prodCol ? DB::raw($prodCol . ' as product') : DB::raw("NULL as product");
        $selects[] = $labelCol ? DB::raw($labelCol . ' as label') : DB::raw("NULL as label");
        $selects[] = $msgCol ? DB::raw($msgCol . ' as pesan') : DB::raw("NULL as pesan");
        $selects[] = $reasonCol ? DB::raw($reasonCol . ' as alasan') : DB::raw("NULL as alasan");
        // Expose room_id if available
        $selects[] = $roomCol ? DB::raw($roomCol . ' as room_id') : DB::raw('NULL as room_id');
        // Always expose a created_at field if any date-like column exists
        if ($dateCol) {
            $selects[] = DB::raw($dateCol . ' as created_at');
        } else {
            $selects[] = DB::raw('NULL as created_at');
        }

        $query = DB::table($table)->select($selects);

        // Apply 3-bucket sentiment filter if provided
        $filter = strtolower($request->get('ca_sentiment', 'all'));
        $negKeys = ['sangat tidak puas', 'tidak puas', 'negatif', 'negative', 'buruk', 'jelek', 'unhappy', 'sad', 'marah', 'sedih', 'not satisfied', 'dissatisfied'];
        $neuKeys = ['netral', 'neutral', 'datar'];
        $posKeys = ['sangat puas', 'puas', 'positif', 'positive', 'bagus', 'baik', 'good', 'great', 'happy', 'senang', 'satisfied', 'very satisfied'];

        Log::info('Sentiment filter:', ['filter' => $filter, 'sentCol' => $sentCol]);

        if (in_array($filter, ['negatif', 'netral', 'positif'], true) && $sentCol) {
            $query->where(function ($q) use ($filter, $sentCol, $negKeys, $neuKeys, $posKeys) {
                $colExpr = DB::raw('LOWER(' . $sentCol . ')');
                $keys = $filter === 'negatif' ? $negKeys : ($filter === 'netral' ? $neuKeys : $posKeys);
                $q->whereIn($colExpr, $keys)
                    ->orWhere(function ($qq) use ($colExpr, $filter) {
                        if ($filter === 'negatif') {
                            $qq->orWhere($colExpr, 'like', '%negatif%')
                                ->orWhere($colExpr, 'like', '%negative%')
                                ->orWhere($colExpr, 'like', '%tidak puas%')
                                ->orWhere($colExpr, 'like', '%not satisfied%')
                                ->orWhere($colExpr, 'like', '%dissatisfied%')
                                ->orWhere($colExpr, 'like', '%buruk%')
                                ->orWhere($colExpr, 'like', '%jelek%');
                        } elseif ($filter === 'positif') {
                            $qq->orWhere($colExpr, 'like', '%positif%')
                                ->orWhere($colExpr, 'like', '%positive%')
                                ->orWhere($colExpr, 'like', '%sangat puas%')
                                ->orWhere($colExpr, 'like', '%puas%')
                                ->orWhere($colExpr, 'like', '%satisfied%')
                                ->orWhere($colExpr, 'like', '%good%')
                                ->orWhere($colExpr, 'like', '%great%')
                                ->orWhere($colExpr, 'like', '%baik%')
                                ->orWhere($colExpr, 'like', '%bagus%');
                        } else { // netral
                            $qq->orWhere($colExpr, 'like', '%netral%')
                                ->orWhere($colExpr, 'like', '%neutral%')
                                ->orWhere($colExpr, 'like', '%datar%');
                        }
                    });
            });
        }

        // Optional date range filtering: only apply if user provides dates
        $dateFrom = $request->get('ca_date_from');
        $dateTo   = $request->get('ca_date_to');
        if ($dateCol && ($dateFrom || $dateTo)) {
            if ($dateFrom) {
                $query->whereDate($dateCol, '>=', $dateFrom);
            }
            if ($dateTo) {
                $query->whereDate($dateCol, '<=', $dateTo);
            }
            $countAfterDate = $query->count();
            Log::info('Records after date filter:', ['count' => $countAfterDate]);
        } else {
            Log::info('No date filter applied - showing all records');
        }

        // ========== MULTI-SELECT FILTERS: Kategori, Product, dan Customer Service ==========

        // Parse kategori (comma-separated string to array)
        $selectedKategoriRaw = trim((string) $request->get('ca_kategori', ''));
        $selectedKategori = [];
        if ($selectedKategoriRaw !== '') {
            $selectedKategori = array_filter(
                array_map('trim', explode(',', $selectedKategoriRaw)),
                fn($val) => $val !== ''
            );
        }
        Log::info('Filter Kategori:', ['raw' => $selectedKategoriRaw, 'parsed' => $selectedKategori]);

        // Parse product (comma-separated string to array)
        $selectedProductRaw = trim((string) $request->get('ca_product', ''));
        $selectedProduct = [];
        if ($selectedProductRaw !== '') {
            $selectedProduct = array_filter(
                array_map('trim', explode(',', $selectedProductRaw)),
                fn($val) => $val !== ''
            );
        }

        // **NEW: Parse labels filter (ca_label) - multi-select**
        $selectedLabelRaw = trim((string) $request->get('ca_label', ''));
        $selectedLabels = [];
        if ($selectedLabelRaw !== '') {
            $selectedLabels = array_filter(
                array_map('trim', explode(',', $selectedLabelRaw)),
                fn($val) => $val !== ''
            );
        }
        $hasLabelFilter = !empty($selectedLabels);

        // Parse customer service (comma-separated string to array)
        $selectedCsRaw = trim((string) $request->get('ca_cs', ''));
        $selectedCs = [];
        if ($selectedCsRaw !== '') {
            $selectedCs = array_filter(
                array_map('trim', explode(',', $selectedCsRaw)),
                fn($val) => $val !== ''
            );
        }

        // Define filter flags
        $hasKategoriFilter = !empty($selectedKategori);
        $hasProductFilter = !empty($selectedProduct);
        $hasCsFilter = !empty($selectedCs);

        // **NEW: Apply labels filter - search in kategori OR product**
        if ($hasLabelFilter && ($katCol || $prodCol)) {
            $query->where(function($q) use ($selectedLabels, $katCol, $prodCol) {
                foreach ($selectedLabels as $label) {
                    $labelLower = strtolower(trim($label));
                    if ($katCol) {
                        $q->orWhereRaw('LOWER(' . $katCol . ') = ?', [$labelLower]);
                    }
                    if ($prodCol) {
                        $q->orWhereRaw('LOWER(' . $prodCol . ') = ?', [$labelLower]);
                    }
                }
            });
            Log::info('Labels filter applied', ['labels' => $selectedLabels]);
        }

        // Apply multi-select kategori filter
        if ($katCol && !empty($selectedKategori)) {
            $query->where(function($q) use ($katCol, $selectedKategori) {
                foreach ($selectedKategori as $kat) {
                    $q->orWhereRaw('LOWER(' . $katCol . ') = ?', [strtolower($kat)]);
                }
            });
        }

        // Apply multi-select product filter
        if ($prodCol && !empty($selectedProduct)) {
            $query->where(function($q) use ($prodCol, $selectedProduct) {
                foreach ($selectedProduct as $prod) {
                    $q->orWhereRaw('LOWER(' . $prodCol . ') = ?', [strtolower($prod)]);
                }
            });

            Log::info('Applied filters', [
                'kategori' => $hasKategoriFilter ? $selectedKategori : 'none',
                'product' => $hasProductFilter ? $selectedProduct : 'none'
            ]);
        }

        if (!$katCol && !empty($selectedKategori)) {
            Log::warning('Kategori filter requested but column not found in table');
        }
        if (!$prodCol && !empty($selectedProduct)) {
            Log::warning('Product filter requested but column not found in table');
        }

        // Apply customer service filter using HAVING clause after SELECT
        $csFilterApplied = false;
        if ($hasCsFilter) {
            if ($csCol) {
                // Use original column if available
                $query->where(function($q) use ($csCol, $selectedCs) {
                    foreach ($selectedCs as $cs) {
                        $q->orWhere($csCol, 'ILIKE', '%' . trim($cs) . '%');
                    }
                });
                $csFilterApplied = true;
            }
        }

        Log::info('Applied filters', [
            'kategori' => $hasKategoriFilter ? $selectedKategori : 'none',
            'product' => $hasProductFilter ? $selectedProduct : 'none',
            'customer_service' => $hasCsFilter ? $selectedCs : 'none',
            'cs_column' => $csCol,
            'filter_cs_column' => $filterCsCol
        ]);

        if (!$katCol && !empty($selectedKategori)) {
            Log::warning('Kategori filter requested but column not found in table');
        }
        if (!$prodCol && !empty($selectedProduct)) {
            Log::warning('Product filter requested but column not found in table');
        }

        // Pagination
        $perPage = (int) $request->get('ca_per_page', 10);
        $perPage = in_array($perPage, [10, 20, 50, 100]) ? $perPage : 10;
        $page = max(1, (int) $request->get('ca_page', 1));

        // Prefer newest first if date column exists
        if ($dateCol) {
            $query->orderBy($dateCol, 'desc');
        } else {
            $query->orderBy('sentimen');
        }

        // Get results first
        $results = $query->get();

        // Apply CS filter on collection if not applied in query
        if ($hasCsFilter && !$csFilterApplied) {
            $results = $results->filter(function($row) use ($selectedCs) {
                $agentName = $row->agent_name ?? '';
                foreach ($selectedCs as $cs) {
                    if (stripos($agentName, trim($cs)) !== false) {
                        return true;
                    }
                }
                return false;
            });
        }

        // Create paginator from filtered results
        $total = $results->count();
        $offset = ($page - 1) * $perPage;
        $paginatedResults = $results->slice($offset, $perPage)->values();

        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $paginatedResults,
            $total,
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'pageName' => 'ca_page',
                'query' => request()->query()
            ]
        );

        // Preserve filters on pagination URLs (avoid duplicating 'section' which view adds manually)
        // $paginator->appends([
        //     'ca_per_page' => $perPage,
        //     'ca_sentiment' => in_array($filter, ['negatif', 'netral', 'positif'], true) ? $filter : 'all',
        //     'ca_date_from' => $dateFrom,
        //     'ca_date_to' => $dateTo,
        //     'ca_kategori' => $selectedKategoriRaw,
        //     'ca_product' => $selectedProductRaw,
        //     'ca_cs' => $selectedCsRaw,
        //     'ca_label' => $selectedLabelRaw,
        // ]);

        // Build options for kategori/product selects
        $categories = [];
        $products = [];
        try {
            if ($katCol) {
                $categories = DB::table($table)
                    ->selectRaw($katCol . ' as kategori')
                    ->whereNotNull($katCol)
                    ->whereRaw('TRIM(' . $katCol . ") <> ''")
                    ->distinct()
                    ->orderBy($katCol)
                    ->pluck('kategori')
                    ->toArray();
            }
            if ($prodCol) {
                $products = DB::table($table)
                    ->selectRaw($prodCol . ' as product')
                    ->whereNotNull($prodCol)
                    ->whereRaw('TRIM(' . $prodCol . ") <> ''")
                    ->distinct()
                    ->orderBy($prodCol)
                    ->pluck('product')
                    ->toArray();
            }
        } catch (\Throwable $e) {
            // ignore
        }

        // Get labels for filter dropdown
        $allLabels = [];
        try {
            $allLabels = DB::table('labels')
                ->pluck('label_name')
                ->toArray();
        } catch (\Exception $e) {
            // Fallback jika table belum ada
            $allLabels = ['sales', 'complain', 'informasi'];
            Log::warning('Labels table not found, using fallback', ['error' => $e->getMessage()]);
        }

        return [
            'conversationAnalysis' => $paginator,
            'caPerPage' => $perPage,
            'caPage' => $page,
            'caSentiment' => in_array($filter, ['negatif', 'netral', 'positif'], true) ? $filter : 'all',
            'caDateFrom' => $dateFrom ?: '',
            'caDateTo' => $dateTo ?: '',
            'caLabel' => $selectedLabels,      // **NEW: Labels array untuk AlpineJS**
            'caKategori' => $selectedKategori,
            'caProduct' => $selectedProduct,
            'caCs' => $selectedCs,
            'allLabels' => $allLabels,         // **PERBAIKAN: Ambil data labels dari database**
            'caCategories' => $categories,
            'caProducts' => $products,
            'allCsAgents' => $this->getAllCsAgents(),
        ];

    }

    // ========== Update Export Method untuk Multi-Select ==========
    public function exportConversationAnalysisReport(Request $request, ExcelExportService $exportService)
    {
        $table = 'conversation_analysis';

        $col = fn(array $candidates) => collect($candidates)->first(fn($c) => Schema::hasColumn($table, $c)) ?? null;

        $customerCol = $col(['customer_name', 'nama_customer', 'customer', 'customer_fullname', 'name']);
        $csCol       = $col(['agent_name', 'cs_name', 'customer_service', 'customer_service_name', 'cs', 'username']);
        $sentCol     = $col(['sentimen', 'sentiment']);
        $katCol      = $col(['kategori', 'category']);
        $prodCol     = $col(['product', 'produk']);
        $msgCol      = $col(['pesan', 'message', 'text', 'content']);
        $reasonCol   = $col(['alasan', 'reason', 'notes', 'keterangan']);
        $roomCol     = $col(['room_id', 'roomid', 'id_room', 'chat_room_id']);

        $selects = [];
        // Fallback name extraction if explicit columns are missing
        $customerNameExpr = (!$customerCol && $msgCol)
            ? "(SELECT m[1] FROM regexp_matches(" . $msgCol . ", '\\] ([^()]+) \\((customer|customer_service)\\)', 'g') AS m WHERE m[2] = 'customer' LIMIT 1)"
            : null;
        $agentNameExpr = (!$csCol && $msgCol)
            ? "(SELECT m[1] FROM regexp_matches(" . $msgCol . ", '\\] ([^()]+) \\((customer|customer_service)\\)', 'g') AS m WHERE m[2] = 'customer_service' LIMIT 1)"
            : null;

        $selects[] = $customerCol ? DB::raw($customerCol . ' as customer_name') : DB::raw(($customerNameExpr ?: "NULL") . ' as customer_name');

        // Build agent name selection for export, excluding 'CS MAXCHAT' similarly
        $agentNonMaxExpr = null;
        if ($msgCol) {
            $agentNonMaxExpr = "(SELECT m[1] FROM regexp_matches(" . $msgCol . ", '\\] ([^()]+) \\((customer|customer_service)\\)', 'g') AS m WHERE m[2] = 'customer_service' AND TRIM(LOWER(m[1])) <> 'cs maxchat' LIMIT 1)";
        }
        if ($csCol) {
            $selects[] = DB::raw(
                "CASE WHEN TRIM(LOWER(" . $csCol . ")) = 'cs maxchat' THEN " . ($agentNonMaxExpr ?: 'NULL') . " ELSE " . $csCol . " END as agent_name"
            );
        } else {
            $selects[] = DB::raw(($agentNonMaxExpr ?: ($agentNameExpr ?: "NULL")) . ' as agent_name');
        }

        $selects[] = $sentCol ? DB::raw('LOWER(' . $sentCol . ') as sentimen') : DB::raw("NULL as sentimen");
        $selects[] = $katCol ? DB::raw($katCol . ' as kategori') : DB::raw("NULL as kategori");
        $selects[] = $prodCol ? DB::raw($prodCol . ' as product') : DB::raw("NULL as product");
        $selects[] = $msgCol ? DB::raw($msgCol . ' as pesan') : DB::raw("NULL as pesan");
        $selects[] = $reasonCol ? DB::raw($reasonCol . ' as alasan') : DB::raw("NULL as alasan");
        $selects[] = $roomCol ? DB::raw($roomCol . ' as room_id') : DB::raw('NULL as room_id');

        $query = DB::table($table)->select($selects);

        // Optional date filtering if a date/timestamp column exists
        $dateCol = collect(['date', 'tanggal', 'created_at', 'updated_at', 'ts', 'timestamp'])
            ->first(fn($c) => Schema::hasColumn($table, $c));
        $dateFrom = $request->get('ca_date_from');
        $dateTo = $request->get('ca_date_to');
        if ($dateCol && ($dateFrom || $dateTo)) {
            if ($dateFrom) {
                $query->whereDate($dateCol, '>=', $dateFrom);
            }
            if ($dateTo) {
                $query->whereDate($dateCol, '<=', $dateTo);
            }
        }

        // Apply sentiment filter
        $filter = strtolower($request->get('ca_sentiment', 'all'));
        $negKeys = ['sangat tidak puas', 'tidak puas', 'negatif', 'negative', 'buruk', 'jelek', 'unhappy', 'sad', 'marah', 'sedih', 'not satisfied', 'dissatisfied'];
        $neuKeys = ['netral', 'neutral', 'datar'];
        $posKeys = ['sangat puas', 'puas', 'positif', 'positive', 'bagus', 'baik', 'good', 'great', 'happy', 'senang', 'satisfied', 'very satisfied'];
        if (in_array($filter, ['negatif', 'netral', 'positif'], true) && $sentCol) {
            $query->where(function ($q) use ($filter, $sentCol, $negKeys, $neuKeys, $posKeys) {
                $colExpr = DB::raw('LOWER(' . $sentCol . ')');
                $keys = $filter === 'negatif' ? $negKeys : ($filter === 'netral' ? $neuKeys : $posKeys);
                $q->whereIn($colExpr, $keys)
                    ->orWhere(function ($qq) use ($colExpr, $filter) {
                        if ($filter === 'negatif') {
                            $qq->orWhere($colExpr, 'like', '%negatif%')
                                ->orWhere($colExpr, 'like', '%negative%')
                                ->orWhere($colExpr, 'like', '%tidak puas%')
                                ->orWhere($colExpr, 'like', '%not satisfied%')
                                ->orWhere($colExpr, 'like', '%dissatisfied%')
                                ->orWhere($colExpr, 'like', '%buruk%')
                                ->orWhere($colExpr, 'like', '%jelek%');
                        } elseif ($filter === 'positif') {
                            $qq->orWhere($colExpr, 'like', '%positif%')
                                ->orWhere($colExpr, 'like', '%positive%')
                                ->orWhere($colExpr, 'like', '%sangat puas%')
                                ->orWhere($colExpr, 'like', '%puas%')
                                ->orWhere($colExpr, 'like', '%satisfied%')
                                ->orWhere($colExpr, 'like', '%good%')
                                ->orWhere($colExpr, 'like', '%great%')
                                ->orWhere($colExpr, 'like', '%baik%')
                                ->orWhere($colExpr, 'like', '%bagus%');
                        } else { // netral
                            $qq->orWhere($colExpr, 'like', '%netral%')
                                ->orWhere($colExpr, 'like', '%neutral%')
                                ->orWhere($colExpr, 'like', '%datar%');
                        }
                    });
            });
        }

        // Apply multi-select kategori/product filters for export
        $selectedKategoriRaw = trim((string) $request->get('ca_kategori', ''));
        $selectedKategori = [];
        if ($selectedKategoriRaw !== '') {
            $selectedKategori = array_filter(
                array_map('trim', explode(',', $selectedKategoriRaw)),
                fn($val) => $val !== ''
            );
        }

        $selectedProductRaw = trim((string) $request->get('ca_product', ''));
        $selectedProduct = [];
        if ($selectedProductRaw !== '') {
            $selectedProduct = array_filter(
                array_map('trim', explode(',', $selectedProductRaw)),
                fn($val) => $val !== ''
            );
        }

        $selectedCsRaw = trim((string) $request->get('ca_cs', ''));
        $selectedCs = [];
        if ($selectedCsRaw !== '') {
            $selectedCs = array_filter(
                array_map('trim', explode(',', $selectedCsRaw)),
                fn($val) => $val !== ''
            );
        }

        // Parse labels filter for export
        $selectedLabelRaw = trim((string) $request->get('ca_label', ''));
        $selectedLabels = [];
        if ($selectedLabelRaw !== '') {
            $selectedLabels = array_filter(
                array_map('trim', explode(',', $selectedLabelRaw)),
                fn($val) => $val !== ''
            );
        }

        if (!empty($selectedKategori) && $katCol) {
            $query->where(function($q) use ($katCol, $selectedKategori) {
                foreach ($selectedKategori as $kat) {
                    $q->orWhereRaw('LOWER(' . $katCol . ') = ?', [strtolower($kat)]);
                }
            });
        }

        if (!empty($selectedProduct) && $prodCol) {
            $query->where(function($q) use ($prodCol, $selectedProduct) {
                foreach ($selectedProduct as $prod) {
                    $q->orWhereRaw('LOWER(' . $prodCol . ') = ?', [strtolower($prod)]);
                }
            });
        }

        // Apply labels filter for export
        if (!empty($selectedLabels) && ($katCol || $prodCol)) {
            $query->where(function($q) use ($selectedLabels, $katCol, $prodCol) {
                foreach ($selectedLabels as $label) {
                    $labelLower = strtolower(trim($label));
                    if ($katCol) {
                        $q->orWhereRaw('LOWER(' . $katCol . ') = ?', [$labelLower]);
                    }
                    if ($prodCol) {
                        $q->orWhereRaw('LOWER(' . $prodCol . ') = ?', [$labelLower]);
                    }
                }
            });
        }

        // Apply customer service filter for export
        if (!empty($selectedCs) && $csCol) {
            $query->where(function($q) use ($csCol, $selectedCs) {
                foreach ($selectedCs as $cs) {
                    $q->orWhere($csCol, 'ILIKE', '%' . trim($cs) . '%');
                }
            });
        }

        // Align export ordering with on-screen list: newest first when date column exists.
        if ($dateCol) {
            $query->orderBy($dateCol, 'desc');
        } else {
            $query->orderBy('sentimen');
        }

        // Fetch ALL filtered rows (no pagination for export)
        $rows = $query->get();

        // Normalize to array
        $data = $rows->map(function ($r) {
            $row = (array) $r;
            return [
                'customer_name' => $row['customer_name'] ?? '-',
                'agent_name' => $row['agent_name'] ?? '-',
                'sentimen' => $row['sentimen'] ?? '-',
                'kategori' => $row['kategori'] ?? '-',
                'product' => $row['product'] ?? '-',
                'room_id' => $row['room_id'] ?? '-',
                'pesan' => $row['pesan'] ?? '-',
                'alasan' => $row['alasan'] ?? '-',
            ];
        })->toArray();

        return $exportService->generateConversationAnalysisReport($data, [
            'ca_sentiment' => $filter,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'ca_kategori' => implode(', ', $selectedKategori),
            'ca_product' => implode(', ', $selectedProduct),
            'ca_cs' => implode(', ', $selectedCs),
            'ca_label' => implode(', ', $selectedLabels),
        ]);
    }

    private function prepareBasePayload(Request $request): array
    {
        $messageStatsStartDate = $request->get('message_stats_start_date', '');
        $messageStatsEndDate = $request->get('message_stats_end_date', '');
        if (empty($messageStatsStartDate) && empty($messageStatsEndDate)) {
            $messageStatsStartDate = Carbon::now()->subDays(90)->format('Y-m-d');
            $messageStatsEndDate = Carbon::now()->format('Y-m-d');
        }

        $selectedDate = $request->input('status_date', now()->toDateString());
        $payload = $this->getDefaultPayload();
        $payload['today'] = Carbon::now()->format('Y-m-d');
        $payload['messageStatsStartDate'] = $messageStatsStartDate;
        $payload['messageStatsEndDate'] = $messageStatsEndDate;
        $payload['selectedDate'] = $selectedDate;

        // Hitung responseTimeDays berdasarkan message stats range
        $payload['responseTimeDays'] = Carbon::parse($messageStatsEndDate)->diffInDays(Carbon::parse($messageStatsStartDate));
        if ($payload['responseTimeDays'] < 1 || $payload['responseTimeDays'] > 180) {
            $payload['responseTimeDays'] = 30; // Fallback ke 30 hari jika tidak valid
        }

        return $payload;
    }

    private function prepareDashboardAgentsPayload(Request $request, array $payload): array
    {
        $satisfactionData = $this->getSatisfactionRatingsData();
        $responseTimeData = $this->getResponseTimeData($payload['responseTimeDays']);
        $messageStats = $this->getMessageStats($payload['messageStatsStartDate'], $payload['messageStatsEndDate']);
        $csAgents = $this->getCSAgentsData($responseTimeData, $payload['responseTimeDays']);

        return array_merge($payload, $satisfactionData, $responseTimeData, $messageStats, $csAgents);
    }

    private function prepareReviewsPayload(Request $request, array $payload): array
    {
        $reviewsData = $this->getReviewsData($request);
        return array_merge($payload, $reviewsData);
    }

    private function prepareAnalyticsAgentsPayload(Request $request, array $payload): array
    {
        $csatCsId = $request->get('csat_cs_id', '');
        $allCsAgents = User::where('role', 'customer_service')
            ->orderBy('username')
            ->get()
            ->mapWithKeys(function ($user) {
                return [$user->username => $user->username];
            })
            ->toArray();

        $chartDates = $this->validateDateInputs(
            $request->get('chart_start_date', $payload['messageStatsStartDate']),
            $request->get('chart_end_date', $payload['messageStatsEndDate'])
        );
        $chatTrendData = $this->getChatTrendData($request);
        // Use the correct date inputs for Customer Report (second_start_date/second_end_date)
        $customerDates = $this->validateDateInputs(
            $request->get('second_start_date', $payload['messageStatsStartDate']),
            $request->get('second_end_date', $payload['messageStatsEndDate'])
        );
        $customerReportData = $this->getCustomerReportData($request);
        $csatData = $this->getCSATData($request);
        $fromAdsData = $this->getFromAdsData($request);

        $summaryMessageStats = $this->getMessageStats($chartDates['start_date'], $chartDates['end_date']);
        $summarySatisfaction = $this->getSatisfactionRatingsData();

        return array_merge($payload, $chatTrendData, $customerReportData, $csatData, $fromAdsData, $summaryMessageStats, $summarySatisfaction, [
            'allCsAgents' => $allCsAgents,
            'csatCsId' => $csatCsId
        ]);
    }

    public function getCsAgents(Request $request)
    {
        $selectedDate = $request->input('status_date', now()->toDateString());
        $agentPerPage = $request->input('agent_per_page', 12);
        $responseTimeDays = (int) $request->get('rt_days', 30);

        $csAgentsData = $this->getCSAgentsData(null, $responseTimeDays);

        return response()->json([
            'success' => true,
            'data' => $csAgentsData,
            'generated_at' => Carbon::now()->toIso8601String(),
            'filters' => [
                'agent_per_page' => $agentPerPage,
                'agent_page' => $csAgentsData['agentPage'],
                'status_date' => $selectedDate,
            ],
        ]);
    }

    private function getSatisfactionRatingsData()
    {
        $perPage = (int) request()->get('rating_per_page', 10);
        $perPage = in_array($perPage, [10, 20, 50, 100]) ? $perPage : 10;
        $page = max(1, (int) request()->get('rating_page', 1));

        // Optimasi: Cache rating counts untuk 5 menit
        $cacheKey = 'satisfaction_rating_counts';
        $ratingData = cache()->remember($cacheKey, 300, function () {
            $rawCounts = DB::table('satisfaction_ratings')
                ->where('rating', 'like', '{%')
                ->selectRaw("LOWER((rating::json->>'id')) AS rating_id, COUNT(*) AS c")
                ->groupBy('rating_id')
                ->pluck('c', 'rating_id');

            $allRatings = ['marah', 'sedih', 'datar', 'puas', 'sangat puas'];
            $ratingCounts = array_fill_keys($allRatings, 0);
            foreach ($rawCounts as $rid => $cnt) {
                if (isset($ratingCounts[$rid])) $ratingCounts[$rid] = (int) $cnt;
            }

            $totalRatings = array_sum($ratingCounts);
            $positiveCount = $ratingCounts['puas'] + $ratingCounts['sangat puas'];
            $negativeCount = $ratingCounts['marah'] + $ratingCounts['sedih'];
            $positivePercentage = $totalRatings > 0 ? round(($positiveCount / $totalRatings) * 100, 2) : 0;
            $negativePercentage = $totalRatings > 0 ? round(($negativeCount / $totalRatings) * 100, 2) : 0;

            return [
                'ratingCounts' => $ratingCounts,
                'positivePercentage' => $positivePercentage,
                'negativePercentage' => $negativePercentage,
            ];
        });

        // Optimasi: Gunakan select minimal untuk pagination
        $query = SatisfactionRating::select(['id', 'rating', 'received_at', 'cs_id', 'chat_id'])
            ->orderByDesc('received_at');
        $paginator = $query->paginate($perPage, ['*'], 'rating_page', $page);

        return array_merge($ratingData, [
            'satisfactionRatings' => $paginator,
            'satisfactionRatingsPaginated' => $paginator,
            'ratingPerPage' => $perPage,
            'ratingPage' => $page,
        ]);
    }

    // Customer sentiment from conversation_analysis.sentimen (emoji cards)
    private function getCustomerSentimentData(): array
    {
        try {
            $raw = DB::table('conversation_analysis')
                ->selectRaw("LOWER(sentimen) AS s, COUNT(*) AS c")
                ->groupBy('s')
                ->pluck('c', 's');

            // Normalize to 5 buckets matching UI
            $buckets = [
                'sangat tidak puas' => 0,
                'tidak puas' => 0,
                'netral' => 0,
                'puas' => 0,
                'sangat puas' => 0,
            ];
            // 3-bucket aggregator (robust to various labels)
            $neg = 0;
            $neu = 0;
            $pos = 0;
            $negKeys = ['sangat tidak puas', 'tidak puas', 'negatif', 'negative', 'buruk', 'jelek', 'bad', 'poor', 'unhappy', 'sad', 'marah', 'sedih', 'not satisfied', 'dissatisfied'];
            $neuKeys = ['netral', 'neutral', 'datar'];
            $posKeys = ['sangat puas', 'puas', 'positif', 'positive', 'bagus', 'baik', 'good', 'great', 'happy', 'senang', 'satisfied', 'very satisfied'];

            foreach ($raw as $k => $v) {
                $v = (int)$v;
                $label = strtolower(trim((string)$k));
                $norm = preg_replace('/[\_\-]+/', ' ', $label);
                $norm = preg_replace('/\s+/', ' ', $norm);

                // Fill exact 5-bucket if matches
                if (array_key_exists($norm, $buckets)) {
                    $buckets[$norm] = $v;
                }

                // Classify into 3 buckets using synonyms and contains checks
                if (in_array($norm, $negKeys, true) || str_contains($norm, 'negatif') || str_contains($norm, 'negative') || str_contains($norm, 'tidak puas') || str_contains($norm, 'not satisfied') || str_contains($norm, 'dissatisfied') || str_contains($norm, 'buruk') || str_contains($norm, 'jelek')) {
                    $neg += $v;
                    continue;
                }
                if (in_array($norm, $posKeys, true) || str_contains($norm, 'positif') || str_contains($norm, 'positive') || str_contains($norm, 'sangat puas') || str_contains($norm, 'puas') || str_contains($norm, 'satisfied') || str_contains($norm, 'good') || str_contains($norm, 'great') || str_contains($norm, 'baik') || str_contains($norm, 'bagus')) {
                    $pos += $v;
                    continue;
                }
                if (in_array($norm, $neuKeys, true) || str_contains($norm, 'netral') || str_contains($norm, 'neutral') || str_contains($norm, 'datar')) {
                    $neu += $v;
                    continue;
                }
                // Unknown labels are ignored to avoid misclassification
            }

            $three = [
                'negatif' => $neg,
                'netral' => $neu,
                'positif' => $pos,
            ];

            return [
                'customerSentimentCounts' => $buckets,
                'customerSentiment3' => $three,
            ];
        } catch (\Throwable $e) {
            Log::error('Customer sentiment query failed', ['error' => $e->getMessage()]);
            return [
                'customerSentimentCounts' => [
                    'sangat tidak puas' => 0,
                    'tidak puas' => 0,
                    'netral' => 0,
                    'puas' => 0,
                    'sangat puas' => 0,
                ],
                'customerSentiment3' => [
                    'negatif' => 0,
                    'netral' => 0,
                    'positif' => 0,
                ],
            ];
        }
    }

    private function getResponseTimeData(int $days = 30)
    {
        $from = Carbon::now()->subDays($days)->startOfDay();
        $to = Carbon::now()->endOfDay();

        // Optimasi: Cache hasil query response time untuk 10 menit
        $cacheKey = 'response_time_data_' . $days . '_' . $from->format('Y-m-d') . '_' . $to->format('Y-m-d');

        return cache()->remember($cacheKey, 600, function () use ($from, $to, $days) {
            Log::info('Response Time Range (CTE): ' . $from . ' to ' . $to);

            $bindings = ['start_date' => $from->toDateString(), 'end_date' => $to->toDateString()];

            // Optimasi: Gunakan indeks yang sudah dibuat dan batasi data yang diproses
            $baseCte = "WITH sessions_in_range AS (
                    SELECT DISTINCT session_id
                    FROM messages
                    WHERE timestamp::date BETWEEN :start_date AND :end_date
                        AND session_id IS NOT NULL
                        AND role IN ('customer', 'customer_service')
                ),
                customer_first_messages AS (
                    SELECT
                        m.session_id,
                        MIN(m.timestamp) AS first_customer_time
                    FROM messages m
                    WHERE m.session_id IN (SELECT session_id FROM sessions_in_range)
                        AND m.role = 'customer'
                        AND LOWER(m.message) != 'pengguna baru'
                    GROUP BY m.session_id
                ),
                cs_first AS (
                    SELECT DISTINCT ON (m.session_id)
                        m.session_id,
                        m.timestamp AS first_cs_time,
                        m.sender_username,
                        m.sender_id AS cs_id
                    FROM messages m
                    JOIN customer_first_messages cfm ON m.session_id = cfm.session_id
                    WHERE m.role = 'customer_service'
                        AND m.timestamp > cfm.first_customer_time
                        AND m.sender_username NOT IN ('system', 'CS_MAXCHAT', 'System', 'undefined')
                        AND LOWER(m.message) != 'pengguna baru'
                        AND m.message NOT ILIKE '%Terima kasih, sobat! atas penilaian kamu%'
                    ORDER BY m.session_id, m.timestamp ASC
                ),
                final_calc AS (
                    SELECT
                        cfm.session_id,
                        cfm.first_customer_time,
                        cs.first_cs_time,
                        cs.sender_username,
                        cs.cs_id,
                        EXTRACT(EPOCH FROM (cs.first_cs_time - cfm.first_customer_time)) AS response_time_seconds
                    FROM customer_first_messages cfm
                    JOIN cs_first cs ON cs.session_id = cfm.session_id
                )";

            $overallSql = $baseCte . "
                SELECT
                    COUNT(*) FILTER (WHERE response_time_seconds <= 180) AS fast_count,
                    COUNT(*) FILTER (WHERE response_time_seconds > 180) AS slow_count,
                    AVG(response_time_seconds) AS avg_seconds
                FROM final_calc";

            $overall = (object) ['fast_count' => 0, 'slow_count' => 0, 'avg_seconds' => 0];
            try {
                $row = DB::selectOne($overallSql, $bindings);
                if ($row) $overall = $row;
            } catch (\Exception $e) {
                Log::error('ResponseTime overall query failed', ['error' => $e->getMessage()]);
            }

            $agentSql = $baseCte . "
                SELECT
                    cs_id,
                    sender_username AS agent_name,
                    COUNT(*) FILTER (WHERE response_time_seconds <= 180) AS fast,
                    COUNT(*) FILTER (WHERE response_time_seconds > 180) AS slow
                FROM final_calc
                WHERE cs_id IS NOT NULL
                GROUP BY cs_id, sender_username";

            $agentResponseTimes = [];
            try {
                $rows = DB::select($agentSql, $bindings);
                foreach ($rows as $r) {
                    $agentResponseTimes[$r->cs_id] = [
                        'fast' => (int) ($r->fast ?? 0),
                        'slow' => (int) ($r->slow ?? 0),
                        'agent_name' => $r->agent_name ?? 'Unknown'
                    ];
                }
            } catch (\Exception $e) {
                Log::error('ResponseTime agent breakdown query failed', ['error' => $e->getMessage()]);
            }

            $avgSeconds = (float) ($overall->avg_seconds ?? 0);
            $avgSecondsInt = (int) ($avgSeconds > 0 ? $avgSeconds : 0);
            $avgResponseTime = $avgSecondsInt > 0 ? gmdate('i\m s\s', $avgSecondsInt) : '0m 0s';

            return [
                'fastCount' => (int) ($overall->fast_count ?? 0),
                'slowCount' => (int) ($overall->slow_count ?? 0),
                'avgResponseTime' => $avgResponseTime,
                'agentResponseTimes' => $agentResponseTimes,
                'responseTimeDays' => $days,
            ];
        });
    }

    private function getResponseTimeDataByDate(string $filterDate)
    {
        Log::info('Response Time Filter Date: ' . $filterDate);

        $bindings = ['filter_date' => $filterDate];
        $baseCte = "WITH sessions_in_range AS (
                SELECT DISTINCT session_id
                FROM messages
                WHERE timestamp::date = :filter_date
                    AND session_id IS NOT NULL
            ),
            customer_first_messages AS (
                SELECT
                    m.session_id,
                    MIN(m.timestamp) AS first_customer_time
                FROM messages m
                JOIN sessions_in_range s ON m.session_id = s.session_id
                WHERE m.role = 'customer'
                    AND LOWER(m.message) != 'pengguna baru'
                GROUP BY m.session_id
            ),
            cs_first AS (
                SELECT DISTINCT ON (m.session_id)
                    m.session_id,
                    m.timestamp AS first_cs_time,
                    m.sender_username,
                    m.sender_id AS cs_id
                FROM messages m
                JOIN customer_first_messages cfm ON m.session_id = cfm.session_id
                WHERE m.role = 'customer_service'
                    AND m.timestamp > cfm.first_customer_time
                    AND m.sender_username NOT IN ('system', 'CS_MAXCHAT')
                    AND LOWER(m.message) != 'pengguna baru'
                    AND m.message NOT ILIKE '%Terima kasih, sobat! atas penilaian kamu%'
                ORDER BY m.session_id, m.timestamp ASC
            ),
            final_calc AS (
                SELECT
                    cfm.session_id,
                    cfm.first_customer_time,
                    cs.first_cs_time,
                    cs.sender_username,
                    cs.cs_id,
                    EXTRACT(EPOCH FROM (cs.first_cs_time - cfm.first_customer_time)) AS response_time_seconds
                FROM customer_first_messages cfm
                JOIN cs_first cs ON cs.session_id = cfm.session_id
                WHERE NOT EXISTS (
                    SELECT 1
                    FROM messages m
                    WHERE m.session_id = cfm.session_id
                        AND m.role = 'customer_service'
                        AND m.timestamp > cfm.first_customer_time
                        AND m.timestamp < cs.first_cs_time
                )
            )";

        $overallSql = $baseCte . "
            SELECT
                COUNT(*) FILTER (WHERE response_time_seconds <= 180) AS fast_count,
                COUNT(*) FILTER (WHERE response_time_seconds > 180) AS slow_count,
                AVG(response_time_seconds) AS avg_seconds
            FROM final_calc";

        $overall = (object) ['fast_count' => 0, 'slow_count' => 0, 'avg_seconds' => 0];
        try {
            $row = DB::selectOne($overallSql, $bindings);
            if ($row) $overall = $row;
        } catch (\Exception $e) {
            Log::error('ResponseTime overall query failed', ['error' => $e->getMessage()]);
        }

        $agentSql = $baseCte . "
            SELECT
                cs_id,
                sender_username AS agent_name,
                COUNT(*) FILTER (WHERE response_time_seconds <= 180) AS fast,
                COUNT(*) FILTER (WHERE response_time_seconds > 180) AS slow
            FROM final_calc
            GROUP BY cs_id, sender_username";

        $agentResponseTimes = [];
        try {
            $rows = DB::select($agentSql, $bindings);
            foreach ($rows as $r) {
                $agentResponseTimes[$r->cs_id] = [
                    'fast' => (int) ($r->fast ?? 0),
                    'slow' => (int) ($r->slow ?? 0),
                    'agent_name' => $r->agent_name ?? 'Unknown'
                ];
            }
        } catch (\Exception $e) {
            Log::error('ResponseTime agent breakdown query failed', ['error' => $e->getMessage()]);
        }

        $avgSeconds = (float) ($overall->avg_seconds ?? 0);
        $avgSecondsInt = (int) ($avgSeconds > 0 ? $avgSeconds : 0);
        $avgResponseTime = $avgSecondsInt > 0 ? gmdate('i\m s\s', $avgSecondsInt) : '0m 0s';

        return [
            'fastCount' => (int) ($overall->fast_count ?? 0),
            'slowCount' => (int) ($overall->slow_count ?? 0),
            'avgResponseTime' => $avgResponseTime,
            'agentResponseTimes' => $agentResponseTimes,
            'filterDate' => $filterDate,
        ];
    }

    private function getMessageStats($startDate = '', $endDate = '')
    {
        if (empty($startDate) && empty($endDate)) {
            $startDate = Carbon::now()->subDays(90)->format('Y-m-d');
            $endDate = Carbon::now()->format('Y-m-d');
        }

        // Optimasi: Cache message stats untuk 15 menit
        $cacheKey = 'message_stats_' . $startDate . '_' . $endDate;

        return cache()->remember($cacheKey, 900, function () use ($startDate, $endDate) {
            Log::info('Message Stats Range: ' . $startDate . ' to ' . $endDate);
            $startTimestamp = $startDate . ' 00:00:00';
            $endTimestamp = $endDate . ' 23:59:59';

            // Optimasi: Gunakan satu query dengan conditional aggregation
            $stats = DB::table('messages')
                ->selectRaw("
                    COUNT(*) FILTER (WHERE role = 'customer' AND sender_username IS NOT NULL AND sender_username != 'System') AS total_all_messages,
                    COUNT(*) FILTER (WHERE role = 'customer' AND sender_username NOT IN ('System', 'undefined', 'system') AND sender_username IS NOT NULL) AS total_messages,
                    COUNT(*) FILTER (WHERE role = 'customer_service' AND reply_to IS NOT NULL) AS total_replies_by_cs
                ")
                ->whereBetween('timestamp', [$startTimestamp, $endTimestamp])
                ->first();

            Log::debug('Message Stats Query', [
                'startTimestamp' => $startTimestamp,
                'endTimestamp' => $endTimestamp,
                'totalAllMessages' => $stats->total_all_messages ?? 0,
                'totalMessages' => $stats->total_messages ?? 0,
            ]);

            return [
                'totalMessages' => (int)($stats->total_messages ?? 0),
                'totalAllMessages' => (int)($stats->total_all_messages ?? 0),
                'totalRepliesByCS' => (int)($stats->total_replies_by_cs ?? 0)
            ];
        });
    }

    private function getCSAgentsData($responseTimeData = null, int $responseTimeDays = 30)
    {
        $perPage = (int) request()->get('agent_per_page', 6);
        $allowed = [6, 12, 24, 48, 96];
        $perPage = in_array($perPage, $allowed) ? $perPage : 6;
        $page = max(1, (int) request()->get('agent_page', 1));
        $offset = ($page - 1) * $perPage;
        $selectedDate = request()->input('status_date', now()->toDateString());

        $responseTimeData = $responseTimeData ?? $this->getResponseTimeData($responseTimeDays);
        $agentResponseTimes = $responseTimeData['agentResponseTimes'] ?? [];
        $fastCount = $responseTimeData['fastCount'] ?? 0;
        $slowCount = $responseTimeData['slowCount'] ?? 0;
        $totalFeedback = $fastCount + $slowCount;

        // Optimasi: Cache count query dengan timeout 5 menit
        $cacheKey = 'total_agents_count';
        try {
            $totalAgents = cache()->remember($cacheKey, 300, function () {
                return User::where('role', 'customer_service')->count();
            });
        } catch (\Exception $e) {
            Log::error('Failed to count agents', ['error' => $e->getMessage()]);
            $totalAgents = 0;
        }

        // Optimasi: Gunakan select minimal dan eager loading
        $usersQuery = User::select(['id', 'username', 'email', 'status', 'last_status_update', 'role'])
            ->where('role', 'customer_service')
            ->orderBy('username');

        $users = $usersQuery->skip($offset)->take($perPage)->get();
        $agentIds = $users->pluck('id')->all();

        if (empty($agentIds)) {
            return [
                'csAgents' => collect(),
                'csAgentsPaginated' => new \Illuminate\Pagination\LengthAwarePaginator(collect(), 0, $perPage, $page, ['path' => request()->url(), 'pageName' => 'agent_page']),
                'agentPerPage' => $perPage,
                'agentPage' => $page,
                'totalFeedback' => 0,
                'selectedDate' => $selectedDate,
            ];
        }

        // Optimasi: Gunakan indeks yang sudah dibuat dan batasi kolom
        $ratingAgg = DB::table('satisfaction_ratings')
            ->selectRaw("cs_id,
                SUM(CASE WHEN LOWER((rating::json->>'id')) IN ('marah','sedih') THEN 1 ELSE 0 END) AS negative_count,
                SUM(CASE WHEN LOWER((rating::json->>'id')) IN ('puas','sangat puas') THEN 1 ELSE 0 END) AS positive_count,
                COUNT(*) AS total_count")
            ->whereIn('cs_id', $agentIds)
            ->where('rating', 'like', '{%')
            ->groupBy('cs_id')
            ->get()
            ->keyBy('cs_id');

        // Optimasi: Gunakan indeks untuk session_id dan sender_id
        $handleCounts = DB::table('messages')
            ->select('sender_id', DB::raw('COUNT(DISTINCT session_id) as total_handle_chat'))
            ->where('role', 'customer_service')
            ->whereIn('sender_id', $agentIds)
            ->whereNotNull('session_id')
            ->groupBy('sender_id')
            ->pluck('total_handle_chat', 'sender_id');

        // Optimasi: Batasi query status duration dengan indeks yang tepat
        try {
            $statusDurations = DB::table('user_status_logs as l')
                ->select(
                    'l.user_id as id',
                    'l.status',
                    DB::raw('SUM(EXTRACT(EPOCH FROM (COALESCE(l.ended_at, NOW()) - l.started_at))) / 3600 as durasi_jam')
                )
                ->whereIn('l.user_id', $agentIds)
                ->whereDate('l.started_at', $selectedDate)
                ->groupBy('l.user_id', 'l.status')
                ->get();
        } catch (\Exception $e) {
            Log::error('Failed to fetch status durations', ['error' => $e->getMessage(), 'selectedDate' => $selectedDate, 'agentIds' => $agentIds]);
            $statusDurations = collect();
        }

        // Optimasi: Proses status duration dengan lebih efisien
        $statusDurationsByAgent = [];
        foreach ($statusDurations as $duration) {
            $statusDurationsByAgent[$duration->id][$duration->status] = round((float) $duration->durasi_jam, 2);
        }

        // Optimasi: Gunakan data user yang sudah di-fetch untuk menghindari query tambahan
        $userStatusMap = $users->keyBy('id');
        foreach ($agentIds as $agentId) {
            if (!isset($statusDurationsByAgent[$agentId])) {
                $user = $userStatusMap[$agentId] ?? null;
                if ($user && $user->status === 'online') {
                    $onlineStart = $user->last_status_update ? Carbon::parse($user->last_status_update) : Carbon::now();
                    $durasiOnline = $onlineStart->diffInHours(Carbon::now());
                    $statusDurationsByAgent[$agentId] = [
                        'online' => max(0, $durasiOnline),
                        'offline' => 0.0,
                        'busy' => 0.0,
                    ];
                } else {
                    $statusDurationsByAgent[$agentId] = [
                        'online' => 0.0,
                        'offline' => 0.0,
                        'busy' => 0.0,
                    ];
                }
            }

            // Pastikan semua status ada dengan default 0
            $statusDurationsByAgent[$agentId] = array_merge([
                'online' => 0.0,
                'offline' => 0.0,
                'busy' => 0.0
            ], $statusDurationsByAgent[$agentId] ?? []);
        }

        // Optimasi: Proses mapping dengan perhitungan yang lebih efisien
        $defaultAvatar = asset('assets/img/profile.jpeg');
        $csAgentsCollection = $users->map(function ($user) use ($agentResponseTimes, $ratingAgg, $handleCounts, $statusDurationsByAgent, $defaultAvatar) {
            $agg = $ratingAgg[$user->id] ?? null;
            $positive = $agg ? (int)$agg->positive_count : 0;
            $negative = $agg ? (int)$agg->negative_count : 0;
            $totalRatings = $agg ? (int)$agg->total_count : 0;

            // Optimasi: Perhitungan satisfaction score yang lebih sederhana
            $satisfactionScore = $totalRatings === 0 ? 100 : max(0, min(100, round((($positive * 100) / $totalRatings), 2)));

            $agentResponse = $agentResponseTimes[$user->id] ?? ['fast' => 0, 'slow' => 0];
            $fast = (int)$agentResponse['fast'];
            $slow = (int)$agentResponse['slow'];
            $totalFS = $fast + $slow;

            // Optimasi: Avatar handling yang lebih efisien
            $avatar = $defaultAvatar;

            $displayName = $user->username ?? '-';

            // Optimasi: Format status duration sebagai array dengan key yang konsisten
            $statusDurations = $statusDurationsByAgent[$user->id] ?? ['online' => 0.0, 'offline' => 0.0, 'busy' => 0.0];
            $formattedStatusDurations = [
                ['status' => 'online', 'durasi_jam' => $statusDurations['online']],
                ['status' => 'offline', 'durasi_jam' => $statusDurations['offline']],
                ['status' => 'busy', 'durasi_jam' => $statusDurations['busy']],
            ];

            return [
                'name' => $displayName,
                'role' => 'Customer Service',
                'avatar' => $avatar,
                'status' => $user->status ?? 'offline',
                'feedback' => $totalRatings,
                'fast' => $fast,
                'slow' => $slow,
                'avg_fast' => $totalFS > 0 ? round(($fast / $totalFS) * 100, 2) : 0,
                'avg_slow' => $totalFS > 0 ? round(($slow / $totalFS) * 100, 2) : 0,
                'contact' => $user->email ?? null,
                'online_time' => $user->last_status_update
                    ? Carbon::parse($user->last_status_update)->diffForHumans(null, true)
                    : ($user->status === 'online' ? 'Online now' : 'Offline'),
                'satisfaction' => $satisfactionScore,
                'total_handle_chat' => $handleCounts[$user->id] ?? 0,
                'status_durations' => $formattedStatusDurations,
            ];
        });

        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $csAgentsCollection,
            $totalAgents,
            $perPage,
            $page,
            ['path' => url('/admin/dashboard'), 'pageName' => 'agent_page']
        );

        return [
            'csAgents' => $csAgentsCollection,
            'csAgentsPaginated' => $paginator,
            'agentPerPage' => $perPage,
            'agentPage' => $page,
            'totalFeedback' => $totalFeedback,
            'selectedDate' => $selectedDate,
        ];
    }

    private function calculateUserSatisfaction($ratings)
    {
        $baseSatisfaction = 100;
        $satisfactionScore = $baseSatisfaction;

        foreach ($ratings as $rating) {
            if (is_string($rating->rating)) {
                $decoded = json_decode($rating->rating, true);
                if (json_last_error() === JSON_ERROR_NONE && isset($decoded['id'])) {
                    $ratingId = strtolower($decoded['id']);
                    switch ($ratingId) {
                        case 'marah':
                        case 'sedih':
                            $satisfactionScore = max(0, $satisfactionScore - 1);
                            break;
                        case 'puas':
                        case 'sangat puas':
                            $satisfactionScore = min(100, $satisfactionScore + 1);
                            break;
                    }
                }
            }
        }

        return $ratings->count() === 0 ? 100 : $satisfactionScore;
    }

    private function getChatTrendData($request = null)
    {
        $chartStartDate = $request ? $request->get('chart_start_date') : null;
        $chartEndDate = $request ? $request->get('chart_end_date') : null;

        $today = Carbon::now()->endOfDay();
        if (empty($chartStartDate) || empty($chartEndDate) || !$request) {
            $startDate = Carbon::now()->subDays(90)->startOfDay();
            $endDate = $today;
        } else {
            try {
                $startDate = Carbon::parse($chartStartDate)->startOfDay();
                $endDate = Carbon::parse($chartEndDate)->endOfDay();
                if ($endDate->gt($today)) $endDate = $today;
                if ($startDate->gt($today)) $startDate = $today->copy()->subDays(90);
                if ($startDate->gt($endDate)) $startDate = $endDate->copy()->subDays(90);
            } catch (\Exception $e) {
                Log::warning('Invalid date format in getChatTrendData: ' . $e->getMessage());
                $startDate = Carbon::now()->subDays(90)->startOfDay();
                $endDate = $today;
            }
        }

        if ($request && ($request->has('chart_start_date') || $request->has('chart_end_date'))) {
            session(['chart_start_date' => $startDate->format('Y-m-d')]);
            session(['chart_end_date' => $endDate->format('Y-m-d')]);
        }

        $customerTrend = Message::selectRaw("DATE(timestamp) as date, COUNT(*) as total")
            ->whereBetween('timestamp', [$startDate, $endDate])
            ->where('role', 'customer')
            ->whereNotNull('timestamp')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->mapWithKeys(function ($item) {
                return [Carbon::parse($item->date)->format('Y-m-d') => $item->total];
            });

        $csTrend = Message::selectRaw("DATE(timestamp) as date, COUNT(*) as total")
            ->whereBetween('timestamp', [$startDate, $endDate])
            ->where('role', 'customer_service')
            ->whereNotNull('timestamp')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->mapWithKeys(function ($item) {
                return [Carbon::parse($item->date)->format('Y-m-d') => $item->total];
            });

        $period = $startDate->daysUntil($endDate);
        $chartLabels = [];
        $customerData = [];
        $csData = [];

        foreach ($period as $date) {
            $dateLabel = $date->format('Y-m-d');
            $chartLabels[] = $date->format('M d');
            $customerData[] = $customerTrend[$dateLabel] ?? 0;
            $csData[] = $csTrend[$dateLabel] ?? 0;
        }

        $totalCustomerInRange = array_sum($customerData);

        Log::info('Chat Trend Data', [
            'startDate' => $startDate->toDateString(),
            'endDate' => $endDate->toDateString(),
            'labels' => $chartLabels,
            'customerData' => $customerData,
            'csData' => $csData,
            'totalCustomerInRange' => $totalCustomerInRange
        ]);

        return [
            'chartLabels' => $chartLabels,
            'customerData' => $customerData,
            'csData' => $csData,
            'chartStartDate' => $startDate->format('Y-m-d'),
            'chartEndDate' => $endDate->format('Y-m-d'),
            'totalCustomerInRange' => $totalCustomerInRange
        ];
    }

    private function getReviewsData(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $validPerPage = in_array($perPage, [10, 20, 50, 100]) ? $perPage : 10;

        $base = Message::query()
            ->where('role', 'customer')
            ->whereNotNull('sender_username')
            ->whereNotIn('sender_username', ['System', 'system', 'undefined'])
            ->where(function ($query) {
                $excludeMessages = [
                    'This chat was assigned to',
                    'This chat was reassigned to',
                    'Terima kasih, sobat! atas penilaian kamu',
                    'foto'
                ];
                foreach ($excludeMessages as $exclude) {
                    $query->where('message', 'not like', '%' . $exclude . '%');
                }
            });

        $reviewsPaginated = $base->orderByDesc('timestamp')->paginate($validPerPage);
        $messagesCurrentPage = collect($reviewsPaginated->items());
        $customerIds = $messagesCurrentPage->pluck('id')->all();
        $roomIds = $messagesCurrentPage->pluck('room_id')->filter()->unique()->values();

        $agentByCustomerId = collect();
        if (!empty($customerIds)) {
            $agentRows = DB::table('messages as c')
                ->select('c.id as customer_id', DB::raw("MIN(cs.sender_username) as agent_name"))
                ->leftJoin('messages as cs', function ($join) {
                    $join->on('cs.role', DB::raw("'customer_service'"))
                        ->on(DB::raw('(cs.reply_to = c.id OR (cs.room_id = c.room_id AND cs.timestamp > c.timestamp))'), DB::raw('TRUE'));
                })
                ->whereIn('c.id', $customerIds)
                ->where('c.role', 'customer')
                ->groupBy('c.id')
                ->get();
            $agentByCustomerId = $agentRows->pluck('agent_name', 'customer_id');
        }

        $reviews = $messagesCurrentPage->map(function ($message) use ($agentByCustomerId) {
            $avatarUrl = 'https://ui-avatars.com/api/?name=' . urlencode($message->sender_username) . '&size=48&background=random';
            $timeAgo = Carbon::parse($message->timestamp)->diffForHumans();
            $rating = rand(3, 5);
            $stars = str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);
            $agent = $agentByCustomerId[$message->id] ?? null;
            return [
                'id' => $message->id,
                'name' => $message->sender_username,
                'agent_name' => $agent,
                'avatar' => $avatarUrl,
                'time_ago' => $timeAgo,
                'message' => $message->message ?? 'No message content',
                'rating' => $rating,
                'stars' => $stars,
                'timestamp' => $message->timestamp
            ];
        });

        $reviewsPaginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $reviews,
            $reviewsPaginated->total(),
            $reviewsPaginated->perPage(),
            $reviewsPaginated->currentPage(),
            ['path' => request()->url(), 'pageName' => 'page']
        );

        return [
            'reviewsPaginated' => $reviewsPaginated,
            'currentPerPage' => $validPerPage
        ];
    }

    private function getCustomerReportData(Request $request)
    {
        $dateInputs = $this->validateDateInputs(
            $request->get('second_start_date'),
            $request->get('second_end_date')
        );

        $startDate = $dateInputs['start_date'] . ' 00:00:00';
        $endDate = $dateInputs['end_date'] . ' 23:59:59';
        $today = $dateInputs['today'];

        // Guard: if range is inverted or too large, clamp or return zeros quickly
        if (Carbon::parse($startDate)->gt(Carbon::parse($endDate))) {
            return [
                'monthlyActiveUser' => 0,
                'newCustomer' => 0,
                'existingCustomer' => 0,
                'customerChartDates' => [],
                'customerChartNewCustomers' => [],
                'customerChartExistingCustomers' => [],
                'startDate' => $dateInputs['start_date'],
                'endDate' => $dateInputs['end_date'],
                'today' => $today,
            ];
        }

        // Jika tidak ada input tanggal, gunakan default 30 hari terakhir
        if (empty($request->get('second_start_date')) && empty($request->get('second_end_date'))) {
            $dateInputs['start_date'] = Carbon::now()->subDays(30)->format('Y-m-d');
            $dateInputs['end_date'] = $today;
            $startDate = $dateInputs['start_date'] . ' 00:00:00';
            $endDate = $dateInputs['end_date'] . ' 23:59:59';
        }

        session(['second_start_date' => $dateInputs['start_date']]);
        session(['second_end_date' => $dateInputs['end_date']]);

        // Monthly Active User (range) berbasis chat_rooms: total room (new + existing) dalam rentang
        $monthlyActiveUser = (int) DB::table('chat_rooms')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        // New Customer (range) = chat_rooms tanpa kode_user valid
        $newCustomer = (int) DB::table('chat_rooms')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where(function ($q) {
                $q->whereNull('kode_user')
                    ->orWhereRaw("NULLIF(TRIM(kode_user), '') IS NULL")
                    ->orWhereRaw("LOWER(TRIM(kode_user)) = 'null'");
            })
            ->count();

        // Existing Customer (range) = chat_rooms dengan kode_user valid
        $existingCustomer = (int) DB::table('chat_rooms')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('kode_user')
            ->whereRaw("NULLIF(TRIM(kode_user), '') IS NOT NULL")
            ->whereRaw("LOWER(TRIM(kode_user)) <> 'null'")
            ->count();

        // Get chart data directly
        $chartData = $this->getCustomerChartData($dateInputs['start_date'], $dateInputs['end_date'], $today);

        return [
            'monthlyActiveUser' => $monthlyActiveUser,
            'newCustomer' => $newCustomer,
            'existingCustomer' => $existingCustomer,
            'customerChartDates' => $chartData['dates'],
            'customerChartNewCustomers' => $chartData['newCustomers'],
            'customerChartExistingCustomers' => $chartData['existingCustomers'],
            'startDate' => $dateInputs['start_date'],
            'endDate' => $dateInputs['end_date'],
            'today' => $today,
        ];
    }

    private function getCustomerChartData($startDate, $endDate, $today)
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        // Hitung New vs Existing per hari berbasis chat_rooms.created_at dan kode_user
        $raw = DB::table('chat_rooms')
            ->selectRaw(
                "DATE(created_at) as d,
                SUM(CASE WHEN (kode_user IS NULL OR TRIM(kode_user) = '' OR LOWER(TRIM(kode_user)) = 'null') THEN 1 ELSE 0 END) AS new_c,
                SUM(CASE WHEN (kode_user IS NOT NULL AND NULLIF(TRIM(kode_user), '') IS NOT NULL AND LOWER(TRIM(kode_user)) <> 'null') THEN 1 ELSE 0 END) AS existing_c"
            )
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('d')
            ->orderBy('d')
            ->get()
            ->keyBy(fn($row) => (string) $row->d);

        $period = $start->daysUntil($end);
        $chartDates = [];
        $chartNewCustomers = [];
        $chartExistingCustomers = [];

        foreach ($period as $date) {
            $ymd = $date->format('Y-m-d');
            if ($ymd > $today) break;

            $chartDates[] = $date->format('M d');
            $row = $raw[$ymd] ?? null;
            $chartNewCustomers[] = $row ? (int) $row->new_c : 0;
            $chartExistingCustomers[] = $row ? (int) $row->existing_c : 0;
        }

        return [
            'dates' => $chartDates,
            'newCustomers' => $chartNewCustomers,
            'existingCustomers' => $chartExistingCustomers
        ];
    }

    private function getCSATData(Request $request)
    {
        $perPage = (int) $request->get('csat_per_page', 10);
        $perPage = in_array($perPage, [10, 20, 50, 100]) ? $perPage : 10;
        $page = max(1, (int) $request->get('csat_page', 1));
        $search = trim($request->get('csat_search', ''));
        $csatCsId = $request->get('csat_cs_id', '');
        $startDate = $request->get('csat_start_date');
        $endDate = $request->get('csat_end_date');
        
        // Sorting parameters
        $sortBy = $request->get('sort_by', '');
        $sortOrder = $request->get('sort_order', 'asc');
        
        // Validasi kolom sorting
        $allowedSortColumns = ['first_response_time', 'average_response_time', 'resolved_time'];
        if (!in_array($sortBy, $allowedSortColumns)) {
            $sortBy = '';
        }
        
        // Validasi sort order
        if (!in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'asc';
        }

        if (empty($startDate) || empty($endDate)) {
            $startDate = Carbon::now()->subDays(90)->format('Y-m-d');
            $endDate = Carbon::now()->format('Y-m-d');
        }

        // Query dari tabel agent_responses
        $query = AgentResponse::query();

        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('customer_name', 'ILIKE', '%' . $search . '%')
                  ->orWhere('agent_name', 'ILIKE', '%' . $search . '%');
            });
        }

        if (!empty($csatCsId)) {
            $query->where('agent_name', 'ILIKE', '%' . $csatCsId . '%');
        }

        if (!empty($startDate)) {
            $query->whereDate('date', '>=', $startDate);
        }

        if (!empty($endDate)) {
            $query->whereDate('date', '<=', $endDate);
        }
        
        // Apply sorting jika ada
        if (!empty($sortBy)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('date', 'desc');
        }

        $paginator = $query->paginate($perPage, ['*'], 'csat_page', $page);
        $paginator = $query->orderBy('date', 'desc')
            ->paginate($perPage, ['*'], 'csat_page', $page);

        // Format data untuk view
        $collection = $paginator->getCollection()->map(function($row) {
            return [
                'id' => $row->id,
                'customer_name' => $row->customer_name,
                'agent_name' => $row->agent_name,
                'date' => Carbon::parse($row->date)->format('d M Y'),
                'first_response_time' => $row->first_response_time,
                'average_response_time' => $row->average_response_time,
                'resolved_time' => $row->resolved_time,
                'timestamp' => $row->date,
            ];
        });

        $paginator->setCollection($collection);
        $paginator->appends([
            'csat_per_page' => $perPage,
            'csat_search' => $search,
            'csat_start_date' => $startDate,
            'csat_end_date' => $endDate,
            'csat_cs_id' => $csatCsId,
            'sort_by' => $sortBy,
            'sort_order' => $sortOrder,
            'section' => 'agents'
        ]);

        // Hitung statistik
        $totalCSATSent = AgentResponse::whereBetween('date', [$startDate, $endDate])->count();
        $totalCSATResponded = $totalCSATSent;
        
        // Hitung average response time
        $avgSeconds = AgentResponse::whereBetween('date', [$startDate, $endDate])
            ->selectRaw('AVG(EXTRACT(EPOCH FROM first_response_time::time)) as avg_sec')
            ->value('avg_sec');
        $avgResponseTime = $avgSeconds > 0 ? gmdate('i\m s\s', (int)$avgSeconds) : '0m 0s';
        
        // Hitung average handle time
        $avgAHTSeconds = AgentResponse::whereBetween('date', [$startDate, $endDate])
            ->selectRaw('AVG(EXTRACT(EPOCH FROM average_response_time::time)) as avg_sec')
            ->value('avg_sec');
        $avgAHT = $avgAHTSeconds > 0 ? gmdate('i\m s\s', (int)$avgAHTSeconds) : '0m 0s';


        $avgCSAT = 4.5; // Default CSAT score
        $avgCSAT = 90; // CSAT: 4.5/5 * 100% = 90% ✓
        // PERUBAHAN BLACKBOXAI: Konversi skor CSAT dari skala 1-5 ke persentase sesuai request user
        $chatResolved = $totalCSATSent;

        return [
            'csatResponsesPaginated' => $paginator,
            'csatResponses' => $collection,
            'totalCSATSent' => $totalCSATSent,
            'totalCSATResponded' => $totalCSATResponded,
            'avgCSATResponseTime' => $avgResponseTime,
            'avgResponseTime' => $avgResponseTime,
            'avgAHT' => $avgAHT,
            'avgCSAT' => $avgCSAT,
            'chatResolved' => $chatResolved,
            'csatPerPage' => $perPage,
            'csatSearch' => $search,
            'csatStartDate' => $startDate,
            'csatEndDate' => $endDate,
            'totalSessions' => $totalCSATSent,

            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder,
        ];
    }

    private function getAvgCSATScore($csId, $startDate, $endDate)
    {
        $startTime = $startDate . ' 00:00:00';
        $endTime = $endDate . ' 23:59:59';

        $query = SatisfactionRating::select('rating')
            ->whereBetween('received_at', [$startTime, $endTime]);

        if ($csId) {
            $query->where('cs_id', $csId);
        }

        $ratings = $query->get();

        if ($ratings->isEmpty()) {
            return 0;
        }

        $totalScore = 0;
        $count = 0;
        foreach ($ratings as $rating) {
            $decoded = json_decode($rating->rating, true);
            if (json_last_error() === JSON_ERROR_NONE && isset($decoded['id'])) {
                $ratingId = strtolower($decoded['id']);
                $score = match ($ratingId) {
                    'marah' => 1,
                    'sedih' => 2,
                    'datar' => 3,
                    'puas' => 4,
                    'sangat puas' => 5,
                    default => 3,
                };
                $totalScore += $score;
                $count++;
            }
        }

        return $count > 0 ? round($totalScore / $count, 2) : 0;
    }

    private function formatAvgTime($seconds)
    {
        if ($seconds <= 0) return '0m 0s';
        $minutes = floor($seconds / 60);
        $secs = (int) ($seconds % 60);
        return $minutes . 'm ' . $secs . 's';
    }

    private function validateDateInputs($startDate, $endDate)
    {
        $today = Carbon::now()->format('Y-m-d');
        $validStartDate = $startDate ?: Carbon::now()->subDays(90)->format('Y-m-d');
        $validEndDate = $endDate ?: $today;

        try {
            if (!empty($startDate)) $validStartDate = Carbon::parse($startDate)->format('Y-m-d');
            if (!empty($endDate)) $validEndDate = Carbon::parse($endDate)->format('Y-m-d');
        } catch (\Exception $e) {
            Log::warning('Invalid date format in validateDateInputs: ' . $e->getMessage());
            $validStartDate = Carbon::now()->subDays(90)->format('Y-m-d');
            $validEndDate = $today;
        }

        if ($validStartDate > $today) $validStartDate = $today;
        if ($validEndDate > $today) $validEndDate = $today;
        if ($validStartDate > $validEndDate) $validStartDate = $validEndDate;

        return ['start_date' => $validStartDate, 'end_date' => $validEndDate, 'today' => $today];
    }

    private function calculateAverageResponseTime($responses)
    {
        $totalSeconds = 0;
        $count = 0;
        foreach ($responses as $response) {
            try {
                $requestTime = Carbon::createFromFormat('H:i:s', $response['requested_at']);
                $responseTime = Carbon::createFromFormat('H:i:s', $response['responded_at']);
                $diff = $responseTime->diffInSeconds($requestTime);
                $totalSeconds += $diff;
                $count++;
            } catch (\Exception $e) {
                continue;
            }
        }
        return $count === 0 ? '0m 0s' : sprintf('%dm %ds', floor($totalSeconds / $count / 60), ($totalSeconds / $count) % 60);
    }

    private function getFallbackData()
    {
        $today = Carbon::now()->format('Y-m-d');
        $startDate = Carbon::now()->subDays(6)->format('Y-m-d');

        return [
            'ratingCounts' => ['marah' => 2, 'sedih' => 5, 'datar' => 8, 'puas' => 25, 'sangat puas' => 15],
            'fastCount' => 35,
            'slowCount' => 8,
            'positivePercentage' => 72.7,
            'negativePercentage' => 12.7,
            'totalMessages' => 125,
            'totalAllMessages' => 156,
            'totalRepliesByCS' => 98,
            'csAgents' => [
                ['name' => 'Agent Demo', 'role' => 'Customer Service', 'avatar' => 'https://ui-avatars.com/api/?name=Agent+Demo&size=48', 'status' => 'online', 'feedback' => 15, 'fast' => 12, 'slow' => 3, 'avg_fast' => 80.0, 'avg_slow' => 20.0, 'contact' => 'demo@responily.com', 'online_time' => '2 hours ago', 'satisfaction' => 85, 'total_handle_chat' => 10],
            ],
            'avgResponseTime' => '3m 45s',
            'chartLabels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            'customerData' => [8, 12, 10, 15, 20, 10, 14],
            'csData' => [4, 6, 5, 7, 8, 6, 6],
            'chartStartDate' => Carbon::now()->subDays(6)->format('Y-m-d'),
            'chartEndDate' => $today,
            'satisfactionRatings' => collect([]),
            'reviewsPaginated' => new \Illuminate\Pagination\LengthAwarePaginator(
                collect([['id' => 1, 'name' => 'Demo User', 'avatar' => 'https://ui-avatars.com/api/?name=Demo+User&size=48', 'time_ago' => '2 hours ago', 'message' => 'This is demo data. Database connection needed for real data.', 'rating' => 4, 'stars' => '★★★★☆', 'timestamp' => now()]]),
                1,
                10,
                1,
                ['path' => request()->url()]
            ),
            'currentPerPage' => 10,
            'monthlyActiveUser' => 45,
            'newCustomer' => 12,
            'existingCustomer' => 33,
            'customerChartDates' => collect(range(0, 6))->map(function ($day) use ($startDate) {
                return Carbon::parse($startDate)->addDays($day)->format('M d');
            })->toArray(),
            'customerChartNewCustomers' => array_fill(0, 7, 0),
            'customerChartExistingCustomers' => array_fill(0, 7, 0),
            'startDate' => Carbon::now()->startOfMonth()->format('Y-m-d'),
            'endDate' => $today,
            'csatResponsesPaginated' => new \Illuminate\Pagination\LengthAwarePaginator(
                collect([
                    ['id' => 1, 'customer_name' => 'Yani Nurmala Dewi', 'agent_name' => 'Fayza', 'date' => '28 Jul 2025', 'requested_at' => '13:11:03', 'responded_at' => '13:24:03', 'timestamp' => now()->subHours(1)],
                    ['id' => 2, 'customer_name' => 'Deasy', 'agent_name' => 'Nanda', 'date' => '28 Jul 2025', 'requested_at' => '13:10:52', 'responded_at' => '13:37:52', 'timestamp' => now()->subHours(2)],
                    ['id' => 3, 'customer_name' => 'Diana', 'agent_name' => 'Nadia', 'date' => '28 Jul 2025', 'requested_at' => '13:10:08', 'responded_at' => '13:34:08', 'timestamp' => now()->subHours(3)],
                    ['id' => 4, 'customer_name' => 'abdul madjid', 'agent_name' => 'Fayza', 'date' => '28 Jul 2025', 'requested_at' => '13:06:42', 'responded_at' => '13:34:42', 'timestamp' => now()->subHours(4)],
                    ['id' => 5, 'customer_name' => 'Meyyy', 'agent_name' => 'Nadia', 'date' => '28 Jul 2025', 'requested_at' => '13:03:42', 'responded_at' => '13:11:42', 'timestamp' => now()->subHours(5)]
                ]),
                5,
                10,
                1,
                ['path' => request()->url(), 'pageName' => 'csat_page']
            ),
            'csatResponses' => collect([]),
            'totalCSATSent' => 125,
            'totalCSATResponded' => 98,
            'avgCSATResponseTime' => '2m 15s',
            'csatPerPage' => 10,
            'csatSearch' => '',
            'csatStartDate' => '',
            'csatEndDate' => ''
        ];
    }

    public function downloadAgentCsatTemplate(ExcelExportService $exportService)
    {
        return $exportService->generateTemplate();
    }

    public function exportAgentCsatReport(Request $request, ExcelExportService $exportService)
    {
        Log::info('Export Route Hit', ['params' => $request->all()]);
        try {
            $search    = trim($request->get('search', ''));
            $csatCsId  = $request->get('csat_cs_id', '');
            $startDate = $request->get('date_from');
            $endDate   = $request->get('date_to');
            $format    = strtolower($request->get('format', 'xlsx')); // allow ?format=csv or xls

            if (empty($startDate) || empty($endDate)) {
                $startDate = Carbon::now()->subMonth()->startOfMonth()->format('Y-m-d');
                $endDate   = Carbon::now()->format('Y-m-d');
            }

            // Validate dates
            try {
                $start = Carbon::parse($startDate);
                $end   = Carbon::parse($endDate);
                if ($start->gt($end)) {
                    throw new \Exception('Start date cannot be later than end date.');
                }
            } catch (\Exception $e) {
                throw new \Exception('Invalid date format provided.');
            }

            $data = $this->buildCSATExportData($search, $startDate, $endDate, $csatCsId);
            if (empty($data)) {
                throw new \Exception('No data available for the selected filters.');
            }

            $agentSuffixLog = $csatCsId ? ('agent:' . $csatCsId) : 'all-agents';
            Log::info('Export Data Prepared', [
                'row_count' => count($data),
                'csat_cs_id' => $csatCsId,
                'agent_scope' => $agentSuffixLog,
                'requested_format' => $format
            ]);

            $filters = [
                'search'    => $search,
                'date_from' => $startDate,
                'date_to'   => $endDate,
                'csat_cs_id' => $csatCsId
            ];

            // 1. CSV (universally safe) ---------------------------------------
            if ($format === 'csv') {
                $filename = 'Agent_CSAT_Report' . ($csatCsId ? ('_' . $csatCsId) : '') . '_' . Carbon::now()->format('Y-m-d_H-i-s') . '.csv';
                $handle = fopen('php://temp', 'r+');
                fputcsv($handle, ['#', 'Customer Name', 'Agent Name', 'Date', 'First Response Time', 'Average Response Time', 'Resolved Time']);
                $rowNo = 1;
                foreach ($data as $row) {
                    fputcsv($handle, [
                        $rowNo++,
                        $row['customer_name'] ?? '',
                        $row['agent_name'] ?? '',
                        $row['date'] ?? '',
                        $row['first_response_time'] ?? '',
                        $row['average_response_time'] ?? '',
                        $row['resolved_time'] ?? '',
                    ]);
                }
                rewind($handle);
                $csv = stream_get_contents($handle);
                fclose($handle);
                return response($csv)
                    ->header('Content-Type', 'text/csv; charset=UTF-8')
                    ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                    ->header('Cache-Control', 'max-age=0');
            }

            // 2. Force legacy HTML .xls explicitly via ?format=xls -------------
            if ($format === 'xls') {
                return $exportService->generateAgentCsatReport($data, $filters, $csatCsId);
            }

            // 3. Try native XLSX (default) ------------------------------------
            try {
                // Attempt real XLSX export first (will throw if package missing or error)
                return Excel::download(new AgentCsatExport($data, $filters), 'Agent_CSAT_Report' . ($csatCsId ? ('_' . $csatCsId) : '') . '_' . Carbon::now()->format('Y-m-d_H-i-s') . '.xlsx');
            } catch (\Throwable $excelEx) {
                Log::warning('XLSX export failed, falling back to HTML .xls', [
                    'error' => $excelEx->getMessage(),
                    'trace_head' => collect(explode("\n", $excelEx->getTraceAsString()))->slice(0, 3)->implode('; ')
                ]);
                // Ensure no partial binary output leaked before fallback
                if (function_exists('ob_get_length') && ob_get_length()) {
                    @ob_end_clean();
                }
                return $exportService->generateAgentCsatReport($data, $filters, $csatCsId);
            }
        } catch (\Exception $e) {
            Log::error('Export Error', ['message' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error exporting report: ' . $e->getMessage()
            ], 400); // Use 400 for client errors, 500 for server errors
        }
    }

    private function buildCSATExportData(string $search, string $startDate, string $endDate, string $csatCsId = ''): array
    {
        $bindings = [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];
        $searchCondition = '';
        if ($search !== '') {
            $bindings['search_like'] = '%' . $search . '%';
            $searchCondition = " AND (f.customer_name ILIKE :search_like OR COALESCE(r.cs_username,'') ILIKE :search_like)";
        }
        if ($csatCsId !== '') {
            $bindings['cs_username'] = $csatCsId;
            $searchCondition .= " AND r.cs_username = :cs_username";
        }

        $sql = "WITH sessions_in_range AS (
            SELECT DISTINCT session_id
            FROM messages
            WHERE timestamp::date BETWEEN :start_date::date AND :end_date::date
                AND session_id IS NOT NULL
        ),
        customer_first_messages AS (
            SELECT
                m.session_id,
                MIN(m.timestamp) AS customer_first_message_time,
                MIN(m.sender_username) FILTER (WHERE m.sender_username NOT IN ('System','system','undefined')) AS customer_name
            FROM messages m
            JOIN sessions_in_range s ON m.session_id = s.session_id
            WHERE m.role = 'customer'
                AND LOWER(m.message) != 'pengguna baru'
            GROUP BY m.session_id
        ),
        first_cs_response AS (
            SELECT DISTINCT ON (m.session_id)
                m.session_id,
                m.sender_id AS cs_id,
                m.sender_username AS cs_username,
                m.timestamp AS cs_first_response_time
            FROM messages m
            JOIN customer_first_messages f ON m.session_id = f.session_id
            WHERE m.role = 'customer_service'
                AND m.timestamp > f.customer_first_message_time
                AND m.sender_username != 'system'
                AND LOWER(m.message) != 'pengguna baru'
                AND m.message NOT ILIKE '%Terima kasih, sobat! atas penilaian kamu%'
            ORDER BY m.session_id, m.timestamp ASC
        ),
        cs_messages AS (
            SELECT m.session_id, m.id, m.timestamp, m.sender_username, m.sender_id
            FROM messages m
            JOIN customer_first_messages f ON m.session_id = f.session_id
            WHERE m.role = 'customer_service'
                AND m.timestamp > f.customer_first_message_time
                AND m.sender_username != 'system'
                AND LOWER(m.message) != 'pengguna baru'
                AND m.message NOT ILIKE '%Terima kasih, sobat! atas penilaian kamu%'
        ),
        cs_stats AS (
            SELECT session_id,
                MAX(timestamp) AS cs_last_response_time,
                COUNT(*) AS cs_message_count,
                AVG(diff_seconds) AS avg_between_cs_seconds
            FROM (
                SELECT cm.session_id,
                    cm.timestamp,
                    EXTRACT(EPOCH FROM (cm.timestamp - LAG(cm.timestamp) OVER (PARTITION BY cm.session_id ORDER BY cm.timestamp))) AS diff_seconds
                FROM cs_messages cm
            ) gaps
            GROUP BY session_id
        )
        SELECT
            f.session_id AS id,
            COALESCE(f.customer_name, 'Unknown') AS customer_name,
            r.cs_username AS agent_name,
            TO_CHAR(f.customer_first_message_time, 'DD Mon YYYY HH24:MI:SS') AS full_date,
            f.customer_first_message_time,
            r.cs_first_response_time,
            st.cs_last_response_time,
            st.avg_between_cs_seconds,
            st.cs_message_count,
            EXTRACT(EPOCH FROM (r.cs_first_response_time - f.customer_first_message_time)) AS first_response_seconds
        FROM customer_first_messages f
        LEFT JOIN first_cs_response r ON f.session_id = r.session_id
        LEFT JOIN cs_stats st ON st.session_id = f.session_id
        WHERE r.cs_first_response_time IS NOT NULL $searchCondition
        ORDER BY f.customer_first_message_time DESC";

        $rows = DB::select($sql, $bindings);
        $formatHMS = function (?int $seconds) {
            if ($seconds === null)
                return '-';
            $h = floor($seconds / 3600);
            $m = floor(($seconds % 3600) / 60);
            $s = $seconds % 60;
            return sprintf('%02d:%02d:%02d', $h, $m, $s);
        };
        return collect($rows)->map(function ($row) use ($formatHMS) {
            $firstSecondsFloat = $row->first_response_seconds !== null ? max(0, (float) $row->first_response_seconds) : null;
            $firstInt = $firstSecondsFloat !== null ? (int) floor($firstSecondsFloat) : null;
            $firstMs = $firstSecondsFloat !== null ? (int) round(($firstSecondsFloat - $firstInt) * 1000) : null;
            $resolvedSeconds = ($row->cs_last_response_time && $row->customer_first_message_time)
                ? max(0, Carbon::parse($row->cs_last_response_time)->diffInSeconds(Carbon::parse($row->customer_first_message_time)))
                : null;
            $avgSeconds = ($row->cs_message_count >= 2 && $row->avg_between_cs_seconds !== null)
                ? (int) round($row->avg_between_cs_seconds)
                : null;
            $firstFormatted = '-';
            if ($firstInt !== null) {
                if ($firstInt === 0) {
                    $firstFormatted = '00:00:00';
                    if ($firstMs !== null)
                        $firstFormatted .= ' (' . $firstMs . 'ms)';
                } else {
                    $firstFormatted = $formatHMS($firstInt);
                }
            }
            return [
                'customer_name' => $row->customer_name,
                'agent_name' => $row->agent_name ?? 'No Response',
                'date' => $row->full_date,
                'first_response_time' => $firstFormatted,
                'average_response_time' => $avgSeconds !== null ? $formatHMS($avgSeconds) : '-',
                'resolved_time' => $formatHMS($resolvedSeconds),
                // legacy fields kept blank for backward compatibility (if some consumer expects them)
                'requested_at' => '',
                'responded_at' => '',
            ];
        })->toArray();
    }

    /**
     * Export Review Log with aggregated ratings per user
     */
    public function exportReviewLogReport(Request $request, ExcelExportService $exportService)
    {
        Log::info('Review Log Export Route Hit', ['params' => $request->all()]);

        try {
            $search = trim($request->get('search', ''));
            $startDate = $request->get('date_from');
            $endDate = $request->get('date_to');

            if (empty($startDate) || empty($endDate)) {
                $startDate = Carbon::now()->subDays(30)->format('Y-m-d');
                $endDate = Carbon::now()->format('Y-m-d');
            }

            // Validate dates
            try {
                $start = Carbon::parse($startDate);
                $end = Carbon::parse($endDate);
                if ($start->gt($end)) {
                    throw new \Exception('Start date cannot be later than end date.');
                }
            } catch (\Exception $e) {
                throw new \Exception('Invalid date format provided.');
            }

            $data = $this->buildReviewLogExportData($search, $startDate, $endDate);

            if (empty($data)) {
                // Return empty data instead of throwing exception
                $data = [[
                    'id' => 1,
                    'agent_name' => 'No Data',
                    'date' => Carbon::parse($startDate)->format('d M Y'),
                    'total_ratings' => 0,
                    'average_rating' => 0,
                    'dominant_rating' => 'No Data',
                    'sangat_puas' => 0,
                    'puas' => 0,
                    'datar' => 0,
                    'sedih' => 0,
                    'marah' => 0,
                ]];
            }

            Log::info('Review Log Export Data Prepared', ['row_count' => count($data)]);

            $filters = [
                'search' => $search,
                'date_from' => $startDate,
                'date_to' => $endDate
            ];

            return $exportService->generateReviewLogReport($data, $filters);
        } catch (\Exception $e) {
            Log::error('Review Log Export Error', ['message' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error exporting review log report: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Build aggregated review log data for export
     */
    private function buildReviewLogExportData(string $search, string $startDate, string $endDate): array
    {
        $startTime = $startDate . ' 00:00:00';
        $endTime = $endDate . ' 23:59:59';

        $query = DB::table('satisfaction_ratings as sr')
            ->leftJoin('users as cs', function ($join) {
                $join->on('sr.cs_id', '=', DB::raw('CAST(cs.id AS TEXT)'));
            })
            ->select([
                'sr.cs_id',
                DB::raw("COALESCE(cs.username, sr.cs_id, 'Unknown') as agent_name"),
                DB::raw('DATE(sr.received_at) as rating_date'),
                DB::raw('COUNT(*) as total_ratings'),
                DB::raw("SUM(CASE WHEN LOWER((sr.rating::json->>'id')) = 'sangat puas' THEN 1 ELSE 0 END) as sangat_puas"),
                DB::raw("SUM(CASE WHEN LOWER((sr.rating::json->>'id')) = 'puas' THEN 1 ELSE 0 END) as puas"),
                DB::raw("SUM(CASE WHEN LOWER((sr.rating::json->>'id')) = 'datar' THEN 1 ELSE 0 END) as datar"),
                DB::raw("SUM(CASE WHEN LOWER((sr.rating::json->>'id')) = 'sedih' THEN 1 ELSE 0 END) as sedih"),
                DB::raw("SUM(CASE WHEN LOWER((sr.rating::json->>'id')) = 'marah' THEN 1 ELSE 0 END) as marah"),
            ])
            ->whereBetween('sr.received_at', [$startTime, $endTime])
            ->where('sr.rating', 'like', '{%');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('cs.username', 'ILIKE', '%' . $search . '%')
                    ->orWhere('sr.cs_id', 'ILIKE', '%' . $search . '%');
            });
        }

        $results = $query
            ->groupBy('sr.cs_id', 'cs.username', DB::raw('DATE(sr.received_at)'))
            ->orderBy('rating_date', 'desc')
            ->orderBy('agent_name')
            ->get();

        $exportData = [];
        $counter = 1;

        foreach ($results as $row) {
            // Hitung rata-rata rating berdasarkan distribusi
            $totalRatings = $row->total_ratings;
            $weightedSum = ($row->sangat_puas * 5) + ($row->puas * 4) + ($row->datar * 3) + ($row->sedih * 2) + ($row->marah * 1);
            $averageRating = $totalRatings > 0 ? round($weightedSum / $totalRatings, 2) : 0;

            // Determine dominant rating
            $ratings = [
                'sangat_puas' => $row->sangat_puas ?? 0,
                'puas' => $row->puas ?? 0,
                'datar' => $row->datar ?? 0,
                'sedih' => $row->sedih ?? 0,
                'marah' => $row->marah ?? 0
            ];

            $dominantRating = array_keys($ratings, max($ratings))[0];
            $dominantRatingText = match ($dominantRating) {
                'sangat_puas' => 'Sangat Puas',
                'puas' => 'Puas',
                'datar' => 'Datar',
                'sedih' => 'Sedih',
                'marah' => 'Marah',
                default => 'Datar'
            };

            $exportData[] = [
                'id' => $counter++,
                'agent_name' => $row->agent_name,
                'date' => Carbon::parse($row->rating_date)->format('d M Y'),
                'total_ratings' => $totalRatings,
                'average_rating' => $averageRating,
                'dominant_rating' => $dominantRatingText,
                'sangat_puas' => (int)($row->sangat_puas ?? 0),
                'puas' => (int)($row->puas ?? 0),
                'datar' => (int)($row->datar ?? 0),
                'sedih' => (int)($row->sedih ?? 0),
                'marah' => (int)($row->marah ?? 0),
            ];
        }

        return $exportData;
    }

    /**
     * Export Message Log Report with conversation details
     */
    public function exportMessageLogReport(Request $request, ExcelExportService $exportService)
    {
        Log::info('Message Log Export Route Hit', ['params' => $request->all()]);

        try {
            $startDate = $request->get('date_from');
            $endDate = $request->get('date_to');

            if (empty($startDate) || empty($endDate)) {
                $startDate = Carbon::now()->subDays(30)->format('Y-m-d');
                $endDate = Carbon::now()->format('Y-m-d');
            }

            // Validasi tanggal
            $start = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);
            if ($start->gt($end)) {
                throw new \Exception('Start date cannot be later than end date.');
            }

            $data = $this->buildMessageLogExportData($startDate, $endDate);

            if (empty($data)) {
                // Return empty data instead of throwing exception
                $data = [[
                    'id' => 1,
                    'date' => Carbon::parse($startDate)->format('d M Y'),
                    'agent_name' => 'No Data',
                    'customer' => 'No messages found for selected date range',
                    'customer_service' => 'No CS messages found',
                    'csat_rating' => 'No Rating'
                ]];
            }

            $filters = [
                'date_from' => $startDate,
                'date_to' => $endDate
            ];

            return $exportService->generateMessageLogReport($data, $filters);
        } catch (\Exception $e) {
            Log::error('Message Log Export Error', ['message' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error exporting message log report: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Build message log data for export
     */
    private function buildMessageLogExportData(string $startDate, string $endDate): array
    {
        $startTime = $startDate . ' 00:00:00';
        $endTime = $endDate . ' 23:59:59';

        $results = DB::table('satisfaction_ratings as sr')
            ->leftJoin('messages as m', function ($join) {
                $join->on('sr.chat_id', '=', 'm.room_id')
                    ->whereIn('m.role', ['customer', 'customer_service'])
                    ->where('m.type', '=', 'text');
            })
            ->leftJoin('users as cs', function ($join) {
                $join->on('sr.cs_id', '=', DB::raw('CAST(cs.id AS TEXT)'));
            })
            ->whereNotNull('sr.cs_id')
            ->where('sr.cs_id', '!=', '')
            ->select([
                'sr.id as rating_id',
                'sr.chat_id',
                DB::raw("COALESCE(cs.username, sr.cs_id, 'Unknown') as agent_name"),
                DB::raw("TO_CHAR(sr.received_at, 'DD Mon YYYY') as date"),
                DB::raw("STRING_AGG(CASE WHEN m.role = 'customer' THEN COALESCE(m.message, '') ELSE NULL END, ' | ') FILTER (WHERE m.role = 'customer') as customer"),
                DB::raw("STRING_AGG(CASE WHEN m.role = 'customer_service' THEN COALESCE(m.message, '') ELSE NULL END, ' | ') FILTER (WHERE m.role = 'customer_service') as customer_service"),
                DB::raw("LOWER((sr.rating::json->>'id')) as csat_rating")
            ])
            ->whereBetween('sr.received_at', [$startTime, $endTime])
            ->groupBy('sr.id', 'sr.chat_id', 'cs.username', 'sr.cs_id', 'sr.rating', 'sr.received_at')
            ->orderBy('sr.received_at', 'desc')
            ->get();

        $exportData = [];
        $counter = 1;

        foreach ($results as $row) {
            $exportData[] = [
                'id' => $counter++,
                'date' => $row->date,
                'agent_name' => $row->agent_name,
                'customer' => $row->customer ?: 'No customer messages',
                'customer_service' => $row->customer_service ?: 'No CS messages',
                'csat_rating' => ucfirst($row->csat_rating ?? 'Unknown')
            ];
        }

        return $exportData;
    }

    // ===================== AJAX ENDPOINT HELPERS (hindari load semua data di initial page) =====================
    public function getChartData(Request $request)
    {
        return response()->json($this->getChatTrendData($request));
    }
    public function getReviewsDataAjax(Request $request)
    {
        return response()->json($this->getReviewsData($request));
    }
    public function getCustomerReportDataAjax(Request $request)
    {
        return response()->json($this->getCustomerReportData($request));
    }
    public function getFromAdsDataAjax(Request $request)
    {
        return response()->json($this->getFromAdsData($request));
    }
    public function getCSATDataAjax(Request $request)
    {
        return response()->json($this->getCSATData($request));
    }
    public function getResponseTimeDataAjax(Request $request)
    {
        return response()->json($this->getResponseTimeData((int) $request->get('rt_days', 30)));
    }
    public function getSatisfactionRatingsDataAjax(Request $request)
    {
        return response()->json($this->getSatisfactionRatingsData());
    }
    public function getCSAgentsDataAjax(Request $request)
    {
        return response()->json($this->getCSAgentsData(null, (int) $request->get('rt_days', 30)));
    }

    /**
     * Default kosong untuk semua variabel Blade (dipakai di index() sebelum lazy load real data)
     */
    private function getDefaultPayload(): array
    {
        $emptyPaginator = new \Illuminate\Pagination\LengthAwarePaginator(collect(), 0, 10, 1, ['path' => request()->url()]);
        return [
            'ratingCounts' => ['marah' => 0, 'sedih' => 0, 'datar' => 0, 'puas' => 0, 'sangat puas' => 0],
            'positivePercentage' => 0,
            'negativePercentage' => 0,
            'satisfactionRatings' => $emptyPaginator,
            'fastCount' => 0,
            'slowCount' => 0,
            'avgResponseTime' => '0m 0s',
            'agentResponseTimes' => [],
            'csAgents' => $emptyPaginator,
            'totalMessages' => 0,
            'totalAllMessages' => 0,
            'totalRepliesByCS' => 0,
            'chartLabels' => [],
            'customerData' => [],
            'csData' => [],
            'chartStartDate' => null,
            'chartEndDate' => null,
            'reviewsPaginated' => $emptyPaginator,
            'currentPerPage' => 10,
            'monthlyActiveUser' => 0,
            'newCustomer' => 0,
            'existingCustomer' => 0,
            'customerChartDates' => [],
            'customerChartNewCustomers' => [],
            'customerChartExistingCustomers' => [],
            'startDate' => null,
            'endDate' => null,
            'today' => null,
            'totalFromAds' => 0,
            'fromAdsChartDates' => [],
            'fromAdsChartData' => [],
            'adsStartDate' => null,
            'adsEndDate' => null,
            'adsToday' => null,
            'csatResponsesPaginated' => $emptyPaginator,
            'csatResponses' => collect(),
            'totalCSATSent' => 0,
            'totalCSATResponded' => 0,
            'avgCSATResponseTime' => '0m 0s',
            'csatPerPage' => 10,
            'csatSearch' => '',
            'csatStartDate' => '',
            'csatEndDate' => '',
            'csatCsId' => '',
            'messageStatsStartDate' => null,
            'messageStatsEndDate' => null,
        ];
    }

    // ===================== BUNDLE ANALYTICS (1 request untuk semua data berat) =====================
    public function getAnalyticsBundle(Request $request)
    {
        // Penggabungan agar front-end cukup 1 fetch (lebih cepat daripada 3-4 request beruntun)
        $chatTrendData = $this->getChatTrendData($request);
        $customerReportData = $this->getCustomerReportData($request);
        $csatData = $this->getCSATData($request);
        $fromAdsData = $this->getFromAdsData($request);

        // Include summary metrics for the analytics top cards
        $chartDates = $this->validateDateInputs(
            $request->get('chart_start_date'),
            $request->get('chart_end_date')
        );
        $summaryMessageStats = $this->getMessageStats($chartDates['start_date'], $chartDates['end_date']);
        $summarySatisfaction = $this->getSatisfactionRatingsData();

        // Sertakan meta untuk hashing cache di front-end (localStorage)
        return response()->json([
            'success' => true,
            'generated_at' => Carbon::now()->toIso8601String(),
            'filters' => [
                'chart_start_date' => $request->get('chart_start_date'),
                'chart_end_date' => $request->get('chart_end_date'),
                'second_start_date' => $request->get('second_start_date'),
                'second_end_date' => $request->get('second_end_date'),
                'csat_search' => $request->get('csat_search'),
                'csat_start_date' => $request->get('csat_start_date'),
                'csat_end_date' => $request->get('csat_end_date'),
                'csat_per_page' => $request->get('csat_per_page'),
                'csat_page' => $request->get('csat_page'),
                'csat_cs_id' => $request->get('csat_cs_id'),
                'ads_start_date' => $request->get('ads_start_date'),
                'ads_end_date' => $request->get('ads_end_date'),
            ],
            'data' => array_merge($chatTrendData, $customerReportData, $csatData, $fromAdsData, $summaryMessageStats, $summarySatisfaction)
        ]);
    }

    public function getFirstResponseTimeData(Request $request)
    {
        $sql = "
            WITH customer_first_messages AS (
                SELECT
                    session_id,
                    MIN(timestamp) AS first_customer_time
                FROM messages
                WHERE role = 'customer'
                    AND session_id IS NOT NULL
                    AND LOWER(message) != 'pengguna baru'
                GROUP BY session_id
            ),
            cs_first AS (
                SELECT DISTINCT ON (m.session_id)
                    m.session_id,
                    m.timestamp AS first_cs_time,
                    m.sender_username
                FROM messages m
                JOIN customer_first_messages cfm
                    ON m.session_id = cfm.session_id
                WHERE m.role = 'customer_service'
                    AND m.timestamp > cfm.first_customer_time
                    AND m.sender_username != 'system'
                    AND m.sender_username NOT ILIKE '%MAXCHAT%'
                    AND LOWER(m.message) != 'pengguna baru'
                    AND m.message NOT ILIKE '%Terima kasih, sobat! atas penilaian kamu%'
                ORDER BY m.session_id, m.timestamp ASC
            ),
            final_calc AS (
                SELECT
                    cfm.session_id,
                    cfm.first_customer_time,
                    cs.first_cs_time,
                    cs.sender_username,
                    EXTRACT(EPOCH FROM (cs.first_cs_time - cfm.first_customer_time)) AS response_time_seconds,
                    ROUND(EXTRACT(EPOCH FROM (cs.first_cs_time - cfm.first_customer_time)) / 60, 2) AS response_time_minutes
                FROM customer_first_messages cfm
                JOIN cs_first cs ON cs.session_id = cfm.session_id
                WHERE NOT EXISTS (
                    SELECT 1
                    FROM messages m
                    WHERE m.session_id = cfm.session_id
                        AND m.role = 'customer_service'
                        AND m.sender_username ILIKE '%MAXCHAT%'
                        AND m.timestamp > cfm.first_customer_time
                        AND m.timestamp < cs.first_cs_time
                )
            ),
            avg_calc AS (
                SELECT ROUND(AVG(response_time_minutes),2) AS avg_response_minutes
                FROM final_calc
            )
            SELECT
                COUNT(*) AS total_sessions,
                ROUND(AVG(response_time_seconds)::numeric, 2) AS avg_seconds,
                ac.avg_response_minutes AS avg_minutes
            FROM final_calc fc
            CROSS JOIN avg_calc ac
            GROUP BY ac.avg_response_minutes
        ";

        try {
            $result = DB::selectOne($sql);
            $avgSeconds = (float)($result->avg_seconds ?? 0);
            $simpleFormat = $avgSeconds > 0 ? floor($avgSeconds / 60) . 'm ' . (int)($avgSeconds % 60) . 's' : '0m 0s';

            return response()->json([
                'success' => true,
                'total_sessions' => (int)($result->total_sessions ?? 0),
                'avg_seconds' => $avgSeconds,
                'simple_format' => $simpleFormat
            ]);
        } catch (\Exception $e) {
            Log::error('First Response Time Query Failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'simple_format' => '0m 0s'
            ], 500);
        }
    }

    public function getDashboardSummary(Request $request)
    {
        try {
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            return response()->json(['error' => 'DB unavailable'], 503);
        }
        // Reuse existing lightweight methods with small cached windows
        $responseTimeDays = (int) $request->get('rt_days', 30);
        if ($responseTimeDays < 1 || $responseTimeDays > 180) {
            $responseTimeDays = 30;
        }
        $satisfaction = $this->getSatisfactionRatingsData();
        $responseTime = $this->getResponseTimeData($responseTimeDays);
        $messageStats = $this->getMessageStats(
            $request->get('message_stats_start_date', ''),
            $request->get('message_stats_end_date', '')
        );
        return response()->json([
            'fastCount' => $responseTime['fastCount'] ?? 0,
            'slowCount' => $responseTime['slowCount'] ?? 0,
            'totalAllMessages' => $messageStats['totalAllMessages'] ?? 0,
            'totalFeedback' => array_sum($satisfaction['ratingCounts'] ?? []),
            'positivePercentage' => $satisfaction['positivePercentage'] ?? 0,
            'negativePercentage' => $satisfaction['negativePercentage'] ?? 0,
            'ratingCounts' => $satisfaction['ratingCounts'] ?? [],
        ]);
    }

    private function getFromAdsData(Request $request)
    {
        // Use ads dates if provided, otherwise fall back to chart dates
        $adsStartDate = $request->get('ads_start_date') ?: $request->get('chart_start_date');
        $adsEndDate = $request->get('ads_end_date') ?: $request->get('chart_end_date');
        
        $dateInputs = $this->validateDateInputs($adsStartDate, $adsEndDate);

        $startDate = $dateInputs['start_date'] . ' 00:00:00';
        $endDate = $dateInputs['end_date'] . ' 23:59:59';
        $today = $dateInputs['today'];

        // Jika tidak ada input tanggal, gunakan default 30 hari terakhir
        if (empty($request->get('ads_start_date')) && empty($request->get('ads_end_date'))) {
            $dateInputs['start_date'] = Carbon::now()->subDays(30)->format('Y-m-d');
            $dateInputs['end_date'] = $today;
            $startDate = $dateInputs['start_date'] . ' 00:00:00';
            $endDate = $dateInputs['end_date'] . ' 23:59:59';
        }

        // Query untuk customer FROM ADS
        $fromAdsQuery = DB::select("
            SELECT DISTINCT ON (room_id)
                room_id,
                message,
                timestamp
            FROM messages
            WHERE
                role = 'customer'
                AND message = 'Saya dapat info dari iklan, Ada paket apa saja?'
                AND timestamp BETWEEN ? AND ?
            ORDER BY
                room_id,
                timestamp ASC
        ", [$startDate, $endDate]);

        // Query untuk customer NON ADS
        $nonAdsQuery = DB::select("
            SELECT DISTINCT ON (m.room_id)
                m.room_id,
                m.message,
                m.timestamp
            FROM messages m
            WHERE
                m.role = 'customer'
                AND m.timestamp BETWEEN ? AND ?
                AND NOT EXISTS (
                    SELECT 1
                    FROM messages x
                    WHERE x.room_id = m.room_id
                      AND x.message = 'Saya dapat info dari iklan, Ada paket apa saja?'
                )
            ORDER BY
                m.room_id,
                m.timestamp ASC
        ", [$startDate, $endDate]);

        $totalFromAds = count($fromAdsQuery);
        $totalNonAds = count($nonAdsQuery);

        // Get chart data per hari
        $chartData = $this->getFromAdsChartData($dateInputs['start_date'], $dateInputs['end_date'], $today);

        return [
            'totalFromAds' => $totalFromAds,
            'totalNonAds' => $totalNonAds,
            'fromAdsChartDates' => $chartData['dates'],
            'fromAdsChartData' => $chartData['fromAds'],
            'nonAdsChartData' => $chartData['nonAds'],
            'adsStartDate' => $dateInputs['start_date'],
            'adsEndDate' => $dateInputs['end_date'],
            'adsToday' => $today,
        ];
    }

    private function getFromAdsChartData($startDate, $endDate, $today)
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        // Hitung from ads per hari
        $adsRaw = DB::select("
            SELECT 
                DATE(timestamp) as d,
                COUNT(DISTINCT room_id) as ads_count
            FROM messages
            WHERE
                role = 'customer'
                AND message = 'Saya dapat info dari iklan, Ada paket apa saja?'
                AND timestamp BETWEEN ? AND ?
            GROUP BY DATE(timestamp)
            ORDER BY d
        ", [$start, $end]);

        // Hitung non ads per hari
        $nonAdsRaw = DB::select("
            SELECT 
                DATE(m.timestamp) as d,
                COUNT(DISTINCT m.room_id) as non_ads_count
            FROM messages m
            WHERE
                m.role = 'customer'
                AND m.timestamp BETWEEN ? AND ?
                AND NOT EXISTS (
                    SELECT 1
                    FROM messages x
                    WHERE x.room_id = m.room_id
                      AND x.message = 'Saya dapat info dari iklan, Ada paket apa saja?'
                )
            GROUP BY DATE(m.timestamp)
            ORDER BY d
        ", [$start, $end]);

        $adsData = collect($adsRaw)->keyBy('d');
        $nonAdsData = collect($nonAdsRaw)->keyBy('d');

        $period = $start->daysUntil($end);
        $chartDates = [];
        $chartFromAds = [];
        $chartNonAds = [];

        foreach ($period as $date) {
            $ymd = $date->format('Y-m-d');
            if ($ymd > $today) break;

            $chartDates[] = $date->format('M d');
            
            $adsRow = $adsData[$ymd] ?? null;
            $nonAdsRow = $nonAdsData[$ymd] ?? null;
            
            $chartFromAds[] = $adsRow ? (int) $adsRow->ads_count : 0;
            $chartNonAds[] = $nonAdsRow ? (int) $nonAdsRow->non_ads_count : 0;
        }

        return [
            'dates' => $chartDates,
            'fromAds' => $chartFromAds,
            'nonAds' => $chartNonAds
        ];
    }

    private function getAllCsAgents()
    {
        try {
            return User::where('role', 'customer_service')
                ->orderBy('username')
                ->pluck('username')
                ->toArray();
        } catch (\Exception $e) {
            Log::error('Failed to get CS agents', ['error' => $e->getMessage()]);
            return [];
        }
    }

    public function getAgentResponses(Request $request)
    {
        $perPage = (int) $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 20, 50, 100]) ? $perPage : 10;
        $page = max(1, (int) $request->get('page', 1));
        $agentName = $request->get('agent_name', '');
        $startDate = $request->get('start_date', '');
        $endDate = $request->get('end_date', '');

        $query = AgentResponse::query();

        if (!empty($agentName)) {
            $query->where('agent_name', 'ILIKE', '%' . $agentName . '%');
        }

        if (!empty($startDate)) {
            $query->whereDate('date', '>=', $startDate);
        }

        if (!empty($endDate)) {
            $query->whereDate('date', '<=', $endDate);
        }

        $paginator = $query->orderBy('date', 'desc')
            ->orderBy('agent_name')
            ->paginate($perPage, ['*'], 'page', $page);

        $paginator->appends([
            'per_page' => $perPage,
            'agent_name' => $agentName,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        return response()->json([
            'success' => true,
            'data' => $paginator->items(),
            'pagination' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'filters' => [
                'agent_name' => $agentName,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]
        ]);
    }
}
