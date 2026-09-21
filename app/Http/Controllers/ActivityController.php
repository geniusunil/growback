<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Activity;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\Attachment;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Models\User;

class ActivityController extends Controller
{
    /**
     * Check whether the user's account is scheduled for deletion.
     *
     * Users with deletion_requested = true can only:
     * - View existing information
     * - Cancel account deletion
     */
    private function accountDeletionRequested($userId): bool
    {
        if (!$userId) {
            return false;
        }

        return User::where('id', $userId)
            ->where('deletion_requested', true)
            ->exists();
    }

    /**
     * Standard response when an action is blocked
     * because the account is scheduled for deletion.
     */
    private function deletionBlockedResponse()
    {
        return response()->json([
            'success' => false,
            'message' => 'Account is scheduled for deletion.'
        ], 403);
    }



    /**
     * Display a listing of activities for a user/guest.
     *
     * READ-ONLY:
     * Users scheduled for deletion can still view activities.
     */
    public function index(Request $request)
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
                ->when($user_id, fn($q) => $q->where('user_id', $user_id))
                ->when($guest_id, fn($q) => $q->where('guest_id', $guest_id))
                ->get();

            $activities = $activities->map(function ($activity) {

                // Due time if not mentioned = created_at + 8 days
                $dueTime = $activity->due_date
                    ? Carbon::parse($activity->due_date)
                    : Carbon::parse($activity->created_at)->addDays(8);

                // Remaining time in hours = Due time - Current time
                $remainingHours = round(
                    now()->diffInHours($dueTime, false, true),
                    2
                );

                $duration = (
                    empty($activity->duration_value) ||
                    strtolower($activity->duration_unit ?? '') === 'none'
                )
                    ? 1
                    : (float) $activity->duration_value;

                $duration = round($duration, 2);

                // Priority weight
                $priorityValue = match (strtolower($activity->priority ?? 'medium')) {
                    'high' => 1,
                    'medium' => 2,
                    'low' => 3,
                    default => 2,
                };

                // Calculate time available after considering duration
                $timeDifference = round(
                    $remainingHours - $duration,
                    2
                );

                // If task is already overdue after considering duration,
                // reverse the priority
                $adjustedPriority = $priorityValue;

                if ($timeDifference < 0) {
                    $adjustedPriority = 4 - $priorityValue;
                }

                // Final urgency
                $activity->urgency = round(
                    $timeDifference * $adjustedPriority,
                    2
                );

                $activity->remaining_hours = $remainingHours;

                return $activity;
            })->sortBy('urgency')->values();

