<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\PosSale;
use App\Models\PosSaleItem;
use App\Models\PosSession;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PosController extends Controller
{
    public function currentSession(Request $request)
    {
        $this->authorize('viewAny', PosSession::class);

        $session = PosSession::where('company_id', TenantContext::getCompanyId())
            ->where('user_id', $request->user()->id)
            ->where('status', 'open')
            ->first();

        return response()->json(['data' => $session]);
    }

    public function printerConfiguration(Request $request, PosSession $session)
    {
        $this->authorize('view', $session);

        if ($session->company_id !== TenantContext::getCompanyId()) {
            abort(403, 'Session de caisse invalide pour cette entreprise.');
        }

        $company = $session->company;

        $config = [
            'printer_model' => $session->printer_model ?? ($company->metadata['printer_model'] ?? ''),
            'printer_ip' => $session->printer_ip ?? ($company->metadata['printer_ip'] ?? ''),
            'auto_print' => $session->printer_auto_print ?? ($company->metadata['printer_auto_print'] ?? true),
            'receipt_template' => $company->metadata['receipt_template'] ?? 'default',
        ];

        return response()->json(['data' => $config]);
    }

    public function updatePrinterConfiguration(Request $request, PosSession $session)
    {
        $this->authorize('update', $session);

        if ($session->company_id !== TenantContext::getCompanyId()) {
            abort(403, 'Session de caisse invalide pour cette entreprise.');
        }

        $request->validate([
            'printer_model' => 'nullable|string|max:50',
            'printer_ip' => 'nullable|string|max:45',
            'auto_print' => 'nullable|boolean',
        ]);

        $session->update([
            'printer_model' => $request->input('printer_model'),
            'printer_ip' => $request->input('printer_ip'),
            'printer_auto_print' => $request->input('auto_print'),
        ]);

        // Also update company metadata
        if ($session->company) {
            $session->company->metadata['printer_model'] = $request->input('printer_model');
            $session->company->metadata['printer_ip'] = $request->input('printer_ip');
            $session->company->metadata['printer_auto_print'] = $request->input('auto_print');
            $session->company->save();
        }

        return response()->json(['data' => $session]);
    }

    public function syncOfflineSales(Request $request)
    {
        $this->authorize('create', PosSale::class);

        $companyId = TenantContext::getCompanyId();
        $request->validate([
            'sales' => 'required|array|min:1',
            'sales.*.id' => 'sometimes|exists:pos_sales,id',
            'sales.*.pos_session_id' => 'required|exists:pos_sessions,id',
            'sales.*.product_id' => 'required|exists:products,id',
            'sales.*.quantity' => 'required|integer|min:1',
            'sales.*.unit_price_xof' => 'required|integer|min:0',
            'sales.*.customer_id' => 'nullable|exists:customers,id',
            'sales.*.payment_method' => 'required|in:cash,card,mobile_money',
            'sales.*.amount_paid_xof' => 'required|integer|min:0',
            'sales.*.change_returned_xof' => 'nullable|integer|min:0',
        ]);

        $user = $request->user();
        $results = [];

        foreach ($request->input('sales') as $saleData) {
            $session = PosSession::find($saleData['pos_session_id']);
            if (! $session || $session->company_id !== $companyId || $session->status !== 'open') {
                $results[] = [
                    'id' => optional($saleData['id']) ?? null,
                    'success' => false,
                    'message' => 'Session invalide ou fermée',
                ];

                continue;
            }

            // Check stock availability
            $warehouse = Warehouse::where('company_id', $companyId)->first();
            if (! $warehouse) {
                $warehouse = Warehouse::create([
                    'company_id' => $companyId,
                    'name' => 'Entrepôt principal',
                    'code' => 'PRINCIPAL',
                    'is_active' => true,
                ]);
            }

            $product = Product::where('company_id', $companyId)->where('id', $saleData['product_id'])->first();
            if (! $product) {
                $results[] = [
                    'id' => optional($saleData['id']) ?? null,
                    'success' => false,
                    'message' => "Produit introuvable: {$saleData['product_id']}",
                ];

                continue;
            }

            $stock = WarehouseStock::where('warehouse_id', $warehouse->id)
                ->where('product_id', $product->id)
                ->first();

            $availableQty = $stock ? $stock->quantity : 0;
            if ($saleData['quantity'] > $availableQty) {
                $results[] = [
                    'id' => optional($saleData['id']) ?? null,
                    'success' => false,
                    'message' => "Stock insuffisant pour {$product->name}",
                ];

                continue;
            }

            DB::beginTransaction();
            try {
                $subtotal = $saleData['quantity'] * $saleData['unit_price_xof'];
                $total = $subtotal;
                $change = max(0, $saleData['amount_paid_xof'] - $total);

                $receiptNumber = 'POS-'.date('Ymd').'-'.strtoupper(Str::random(6));

                $sale = PosSale::create([
                    'company_id' => $companyId,
                    'pos_session_id' => $session->id,
                    'user_id' => $user->id,
                    'customer_id' => $saleData['customer_id'],
                    'receipt_number' => $receiptNumber,
                    'subtotal_xof' => $subtotal,
                    'tax_xof' => 0,
                    'discount_xof' => 0,
                    'total_xof' => $total,
                    'payment_method' => $saleData['payment_method'],
                    'amount_paid_xof' => $saleData['amount_paid_xof'],
                    'change_returned_xof' => $change,
                    'status' => 'completed',
                ]);

                // Create POS items
                PosSaleItem::create([
                    'pos_sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $saleData['quantity'],
                    'unit_price_xof' => $saleData['unit_price_xof'],
                    'subtotal_xof' => $subtotal,
                ]);

                // Update stock
                $stock->quantity -= $saleData['quantity'];
                $stock->save();

                // Keep product stock in sync with warehouse stock
                $product->decrement('quantity', $saleData['quantity']);

                // Create stock movement
                StockMovement::create([
                    'company_id' => $companyId,
                    'warehouse_id' => $warehouse->id,
                    'product_id' => $product->id,
                    'type' => 'out',
                    'quantity' => $saleData['quantity'],
                    'before_quantity' => $stock->quantity + $saleData['quantity'],
                    'after_quantity' => $stock->quantity,
                    'reason' => 'Vente POS offline $receiptNumber',
                    'reference_type' => PosSale::class,
                    'reference_id' => $sale->id,
                    'created_by' => $user->id,
                ]);

                DB::commit();

                $results[] = [
                    'id' => $sale->id,
                    'success' => true,
                    'receipt_number' => $receiptNumber,
                    'total_xof' => $total,
                ];
            } catch (\Exception $e) {
                DB::rollBack();
                $results[] = [
                    'id' => optional($saleData['id']) ?? null,
                    'success' => false,
                    'message' => 'Erreur: '.$e->getMessage(),
                ];
            }
        }

        return response()->json(['data' => $results]);
    }

    public function openSession(Request $request)
    {
        $this->authorize('create', PosSession::class);

        $request->validate([
            'opening_cash_xof' => 'required|integer|min:0',
        ]);

        $existing = PosSession::where('company_id', TenantContext::getCompanyId())
            ->where('user_id', $request->user()->id)
            ->where('status', 'open')
            ->first();

        if ($existing) {
            return response()->json(['message' => 'A session is already open', 'data' => $existing], 400);
        }

        $session = PosSession::create([
            'company_id' => $request->user()->company_id,
            'user_id' => $request->user()->id,
            'status' => 'open',
            'opening_cash_xof' => $request->input('opening_cash_xof'),
            'opened_at' => now(),
        ]);

        return response()->json(['data' => $session]);
    }

    public function closeSession(Request $request, PosSession $session)
    {
        $this->authorize('update', $session);

        $request->validate([
            'closing_cash_xof' => 'required|integer|min:0',
            'notes' => 'nullable|string',
        ]);

        [$session, $summary] = DB::transaction(function () use ($request, $session) {
            $session = PosSession::where('company_id', TenantContext::getCompanyId())
                ->whereKey($session->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($session->user_id !== $request->user()->id) {
                abort(403, 'Unauthorized');
            }

            if ($session->status !== 'open') {
                throw ValidationException::withMessages([
                    'session' => ['La session de caisse est déjà fermée.'],
                ]);
            }

            $sales = $session->sales()->get();
            $cashSales = $sales->where('payment_method', 'cash')->where('status', 'completed');
            $cashCollected = $cashSales->sum(
                fn ($sale) => $sale->amount_paid_xof - $sale->change_returned_xof,
            );
            $expectedClosingCash = $session->opening_cash_xof + $cashCollected;
            $cashDifference = $request->integer('closing_cash_xof') - $expectedClosingCash;

            $session->update([
                'status' => 'closed',
                'closing_cash_xof' => $request->integer('closing_cash_xof'),
                'expected_closing_cash_xof' => $expectedClosingCash,
                'cash_difference_xof' => $cashDifference,
                'closed_at' => now(),
                'notes' => $request->input('notes'),
            ]);

            return [$session->fresh(), [
                'sales_count' => $sales->count(),
                'total_xof' => (int) $sales->sum('total_xof'),
                'cash_total_xof' => (int) $sales->where('payment_method', 'cash')->sum('total_xof'),
                'card_total_xof' => (int) $sales->where('payment_method', 'card')->sum('total_xof'),
                'mobile_money_total_xof' => (int) $sales->where('payment_method', 'mobile_money')->sum('total_xof'),
                'opening_cash_xof' => $session->opening_cash_xof,
                'expected_closing_cash_xof' => $expectedClosingCash,
                'cash_difference_xof' => $cashDifference,
            ]];
        });

        return response()->json([
            'data' => $session,
            'summary' => $summary,
        ]);
    }

    public function catalog(Request $request)
    {
        $this->authorize('viewAny', Product::class);

        // Catalogue léger pour la caisse : colonnes strictement nécessaires,
        // relations éliminées (category/taxes non utilisées à l'encaissement).
        $products = Product::query()
            ->where('company_id', TenantContext::getCompanyId())
            ->where('is_active', true)
            ->select(['id', 'name', 'sku', 'barcode', 'image', 'price_xof'])
            ->with(['variants:id,product_id,price_xof'])
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $products]);
    }

    public function processSale(Request $request)
    {
        $this->authorize('create', PosSale::class);

        $validated = $request->validate([
            'pos_session_id' => ['required', Rule::exists('pos_sessions', 'id')->where('company_id', TenantContext::getCompanyId())],
            'customer_id' => ['nullable', Rule::exists('customers', 'id')->where('company_id', TenantContext::getCompanyId())],
            'payment_method' => ['required', 'in:cash,card,mobile_money'],
            'amount_paid_xof' => ['required', 'integer', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', Rule::exists('products', 'id')->where('company_id', TenantContext::getCompanyId())],
            'items.*.product_variant_id' => ['nullable', Rule::exists('product_variants', 'id')->where('company_id', TenantContext::getCompanyId())],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price_xof' => ['required', 'integer', 'min:0'],
        ]);

        $user = $request->user();
        $companyId = TenantContext::getCompanyId();
        $items = $validated['items'];
        $total = array_sum(array_map(
            fn (array $item): int => $item['quantity'] * $item['unit_price_xof'],
            $items,
        ));

        if ($validated['amount_paid_xof'] < $total) {
            throw ValidationException::withMessages([
                'amount_paid_xof' => ['Le montant payé doit couvrir le total de la vente.'],
            ]);
        }

        foreach ($items as $item) {
            if (isset($item['product_variant_id']) && ! DB::table('product_variants')
                ->where('id', $item['product_variant_id'])
                ->where('product_id', $item['product_id'])
                ->where('company_id', $companyId)
                ->exists()) {
                throw ValidationException::withMessages([
                    'items' => ['Chaque variante doit appartenir au produit correspondant.'],
                ]);
            }
        }

        $sale = DB::transaction(function () use ($validated, $user, $companyId, $items, $total) {
            $session = PosSession::where('company_id', $companyId)
                ->whereKey($validated['pos_session_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($session->user_id !== $user->id) {
                abort(403, 'Cette session de caisse appartient à un autre utilisateur.');
            }

            if ($session->status !== 'open') {
                throw ValidationException::withMessages([
                    'pos_session_id' => ['La session de caisse est fermée.'],
                ]);
            }

            $warehouse = Warehouse::where('company_id', $companyId)->first();
            if (! $warehouse) {
                $warehouse = Warehouse::create([
                    'company_id' => $companyId,
                    'name' => 'Entrepôt principal',
                    'code' => 'PRINCIPAL',
                    'is_active' => true,
                ]);
            }

            $productIds = collect($items)->pluck('product_id')->unique()->values();
            $products = Product::where('company_id', $companyId)
                ->whereIn('id', $productIds)
                ->get()
                ->keyBy('id');
            $requiredQuantities = collect($items)
                ->groupBy('product_id')
                ->map(fn ($productItems): int => $productItems->sum('quantity'));

            $stocks = WarehouseStock::where('company_id', $companyId)
                ->where('warehouse_id', $warehouse->id)
                ->whereIn('product_id', $productIds)
                ->orderBy('product_id')
                ->lockForUpdate()
                ->get()
                ->keyBy('product_id');

            $stockErrors = [];
            foreach ($requiredQuantities as $productId => $quantity) {
                $product = $products->get($productId);
                $available = (int) ($stocks->get($productId)?->quantity ?? 0);

                if ($quantity > $available) {
                    $stockErrors[] = $product->name.' (demandé: '.$quantity.', disponible: '.$available.')';
                }
            }

            if ($stockErrors !== []) {
                throw ValidationException::withMessages([
                    'stock' => ['Stock insuffisant : '.implode(', ', $stockErrors)],
                ]);
            }

            $receiptNumber = 'POS-'.date('Ymd').'-'.strtoupper(Str::random(6));
            $sale = PosSale::create([
                'company_id' => $companyId,
                'pos_session_id' => $session->id,
                'user_id' => $user->id,
                'customer_id' => $validated['customer_id'] ?? null,
                'receipt_number' => $receiptNumber,
                'subtotal_xof' => $total,
                'tax_xof' => 0,
                'discount_xof' => 0,
                'total_xof' => $total,
                'payment_method' => $validated['payment_method'],
                'amount_paid_xof' => $validated['amount_paid_xof'],
                'change_returned_xof' => $validated['amount_paid_xof'] - $total,
                'status' => 'completed',
            ]);

            foreach ($items as $item) {
                $product = $products->get($item['product_id']);

                PosSaleItem::create([
                    'pos_sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'product_variant_id' => $item['product_variant_id'] ?? null,
                    'product_name' => $product->name,
                    'quantity' => $item['quantity'],
                    'unit_price_xof' => $item['unit_price_xof'],
                    'subtotal_xof' => $item['quantity'] * $item['unit_price_xof'],
                ]);
            }

            foreach ($requiredQuantities as $productId => $quantity) {
                $stock = $stocks->get($productId);
                $beforeQuantity = (int) $stock->quantity;
                $stock->quantity = $beforeQuantity - $quantity;
                $stock->available_quantity = max(0, (int) $stock->available_quantity - $quantity);
                $stock->save();

                $product = $products->get($productId);
                if ($product) {
                    $product->decrement('quantity', $quantity);
                }

                StockMovement::create([
                    'company_id' => $companyId,
                    'warehouse_id' => $warehouse->id,
                    'product_id' => $productId,
                    'type' => 'out',
                    'quantity' => $quantity,
                    'before_quantity' => $beforeQuantity,
                    'after_quantity' => $stock->quantity,
                    'reason' => 'Vente POS '.$receiptNumber,
                    'reference_type' => PosSale::class,
                    'reference_id' => $sale->id,
                    'created_by' => $user->id,
                ]);
            }

            return $sale;
        });

        return response()->json(['data' => $sale->load(['items', 'customer', 'session'])]);
    }

    public function sessions(Request $request)
    {
        $this->authorize('viewAny', PosSession::class);

        $companyId = TenantContext::getCompanyId();

        $query = PosSession::with('user:id,name,email')
            ->where('company_id', $companyId)
            ->withCount('sales')
            ->withSum('sales', 'total_xof');

        if ($request->filled('search')) {
            $search = '%'.$request->input('search').'%';
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', $search)
                    ->orWhere('email', 'like', $search);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $perPage = $request->integer('per_page', 20);
        $perPage = in_array($perPage, [10, 20, 25, 50, 100]) ? $perPage : 20;

        $sessions = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json($sessions);
    }

    public function sessionSales(Request $request, PosSession $session)
    {
        $this->authorize('view', $session);

        if ($session->company_id !== TenantContext::getCompanyId()) {
            abort(403);
        }

        $sales = $session->sales()
            ->with(['user', 'items', 'customer'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['data' => $sales]);
    }
}
