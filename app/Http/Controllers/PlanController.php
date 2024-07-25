<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PlanController extends Controller
{
    // get plan with id
    // get user's all plans
    // delete plan
    // all need to be authenticated
    public function getWithUserId() {
        $user = auth()->user();
        return response()->json(['plans' => $user->plans, 'success'=>true], 200);
    }

    public function getWithPlanId($plan_id) {
        $requestSender = auth()->user();
        $plan = Plan::find($plan_id);
        $planOwner = $plan->user;

        // return response()->json(["requester"=>$requestSender, "planOwner"=>$planOwner], 200);

        if($requestSender->id != $planOwner->id) {
            return response()->json(["error"=>"Unauthenticated"], 403);
        }

        unset($plan->user);

        return response()->json(["message" => "Access granted", "plan" => $plan], 200);
    }

    public function create(Request $request) {
        $user = auth()->user();
        if(!$user) {
            return response()->json(["error"=>"Unauthenticated"], 403);
        }

        $validator = Validator::make(request()->all(), [
            'title' => 'required|string',
            'description' => 'required|string',
            'seconds' => 'nullable|integer',
            'hours' => 'nullable|integer',
            'minutes' => 'nullable|integer'
        ]);
        
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $validatedData = $validator->validated();

        $seconds = $validatedData['seconds'] ?? 0;
        $hours = $validatedData['hours'] ?? 0;
        $minutes = $validatedData['minutes'] ?? 0;
        $user_id = $user->id;

        $plan = new Plan;
        $plan->title = $validatedData["title"];
        $plan->description = $validatedData["description"];
        $plan->seconds = $seconds;
        $plan->minutes = $minutes;
        $plan->hours = $hours;
        $plan->user_id = $user_id;

        $plan->save();

        return response()->json(["message"=>"plan created successfully", "plan"=>$plan], 201);
    }
}
