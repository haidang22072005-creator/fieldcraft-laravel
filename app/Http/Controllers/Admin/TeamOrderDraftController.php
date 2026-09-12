<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TeamMember;
use App\Models\TeamOrderDraft;
use App\Models\TeamProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TeamOrderDraftController extends Controller
{
    public function showOrCreate(Request $request, TeamProfile $teamProfile): JsonResponse
    {
        return response()->json($this->draftResponse($this->persistDraft($request, $teamProfile)));
    }

    public function store(Request $request, TeamProfile $teamProfile): JsonResponse
    {
        $payload = $request->validate($this->rules());
        $draft = $this->persistDraft($request, $teamProfile, $payload['items'] ?? null);

        return response()->json($this->draftResponse($draft), $draft->wasRecentlyCreated ? 201 : 200);
    }

    public function show(TeamOrderDraft $teamOrderDraft): JsonResponse
    {
        return response()->json(['data' => $teamOrderDraft->load(['teamProfile', 'createdBy', 'items.teamMember', 'items.productVariant.product'])]);
    }

    public function update(Request $request, TeamOrderDraft $teamOrderDraft): JsonResponse
    {
        abort_unless($teamOrderDraft->status === 'active', 422, 'Đơn nháp không còn hoạt động.');
        $payload = $request->validate(array_merge($this->rules(), ['status' => ['sometimes', 'in:active,cancelled']]));

        DB::transaction(function () use ($teamOrderDraft, $payload): void {
            $draft = TeamOrderDraft::query()->lockForUpdate()->findOrFail($teamOrderDraft->id);
            if ($draft->status !== 'active') {
                throw ValidationException::withMessages(['draft' => 'Đơn nháp không còn hoạt động.']);
            }
            if (array_key_exists('items', $payload)) {
                $this->replaceItems($draft, $draft->team_profile_id, $payload['items']);
            }
            if (($payload['status'] ?? 'active') === 'cancelled') {
                $draft->update(['status' => 'cancelled', 'active_key' => null]);
            }
        });

        return response()->json(['data' => TeamOrderDraft::with(['teamProfile', 'createdBy', 'items.teamMember', 'items.productVariant.product'])->findOrFail($teamOrderDraft->id)]);
    }

    private function persistDraft(Request $request, TeamProfile $teamProfile, ?array $items = null): TeamOrderDraft
    {
        return DB::transaction(function () use ($request, $teamProfile, $items): TeamOrderDraft {
            $team = TeamProfile::query()->with('members')->lockForUpdate()->findOrFail($teamProfile->id);
            $draft = TeamOrderDraft::query()->active()->where('team_profile_id', $team->id)->lockForUpdate()->first();

            if (! $draft) {
                $members = $team->members->map(fn (TeamMember $member): array => $this->memberSnapshot($member))->values()->all();
                $draft = TeamOrderDraft::query()->create([
                    'team_profile_id' => $team->id,
                    'created_by' => $request->user()->id,
                    'status' => 'active',
                    'active_key' => 'team-'.$team->id,
                    'roster_snapshot' => [
                        'team_profile_id' => $team->id,
                        'team_name' => $team->team_name,
                        'owner_id' => $team->user_id,
                        'captain' => $team->captain,
                        'contact' => $team->contact,
                        'phone' => $team->phone,
                        'members' => $members,
                    ],
                ]);
                foreach ($members as $member) {
                    $draft->items()->create($this->itemAttributes($member));
                }
            } elseif ($items !== null) {
                $this->replaceItems($draft, $team->id, $items);
            }

            return $draft->load(['teamProfile', 'createdBy', 'items.teamMember', 'items.productVariant.product']);
        });
    }

    private function replaceItems(TeamOrderDraft $draft, int $teamProfileId, array $items): void
    {
        $draft->items()->delete();
        foreach ($items as $item) {
            $member = null;
            if (! empty($item['team_member_id'])) {
                $member = TeamMember::query()->where('team_profile_id', $teamProfileId)->findOrFail($item['team_member_id']);
            }
            $attributes = [
                'team_member_id' => $member?->id,
                'product_variant_id' => $item['product_variant_id'] ?? null,
                'player_name' => $item['player_name'] ?? $member?->player_name,
                'shirt_name' => array_key_exists('shirt_name', $item) ? $item['shirt_name'] : $member?->shirt_name,
                'shirt_number' => array_key_exists('shirt_number', $item) ? $item['shirt_number'] : $member?->shirt_number,
                'shirt_size' => array_key_exists('shirt_size', $item) ? $item['shirt_size'] : $member?->shirt_size,
                'quantity' => $item['quantity'] ?? 1,
                'customization' => $item['customization'] ?? null,
                'notes' => array_key_exists('notes', $item) ? $item['notes'] : $member?->notes,
            ];
            if (blank($attributes['player_name'])) {
                throw ValidationException::withMessages(['items' => 'Mỗi dòng nháp phải có tên cầu thủ.']);
            }
            $draft->items()->create($attributes);
        }
    }

    private function rules(): array
    {
        return [
            'items' => ['sometimes', 'array'],
            'items.*.team_member_id' => ['nullable', 'integer', 'exists:team_members,id'],
            'items.*.product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'items.*.player_name' => ['nullable', 'string', 'max:100'],
            'items.*.shirt_name' => ['nullable', 'string', 'max:30'],
            'items.*.shirt_number' => ['nullable', 'integer', 'min:0', 'max:999'],
            'items.*.shirt_size' => ['nullable', 'string', 'max:20'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'items.*.customization' => ['nullable', 'array'],
            'items.*.notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    private function memberSnapshot(TeamMember $member): array
    {
        return [
            'team_member_id' => $member->id,
            'player_name' => $member->player_name,
            'shirt_name' => $member->shirt_name,
            'shirt_number' => $member->shirt_number,
            'shirt_size' => $member->shirt_size,
            'notes' => $member->notes,
        ];
    }

    private function itemAttributes(array $member): array
    {
        return [
            'team_member_id' => $member['team_member_id'],
            'player_name' => $member['player_name'],
            'shirt_name' => $member['shirt_name'],
            'shirt_number' => $member['shirt_number'],
            'shirt_size' => $member['shirt_size'],
            'quantity' => 1,
            'notes' => $member['notes'],
        ];
    }

    private function draftResponse(TeamOrderDraft $draft): array
    {
        return [
            'data' => $draft,
            'draft' => $draft,
            'team_order_draft_id' => $draft->id,
            'team_profile_id' => $draft->team_profile_id,
            'status' => $draft->status,
            // Keep the existing dashboard action's response contract while
            // exposing the persisted draft and its item rows to new clients.
            'members' => $draft->items,
        ];
    }
}
