<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffPermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'permission_key',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the user that owns the permission.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Available permission keys and their descriptions
     */
    public static function availablePermissions()
    {
        $permissions = [
            // Dashboard
            'menu_dashboard' => 'Dashboard/Overview Menu',
            
            // Products
            'menu_products' => 'Products Menu',
            'menu_products_list' => 'List Products',
            'menu_products_add' => 'Add Product',
            'menu_products_edit' => 'Edit Product',
            'menu_products_stock_adjustment' => 'Stock Adjustment',
            'menu_products_history' => 'Product History',
            'menu_products_delete' => 'Delete Product',
            'menu_products_export' => 'Export Products',
            'menu_products_import' => 'Import Products',
            'menu_products_brand' => 'Product Brands',
            'menu_products_category' => 'Product Categories',
            
            // Sales
            'menu_sales' => 'Sales Menu',
            'menu_sales_add' => 'Add Sales',
            'menu_sales_list' => 'List Sales',
            'menu_sales_view' => 'View Sale Details',
            'menu_sales_edit' => 'Edit Sale',
            'menu_sales_delete' => 'Delete Sale',
            'menu_sales_print' => 'Print Invoice',
            'menu_sales_download' => 'Download Invoice',
            'menu_sales_pos' => 'POS Sales',
            'menu_sales_pos_add' => 'Add POS Sale',
            'menu_sales_pos_view' => 'View POS Sale',
            'menu_sales_pos_edit' => 'Edit POS Sale',
            'menu_sales_pos_delete' => 'Delete POS Sale',
            'menu_sales_pos_print' => 'Print POS Invoice',
            'menu_sales_pos_download' => 'Download POS Invoice',
            
            // Quotation
            'menu_quotation' => 'Quotation Menu',
            'menu_quotation_add' => 'Add Quotation',
            'menu_quotation_list' => 'List Quotation',
            'menu_quotation_view' => 'View Quotation',
            'menu_quotation_edit' => 'Edit Quotation',
            'menu_quotation_delete' => 'Delete Quotation',
            'menu_quotation_print' => 'Print Quotation',
            
            // Purchase
            'menu_purchase' => 'Purchase Menu',
            'menu_purchase_order' => 'Purchase Order',
            'menu_purchase_add' => 'Add Purchase Order',
            'menu_purchase_edit' => 'Edit Purchase Order',
            'menu_purchase_delete' => 'Delete Purchase Order',
            'menu_purchase_grn' => 'GRN (Goods Received Note)',
            
            // Return
            'menu_return' => 'Return Menu',
            'menu_return_customer_add' => 'Add Customer Return',
            'menu_return_customer_list' => 'List Customer Return',
            'menu_return_customer_delete' => 'Delete Customer Return',
            'menu_return_supplier_add' => 'Add Supplier Return',
            'menu_return_supplier_list' => 'List Supplier Return',
            'menu_return_supplier_delete' => 'Delete Supplier Return',
            
            // Cheque/Banks
            'menu_banks' => 'Cheque/Banks Menu',
            'menu_banks_deposit' => 'Deposit By Cash',
            'menu_banks_cheque_list' => 'Customer Cheque List',
            'menu_banks_supplier_cheque_list' => 'Supplier Cheque List',
            'menu_banks_cheque_edit' => 'Edit Cheque',
            'menu_banks_cheque_complete' => 'Mark Cheque Complete',
            'menu_banks_cheque_return' => 'Return Cheque Action',
            'menu_banks_return_cheque' => 'Return Cheque Page',
            
            // Expenses
            'menu_expenses' => 'Expenses Menu',
            'menu_expenses_list' => 'List Expenses',
            'menu_expenses_add' => 'Add Expense',
            'menu_expenses_edit' => 'Edit Expense',
            'menu_expenses_delete' => 'Delete Expense',
            
            // Payment Management
            'menu_payment' => 'Payment Management Menu',
            'menu_payment_customer_receipt_add' => 'Add Customer Receipt',
            'menu_payment_customer_receipt_list' => 'List Customer Receipt',
            'menu_payment_supplier_add' => 'Add Supplier Payment',
            'menu_payment_supplier_list' => 'List Supplier Payment',
            
            // People
            'menu_people' => 'People Menu',
            'menu_people_suppliers' => 'Suppliers',
            'menu_people_suppliers_add' => 'Add Supplier',
            'menu_people_suppliers_edit' => 'Edit Supplier',
            'menu_people_suppliers_delete' => 'Delete Supplier',
            'menu_people_customers' => 'Customers',
            'menu_people_customers_add' => 'Add Customer',
            'menu_people_customers_edit' => 'Edit Customer',
            'menu_people_customers_delete' => 'Delete Customer',
            'menu_people_staff' => 'Staff',
            'menu_people_staff_add' => 'Add Staff',
            'menu_people_staff_edit' => 'Edit Staff',
            'menu_people_staff_delete' => 'Delete Staff',
            
            // POS
            'menu_pos' => 'POS (Point of Sale)',
            
            // Reports
            'menu_reports' => 'Reports',
            'menu_analytics' => 'Analytics',
            'menu_day_summary' => 'Day Summary',
            'menu_profit_loss' => 'Profit & Loss',
            'menu_profit_share' => 'Profit Share Management',
            'menu_profit_share_edit' => 'Edit Profit Share/Ledger',
            'menu_profit_share_delete' => 'Delete Ledger Entries',
            
            // Settings
            'menu_settings' => 'System Settings',
        ];

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('investors')) {
                $investors = \App\Models\Investor::all();
                foreach ($investors as $investor) {
                    $permissions['view_investor_' . $investor->id] = 'View Investor: ' . $investor->name;
                }
            }
        } catch (\Exception $e) {
            // Ignore if DB not ready
        }

        return $permissions;
    }

    /**
     * Hierarchical permission categories for organized display
     * Structure: Category => [ ['key' => '...', 'children' => [ ['key' => '...', 'children' => ['key1', 'key2']] ]] ]
     */
    public static function permissionCategories()
    {
        $categories = [
            'Dashboard' => [
                ['key' => 'menu_dashboard'],
            ],
            'Products Management' => [
                ['key' => 'menu_products', 'children' => [
                    ['key' => 'menu_products_list', 'children' => [
                        ['key' => 'menu_products_add'],
                        ['key' => 'menu_products_edit'],
                        ['key' => 'menu_products_stock_adjustment'],
                        ['key' => 'menu_products_history'],
                        ['key' => 'menu_products_delete'],
                        ['key' => 'menu_products_export'],
                        ['key' => 'menu_products_import'],
                    ]],
                    ['key' => 'menu_products_brand'],
                    ['key' => 'menu_products_category'],
                ]],
            ],
            'Sales Management' => [
                ['key' => 'menu_sales', 'children' => [
                    ['key' => 'menu_sales_add'],
                    ['key' => 'menu_sales_list', 'children' => [
                        ['key' => 'menu_sales_view'],
                        ['key' => 'menu_sales_edit'],
                        ['key' => 'menu_sales_delete'],
                        ['key' => 'menu_sales_print'],
                        ['key' => 'menu_sales_download'],
                    ]],
                    ['key' => 'menu_sales_pos', 'children' => [
                        ['key' => 'menu_sales_pos_add'],
                        ['key' => 'menu_sales_pos_view'],
                        ['key' => 'menu_sales_pos_edit'],
                        ['key' => 'menu_sales_pos_delete'],
                        ['key' => 'menu_sales_pos_print'],
                        ['key' => 'menu_sales_pos_download'],
                    ]],
                ]],
            ],
            'Quotation Management' => [
                ['key' => 'menu_quotation', 'children' => [
                    ['key' => 'menu_quotation_add'],
                    ['key' => 'menu_quotation_list', 'children' => [
                        ['key' => 'menu_quotation_view'],
                        ['key' => 'menu_quotation_edit'],
                        ['key' => 'menu_quotation_delete'],
                        ['key' => 'menu_quotation_print'],
                    ]],
                ]],
            ],
            'Purchase Management' => [
                ['key' => 'menu_purchase', 'children' => [
                    ['key' => 'menu_purchase_order', 'children' => [
                        ['key' => 'menu_purchase_add'],
                        ['key' => 'menu_purchase_edit'],
                        ['key' => 'menu_purchase_delete'],
                    ]],
                    ['key' => 'menu_purchase_grn'],
                ]],
            ],
            'Return Management' => [
                ['key' => 'menu_return', 'children' => [
                    ['key' => 'menu_return_customer_add'],
                    ['key' => 'menu_return_customer_list', 'children' => [
                        ['key' => 'menu_return_customer_delete'],
                    ]],
                    ['key' => 'menu_return_supplier_add'],
                    ['key' => 'menu_return_supplier_list', 'children' => [
                        ['key' => 'menu_return_supplier_delete'],
                    ]],
                ]],
            ],
            'Cheque & Banks' => [
                ['key' => 'menu_banks', 'children' => [
                    ['key' => 'menu_banks_deposit'],
                    ['key' => 'menu_banks_cheque_list', 'children' => [
                        ['key' => 'menu_banks_cheque_edit'],
                        ['key' => 'menu_banks_cheque_complete'],
                        ['key' => 'menu_banks_cheque_return'],
                    ]],
                    ['key' => 'menu_banks_return_cheque'],
                ]],
            ],
            'Expenses Management' => [
                ['key' => 'menu_expenses', 'children' => [
                    ['key' => 'menu_expenses_list', 'children' => [
                        ['key' => 'menu_expenses_add'],
                        ['key' => 'menu_expenses_edit'],
                        ['key' => 'menu_expenses_delete'],
                    ]],
                ]],
            ],
            'Payment Management' => [
                ['key' => 'menu_payment', 'children' => [
                    ['key' => 'menu_payment_customer_receipt_add'],
                    ['key' => 'menu_payment_customer_receipt_list'],
                    ['key' => 'menu_payment_supplier_add'],
                    ['key' => 'menu_payment_supplier_list'],
                ]],
            ],
            'People Management' => [
                ['key' => 'menu_people', 'children' => [
                    ['key' => 'menu_people_suppliers', 'children' => [
                        ['key' => 'menu_people_suppliers_add'],
                        ['key' => 'menu_people_suppliers_edit'],
                        ['key' => 'menu_people_suppliers_delete'],
                    ]],
                    ['key' => 'menu_people_customers', 'children' => [
                        ['key' => 'menu_people_customers_add'],
                        ['key' => 'menu_people_customers_edit'],
                        ['key' => 'menu_people_customers_delete'],
                    ]],
                    ['key' => 'menu_people_staff', 'children' => [
                        ['key' => 'menu_people_staff_add'],
                        ['key' => 'menu_people_staff_edit'],
                        ['key' => 'menu_people_staff_delete'],
                    ]],
                ]],
            ],
            'Point of Sale' => [
                ['key' => 'menu_pos'],
            ],
            'Reports & Analytics' => [
                ['key' => 'menu_reports'],
                ['key' => 'menu_analytics'],
                ['key' => 'menu_day_summary'],
                ['key' => 'menu_profit_loss'],
            ],
            'Profit Share' => [
                ['key' => 'menu_profit_share', 'children' => [
                    ['key' => 'menu_profit_share_edit'],
                    ['key' => 'menu_profit_share_delete'],
                ]],
            ],
            'Settings' => [
                ['key' => 'menu_settings'],
            ],
        ];

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('investors')) {
                $investors = \App\Models\Investor::all();
                if ($investors->count() > 0) {
                    $investorChildren = [];
                    foreach ($investors as $investor) {
                        $investorChildren[] = ['key' => 'view_investor_' . $investor->id];
                    }
                    
                    if (isset($categories['Profit Share'])) {
                        foreach ($categories['Profit Share'] as &$item) {
                            if ($item['key'] === 'menu_profit_share') {
                                if (!isset($item['children'])) {
                                    $item['children'] = [];
                                }
                                $item['children'] = array_merge($item['children'], $investorChildren);
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // Ignore
        }

        return $categories;
    }

    /**
     * Check if a user has a specific permission
     */
    public static function hasPermission($userId, $permissionKey)
    {
        return self::where('user_id', $userId)
            ->where('permission_key', $permissionKey)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Get all permissions for a user
     */
    public static function getUserPermissions($userId)
    {
        return self::where('user_id', $userId)
            ->where('is_active', true)
            ->pluck('permission_key')
            ->toArray();
    }

    /**
     * Sync permissions for a user
     */
    public static function syncPermissions($userId, array $permissions)
    {
        // Delete existing permissions
        self::where('user_id', $userId)->delete();

        // Create new permissions
        foreach ($permissions as $permission) {
            self::create([
                'user_id' => $userId,
                'permission_key' => $permission,
                'is_active' => true,
            ]);
        }
    }
}
