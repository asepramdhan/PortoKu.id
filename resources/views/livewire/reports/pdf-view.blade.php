<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Laporan Keuangan</title>
  <style>
    body {
      font-family: 'Helvetica', 'Arial', sans-serif;
      font-size: 12px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    th,
    td {
      border: 1px solid #ddd;
      padding: 8px;
      text-align: left;
    }

    th {
      background-color: #f2f2f2;
    }

    h1 {
      text-align: center;
    }

  </style>
</head>
<body>
  <h1>Laporan Keuangan</h1>
  <p><strong>Periode:</strong> {{ $startDate->format('d M Y') }} - {{ $endDate->format('d M Y') }}</p>

  <table>
    <thead>
      <tr>
        <th>Tanggal</th>
        <th>Tipe</th>
        <th>Kategori/Aset</th>
        <th>Jumlah (IDR)</th>
        <th>Catatan</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($entries as $entry)
      <tr>
        <td>{{ $entry->transaction_date->format('Y-m-d') }}</td>
        <td>{{ \Illuminate\Support\Str::title($entry->type) }}</td>
        <td>{{ $entry->asset->name ?? $entry->category }}</td>
        <td>{{ number_format($entry->amount, 0, ',', '.') }}</td>
        <td>{{ $entry->notes }}</td>
      </tr>
      @empty
      <tr>
        <td colspan="5" style="text-align: center;">Tidak ada data pada rentang waktu ini.</td>
      </tr>
      @endforelse
    </tbody>
  </table>
</body>
</html>
