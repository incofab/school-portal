<?php
$amountWithCharge = \App\Core\MonnifyHelper::applyCharge(
  $paymentReference->amount
); ?>
<html>
<head>
  <script
    type="text/javascript"
    src="https://sdk.monnify.com/plugin/monnify.js"
  ></script>
  <script>
    console.log("Monnify SDK loaded", @json(config('app.debug')));
    let paymentCompleted = false;

    function payWithMonnify() {
      MonnifySDK.initialize({
        amount: @json($amountWithCharge),
        currency: "NGN",
        reference: @json($paymentReference->reference),
        customerFullName: @json($paymentReference->user->full_name),
        customerEmail: @json($paymentReference->user->email),
        apiKey: @json(config('services.monnify.public')),
        contractCode: @json(config('services.monnify.contract-code')),
        paymentDescription: @json($paymentReference->purpose->value),
        isTestMode: @json(config('app.debug')),
        // metadata: {
        //   name: "Damilare",
        //   age: 45,
        // },
        // incomeSplitConfig: [
        //   {
        //     subAccountCode: "MFY_SUB_342113621921",
        //     feePercentage: 50,
        //     splitAmount: 1900,
        //     feeBearer: true,
        //   },
        //   {
        //     subAccountCode: "MFY_SUB_342113621922",
        //     feePercentage: 50,
        //     splitAmount: 2100,
        //     feeBearer: true,
        //   },
        // ],
        onLoadStart: () => {
          console.log("loading has started");
        },
        onLoadComplete: () => {
          console.log("SDK is UP");
        },
        onComplete: function (response) {
          //Implement what happens when the transaction is completed.
          console.log(response);
          paymentCompleted = true;
          window.location.href = @json(route('monnify.callback', ['reference' => $paymentReference->reference]));
        },
        onClose: function (data) {
          //Implement what should happen when the modal is closed here
          console.log('On Close called', data);
          if (!paymentCompleted) {
            window.location.href = @json($paymentReference->redirect_url ?? route('home'));
          }
        },
      });
    }
    window.onload = function () {
      payWithMonnify();
    };
  </script>
</head>
<body>
</body>
</html>
