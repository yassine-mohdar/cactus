<?php

namespace App\Modules\Shipping\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Orders\Models\Order;
use App\Modules\Shipping\Enums\ShipmentStatus;
use App\Modules\Shipping\Jobs\SyncSenditDeliveryUpdateJob;
use App\Modules\Shipping\Models\Shipment;
use App\Modules\Shipping\Models\ShippingMethod;
use App\Modules\Shipping\Services\SenditService;
use App\Modules\Shipping\Services\ShippingSettingsService;
use App\Modules\Shipping\Services\ShipmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ShipmentController extends Controller
{
    public function __construct(
        private ShipmentService $shipmentService,
        private SenditService $senditService,
        private ShippingSettingsService $shippingSettings,
    ) {
        $this->middleware('permission.any:shipping.viewAny,shipping.update')->only(['index', 'show', 'reports']);
        $this->middleware('permission.any:shipping.update')->only(['store', 'updateStatus', 'updateTracking', 'toggleIssue']);
    }

    /**
     * Ready-to-ship queue + all shipments listing with filters.
     */
    public function index(Request $request)
    {
        $query = Shipment::with(['order.customer', 'shippingMethod']);

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Issue filter
        if ($request->filled('issues') && $request->issues === '1') {
            $query->withIssues();
        }

        if ($request->filled('tracking')) {
            if ($request->tracking === 'missing') {
                $query->where(function ($builder) {
                    $builder->whereNull('tracking_number')->orWhere('tracking_number', '');
                });
            }

            if ($request->tracking === 'present') {
                $query->whereNotNull('tracking_number')->where('tracking_number', '!=', '');
            }
        }

        // Search by tracking or order reference
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('tracking_number', 'like', "%{$search}%")
                    ->orWhereHas('order', fn($oq) => $oq->where('reference_number', 'like', "%{$search}%"));
            });
        }

        $shipments = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        $stats = [
            'total' => Shipment::count(),
            'ready_to_ship' => Shipment::where('status', ShipmentStatus::READY_TO_SHIP)->count(),
            'packed' => Shipment::where('status', ShipmentStatus::PACKED)->count(),
            'in_transit' => Shipment::whereIn('status', [ShipmentStatus::DISPATCHED, ShipmentStatus::IN_TRANSIT])->count(),
            'issues' => Shipment::where('has_delivery_issue', true)->where('status', '!=', ShipmentStatus::DELIVERED)->count(),
            'returned' => Shipment::where('status', ShipmentStatus::RETURNED)->count(),
            'delivered_today' => Shipment::whereDate('delivered_at', today())->count(),
            'attention' => Shipment::where(function ($builder) {
                $builder->where('has_delivery_issue', true)
                    ->orWhere('status', ShipmentStatus::FAILED_DELIVERY);
            })->count(),
        ];

        $statusCounts = Shipment::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $statusBreakdown = collect(ShipmentStatus::cases())
            ->map(fn (ShipmentStatus $status) => [
                'status' => $status,
                'count' => (int) ($statusCounts[$status->value] ?? 0),
            ])
            ->filter(fn (array $row) => $row['count'] > 0)
            ->values();

        $carrierExpression = "COALESCE(NULLIF(carrier_name, ''), 'Unassigned')";
        $carrierBreakdown = DB::query()
            ->fromSub(
                Shipment::query()->selectRaw($carrierExpression.' as carrier_label'),
                'shipment_carriers'
            )
            ->selectRaw('carrier_label, COUNT(*) as aggregate')
            ->groupBy('carrier_label')
            ->orderByDesc('aggregate')
            ->limit(5)
            ->get();

        $issueBreakdown = [
            'open' => Shipment::where('has_delivery_issue', true)
                ->whereNotIn('status', [ShipmentStatus::DELIVERED, ShipmentStatus::RETURNED, ShipmentStatus::CANCELLED])
                ->count(),
            'failed_delivery' => Shipment::where('status', ShipmentStatus::FAILED_DELIVERY)->count(),
            'returned' => Shipment::where('status', ShipmentStatus::RETURNED)->count(),
        ];

        $agedCounts = [
            'ready_over_24h' => Shipment::where('status', ShipmentStatus::READY_TO_SHIP)
                ->where('updated_at', '<', now()->subDay())
                ->count(),
            'transit_over_72h' => Shipment::whereIn('status', [ShipmentStatus::DISPATCHED, ShipmentStatus::IN_TRANSIT])
                ->where('updated_at', '<', now()->subDays(3))
                ->count(),
            'issues_over_48h' => Shipment::where('has_delivery_issue', true)
                ->where('updated_at', '<', now()->subDays(2))
                ->count(),
        ];

        return view('admin.shipping.shipments.index', compact(
            'shipments',
            'stats',
            'statusBreakdown',
            'carrierBreakdown',
            'issueBreakdown',
            'agedCounts',
        ));
    }

    /**
     * View a single shipment with its full timeline.
     */
    public function show(Shipment $shipment)
    {
        $shipment->load([
            'order.customer',
            'order.lineItems',
            'order.addresses',
            'shippingMethod.shippingCarrier',
            'statusHistory.changedByUser',
            'packedByUser',
            'dispatchedByUser',
        ]);

        $availableTransitions = $shipment->status->allowedTransitions();
        $shippingAddress = $shipment->order?->addresses?->firstWhere('type', 'shipping');
        $senditCarrier = $shipment->shippingMethod?->shippingCarrier;
        $senditContext = $shipment->usesSendit() ? [
            'carrier' => $senditCarrier,
            'configured' => $senditCarrier?->isConfigured() ?? false,
        ] : null;

        return view('admin.shipping.shipments.show', compact('shipment', 'availableTransitions', 'shippingAddress', 'senditContext'));
    }

    /**
     * Create a shipment for an order (from the order detail page).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'shipping_method_id' => 'nullable|exists:shipping_methods,id',
            'carrier_name' => 'nullable|string|max:100',
            'carrier_service' => 'nullable|string|max:100',
            'tracking_number' => 'nullable|string|max:255',
            'weight' => 'nullable|numeric|min:0',
            'dimensions' => 'nullable|string|max:50',
            'package_count' => 'nullable|integer|min:1',
            'internal_notes' => 'nullable|string|max:1000',
        ]);

        $order = Order::findOrFail($validated['order_id']);
        unset($validated['order_id']);

        $shipment = $this->shipmentService->createShipment(
            $order,
            $validated['shipping_method_id'] ?? null,
            $validated
        );

        return redirect()->route('admin.shipping.shipments.show', $shipment)
            ->with('success', 'Shipment created successfully.');
    }

    /**
     * Transition a shipment to a new status.
     */
    public function updateStatus(Request $request, Shipment $shipment)
    {
        $validated = $request->validate([
            'status' => 'required|string',
            'notes' => 'nullable|string|max:1000',
            'tracking_number' => 'nullable|string|max:255',
            'tracking_url' => 'nullable|url|max:500',
            'failure_reason' => 'nullable|string|max:500',
        ]);

        $newStatus = ShipmentStatus::from($validated['status']);

        if (
            $newStatus === ShipmentStatus::DISPATCHED
            && $this->shippingSettings->trackingRequiredOnDispatch()
            && empty($validated['tracking_number'])
            && ! filled($shipment->tracking_number)
        ) {
            return back()->withErrors([
                'tracking_number' => 'A tracking number is required before a shipment can be dispatched.',
            ]);
        }

        // Update tracking if provided with the status change
        if (!empty($validated['tracking_number'])) {
            $this->shipmentService->updateTracking(
                $shipment,
                $validated['tracking_number'],
                $validated['tracking_url'] ?? null
            );
        }

        $extraAttributes = [];
        if (!empty($validated['failure_reason'])) {
            $extraAttributes['failure_reason'] = $validated['failure_reason'];
        }

        try {
            if ($newStatus === ShipmentStatus::CANCELLED && $shipment->usesSendit() && filled($shipment->external_reference)) {
                $this->senditService->deleteDelivery(
                    $shipment,
                    transitionLocal: true,
                    note: $validated['notes'] ?? 'Shipment cancelled before Sendit collection.',
                );
            } else {
                $this->shipmentService->transitionStatus(
                    $shipment,
                    $newStatus,
                    $validated['notes'] ?? null,
                    $extraAttributes
                );
            }
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.shipping.shipments.show', $shipment)
            ->with('success', "Shipment status updated to {$newStatus->label()}.");
    }

    /**
     * Update tracking information.
     */
    public function updateTracking(Request $request, Shipment $shipment)
    {
        $validated = $request->validate([
            'tracking_number' => 'required|string|max:255',
            'tracking_url' => 'nullable|url|max:500',
            'carrier_name' => 'nullable|string|max:100',
        ]);

        $this->shipmentService->updateTracking(
            $shipment,
            $validated['tracking_number'],
            $validated['tracking_url'] ?? null,
            $validated['carrier_name'] ?? null
        );

        return back()->with('success', 'Tracking information updated.');
    }

    /**
     * Flag or resolve a delivery issue.
     */
    public function toggleIssue(Request $request, Shipment $shipment)
    {
        if ($shipment->has_delivery_issue) {
            $this->shipmentService->resolveDeliveryIssue($shipment, $request->input('notes'));
            return back()->with('success', 'Delivery issue resolved.');
        }

        $validated = $request->validate([
            'delivery_issue_notes' => 'required|string|max:1000',
            'failure_reason' => 'nullable|string|max:500',
        ]);

        $this->shipmentService->flagDeliveryIssue(
            $shipment,
            $validated['delivery_issue_notes'],
            $validated['failure_reason'] ?? null
        );

        return back()->with('success', 'Delivery issue flagged.');
    }

    public function createSenditDelivery(Shipment $shipment)
    {
        $this->authorizeSenditShipment($shipment);

        $this->senditService->createDelivery($shipment);

        return back()->with('success', 'Sendit delivery created successfully.');
    }

    public function updateSenditDelivery(Shipment $shipment)
    {
        $this->authorizeSenditShipment($shipment);

        $shipment->forceFill([
            'provider_error' => null,
        ])->save();

        SyncSenditDeliveryUpdateJob::dispatch($shipment->id);

        return back()->with('success', 'Sendit delivery update queued successfully.');
    }

    public function syncSenditStatus(Shipment $shipment)
    {
        $this->authorizeSenditShipment($shipment);

        $this->senditService->fetchDelivery($shipment);

        return back()->with('success', 'Sendit shipment status synced.');
    }

    public function downloadSenditLabel(Request $request, Shipment $shipment)
    {
        $this->authorizeSenditShipment($shipment);

        $format = $request->query('format') === 'thermal' ? 1 : 0;
        $fileUrl = (string) ($shipment->label_url ?? '');

        if ($fileUrl === '') {
            $fileUrl = $this->refreshSenditLabelUrl($shipment, $format);
        }

        abort_unless($fileUrl !== '', 404);

        $fileResponse = Http::get($fileUrl);

        if (! $fileResponse->successful()) {
            $refreshedUrl = $this->refreshSenditLabelUrl($shipment->fresh(), $format);
            abort_unless($refreshedUrl !== '', 404);

            $fileResponse = Http::get($refreshedUrl);
            abort_unless($fileResponse->successful(), 502);
        }

        $filename = ($format === 1 ? 'sendit-thermal-' : 'sendit-a4-').($shipment->external_reference ?: $shipment->id).'.pdf';

        return response($fileResponse->body(), 200, [
            'Content-Type' => $fileResponse->header('Content-Type', 'application/pdf'),
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * Shipment reports: history, status log, failed/returned queues.
     */
    public function reports(Request $request)
    {
        $tab = $request->get('tab', 'history');

        $data = match ($tab) {
            'failed' => [
                'shipments' => Shipment::with('order.customer')
                    ->whereIn('status', [ShipmentStatus::FAILED_DELIVERY, ShipmentStatus::RETURNED])
                    ->orderByDesc('updated_at')
                    ->paginate(20)->withQueryString(),
            ],
            'timeline' => [
                'history' => \App\Modules\Shipping\Models\ShipmentStatusHistory::with(['shipment.order', 'changedByUser'])
                    ->orderByDesc('created_at')
                    ->paginate(30)->withQueryString(),
            ],
            default => [
                'shipments' => Shipment::with(['order.customer', 'shippingMethod'])
                    ->orderByDesc('created_at')
                    ->paginate(20)->withQueryString(),
            ],
        };

        return view('admin.shipping.reports', array_merge($data, compact('tab')));
    }

    private function authorizeSenditShipment(Shipment $shipment): void
    {
        abort_unless($shipment->usesSendit(), 404);
        abort_unless(auth()->user()?->canAny(['shipping.update']), 403);
    }

    private function refreshSenditLabelUrl(Shipment $shipment, int $format): string
    {
        $response = $this->senditService->printLabels([$shipment->external_reference], $format, $shipment->shippingMethod?->shippingCarrier);
        $fileUrl = (string) data_get($response, 'data.fileUrl');

        if ($fileUrl !== '' && $fileUrl !== $shipment->label_url) {
            $shipment->forceFill(['label_url' => $fileUrl])->saveQuietly();
        }

        return $fileUrl;
    }
}
