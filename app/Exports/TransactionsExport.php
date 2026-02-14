<?php

namespace App\Exports;

use App\Models\TransactionItem;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TransactionsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    protected $startDate;
    protected $endDate;

    public function __construct($startDate, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function query()
    {
        return TransactionItem::query()
            ->select([
                'transaction_items.*',
                'transactions.transaction_code',
                'transactions.created_at as transaction_date',
                'transactions.payment_method',
                'transactions.status as transaction_status',
                'transactions.notes as transaction_notes',
                'users.name as customer_name',
                'products.name as product_name',
                'categories.name as category_name'
            ])
            ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
            ->join('products', 'transaction_items.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->leftJoin('users', 'transactions.user_id', '=', 'users.id')
            ->whereBetween('transactions.created_at', [
                $this->startDate . ' 00:00:00',
                $this->endDate . ' 23:59:59'
            ])
            ->orderBy('transactions.created_at');
    }

    public function headings(): array
    {
        return [
            'Date',
            'Transaction Code',
            'Customer Name',
            'Product Name',
            'Category',
            'Quantity',
            'Unit Price',
            'Subtotal',
            'Payment Method',
            'Status',
            'Notes'
        ];
    }

    public function map($item): array
    {
        return [
            $item->transaction_date,
            $item->transaction_code,
            $item->customer_name ?? 'Guest',
            $item->product_name,
            $item->category_name ?? '-',
            $item->quantity,
            $item->unit_price,
            $item->subtotal,
            $item->payment_method,
            $item->transaction_status,
            $item->transaction_notes
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20,
            'B' => 25,
            'C' => 25,
            'D' => 25,
            'E' => 15,
            'F' => 10,
            'G' => 15,
            'H' => 15,
            'I' => 15,
            'J' => 15,
            'K' => 30,
        ];
    }
}
