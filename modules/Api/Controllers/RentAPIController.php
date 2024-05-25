<?php

namespace Modules\Api\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Modules\Car\Models\Car;
// use Modules\Tour\Models\TourParent;
use Illuminate\Support\Facades\Http;
use Modules\Location\Models\Location;
use Modules\User\Models\User;
use App\Models\BravoCar;
use App\Models\BravoReview;
use App\Models\BravoCarBooking;
use Illuminate\Support\Facades\Cache;
use Modules\Car\Models\CarDate;
use Modules\Media\Models\MediaFile;
use App\Models\ShopSetting;

class RentAPIController extends Controller
{
    public $token;
    public $verifRole = [99,3,4,5,7];

    private function generateRentSlug($uid, $title)
    {
        $slug = strtolower(str_replace(' ', '-', $title)) . '-' . $uid . '-';
        $code = substr(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 6);
        $slug .= $code;
        if (BravoCar::where('slug', $slug)->doesntExist())
            return $slug;
        $this->generateRentSlug($uid, $title);
    }

    public function index(Request $request)
    {
        return $this->search($request);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $slug = $this->generateRentSlug($user->id, $request->title);

        $this->token = $request->bearerToken();
        $cdn = "https://cdn.mykomodo.kabtour.com/v2/media_files";
        $post = Http::withToken($this->token)->withoutVerifying()->withOptions(["verify" => false])->acceptJson();
        $names = [];
        foreach ($request->allFiles() as $k => $v) {
            if (is_array($v)) {
                foreach ($v as $vk) {
                    $name = $vk->getClientOriginalName();
                    $post->attach($k . '[]', file_get_contents($vk), $name);
                    $names[] = $name;
                }
            } else {
                $name = $v->getClientOriginalName();
                $post->attach($k, file_get_contents($v), $name);
                $names[] = $name;
            }
        }
        // dd($names);

        $response = $post->post($cdn, ["prefix" => $slug]);
        // dd($response->json());
        // dd($response->json());
        $result = json_decode(json_encode($response->json()));
        $banner = 0;
        $gallery = [];

        $banner = $result->banner->id;
        if (isset($result->gallery)) {
            for ($i = 0; $i < sizeof($result->gallery); $i++) {
                $gallery[] = $result->gallery[$i]->id;
            }
        }

        $car = new BravoCar();
        $car->title = $request->title;
        $car->slug = $this->generateRentSlug($user->id, $car->title);
        $car->vehicle_type = $request->vehicle_type;
        $car->vehicle_year = $request->vehicle_year;
        $car->passenger = $request->passenger;
        // $car->door = $request->door;
        $car->number = $request->number;
        $car->prices = $request->prices;
        $car->baggage = $request->baggage;
        // $car->door = $request->door;
        // $car->sale_prices = $request->sale_prices;
        // $car->address = $request->address;
        // $car->map_lat = $request->map_lat;
        // $car->map_lng = $request->map_lng;
        $car->content = $request->content;
        $car->gear = $request->gear;
        $car->status = $request->status ?? 'publish';
        $car->image_id = $banner;
        $car->banner_image_id = $car->image_id;
        $car->gallery = implode(',', $gallery);
        $car->location_id = Location::where('slug', $user->location)->first()->id;
        $car->create_user = $user->id;
        $car->created_at = date('Y-m-d H:i:s', strtotime('+8 Hours'));
        $car->updated_at = date('Y-m-d H:i:s', strtotime('+8 Hours'));
        $car->save();

        Cache::forget('bravo_cars');

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil ditambahkan',
            'data' => $car
        ]);
    }

    public function update($id, Request $request)
    {
        $car = BravoCar::find($id);
        if ($car) {
            $banner = $car->image_id;
            $gallery = explode(',', $car->gallery);

            $result = '';
            $this->token = $request->bearerToken();
            $cdn = "https://cdn.mykomodo.kabtour.com/v2/media_files";
            $post = Http::withToken($this->token)->withoutVerifying()->withOptions(["verify" => false])->acceptJson();
            $names = [];
            foreach ($request->allFiles() as $k => $v) {
                if (is_array($v)) {
                    foreach ($v as $vk) {
                        $name = $vk->getClientOriginalName();
                        $post->attach($k . '[]', file_get_contents($vk), $name);
                        $names[] = $name;
                    }
                } else {
                    $name = $v->getClientOriginalName();
                    $post->attach($k, file_get_contents($v), $name);
                    $names[] = $name;
                }
            }
            if (sizeof($names) > 0) {
                $response = $post->post($cdn, ["prefix" => $car->slug]);
                $result = json_decode(json_encode($response->json()));
            }
            if (isset($result->banner)) {
                $banner = $result->banner->id;
            }
            if (isset($result->gallery)) {
                for ($i = 0; $i < sizeof($result->gallery); $i++) {
                    $filename = $result->gallery[$i]->file_name;
                    $temp = explode('-', $filename);
                    $temp_name = $temp[sizeof($temp) - 1];
                    $temp_num = explode('.', $temp_name)[0];
                    $gallery[(int)$temp_num] = $result->gallery[$i]->id;
                }
            }
            $car->title = $request->title ?? $car->title;
            $car->vehicle_type = $request->vehicle_type ?? $car->vehicle_type;
            $car->vehicle_year = $request->vehicle_year ?? $car->vehicle_year;
            $car->passenger = $request->passenger ?? $car->passenger;
            // $car->door = $request->door;
            $car->number = $request->number ?? $car->number;
            $car->prices = $request->prices ?? $car->prices;
            $car->baggage = $request->baggage ?? $car->baggage;
            // $car->door = $request->door;
            // $car->sale_prices = $request->sale_prices;
            // $car->address = $request->address;
            // $car->map_lat = $request->map_lat;
            // $car->map_lng = $request->map_lng;
            $car->content = $request->content ?? $car->content;
            $car->gear = $request->gear ?? $car->gear;
            $car->status = $request->status ?? $car->status;
            $car->image_id = $banner;
            $car->banner_image_id = $car->image_id;
            $car->gallery = implode(',', $gallery);
            $car->updated_at = date('Y-m-d H:i:s', strtotime('+8 Hours'));
            $car->create_user = $car->create_user;
            $car->update_user = Auth::id();
            $car->save();

            Cache::forget('bravo_cars');

            return response()->json([
                'success' => true,
                'message' => 'Data berhasil diubah',
                'data' => $car
            ]);
        }
        return response()->json([
            'success' => false,
            'message' => 'Data tidak ditemukan'
        ]);
    }

    public function delete($id)
    {
        $user = Auth::user();
        $rent = BravoCar::find($id);
        if ($rent) {
            if($rent->create_user != $user->id){
                if(!in_array($user->role_id,$this->$verifRole)){
                    return response()->json([
                        'success' => false,
                        'message' => 'Insuffient Permissions'
                    ]);
                }
            }
            $rent->delete();
            CarDate::where('target_id', $id)->delete();
            // Menghapus data mobil dari cache
            Cache::forget('bravo_cars');
            return response()->json([
                'success' => true,
                'message' => 'Data telah dihapus'
            ]);
        }
        return response()->json([
            'success' => false,
            'message' => 'Data tidak ditemukan'
        ]);
    }

    public function myProducts()
    {
        $cars = BravoCar::where('create_user', Auth::id())->get();
        foreach ($cars as &$car) {
            if ($car->image_id) {
                $media = MediaFile::find($car->image_id);
                $pre_url = 'https://cdn.mykomodo.kabtour.com/uploads/';
                $car->banner = [
                    "original" => $pre_url . $media->file_path,
                    "200x150" => $media->file_resize_200 != null ? $pre_url . $media->file_resize_200 : null,
                    "250x200" => $media->file_resize_250 != null ? $pre_url . $media->file_resize_250 : null,
                    "400x350" => $media->file_resize_400 != null ? $pre_url . $media->file_resize_400 : null,
                ];
                $arrGall = [];
                if ($car->gallery) {
                    $tempgal = explode(',', $car->gallery);
                    for ($i = 0; $i < sizeof($tempgal); $i++) {
                        if ($tempgal[$i] && $tempgal[$i] != '') {
                            $gal = MediaFile::find($tempgal[$i]);
                            $arrGall[] = [
                                "original" => $pre_url . $gal->file_path,
                                "200x150" => $gal->file_resize_200 != null ? $pre_url . $gal->file_resize_200 : null,
                                "250x200" => $gal->file_resize_250 != null ? $pre_url . $gal->file_resize_250 : null,
                                "400x350" => $gal->file_resize_400 != null ? $pre_url . $gal->file_resize_400 : null,
                            ];
                        }
                    }
                }
                $car->gallery = $arrGall;
            }
        }
        return response()->json([
            'success' => true,
            'data' => $cars
        ]);
    }

    public function search(Request $request)
    {
        $startDate = $request->input('start_date') ?? date('Y-m-d', strtotime('+32 Hours'));
        $endDate = $request->input('end_date') ?? date('Y-m-d', strtotime('+32 Hours'));
        $withDriver = $request->input('supir') ?? '1';
        $selfDrive = $request->input('lepas_kunci') ?? '1';
        $locationId = $request->input('location_id') ?? '0';


        // Mendapatkan data kendaraan dari cache
        $bravoCars = Cache::remember('bravo_cars', 60, function () {
            return BravoCar::all();
        });
        // $model_car = BravoCar::query();

        // if(!empty($createUser = $request->create_user)){
        //     $model_car->where('create_user',$createUser);
        // }
        // // dd($bravoCars);

        // if(!empty($keyword = $request->keyword)){
        //     $model_car->where('title','LIKE', '%'.$keyword.'%');
        // }

        // $bravoCars = $model_car->get();

        if ($request->has('create_user')) {
            $createUser = $request->create_user;
            $bravoCars = $bravoCars->filter(function ($car) use ($createUser) {
                return $car->create_user === intval($createUser);
            });
        }
        // dd($bravoCars);

        if ($request->has('keyword')) {
            $keyword = $request->keyword;
            $bravoCars = $bravoCars->filter(function ($car) use ($keyword) {
                return stristr($car->title, $keyword) !== false;
            });
        }


        // Filter kendaraan yang memiliki status "publish"
        $publishedCars = $bravoCars->filter(function ($car) {
            return $car->status === 'publish';
        });

        $availableCars = $publishedCars->filter(function ($car) use ($withDriver, $selfDrive, $locationId) {
            // Cek availability berdasarkan kriteria
            $prices = json_decode($car->prices, true) ?? [];

            if ($withDriver && $selfDrive) {
                // Jika keduanya true, maka kendaraan bisa disewa dengan atau tanpa supir
                return isset($prices['driver']) || isset($prices['nodriver']);
            } elseif ($withDriver) {
                // Jika hanya supir yang true, maka kendaraan disewa dengan supir
                return isset($prices['driver']);
            } elseif ($selfDrive) {
                // Jika hanya lepas kunci yang true, maka kendaraan disewa tanpa supir
                return isset($prices['nodriver']);
            }
            return false;
        });

        if (intval($locationId) > 0) {
            $availableCars = $availableCars->filter(function ($car) use ($locationId) {
                // Filter berdasarkan location_id
                return $car->location_id == $locationId;
            });
        }

        // Menghitung sisa stok kendaraan berdasarkan jumlah booking pada tanggal pencarian
        $availableCars->each(function ($car) use ($startDate, $endDate) {
            $bookingsCount = BravoCarBooking::where('car_id', $car->id)
                ->where(function ($bookingQuery) use ($startDate, $endDate) {
                    $bookingQuery->whereBetween('start_date', [$startDate, $endDate])
                        ->orWhereBetween('end_date', [$startDate, $endDate])
                        ->orWhere(function ($dateQuery) use ($startDate, $endDate) {
                            $dateQuery->where('start_date', '<=', $startDate)
                                ->where('end_date', '>=', $endDate);
                        });
                })
                ->sum('number');

            $car->available_stock = $car->number - $bookingsCount;
        });

        // Filter kendaraan yang memiliki available_stock > 0
        $availableCars = $availableCars->filter(function ($car) {
            return $car->available_stock > 0;
        });

        // Menambahkan field "banner" dan mengubah isi "gallery"
        $availableCars->each(function ($car) {
            // $car->banner = $car->banner_image_id ? $car->getBannerImageUrl() : null;
            // $car->gallery = $car->getGalleryImagesUrls();
            if ($car->image_id) {
                $media = MediaFile::find($car->image_id);
                $pre_url = 'https://cdn.mykomodo.kabtour.com/uploads/';
                $car->banner = [
                    "original" => $pre_url . $media->file_path,
                    "200x150" => $media->file_resize_200 != null ? $pre_url . $media->file_resize_200 : null,
                    "250x200" => $media->file_resize_250 != null ? $pre_url . $media->file_resize_250 : null,
                    "400x350" => $media->file_resize_400 != null ? $pre_url . $media->file_resize_400 : null,
                ];
                $arrGall = [];
                if ($car->gallery) {
                    $tempgal = explode(',', $car->gallery);
                    for ($i = 0; $i < sizeof($tempgal); $i++) {
                        if ($tempgal[$i] && $tempgal[$i] != '') {
                            $gal = MediaFile::find($tempgal[$i]);
                            $arrGall[] = [
                                "original" => $pre_url . $gal->file_path,
                                "200x150" => $gal->file_resize_200 != null ? $pre_url . $gal->file_resize_200 : null,
                                "250x200" => $gal->file_resize_250 != null ? $pre_url . $gal->file_resize_250 : null,
                                "400x350" => $gal->file_resize_400 != null ? $pre_url . $gal->file_resize_400 : null,
                            ];
                        }
                    }
                }
                $car->gallery = $arrGall;
            }
        });

        $availableCars->each(function ($car) {
            $car->seller = new \stdClass(); // Inisialisasi $car->seller sebagai objek jika belum ada
            $seller = User::find($car->create_user, ['id', 'first_name', 'last_name', 'name as pic_name', 'email']);
            $car->seller->id = $seller->id;
            $car->seller->first_name = $seller->first_name;
            $car->seller->last_name = $seller->last_name;
            $car->seller->pic_name = $seller->pic_name;
            $car->seller->email = $seller->email;
            $car->seller->name = $car->business_name ?? null;
            
            // if($car->create_user==588){
            //     dd($car);
            // }

            $setting = ShopSetting::where(["user_id" => $car->create_user, "object_model" => 'transportasi'])->first();

            if ($setting) {
                // if($car->create_user==588){
                //     dd($setting);
                // }
                if ($setting->name && !empty($setting->name) && $setting->name != '') {
                    $car->seller->name = $setting->name;
                    // if($car->create_user==588){
                    //     dd($car->seller);
                    // }
                }
            }
            if ($setting && $setting->image_id) {
                $banner = MediaFile::find($setting->image_id)->file_resize_200;
                $car->seller->banner = "http://cdn.onlinewebfonts.com/svg/download_374502.svg" ? "https://cdn.mykomodo.kabtour.com/uploads/" . $banner : "https://cdn.mykomodo.kabtour.com/uploads/" . $banner;
            }

            $rating = 0;
            $review_count = 0;
            $reviews = BravoReview::where(['object_model' => 'transportasi', 'vendor_id' => $car->create_user])->get();
            if ($reviews && count($reviews) > 0) {
                foreach ($reviews as $rk) {
                    $rating = $rating + $rk->rate_number;
                    $review_count++;
                }
                $car->seller->review = intval(ceil($rating / $review_count));
                // if($k->create_user==10){
                //     dd([$reviews,$k->review,$review_count,$rating]);
                // }

            }

            $car->seller->address = $setting->address ?? ($car->address ?? $car->location_name);
            $car->seller->latitude = $setting->latitude ?? NULL;
            $car->seller->longitude = $setting->longitude ?? NULL;
            // if($car->create_user==588){
            //     dd($car);
            // }
        });

        $list = [];
        foreach ($availableCars as &$k) {
            $user = User::find($k->create_user);
            if ($user->status != 'suspend') {
                $list[] = $k;
            }
        }
        // dd($list);

        return response()->json([
            'success' => true,
            'data' => $list
        ]);
    }

    public function show($id)
    {
        $car = BravoCar::find($id);
        if ($car) {
            if ($car->image_id) {
                $media = MediaFile::find($car->image_id);
                $pre_url = 'https://cdn.mykomodo.kabtour.com/uploads/';
                $car->banner = [
                    "original" => $pre_url . $media->file_path,
                    "200x150" => $media->file_resize_200 != null ? $pre_url . $media->file_resize_200 : null,
                    "250x200" => $media->file_resize_250 != null ? $pre_url . $media->file_resize_250 : null,
                    "400x350" => $media->file_resize_400 != null ? $pre_url . $media->file_resize_400 : null,
                ];
                $arrGall = [];
                if ($car->gallery) {
                    $tempgal = explode(',', $car->gallery);
                    for ($i = 0; $i < sizeof($tempgal); $i++) {
                        if ($tempgal[$i] && $tempgal[$i] != '') {
                            $gal = MediaFile::find($tempgal[$i]);
                            $arrGall[] = [
                                "original" => $pre_url . $gal->file_path,
                                "200x150" => $gal->file_resize_200 != null ? $pre_url . $gal->file_resize_200 : null,
                                "250x200" => $gal->file_resize_250 != null ? $pre_url . $gal->file_resize_250 : null,
                                "400x350" => $gal->file_resize_400 != null ? $pre_url . $gal->file_resize_400 : null,
                            ];
                        }
                    }
                }
                $car->gallery = $arrGall;
            }
            return response()->json([
                'success' => true,
                'data' => $car
            ]);
        }
        return response()->json([
            'success' => false,
            'message' => 'Data tidak ditemukan'
        ]);
    }
}
