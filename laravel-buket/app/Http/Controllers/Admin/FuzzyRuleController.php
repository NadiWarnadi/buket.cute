<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FuzzyRule;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class FuzzyRuleController extends Controller
{
    /**
     * Display a listing of fuzzy rules.
     */
    public function index(Request $request): View
    {
        $query = FuzzyRule::query();

        // Search
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('intent', 'like', "%{$search}%")
                ->orWhere('pattern', 'like', "%{$search}%")
                ->orWhere('action', 'like', "%{$search}%");
        }

        // Filter by active status
        if ($request->filled('status')) {
            $status = $request->input('status');
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Sort
        $sortBy = $request->input('sort', 'created_at');
        $sortOrder = $request->input('order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $rules = $query->paginate(15);

        return view('admin.fuzzy-rules.index', compact('rules'));
    }

    /**
     * Show the form for creating a new fuzzy rule.
     */
    public function create(): View
    {
        $actions = [
            'reply' => 'Kirim Balasan Otomatis',
            'escalate' => 'Eskalasi ke Admin',
            'manual_review' => 'Memerlukan Review Manual',
            'order' => 'Proses Pesanan',
            'category' => 'Kategorisasi Pesan',
            'pending' => 'Tandai Pending',
        ];

        return view('admin.fuzzy-rules.create', compact('actions'));
    }

    /**
     * Store a newly created fuzzy rule in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'intent' => 'required|string|max:100|unique:fuzzy_rules,intent',
            'pattern' => 'required|string',
            'confidence_threshold' => 'required|numeric|min:0|max:1',
            'action' => 'required|string|max:100',
            'response_template' => 'nullable|string',
            'is_active' => 'boolean',
        ], [
            'intent.required' => 'Nama intent harus diisi',
            'intent.unique' => 'Nama intent sudah ada',
            'pattern.required' => 'Pattern harus diisi',
            'confidence_threshold.required' => 'Confidence threshold harus diisi',
            'confidence_threshold.numeric' => 'Confidence threshold harus berupa angka',
            'confidence_threshold.min' => 'Confidence threshold minimal 0',
            'confidence_threshold.max' => 'Confidence threshold maksimal 1',
            'action.required' => 'Aksi harus dipilih',
        ]);

        FuzzyRule::create($validated);

        return redirect()->route('admin.fuzzy-rules.index')
            ->with('success', 'Fuzzy rule berhasil dibuat');
    }

    /**
     * Show the form for editing the specified fuzzy rule.
     */
    public function edit(FuzzyRule $fuzzyRule): View
    {
        $actions = [
            'reply' => 'Kirim Balasan Otomatis',
            'escalate' => 'Eskalasi ke Admin',
            'manual_review' => 'Memerlukan Review Manual',
            'order' => 'Proses Pesanan',
            'category' => 'Kategorisasi Pesan',
            'pending' => 'Tandai Pending',
        ];

        return view('admin.fuzzy-rules.edit', compact('fuzzyRule', 'actions'));
    }

    /**
     * Update the specified fuzzy rule in storage.
     */
    public function update(Request $request, FuzzyRule $fuzzyRule): RedirectResponse
    {
        $validated = $request->validate([
            'intent' => 'required|string|max:100|unique:fuzzy_rules,intent,' . $fuzzyRule->id,
            'pattern' => 'required|string',
            'confidence_threshold' => 'required|numeric|min:0|max:1',
            'action' => 'required|string|max:100',
            'response_template' => 'nullable|string',
            'is_active' => 'boolean',
        ], [
            'intent.required' => 'Nama intent harus diisi',
            'intent.unique' => 'Nama intent sudah ada',
            'pattern.required' => 'Pattern harus diisi',
            'confidence_threshold.required' => 'Confidence threshold harus diisi',
            'confidence_threshold.numeric' => 'Confidence threshold harus berupa angka',
            'confidence_threshold.min' => 'Confidence threshold minimal 0',
            'confidence_threshold.max' => 'Confidence threshold maksimal 1',
            'action.required' => 'Aksi harus dipilih',
        ]);

        $fuzzyRule->update($validated);

        return redirect()->route('admin.fuzzy-rules.index')
            ->with('success', 'Fuzzy rule berhasil diperbarui');
    }

    /**
     * Remove the specified fuzzy rule from storage.
     */
    public function destroy(FuzzyRule $fuzzyRule): RedirectResponse
    {
        $fuzzyRule->delete();

        return redirect()->route('admin.fuzzy-rules.index')
            ->with('success', 'Fuzzy rule berhasil dihapus');
    }

    /**
     * Toggle the active status of a fuzzy rule.
     */
    public function toggle(FuzzyRule $fuzzyRule): RedirectResponse
    {
        $fuzzyRule->update([
            'is_active' => !$fuzzyRule->is_active,
        ]);

        $status = $fuzzyRule->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Fuzzy rule berhasil {$status}");
    }

    /**
     * Show details of a fuzzy rule
     */
    public function show(FuzzyRule $fuzzyRule): View
    {
        return view('admin.fuzzy-rules.show', compact('fuzzyRule'));
    }

    /**
     * Test a pattern against a message
     */
    public function testPattern(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'pattern' => 'required|string',
            'test_message' => 'required|string',
        ]);

        try {
            $pattern = $request->input('pattern');
            $testMessage = $request->input('test_message');

            // Parse and test pattern
            $patterns = $this->parsePatterns($pattern);
            $matched = false;

            // Test keywords
            foreach ($patterns['keywords'] as $keyword) {
                if (stripos(strtolower($testMessage), strtolower($keyword)) !== false) {
                    $matched = true;
                    break;
                }
            }

            // Test regex if no keyword match
            if (!$matched) {
                foreach ($patterns['regex'] as $regex) {
                    try {
                        if (preg_match($regex, $testMessage)) {
                            $matched = true;
                            break;
                        }
                    } catch (\Exception $e) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Error pada regex: ' . $e->getMessage(),
                        ]);
                    }
                }
            }

            return response()->json([
                'success' => true,
                'matched' => $matched,
                'keywords' => $patterns['keywords'],
                'regex' => $patterns['regex'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Parse pattern string into keywords and regex patterns
     */
    private function parsePatterns(string $patternString): array
    {
        $keywords = [];
        $regex = [];

        $parts = array_map('trim', explode('|', $patternString));

        foreach ($parts as $part) {
            if (!empty($part)) {
                if (preg_match('~^/(.+)/([imsxADSUXJu]*)$~', $part, $matches)) {
                    $regex[] = '~' . $matches[1] . '~' . ($matches[2] ?? '');
                } else {
                    $keywords[] = $part;
                }
            }
        }

        return ['keywords' => $keywords, 'regex' => $regex];
    }

    /**
     * Import fuzzy rules from JSON
     */
    public function importForm(): View
    {
        return view('admin.fuzzy-rules.import');
    }

    /**
     * Handle import
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:json',
        ]);

        try {
            $content = file_get_contents($request->file('file')->getRealPath());
            $rules = json_decode($content, true);

            if (!is_array($rules)) {
                return back()->with('error', 'Format JSON tidak valid');
            }

            $imported = 0;
            foreach ($rules as $rule) {
                FuzzyRule::create([
                    'intent' => $rule['intent'] ?? '',
                    'pattern' => $rule['pattern'] ?? '',
                    'confidence_threshold' => $rule['confidence_threshold'] ?? 0.5,
                    'action' => $rule['action'] ?? 'reply',
                    'response_template' => $rule['response_template'] ?? null,
                    'is_active' => $rule['is_active'] ?? true,
                ]);
                $imported++;
            }

            return redirect()->route('admin.fuzzy-rules.index')
                ->with('success', "{$imported} fuzzy rules berhasil diimpor");
        } catch (\Exception $e) {
            return back()->with('error', 'Error saat import: ' . $e->getMessage());
        }
    }

    /**
     * Export fuzzy rules as JSON
     */
    public function export(): \Symfony\Component\HttpFoundation\Response
    {
        $rules = FuzzyRule::all()->toArray();

        return response()->json($rules, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="fuzzy-rules-' . date('Y-m-d-His') . '.json"',
        ]);
    }
}
