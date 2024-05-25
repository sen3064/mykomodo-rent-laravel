<?php
namespace Modules\Tour\Models;

use Illuminate\Database\Eloquent\Model;
use App\BaseModel;
use Kalnoy\Nestedset\NodeTrait;
use Illuminate\Database\Eloquent\SoftDeletes;

class TourParent extends Model
{
    use SoftDeletes;
    // use NodeTrait;
    protected $table = 'bravo_tour_parent';
    protected $fillable = [
        'title',
        'slug',
        'content',
        'image_id',
        'gallery',
        'location_id',
        'status',
        'create_user',
        'update_user'
    ];
    protected $slugField     = 'slug';
    protected $slugFromField = 'name';

    public static function getModelName()
    {
        return __("Tour Parent");
    }

    public static function searchForMenu($q = false)
    {
        $query = static::select('id', 'name');
        if (strlen($q)) {
            $query->where('name', 'like', "%" . $q . "%");
        }
        $a = $query->orderBy('id', 'desc')->limit(10)->get();
        return $a;
    }
    public function getDetailUrl(){
        return url(app_get_locale(false, false, '/') . config('tour.tour_route_prefix').'?cat_id[]='.$this->id);
    }

    public static function getLinkForPageSearch($locale = false, $param = [])
    {
        return url(app_get_locale(false, false, '/') . config('tour.tour_route_prefix') . "?" . http_build_query($param));
    }

    public function dataForApi(){
        $translation = $this->translateOrOrigin(app()->getLocale());
        return [
            'id'=>$this->id,
            'name'=>$translation->name,
            'slug'=>$this->slug,
        ];
    }

    public function location()
    {
        return $this->hasOne("Modules\Location\Models\Location", "id", 'location_id');
    }
}