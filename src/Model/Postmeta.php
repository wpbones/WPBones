<?php
namespace Ondapresswp\WPBones\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

//use Ondapresswp\WPBones\Database\Relations\BelongsTo;

/**
 * Option class.
 *
 * @package WPBones\WPBones\Model
 * @author Brede Basualdo Serraino <git@brede.cl>
 */
class Postmeta extends Model
{
    /**
     * @var string
     */
    public $table = 'postmeta';

    /**
     * @var string
     */
    protected $primaryKey = 'meta_id';
    public $nameField = 'meta_key';

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'ID', 'post_id');
    }

}
