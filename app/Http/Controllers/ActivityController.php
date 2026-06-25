<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Activity;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\Validator;
use App\Models\Attachment;
use Illuminate\Support\Facades\Storage;

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

            $activities = Activity::with('attachments')
                ->when($user_id, fn($q) => $q->where('user_id', $user_id))
                ->when($guest_id, fn($q) => $q->where('guest_id', $guest_id))
                ->orderByRaw('CASE WHEN due_date IS NULL THEN 1 ELSE 0 END ASC')
                ->orderByRaw('ABS(DATEDIFF(due_date, CURDATE())) ASC')
                ->orderByRaw('CASE WHEN due_date < CURDATE() THEN 0 ELSE 1 END ASC')
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

    public function show($id)
    {
        try {
            $activity = Activity::with('attachments')->find($id);

            if (!$activity) {
                return response()->json(['success' => false, 'message' => 'Activity not found'], 404);
            }

            return response()->json(['success' => true, 'activity' => $activity]);
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
                'priority' => 'nullable|in:low,medium,high',
                'thumbnail' => 'nullable|image|mimes:jpg,jpeg,png,webp',
                'attachments' => 'nullable|array|max:5',
                'attachments.*' =>
                'file|mimes:jpg,jpeg,png,webp,mp4,mov,mp3,pdf,doc,docx,txt',
                'reminder_times' => 'nullable|array',
                'frequency_unit' => 'nullable|string|in:none,minutes,hours,days,weeks,months,years',
                'frequency_value' => 'nullable|integer|min:0',
                'reminder_sound' => 'nullable|string|in:continuous,small,none',
                'reminder_vibration' => 'nullable|boolean',
                'show_in_drawer' => 'nullable|boolean',
                'notification_sound' => 'nullable|boolean',
                'notification_vibration' => 'nullable|boolean',
                'show_full_screen' => 'nullable|boolean',
                'custom_sound_path' => 'nullable|string',
                'due_date' => 'nullable|date',

            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Check error message above',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Total attachment size validation (10 MB)

            if ($request->hasFile('attachments')) {

                $totalSize = 0;

                foreach ($request->file('attachments') as $file) {
                    $totalSize += $file->getSize();
                }

                if ($totalSize > (10 * 1024 * 1024)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Total attachment size cannot exceed 10 MB'
                    ], 422);
                }
            }

            $data = $validator->validated();

            // Upload thumbnail
            if ($request->hasFile('thumbnail')) {

                $path = $request->file('thumbnail')
                    ->store('thumbnails', 'public');
                $data['thumbnail'] = basename($path);
            }

            // Create activity

            $activity = Activity::create($data);

            // Save attachments in attachments table

            if ($request->hasFile('attachments')) {

                foreach ($request->file('attachments') as $file) {

                    $path = $file->store('attachments', 'public');

                    Attachment::create([
                        'user_id' => $activity->user_id,
                        'guest_id'    => $activity->guest_id,
                        'activity_id' => $activity->id,
                        'file_name' => basename($path),
                        'file_size' => $file->getSize(),
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Activity created successfully',
                'activity' => $activity->load('attachments')
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
                return response()->json([
                    'success' => false,
                    'message' => 'Activity not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'category' => 'nullable|string',
                'priority' => 'nullable|in:low,medium,high',
                'thumbnail' => 'nullable|image|mimes:jpg,jpeg,png,webp',
                'attachments' => 'nullable|array|max:5',
                'attachments.*' => 'file|mimes:jpg,jpeg,png,webp,mp4,mov,mp3,pdf,doc,docx,txt',
                'deleted_attachment_ids' => 'nullable|array',
                'deleted_attachment_ids.*' => 'integer|exists:attachments,id',
                'reminder_times' => 'nullable|array',
                'frequency_unit' => 'nullable|string|in:none,minutes,hours,days,weeks,months,years',
                'frequency_value' => 'nullable|integer|min:0',
                'reminder_sound' => 'nullable|string|in:continuous,small,none',
                'reminder_vibration' => 'nullable|boolean',
                'show_in_drawer' => 'nullable|boolean',
                'notification_sound' => 'nullable|boolean',
                'notification_vibration' => 'nullable|boolean',
                'show_full_screen' => 'nullable|boolean',
                'custom_sound_path' => 'nullable|string',
                'due_date' => 'nullable|date',
                'is_completed' => 'nullable|boolean',
                'completed_at' => 'nullable|date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Total attachment size validation (10 MB)
            if ($request->hasFile('attachments')) {

                $totalSize = 0;

                foreach ($request->file('attachments') as $file) {
                    $totalSize += $file->getSize();
                }

                if ($totalSize > (10 * 1024 * 1024)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Total attachment size cannot exceed 10 MB'
                    ], 422);
                }
            }

            $data = $validator->validated();

            // Upload thumbnail
            if ($request->hasFile('thumbnail')) {

                if (
                    $activity->thumbnail &&
                    Storage::disk('public')
                    ->exists('thumbnails/' . $activity->thumbnail)
                ) {
                    Storage::disk('public')
                        ->delete('thumbnails/' . $activity->thumbnail);
                }

                $path = $request->file('thumbnail')
                    ->store('thumbnails', 'public');

                $data['thumbnail'] = basename($path);
            }

            // if ($request->filled('deleted_attachment_ids')) {

            //     $attachments = Attachment::whereIn(
            //         'id',
            //         $request->deleted_attachment_ids
            //     )->where('activity_id', $activity->id)->get();

            //     foreach ($attachments as $attachment) {

            //         if (
            //             Storage::disk('public')
            //             ->exists('attachments/' . $attachment->file_name)
            //         ) {
            //             Storage::disk('public')
            //                 ->delete('attachments/' . $attachment->file_name);
            //         }

            //         $attachment->delete();
            //     }
            // }

            // Update activity
            $activity->update($data);

            // Save new attachments
            if ($request->hasFile('attachments')) {

                foreach ($request->file('attachments') as $file) {

                    $path = $file->store('attachments', 'public');

                    Attachment::create([
                        'user_id' => $activity->user_id,
                        'guest_id'    => $activity->guest_id,
                        'activity_id' => $activity->id,
                        'file_name'   => basename($path),
                        'file_size' => $file->getSize(),
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Activity updated successfully',
                'activity' => $activity->load('attachments')
            ]);
        } catch (\Exception $e) {

            Log::error('Activity update error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update activity: ' . $e->getMessage()
            ], 500);
        }
    }

    public function deleteAttachment($id)
{
    try {

        $attachment = Attachment::find($id);

        if (!$attachment) {
            return response()->json([
                'success' => false,
                'message' => 'Attachment not found'
            ], 404);
        }

        // Delete physical file
        if (
            Storage::disk('public')
                ->exists('attachments/' . $attachment->file_name)
        ) {
            Storage::disk('public')
                ->delete('attachments/' . $attachment->file_name);
        }

        // Delete database record
        $attachment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Attachment deleted successfully'
        ]);

    } catch (\Exception $e) {

        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}
    /**
     * Mark an activity as permanently completed (stops alarms).
     */
    public function markComplete($id)
    {
        try {
            $activity = Activity::find($id);
            if (!$activity) {
                return response()->json(['success' => false, 'message' => 'Activity not found'], 404);
            }
            $activity->update([
                'is_completed' => true,
                'completed_at' => now(),
            ]);
            return response()->json(['success' => true, 'message' => 'Activity marked as completed', 'activity' => $activity->fresh()]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
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

            // ðŸ”¥ Force set deleted_at (backup safe method)
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

            $activities = Activity::with('attachments')
                ->onlyTrashed()
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

            $activity = Activity::with('attachments')
                ->onlyTrashed()
                ->find($id);

            if (!$activity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Activity not found in trash'
                ], 404);
            }

            // Delete thumbnail
            if (
                $activity->thumbnail &&
                Storage::disk('public')
                ->exists('thumbnails/' . $activity->thumbnail)
            ) {
                Storage::disk('public')
                    ->delete('thumbnails/' . $activity->thumbnail);
            }

            // Delete attachment files
            foreach ($activity->attachments as $attachment) {

                if (
                    Storage::disk('public')
                    ->exists('attachments/' . $attachment->file_name)
                ) {
                    Storage::disk('public')
                        ->delete('attachments/' . $attachment->file_name);
                }
            }

            // Permanently delete activity
            $activity->forceDelete();

            return response()->json([
                'success' => true,
                'message' => 'Activity permanently deleted'
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
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
                return response()->json([
                    'success' => false,
                    'message' => 'Missing ID'
                ], 400);
            }

            $activities = Activity::with('attachments')
                ->onlyTrashed()
                ->when($user_id, fn($q) => $q->where('user_id', $user_id))
                ->when($guest_id, fn($q) => $q->where('guest_id', $guest_id))
                ->get();

            foreach ($activities as $activity) {

                // Delete thumbnail
                if (
                    $activity->thumbnail &&
                    Storage::disk('public')
                    ->exists('thumbnails/' . $activity->thumbnail)
                ) {
                    Storage::disk('public')
                        ->delete('thumbnails/' . $activity->thumbnail);
                }

                // Delete attachment files
                foreach ($activity->attachments as $attachment) {

                    if (
                        Storage::disk('public')
                        ->exists('attachments/' . $attachment->file_name)
                    ) {
                        Storage::disk('public')
                            ->delete('attachments/' . $attachment->file_name);
                    }
                }

                // Delete activity record
                $activity->forceDelete();
            }

            return response()->json([
                'success' => true,
                'message' => 'All activities permanently deleted'
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
