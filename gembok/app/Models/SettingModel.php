<?php
namespace App\Models;

use CodeIgniter\Model;

class SettingModel extends Model
{
    protected $table      = 'settings';
    protected $primaryKey = 'key';
    protected $allowedFields = ['key', 'value'];
    public $timestamps = false;
}
?>
