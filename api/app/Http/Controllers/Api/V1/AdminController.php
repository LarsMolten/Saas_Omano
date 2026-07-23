<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Report;
use App\Models\Review;
use App\Models\User;
use App\Services\AdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    public function __construct(
        private AdminService $adminService
    ) {}

    // ── Users ──

    public function users(Request $request): JsonResponse
    {
        $query = User::query()
            ->select('id', 'name', 'email', 'role', 'status', 'created_at')
            ->orderByDesc('created_at');

        if ($role = $request->query('role')) {
            $query->where('role', $role);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%");
            });
        }

        return response()->json($query->paginate($request->input('per_page', 20)));
    }

    public function updateUser(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'status' => 'sometimes|in:active,suspended,banned',
            'role' => 'sometimes|in:client,prestataire,admin',
        ]);

        $user = User::findOrFail($validated['user_id']);
        $admin = $request->user();
        $changes = [];

        if (isset($validated['status']) && $validated['status'] !== $user->status) {
            $changes['status'] = ['old' => $user->status, 'new' => $validated['status']];
            $user->status = $validated['status'];
        }

        if (isset($validated['role']) && $validated['role'] !== $user->role) {
            $changes['role'] = ['old' => $user->role, 'new' => $validated['role']];
            $user->role = $validated['role'];
        }

        if (empty($changes)) {
            return response()->json(['message' => 'Aucune modification.'], 422);
        }

        $user->save();

        $this->adminService->logAction(
            adminId: $admin->id,
            action: 'user.update',
            targetType: 'user',
            targetId: $user->id,
            reason: $request->input('reason'),
            metadata: $changes,
        );

        return response()->json($user->only('id', 'name', 'email', 'role', 'status'));
    }

    // ── Categories ──

    public function categories(): JsonResponse
    {
        return response()->json(
            Category::orderBy('sort_order')->orderBy('name')->get()
        );
    }

    public function storeCategory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'icon' => 'nullable|string|max:50',
            'sort_order' => 'nullable|integer|min:0',
            'active' => 'nullable|boolean',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        if (Category::where('slug', $validated['slug'])->exists()) {
            return response()->json(['message' => 'Une catégorie avec ce nom existe déjà.'], 422);
        }

        $category = Category::create($validated);

        $this->adminService->logAction(
            adminId: $request->user()->id,
            action: 'category.create',
            targetType: 'category',
            targetId: $category->id,
        );

        return response()->json($category, 201);
    }

    public function updateCategory(Request $request, string $id): JsonResponse
    {
        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:100',
            'icon' => 'nullable|string|max:50',
            'sort_order' => 'nullable|integer|min:0',
            'active' => 'nullable|boolean',
        ]);

        if (isset($validated['name'])) {
            $validated['slug'] = Str::slug($validated['name']);
            $exists = Category::where('slug', $validated['slug'])
                ->where('id', '!=', $category->id)
                ->exists();
            if ($exists) {
                return response()->json(['message' => 'Une catégorie avec ce nom existe déjà.'], 422);
            }
        }

        $category->update($validated);

        $this->adminService->logAction(
            adminId: $request->user()->id,
            action: 'category.update',
            targetType: 'category',
            targetId: $category->id,
        );

        return response()->json($category);
    }

    public function destroyCategory(Request $request, string $id): JsonResponse
    {
        $category = Category::findOrFail($id);
        $category->delete();

        $this->adminService->logAction(
            adminId: $request->user()->id,
            action: 'category.delete',
            targetType: 'category',
            targetId: (int) $id,
        );

        return response()->json(['message' => 'Catégorie supprimée.']);
    }

    // ── Subscriptions ──

    public function subscriptions(Request $request): JsonResponse
    {
        $query = DB::table('subscriptions')
            ->join('users', 'users.id', '=', 'subscriptions.provider_id')
            ->select(
                'subscriptions.*',
                'users.name as provider_name',
                'users.email as provider_email',
            )
            ->orderByDesc('subscriptions.created_at');

        if ($plan = $request->query('plan')) {
            $query->where('subscriptions.plan', $plan);
        }

        if ($status = $request->query('status')) {
            $query->where('subscriptions.status', $status);
        }

        return response()->json($query->paginate($request->input('per_page', 20)));
    }

    public function updateSubscription(Request $request, string $id): JsonResponse
    {
        $subscription = DB::table('subscriptions')->where('id', $id)->first();

        if (!$subscription) {
            return response()->json(['message' => 'Abonnement introuvable.'], 404);
        }

        $validated = $request->validate([
            'status' => 'sometimes|in:active,expired,cancelled',
            'plan' => 'sometimes|in:starter,pro,premium',
        ]);

        DB::table('subscriptions')->where('id', $id)->update($validated);

        $this->adminService->logAction(
            adminId: $request->user()->id,
            action: 'subscription.update',
            targetType: 'subscription',
            targetId: (int) $id,
            metadata: $validated,
        );

        return response()->json(['message' => 'Abonnement mis à jour.']);
    }

    // ── Reports ──

    public function reports(Request $request): JsonResponse
    {
        $query = Report::with(['reporter:id,name', 'resolver:id,name'])
            ->orderByDesc('created_at');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        } else {
            $query->where('status', 'pending');
        }

        if ($type = $request->query('type')) {
            $query->where('reportable_type', $type);
        }

        return response()->json($query->paginate($request->input('per_page', 20)));
    }

    public function resolveReport(Request $request, string $id): JsonResponse
    {
        $report = Report::findOrFail($id);

        if ($report->status !== 'pending') {
            return response()->json(['message' => 'Ce signalement a déjà été traité.'], 422);
        }

        $validated = $request->validate([
            'action' => 'required|in:dismiss,content_deleted,sanction',
            'resolution_note' => 'nullable|string|max:1000',
        ]);

        $admin = $request->user();

        DB::beginTransaction();

        try {
            $report->update([
                'status' => $validated['action'] === 'dismiss' ? 'dismissed' : 'sanctioned',
                'resolved_by' => $admin->id,
                'resolved_at' => now(),
                'resolution_note' => $validated['resolution_note'] ?? null,
            ]);

            if ($validated['action'] === 'content_deleted') {
                $this->deleteReportedContent($report);
            }

            if ($validated['action'] === 'sanction') {
                $this->sanctionTarget($report);
            }

            $this->adminService->logAction(
                adminId: $admin->id,
                action: "report.{$validated['action']}",
                targetType: $report->reportable_type,
                targetId: $report->reportable_id,
                reason: $validated['resolution_note'] ?? null,
                metadata: ['report_id' => $report->id],
            );

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return response()->json(['message' => 'Signalement traité.']);
    }

    private function deleteReportedContent(Report $report): void
    {
        if ($report->reportable_type === 'review') {
            Review::where('id', $report->reportable_id)->delete();
        }
    }

    private function sanctionTarget(Report $report): void
    {
        if ($report->reportable_type === 'review') {
            $review = Review::find($report->reportable_id);
            if ($review) {
                User::where('id', $review->provider_id)->update(['status' => 'suspended']);
            }
        } elseif ($report->reportable_type === 'user') {
            User::where('id', $report->reportable_id)->update(['status' => 'suspended']);
        }
    }

    // ── Stats ──

    public function stats(): JsonResponse
    {
        return response()->json($this->adminService->getGlobalStats());
    }
}
