<?php
namespace Ondapresswp\WPBones\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * Option class.
 *
 * @package App\Models\Wordpress
 * @author Brede Basualdo Serraino <git@brede.cl>
 */
class User extends Model
{
    /**
     * @var string
     */
    public $tablename = 'posts';

    public function __construct(array $attributes = [])
    {
        $this->getConnection()->setTablePrefix('op_');
        parent::__construct($attributes);
    }
    public function posts()
    {
        return $this->hasMany(Post::class, 'user_id', 'ID');
    }

}
