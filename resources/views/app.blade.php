<?php
$institution = currentInstitution();
$institutionGroup =
  $institution?->institutionGroup ?? getInstitutionGroupFromDomain();
$logo =
  $institution?->photo ?? $institutionGroup?->institutions()?->first()?->photo;

// dd($institutionGroup->toArray());
?>
<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

    <title>{{ $institutionGroup?->name ?? config('app.name') }}</title>

    <link rel="icon" type="image/x-icon" href="{{ $logo ?? '/favicon.ico' }}?v=2" />

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