<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Helper\V1\ApiResponse;
use App\Http\Resources\Api\V1\ApartmentCollection;
use App\Models\Apartment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

use function PHPUnit\Framework\isEmpty;

class ApartmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $apartments = Apartment::orderBy('total_views', 'desc')
            ->paginate();

        return new ApartmentCollection($apartments);
    }

    public function nearby()
    {
        $apartments = Apartment::all();

        $list = [];
        foreach ($apartments as $apartment) {
            if(Auth::user()->state == User::where('id', $apartment->user_id)->first()->state){
                $list[] = $apartment;
            } else{
                continue;
            }

        }

        if($list == []){
            $apartments = Apartment::orderBy('daily_views', 'desc')
            ->paginate();

            return new ApartmentCollection($apartments);
        } else{
            $perPage = 15; 
            $currentPage = request()->get('page', 1); 
            $offset = ($currentPage - 1) * $perPage;
            $items = array_slice($list, $offset, $perPage);
            $paginator = new LengthAwarePaginator($items, count($list), $perPage, $currentPage);
    
            return new ApartmentCollection($paginator);
        }

    }


    public function listApartments()
    {
        $apartments = Apartment::where('user_id', Auth::id())->latest()->paginate(15);
        return new ApartmentCollection($apartments);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $rules = [
            'apartment_type' => 'required',
            'service_type' => 'required',
            'location' => 'required',
            'square_fit' => 'required',
            'people_category' => 'required',
            'contact_number' => ['required', 'digits:11', 'min:11'],
            'rooms' => 'required|integer',
            'bathrooms' => 'required|integer',
            'description' => 'required',
            'parking_space' => 'required|boolean',
            'price_monthly' => 'required|numeric',
            'price_yearly' => 'required|numeric',
            'amenities' => 'array|required',
            'amenities.*' => 'required|string',
            'images' => 'array|required',
            'images.*' => 'required|mimes:jpeg,jpg|max:2048',
        ];

        $validation = Validator::make($request->all(), $rules);
        $validatedData = $request->all();
        if ( $validation->fails() ) {
            return ApiResponse::validationError([
                    "message" => $validation->errors()->first()
            ]);
        }

        $amenities = [];
        foreach ($request->only('amenities') as $amenity) {
            $amenities[] = $amenity;
        }

        $images = [];
        $user = User::where('id', Auth::id())->first();
        foreach ($request->images as $image) {
            $imagee = $request->apartment_type.time().'.'.$image->getClientOriginalExtension();
            $destinationPath = public_path().'/uploads/images/apartments/'.$user->email;
            $image->move($destinationPath, $imagee);
            $pathh = $destinationPath.$request->image;
            $images[] = env('APP_URL').'/images/'.$user->email.'/'.$imagee;
        }

        $apartment = Apartment::create([
            'service_type' => $request->service_type,
            'location' => $request->location,
            'square_fit' => $request->square_fit,
            'people_category' => $request->people_category,
            'contact_number' => $request->contact_number,
            'user_id' => Auth::id(),
            'rooms' => $request->rooms,
            'description' => $request->description,
            'parking_space' => $request->parking_space,
            'price_monthly' => $request->price_monthly,
            'price_yearly' => $request->price_yearly,
            'images' => $images,
            'amenities' => $amenities,
            'bathrooms' => $request->bathrooms,
            'apartment_type' => $request->apartment_type,
        ]);

        return response()->json([
            'apartment' => $apartment
        ]);

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $apartment = Apartment::where('id', $id)->first();
        // return new ApartmentCollection($apartment);
        return response()->json([
            'data' => $apartment,
        ]);
    }

    public function view(string $id)
    {

        $apartment = Apartment::where('id', $id)->first();
        $apartment->update([
            "total_views" => $apartment->total_views + 1,
            "daily_views" => $apartment->daily_views + 1
        ]);
        return new ApartmentCollection([$apartment]);
        // return response()->json([
        //     'data' => $apartment,
        // ]);
    }

     /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $rules = [
            'apartment_type' => 'sometimes',
            'service_type' => 'sometimes',
            'location' => 'sometimes',
            'square_fit' => 'sometimes',
            'people_category' => 'sometimes',
            'contact_number' => ['sometimes', 'digits:10', 'min:10'],
            'rooms' => 'sometimes|integer',
            'bathrooms' => 'sometimes|integer',
            'description' => 'sometimes',
            'parking_space' => 'sometimes|boolean',
            'price_monthly' => 'sometimes|numeric',
            'price_yearly' => 'sometimes|numeric',
            // 'notify' => 'sometimes|boolean',
        ];

        $validation = Validator::make($request->all(), $rules);
        $validatedData = $request->all();
        if ( $validation->fails() ) {
            return ApiResponse::validationError([
                    "message" => $validation->errors()->first()
            ]);
        }


        $apartment = Apartment::where('id', $id)->first();

        // $apartment->update($validatedData);
        if($request->has('service_type')){
            $apartment->update(['service_type' => $request->service_type]);
        } else if ($request->has('location')){
            $apartment->update(['location' => $request->location]);
        } else if ($request->has('square_fit')){
            $apartment->update(['square_fit' => $request->square_fit]);
        } else if ($request->has('people_category')){
            $apartment->update(['people_category' => $request->people_category]);
        } else if ($request->has('contact_number')){
            $apartment->update(['contact_number' => $request->contact_number]);
        } else if ($request->has('description')){
            $apartment->update(['description' => $request->description]);
        } else if ($request->has('parking_space')){
            $apartment->update(['parking_space' => $request->parking_space]);
        } else if ($request->has('price_monthly')){
            $apartment->update(['price_monthly' => $request->price_monthly]);
        } else if ($request->has('price_yearly')){
            $apartment->update(['price_yearly' => $request->price_yearly]);
        } else if ($request->has('bathrooms')){
            $apartment->update(['bathrooms' => $request->bathrooms]);
        } else if ($request->has('rooms')){
            $apartment->update(['rooms' => $request->rooms]);
        } else if ($request->has('apartment_type')){
            $apartment->update(['apartment_type' => $request->apartment_type]);
        } 
        // else if ($request->has('notify')){
        //     if($request->notify == true){
        //         //carry out notification logic
        //     }
        // } 
        NotificationController::Notify($apartment->user_id, "You have just edited your apartment $apartment->apartment_type at $apartment->location with price of $apartment->price_yearly.");
        return ApiResponse::successResponse('Apartment updated successfully.');
        // return ApiResponse::successResponse('Profile updated successfully.', 200);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

   

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
