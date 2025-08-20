<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 12px; color: #333; }
        h1 { font-size: 22px; text-align: center; margin-bottom: 5px; }
        .header-info { text-align: center; margin-bottom: 25px; font-size: 14px; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .summary { float: right; width: 280px; border: 1px solid #ddd; padding: 15px; margin-top: 20px; }
        .summary-item { display: flex; justify-content: space-between; margin-bottom: 8px; }
        .summary-item .label { font-weight: bold; }
        .text-right { text-align: right; }
        .item-list { margin: 0; padding-left: 17px; list-style-type: disc; }
        /* Hindari baris ringkasan terpotong di halaman berbeda */
        .summary { page-break-inside: avoid; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <div class="header-info">
        <p>Periode: {{ $startDate }} - {{ $endDate }}</p>
        <p>Dicetak pada: {{ $generatedDate }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID Pesanan</th>
                <th>Tanggal</th>
                <th>Pelanggan</th>
                <th>Produk Dibeli</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($orders as $order)
                <tr>
                    <td>#{{ $order['id'] }}</td>
                    <td>{{ $order['date'] }}</td>
                    <td>{{ $order['customer'] }}</td>
                    <td>
                        <ul class="item-list">
                            @foreach ($order['items'] as $item)
                                <li>{{ $item['quantity'] }}x {{ $item['name'] }}</li>
                            @endforeach
                        </ul>
                    </td>
                    <td class="text-right">Rp {{ number_format($order['total'], 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center;">Tidak ada data penjualan pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="summary">
        <div class="summary-item">
            <span class="label">Total Pesanan Selesai:</span>
            <span>{{ $totalOrders }}</span>
        </div>
        <div class="summary-item">
            <span class="label">Total Pendapatan:</span>
            <span>Rp {{ number_format($totalRevenue, 0, ',', '.') }}</span>
        </div>
    </div>
</body>
</html>