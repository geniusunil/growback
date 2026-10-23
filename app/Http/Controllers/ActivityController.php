<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Activity;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\Attachment;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Models\GuestSession;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\ActivitySubtask;

class ActivityController extends Controller
{
    
    
public function createGuestSession(Request $request)
{
    try {
        // Existing valid token hai to wahi guest session reuse karo.
        $bearer = $request->bearerToken();

        if ($bearer) {
            $session = GuestSession::where(
                'token_hash',
                hash('sha256', $bearer)
            )
                ->where(function ($query) {
                    $query->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                })
                ->first();

            if ($session) {
                return response()->json([
                    'success' => true,
                    'guest_id' => $session->guest_id,
                    'guest_auth' => [
                        'token' => $bearer,
                        'expires_at' => $session->expires_at,
                    ],
                ]);
            }
        }

        // Naye guest ke liye server khud ID aur token banata hai.
        $guestId = (string) Str::uuid();
        $plainToken = Str::random(64);

        $session = GuestSession::create([
            'guest_id' => $guestId,
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->addDays(30),
        ]);

        return response()->json([
            'success' => true,
            'guest_id' => $guestId,
            'guest_auth' => [
                'token' => $plainToken,
                'expires_at' => $session->expires_at,
            ],
        ], 201);
    } catch (\Throwable $e) {
        Log::error('Guest session creation failed', [
            'error' => $e->getMessage(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Unable to create guest session.',
        ], 500);
    }
}

    
    
    /**
     * Check whether the user's account is scheduled for deletion.
     */

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
 // NEW FUNCTION: Guest token generate karna
    private function getOrCreateGuestToken(string $guestId): array
    {
        $session = GuestSession::where('guest_id', $guestId)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();

        if ($session) {
            return [
                'token' => null,
                'expires_at' => $session->expires_at,
                'new_token_generated' => false,
            ];
        }

        $plainToken = Str::random(64);

        $session = GuestSession::updateOrCreate(
            ['guest_id' => $guestId],
            [
                'token_hash' => hash('sha256', $plainToken),
                'expires_at' => now()->addDays(30),
            ]
        );

        return [
            'token' => $plainToken,
            'expires_at' => $session->expires_at,
            'new_token_generated' => true,
        ];
    }
    
    // Add the new function here
    private function ownedActivities(Request $request)
    {
        $type = $request->attributes->get('actor_type');
        $id = $request->attributes->get('actor_id');

        if ($type === 'user' && $id) {
            return Activity::query()->where('user_id', $id);
        }

        if ($type === 'guest' && $id) {
            return Activity::query()
                ->where('guest_id', $id)
                ->whereNull('user_id');
        }

        abort(401, 'Authentication required.');
    }

    /**
     * READ-ONLY:
     * Users scheduled for deletion can still view activities.
     */
    public function index(Request $request)
    {
        try {

            $activities = $this->ownedActivities($request)
                ->with('attachments')
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

                // Reverse priority if overdue after considering duration
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

public function getSubtasks(Request $request, $id)
{
    try {
        $activity = $this->ownedActivities($request)
            ->with([
                'subtasks.items' => function ($query) {
                    $query->orderBy('sort_order');
                },
            ])
            ->find($id);

        if (!$activity) {
            return response()->json([
                'success' => false,
                'message' => 'Activity not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $activity->subtasks,
        ]);
    } catch (\Exception $e) {
        Log::error('Get activity subtasks error', [
            'error' => $e->getMessage(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch subtasks.',
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

    public function show(Request $request, $id)
    {
        try {
            $activity = $this->ownedActivities($request)
                ->with('attachments')
                ->find($id);

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
    Log::info('Activity store request arrived', $request->except([
        'attachments',
        'thumbnail',
    ]));

    try {
        $validator = Validator::make($request->all(), [
            'user_id' => 'nullable|exists:users,id',
            'guest_id' => 'nullable|string|required_without:user_id',
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
                        'jpg', 'jpeg', 'png', 'webp',
                        'mp4', 'mov', 'mp3', 'wav', 'aac',
                        'opus', 'pdf', 'doc', 'docx', 'txt',
                    ];

                    $ext = strtolower(
                        $value->getClientOriginalExtension()
                    );

                    if (!in_array($ext, $allowed, true)) {
                        $fail('Invalid file format.');
                    }
                },
            ],

            'reminder_times' => 'nullable|array',
            'frequency_unit' => 'nullable|string|in:none,minutes,hours,days,weeks,months,years',
            'frequency_value' => 'nullable|integer|min:0',

            'is_mandatory_gap' => 'nullable|boolean',
            'mandatory_gap_value' => 'nullable|integer|min:0',
            'mandatory_gap_unit' => 'nullable|string|in:minutes,hours,days,weeks,years',

            'empty_stomach_value' => 'nullable|integer|min:0',
            'empty_stomach_unit' => 'nullable|string',
            'empty_stomach_after' => 'nullable|string',

            'reminder_sound' => 'nullable|string|in:continuous,small,none',
            'reminder_vibration' => 'nullable|boolean',
            'show_in_drawer' => 'nullable|boolean',
            'notification_sound' => 'nullable|boolean',
            'notification_vibration' => 'nullable|boolean',
            'show_full_screen' => 'nullable|boolean',
            'custom_sound_path' => 'nullable|string',

            'duration_value' => 'nullable|numeric|min:0',
            'duration_unit' => 'nullable|in:none,minutes,hours,days,weeks,months,years',
            'due_date' => 'nullable|date',
            'repeat_enabled' => 'nullable|boolean',

            'urls' => 'nullable|array',
            'urls.*' => 'url|max:2048',

            'time_window_enabled' => 'nullable|boolean',
            'time_window_mode' => 'nullable|string|in:can,cannot',
            'time_window_ranges' => 'nullable|array|max:3',
            'time_window_ranges.*.from' => 'required_with:time_window_ranges|string|date_format:H:i',
            'time_window_ranges.*.to' => 'required_with:time_window_ranges|string|date_format:H:i',

            'days_of_week_enabled' => 'nullable|boolean',
            'days_of_week_mode' => 'nullable|string|in:can,cannot',
            'days_of_week' => 'nullable|array',
            'days_of_week.*' => 'string|in:mon,tue,wed,thu,fri,sat,sun',
            'weekly_days' => 'nullable|array',
            'weekly_days.*' => 'string|in:mon,tue,wed,thu,fri,sat,sun',

            'public_holidays_enabled' => 'nullable|boolean',
            'public_holidays_mode' => 'nullable|string|in:can,cannot',
            'repeat_window' => 'nullable'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Maximum total attachment size: 10 MB.
        if ($request->hasFile('attachments')) {
            $totalSize = 0;

            foreach ($request->file('attachments') as $file) {
                $totalSize += $file->getSize();
            }

            if ($totalSize > 10 * 1024 * 1024) {
                return response()->json([
                    'success' => false,
                    'message' => 'Total attachment size cannot exceed 10 MB',
                ], 422);
            }
        }

        $data = $validator->validated();

        /*
         * Determine owner from authenticated actor.
         */
        $actorType = $request->attributes->get('actor_type');
        $actorId = $request->attributes->get('actor_id');

        if ($actorType === 'user' && $actorId) {
            $data['user_id'] = $actorId;
            $data['guest_id'] = null;
            $guestId = null;
        } elseif ($actorType === 'guest' && $actorId) {
            $data['user_id'] = null;
            $data['guest_id'] = $actorId;
            $guestId = $actorId;
        } elseif (
            !$actorType &&
            !empty($data['guest_id']) &&
            empty($data['user_id'])
        ) {
            /*
             * Initial guest request without a token.
             * This trusts the supplied guest_id, so use only if
             * your first-request flow authorizes that ID safely.
             */
            $guestId = $data['guest_id'];
            $data['user_id'] = null;
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required or invalid activity owner.',
            ], 401);
        }

        // Validate reminder dates and times.
        if (!empty($data['reminder_times'])) {
            foreach ($data['reminder_times'] as $reminder) {
                if (!empty($reminder['date']) && !empty($reminder['time'])) {
                    try {
                        $reminderDateTime = Carbon::createFromFormat(
                            '!Y-m-d H:i',
                            $reminder['date'] . ' ' . $reminder['time'],
                            'Asia/Kolkata'
                        );
                    } catch (\Exception $e) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Invalid reminder date or time.',
                        ], 422);
                    }

                    if ($reminderDateTime->isPast()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Reminder time cannot be in the past.',
                        ], 422);
                    }
                }
            }
        }

        // Upload thumbnail.
        if ($request->hasFile('thumbnail')) {
            $path = $request->file('thumbnail')->store(
                'thumbnails',
                'public'
            );

            $data['thumbnail'] = basename($path);
        }

        /*
         * Save activity and get/create guest token.
         */
        $guestTokenData = null;

        $activity = DB::transaction(function () use (
            $data,
            $guestId,
            &$guestTokenData
        ) {
            $activity = Activity::create($data);

            if ($guestId !== null) {
                // CALL the existing helper function.
                $guestTokenData = $this->getOrCreateGuestToken($guestId);
            }

            return $activity;
        });

        // Save attachments.
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $fileName = $file->getClientOriginalName();

                $file->storeAs(
                    'attachments',
                    $fileName,
                    'public'
                );

                Attachment::create([
                    'user_id' => $activity->user_id,
                    'guest_id' => $activity->guest_id,
                    'activity_id' => $activity->id,
                    'file_name' => $fileName,
                    'file_size' => $file->getSize(),
                ]);
            }
        }

        $response = [
            'success' => true,
            'message' => 'Activity created successfully',
            'activity' => $activity->load('attachments'),
        ];

        if ($guestTokenData !== null) {
            $response['guest_auth'] = $guestTokenData;
        }

        return response()->json($response, 201);

    } catch (\Exception $e) {
        Log::error('Activity store error', [
            'error' => $e->getMessage(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to create activity.',
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

            $activity = $this->ownedActivities($request)->find($id);

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
 * Time Window
 */
                    'time_window_enabled' =>
                    'nullable|boolean',

                    'time_window_mode' =>
                    'nullable|string|in:can,cannot',

                    'time_window_ranges' =>
                    'nullable|array|max:3',

                    'time_window_ranges.*.from' =>
                    'required_with:time_window_ranges|string|date_format:H:i',

                    'time_window_ranges.*.to' =>
                    'required_with:time_window_ranges|string|date_format:H:i',


                    /*
 * Days of Week
 */
                    'days_of_week_enabled' =>
                    'nullable|boolean',

                    'days_of_week_mode' =>
                    'nullable|string|in:can,cannot',

                    'days_of_week' =>
                    'nullable|array',

                    'days_of_week.*' =>
                    'string|in:mon,tue,wed,thu,fri,sat,sun',

                    'weekly_days' => 'nullable|array',
                    'weekly_days.*' => 'string|in:mon,tue,wed,thu,fri,sat,sun',


                    /*
 * Public Holidays
 */
                    'public_holidays_enabled' =>
                    'nullable|boolean',

                    'public_holidays_mode' =>
                    'nullable|string|in:can,cannot',
'repeat_window' => 'nullable'



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
 public function destroy(Request $request, $id)
{
    try {
        $activity = $this->ownedActivities($request)->find($id);

        if (!$activity) {
            return response()->json([
                'success' => false,
                'message' => 'Activity not found',
            ], 404);
        }

        $activity->deleted_at = now()->second(0);
        $activity->save();

        return response()->json([
            'success' => true,
            'message' => 'Activity moved to trash',
            'deleted_at' => $activity->deleted_at,
        ]);

    } catch (\Exception $e) {
        Log::error('Activity destroy error', [
            'error' => $e->getMessage(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to delete activity.',
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
        $activities = $this->ownedActivities($request)
            ->onlyTrashed()
            ->with('attachments')
            ->orderBy('deleted_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'activities' => $activities,
        ]);

    } catch (\Exception $e) {
        Log::error('Activity trash error', [
            'error' => $e->getMessage(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch trash.',
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
     * Only the authenticated user's or guest's own activity can be deleted.
     */
    public function forceDelete(Request $request, $id)
{
    try {
        $activity = $this->ownedActivities($request)
            ->onlyTrashed()
            ->with('attachments')
            ->find($id);

        if (!$activity) {
            return response()->json([
                'success' => false,
                'message' => 'Activity not found in trash',
            ], 404);
        }

        $thumbnail = $activity->thumbnail;
        $attachmentNames = $activity->attachments
            ->pluck('file_name')
            ->all();

        DB::transaction(function () use ($activity) {
            $activity->attachments()->delete();
            $activity->forceDelete();
        });

        if ($thumbnail) {
            Storage::disk('public')->delete(
                'thumbnails/' . $thumbnail
            );
        }

        foreach ($attachmentNames as $fileName) {
            Storage::disk('public')->delete(
                'attachments/' . $fileName
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Activity permanently deleted',
        ]);

    } catch (\Exception $e) {
        Log::error('Activity force delete error', [
            'error' => $e->getMessage(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to permanently delete activity.',
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
     * Permanently delete all trashed activities belonging
     * to the authenticated user or guest.
     */
   public function forceDeleteAll(Request $request)
{
    try {
        $activities = $this->ownedActivities($request)
            ->onlyTrashed()
            ->with('attachments')
            ->get();

        DB::transaction(function () use ($activities) {
            foreach ($activities as $activity) {
                $activity->attachments()->delete();
                $activity->forceDelete();
            }
        });

        foreach ($activities as $activity) {
            if ($activity->thumbnail) {
                Storage::disk('public')->delete(
                    'thumbnails/' . $activity->thumbnail
                );
            }

            foreach ($activity->attachments as $attachment) {
                Storage::disk('public')->delete(
                    'attachments/' . $attachment->file_name
                );
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'All trashed activities permanently deleted',
        ]);

    } catch (\Exception $e) {
        Log::error('Activity force delete all error', [
            'error' => $e->getMessage(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to permanently delete activities.',
        ], 500);
    }
}
}
