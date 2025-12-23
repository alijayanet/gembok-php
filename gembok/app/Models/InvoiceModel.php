<?php
namespace App\Models;

use CodeIgniter\Model;

class InvoiceModel extends Model
{
    protected $table      = 'invoices';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'customer_id',
        'amount',
        'description',
        'due_date',
        'paid',
        'status',
        'created_at',
        'updated_at',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Get invoices by customer ID
     */
    public function getByCustomerId(int $customerId)
    {
        return $this->where('customer_id', $customerId)
                    ->orderBy('created_at', 'DESC')
                    ->findAll();
    }

    /**
     * Get pending invoices
     */
    public function getPending()
    {
        return $this->where('status', 'pending')
                    ->orderBy('due_date', 'ASC')
                    ->findAll();
    }

    /**
     * Mark invoice as paid
     */
    public function markAsPaid(int $id): bool
    {
        return $this->update($id, [
            'status' => 'paid',
            'paid' => true
        ]);
    }
}
