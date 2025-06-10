<?php
namespace Ondapresswp\WPBones\Model;


use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Ondapresswp\WPBones\Traits\SupportMultisite;
//use Ondapresswp\WPBones\Database\Relations\BelongsTo;


/**
 * Post class.
 *
 * @package WPBones\WPBones\Model
 * @author Brede Basualdo Serraino <git@brede.cl>
 */
class Post extends Model
{
    use SupportMultisite;
    protected $primaryKey = 'ID';
    public $nameField = 'post_title';
    public $tablename = "posts";

    public $timestamps = false;
    public $casts = [
        "post_date" => "datetime",
        "post_date_gmt" => "datetime",
        "post_modified" => "datetime",
        "post_modified_gmt" => "datetime",
    ];
    public $fillable = [
        "ID",
        "post_author",
        "post_date",
        "post_date_gmt",
        "post_content",
        "post_title",
        "post_excerpt",
        "post_status",
        "comment_status",
        "ping_status",
        "post_password",
        "post_name",
        "to_ping",
        "pinged",
        "post_modified",
        "post_modified_gmt",
        "post_content_filtered",
        "post_parent",
        "guid",
        "menu_order",
        "post_type",
        "post_mime_type",
        "comment_count"
    ];

    function __construct()
    {
        $id = get_current_blog_id();
        if ($id != 0)
            $this->tablename = $id . "_" . $this->tablename;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'post_author', 'ID');
    }

    public function meta(): HasMany
    {
        return $this->hasMany(Postmeta::class, 'post_id', 'ID');
    }


}
