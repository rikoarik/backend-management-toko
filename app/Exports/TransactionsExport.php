<?php

namespace App\Exports;

use App\Models\TransactionItem;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TransactionsExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths
{
    protected $startDate;
    protected $endDate;

    public function __construct($startDate, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $data = TransactionItem::query()
            ->select([
                'transaction_items.*',
                'transactions.transaction_code',
                'transactions.order_name',
                'transactions.created_at as transaction_date',
                'transactions.payment_method',
                'transactions.status as transaction_status',
                'transactions.notes as transaction_notes',
                'transactions.discount_amount',
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
            ->orderBy('transactions.created_at')
            ->orderBy('transactions.id')
            ->get();

        $lastCode = null;
        $output = [];

        foreach ($data as $item) {
            $discount = ($item->transaction_code === $lastCode) ? '' : $item->discount_amount;
            $lastCode = $item->transaction_code;

            // Ensure date is a formatted string
            $dateStr = $item->transaction_date;
            if ($dateStr instanceof \DateTimeInterface) {
                $dateStr = $dateStr->setTimezone(new \DateTimeZone(config('app.timezone')))->format('Y-m-d H:i:s');
            }

            $output[] = [
                'Date' => (string) $dateStr,
                'Transaction Code' => $item->transaction_code,
                'Order Name' => $item->order_name ?? '-',
                'Product Name' => $item->product_name,
                'Category' => $item->category_name ?? '-',
                'Quantity' => $item->quantity,
                'Unit Price' => $item->unit_price,
                'Subtotal' => $item->subtotal,
                'Discount' => $discount,
                'Payment Method' => $item->payment_method,
                'Status' => $item->transaction_status,
                'Notes' => $item->transaction_notes
            ];
        }

        return collect($output);
    }

    public function headings(): array
    {
        return [
            'Date',
            'Transaction Code',
            'Order Name',
            'Product Name',
            'Category',
            'Quantity',
            'Unit Price',
            'Subtotal',
            'Discount',
            'Payment Method',
            'Status',
            'Notes'
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
            'A' => 20,  // Date
            'B' => 25,  // Transaction Code
            'C' => 20,  // Order Name
            'D' => 25,  // Product Name
            'E' => 15,  // Category
            'F' => 10,  // Quantity
            'G' => 15,  // Unit Price
            'H' => 15,  // Subtotal
            'I' => 15,  // Discount
            'J' => 15,  // Payment Method
            'K' => 15,  // Status
            'L' => 30,  // Notes
        ];
    }
}
