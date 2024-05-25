<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\CourierTracking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookingController extends Controller
{
    public function index()
    {
        return Booking::all();
    }

    public function storeCourierLocation(Request $request){
        $booking_ids = explode(',',$request->booking_ids);
        $dt = date('Y-m-d H:i:s',strtotime("+7 Hours"));
        foreach ($booking_ids as $k => $v) {
            $tracking = new CourierTracking();
            $tracking->booking_id = $v;
            $tracking->courier_id = Auth::id();
            $tracking->latitude = $request->latitude;
            $tracking->longitude = $request->longitude;
            $tracking->created_at = $dt;
            $tracking->updated_at = $dt;
            $tracking->save();
        }
        return response()->json(['success'=>true,'message'=>'courier location received'],200);
    }

    public function getCourierLocation(Request $request){
        if($request->has('booking_id')){
            return response()->json(CourierTracking::where('booking_id',$request->booking_id)->latest()->first(),200);
        }
        return response()->json(['success'=>false,'message'=>'Required booking_id'],401);
    }
}
