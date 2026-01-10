<html>
<head>
  <script
    type="text/javascript"
    src="https://sdk.monnify.com/plugin/monnify.js"
  ></script>
  <script>
    console.log("Monnify SDK loaded", <?= json_encode(config('app.debug')) ?>);
    function payWithMonnify() {
      MonnifySDK.initialize({
        amount: <?= $paymentReference->amount ?>,
        currency: "NGN",
        reference: "{{ $paymentReference->reference }}",
        customerFullName: "{{ $paymentReference->user->full_name }}",
        customerEmail: "{{ $paymentReference->user->email }}",
        apiKey: "{{ config('services.monnify.secret') }}",
        contractCode: "{{ config('services.monnify.contract-code') }}",
        paymentDescription: "{{ $paymentReference->purpose }}",
        isTestMode: <?= json_encode(config('app.debug')) ?>,
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
        },
        onClose: function (data) {
          //Implement what should happen when the modal is closed here
          console.log('On Close called', data);
          window.location.href = "{{ route('monnify.callback', ['reference' => $paymentReference->reference]) }}";
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