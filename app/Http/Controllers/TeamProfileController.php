<?php

namespace App\Http\Controllers;

use App\Models\TeamMember;
use App\Models\TeamProfile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TeamProfileController extends Controller
{
    public function index(Request $request): JsonResponse
    { $teams = in_array($request->user()->role, ['admin', 'super-admin'], true) ? TeamProfile::with('members')->latest()->get() : $request->user()->teamProfiles()->with('members')->latest()->get(); return response()->json(['data' => $teams]); }

    public function show(Request $request, TeamProfile $teamProfile): JsonResponse
    { $this->authorize($request, $teamProfile); return response()->json(['data' => $teamProfile->load('members')]); }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['user_id' => ['nullable', 'integer', 'exists:users,id'], 'team_name' => ['required', 'string', 'max:150'], 'logo' => ['nullable', 'string', 'max:255'], 'captain' => ['nullable', 'string', 'max:100'], 'contact' => ['nullable', 'string', 'max:100'], 'phone' => ['nullable', 'string', 'max:25'], 'notes' => ['nullable', 'string', 'max:5000']]);
        $isAdmin = in_array($request->user()->role, ['admin', 'super-admin'], true);
        if ($isAdmin && empty($data['user_id'])) throw ValidationException::withMessages(['user_id' => 'Cần chọn khách hàng cho hồ sơ đội bóng.']);
        $owner = $isAdmin ? User::findOrFail($data['user_id']) : $request->user();
        abort_unless($owner->role === 'customer', 422, 'Hồ sơ đội bóng chỉ thuộc về khách hàng.'); unset($data['user_id']);
        $team = $owner->teamProfiles()->create($data);
        return response()->json(['data' => $team->load('members')], 201);
    }

    public function update(Request $request, TeamProfile $teamProfile): JsonResponse
    { $this->authorize($request, $teamProfile); $teamProfile->update($request->validate(['team_name' => ['sometimes', 'required', 'string', 'max:150'], 'logo' => ['nullable', 'string', 'max:255'], 'captain' => ['nullable', 'string', 'max:100'], 'contact' => ['nullable', 'string', 'max:100'], 'phone' => ['nullable', 'string', 'max:25'], 'notes' => ['nullable', 'string', 'max:5000']])); return response()->json(['data' => $teamProfile->fresh('members')]); }

    public function storeMember(Request $request, TeamProfile $teamProfile): JsonResponse
    { $this->authorize($request, $teamProfile); $member = $teamProfile->members()->create($request->validate(['player_name' => ['required', 'string', 'max:100'], 'shirt_name' => ['nullable', 'string', 'max:30'], 'shirt_number' => ['nullable', 'integer', 'min:0', 'max:99'], 'shirt_size' => ['nullable', 'string', 'max:20'], 'notes' => ['nullable', 'string', 'max:1000']])); return response()->json(['data' => $member], 201); }

    public function updateMember(Request $request, TeamProfile $teamProfile, TeamMember $teamMember): JsonResponse
    { $this->authorize($request, $teamProfile); abort_unless($teamMember->team_profile_id === $teamProfile->id, 404); $teamMember->update($request->validate(['player_name' => ['sometimes', 'required', 'string', 'max:100'], 'shirt_name' => ['nullable', 'string', 'max:30'], 'shirt_number' => ['nullable', 'integer', 'min:0', 'max:99'], 'shirt_size' => ['nullable', 'string', 'max:20'], 'notes' => ['nullable', 'string', 'max:1000']])); return response()->json(['data' => $teamMember->fresh()]); }

    public function draftReorder(Request $request, TeamProfile $teamProfile): JsonResponse
    { $this->authorize($request, $teamProfile); return response()->json(['team_profile_id' => $teamProfile->id, 'status' => 'draft', 'members' => $teamProfile->load('members')->members]); }

    private function authorize(Request $request, TeamProfile $teamProfile): void
    { abort_unless($request->user()->role === 'super-admin' || $request->user()->role === 'admin' || (int) $teamProfile->user_id === (int) $request->user()->id, 403); }
}
