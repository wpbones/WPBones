<?php
namespace Ondapresswp\WPBones\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Ondapresswp\WPBones\Traits\DontUseMultisite;
use Ondapresswp\WPBones\Traits\SupportMultisite;

//use Ondapresswp\WPBones\Database\Relations\BelongsTo;

/**
 * Option class.
 *
 * @package WPBones\WPBones\Model
 * @author Brede Basualdo Serraino <git@brede.cl>
 */
class Blog extends Model
{
    use DontUseMultisite;
    /**
     * @var string
     */
    protected $tablename = 'blogs';
  

    /**
     * @var string
     */
    protected $primaryKey = 'blog_id';
    public $nameField = 'domain';


}
