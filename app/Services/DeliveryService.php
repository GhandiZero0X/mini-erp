<?php

namespace App\Services;

use App\Models\DeliveryModel;
use CodeIgniter\Database\Exceptions\DatabaseException;

class DeliveryService
{
    private DeliveryModel $deliveries;
    private SalesOrderService $orders;
    private array $msg;

    public function __construct(?DeliveryModel $deliveries = null, ?SalesOrderService $orders = null, ?\Config\Sales $cfg = null)
    {
        $this->deliveries = $deliveries ?? new DeliveryModel();
        $this->orders     = $orders     ?? new SalesOrderService();
        $this->msg        = ($cfg ?? config('Sales'))->messages;
    }

    public function listPage(string $search = ''): array
    {
        $rows = $this->deliveries->listPage($search, 10);
        return ['rows' => $rows, 'pager' => $this->deliveries->pager];
    }

    public function find(int $id): ?array
    {
        $dl = $this->deliveries->findById($id);
        if ($dl === null) { return null; }
        $dl['items']     = $this->deliveries->itemsOf($id);
        $dl['total_qty'] = array_sum(array_column($dl['items'], 'quantity_delivered'));
        return $dl;
    }

    public function formData(int $soId): ?array
    {
        return $this->orders->find($soId);
    }

    public function create(int $soId, array $input, int $userId): array
    {
        $so = $this->orders->find($soId);
        if ($so === null)            { return $this->failure('so_not_found', 'not_found'); }
        if (! $so['deliverable'])    { return $this->failure('delivery_not_receivable', 'not_receivable'); }

        $in     = self::clean($input);
        $errors = self::validateFields($in, $so['items'], $this->msg);
        if ($errors !== []) { return $this->invalid($errors); }

        $qtyByItem = [];
        foreach ($so['items'] as $it) {
            $qtyByItem[$it['id']] = (int) ($in['quantities'][$it['id']] ?? 0);
        }

        try {
            $r = $this->deliveries->createFromSo($soId, $in['delivery_date'], $qtyByItem, $userId);
        } catch (DatabaseException $e) {
            log_message('error', 'Gagal menyimpan Delivery: ' . $e->getMessage());
            return $this->failure('save_failed');
        }

        switch ($r['status']) {
            case 'ok':
                $res = $this->success('delivery_created');
                $res['id'] = $r['id'];
                return $res;
            case 'not_found':
                return $this->failure('so_not_found', 'not_found');
            case 'so_not_shippable':
                return $this->failure('delivery_not_receivable', 'not_receivable');
            case 'insufficient_stock':
                return $this->failure(strtr($this->msg['delivery_insufficient_stock'], [
                    ':produk' => (string) ($r['product'] ?? '-'),
                    ':stok'   => (string) ($r['stock']   ?? 0),
                    ':butuh'  => (string) ($r['needed']  ?? 0),
                ]), 'insufficient_stock');
            case 'exceeds':
                $q = [];
                foreach ($qtyByItem as $itemId => $qty) {
                    if ($qty > ($r['remaining'][$itemId] ?? 0)) {
                        $q[$itemId] = self::qtyMessage($this->msg, $r['remaining'][$itemId] ?? 0);
                    }
                }
                return $this->invalid(['quantities' => $q]);
            case 'nothing':
                return $this->invalid(['items' => $this->msg['delivery_nothing']]);
        }
        return $this->failure('save_failed');
    }

    public function cancel(int $id, array $input, int $userId): array
    {
        $reason = trim((string) ($input['cancel_reason'] ?? ''));
        if ($reason === '') { return $this->invalid(['cancel_reason' => $this->msg['cancel_reason_required']]); }
        if (mb_strlen($reason) > 500) { return $this->invalid(['cancel_reason' => $this->msg['cancel_reason_max']]); }

        try {
            $r = $this->deliveries->cancel($id, $userId, $reason);
        } catch (DatabaseException $e) {
            log_message('error', 'Gagal membatalkan Delivery: ' . $e->getMessage());
            return $this->failure('save_failed');
        }

        return match ($r['status']) {
            'ok'         => $this->success('delivery_cancelled'),
            'not_found'  => $this->failure('delivery_not_found', 'not_found'),
            'not_active' => $this->failure('delivery_cancel_not_allowed', 'not_cancellable'),
            default      => $this->failure('save_failed'),
        };
    }

    public static function clean(array $input): array
    {
        $q = [];
        if (is_array($input['quantities'] ?? null)) {
            foreach ($input['quantities'] as $itemId => $v) {
                if (is_scalar($v) && ctype_digit((string) $itemId)) {
                    $q[(int) $itemId] = trim((string) $v);
                }
            }
        }
        return [
            'delivery_date' => trim((string) ($input['delivery_date'] ?? '')),
            'quantities'    => $q,
        ];
    }

    public static function validateFields(array $in, array $soItems, array $msg): array
    {
        $e = [];
        if (! PurchaseOrderService::isValidDate($in['delivery_date'])) {
            $e['delivery_date'] = $msg['delivery_date_invalid'];
        }
        $sum    = 0;
        $errors = [];
        foreach ($soItems as $it) {
            $raw = $in['quantities'][$it['id']] ?? '';
            $ok  = (bool) preg_match('/^(0|[1-9]\d{0,9})$/', $raw) && (int) $raw <= $it['remaining'];
            if (! $ok) {
                $errors[$it['id']] = self::qtyMessage($msg, (int) $it['remaining']);
                continue;
            }
            $sum += (int) $raw;
        }
        if ($errors !== [])       { $e['quantities'] = $errors; }
        elseif ($sum === 0)       { $e['items'] = $msg['delivery_nothing']; }
        return $e;
    }

    private static function qtyMessage(array $msg, int $remaining): string
    {
        return str_replace('{sisa}', (string) $remaining, $msg['delivery_qty_invalid']);
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