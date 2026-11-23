<?php

namespace App\Services;

use App\Models\SalesOrderModel;
use CodeIgniter\Database\Exceptions\DatabaseException;
use Config\Documents;

class SalesOrderService
{
    private const MAX_ROWS = 100;

    private SalesOrderModel $orders;
    private array $msg;

    public function __construct(?SalesOrderModel $orders = null, ?\Config\Sales $cfg = null)
    {
        $this->orders = $orders ?? new SalesOrderModel();
        $this->msg    = ($cfg ?? config('Sales'))->messages;
    }

    public function listPage(string $search = '', string $status = '', bool $shippableOnly = false): array
    {
        if ($shippableOnly) {
            return ['rows' => $this->orders->listShippable($search, 10), 'pager' => $this->orders->pager];
        }
        if (! in_array($status, config('Documents')->soStatuses, true)) { $status = ''; }
        return ['rows' => $this->orders->listPage($search, $status, 10), 'pager' => $this->orders->pager];
    }

    public function find(int $id): ?array
    {
        $so = $this->orders->findById($id);
        if ($so === null) { return null; }

        $items     = $this->orders->itemsOf($id);
        $remaining = 0;
        foreach ($items as &$it) {
            $it['remaining'] = max(0, $it['quantity'] - $it['delivered']);
            $remaining      += $it['remaining'];
        }
        unset($it);

        $so['items']       = $items;
        $so['deliveries']  = $this->orders->deliveriesOf($id);
        $so['deliverable'] = $so['status'] === Documents::SO_OPEN && $remaining > 0;
        return $so;
    }

    public function formData(): array
    {
        return [
            'customers' => $this->orders->customerOptions(),
            'products'  => $this->orders->productOptions(),
        ];
    }

    public function create(array $input, int $userId): array
    {
        $in     = self::clean($input);
        $errors = self::validateFields($in, $this->msg);
        $errors = $this->checkReferences($in, $errors);
        if ($errors !== []) { return $this->invalid($errors); }

        [$header, $items] = self::buildPayload($in, $userId);
        try {
            $saved = $this->orders->createWithItems($header, $items);
        } catch (DatabaseException $e) {
            $errors = $this->checkReferences($in, []);
            if ($errors !== []) { return $this->invalid($errors); }
            log_message('error', 'Gagal menyimpan SO: ' . $e->getMessage());
            return $this->failure('save_failed');
        }

        $res       = $this->success('so_created');
        $res['id'] = $saved['id'];
        return $res;
    }

    public function cancel(int $id, array $input, int $userId): array
    {
        $reason = trim((string) ($input['cancel_reason'] ?? ''));
        if ($reason === '') { return $this->invalid(['cancel_reason' => $this->msg['cancel_reason_required']]); }
        if (mb_strlen($reason) > 500) { return $this->invalid(['cancel_reason' => $this->msg['cancel_reason_max']]); }

        try {
            $status = $this->orders->cancel($id, $userId, $reason);
        } catch (DatabaseException $e) {
            log_message('error', 'Gagal membatalkan SO: ' . $e->getMessage());
            return $this->failure('save_failed');
        }

        return match ($status) {
            'ok'                  => $this->success('so_cancelled'),
            'not_found'           => $this->failure('so_not_found', 'not_found'),
            'has_active_delivery' => $this->failure('so_cancel_has_delivery', 'not_cancellable'),
            'not_cancellable'     => $this->failure('so_cancel_not_allowed', 'not_cancellable'),
            default               => $this->failure('save_failed'),
        };
    }

    // ---------- Pure validators ----------

    public static function clean(array $input): array
    {
        $items = [];
        if (is_array($input['items'] ?? null)) {
            foreach ($input['items'] as $row) {
                if (! is_array($row)) { continue; }
                $items[] = [
                    'product_id' => trim((string) ($row['product_id'] ?? '')),
                    'quantity'   => trim((string) ($row['quantity']   ?? '')),
                    'unit_price' => trim((string) ($row['unit_price'] ?? '')),
                ];
            }
        }
        return [
            'so_date'     => trim((string) ($input['so_date'] ?? '')),
            'customer_id' => (int) ($input['customer_id'] ?? 0),
            'items'       => $items,
        ];
    }

