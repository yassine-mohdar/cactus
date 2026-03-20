@php
    $themeStylesheet = $adminThemeStylesheet ?? 'resources/themes/nino-v2/app.css';
    $themeScriptEntries = $adminThemeScriptEntries ?? ['resources/js/app.js'];
@endphp

<link rel="stylesheet" href="{{ \Illuminate\Support\Facades\Vite::asset($themeStylesheet) }}">
@vite($themeScriptEntries)
