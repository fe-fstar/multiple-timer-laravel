<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Step;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PlanController extends Controller
{
    // Retrieve the plans of a user
    // Needs to be logged in
    // If requests self plans, retrieves all; otherwise, requests only public ones
    public function getWithUsername($username) {
        $planOwner = User::where("username", "=", $username)->first();
        $user = auth()->user();
        
        if($planOwner->id == $user->id) {
            return response()->json(['plans' => $user->plans, 'success'=>true], 200);
        } else {
            return response()->json(['plans' => $planOwner->plans()->where('is_private', false)->get(), 'success'=>true], 200);
        }
    }

    // Retrieve the plan with given ID
    public function getWithPlanId($plan_id) {
        $requestSender = auth()->user();
        $plan = Plan::find($plan_id);

        // If plan is private, checks if requesting user is the plan owner
        if($plan->is_private) {
            if(!$requestSender || $requestSender->id != $plan->user_id) {
                return response()->json(["error"=>"Unauthenticated"], 403);
            }
        }

        // If plan is public or confirmation satisfies, proceed.
        $steps = Step::where("plan_id", "=", $plan_id)->get();
        $plan->steps = $steps;

        unset($plan->user);

        return response()->json(["message" => "Access granted", "plan" => $plan], 200);
    }

    // Create a plan for the authenticated user
    public function create(Request $request) {
        $user = auth()->user();
        if (!$user) {
            return response()->json(["error" => "Unauthenticated"], 403);
        }

        $validator = Validator::make(request()->all(), [
            'title' => 'required|string',
            'description' => 'required|string',
            'seconds' => 'nullable|integer',
            'hours' => 'nullable|integer',
            'minutes' => 'nullable|integer',
            'is_private' => 'boolean',
            'steps' => 'required|array',
            'steps.*.title' => 'required|string',
            'steps.*.description' => 'required|string',
            'steps.*.seconds' => 'nullable|integer',
            'steps.*.hours' => 'nullable|integer',
            'steps.*.minutes' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validatedData = $validator->validated();

        DB::transaction(function () use ($validatedData, $user) {
            // Create the plan
            $plan = new Plan();
            $plan->is_private = $validatedData['is_private'];
            $plan->title = $validatedData['title'];
            $plan->description = $validatedData['description'];
            $plan->hours = $validatedData['hours'] ?? 0;
            $plan->minutes = $validatedData['minutes'] ?? 0;
            $plan->seconds = $validatedData['seconds'] ?? 0;
            $plan->user_id = $user->id;
            $plan->save();

            foreach ($validatedData['steps'] as $index=>$stepData) {
                $step = new Step();
                $step->title = $stepData['title'];
                $step->description = $stepData['description'];
                $step->hours = $stepData['hours'] ?? 0;
                $step->minutes = $stepData['minutes'] ?? 0;
                $step->seconds = $stepData['seconds'] ?? 0;
                $step->plan_id = $plan->id;
                $step->id = $index;
                $step->save();
            }
        });

        return response()->json(["message" => "Plan created successfully", "plan" => $validatedData], 201);
    }
    
    // Updates the plan given as a request
    public function update(Request $request) {
        // Check if authenticated
        $requestSender = auth()->user();
        $plan = Plan::find($request->id);

        // Check if plan exists and requester is plan owner
        if (!$plan) {
            return response()->json(["message" => "Plan not found"], 404);
        }
        
        if($requestSender->id != $plan->user_id) {
            return response()->json(["message"=>"Unauthorized"], 403);
        }

        // Validate the request
        $validator = Validator::make($request->all(), [
            'title' => 'required|string',
            'description' => 'required|string',
            'seconds' => 'nullable|integer',
            'hours' => 'nullable|integer',
            'minutes' => 'nullable|integer',
            'is_private' => 'boolean',
            'steps' => 'required|array',
            'steps.*.title' => 'required|string',
            'steps.*.description' => 'required|string',
            'steps.*.seconds' => 'nullable|integer',
            'steps.*.hours' => 'nullable|integer',
            'steps.*.minutes' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validatedData = $validator->validated();

        // Use a transaction to ensure all operations are atomic
        DB::beginTransaction();

        try {
            // Update the plan
            $plan->update([
                'title' => $validatedData['title'],
                'description' => $validatedData['description'],
                'seconds' => $validatedData['seconds'] ?? $plan->seconds,
                'hours' => $validatedData['hours'] ?? $plan->hours,
                'minutes' => $validatedData['minutes'] ?? $plan->minutes,
                'is_private' => $validatedData['is_private'] ?? $plan->is_private,
            ]);

            // Delete the existing steps
            $plan->steps()->delete();

            // Add new steps
            foreach ($validatedData['steps'] as $index=>$stepData) {
                $plan->steps()->create([
                    'title' => $stepData['title'],
                    'description' => $stepData['description'],
                    'seconds' => $stepData['seconds'] ?? 0,
                    'hours' => $stepData['hours'] ?? 0,
                    'minutes' => $stepData['minutes'] ?? 0,
                    'plan_id' => $plan->id,
                    'id'=>$index
                ]);
            }

            // Commit the transaction
            DB::commit();

            return response()->json(['message' => 'Plan updated successfully', 'plan' => $plan->load('steps')], 200);
        } catch (\Exception $e) {
            // Rollback the transaction
            DB::rollBack();

            return response()->json(['message' => 'Failed to update plan', 'error' => $e->getMessage()], 500);
        }
    }

    public function delete(Request $request) {
        $requestSender = auth()->user();
        $plan = Plan::find($request->id);
        $planOwner = $plan->user_id;

        if($requestSender->id != $planOwner) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Use a transaction to ensure all operations are atomic
        DB::beginTransaction();

        try {
            $plan->steps()->delete();
            $plan->delete();

            // Commit the transaction
            DB::commit();
            return response()->json(['message' => 'Plan deleted successfully'], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to delete plan', 'error' => $e->getMessage()], 500);
        }   
    }
}
