<!DOCTYPE html>
<html>
<head>
    <title>Invoice</title>
    <style>
        body { font-family: sans-serif; }
        .container { width: 100%; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; }
        .header .company-details { text-align: left; }
        .header .invoice-details { text-align: right; }
        .billing-details { margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .total { text-align: right; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="company-details">
                <h1>Invoice</h1>
                <p>
                    <strong>Edumanager</strong><br>
                    09035316014<br>
                    support@edumanager.com<br>
                    https://edumanager.ng
                </p>
            </div>
            <div class="invoice-details">
                <p>
                    <strong>Invoice Number:</strong> {{ $invoice_number }}<br>
                    <strong>Date:</strong> {{ $invoice_date }}
                </p>
            </div>
        </div>

        <div class="billing-details">
            <h2>Hi:</h2>
            <p>
                <strong style="font-size: 18">{{ $institution_group->name }}</strong><br>
                {{ $institution_group->address }}<br>
                {{ $institution_group->email }}
            </p>
            <p>
                We hope this message finds you well.
            </p>
            <p>
                This is a friendly reminder that your Edumanager subscription fee is due. Timely payment ensures uninterrupted access to all Edumanager features.
            </p>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Institution</th>
                    <th>Description</th>
                    <th>Quantity</th>
                    <th>Unit Price</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $item)
                    <?php $inst = $item['institution']; ?>
                    <tr>
                        <td>{{ $inst->name }}</td>
                        <td>{{ $item['description'] }}</td>
                        <td>{{ $item['quantity'] }}</td>
                        <td>{{ number_format($item['unit_price'], 2) }}</td>
                        <td>{{ number_format($item['amount'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="total">
            <h3>Total: {{ number_format($total_amount, 2) }}</h3>
        </div>

        <div class="footer">
            <p><strong>Payment Information:</strong></p>
            <p>Please make payment to the following bank account:</p>
            <p>
                Bank: GT Bank <br>
                Account Name: Examscholars - Edumanager <br>
                Account Number: 0941655850
            </p>
        </div>
    </div>
</body>
</html>
