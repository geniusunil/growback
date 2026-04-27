<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Activity;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\Validator;

class ActivityController extends Controller
{
    /**
     * Display a listing of activities for a user/guest.
     */
    public function index(Request $request)
    {
        try {
            $user_id = $request->query('user_id');
            $guest_id = $request->query('guest_id');

            if (!$user_id && !$guest_id) {
                return response()->json(['success' => false, 'message' => 'Missing ID'], 400);
            }

            $activities = Activity::query()
                ->when($user_id, fn($q) => $q->where('user_id', $user_id))
                ->when($guest_id, fn($q) => $q->where('guest_id', $guest_id))
                ->orderBy('updated_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'activities' => $activities
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created activity in storage.
     */
    public function store(Request $request)
    {
        Log::info('Activity store request arrived', $request->all());
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'nullable|exists:users,id',
                'guest_id' => 'nullable|string',
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'category' => 'nullable|string',
                'reminder_times' => 'nullable|array',
                'frequency_unit' => 'nullable|string|in:minutes,hours,days,weeks,months,years',
                'frequency_value' => 'nullable|integer|min:1',
                'reminder_sound' => 'nullable|string|in:continuous,small,none',
                'reminder_vibration' => 'nullable|boolean',
                'show_in_drawer' => 'nullable|boolean',
                'notification_sound' => 'nullable|boolean',
                'notification_vibration' => 'nullable|boolean',
                'show_full_screen' => 'nullable|boolean',
                'custom_sound_path' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Check error message above',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Create activity with all validated data
            $activity = Activity::create($validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Activity created successfully',
                'activity' => $activity
            ], 201);

        } catch (\Exception $e) {
            Log::error('Activity store error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create activity: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update an existing activity.
     */
    public function update(Request $request, $id)
    {
        try {
            $activity = Activity::find($id);
            if (!$activity) {
                return response()->json(['success' => false, 'message' => 'Activity not found'], 404);
            }

            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'category' => 'required|string',
                'reminder_times' => 'array',
                'frequency_unit' => 'required|string|in:minutes,hours,days,weeks,months,years',
                'frequency_value' => 'required|integer|min:1',
                'reminder_sound' => 'required|string|in:continuous,small,none',
                'reminder_vibration' => 'required|boolean',
                'show_in_drawer' => 'required|boolean',
                'notification_sound' => 'required|boolean',
                'notification_vibration' => 'required|boolean',
                'show_full_screen' => 'required|boolean',
                'custom_sound_path' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $activity->update($validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Activity updated successfully',
                'activity' => $activity->fresh()
            ]);

        } catch (\Exception $e) {
            Log::error('Activity update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update activity: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Soft-delete an activity (moves to trash).
     */
public function destroy($id)
{
     Log::info("DELETE_REQUEST_ID: $id");
    try {
        $activity = Activity::find($id);

        if (!$activity) {
            return response()->json([
                'success' => false,
                'message' => 'Activity not found'
            ], 404);
        }

        // 🔥 Force set deleted_at (backup safe method)
        $activity->deleted_at = now();
        $activity->save();

        return response()->json([
            'success' => true,
            'message' => 'Activity moved to trash',
            'deleted_at' => $activity->deleted_at
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to delete activity: ' . $e->getMessage()
        ], 500);
    }
}

    /**
     * List soft-deleted (trashed) activities.
     */
    public function trash(Request $request)
    {
        try {
            $user_id = $request->query('user_id');
            $guest_id = $request->query('guest_id');

            if (!$user_id && !$guest_id) {
                return response()->json(['success' => false, 'message' => 'Missing ID'], 400);
            }

            $activities = Activity::onlyTrashed()
                ->when($user_id, fn($q) => $q->where('user_id', $user_id))
                ->when($guest_id, fn($q) => $q->where('guest_id', $guest_id))
                ->orderBy('deleted_at', 'desc')
                ->get();

            return response()->json(['success' => true, 'activities' => $activities]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Restore a trashed activity.
     */
    public function restore($id)
    {
        try {
            $activity = Activity::onlyTrashed()->find($id);
            if (!$activity) {
                return response()->json(['success' => false, 'message' => 'Activity not found in trash'], 404);
            }

            $activity->restore();

            return response()->json(['success' => true, 'message' => 'Activity restored', 'activity' => $activity]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Permanently delete a trashed activity.
     */
    public function forceDelete($id)
    {
        try {
            $activity = Activity::onlyTrashed()->find($id);
            if (!$activity) {
                return response()->json(['success' => false, 'message' => 'Activity not found in trash'], 404);
            }

            $activity->forceDelete();

            return response()->json(['success' => true, 'message' => 'Activity permanently deleted']);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Bulk restore all trashed activities for a user/guest.
     */
    public function restoreAll(Request $request)
    {
        try {
            $user_id = $request->query('user_id');
            $guest_id = $request->query('guest_id');

            if (!$user_id && !$guest_id) {
                return response()->json(['success' => false, 'message' => 'Missing ID'], 400);
            }

            Activity::onlyTrashed()
                ->when($user_id, fn($q) => $q->where('user_id', $user_id))
                ->when($guest_id, fn($q) => $q->where('guest_id', $guest_id))
                ->restore();

            return response()->json(['success' => true, 'message' => 'All activities restored']);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Bulk permanently delete all trashed activities for a user/guest.
     */
    public function forceDeleteAll(Request $request)
    {
        try {
            $user_id = $request->query('user_id');
            $guest_id = $request->query('guest_id');

            if (!$user_id && !$guest_id) {
                return response()->json(['success' => false, 'message' => 'Missing ID'], 400);
            }

            Activity::onlyTrashed()
                ->when($user_id, fn($q) => $q->where('user_id', $user_id))
                ->when($guest_id, fn($q) => $q->where('guest_id', $guest_id))
                ->forceDelete();

            return response()->json(['success' => true, 'message' => 'All activities permanently deleted']);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}




