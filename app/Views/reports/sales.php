<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<style>
/* Prefiks "srep-" (Sales Report) */
.srep-head {
    position: relative;
    overflow: hidden;
    border-radius: 22px;
    padding: 1.35rem 1.6rem;
    color: #fff;
    background: linear-gradient(125deg, #4338ca 0%, #6d4aed 55%, #0ea5c6 130%);
    box-shadow: 0 18px 40px -24px rgba(67, 56, 202, .7);
}

.srep-head::before {
    content: "";
    position: absolute;
    width: 220px;
    height: 220px;
    right: -60px;
    top: -100px;
    border-radius: 50%;
    background: rgba(255, 255, 255, .11);
}

.srep-head>* {
    position: relative;
    z-index: 1;
}

.srep-head-ico {
    flex: 0 0 auto;
    width: 48px;
    height: 48px;
    display: grid;
    place-items: center;
    border-radius: 14px;
    background: rgba(255, 255, 255, .2);
    font-size: 1.35rem;
}

.srep-head h2 {
    font-size: 1.3rem;
    font-weight: 800;
    letter-spacing: -.01em;
    margin: 0;
}

.srep-head p {
    margin: .1rem 0 0;
    font-size: .86rem;
    opacity: .88;
}

/* ---------- Toolbar / Filter ---------- */
.srep-bar {
    padding: 1.25rem;
    border-bottom: 1px solid var(--app-line);
    background: #fafbff;
    border-radius: 18px 18px 0 0;
}

.srep-filter {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    align-items: flex-end;
}

.srep-filter-group {
    display: flex;
    flex-direction: column;
    gap: .35rem;
    flex: 1 1 180px;
    max-width: 250px;
}

.srep-filter-group label {
    font-size: .78rem;
    font-weight: 700;
    color: var(--app-muted);
    text-transform: uppercase;
    letter-spacing: .04em;
    margin: 0;
}

.srep-filter-group .form-control {
    border-radius: 10px;
    border-color: #dfe3ee;
    background: #fff;
}

/* ---------- Tabel ---------- */
.srep-table td {
    vertical-align: middle;
}

.srep-code {
    display: inline-block;
    padding: .2rem .55rem;
    border-radius: 8px;
    background: #f1f3f9;
    color: #3b4358;
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    font-size: .8rem;
    font-weight: 600;
    white-space: nowrap;
}

.srep-date {
    font-size: .85rem;
    color: var(--app-muted);
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}

.srep-customer {
    font-weight: 600;
    overflow-wrap: anywhere;
}

.srep-amount {
    font-weight: 700;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}

/* ---------- Ringkasan Total ---------- */
.srep-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.25rem 1.5rem;
    background: #fff;
    border-top: 1px solid var(--app-line);
    border-radius: 0 0 18px 18px;
}

.srep-footer-label {
    font-size: .95rem;
    font-weight: 700;
    color: var(--app-muted);
    text-transform: uppercase;
    letter-spacing: .04em;
}

.srep-footer-total {
    font-size: 1.25rem;
    font-weight: 800;
    color: var(--app-ink);
    font-variant-numeric: tabular-nums;
}

/* ---------- Kosong ---------- */
.srep-empty {
    padding: 3.2rem 1rem;
    text-align: center;
}

.srep-empty-ico {
    width: 60px;
    height: 60px;
    display: grid;
    place-items: center;
    margin: 0 auto 1rem;
    border-radius: 18px;
    background: #eef0ff;
    color: #4338ca;
    font-size: 1.6rem;
}

.srep-empty h3 {
    font-size: 1.05rem;
    font-weight: 800;
    margin-bottom: .25rem;
}

.srep-empty p {
    color: var(--app-muted);
    font-size: .88rem;
    margin-bottom: 1rem;
}

