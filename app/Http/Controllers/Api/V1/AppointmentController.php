<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Helper\V1\ApiResponse;
use App\Http\Resources\Api\V1\AppointmentCollection;
use App\Models\Apartment;
use App\Models\Appointment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AppointmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $appointments = Appointment::where('user_id', Auth::id())->paginate();
        return new AppointmentCollection($appointments);
    }
    public function indexAgent()
    {
        $appointments = Appointment::where('agent_id', Auth::id())->paginate();
        return new AppointmentCollection($appointments);
    }

      /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $rules = [
            'date' => 'required|after:today|date_format:d-m-Y',
            'time' => 'required|date_format:h:i A',
            'apartment_id' => 'required|exists:apartments,id',
        ];

        $validation = Validator::make($request->all(), $rules);
        $validatedData = $request->all();
        if ( $validation->fails() ) {
            return ApiResponse::validationError([
                    "message" => $validation->errors()->first()
            ]);
        }

        $apartment = Apartment::where('id', $request->apartment_id)->first();
        $inputDate = Carbon::parse($request->date);
        $inputTime = Carbon::parse($request->time);

        $appointments = Appointment::where('agent_id', $apartment->user_id)
                            ->where('is_pending', false)
                            ->where('is_accepted', true)
                            ->where('date', $inputDate->toDateString())
                            ->get();

        $startTime = $inputTime->clone()->subHours(3);  
        $endTime = $inputTime->clone()->addHours(3);   

        foreach ($appointments as $appointment) {
            $appointmentTime = Carbon::parse($appointment->time);

            if ($appointmentTime->between($startTime, $endTime)) {
                return ApiResponse::errorResponse("This time slot conflicts with an existing appointment. Please choose a different time.");
            }
        }
      
        $appointment = Appointment::create([
            'user_id' => Auth::id(),
            'date' => $request->date,
            'apartment_id' => $request->apartment_id,
            'time' => $request->time,
            'agent_id' => $apartment->user_id,
        ]);

        $user_name = Auth::user()->name;
        $message = "Appointment booking for, ". $request->time . ", ". $request->date ." by ". $user_name;
        return response()->json([
            'appointment' => $appointment,
            'notification' => NotificationController::Notify($apartment->user_id, $message),
        ], 201);    

    }

    public function action(Request $request, string $id)
    {
        $rules = [
            'is_accepted' => 'sometimes|boolean'
        ];

        $validation = Validator::make($request->all(), $rules);
        if ( $validation->fails() ) {
            return ApiResponse::validationError([
                    "message" => $validation->errors()->first()
            ]);
        }

        $appointment = Appointment::where('id', $id)->first();
        $apartment = Apartment::where('id', $appointment->apartment_id)->first();
        $appointment->update([
            'is_accepted' => $request->is_accepted,
            'is_pending' => false,
        ]);

        if($request->is_accepted == true){
            NotificationController::Notify($apartment->user_id, "Appointment has been accepted on $apartment->apartment_type at $apartment->location.");
            NotificationController::Notify($appointment->user_id, "Appointment has been accepted on $apartment->apartment_type at $apartment->location with price $apartment->price_yearly.");
            return ApiResponse::successResponse('Appointment has been accepted.');
        } else {
            NotificationController::Notify($apartment->user_id, "Appointment has been rejectd on $apartment->apartment_type at $apartment->location");
            NotificationController::Notify($appointment->user_id, "Appointment has been rejected on $apartment->apartment_type at $apartment->location");
            return ApiResponse::successResponse('Appointment has been rejected.');   
        }

    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

  

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        Appointment::where('id', $id)->delete();
        NotificationController::Notify(Auth::id(), "An appointment has been cancelled and deleted.");
        return ApiResponse::successResponse('Appointment has been cancelled and deleted.');
    }
}
