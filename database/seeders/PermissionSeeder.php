<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);

        foreach (self::allPermissions() as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission, 'guard_name' => 'web'],
                ['name' => $permission, 'guard_name' => 'web'],
            );
        }

        foreach (self::adminGlobalPermissions() as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission, 'guard_name' => 'web'],
                ['name' => $permission, 'guard_name' => 'web'],
            );
        }
    }

    public static function adminGlobalPermissions(): array
    {
        return [
            'admin_dashboard', 'admin_companies', 'admin_company_edit',
            'admin_login_logs', 'admin_users', 'admin_settings',
        ];
    }

    public static function allPermissions(): array
    {
        return [
            // Products
            'create_product', 'view_product', 'update_product', 'delete_product',
            // Product Variants
            'create_product_variant', 'view_product_variant', 'update_product_variant', 'delete_product_variant',
            'create_category', 'view_category', 'update_category', 'delete_category',
            'create_customer', 'view_customer', 'update_customer', 'delete_customer',
            'create_supplier', 'view_supplier', 'update_supplier', 'delete_supplier',
            'create_purchase_order', 'view_purchase_order', 'update_purchase_order', 'delete_purchase_order',
            'create_credit_note', 'view_credit_note', 'update_credit_note', 'delete_credit_note',
            'create_invoice', 'view_invoice', 'update_invoice', 'delete_invoice',
            'validate_invoice', 'send_invoice',
            'create_quote', 'view_quote', 'update_quote', 'delete_quote',
            'validate_quote', 'convert_quote',
            'create_payment', 'view_payment', 'delete_payment',
            'create_warehouse', 'view_warehouse', 'update_warehouse', 'delete_warehouse',
            'view_stock', 'adjust_stock', 'transfer_stock',
            'create_delivery_note', 'view_delivery_note', 'update_delivery_note', 'delete_delivery_note',
            'create_tax', 'view_tax', 'update_tax', 'delete_tax',
            'create_unit', 'view_unit', 'update_unit', 'delete_unit',
            'manage_users', 'manage_settings',
            'export_data', 'view_dashboard',
            'view_profits', 'export_profits',
            'view_expenses', 'view_events', 'view_activity_logs',
            'view_capital',
        ];
    }

    public static function adminPermissions(): array
    {
        return self::allPermissions();
    }

    public static function managerPermissions(): array
    {
        return [
            'create_product', 'view_product', 'update_product', 'delete_product',
            'create_product_variant', 'view_product_variant', 'update_product_variant', 'delete_product_variant',
            'create_category', 'view_category', 'update_category', 'delete_category',
            'create_customer', 'view_customer', 'update_customer', 'delete_customer',
            'create_supplier', 'view_supplier', 'update_supplier', 'delete_supplier',
            'create_purchase_order', 'view_purchase_order', 'update_purchase_order', 'delete_purchase_order',
            'create_credit_note', 'view_credit_note', 'update_credit_note', 'delete_credit_note',
            'create_invoice', 'view_invoice', 'update_invoice',
            'send_invoice',
            'create_quote', 'view_quote', 'update_quote', 'convert_quote',
            'create_payment', 'view_payment',
            'create_warehouse', 'view_warehouse', 'update_warehouse',
            'view_stock', 'adjust_stock', 'transfer_stock',
            'create_delivery_note', 'view_delivery_note', 'update_delivery_note', 'delete_delivery_note',
            'create_tax', 'view_tax', 'update_tax',
            'create_unit', 'view_unit', 'update_unit',
            'manage_settings',
            'export_data', 'view_dashboard',
            'view_expenses', 'view_events', 'view_activity_logs',
        ];
    }

    public static function accountantPermissions(): array
    {
        return [
            'view_product',
            'view_product_variant',
            'view_customer',
            'view_supplier',
            'view_purchase_order',
            'create_credit_note', 'view_credit_note', 'update_credit_note',
            'create_invoice', 'view_invoice', 'update_invoice',
            'validate_invoice', 'send_invoice',
            'view_quote',
            'create_payment', 'view_payment',
            'view_stock',
            'view_delivery_note',
            'view_tax',
            'export_data', 'view_dashboard',
            'view_profits',
            'view_expenses',
        ];
    }

    public static function warehouseManagerPermissions(): array
    {
        return [
            'create_product', 'view_product', 'update_product',
            'create_product_variant', 'view_product_variant', 'update_product_variant',
            'create_category', 'view_category', 'update_category',
            'view_customer',
            'create_supplier', 'view_supplier', 'update_supplier',
            'create_purchase_order', 'view_purchase_order', 'update_purchase_order',
            'view_credit_note',
            'view_warehouse', 'update_warehouse',
            'view_stock', 'adjust_stock', 'transfer_stock',
            'view_delivery_note',
            'view_tax',
            'view_dashboard',
            'view_expenses', 'view_events', 'view_activity_logs',
        ];
    }

    public static function salesPermissions(): array
    {
        return [
            'view_product',
            'view_product_variant',
            'create_customer', 'view_customer', 'update_customer',
            'create_invoice', 'view_invoice', 'update_invoice',
            'view_credit_note',
            'create_quote', 'view_quote', 'update_quote', 'convert_quote',
            'view_payment',
            'view_stock',
            'view_delivery_note',
            'export_data', 'view_dashboard',
            'view_expenses',
        ];
    }

    public static function employeePermissions(): array
    {
        return [
            'view_product',
            'view_product_variant',
            'view_customer',
            'view_invoice',
            'view_credit_note',
            'view_quote',
            'view_payment',
            'view_stock',
            'view_delivery_note',
            'view_dashboard',
        ];
    }
}
