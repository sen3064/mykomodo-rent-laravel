<?php
namespace Modules\Api\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Booking\Models\Service;
use Modules\Flight\Controllers\FlightController;
use Illuminate\Support\Facades\Auth;
use App\User;
use Illuminate\Support\Facades\DB;
use App\Models\ShopSetting;

class SearchController extends Controller
{

    public function search($type = 'rent'){
        $type = $type ? $type : request()->get('type');
        $filters = []; 
        if(empty($type))
        {
            return $this->sendError(__("Type is required"));
        }

        $class = get_bookable_service_by_id($type);
        if(empty($class) or !class_exists($class)){
            // return $this->sendError(__("Type does not exists"));
            $class = "Modules\Car\Models\Car";
        } 
        
        $rows = call_user_func([$class,'search'],request());
        // dd($rows);
        $business_names = [];   

        $c=0;
        $unsets=[];
        foreach($rows as &$row){
            // $getSetting = DB::table('shop_settings')->where(['user_id'=>$row->create_user,'object_model'=>'car'])->first();
            $getSetting = ShopSetting::where(['user_id'=>$row->create_user,'object_model'=>'car'])->first();
            $is_open = !$getSetting ? true : ($getSetting->is_open==1 ? true:false);
            if(!$is_open){
                array_push($unsets,$c);
                $c++;
                continue;
            }
            if(!array_key_exists($row->create_user,$business_names)){
                // dd($row->create_user);
                if($getSetting){
                    $business_names[$row->create_user] = $getSetting->name;
                }else{
                    $business_names[$row->create_user] = User::find($row->create_user)->business_name;
                }
            }
            $row->business_name = $business_names[$row->create_user];
            $row->vendor = User::find($row->create_user,['id','first_name','last_name','name','email']);
            $c++;
        }
        // $total = $rows->total();
        foreach($unsets as $k => $v){
            unset($rows[$v]);
        }
        $total = sizeof($rows);

        return $this->sendSuccess(
            [
                'total'=>$total,
                // 'total_pages'=>$rows->lastPage(),
                // 'data'=>$rows->map(function($row){
                //     return $row->dataForApi();
                // }),
                'data'=>$rows,
            ]
        );

        // if(!empty(request()->query('limit'))){
        //     $limit = request()->query('limit');
        // }else{
        //     $limit = !empty(setting_item($type."_page_limit_item"))? setting_item($type."_page_limit_item") : 9;
        // }

        // $query = new $class();
        // $rows = $query->search(request()->input())->paginate($limit);

        // $total = $rows->total();
        // return $this->sendSuccess(
        //     [
        //         'total'=>$total,
        //         'total_pages'=>$rows->lastPage(),
        //         'data'=>$rows->map(function($row){
        //             return $row->dataForApi();
        //         }),
        //     ]
        // );
    }


    public function searchServices(){
        if(!empty(request()->query('limit'))){
            $limit = request()->query('limit');
        }else{
            $limit = 9;
        }
        $query = new Service();
        $rows = $query->search(request()->input())->paginate($limit);
        $total = $rows->total();
        return $this->sendSuccess(
            [
                'total'=>$total,
                'total_pages'=>$rows->lastPage(),
                'data'=>$rows->map(function($row){
                    return $row->dataForApi();
                }),
            ]
        );
    }

    public function getFilters($type = ''){
        $type = $type ? $type : request()->get('type');
        if(empty($type))
        {
            return $this->sendError(__("Type is required"));
        }
        $class = get_bookable_service_by_id($type);
        if(empty($class) or !class_exists($class)){
            return $this->sendError(__("Type does not exists"));
        }
        $data = call_user_func([$class,'getFiltersSearch'],request());
        return $this->sendSuccess(
            [
                'data'=>$data
            ]
        );
    }

    public function getFormSearch($type = ''){
        $type = $type ? $type : request()->get('type');
        if(empty($type))
        {
            return $this->sendError(__("Type is required"));
        }
        $class = get_bookable_service_by_id($type);
        if(empty($class) or !class_exists($class)){
            return $this->sendError(__("Type does not exists"));
        }
        $data = call_user_func([$class,'getFormSearch'],request());
        return $this->sendSuccess(
            [
                'data'=>$data
            ]
        );
    }

    public function detail($type = '',$id = '')
    {
        if(empty($type)){
            return $this->sendError(__("Resource is not available"));
        }
        if(empty($id)){
            return $this->sendError(__("Resource ID is not available"));
        }

        $class = get_bookable_service_by_id($type);
        if(empty($class) or !class_exists($class)){
            return $this->sendError(__("Type does not exists"));
        }

        $row = $class::find($id);
        if(empty($row))
        {
            return $this->sendError(__("Resource not found"));
        }

        if($type=='flight'){
            return app()->make(FlightController::class)->getData(\request(),$id);
        }

        return $this->sendSuccess([
            'data'=>$row->dataForApi(true)
        ]);

    }

    public function checkAvailability(Request $request , $type = '',$id = ''){
        if(empty($type)){
            return $this->sendError(__("Resource is not available"));
        }
        if(empty($id)){
            return $this->sendError(__("Resource ID is not available"));
        }
        $class = get_bookable_service_by_id($type);
        if(empty($class) or !class_exists($class)){
            return $this->sendError(__("Type does not exists"));
        }
        $classAvailability = $class::getClassAvailability();
        $classAvailability = app()->make($classAvailability);
        $request->merge(['id' => $id]);
        if($type == "hotel"){
            $request->merge(['hotel_id' => $id]);
            return $classAvailability->checkAvailability($request);
        }
        return $classAvailability->loadDates($request);
    }

    public function checkBoatAvailability(Request $request ,$id = ''){
        if(empty($id)){
            return $this->sendError(__("Boat ID is not available"));
        }
        $class = get_bookable_service_by_id('boat');
        $classAvailability = $class::getClassAvailability();
        $classAvailability = app()->make($classAvailability);
        $request->merge(['id' => $id]);
        return $classAvailability->availabilityBooking($request);
    }
}
