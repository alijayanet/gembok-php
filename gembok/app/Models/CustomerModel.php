<?php
namespace App\Models;

use CodeIgniter\Model;

class CustomerModel extends Model
{
    protected $table      = 'customers';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'name',
        'pppoe_username',
        'phone',
        'whatsapp_lid',
        'email',
        'address',
        'package_id',
        'isolation_date',
        'lat',
        'lng',
        'status',
        'created_at',
        'updated_at',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
?>
