<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{

    // CREATE TASK
    public function store(Request $request)
    {

        if ($request->start_time >= $request->end_time) {
            return response()->json([
                'status' => false,
                'message' => 'Start time must be before end time'
            ]);
        }

        $conflict = Task::where('email', $request->email)
            ->where('start_time','<',$request->end_time)
            ->where('end_time','>',$request->start_time)
            ->exists();

        if($conflict){
            return response()->json([
                'status' => false,
                'message' => 'This time slot is already booked'
            ]);
        }

        $task = Task::create([
            'email' => $request->email,
            'title' => $request->title,
            'description' => $request->description,
            'category' => $request->category,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Task created successfully',
            'task' => $task
        ]);
    }


    // UPDATE TASK
    public function update(Request $request, $email)
    {

        $task = Task::where('email', $email)->first();

        if(!$task){
            return response()->json([
                'status' => false,
                'message' => 'Task not found'
            ]);
        }

        $task->update([
            'title' => $request->title,
            'description' => $request->description,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Task updated successfully'
        ]);
    }


    // DELETE TASK
    public function destroy($email)
    {

        $task = Task::where('email', $email)->first();

        if(!$task){
            return response()->json([
                'status' => false,
                'message' => 'Task not found'
            ]);
        }

        $task->delete();

        return response()->json([
            'status' => true,
            'message' => 'Task deleted successfully'
        ]);
    }

}