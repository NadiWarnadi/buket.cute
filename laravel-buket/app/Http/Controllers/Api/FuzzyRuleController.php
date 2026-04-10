<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFuzzyRuleRequest;
use App\Http\Requests\UpdateFuzzyRuleRequest;
use App\Models\FuzzyRule;
use App\Services\FuzzyBotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class FuzzyRuleController extends Controller
{
    protected FuzzyBotService $fuzzyBotService;

    public function __construct(FuzzyBotService $fuzzyBotService)
    {
        $this->fuzzyBotService = $fuzzyBotService;
    }

    /**
     * Get all fuzzy rules
     * GET /api/fuzzy-rules
     */
    public function index(Request $request)
    {
        try {
            $query = FuzzyRule::query();

            // Filter by intent
            if ($request->has('intent')) {
                $query->where('intent', $request->intent);
            }

            // Filter by is_active
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            // Sorting
            $sortBy = $request->input('sort_by', 'created_at');
            $sortOrder = $request->input('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            
            $rules = $query->paginate($request->input('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $rules->items(),
                'pagination' => [
                    'total' => $rules->total(),
                    'per_page' => $rules->perPage(),
                    'current_page' => $rules->currentPage(),
                    'last_page' => $rules->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching fuzzy rules', ['error' => $e->getMessage()]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Create new fuzzy rule
     * POST /api/fuzzy-rules
     */
    public function store(StoreFuzzyRuleRequest $request)
    {
        try {
            $validated = $request->validated();

            // Test the pattern to ensure it's valid
            $this->validatePattern($validated['pattern']);

            $rule = FuzzyRule::create([
                ...$validated,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            Log::channel('whatsapp')->info('Fuzzy rule created', [
                'rule_id' => $rule->id,
                'intent' => $rule->intent,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Fuzzy rule created successfully',
                'data' => $rule,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Error creating fuzzy rule', ['error' => $e->getMessage()]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get single fuzzy rule
     * GET /api/fuzzy-rules/{id}
     */
    public function show(FuzzyRule $fuzzyRule)
    {
        return response()->json([
            'success' => true,
            'data' => $fuzzyRule,
        ]);
    }

    /**
     * Update fuzzy rule
     * PUT /api/fuzzy-rules/{id}
     */
    public function update(UpdateFuzzyRuleRequest $request, FuzzyRule $fuzzyRule)
    {
        try {
            $validated = $request->validated();

            // Test the pattern if it's being updated
            if (isset($validated['pattern'])) {
                $this->validatePattern($validated['pattern']);
            }

            $fuzzyRule->update($validated);

            Log::channel('whatsapp')->info('Fuzzy rule updated', [
                'rule_id' => $fuzzyRule->id,
                'intent' => $fuzzyRule->intent,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Fuzzy rule updated successfully',
                'data' => $fuzzyRule,
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Error updating fuzzy rule', ['error' => $e->getMessage()]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete fuzzy rule
     * DELETE /api/fuzzy-rules/{id}
     */
    public function destroy(FuzzyRule $fuzzyRule)
    {
        try {
            $fuzzyRule->delete();

            Log::channel('whatsapp')->info('Fuzzy rule deleted', [
                'rule_id' => $fuzzyRule->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Fuzzy rule deleted successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting fuzzy rule', ['error' => $e->getMessage()]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    /**
     * Test fuzzy bot with a message
     * POST /api/fuzzy-rules/test
     */
    public function testMessage(Request $request)
    {
        try {
            $validated = $request->validate([
                'message' => 'required|string',
                'context' => 'nullable|string',
            ]);

            $message = $validated['message'];
            $context = $request->input('context');

            // PERBAIKAN 1: Tampung hasil service ke dalam variabel $result
            $result = $this->fuzzyBotService->processMessageWithContext($message, $context);

            return response()->json([
                'success' => true,
                // PERBAIKAN 2: Susunan array input yang benar
                'input' => [
                    'message' => $message,
                    'context' => $context
                ],
                'result' => $result,
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Error testing fuzzy bot', ['error' => $e->getMessage()]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get rule statistics
     * GET /api/fuzzy-rules/stats
     */
    public function stats()
    {
        try {
            $totalRules = FuzzyRule::count();
            $activeRules = FuzzyRule::where('is_active', true)->count();
            $intents = FuzzyRule::distinct('intent')->count();
            $actions = FuzzyRule::distinct('action')->count();

            $byIntent = FuzzyRule::selectRaw('intent, count(*) as count')
                ->groupBy('intent')
                ->get();

            $byAction = FuzzyRule::selectRaw('action, count(*) as count')
                ->groupBy('action')
                ->get();

            return response()->json([
                'success' => true,
                'stats' => [
                    'total_rules' => $totalRules,
                    'active_rules' => $activeRules,
                    'inactive_rules' => $totalRules - $activeRules,
                    'total_intents' => $intents,
                    'total_actions' => $actions,
                    'rules_by_intent' => $byIntent,
                    'rules_by_action' => $byAction,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting fuzzy rule stats', ['error' => $e->getMessage()]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Validate pattern syntax
     */
    private function validatePattern(string $pattern): void
    {
        $parts = array_map('trim', explode('|', $pattern));

        foreach ($parts as $part) {
            if (empty($part)) {
                continue;
            }

            // Check if it's a regex
            if (preg_match('~^/(.+)/([imsxADSUXJu]*)$~', $part, $matches)) {
                try {
                    // Test regex
                    @preg_match('~'.$matches[1].'~'.($matches[2] ?? ''), '');
                } catch (\Exception $e) {
                    throw new \Exception("Invalid regex pattern: {$part}");
                }
            }
        }
    }

    /**
     * Bulk create/import fuzzy rules
     * POST /api/fuzzy-rules/import
     */
    public function import(Request $request)
    {
        try {
            $validated = $request->validate([
                'rules' => 'required|array|min:1',
                'rules.*.intent' => 'required|string|max:100',
                'rules.*.pattern' => 'required|string',
                'rules.*.confidence_threshold' => 'required|numeric|min:0|max:1',
                'rules.*.action' => 'required|string|max:100',
                'rules.*.response_template' => 'nullable|string',
            ]);

            $created = [];
            $errors = [];

            foreach ($validated['rules'] as $index => $ruleData) {
                try {
                    // Validate pattern
                    $this->validatePattern($ruleData['pattern']);

                    $rule = FuzzyRule::create([
                        ...$ruleData,
                        'is_active' => true,
                    ]);

                    $created[] = $rule;
                } catch (\Exception $e) {
                    $errors[] = [
                        'index' => $index,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            Log::channel('whatsapp')->info('Fuzzy rules imported', [
                'created' => count($created),
                'errors' => count($errors),
            ]);

            return response()->json([
                'success' => count($created) > 0,
                'created' => count($created),
                'errors' => count($errors),
                'error_details' => $errors,
                'data' => $created,
            ], count($errors) === 0 ? 201 : 207);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Error importing fuzzy rules', ['error' => $e->getMessage()]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