/* ---------- Ponsel ---------- */
@media (max-width: 767.98px) {
    .srep-head {
        padding: 1.1rem;
    }

    .srep-filter {
        flex-direction: column;
        align-items: stretch;
    }

    .srep-filter-group {
        max-width: 100%;
    }

    .srep-table,
    .srep-table tbody,
    .srep-table tr,
    .srep-table td {
        display: block;
        width: 100%;
    }

    .srep-table thead {
        display: none;
    }

    .srep-table tbody tr {
        padding: .9rem 1.1rem;
        border-bottom: 1px solid var(--app-line);
    }

    .srep-table tbody tr:last-child {
        border-bottom: 0;
    }

    .srep-table tbody td {
        padding: .3rem 0;
        border: 0;
        text-align: left !important;
    }

    .srep-table td[data-th] {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
    }

    .srep-table td[data-th]::before {
        content: attr(data-th);
        color: var(--app-muted);
        font-size: .76rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .05em;
    }

    .srep-footer {
        flex-direction: column;
        gap: .5rem;
        text-align: center;
    }
}
</style>

<!-- Header -->
<div class="srep-head d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div class="d-flex align-items-center gap-3">
        <span class="srep-head-ico" aria-hidden="true"><i class="bi bi-graph-up-arrow"></i></span>
        <div>
            <h2>Laporan Penjualan</h2>
            <p>Rangkuman data transaksi penjualan berdasarkan periode.</p>
        </div>
    </div>
</div>

<div class="mb-3"></div>

<div class="card overflow-hidden">
    <!-- Toolbar / Filter -->
    <div class="srep-bar">
        <form method="get" action="<?= site_url('reports/sales') ?>" class="srep-filter">
            <div class="srep-filter-group">
                <label for="start">Dari Tanggal</label>
                <input type="date" id="start" name="start" class="form-control" value="<?= esc($filter['start']) ?>">
            </div>

            <div class="srep-filter-group">
                <label for="end">Sampai Tanggal</label>
                <input type="date" id="end" name="end" class="form-control" value="<?= esc($filter['end']) ?>">
            </div>

            <div class="srep-filter-group" style="flex: 0 0 auto; min-width: 100px;">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-funnel me-1"></i>Tampilkan
                </button>
            </div>
        </form>
    </div>

    <?php if (isset($errors['period'])): ?>
    <div class="alert alert-danger rounded-0 border-0 m-0 py-2 text-center">
        <i class="bi bi-exclamation-triangle me-1"></i><?= esc($errors['period']) ?>
    </div>
    <?php endif; ?>

    <!-- Tabel -->
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 srep-table">
            <thead>
                <tr>
                    <th style="width: 150px;">Nomor SO</th>
                    <th style="width: 150px;">Tanggal</th>
                    <th>Customer</th>
                    <th style="width: 150px;">Status</th>
                    <th class="text-end" style="width: 180px;">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($rows === []): ?>
                <tr>
                    <td colspan="5" class="p-0">
                        <div class="srep-empty">
                            <div class="srep-empty-ico"><i class="bi bi-receipt"></i></div>
                            <h3>Tidak Ada Data</h3>
                            <p>Tidak ada transaksi penjualan pada periode yang Anda pilih.</p>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($rows as $r): ?>
                <tr>
                    <td><span class="srep-code"><?= esc($r['so_number']) ?></span></td>
                    <td><span class="srep-date"><?= esc(format_date($r['so_date'])) ?></span></td>
                    <td>
                        <div class="srep-customer"><?= esc($r['customer_name']) ?></div>
                    </td>
                    <td data-th="Status"><?= status_badge($r['status']) ?></td>
                    <td class="text-end" data-th="Total">
                        <span class="srep-amount"><?= esc(format_rupiah($r['total_amount'])) ?></span>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Ringkasan Total -->
    <?php if ($rows !== []): ?>
    <div class="srep-footer">
        <span class="srep-footer-label">Total Penjualan</span>
        <span class="srep-footer-total"><?= esc(format_rupiah($total)) ?></span>
    </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>