    public static function validateFields(array $in, array $msg): array
    {
        $e = [];
        if (! PurchaseOrderService::isValidDate($in['so_date'])) { $e['so_date'] = $msg['so_date_invalid']; }
        if ($in['customer_id'] <= 0) { $e['customer_id'] = $msg['customer_required']; }
        if ($in['items'] === []) { $e['items'] = $msg['items_required']; return $e; }
        if (count($in['items']) > self::MAX_ROWS) { $e['items'] = $msg['items_too_many']; return $e; }

        $seen      = [];
        $rowErrors = [];
        foreach ($in['items'] as $idx => $row) {
            $rErr = [];
            $pid  = $row['product_id'] === '' ? 0 : (int) $row['product_id'];
            if ($pid <= 0)                 { $rErr['product_id'] = $msg['product_required']; }
            elseif (isset($seen[$pid]))    { $rErr['product_id'] = $msg['product_duplicate']; }
            else                           { $seen[$pid] = true; }

            if (! preg_match('/^[1-9]\d{0,9}$/', $row['quantity'])) {
                $rErr['quantity'] = $msg['quantity_invalid'];
            }
            if (! preg_match('/^\d{1,13}(\.\d{1,2})?$/', $row['unit_price'])) {
                $rErr['unit_price'] = $msg['unit_price_invalid'];
            }
            if ($rErr !== []) { $rowErrors[$idx] = $rErr; }
        }
        if ($rowErrors !== []) { $e['item_rows'] = $rowErrors; }
        return $e;
    }

    public function checkReferences(array $in, array $errors): array
    {
        if ($in['customer_id'] > 0 && ! $this->orders->customerExists($in['customer_id'])) {
            $errors['customer_id'] = $this->msg['customer_not_found'];
        }
        $ids = [];
        foreach ($in['items'] as $r) {
            $pid = (int) $r['product_id'];
            if ($pid > 0) { $ids[] = $pid; }
        }
        $missing = array_diff($ids, $this->orders->existingProductIds($ids));
        if ($missing !== []) {
            $errors['item_rows'] ??= [];
            foreach ($in['items'] as $idx => $r) {
                $pid = (int) $r['product_id'];
                if ($pid > 0 && in_array($pid, $missing, true)) {
                    $errors['item_rows'][$idx]['product_id'] = $this->msg['product_not_found'];
                }
            }
        }
        return $errors;
    }

    public static function buildPayload(array $in, int $userId): array
    {
        $totalCents = 0;
        $items      = [];
        foreach ($in['items'] as $row) {
            $qty        = (int) $row['quantity'];
            $priceCents = self::moneyToCents($row['unit_price']);
            $subCents   = $qty * $priceCents;
            $totalCents += $subCents;
            $items[] = [
                'product_id' => (int) $row['product_id'],
                'quantity'   => $qty,
                'unit_price' => self::centsToMoney($priceCents),
                'subtotal'   => self::centsToMoney($subCents),
            ];
        }
        return [
            [
                'so_date'      => $in['so_date'],
                'customer_id'  => $in['customer_id'],
                'total_amount' => self::centsToMoney($totalCents),
                'created_by'   => $userId,
            ],
            $items,
        ];
    }

    private static function moneyToCents(string $s): int
    {
        $s = str_replace(',', '.', $s);
        if (! preg_match('/^(\d+)(?:\.(\d{1,2}))?$/', $s, $m)) { return 0; }
        return (int) $m[1] * 100 + (int) str_pad($m[2] ?? '0', 2, '0');
    }

    private static function centsToMoney(int $cents): string
    {
        return intdiv($cents, 100) . '.' . str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }

    private function success(string $key): array
    {
        return ['ok' => true, 'code' => 'ok', 'errors' => [], 'message' => $this->msg[$key], 'id' => null];
    }
    private function failure(string $key, string $code = 'failed'): array
    {
        return ['ok' => false, 'code' => $code, 'errors' => [], 'message' => $this->msg[$key], 'id' => null];
    }
    private function invalid(array $errors): array
    {
        return ['ok' => false, 'code' => 'invalid', 'errors' => $errors, 'message' => '', 'id' => null];
    }
}