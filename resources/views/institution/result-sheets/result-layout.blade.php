<?php
$svgCode = "<svg xmlns='http://www.w3.org/2000/svg' width='140' height='100' opacity='0.08' viewBox='0 0 100 100' transform='rotate(45)'><text x='0' y='50' font-size='18' fill='%23000'>{$institution->name}</text></svg>";

$svgCode = 'data:image/svg+xml;base64,' . base64_encode($svgCode);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Result sheet</title>
    <style ></style>
    <style>
        body {
          background-image: url('{{$svgCode}}');
          background-repeat: repeat;
        }
        .avartar{
            width: 100px;
            height: 100px;
            border-radius: 50%;
        }
        .avartar > img{
            width: 100%;
        }
        .vertical-flex {
            display: flex;
            flex-direction: column;
        }
        .horizontal-flex {
            display: flex;
            flex-direction: row;
        }
    </style>
    <link rel="stylesheet" href="{{asset('style/result-sheet.css')}}" type="text/css">
</head>
<body>
  @yield('content')
</body>
</html>