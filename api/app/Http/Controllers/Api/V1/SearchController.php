<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
            'radius' => 'nullable|numeric|min:1|max:500',
            'price_min' => 'nullable|numeric|min:0',
            'price_max' => 'nullable|numeric|min:0',
            'rating_min' => 'nullable|numeric|min:0|max:5',
            'verified' => 'nullable|string|in:true,false,0,1',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:50',
        ]);

        $query = User::query()
            ->where('role', 'prestataire')
            ->with('services')
            ->select('users.*')
            ->leftJoin('subscriptions', function ($join) {
                $join->on('users.id', '=', 'subscriptions.provider_id')
                    ->where('subscriptions.status', '=', 'active')
                    ->where('subscriptions.ends_at', '>', now());
            })
            ->addSelect(DB::raw(
                "CASE WHEN subscriptions.plan = 'premium' THEN 1 ELSE 0 END AS is_premium"
            ));

        $hasGeoSearch = !empty($validated['lat']) && !empty($validated['lng']);
        $radius = $validated['radius'] ?? 25;

        if ($hasGeoSearch) {
            $lat = (float) $validated['lat'];
            $lng = (float) $validated['lng'];

            $haversine = "(6371 * acos(
                cos(radians(?)) * cos(radians(latitude)) *
                cos(radians(longitude) - radians(?)) +
                sin(radians(?)) * sin(radians(latitude))
            ))";

            $query->selectRaw("users.*, {$haversine} AS distance_km", [$lat, $lng, $lat])
                ->whereRaw("{$haversine} <= ?", [$lat, $lng, $lat, $radius])
                ->orderBy('distance_km');
        }

        if (!empty($validated['q'])) {
            $searchTerm = $validated['q'];
            $tsQuery = $this->buildTsQuery($searchTerm);

            $query->whereRaw(
                "search_vector @@ to_tsquery('simple', ?)",
                [$tsQuery]
            );

            $query->orderByRaw(
                "is_premium DESC, ts_rank(search_vector, to_tsquery('simple', ?)) DESC",
                [$tsQuery]
            );
        } elseif (!$hasGeoSearch) {
            $query->orderByRaw('is_premium DESC, average_rating DESC');
        }

        if (!empty($validated['category'])) {
            $query->whereRaw(
                "category ILIKE ?",
                ["%{$validated['category']}%"]
            );
        }

        if (!empty($validated['city'])) {
            $query->whereRaw(
                "city ILIKE ?",
                ["%{$validated['city']}%"]
            );
        }

        if (isset($validated['rating_min'])) {
            $query->where('average_rating', '>=', $validated['rating_min']);
        }

        if (isset($validated['verified'])) {
            if (in_array($validated['verified'], ['true', '1'], true)) {
                $query->whereNotNull('email_verified_at');
            } else {
                $query->whereNull('email_verified_at');
            }
        }

        if (isset($validated['price_min']) || isset($validated['price_max'])) {
            $query->whereHas('services', function ($q) use ($validated) {
                if (isset($validated['price_min'])) {
                    $q->where('price', '>=', $validated['price_min']);
                }
                if (isset($validated['price_max'])) {
                    $q->where('price', '<=', $validated['price_max']);
                }
            });
        }

        $perPage = $validated['per_page'] ?? 20;
        $results = $query->paginate($perPage);

        return response()->json([
            'data' => $results->items(),
            'meta' => [
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total(),
            ],
        ]);
    }

    private function buildTsQuery(string $term): string
    {
        $cleaned = preg_replace('/[^\w\s]/u', '', trim($term));
        $words = preg_split('/\s+/u', $cleaned, -1, PREG_SPLIT_NO_EMPTY);

        if (empty($words)) {
            return '';
        }

        $parts = array_map(function (string $word) {
            return preg_match('/^\d+$/', $word) ? $word : $word . ':*';
        }, $words);

        return implode(' & ', $parts);
    }
}
