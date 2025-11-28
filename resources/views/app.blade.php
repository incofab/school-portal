<?php

$institutionGroup =
  currentInstitution()?->institutionGroup ?? getInstitutionGroupFromDomain();
// dd($institutionGroup->toArray());
?>
<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

    <title>{{config('app.name')}}</title>

    @routes
    @viteReactRefresh
    @vite('resources/js/app.tsx')
    @inertiaHead
  </head>
  <body>
    @inertia
    <script>
        window.AppProps = {
            institutionGroup: @json($institutionGroup),
        };
    </script>
  </body>
</html>