            return response()->json([
                'success' => true,
                'activities' => $activities
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }




    /**
     * Mark activity as active again.
     *
     * BLOCKED when account deletion is requested.
     */
    public function unmarkComplete($id)
    {
        try {
            $activity = Activity::find($id);

            if (!$activity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Activity not found'
                ], 404);
            }

            // Account deletion restriction
            if ($this->accountDeletionRequested($activity->user_id)) {
                return $this->deletionBlockedResponse();
            }

            $activity->update([
                'is_completed' => false,
                'completed_at' => null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Activity marked as active',
                'activity' => $activity->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Show a single activity.
     *
     * READ-ONLY:
     * Users scheduled for deletion can still view activities.
     */
    public function show($id)
    {
        try {
            $activity = Activity::with('attachments')->find($id);

            if (!$activity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Activity not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'activity' => $activity
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Store a newly created activity in storage.
     *
     * BLOCKED when account deletion is requested.
     */
    public function store(Request $request)
    {
        Log::info('Activity store request arrived', $request->all());

        try {

            /*
             * Account deletion restriction
             *
             * A user whose account is scheduled for deletion
             * cannot create a new activity.
             */
            if ($this->accountDeletionRequested($request->user_id)) {
                return $this->deletionBlockedResponse();
            }


            $validator = Validator::make($request->all(), [

                'user_id' => 'nullable|exists:users,id',

                'guest_id' => 'nullable|string',

                'title' => 'required|string|max:255',

                'description' => 'nullable|string',

                'category' => 'nullable|string',

                'priority' => 'required|string|in:high,medium,low',

                'thumbnail' => 'nullable|image|mimes:jpg,jpeg,png,webp',

                'attachments' => 'nullable|array|max:5',

                'attachments.*' => [
                    'file',
                    function ($attribute, $value, $fail) {

                        $allowed = [
                            'jpg',
                            'jpeg',
                            'png',
                            'webp',
                            'mp4',
                            'mov',
                            'mp3',
                            'wav',
                            'aac',
                            'opus',
                            'pdf',
                            'doc',
                            'docx',
                            'txt'
                        ];

                        $ext = strtolower(
                            $value->getClientOriginalExtension()
                        );

                        if (!in_array($ext, $allowed)) {
                            $fail('Invalid file format.');
                        }
                    }
                ],

                'reminder_times' => 'nullable|array',

                'frequency_unit' =>
                'nullable|string|in:none,minutes,hours,days,weeks,months,years',

                'frequency_value' =>
                'nullable|integer|min:0',


                /*
                 * Mandatory Gap
                 */
                'is_mandatory_gap' =>
                'nullable|boolean',

                'mandatory_gap_value' =>
                'nullable|integer|min:0',

                'mandatory_gap_unit' =>
                'nullable|string|in:minutes,hours,days,weeks,years',


                /*
                 * Empty Stomach Condition
                 */
                'empty_stomach_value' =>
                'nullable|integer|min:0',

                'empty_stomach_unit' =>
                'nullable|string',

                'empty_stomach_after' =>
                'nullable|string',


                'reminder_sound' =>
                'nullable|string|in:continuous,small,none',

                'reminder_vibration' =>
                'nullable|boolean',

                'show_in_drawer' =>
                'nullable|boolean',

                'notification_sound' =>
                'nullable|boolean',

                'notification_vibration' =>
                'nullable|boolean',

                'show_full_screen' =>
                'nullable|boolean',

                'custom_sound_path' =>
                'nullable|string',

                'duration_value' =>
                'nullable|numeric|min:0',

                'duration_unit' =>
                'nullable|in:none,minutes,hours,days,weeks,months,years',

                'due_date' =>
                'nullable|date',

                'repeat_enabled' =>
                'nullable|boolean',

                /*
                 * URLs
                 */
                'urls' =>
                'nullable|array',

                'urls.*' =>
                'url|max:2048',

                /*
 * Time Window Restriction
 */
                'time_range_enabled' =>
                'nullable|boolean',

                'time_range_mode' =>
                'nullable|string|in:can,cannot',

                'time_ranges' =>
                'nullable|array|max:3',


                'time_ranges.*.start' =>
                'required_with:time_ranges.*.end|date_format:H:i',

                'time_ranges.*.end' =>
                'required_with:time_ranges.*.start|date_format:H:i',


                /*
 * Days of Week Restriction
 */
                'weekday_enabled' =>
                'nullable|boolean',

                'weekday_mode' =>
                'nullable|string|in:can,cannot',

                'weekdays' =>
                'nullable|array',

                'weekdays.*' =>
                'integer|between:0,6',



                /*
 * Public Holiday Restriction
 */
                /*
 * Public Holiday Restriction
 */
                'holiday_enabled' =>
                'nullable|boolean',

                'holiday_mode' =>
                'nullable|string|in:can,cannot',

                'holiday_country' =>
                'nullable|string|size:2',

            ]);


            if ($validator->fails()) {

                $errors = $validator->errors();

                if ($request->hasFile('attachments')) {

                    foreach (
                        $request->file('attachments')
                        as $index => $file
                    ) {

                        if ($errors->has("attachments.$index")) {

                            return response()->json([
                                'success' => false,
                                'message' =>
                                "Invalid file format: {$file->getClientOriginalName()}",
                                'errors' => $errors
                            ], 422);
                        }
                    }
                }

                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $errors
                ], 422);
            }


            /*
             * Total attachment size validation
             * Maximum 10 MB
             */
            if ($request->hasFile('attachments')) {

                $totalSize = 0;

                foreach (
                    $request->file('attachments')
                    as $file
                ) {
                    $totalSize += $file->getSize();
                }

                if ($totalSize > (10 * 1024 * 1024)) {

                    return response()->json([
                        'success' => false,
                        'message' =>
                        'Total attachment size cannot exceed 10 MB'
                    ], 422);
                }
            }


            $data = $validator->validated();


            /*
             * Check reminder time is not in the past
             */
            if (!empty($data['reminder_times'])) {

                foreach ($data['reminder_times'] as $reminder) {

                    if (
                        !empty($reminder['date']) &&
                        !empty($reminder['time'])
                    ) {

                        $reminderDateTime = Carbon::createFromFormat(
                            'Y-m-d H:i',
                            $reminder['date'] . ' ' . $reminder['time'],
                            'Asia/Kolkata'
                        );

                        if ($reminderDateTime->isPast()) {

                            return response()->json([
                                'success' => false,
                                'message' =>
                                'Reminder time cannot be in the past.'
                            ], 422);
                        }
                    }
                }
            }


            /*
             * Upload thumbnail
             */
            if ($request->hasFile('thumbnail')) {

                $path = $request->file('thumbnail')
                    ->store('thumbnails', 'public');

                $data['thumbnail'] = basename($path);
            }


            /*
             * Create activity
             */
            $activity = Activity::create($data);


            /*
             * Save attachments
             */
            if ($request->hasFile('attachments')) {

                foreach (
                    $request->file('attachments')
                    as $file
                ) {

                    $fileName =
                        $file->getClientOriginalName();

                    $file->storeAs(
                        'attachments',
                        $fileName,
                        'public'
                    );

                    Attachment::create([
                        'user_id' =>
                        $activity->user_id,

                        'guest_id' =>
                        $activity->guest_id,

                        'activity_id' =>
                        $activity->id,

                        'file_name' =>
                        $fileName,

                        'file_size' =>
                        $file->getSize(),
                    ]);
                }
            }


            return response()->json([
                'success' => true,
                'message' =>
                'Activity created successfully',
                'activity' =>
                $activity->load('attachments')
            ], 201);
        } catch (\Exception $e) {

            Log::error(
                'Activity store error: ' .
                    $e->getMessage()
            );

            return response()->json([
                'success' => false,
                'message' =>
                'Failed to create activity: ' .
                    $e->getMessage()
            ], 500);
        }
    }


    /**
     * Update an existing activity.
     *
     * BLOCKED when account deletion is requested.
     */
    public function update(Request $request, $id)
    {
        try {

            $activity = Activity::find($id);

            if (!$activity) {

                return response()->json([
                    'success' => false,
                    'message' =>
                    'Activity not found'
                ], 404);
            }


            /*
             * Account deletion restriction
             */
            if ($this->accountDeletionRequested($activity->user_id)) {
                return $this->deletionBlockedResponse();
            }


            /*
             * Convert reminder_times JSON string to array
             */
            if (
                $request->has('reminder_times') &&
                is_string($request->reminder_times)
            ) {

                $decodedReminderTimes =
                    json_decode(
                        $request->reminder_times,
                        true
                    );

                if (
                    json_last_error() !==
                    JSON_ERROR_NONE
                ) {

                    return response()->json([
                        'success' => false,
                        'message' =>
                        'Invalid reminder_times format.'
                    ], 422);
                }

                $request->merge([
                    'reminder_times' =>
                    $decodedReminderTimes
                ]);
            }


            $validator = Validator::make(
                $request->all(),
                [

                    'title' =>
                    'required|string|max:255',

                    'description' =>
                    'nullable|string',

                    'category' =>
                    'nullable|string',

                    'priority' =>
                    'required|string|in:high,medium,low',

                    'thumbnail' =>
                    'nullable|image|mimes:jpg,jpeg,png,webp',

                    'attachments' =>
                    'nullable|array|max:5',

                    'attachments.*' => [
                        'file',
                        function (
                            $attribute,
                            $value,
                            $fail
                        ) {

                            $allowed = [
                                'jpg',
                                'jpeg',
                                'png',
                                'webp',
                                'mp4',
                                'mov',
                                'mp3',
                                'wav',
                                'aac',
                                'opus',
                                'pdf',
                                'doc',
                                'docx',
                                'txt'
                            ];

                            $ext = strtolower(
                                $value
                                    ->getClientOriginalExtension()
                            );

                            if (
                                !in_array(
                                    $ext,
                                    $allowed
                                )
                            ) {
                                $fail(
                                    'Invalid file format.'
                                );
                            }
                        }
                    ],

                    'reminder_times' =>
                    'nullable|array',

                    'frequency_unit' =>
                    'nullable|string|in:none,minutes,hours,days,weeks,months,years',

                    'frequency_value' =>
                    'nullable|integer|min:0',


                    /*
                     * Mandatory Gap
                     */
                    'is_mandatory_gap' =>
                    'nullable|boolean',

                    'mandatory_gap_value' =>
                    'nullable|integer|min:0',

                    'mandatory_gap_unit' =>
                    'nullable|string|in:minutes,hours,days,weeks,years',


                    /*
                     * Empty Stomach Condition
                     */
                    'empty_stomach_value' =>
                    'nullable|integer|min:0',

                    'empty_stomach_unit' =>
                    'nullable|string',

                    'empty_stomach_after' =>
                    'nullable|string',


                    'reminder_sound' =>
                    'nullable|string|in:continuous,small,none',

                    'reminder_vibration' =>
                    'nullable|boolean',

                    'show_in_drawer' =>
                    'nullable|boolean',

                    'notification_sound' =>
                    'nullable|boolean',

                    'notification_vibration' =>
                    'nullable|boolean',

                    'show_full_screen' =>
                    'nullable|boolean',

                    'custom_sound_path' =>
                    'nullable|string',

                    'duration_value' =>
                    'nullable|numeric|min:0',

                    'duration_unit' =>
                    'nullable|in:none,minutes,hours,days,weeks,months,years',

                    'due_date' =>
                    'nullable|date',

                    'is_completed' =>
                    'nullable|boolean',

                    'completed_at' =>
                    'nullable|date',

                    'repeat_enabled' =>
                    'nullable|boolean',


                    /*
                     * URLs
                     */
                    'urls' =>
                    'nullable|array',

                    'urls.*' =>
                    'url|max:2048',

                    /*
 * Time Window Restriction
 */
                    'time_range_enabled' =>
                    'nullable|boolean',

                    'time_range_mode' =>
                    'nullable|string|in:can,cannot',

                    'time_ranges' =>
                    'nullable|array|max:3',


                    'time_ranges.*.start' =>
                    'required_with:time_ranges.*.end|date_format:H:i',

                    'time_ranges.*.end' =>
                    'required_with:time_ranges.*.start|date_format:H:i',


                    /*
 * Days of Week Restriction
 */
                    'weekday_enabled' =>
                    'nullable|boolean',

                    'weekday_mode' =>
                    'nullable|string|in:can,cannot',

                    'weekdays' =>
                    'nullable|array',
                    'weekdays.*' =>
                    'integer|between:0,6',




                    /*
 * Public Holiday Restriction
 */
                    'holiday_enabled' =>
                    'nullable|boolean',

                    'holiday_mode' =>
                    'nullable|string|in:can,cannot',

                    'holiday_country' =>
                    'nullable|string|size:2',

                ]
            );


            if ($validator->fails()) {

                $errors = $validator->errors();

                if ($request->hasFile('attachments')) {

                    foreach (
                        $request->file('attachments')
                        as $index => $file
                    ) {

                        if (
                            $errors->has(
                                "attachments.$index"
                            )
                        ) {

                            return response()->json([
                                'success' => false,
                                'message' =>
                                "Invalid file format: {$file->getClientOriginalName()}",
                                'errors' =>
                                $errors
                            ], 422);
                        }
                    }
                }

                return response()->json([
                    'success' => false,
                    'message' =>
                    'Validation failed',
                    'errors' =>
                    $errors
                ], 422);
            }


            /*
             * Total attachment size
             * Maximum 10 MB
             */
            if ($request->hasFile('attachments')) {

                $totalSize = 0;

                foreach (
                    $request->file('attachments')
                    as $file
                ) {
                    $totalSize +=
                        $file->getSize();
                }

                if (
                    $totalSize >
                    (10 * 1024 * 1024)
                ) {

                    return response()->json([
                        'success' => false,
                        'message' =>
                        'Total attachment size cannot exceed 10 MB'
                    ], 422);
                }
            }


            $data =
                $validator->validated();


            /*
             * Check reminder time only when
             * reminder date/time changes
             */
            $oldReminderTimes =
                $activity->reminder_times ?? [];

            $newReminderTimes =
                $data['reminder_times'] ?? [];

            if (
                $oldReminderTimes !=
                $newReminderTimes
            ) {

                foreach (
                    $newReminderTimes
                    as $reminder
                ) {

                    if (
                        !empty($reminder['date']) &&
                        !empty($reminder['time'])
                    ) {

                        $reminderDateTime =
                            Carbon::createFromFormat(
                                'Y-m-d H:i',
                                $reminder['date'] .
                                    ' ' .
                                    $reminder['time'],
                                'Asia/Kolkata'
                            );

                        if (
                            $reminderDateTime->isPast()
                        ) {

                            return response()->json([
                                'success' => false,
                                'message' =>
                                'Reminder time cannot be in the past.'
                            ], 422);
                        }
                    }
                }
            }


            /*
             * Remove thumbnail
             */
            if (
                $request->boolean(
                    'remove_thumbnail'
                )
            ) {

                if (
                    $activity->thumbnail &&
                    Storage::disk('public')
                    ->exists(
                        'thumbnails/' .
                            $activity->thumbnail
                    )
                ) {

                    Storage::disk('public')
                        ->delete(
                            'thumbnails/' .
                                $activity->thumbnail
                        );
                }

                $data['thumbnail'] = null;
            }


            /*
             * Upload new thumbnail
             */
            if ($request->hasFile('thumbnail')) {

                if (
                    $activity->thumbnail &&
                    Storage::disk('public')
                    ->exists(
                        'thumbnails/' .
                            $activity->thumbnail
                    )
                ) {

                    Storage::disk('public')
                        ->delete(
                            'thumbnails/' .
                                $activity->thumbnail
                        );
                }

                $path =
                    $request->file('thumbnail')
                    ->store(
                        'thumbnails',
                        'public'
                    );

                $data['thumbnail'] =
                    basename($path);
            }


            /*
             * Update activity
             */
            $activity->update($data);


            /*
             * Save new attachments
             */
            if ($request->hasFile('attachments')) {

                foreach (
                    $request->file('attachments')
                    as $file
                ) {

                    $fileName =
                        $file->getClientOriginalName();

                    $file->storeAs(
                        'attachments',
                        $fileName,
                        'public'
                    );

                    Attachment::create([
                        'user_id' =>
                        $activity->user_id,

                        'guest_id' =>
                        $activity->guest_id,

                        'activity_id' =>
                        $activity->id,

                        'file_name' =>
                        $fileName,

                        'file_size' =>
                        $file->getSize(),
                    ]);
                }
            }


            return response()->json([
                'success' => true,
                'message' =>
                'Activity updated successfully',
                'activity' =>
                $activity->load('attachments')
            ]);
        } catch (\Exception $e) {

            Log::error(
                'Activity update error: ' .
                    $e->getMessage()
            );

            return response()->json([
                'success' => false,
                'message' =>
                'Failed to update activity: ' .
                    $e->getMessage()
            ], 500);
        }
    }


    /**
     * Delete an attachment.
     *
     * BLOCKED when account deletion is requested.
     */
    public function deleteAttachment($id)
    {
        try {

            $attachment =
                Attachment::find($id);

            if (!$attachment) {

                return response()->json([
                    'success' => false,
                    'message' =>
                    'Attachment not found'
                ], 404);
            }


            /*
             * Account deletion restriction
             */
            if ($this->accountDeletionRequested($attachment->user_id)) {
                return $this->deletionBlockedResponse();
            }


            /*
             * Delete physical file
             */
            if (
                Storage::disk('public')
                ->exists(
                    'attachments/' .
                        $attachment->file_name
                )
            ) {

                Storage::disk('public')
                    ->delete(
                        'attachments/' .
                            $attachment->file_name
                    );
            }


            /*
             * Delete database record
             */
            $attachment->delete();


            return response()->json([
                'success' => true,
                'message' =>
                'Attachment deleted successfully'
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' =>
                $e->getMessage()
            ], 500);
        }
    }


    /**
     * Mark an activity as permanently completed.
     *
     * BLOCKED when account deletion is requested.
     */
    public function markComplete($id)
    {
        try {

            $activity =
                Activity::find($id);

            if (!$activity) {

                return response()->json([
                    'success' => false,
                    'message' =>
                    'Activity not found'
                ], 404);
            }


            /*
             * Account deletion restriction
             */
            if ($this->accountDeletionRequested($activity->user_id)) {
                return $this->deletionBlockedResponse();
            }


            $activity->is_completed = true;

            $activity->completed_at =
                now()->second(0);

            /*
             * Keep existing cPanel behaviour.
             * This requires snoozed_until column
             * to exist in activities table.
             */
            $activity->snoozed_until = null;

            $activity->save();


            return response()->json([
                'success' => true,
                'message' =>
                'Activity marked as completed',
                'activity' =>
                $activity->fresh()
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' =>
                $e->getMessage()
            ], 500);
        }
    }


    /**
     * Soft-delete an activity.
     *
     * BLOCKED when account deletion is requested.
     */
    public function destroy($id)
    {
        Log::info(
            "DELETE_REQUEST_ID: $id"
        );

        try {

            $activity =
                Activity::find($id);

            if (!$activity) {

                return response()->json([
                    'success' => false,
                    'message' =>
                    'Activity not found'
                ], 404);
            }


            /*
             * Account deletion restriction
             */
            if ($this->accountDeletionRequested($activity->user_id)) {
                return $this->deletionBlockedResponse();
            }


            $activity->deleted_at =
                now()->second(0);

            $activity->save();


            return response()->json([
                'success' => true,
                'message' =>
                'Activity moved to trash',
                'deleted_at' =>
                $activity->deleted_at
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' =>
                'Failed to delete activity: ' .
                    $e->getMessage()
            ], 500);
        }
    }


    /**
     * List soft-deleted activities.
     *
     * READ-ONLY:
     * Users scheduled for deletion can still view trash.
     */
    public function trash(Request $request)
    {
        try {

            $user_id =
                $request->query('user_id');

            $guest_id =
                $request->query('guest_id');

            if (!$user_id && !$guest_id) {

                return response()->json([
                    'success' => false,
                    'message' =>
                    'Missing ID'
                ], 400);
            }


            $activities =
                Activity::with('attachments')
                ->onlyTrashed()
                ->when(
                    $user_id,
                    fn($q) =>
                    $q->where(
                        'user_id',
                        $user_id
                    )
                )
                ->when(
                    $guest_id,
                    fn($q) =>
                    $q->where(
                        'guest_id',
                        $guest_id
                    )
                )
                ->orderBy(
                    'deleted_at',
                    'desc'
                )
                ->get();


            return response()->json([
                'success' => true,
                'activities' =>
                $activities
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' =>
                $e->getMessage()
            ], 500);
        }
    }


    /**
     * Restore a trashed activity.
     *
     * BLOCKED when account deletion is requested.
     */
    public function restore($id)
    {
        try {

            $activity =
                Activity::onlyTrashed()
                ->find($id);

            if (!$activity) {

                return response()->json([
                    'success' => false,
                    'message' =>
                    'Activity not found in trash'
                ], 404);
            }


            /*
             * Account deletion restriction
             */
            if ($this->accountDeletionRequested($activity->user_id)) {
                return $this->deletionBlockedResponse();
            }


            $activity->restore();


            return response()->json([
                'success' => true,
                'message' =>
                'Activity restored',
                'activity' =>
                $activity
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' =>
                $e->getMessage()
            ], 500);
        }
    }


    /**
     * Permanently delete a trashed activity.
     *
     * BLOCKED when account deletion is requested.
     */
    public function forceDelete($id)
    {
        try {

            $activity =
                Activity::with('attachments')
                ->onlyTrashed()
                ->find($id);

            if (!$activity) {

                return response()->json([
                    'success' => false,
                    'message' =>
                    'Activity not found in trash'
                ], 404);
            }


            /*
             * Account deletion restriction
             */
            if ($this->accountDeletionRequested($activity->user_id)) {
                return $this->deletionBlockedResponse();
            }


            /*
             * Delete thumbnail
             */
            if (
                $activity->thumbnail &&
                Storage::disk('public')
                ->exists(
                    'thumbnails/' .
                        $activity->thumbnail
                )
            ) {

                Storage::disk('public')
                    ->delete(
                        'thumbnails/' .
                            $activity->thumbnail
                    );
            }


            /*
             * Delete attachment files
             */
            foreach (
                $activity->attachments
                as $attachment
            ) {

                if (
                    Storage::disk('public')
                    ->exists(
                        'attachments/' .
                            $attachment->file_name
                    )
                ) {

                    Storage::disk('public')
                        ->delete(
                            'attachments/' .
                                $attachment->file_name
                        );
                }
            }


            /*
             * Permanently delete activity
             */
            $activity->forceDelete();


            return response()->json([
                'success' => true,
                'message' =>
                'Activity permanently deleted'
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' =>
                $e->getMessage()
            ], 500);
        }
    }


    /**
     * Bulk restore all trashed activities.
     *
     * BLOCKED when account deletion is requested.
     */
    public function restoreAll(Request $request)
    {
        try {

            $user_id =
                $request->query('user_id');

            $guest_id =
                $request->query('guest_id');

            if (!$user_id && !$guest_id) {

                return response()->json([
                    'success' => false,
                    'message' =>
                    'Missing ID'
                ], 400);
            }


            /*
             * Account deletion restriction
             */
            if ($this->accountDeletionRequested($user_id)) {
                return $this->deletionBlockedResponse();
            }


            Activity::onlyTrashed()
                ->when(
                    $user_id,
                    fn($q) =>
                    $q->where(
                        'user_id',
                        $user_id
                    )
                )
                ->when(
                    $guest_id,
                    fn($q) =>
                    $q->where(
                        'guest_id',
                        $guest_id
                    )
                )
                ->restore();


            return response()->json([
                'success' => true,
                'message' =>
                'All activities restored'
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' =>
                $e->getMessage()
            ], 500);
        }
    }


    /**
     * Bulk permanently delete all trashed activities.
     *
     * BLOCKED when account deletion is requested.
     */
    public function forceDeleteAll(Request $request)
    {
        try {

            $user_id =
                $request->query('user_id');

            $guest_id =
                $request->query('guest_id');

            if (!$user_id && !$guest_id) {

                return response()->json([
                    'success' => false,
                    'message' =>
                    'Missing ID'
                ], 400);
            }


            /*
             * Account deletion restriction
             */
            if ($this->accountDeletionRequested($user_id)) {
                return $this->deletionBlockedResponse();
            }


            $activities =
                Activity::with('attachments')
                ->onlyTrashed()
                ->when(
                    $user_id,
                    fn($q) =>
                    $q->where(
                        'user_id',
                        $user_id
                    )
                )
                ->when(
                    $guest_id,
                    fn($q) =>
                    $q->where(
                        'guest_id',
                        $guest_id
                    )
                )
                ->get();


            foreach (
                $activities
                as $activity
            ) {

                /*
                 * Delete thumbnail
                 */
                if (
                    $activity->thumbnail &&
                    Storage::disk('public')
                    ->exists(
                        'thumbnails/' .
                            $activity->thumbnail
                    )
                ) {

                    Storage::disk('public')
                        ->delete(
                            'thumbnails/' .
                                $activity->thumbnail
                        );
                }


                /*
                 * Delete attachment files
                 */
                foreach (
                    $activity->attachments
                    as $attachment
                ) {

                    if (
                        Storage::disk('public')
                        ->exists(
                            'attachments/' .
                                $attachment->file_name
                        )
                    ) {

                        Storage::disk('public')
                            ->delete(
                                'attachments/' .
                                    $attachment->file_name
                            );
                    }
                }


                /*
                 * Delete activity record
                 */
                $activity->forceDelete();
            }


            return response()->json([
                'success' => true,
                'message' =>
                'All activities permanently deleted'
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' =>
                $e->getMessage()
            ], 500);
        }
    }
}
