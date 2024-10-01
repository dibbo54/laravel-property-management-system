<?php

namespace App\Http\Controllers\Frontend;

use Carbon\Carbon;
use App\Models\User; 
use App\Models\State; 
use App\Models\Facility;
use App\Models\Property;
use App\Models\Schedule;
use App\Models\Amenities;
use App\Models\MultiImage;
use App\Models\PackagePlan;
use Illuminate\Http\Request;
use App\Models\PropertyType; 
use App\Models\PropertyMessage;  
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class IndexController extends Controller
{
    public function PropertyDetails($id,$slug){

        $property = Property::findOrFail($id);

        $amenities = $property->amenities_id;
        $property_amen = explode(',',$amenities);


        $multiImage = MultiImage::where('property_id',$id)->get();
        $facility = Facility::where('property_id',$id)->get();

        $type_id = $property->ptype_id;
        $relatedProperty = Property::where('ptype_id',$type_id)->where('id','!=',$id)->orderBy('id','DESC')->limit(3)->get();

        return view('frontend.property.property_details',compact('property','multiImage','property_amen','facility','relatedProperty'));

    }// End Method 

    // public function PropertyMessage(Request $request){

    //     $pid = $request->property_id;
    //     $aid = $request->agent_id;

    //     if (Auth::check()) {
            
    //     PropertyMessage::insert([

    //         'user_id' => Auth::user()->id,
    //         'agent_id' => $aid,
    //         'property_id' => $pid,
    //         'msg_name' => $request->msg_name,
    //         'msg_email' => $request->msg_email,
    //         'msg_phone' => $request->msg_phone,
    //         'message' => $request->message,
    //         'created_at' => Carbon::now(), 

    //     ]);

    //     $notification = array(
    //         'message' => 'Send Message Successfully',
    //         'alert-type' => 'success'
    //     );

    //     return redirect()->back()->with($notification);



    //     }else{

    //         $notification = array(
    //         'message' => 'Plz Login Your Account First',
    //         'alert-type' => 'error'
    //     );

    //     return redirect()->back()->with($notification);
    //     }

    // }// End Method 



    public function PropertyMessage(Request $request)
{
    $pid = $request->property_id;
    $aid = $request->agent_id;

    // Check if the user is authenticated
    if (Auth::check()) {
        $userId = Auth::user()->id;

        // Check if the user has already sent a message for this property
        $messageExists = PropertyMessage::where('user_id', $userId)
                        ->where('property_id', $pid)
                        ->where('agent_id', $aid)
                        ->exists();

        if ($messageExists) {
            // If message exists, return with a notification
            $notification = array(
                'message' => 'You have already sent a message for this property.',
                'alert-type' => 'error'
            );

            return redirect()->back()->with($notification);
        }

        // If no previous message exists, insert the new message
        PropertyMessage::insert([
            'user_id' => $userId,
            'agent_id' => $aid,
            'property_id' => $pid,
            'msg_name' => $request->msg_name,
            'msg_email' => $request->msg_email,
            'msg_phone' => $request->msg_phone,
            'message' => $request->message,
            'created_at' => Carbon::now(),
        ]);

        $notification = array(
            'message' => 'Message sent successfully!',
            'alert-type' => 'success'
        );

        return redirect()->back()->with($notification);
    } else {
        // If user is not logged in, show a notification to log in first
        $notification = array(
            'message' => 'Please log in to send a message.',
            'alert-type' => 'error'
        );

        return redirect()->back()->with($notification);
    }
}

    public function AgentDetails($id){

        $agent = User::findOrFail($id);
        $property = Property::where('agent_id',$id)->get();
        $featured = Property::where('featured','1')->limit(3)->get();
        $rentproperty = Property::where('property_status','rent')->get();
        $buyproperty = Property::where('property_status','buy')->get();


        return view('frontend.agent.agent_details',compact('agent','property','featured','rentproperty','buyproperty'));

    }// End Method 


     public function AgentDetailsMessage(Request $request){
 
        $aid = $request->agent_id;

        if (Auth::check()) {
            
        PropertyMessage::insert([

            'user_id' => Auth::user()->id,
            'agent_id' => $aid, 
            'msg_name' => $request->msg_name,
            'msg_email' => $request->msg_email,
            'msg_phone' => $request->msg_phone,
            'message' => $request->message,
            'created_at' => Carbon::now(), 

        ]);

        $notification = array(
            'message' => 'Send Message Successfully',
            'alert-type' => 'success'
        );

        return redirect()->back()->with($notification);



        }else{

            $notification = array(
            'message' => 'Plz Login Your Account First',
            'alert-type' => 'error'
        );

        return redirect()->back()->with($notification);
        }

    }// End Method 


    public function RentProperty(){

        $property = Property::where('status','1')->where('property_status','rent')->paginate(3);

        return view('frontend.property.rent_property',compact('property'));

    }// End Method 


    public function BuyProperty(){

        $property = Property::where('status','1')->where('property_status','buy')->get();

        return view('frontend.property.buy_property',compact('property'));

    }// End Method 


    public function PropertyType($id){

        $property = Property::where('status','1')->where('ptype_id',$id)->get();

        $pbread = PropertyType::where('id',$id)->first();

        return view('frontend.property.property_type',compact('property','pbread'));

    }// End Method 


    public function StateDetails($id){

       $property = Property::where('status','1')->where('state',$id)->get();

       $bstate = State::where('id',$id)->first();
        return view('frontend.property.state_property',compact('property','bstate'));

    }// End Method 

    public function BuyPropertySeach(Request $request){

        $request->validate(['search' => 'required']);
        $item = $request->search;
        $sstate = $request->state;
        $stype = $request->ptype_id;

   $property = Property::where('property_name', 'like' , '%' .$item. '%')->where('property_status','buy')->with('type','pstate')
        ->whereHas('pstate', function($q) use ($sstate){
            $q->where('state_name','like' , '%' .$sstate. '%');
        })
        ->whereHas('type', function($q) use ($stype){
            $q->where('type_name','like' , '%' .$stype. '%');
         })
        ->get();

        return view('frontend.property.property_search',compact('property'));

    }// End Method 


     public function RentPropertySeach(Request $request){

        $request->validate(['search' => 'required']);
        $item = $request->search;
        $sstate = $request->state;
        $stype = $request->ptype_id;

   $property = Property::where('property_name', 'like' , '%' .$item. '%')->where('property_status','rent')->with('type','pstate')
        ->whereHas('pstate', function($q) use ($sstate){
            $q->where('state_name','like' , '%' .$sstate. '%');
        })
        ->whereHas('type', function($q) use ($stype){
            $q->where('type_name','like' , '%' .$stype. '%');
         })
        ->get();

        return view('frontend.property.property_search',compact('property'));

    }// End Method 



    public function AllPropertySeach(Request $request){

        $property_status = $request->property_status;
        $stype = $request->ptype_id; 
        $sstate = $request->state;
        $bedrooms = $request->bedrooms;
        $bathrooms = $request->bathrooms;

   $property = Property::where('status','1')->where('bedrooms',$bedrooms)->where('bathrooms', 'like' , '%' .$bathrooms. '%')->where('property_status',$property_status) 
        ->with('type','pstate')
        ->whereHas('pstate', function($q) use ($sstate){
            $q->where('state_name','like' , '%' .$sstate. '%');
        })
        ->whereHas('type', function($q) use ($stype){
            $q->where('type_name','like' , '%' .$stype. '%');
         })
        ->get();

        return view('frontend.property.property_search',compact('property'));

    }// End Method 

    public function StoreSchedule(Request $request){

        $aid = $request->agent_id;
        $pid = $request->property_id;
        if (Auth::check()) {
            Schedule::insert([
                'user_id' => Auth::user()->id,
                'property_id' => $pid,
                'agent_id' => $aid,
                'tour_date' => $request->tour_date,
                'tour_time' => $request->tour_time,
                'message' => $request->message,
                'created_at' => Carbon::now(), 
            ]);
             $notification = array(
            'message' => 'Send Request Successfully',
            'alert-type' => 'success'
        );
        return redirect()->back()->with($notification);
            
        }else{
           $notification = array(
            'message' => 'Plz Login Your Account First',
            'alert-type' => 'error'
        );
        return redirect()->back()->with($notification);
        }

    }// End Method 




}
 