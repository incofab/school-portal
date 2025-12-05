<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Funding Receipt</title>
  <style>
    body { font-family: Arial, sans-serif; padding: 12px; color: #1a1a1a; }
    .container { width: 100%; margin: 0 auto; }
    .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 18px; }
    .brand h1 { margin: 0; font-size: 24px; letter-spacing: 0.5px; }
    .meta { text-align: right; font-size: 13px; line-height: 1.5; }
    .section { margin-bottom: 18px; }
    table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 14px; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background: #f4f4f4; }
    .summary { display: flex; justify-content: space-between; margin-top: 12px; }
    .summary div { font-weight: bold; }
    .note { font-size: 13px; color: #444; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <div class="brand">
        <h1>Funding Receipt</h1>
        <div>{{config('app.name')}}</div>
        <div>{{config('email')}}</div>
        <div>https://edumanager.ng</div>
      </div>
      <div class="meta">
        <div><strong>Receipt No:</strong> {{ $receipt_number }}</div>
        <div><strong>Date:</strong> {{ $receipt_date }}</div>
        <div><strong>Processed By:</strong> {{ $processed_by?->full_name }}</div>
      </div>
    </div>

    <div class="section">
      <div style="font-size: 15px; font-weight: bold;">Received From</div>
      <div style="margin-top: 4px;">
        <div>{{ $institution_group->name }}</div>
        <div>{{ $institution_group->email }}</div>
        <div>{{ $institution_group->address }}</div>
      </div>
    </div>

    <div class="section">
      <table>
        <thead>
          <tr>
            <th>Description</th>
            <th>Reference</th>
            <th>Wallet</th>
            <th>Amount</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>{{ $funding->remark ?: 'Wallet top-up' }}</td>
            <td>{{ $funding->reference }}</td>
            <td style="text-transform: capitalize;">{{ $funding->wallet }}</td>
            <td>NGN{{ number_format($funding->amount, 2) }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="section summary">
      <div>Previous Balance: NGN{{ number_format($funding->previous_balance, 2) }}</div>
      <div>New Balance: NGN{{ number_format($funding->new_balance, 2) }}</div>
    </div>

    <div class="section note">
      <p>This receipt acknowledges the <b>{{ $funding->wallet }} funding</b> recorded on the date above. Please keep this document for your records.</p>
      <p>If you have any questions, contact support@edumanager.com.</p>
    </div>
  </div>
</body>
</html>
