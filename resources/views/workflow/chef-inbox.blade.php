<!doctype html>
<html lang="fr">
@php
    $serviceSummary = $serviceSummary ?? [];
@endphp
<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <title>Chef de service | ANBG</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        i[class*='fa-'] {
            display: inline-block;
            width: 1.14em;
            height: 1.14em;
            vertical-align: middle;
            color: currentColor;
            flex-shrink: 0;
            font-size: inherit;
            line-height: 1;
            background-color: currentColor;
            -webkit-mask-repeat: no-repeat;
            mask-repeat: no-repeat;
            -webkit-mask-position: center;
            mask-position: center;
            -webkit-mask-size: contain;
            mask-size: contain;
        }
        i[class*='fa-']::before {
            content: '';
            display: block;
            width: 100%;
            height: 100%;
            background-color: currentColor;
            -webkit-mask-repeat: no-repeat;
            mask-repeat: no-repeat;
            -webkit-mask-position: center;
            mask-position: center;
            -webkit-mask-size: contain;
            mask-size: contain;
        }
        .fa-chart-line::before, .fa-chart-simple::before { -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M4 19h16v2H2V4h2v15Zm2.7-4.3 3.5-3.5 2.6 2.6 4.5-5.3 1.5 1.3-5.9 7-2.7-2.7-2.1 2.1-1.4-1.5Z'/%3E%3C/svg%3E"); mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M4 19h16v2H2V4h2v15Zm2.7-4.3 3.5-3.5 2.6 2.6 4.5-5.3 1.5 1.3-5.9 7-2.7-2.7-2.1 2.1-1.4-1.5Z'/%3E%3C/svg%3E"); }
        .fa-user::before { -webkit-mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 12a5 5 0 1 0 0-10 5 5 0 0 0 0 10Zm0 2c-4.4 0-8 2.7-8 6v1h16v-1c0-3.3-3.6-6-8-6Z'/%3E%3C/svg%3E\"); mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 12a5 5 0 1 0 0-10 5 5 0 0 0 0 10Zm0 2c-4.4 0-8 2.7-8 6v1h16v-1c0-3.3-3.6-6-8-6Z'/%3E%3C/svg%3E\"); }
        .fa-right-from-bracket::before { -webkit-mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M10 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h5v-2H5V5h5V3Zm9.7 9-4.2-4.2-1.4 1.4 1.8 1.8H9v2h6.9l-1.8 1.8 1.4 1.4 4.2-4.2Z'/%3E%3C/svg%3E\"); mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M10 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h5v-2H5V5h5V3Zm9.7 9-4.2-4.2-1.4 1.4 1.8 1.8H9v2h6.9l-1.8 1.8 1.4 1.4 4.2-4.2Z'/%3E%3C/svg%3E\"); }
        .fa-briefcase::before { -webkit-mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M9 4h6a2 2 0 0 1 2 2v1h3a2 2 0 0 1 2 2v8a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3V9a2 2 0 0 1 2-2h3V6a2 2 0 0 1 2-2Zm0 3h6V6H9v1Z'/%3E%3C/svg%3E\"); mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M9 4h6a2 2 0 0 1 2 2v1h3a2 2 0 0 1 2 2v8a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3V9a2 2 0 0 1 2-2h3V6a2 2 0 0 1 2-2Zm0 3h6V6H9v1Z'/%3E%3C/svg%3E\"); }
        .fa-clock::before, .fa-hourglass-half::before { -webkit-mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm1 5h-2v6l5 3 1-1.7-4-2.3V7Z'/%3E%3C/svg%3E\"); mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm1 5h-2v6l5 3 1-1.7-4-2.3V7Z'/%3E%3C/svg%3E\"); }
        .fa-users::before { -webkit-mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M9 11a4 4 0 1 0-3.999-4A4 4 0 0 0 9 11Zm6 1a3 3 0 1 0-2.999-3A3 3 0 0 0 15 12Zm-6 2c-3.3 0-6 2-6 4.5V21h12v-2.5C15 16 12.3 14 9 14Zm6 .5c-.8 0-1.6.1-2.3.4 1.4.9 2.3 2.1 2.3 3.6V21h6v-1.8c0-2.6-2.7-4.7-6-4.7Z'/%3E%3C/svg%3E\"); mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M9 11a4 4 0 1 0-3.999-4A4 4 0 0 0 9 11Zm6 1a3 3 0 1 0-2.999-3A3 3 0 0 0 15 12Zm-6 2c-3.3 0-6 2-6 4.5V21h12v-2.5C15 16 12.3 14 9 14Zm6 .5c-.8 0-1.6.1-2.3.4 1.4.9 2.3 2.1 2.3 3.6V21h6v-1.8c0-2.6-2.7-4.7-6-4.7Z'/%3E%3C/svg%3E\"); }
        .fa-reply::before { -webkit-mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M10 7 3 12l7 5v-3h3c3.3 0 5.4 1.1 8 4-1-6-4-9-9-9h-2V7Z'/%3E%3C/svg%3E\"); mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M10 7 3 12l7 5v-3h3c3.3 0 5.4 1.1 8 4-1-6-4-9-9-9h-2V7Z'/%3E%3C/svg%3E\"); }
        .fa-circle-check::before { -webkit-mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2Zm-1.2 14.2-4-4 1.4-1.4 2.6 2.6 5-5 1.4 1.4Z'/%3E%3C/svg%3E\"); mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2Zm-1.2 14.2-4-4 1.4-1.4 2.6 2.6 5-5 1.4 1.4Z'/%3E%3C/svg%3E\"); }
        .fa-circle-exclamation::before, .fa-circle-info::before { -webkit-mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2Zm1 13h-2v-5h2Zm0-7h-2V6h2Z'/%3E%3C/svg%3E\"); mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2Zm1 13h-2v-5h2Zm0-7h-2V6h2Z'/%3E%3C/svg%3E\"); }
        .fa-magnifying-glass::before { -webkit-mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M10 2a8 8 0 1 0 4.9 14.3l4.4 4.4 1.4-1.4-4.4-4.4A8 8 0 0 0 10 2Zm0 2a6 6 0 1 1 0 12 6 6 0 0 1 0-12Z'/%3E%3C/svg%3E\"); mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M10 2a8 8 0 1 0 4.9 14.3l4.4 4.4 1.4-1.4-4.4-4.4A8 8 0 0 0 10 2Zm0 2a6 6 0 1 1 0 12 6 6 0 0 1 0-12Z'/%3E%3C/svg%3E\"); }
        .fa-filter::before { -webkit-mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M3 5h18l-7 8v5l-4 2v-7L3 5Z'/%3E%3C/svg%3E\"); mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M3 5h18l-7 8v5l-4 2v-7L3 5Z'/%3E%3C/svg%3E\"); }
        .fa-inbox::before { -webkit-mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M5 3h14l3 9v7a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-7l3-9Zm1.4 2L4.1 12H9l1 2h4l1-2h4.9L17.6 5H6.4Z'/%3E%3C/svg%3E\"); mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M5 3h14l3 9v7a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-7l3-9Zm1.4 2L4.1 12H9l1 2h4l1-2h4.9L17.6 5H6.4Z'/%3E%3C/svg%3E\"); }
        .fa-eye::before { -webkit-mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 5C5.6 5 2 12 2 12s3.6 7 10 7 10-7 10-7-3.6-7-10-7Zm0 11a4 4 0 1 1 0-8 4 4 0 0 1 0 8Z'/%3E%3C/svg%3E\"); mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 5C5.6 5 2 12 2 12s3.6 7 10 7 10-7 10-7-3.6-7-10-7Zm0 11a4 4 0 1 1 0-8 4 4 0 0 1 0 8Z'/%3E%3C/svg%3E\"); }
        .fa-eye-slash::before { -webkit-mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='m3.3 2 18.7 18.7-1.4 1.4-4.1-4.1A11.7 11.7 0 0 1 12 19C5.6 19 2 12 2 12a19 19 0 0 1 4.4-5.3L1.9 3.4 3.3 2Zm6.1 6.1A4 4 0 0 0 12 16c.7 0 1.4-.2 2-.5L9.4 8.1ZM12 5c6.4 0 10 7 10 7a18.9 18.9 0 0 1-4.1 5.1l-2.2-2.2A4 4 0 0 0 9.1 8.3L7.5 6.7A10.8 10.8 0 0 1 12 5Z'/%3E%3C/svg%3E\"); mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='m3.3 2 18.7 18.7-1.4 1.4-4.1-4.1A11.7 11.7 0 0 1 12 19C5.6 19 2 12 2 12a19 19 0 0 1 4.4-5.3L1.9 3.4 3.3 2Zm6.1 6.1A4 4 0 0 0 12 16c.7 0 1.4-.2 2-.5L9.4 8.1ZM12 5c6.4 0 10 7 10 7a18.9 18.9 0 0 1-4.1 5.1l-2.2-2.2A4 4 0 0 0 9.1 8.3L7.5 6.7A10.8 10.8 0 0 1 12 5Z'/%3E%3C/svg%3E\"); }
        .fa-file-lines::before { -webkit-mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M6 2h8l4 4v16H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2Zm7 1.5V7h3.5L13 3.5ZM8 11h8v2H8v-2Zm0 4h8v2H8v-2Z'/%3E%3C/svg%3E\"); mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M6 2h8l4 4v16H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2Zm7 1.5V7h3.5L13 3.5ZM8 11h8v2H8v-2Zm0 4h8v2H8v-2Z'/%3E%3C/svg%3E\"); }
        .fa-paperclip::before { -webkit-mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M8.5 12.5 13 8a3 3 0 1 1 4.2 4.2l-6 6a5 5 0 1 1-7.1-7.1l6.3-6.3 1.4 1.4-6.3 6.3a3 3 0 1 0 4.2 4.2l6-6a1 1 0 1 0-1.4-1.4l-4.5 4.5-1.4-1.4Z'/%3E%3C/svg%3E\"); mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M8.5 12.5 13 8a3 3 0 1 1 4.2 4.2l-6 6a5 5 0 1 1-7.1-7.1l6.3-6.3 1.4 1.4-6.3 6.3a3 3 0 1 0 4.2 4.2l6-6a1 1 0 1 0-1.4-1.4l-4.5 4.5-1.4-1.4Z'/%3E%3C/svg%3E\"); }
        .fa-user-check::before { -webkit-mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M9 11a4 4 0 1 0-4-4 4 4 0 0 0 4 4Zm0 2c-3.3 0-6 2-6 4.5V20h8.5a6 6 0 0 1-.5-2.5c0-1.7.7-3.2 1.8-4.3A8.8 8.8 0 0 0 9 13Zm8.2 1.3-3.4 3.4-1.6-1.6-1.4 1.4 3 3 4.8-4.8-1.4-1.4Z'/%3E%3C/svg%3E\"); mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M9 11a4 4 0 1 0-4-4 4 4 0 0 0 4 4Zm0 2c-3.3 0-6 2-6 4.5V20h8.5a6 6 0 0 1-.5-2.5c0-1.7.7-3.2 1.8-4.3A8.8 8.8 0 0 0 9 13Zm8.2 1.3-3.4 3.4-1.6-1.6-1.4 1.4 3 3 4.8-4.8-1.4-1.4Z'/%3E%3C/svg%3E\"); }
        .fa-user-plus::before { -webkit-mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M8.5 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm0 2C5.5 13 3 14.7 3 17v2h9v-2c0-1.2.4-2.3 1.1-3.3A9.6 9.6 0 0 0 8.5 13ZM18 8h-2V6h-2v2h-2v2h2v2h2v-2h2V8Z'/%3E%3C/svg%3E\"); mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M8.5 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm0 2C5.5 13 3 14.7 3 17v2h9v-2c0-1.2.4-2.3 1.1-3.3A9.6 9.6 0 0 0 8.5 13ZM18 8h-2V6h-2v2h-2v2h2v2h2v-2h2V8Z'/%3E%3C/svg%3E\"); }
        .fa-cloud-arrow-up::before { -webkit-mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M7 18h10a4 4 0 0 0 .4-8A6 6 0 0 0 6 11a4 4 0 0 0 1 7Zm6-6V8h-2v4H8l4 4 4-4h-3Z'/%3E%3C/svg%3E\"); mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M7 18h10a4 4 0 0 0 .4-8A6 6 0 0 0 6 11a4 4 0 0 0 1 7Zm6-6V8h-2v4H8l4 4 4-4h-3Z'/%3E%3C/svg%3E\"); }
        .fa-envelope-circle-check::before { -webkit-mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M3 6h18v12H3V6Zm2 2v1l7 4 7-4V8l-7 4-7-4Zm11.2 5.8-1.4 1.4-1.3-1.3-1.4 1.4 2.7 2.7 4.8-4.8-1.4-1.4-3.4 3.4Z'/%3E%3C/svg%3E\"); mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M3 6h18v12H3V6Zm2 2v1l7 4 7-4V8l-7 4-7-4Zm11.2 5.8-1.4 1.4-1.3-1.3-1.4 1.4 2.7 2.7 4.8-4.8-1.4-1.4-3.4 3.4Z'/%3E%3C/svg%3E\"); }
        .fa-xmark::before { -webkit-mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='m6.4 5 5.6 5.6L17.6 5 19 6.4 13.4 12 19 17.6 17.6 19 12 13.4 6.4 19 5 17.6 10.6 12 5 6.4 6.4 5Z'/%3E%3C/svg%3E\"); mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='m6.4 5 5.6 5.6L17.6 5 19 6.4 13.4 12 19 17.6 17.6 19 12 13.4 6.4 19 5 17.6 10.6 12 5 6.4 6.4 5Z'/%3E%3C/svg%3E\"); }
        .fa-user-xmark::before { -webkit-mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M9 11a4 4 0 1 0-4-4 4 4 0 0 0 4 4Zm0 2c-3.3 0-6 2-6 4.5V20h9v-2.5a4.8 4.8 0 0 1 1.3-3.2A9.7 9.7 0 0 0 9 13Zm6.4 1L18 16.6l2.6-2.6 1.4 1.4-2.6 2.6 2.6 2.6-1.4 1.4L18 19.4 15.4 22 14 20.6l2.6-2.6-2.6-2.6 1.4-1.4Z'/%3E%3C/svg%3E\"); mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M9 11a4 4 0 1 0-4-4 4 4 0 0 0 4 4Zm0 2c-3.3 0-6 2-6 4.5V20h9v-2.5a4.8 4.8 0 0 1 1.3-3.2A9.7 9.7 0 0 0 9 13Zm6.4 1L18 16.6l2.6-2.6 1.4 1.4-2.6 2.6 2.6 2.6-1.4 1.4L18 19.4 15.4 22 14 20.6l2.6-2.6-2.6-2.6 1.4-1.4Z'/%3E%3C/svg%3E\"); }
        .fa-lock::before { -webkit-mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M7 10V8a5 5 0 0 1 10 0v2h1a2 2 0 0 1 2 2v8H4v-8a2 2 0 0 1 2-2h1Zm2 0h6V8a3 3 0 0 0-6 0v2Z'/%3E%3C/svg%3E\"); mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M7 10V8a5 5 0 0 1 10 0v2h1a2 2 0 0 1 2 2v8H4v-8a2 2 0 0 1 2-2h1Zm2 0h6V8a3 3 0 0 0-6 0v2Z'/%3E%3C/svg%3E\"); }
        .fa-user-tie::before { -webkit-mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 12a4.5 4.5 0 1 0-4.5-4.5A4.5 4.5 0 0 0 12 12Zm-5 8v-1c0-2.8 2.8-5 5-5s5 2.2 5 5v1H7Zm4-8 1 2 1-2h-2Zm1 3-1.5 5h3L12 15Z'/%3E%3C/svg%3E\"); mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 12a4.5 4.5 0 1 0-4.5-4.5A4.5 4.5 0 0 0 12 12Zm-5 8v-1c0-2.8 2.8-5 5-5s5 2.2 5 5v1H7Zm4-8 1 2 1-2h-2Zm1 3-1.5 5h3L12 15Z'/%3E%3C/svg%3E\"); }
        .fa-clock, .fa-hourglass-half { -webkit-mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm1 5h-2v6l5 3 1-1.7-4-2.3V7Z'/%3E%3C/svg%3E\"); mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm1 5h-2v6l5 3 1-1.7-4-2.3V7Z'/%3E%3C/svg%3E\"); }
        .fa-users { -webkit-mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M9 11a4 4 0 1 0-3.999-4A4 4 0 0 0 9 11Zm6 1a3 3 0 1 0-2.999-3A3 3 0 0 0 15 12Zm-6 2c-3.3 0-6 2-6 4.5V21h12v-2.5C15 16 12.3 14 9 14Zm6 .5c-.8 0-1.6.1-2.3.4 1.4.9 2.3 2.1 2.3 3.6V21h6v-1.8c0-2.6-2.7-4.7-6-4.7Z'/%3E%3C/svg%3E\"); mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M9 11a4 4 0 1 0-3.999-4A4 4 0 0 0 9 11Zm6 1a3 3 0 1 0-2.999-3A3 3 0 0 0 15 12Zm-6 2c-3.3 0-6 2-6 4.5V21h12v-2.5C15 16 12.3 14 9 14Zm6 .5c-.8 0-1.6.1-2.3.4 1.4.9 2.3 2.1 2.3 3.6V21h6v-1.8c0-2.6-2.7-4.7-6-4.7Z'/%3E%3C/svg%3E\"); }
        .fa-reply { -webkit-mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M10 7 3 12l7 5v-3h3c3.3 0 5.4 1.1 8 4-1-6-4-9-9-9h-2V7Z'/%3E%3C/svg%3E\"); mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M10 7 3 12l7 5v-3h3c3.3 0 5.4 1.1 8 4-1-6-4-9-9-9h-2V7Z'/%3E%3C/svg%3E\"); }
        .fa-circle-check { -webkit-mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2Zm-1.2 14.2-4-4 1.4-1.4 2.6 2.6 5-5 1.4 1.4Z'/%3E%3C/svg%3E\"); mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2Zm-1.2 14.2-4-4 1.4-1.4 2.6 2.6 5-5 1.4 1.4Z'/%3E%3C/svg%3E\"); }
        .fa-circle-exclamation { -webkit-mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2Zm1 13h-2v-5h2Zm0-7h-2V6h2Z'/%3E%3C/svg%3E\"); mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2Zm1 13h-2v-5h2Zm0-7h-2V6h2Z'/%3E%3C/svg%3E\"); }
        .fa-magnifying-glass { -webkit-mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M10 2a8 8 0 1 0 4.9 14.3l4.4 4.4 1.4-1.4-4.4-4.4A8 8 0 0 0 10 2Zm0 2a6 6 0 1 1 0 12 6 6 0 0 1 0-12Z'/%3E%3C/svg%3E\"); mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M10 2a8 8 0 1 0 4.9 14.3l4.4 4.4 1.4-1.4-4.4-4.4A8 8 0 0 0 10 2Zm0 2a6 6 0 1 1 0 12 6 6 0 0 1 0-12Z'/%3E%3C/svg%3E\"); }
        .fa-filter { -webkit-mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M3 5h18l-7 8v5l-4 2v-7L3 5Z'/%3E%3C/svg%3E\"); mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M3 5h18l-7 8v5l-4 2v-7L3 5Z'/%3E%3C/svg%3E\"); }
        .fa-inbox { -webkit-mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M5 3h14l3 9v7a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-7l3-9Zm1.4 2L4.1 12H9l1 2h4l1-2h4.9L17.6 5H6.4Z'/%3E%3C/svg%3E\"); mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M5 3h14l3 9v7a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-7l3-9Zm1.4 2L4.1 12H9l1 2h4l1-2h4.9L17.6 5H6.4Z'/%3E%3C/svg%3E\"); }
        .fa-file-lines { -webkit-mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M6 2h8l4 4v16H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2Zm7 1.5V7h3.5L13 3.5ZM8 11h8v2H8v-2Zm0 4h8v2H8v-2Z'/%3E%3C/svg%3E\"); mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M6 2h8l4 4v16H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2Zm7 1.5V7h3.5L13 3.5ZM8 11h8v2H8v-2Zm0 4h8v2H8v-2Z'/%3E%3C/svg%3E\"); }
        .fa-paperclip { -webkit-mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M8.5 12.5 13 8a3 3 0 1 1 4.2 4.2l-6 6a5 5 0 1 1-7.1-7.1l6.3-6.3 1.4 1.4-6.3 6.3a3 3 0 1 0 4.2 4.2l6-6a1 1 0 1 0-1.4-1.4l-4.5 4.5-1.4-1.4Z'/%3E%3C/svg%3E\"); mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M8.5 12.5 13 8a3 3 0 1 1 4.2 4.2l-6 6a5 5 0 1 1-7.1-7.1l6.3-6.3 1.4 1.4-6.3 6.3a3 3 0 1 0 4.2 4.2l6-6a1 1 0 1 0-1.4-1.4l-4.5 4.5-1.4-1.4Z'/%3E%3C/svg%3E\"); }
        .fa-user-check { -webkit-mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M9 11a4 4 0 1 0-4-4 4 4 0 0 0 4 4Zm0 2c-3.3 0-6 2-6 4.5V20h8.5a6 6 0 0 1-.5-2.5c0-1.7.7-3.2 1.8-4.3A8.8 8.8 0 0 0 9 13Zm8.2 1.3-3.4 3.4-1.6-1.6-1.4 1.4 3 3 4.8-4.8-1.4-1.4Z'/%3E%3C/svg%3E\"); mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M9 11a4 4 0 1 0-4-4 4 4 0 0 0 4 4Zm0 2c-3.3 0-6 2-6 4.5V20h8.5a6 6 0 0 1-.5-2.5c0-1.7.7-3.2 1.8-4.3A8.8 8.8 0 0 0 9 13Zm8.2 1.3-3.4 3.4-1.6-1.6-1.4 1.4 3 3 4.8-4.8-1.4-1.4Z'/%3E%3C/svg%3E\"); }
        .fa-user-plus { -webkit-mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M8.5 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm0 2C5.5 13 3 14.7 3 17v2h9v-2c0-1.2.4-2.3 1.1-3.3A9.6 9.6 0 0 0 8.5 13ZM18 8h-2V6h-2v2h-2v2h2v2h2v-2h2V8Z'/%3E%3C/svg%3E\"); mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M8.5 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm0 2C5.5 13 3 14.7 3 17v2h9v-2c0-1.2.4-2.3 1.1-3.3A9.6 9.6 0 0 0 8.5 13ZM18 8h-2V6h-2v2h-2v2h2v2h2v-2h2V8Z'/%3E%3C/svg%3E\"); }
        .fa-cloud-arrow-up { -webkit-mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M7 18h10a4 4 0 0 0 .4-8A6 6 0 0 0 6 11a4 4 0 0 0 1 7Zm6-6V8h-2v4H8l4 4 4-4h-3Z'/%3E%3C/svg%3E\"); mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M7 18h10a4 4 0 0 0 .4-8A6 6 0 0 0 6 11a4 4 0 0 0 1 7Zm6-6V8h-2v4H8l4 4 4-4h-3Z'/%3E%3C/svg%3E\"); }
        .fa-envelope-circle-check { -webkit-mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M3 6h18v12H3V6Zm2 2v1l7 4 7-4V8l-7 4-7-4Zm11.2 5.8-1.4 1.4-1.3-1.3-1.4 1.4 2.7 2.7 4.8-4.8-1.4-1.4-3.4 3.4Z'/%3E%3C/svg%3E\"); mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M3 6h18v12H3V6Zm2 2v1l7 4 7-4V8l-7 4-7-4Zm11.2 5.8-1.4 1.4-1.3-1.3-1.4 1.4 2.7 2.7 4.8-4.8-1.4-1.4-3.4 3.4Z'/%3E%3C/svg%3E\"); }
        .fa-user, .fa-user-tie { -webkit-mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 12a5 5 0 1 0 0-10 5 5 0 0 0 0 10Zm0 2c-4.4 0-8 2.7-8 6v1h16v-1c0-3.3-3.6-6-8-6Z'/%3E%3C/svg%3E\"); mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 12a5 5 0 1 0 0-10 5 5 0 0 0 0 10Zm0 2c-4.4 0-8 2.7-8 6v1h16v-1c0-3.3-3.6-6-8-6Z'/%3E%3C/svg%3E\"); }
        .fa-lock { -webkit-mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M7 10V8a5 5 0 0 1 10 0v2h1a2 2 0 0 1 2 2v8H4v-8a2 2 0 0 1 2-2h1Zm2 0h6V8a3 3 0 0 0-6 0v2Z'/%3E%3C/svg%3E\"); mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M7 10V8a5 5 0 0 1 10 0v2h1a2 2 0 0 1 2 2v8H4v-8a2 2 0 0 1 2-2h1Zm2 0h6V8a3 3 0 0 0-6 0v2Z'/%3E%3C/svg%3E\"); }
        .fa-xmark { -webkit-mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='m6.4 5 5.6 5.6L17.6 5 19 6.4 13.4 12 19 17.6 17.6 19 12 13.4 6.4 19 5 17.6 10.6 12 5 6.4 6.4 5Z'/%3E%3C/svg%3E\"); mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='m6.4 5 5.6 5.6L17.6 5 19 6.4 13.4 12 19 17.6 17.6 19 12 13.4 6.4 19 5 17.6 10.6 12 5 6.4 6.4 5Z'/%3E%3C/svg%3E\"); }
        .fa-user-xmark { -webkit-mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M9 11a4 4 0 1 0-4-4 4 4 0 0 0 4 4Zm0 2c-3.3 0-6 2-6 4.5V20h9v-2.5a4.8 4.8 0 0 1 1.3-3.2A9.7 9.7 0 0 0 9 13Zm6.4 1L18 16.6l2.6-2.6 1.4 1.4-2.6 2.6 2.6 2.6-1.4 1.4L18 19.4 15.4 22 14 20.6l2.6-2.6-2.6-2.6 1.4-1.4Z'/%3E%3C/svg%3E\"); mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M9 11a4 4 0 1 0-4-4 4 4 0 0 0 4 4Zm0 2c-3.3 0-6 2-6 4.5V20h9v-2.5a4.8 4.8 0 0 1 1.3-3.2A9.7 9.7 0 0 0 9 13Zm6.4 1L18 16.6l2.6-2.6 1.4 1.4-2.6 2.6 2.6 2.6-1.4 1.4L18 19.4 15.4 22 14 20.6l2.6-2.6-2.6-2.6 1.4-1.4Z'/%3E%3C/svg%3E\"); }
        .kpi-card {
            position: relative;
            overflow: hidden;
        }
        .kpi-card::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(255,255,255,0.08), transparent 55%);
            pointer-events: none;
        }
        .kpi-card > * {
            position: relative;
            z-index: 1;
        }
    </style>
</head>

<body class="bg-neutral-100 font-sans text-navy min-h-screen">

    {{-- TOPBAR --}}
    <header class="bg-navy sticky top-0 z-50 shadow-md">
        <div class="max-w-screen-xl mx-auto px-4 sm:px-6 h-14 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="bg-white rounded-lg px-2.5 py-1.5 flex-shrink-0">
                    <img src="/Logo_anbg.png" alt="ANBG" class="h-9 w-auto object-contain block">
                </div>
                <div class="hidden sm:block">
                    <span class="text-white  font-medium tracking-wider uppercase">Agence Nationale Des Bourses Du Gabon</span>
                </div>
            </div>
            <div class="flex items-center gap-3">
                @if($canPilotage)
                <a href="/pilotage"
                    class="hidden sm:inline-flex items-center gap-1.5 bg-white/10 hover:bg-white/20 border border-white/15 text-white text-xs font-medium px-3 py-1.5 rounded-lg transition-all duration-150">
                    <svg class="icon-svg text-[15px]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M4 19h16v2H2V4h2v15Zm2.7-4.3 3.5-3.5 2.6 2.6 4.5-5.3 1.5 1.3-5.9 7-2.7-2.7-2.1 2.1-1.4-1.5Z" fill="currentColor"/>
                    </svg>
                    <span>Pilotage</span>
                </a>
                @endif
                <div class="hidden sm:flex items-center gap-2 bg-white/10 border border-white/20 rounded-lg px-3 py-1.5">
                    <div class="w-6 h-6 rounded-full bg-sky/30 flex items-center justify-center flex-shrink-0">
                        <svg class="icon-svg text-sky-100 text-[10px]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M12 12a5 5 0 1 0 0-10 5 5 0 0 0 0 10Zm0 2c-4.4 0-8 2.7-8 6v1h16v-1c0-3.3-3.6-6-8-6Z" fill="currentColor"/>
                        </svg>
                    </div>
                    <span class="text-white text-xs font-medium">{{ $actor->prenom }} {{ $actor->nom }}</span>
                </div>
                <form method="post" action="/logout">
                    @csrf
                    <button type="submit"
                        class="flex items-center gap-1.5 bg-white/10 hover:bg-red-500/20 border border-white/15 hover:border-red-400/40 text-white text-xs font-medium px-3 py-1.5 rounded-lg transition-all duration-150">
                        <svg class="icon-svg text-[15px]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M10 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h5v-2H5V5h5V3Zm9.7 9-4.2-4.2-1.4 1.4 1.8 1.8H9v2h6.9l-1.8 1.8 1.4 1.4 4.2-4.2Z" fill="currentColor"/>
                        </svg>
                        <span class="hidden sm:inline">D&eacute;connexion</span>
                    </button>
                </form>
            </div>
        </div>
    </header>

    {{-- HERO --}}
    <section class="bg-navy border-b border-white/10 pb-8 pt-6">
        <div class="max-w-screen-xl mx-auto px-4 sm:px-6">
            <div class="flex items-start gap-4">
                <div class="w-10 h-10 rounded-full bg-sky/20 flex items-center justify-center flex-shrink-0 mt-1">
                    <svg class="icon-svg text-sky-400 text-sm" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M9 4h6a2 2 0 0 1 2 2v1h3a2 2 0 0 1 2 2v8a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3V9a2 2 0 0 1 2-2h3V6a2 2 0 0 1 2-2Zm0 3h6V6H9v1Z" fill="currentColor"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-white text-xl font-medium leading-snug mb-1">Espace Chef de service</h1>
                    <p class="max-w-3xl text-sm leading-6 text-white/78">
                        Vue op&eacute;rationnelle du <span class="font-semibold text-white">{{ $serviceSummary['service_label'] ?? 'Service non renseign&eacute;' }}</span>
                    </p>

                </div>
            </div>
        </div>
    </section>

    {{-- MAIN --}}
    <main class="max-w-screen-xl mx-auto px-4 sm:px-6 py-6 space-y-6">

        {{-- Toasts --}}
        @if(session('success'))
        <div class="flex items-start gap-3 bg-leaf-50 border border-leaf/30 text-green-800 px-4 py-3 rounded-xl text-sm shadow-card">
            <i class="fas fa-circle-check text-leaf mt-0.5 flex-shrink-0"></i>
            <span>{{ session('success') }}</span>
        </div>
        @endif
        @if(session('error'))
        <div class="flex items-start gap-3 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm shadow-card">
            <i class="fas fa-circle-exclamation text-red-500 mt-0.5 flex-shrink-0"></i>
            <span>{{ session('error') }}</span>
        </div>
        @endif

        {{-- Barre de recherche --}}
        <div class="bg-white rounded-xl shadow-card border border-neutral-200 px-5 py-4">
            <form method="get" class="flex gap-3 items-end">
                <div class="flex-1 relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-neutral-400 text-sm pointer-events-none">
                        <svg class="icon-svg" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle cx="11" cy="11" r="5.5" stroke="currentColor" stroke-width="2"/>
                            <path d="m16 16 4 4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </span>
                    <input name="search" value="{{ $search }}"
                        placeholder="Recherche : num&eacute;ro suivi, objet, usager&hellip;"
                        class="field w-full pl-9 pr-4 py-2.5 border border-neutral-200 rounded-xl text-sm bg-neutral-50 text-navy placeholder-neutral-400 transition-all duration-150">
                </div>
                <button type="submit"
                    class="inline-flex items-center gap-2 bg-navy hover:bg-navy-600 text-white text-sm font-medium px-5 py-2.5 rounded-xl transition-colors duration-150">
                    <svg class="icon-svg text-xs" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M3 5h18l-7 8v5l-4 2v-7L3 5Z" fill="currentColor"/>
                    </svg>
                    <span>Filtrer</span>
                </button>
            </form>
        </div>

        {{-- SECTION 1 - Sans agent assigné --}}
        <section id="chef-kpi-content" class="flex flex-wrap items-stretch justify-center gap-3">
            <article class="kpi-card aspect-square w-[146px] rounded-[22px] bg-[linear-gradient(135deg,#3996d3_0%,#1c203d_120%)] px-4 py-4 text-white shadow-card">
                <div class="flex h-full flex-col items-center justify-center text-center gap-1.5">
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-2xl bg-white/12">
                        <svg class="icon-svg text-base" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M5 3h14l3 9v7a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-7l3-9Zm1.4 2L4.1 12H9l1 2h4l1-2h4.9L17.6 5H6.4Z" fill="currentColor"/>
                        </svg>
                    </span>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-white/70">Demandes affect&eacute;es</p>
                    <p class="text-[1.75rem] font-bold leading-none" data-countup="{{ (int) ($serviceSummary['total_sans_agent'] ?? 0) }}">{{ number_format((int) ($serviceSummary['total_sans_agent'] ?? 0), 0, ',', ' ') }}</p>
                </div>
            </article>
            <article class="kpi-card aspect-square w-[146px] rounded-[22px] bg-[linear-gradient(135deg,#f8e932_0%,#d9a90a_110%)] px-4 py-4 text-[#1c203d] shadow-card">
                <div class="flex h-full flex-col items-center justify-center text-center gap-1.5">
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-2xl bg-white/35">
                        <svg class="icon-svg text-base" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.8"/>
                            <path d="M12 7.8v5.2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            <circle cx="12" cy="16.8" r="1" fill="currentColor"/>
                        </svg>
                    </span>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-[#1c203d]/72">Demandes en retard</p>
                    <p class="text-[1.75rem] font-bold leading-none" data-countup="{{ (int) ($serviceSummary['total_en_retard'] ?? 0) }}">{{ number_format((int) ($serviceSummary['total_en_retard'] ?? 0), 0, ',', ' ') }}</p>
                </div>
            </article>
            <article class="kpi-card aspect-square w-[146px] rounded-[22px] bg-[linear-gradient(135deg,#8fc043_0%,#45661c_115%)] px-4 py-4 text-white shadow-card">
                <div class="flex h-full flex-col items-center justify-center text-center gap-1.5">
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-2xl bg-white/12">
                        <svg class="icon-svg text-base" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M17.2 14.3l-3.4 3.4-1.6-1.6-1.4 1.4 3 3 4.8-4.8-1.4-1.4Z" fill="currentColor"/>
                            <path d="M9 11a4 4 0 1 0-4-4 4 4 0 0 0 4 4Zm0 2c-3.3 0-6 2-6 4.5V20h8.5a6 6 0 0 1-.5-2.5c0-1.7.7-3.2 1.8-4.3A8.8 8.8 0 0 0 9 13Z" fill="currentColor"/>
                        </svg>
                    </span>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-white/72">Demandes assign&eacute;es</p>
                    <p class="text-[1.75rem] font-bold leading-none" data-countup="{{ (int) ($serviceSummary['total_suivies'] ?? 0) }}">{{ number_format((int) ($serviceSummary['total_suivies'] ?? 0), 0, ',', ' ') }}</p>
                </div>
            </article>
            <article class="kpi-card aspect-square w-[146px] rounded-[22px] bg-[linear-gradient(135deg,#1c203d_0%,#2a3163_100%)] px-4 py-4 text-white shadow-card">
                <div class="flex h-full flex-col items-center justify-center text-center gap-1.5">
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-2xl bg-white/12">
                        <svg class="icon-svg text-base" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M9 11a4 4 0 1 0-3.999-4A4 4 0 0 0 9 11Zm6 1a3 3 0 1 0-2.999-3A3 3 0 0 0 15 12Zm-6 2c-3.3 0-6 2-6 4.5V21h12v-2.5C15 16 12.3 14 9 14Zm6 .5c-.8 0-1.6.1-2.3.4 1.4.9 2.3 2.1 2.3 3.6V21h6v-1.8c0-2.6-2.7-4.7-6-4.7Z" fill="currentColor"/>
                        </svg>
                    </span>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-white/68">Total agents</p>
                    <p class="text-[1.75rem] font-bold leading-none" data-countup="{{ (int) ($serviceSummary['total_agents'] ?? 0) }}">{{ number_format((int) ($serviceSummary['total_agents'] ?? 0), 0, ',', ' ') }}</p>
                </div>
            </article>
        </section>

        <div id="chef-live-content"
            class="space-y-6"
            data-refresh-url="{{ request()->fullUrl() }}"
            data-refresh-interval="20000">
        <div class="surface-card rounded-[26px] overflow-hidden">
            <div class="section-title-bar px-5 py-4 border-b border-white/70 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="icon-svg text-sky text-sm" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M5 3h14l3 9v7a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-7l3-9Zm1.4 2L4.1 12H9l1 2h4l1-2h4.9L17.6 5H6.4Z" fill="currentColor"/>
                    </svg>
                    <h2 class="text-sm font-semibold text-navy">Demandes sans agent assign&eacute;</h2>
                    <span class="ml-1 rounded-full border border-sky-200 bg-sky-50 px-2 py-0.5 text-xs font-medium text-sky">
                        {{ $pending->total() }}
                    </span>
                </div>
                <p class="text-xs text-neutral-400 hidden sm:block">&Agrave; affecter ou traiter directement</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full font-sans table-readability">
                    <thead>
                        <tr class="table-head border-b border-white/10">
                            <th class="px-4 py-3 text-left text-xs font-semibold text-white uppercase tracking-wider">N&deg; Suivi</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-white uppercase tracking-wider">Usager</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-white uppercase tracking-wider">Service</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-white uppercase tracking-wider">Statut</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-white uppercase tracking-wider">Alerte 16h</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-white uppercase tracking-wider">Temps restant 16h</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-white uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                    @forelse($pending as $demande)
                        <tr class="demand-row transition-colors duration-100">
                            <td class="px-4 py-3">
                                <span class="font-mono text-xs font-medium text-navy bg-navy-50 px-2 py-1 rounded">{{ $demande->numero_suivi }}</span>
                            </td>
                            <td class="px-4 py-3 text-sm font-medium text-navy">
                                {{ trim(($demande->usager_prenom ?? '').' '.($demande->usager_nom ?? '')) }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-xs bg-neutral-100 text-neutral-800 px-2 py-1 rounded-full">
                                    {{ $demande->service_code }} &mdash; {{ $demande->service }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-neutral-800">{{ $demande->statut }}</td>
                            <td class="px-4 py-3">
                                @if($demande->alerte_chef === 'rouge')
                                    <span class="inline-flex items-center gap-1.5 bg-red-50 text-red-700 border border-red-200 text-xs font-medium px-2.5 py-1 rounded-full">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500 flex-shrink-0"></span> En retard
                                    </span>
                                @elseif($demande->alerte_chef === 'orange')
                                    <span class="inline-flex items-center gap-1.5 bg-gold-50 text-amber-700 border border-amber-200 text-xs font-medium px-2.5 py-1 rounded-full">
                                        <span class="w-1.5 h-1.5 rounded-full bg-gold flex-shrink-0"></span> &Agrave; risque
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 bg-leaf-50 text-green-700 border border-green-200 text-xs font-medium px-2.5 py-1 rounded-full">
                                        <span class="w-1.5 h-1.5 rounded-full bg-leaf flex-shrink-0"></span> Dans les d&eacute;lais
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $windowTone = match ($demande->service_window_tone ?? 'neutral') {
                                        'red' => 'text-red-600',
                                        'amber' => 'text-amber-600',
                                        'green' => 'text-green-700',
                                        default => 'text-neutral-500',
                                    };
                                @endphp
                                <div class="text-xs">
                                    <p class="font-medium {{ $windowTone }}">{{ $demande->service_window_label ?: "&mdash;" }}</p>
                                    <p class="text-neutral-700 mt-1">{{ $demande->service_window_hint ?: '' }}</p>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <button type="button"
                                    class="view-toggle inline-flex items-center gap-1.5 bg-sky-50 hover:bg-sky text-sky hover:text-white border border-sky-200 hover:border-sky text-xs font-medium px-3 py-1.5 rounded-lg transition-all duration-150"
                                    data-target="detail-p-{{ $demande->id_demande }}" aria-expanded="false">
                                    <span class="toggle-icon text-[10px]">
                                        <svg class="icon-svg" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="M2.8 12s3.2-5.5 9.2-5.5S21.2 12 21.2 12s-3.2 5.5-9.2 5.5S2.8 12 2.8 12Z" stroke="currentColor" stroke-width="2"/>
                                            <circle cx="12" cy="12" r="2.5" stroke="currentColor" stroke-width="2"/>
                                        </svg>
                                    </span>
                                    <span>Voir</span>
                                </button>
                            </td>
                        </tr>

                        {{-- Détail --}}
                        <tr id="detail-p-{{ $demande->id_demande }}" class="detail-row" style="display:none;">
                            <td colspan="7" class="px-4 py-4 bg-[linear-gradient(180deg,#f8fbfe_0%,#f4f7fb_100%)] border-b border-neutral-100">
                                @php
                                    $pieces = $piecesByDemand->get($demande->id_demande, collect());
                                    $historyEntries = $historyByDemand->get($demande->id_demande, collect());
                                    $historyLabels = [
                                        'soumission_usager' => 'Soumission usager',
                                        'soumission' => 'Soumission',
                                        'categorie_usager' => html_entity_decode('Cat&eacute;gorie choisie', ENT_QUOTES, 'UTF-8'),
                                        'affectation_service' => 'Affectation service',
                                        'affectation_agent' => 'Affectation agent',
                                        'annulation_affectation_agent' => 'Annulation affectation agent',
                                        'reponse_redigee' => html_entity_decode('R&eacute;ponse r&eacute;dig&eacute;e', ENT_QUOTES, 'UTF-8'),
                                        'reponse_directe_chef' => 'R&eacute;ponse directe chef',
                                        'envoi_reponse' => html_entity_decode('Envoi r&eacute;ponse', ENT_QUOTES, 'UTF-8'),
                                    ];
                                @endphp
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

                                    {{-- Détail demande --}}
                                    <div class="bg-white border border-neutral-200 rounded-xl p-4 space-y-3">
                                        <h4 class="text-xs font-medium text-neutral-500 uppercase tracking-wider border-b border-neutral-100 pb-2">
                                            <svg class="icon-svg text-sky mr-1.5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 2h8l4 4v16H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2Zm7 1.5V7h3.5L13 3.5ZM8 11h8v2H8v-2Zm0 4h8v2H8v-2Z" fill="currentColor"/></svg>D&eacute;tail de la demande
                                        </h4>
                                        <div>
                                            <p class="text-xs text-neutral-400 mb-0.5">Objet</p>
                                            <p class="text-sm font-medium text-navy">{{ $demande->objet }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-neutral-400 mb-1">Message</p>
                                            <div class="bg-neutral-50 border border-neutral-200 rounded-lg p-3 text-sm text-neutral-700 leading-relaxed whitespace-pre-wrap max-h-40 overflow-y-auto">{{ $demande->message }}</div>
                                        </div>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            <div>
                                                <p class="text-xs text-neutral-400">Email</p>
                                                <p class="text-sm text-navy">{{ $demande->usager_email ?: '-' }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-neutral-400">Statut usager</p>
                                                <p class="text-sm text-navy">{{ $demande->usager_statut ?: '-' }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-neutral-400">Pays</p>
                                                <p class="text-sm text-navy">{{ $demande->usager_pays ?: '-' }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-neutral-400">&Eacute;tablissement</p>
                                                <p class="text-sm text-navy">{{ $demande->usager_etablissement ?: 'Non renseigne' }}</p>
                                            </div>
                                        </div>
                                        @if($pieces->isNotEmpty())
                                        <div>
                                            <p class="text-xs text-neutral-400 mb-1.5">Pi&egrave;ces jointes</p>
                                            <div class="space-y-1.5">
                                                @foreach($pieces as $piece)
                                                <a href="/pieces-jointes/{{ $piece->id_piece_jointe }}" target="_blank" rel="noopener"
                                                    class="flex items-center gap-2 bg-sky-50 border border-sky-100 text-sky text-xs font-medium px-3 py-2 rounded-lg hover:bg-sky-100 transition-colors duration-150">
                                                    <svg class="icon-svg text-[10px]" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8.5 12.5 13 8a3 3 0 1 1 4.2 4.2l-6 6a5 5 0 1 1-7.1-7.1l6.3-6.3 1.4 1.4-6.3 6.3a3 3 0 1 0 4.2 4.2l6-6a1 1 0 1 0-1.4-1.4l-4.5 4.5-1.4-1.4Z" fill="currentColor"/></svg> {{ $piece->nom_fichier }}
                                                </a>
                                                @endforeach
                                            </div>
                                        </div>
                                        @else
                                        <p class="text-xs text-neutral-400 italic">Aucune pi&egrave;ce jointe.</p>
                                        @endif
                                    </div>

                                    {{-- Actions --}}
                                    <div class="space-y-3">

                                        {{-- Affecter agent --}}
                                        <div class="bg-white border border-neutral-200 rounded-xl p-4">
                                            <h4 class="text-xs font-medium text-neutral-500 uppercase tracking-wider border-b border-neutral-100 pb-2 mb-3">
                                                <svg class="icon-svg text-sky mr-1.5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 11a4 4 0 1 0-4-4 4 4 0 0 0 4 4Zm0 2c-3.3 0-6 2-6 4.5V20h8.5a6 6 0 0 1-.5-2.5c0-1.7.7-3.2 1.8-4.3A8.8 8.8 0 0 0 9 13Zm8.2 1.3-3.4 3.4-1.6-1.6-1.4 1.4 3 3 4.8-4.8-1.4-1.4Z" fill="currentColor"/></svg>Affecter &agrave; un agent
                                            </h4>
                                            <form method="post" action="/chef/demandes/{{ $demande->id_demande }}/affecter-agent" class="space-y-2.5">
                                                @csrf @method('put')
                                                <select name="id_agent" required
                                                    class="field w-full px-3 py-2.5 border border-neutral-200 rounded-xl text-sm bg-neutral-50 text-navy appearance-none transition-all duration-150">
                                                    <option value="">&mdash; Choisir un agent &mdash;</option>
                                                    @foreach($agentsByService->get($demande->id_service_courant, collect()) as $agent)
                                                        <option value="{{ $agent->id_utilisateur }}">{{ $agent->prenom }} {{ $agent->nom }}</option>
                                                    @endforeach
                                                </select>
                                                <input name="commentaire" placeholder="Commentaire optionnel&hellip;"
                                                    class="field w-full px-3 py-2.5 border border-neutral-200 rounded-xl text-sm bg-neutral-50 text-navy placeholder-neutral-400 transition-all duration-150">
                                                <button type="submit"
                                                    class="w-full flex items-center justify-center gap-2 bg-navy hover:bg-navy-600 text-white text-sm font-medium py-2.5 rounded-xl transition-colors duration-150">
                                                    <svg class="icon-svg text-xs" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8.5 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm0 2C5.5 13 3 14.7 3 17v2h9v-2c0-1.2.4-2.3 1.1-3.3A9.6 9.6 0 0 0 8.5 13ZM18 8h-2V6h-2v2h-2v2h2v2h2v-2h2V8Z" fill="currentColor"/></svg> Affecter &agrave; l'agent
                                                </button>
                                            </form>
                                        </div>

                                        {{-- R&eacute;ponse directe --}}
                                        <div class="bg-white border border-neutral-200 rounded-xl p-4">
                                            <h4 class="text-xs font-medium text-neutral-500 uppercase tracking-wider border-b border-neutral-100 pb-2 mb-3">
                                                <svg class="icon-svg text-sky mr-1.5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M10 7 3 12l7 5v-3h3c3.3 0 5.4 1.1 8 4-1-6-4-9-9-9h-2V7Z" fill="currentColor"/></svg>R&eacute;ponse directe du chef
                                            </h4>
                                            <form method="post" action="/chef/demandes/{{ $demande->id_demande }}/reponse-directe" enctype="multipart/form-data" class="space-y-2.5">
                                                @csrf @method('put')
                                                <textarea name="contenu_reponse" required rows="4"
                                                    placeholder="Saisir la r&eacute;ponse &agrave; envoyer &agrave; l'usager&hellip;"
                                                    class="field w-full px-3 py-2.5 border border-neutral-200 rounded-xl text-sm bg-neutral-50 text-navy placeholder-neutral-400 resize-y transition-all duration-150"></textarea>
                                                <label for="chef-direct-files-{{ $demande->id_demande }}" class="flex flex-col items-center justify-center gap-1.5 border-2 border-dashed border-neutral-200 hover:border-sky rounded-lg p-3 cursor-pointer transition-colors duration-150 bg-neutral-50 hover:bg-sky-50">
                                                    <svg class="icon-svg text-neutral-400 text-base" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 18h10a4 4 0 0 0 .4-8A6 6 0 0 0 6 11a4 4 0 0 0 1 7Zm6-6V8h-2v4H8l4 4 4-4h-3Z" fill="currentColor"/></svg>
                                                    <span class="text-xs text-neutral-400">Pi&egrave;ces jointes (optionnel)</span>
                                                    <span class="file-selection-feedback text-[11px] text-neutral-400 text-center">Aucun fichier s&eacute;lectionn&eacute;</span>
                                                    <input id="chef-direct-files-{{ $demande->id_demande }}" type="file" name="pieces_jointes[]" multiple class="hidden" data-file-feedback>
                                                </label>
                                                <button type="submit"
                                                    class="w-full flex items-center justify-center gap-2 bg-sky hover:bg-sky-600 text-white text-sm font-medium py-2.5 rounded-xl transition-colors duration-150">
                                                    <svg class="icon-svg text-xs" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 6h18v12H3V6Zm2 2v1l7 4 7-4V8l-7 4-7-4Zm11.2 5.8-1.4 1.4-1.3-1.3-1.4 1.4 2.7 2.7 4.8-4.8-1.4-1.4-3.4 3.4Z" fill="currentColor"/></svg> Envoyer la r&eacute;ponse
                                                </button>
                                            </form>
                                        </div>

                                    </div>
                                </div>

                                @if(false)
                                <div class="mt-4 bg-white border border-neutral-200 rounded-xl p-4">
                                    <h4 class="text-xs font-medium text-neutral-500 uppercase tracking-wider border-b border-neutral-100 pb-2 mb-3">
                                        <svg class="icon-svg text-sky mr-1.5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="M4 6.5A2.5 2.5 0 0 1 6.5 4H10l2 2h5.5A2.5 2.5 0 0 1 20 8.5v9A2.5 2.5 0 0 1 17.5 20h-11A2.5 2.5 0 0 1 4 17.5v-11Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                            <path d="M8 11h8M8 15h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                        </svg>Historique des actions
                                    </h4>
                                    <div class="space-y-2.5">
                                        @forelse($historyEntries as $entry)
                                        <div class="rounded-lg border border-neutral-200 bg-neutral-50 px-3 py-3">
                                            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                                <div class="space-y-1">
                                                    <span class="inline-flex items-center gap-2 rounded-full border border-sky-100 bg-sky-50 px-2.5 py-1 text-[11px] font-medium text-sky">
                                                        <svg class="icon-svg text-[10px]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                            <path d="M12 7v5l3 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                            <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/>
                                                        </svg>
                                                        {{ $historyLabels[$entry->type_action] ?? ucfirst(str_replace('_', ' ', (string) $entry->type_action)) }}
                                                    </span>
                                                    <p class="text-sm font-medium text-navy">
                                                        {{ trim(((string) ($entry->acteur_prenom ?? '')).' '.((string) ($entry->acteur_nom ?? ''))) !== '' ? trim(((string) ($entry->acteur_prenom ?? '')).' '.((string) ($entry->acteur_nom ?? ''))) : html_entity_decode('Syst&egrave;me', ENT_QUOTES, 'UTF-8') }}
                                                    </p>
                                                    <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-neutral-400">
                                                        <span>{{ \Carbon\Carbon::parse($entry->date_action)->format('d/m/Y H:i') }}</span>
                                                        @if(!empty($entry->service_code))
                                                        <span>Service : {{ $entry->service_code }}</span>
                                                        @endif
                                                        @if(!empty($entry->ancien_statut) || !empty($entry->nouveau_statut))
                                                        <span>{{ $entry->ancien_statut ?: '-' }} -> {{ $entry->nouveau_statut ?: '-' }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                            @if(!empty($entry->commentaire))
                                            <p class="mt-2 text-xs leading-relaxed text-neutral-500">{{ $entry->commentaire }}</p>
                                            @endif
                                        </div>
                                        @empty
                                        <p class="text-sm text-neutral-400">Aucune action enregistr&eacute;e pour cette demande.</p>
                                        @endforelse
                                    </div>
                                </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center">
                                <div class="flex flex-col items-center gap-3 text-neutral-400">
                                    <div class="w-12 h-12 rounded-full bg-neutral-100 flex items-center justify-center">
                                        <svg class="icon-svg text-xl" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="M5 3h14l3 9v7a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-7l3-9Zm1.4 2L4.1 12H9l1 2h4l1-2h4.9L17.6 5H6.4Z" fill="currentColor"/>
                                        </svg>
                                    </div>
                                    <p class="text-sm">Aucune demande en attente.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @if($pending->hasPages())
            <div class="px-5 py-4 border-t border-neutral-100 bg-neutral-50">
                {{ $pending->links() }}
            </div>
            @endif
        </div>

        {{-- SECTION 2 - Avec agent assigné --}}
        <div class="surface-card rounded-[26px] overflow-hidden">
            <div class="section-title-bar px-5 py-4 border-b border-white/70 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="icon-svg text-sky text-sm" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 12a4.5 4.5 0 1 0-4.5-4.5A4.5 4.5 0 0 0 12 12Zm-5 8v-1c0-2.8 2.8-5 5-5s5 2.2 5 5v1H7Zm4-8 1 2 1-2h-2Zm1 3-1.5 5h3L12 15Z" fill="currentColor"/>
                    </svg>
                    <h2 class="text-sm font-semibold text-navy">Demandes avec agent assigne</h2>
                    <span class="ml-1 rounded-full border border-sky-200 bg-sky-50 px-2 py-0.5 text-xs font-medium text-sky">
                        {{ $assigned->total() }}
                    </span>
                </div>
                <p class="text-xs text-neutral-400 hidden sm:block">Suivi et supervision</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full font-sans table-readability">
                    <thead>
                        <tr class="table-head border-b border-white/10">
                            <th class="px-4 py-3 text-left text-[11px] font-semibold text-white uppercase tracking-wider">N&deg; Suivi</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold text-white uppercase tracking-wider">Usager</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold text-white uppercase tracking-wider">Agent</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold text-white uppercase tracking-wider">Statut</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold text-white uppercase tracking-wider">Alerte 16h</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold text-white uppercase tracking-wider">Temps restant 16h</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold text-white uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                    @forelse($assigned as $demande)
                        <tr class="trow transition-colors duration-100">
                            <td class="px-4 py-3">
                                <span class="font-mono text-xs font-medium text-navy bg-navy-50 px-2 py-1 rounded">{{ $demande->numero_suivi }}</span>
                            </td>
                            <td class="px-4 py-3 text-sm font-medium text-navy">
                                {{ trim(($demande->usager_prenom ?? '').' '.($demande->usager_nom ?? '')) }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full bg-sky-100 flex items-center justify-center flex-shrink-0">
                                        <svg class="icon-svg text-sky text-[10px]" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 12a5 5 0 1 0 0-10 5 5 0 0 0 0 10Zm0 2c-4.4 0-8 2.7-8 6v1h16v-1c0-3.3-3.6-6-8-6Z" fill="currentColor"/></svg>
                                    </div>
                                    <span class="text-sm text-navy">{{ trim(($demande->agent_prenom ?? '').' '.($demande->agent_nom ?? '')) ?: html_entity_decode('Non d&eacute;fini', ENT_QUOTES, 'UTF-8') }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-xs bg-neutral-100 text-neutral-600 px-2 py-1 rounded-full">{{ $demande->statut }}</span>
                            </td>
                            <td class="px-4 py-3">
                                @if($demande->alerte_agent === 'rouge')
                                    <span class="inline-flex items-center gap-1.5 bg-red-50 text-red-700 border border-red-200 text-xs font-medium px-2.5 py-1 rounded-full">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500 flex-shrink-0"></span> En retard
                                    </span>
                                @elseif($demande->alerte_agent === 'orange')
                                    <span class="inline-flex items-center gap-1.5 bg-gold-50 text-amber-700 border border-amber-200 text-xs font-medium px-2.5 py-1 rounded-full">
                                        <span class="w-1.5 h-1.5 rounded-full bg-gold flex-shrink-0"></span> &Agrave; risque
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 bg-leaf-50 text-green-700 border border-green-200 text-xs font-medium px-2.5 py-1 rounded-full">
                                        <span class="w-1.5 h-1.5 rounded-full bg-leaf flex-shrink-0"></span> Dans les d&eacute;lais
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $windowTone = match ($demande->service_window_tone ?? 'neutral') {
                                        'red' => 'text-red-600',
                                        'amber' => 'text-amber-600',
                                        'green' => 'text-green-700',
                                        default => 'text-neutral-500',
                                    };
                                @endphp
                                <div class="text-xs">
                                    <p class="font-medium {{ $windowTone }}">{{ $demande->service_window_label ?: "&mdash;" }}</p>
                                    <p class="text-neutral-400 mt-1">{{ $demande->service_window_hint ?: '' }}</p>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <button type="button"
                                    class="view-toggle inline-flex items-center gap-1.5 bg-sky-50 hover:bg-sky text-sky hover:text-white border border-sky-200 hover:border-sky text-xs font-medium px-3 py-1.5 rounded-lg transition-all duration-150"
                                    data-target="detail-a-{{ $demande->id_demande }}" aria-expanded="false">
                                    <span class="toggle-icon text-[10px]">
                                        <svg class="icon-svg" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="M2.8 12s3.2-5.5 9.2-5.5S21.2 12 21.2 12s-3.2 5.5-9.2 5.5S2.8 12 2.8 12Z" stroke="currentColor" stroke-width="2"/>
                                            <circle cx="12" cy="12" r="2.5" stroke="currentColor" stroke-width="2"/>
                                        </svg>
                                    </span>
                                    <span>Voir</span>
                                </button>
                            </td>
                        </tr>

                        {{-- Détail --}}
                        <tr id="detail-a-{{ $demande->id_demande }}" class="detail-row" style="display:none;">
                            <td colspan="7" class="px-4 py-4 bg-[linear-gradient(180deg,#f8fbfe_0%,#f4f7fb_100%)] border-b border-neutral-100">
                                @php
                                    $pieces = $piecesByDemand->get($demande->id_demande, collect());
                                    $historyEntries = $historyByDemand->get($demande->id_demande, collect());
                                    $historyLabels = [
                                        'soumission_usager' => 'Soumission usager',
                                        'soumission' => 'Soumission',
                                        'categorie_usager' => html_entity_decode('Cat&eacute;gorie choisie', ENT_QUOTES, 'UTF-8'),
                                        'affectation_service' => 'Affectation service',
                                        'affectation_agent' => 'Affectation agent',
                                        'annulation_affectation_agent' => 'Annulation affectation agent',
                                        'reponse_redigee' => html_entity_decode('R&eacute;ponse r&eacute;dig&eacute;e', ENT_QUOTES, 'UTF-8'),
                                        'reponse_directe_chef' => 'R&eacute;ponse directe chef',
                                        'envoi_reponse' => html_entity_decode('Envoi r&eacute;ponse', ENT_QUOTES, 'UTF-8'),
                                    ];
                                @endphp
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

                                    {{-- Détail demande --}}
                                    <div class="bg-white border border-neutral-200 rounded-xl p-4 space-y-3">
                                        <h4 class="text-xs font-medium text-neutral-500 uppercase tracking-wider border-b border-neutral-100 pb-2">
                                            <svg class="icon-svg text-sky mr-1.5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 2h8l4 4v16H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2Zm7 1.5V7h3.5L13 3.5ZM8 11h8v2H8v-2Zm0 4h8v2H8v-2Z" fill="currentColor"/></svg>D&eacute;tail de la demande
                                        </h4>
                                        <div>
                                            <p class="text-xs text-neutral-400 mb-0.5">Objet</p>
                                            <p class="text-sm font-medium text-navy">{{ $demande->objet }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-neutral-400 mb-1">Message</p>
                                            <div class="bg-neutral-50 border border-neutral-200 rounded-lg p-3 text-sm text-neutral-700 leading-relaxed whitespace-pre-wrap max-h-40 overflow-y-auto">{{ $demande->message }}</div>
                                        </div>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            <div>
                                                <p class="text-xs text-neutral-400">Email</p>
                                                <p class="text-sm text-navy">{{ $demande->usager_email ?: '-' }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-neutral-400">Statut usager</p>
                                                <p class="text-sm text-navy">{{ $demande->usager_statut ?: '-' }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-neutral-400">Pays</p>
                                                <p class="text-sm text-navy">{{ $demande->usager_pays ?: '-' }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-neutral-400">&Eacute;tablissement</p>
                                                <p class="text-sm text-navy">{{ $demande->usager_etablissement ?: 'Non renseigne' }}</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2 bg-sky-50 border border-sky-100 rounded-lg px-3 py-2">
                                            <svg class="icon-svg text-sky text-xs flex-shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 12a5 5 0 1 0 0-10 5 5 0 0 0 0 10Zm0 2c-4.4 0-8 2.7-8 6v1h16v-1c0-3.3-3.6-6-8-6Z" fill="currentColor"/></svg>
                                            <span class="text-xs text-navy font-medium">Agent : {{ trim(($demande->agent_prenom ?? '').' '.($demande->agent_nom ?? '')) ?: html_entity_decode('Non d&eacute;fini', ENT_QUOTES, 'UTF-8') }}</span>
                                        </div>
                                        @if($pieces->isNotEmpty())
                                        <div>
                                            <p class="text-xs text-neutral-400 mb-1.5">Pi&egrave;ces jointes</p>
                                            <div class="space-y-1.5">
                                                @foreach($pieces as $piece)
                                                <a href="/pieces-jointes/{{ $piece->id_piece_jointe }}" target="_blank" rel="noopener"
                                                    class="flex items-center gap-2 bg-sky-50 border border-sky-100 text-sky text-xs font-medium px-3 py-2 rounded-lg hover:bg-sky-100 transition-colors duration-150">
                                                    <svg class="icon-svg text-[10px]" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8.5 12.5 13 8a3 3 0 1 1 4.2 4.2l-6 6a5 5 0 1 1-7.1-7.1l6.3-6.3 1.4 1.4-6.3 6.3a3 3 0 1 0 4.2 4.2l6-6a1 1 0 1 0-1.4-1.4l-4.5 4.5-1.4-1.4Z" fill="currentColor"/></svg> {{ $piece->nom_fichier }}
                                                </a>
                                                @endforeach
                                            </div>
                                        </div>
                                        @endif
                                    </div>

                                    {{-- Actions --}}
                                    <div class="space-y-3">

                                        {{-- R&eacute;ponse directe chef --}}
                                        <div class="bg-white border border-neutral-200 rounded-xl p-4">
                                            <h4 class="text-xs font-medium text-neutral-500 uppercase tracking-wider border-b border-neutral-100 pb-2 mb-3">
                                                <svg class="icon-svg text-sky mr-1.5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M10 7 3 12l7 5v-3h3c3.3 0 5.4 1.1 8 4-1-6-4-9-9-9h-2V7Z" fill="currentColor"/></svg>R&eacute;ponse directe du chef
                                            </h4>
                                            <p class="text-xs text-neutral-400 mb-3">Le chef peut reprendre la main et cl&ocirc;turer la demande lui-m&ecirc;me.</p>
                                            <form method="post" action="/chef/demandes/{{ $demande->id_demande }}/reponse-directe" enctype="multipart/form-data" class="space-y-2.5">
                                                @csrf @method('put')
                                                <textarea name="contenu_reponse" required rows="4"
                                                    placeholder="Saisir la r&eacute;ponse &agrave; envoyer &agrave; l'usager&hellip;"
                                                    class="field w-full px-3 py-2.5 border border-neutral-200 rounded-xl text-sm bg-neutral-50 text-navy placeholder-neutral-400 resize-y transition-all duration-150"></textarea>
                                                <label for="chef-assigned-files-{{ $demande->id_demande }}" class="flex flex-col items-center justify-center gap-1.5 border-2 border-dashed border-neutral-200 hover:border-sky rounded-lg p-3 cursor-pointer transition-colors duration-150 bg-neutral-50 hover:bg-sky-50">
                                                    <svg class="icon-svg text-neutral-400 text-base" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 18h10a4 4 0 0 0 .4-8A6 6 0 0 0 6 11a4 4 0 0 0 1 7Zm6-6V8h-2v4H8l4 4 4-4h-3Z" fill="currentColor"/></svg>
                                                    <span class="text-xs text-neutral-400">Pi&egrave;ces jointes (optionnel)</span>
                                                    <span class="file-selection-feedback text-[11px] text-neutral-400 text-center">Aucun fichier s&eacute;lectionn&eacute;</span>
                                                    <input id="chef-assigned-files-{{ $demande->id_demande }}" type="file" name="pieces_jointes[]" multiple class="hidden" data-file-feedback>
                                                </label>
                                                <button type="submit"
                                                    class="w-full flex items-center justify-center gap-2 bg-sky hover:bg-sky-600 text-white text-sm font-medium py-2.5 rounded-xl transition-colors duration-150">
                                                    <svg class="icon-svg text-xs" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 6h18v12H3V6Zm2 2v1l7 4 7-4V8l-7 4-7-4Zm11.2 5.8-1.4 1.4-1.3-1.3-1.4 1.4 2.7 2.7 4.8-4.8-1.4-1.4-3.4 3.4Z" fill="currentColor"/></svg> Envoyer la r&eacute;ponse
                                                </button>
                                            </form>
                                        </div>

                                        {{-- Annuler / Verrouillé --}}
                                        @if($demande->statut_code === 'affectee_agent')
                                        <div class="bg-white border border-red-100 rounded-xl p-4">
                                            <h4 class="text-xs font-medium text-red-500 uppercase tracking-wider border-b border-red-100 pb-2 mb-3">
                                                <svg class="icon-svg mr-1.5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m6.4 5 5.6 5.6L17.6 5 19 6.4 13.4 12 19 17.6 17.6 19 12 13.4 6.4 19 5 17.6 10.6 12 5 6.4 6.4 5Z" fill="currentColor"/></svg>Annuler l'affectation agent
                                            </h4>
                                            <p class="text-xs text-neutral-400 mb-3">Utiliser si l'agent n'est plus disponible pour traiter cette demande.</p>
                                            <form method="post" action="/chef/demandes/{{ $demande->id_demande }}/annuler-affectation-agent" class="space-y-2.5">
                                                @csrf @method('put')
                                                <input name="commentaire" placeholder="Motif optionnel de l'annulation"
                                                    class="field w-full px-3 py-2.5 border border-red-200 rounded-xl text-sm bg-red-50 text-navy placeholder-neutral-400 transition-all duration-150">
                                                <button type="submit"
                                                    class="w-full flex items-center justify-center gap-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium py-2.5 rounded-xl transition-colors duration-150">
                                                    <svg class="icon-svg text-xs" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 11a4 4 0 1 0-4-4 4 4 0 0 0 4 4Zm0 2c-3.3 0-6 2-6 4.5V20h9v-2.5a4.8 4.8 0 0 1 1.3-3.2A9.7 9.7 0 0 0 9 13Zm6.4 1L18 16.6l2.6-2.6 1.4 1.4-2.6 2.6 2.6 2.6-1.4 1.4L18 19.4 15.4 22 14 20.6l2.6-2.6-2.6-2.6 1.4-1.4Z" fill="currentColor"/></svg> Annuler l'affectation
                                                </button>
                                            </form>
                                        </div>
                                        @else
                                        <div class="bg-neutral-50 border border-neutral-200 rounded-xl p-4 flex items-start gap-3">
                                            <svg class="icon-svg text-neutral-400 text-sm mt-0.5 flex-shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 10V8a5 5 0 0 1 10 0v2h1a2 2 0 0 1 2 2v8H4v-8a2 2 0 0 1 2-2h1Zm2 0h6V8a3 3 0 0 0-6 0v2Z" fill="currentColor"/></svg>
                                            <div>
                                                <p class="text-xs font-medium text-neutral-600 mb-0.5">Assignation verrouill&eacute;e</p>
                                                <p class="text-xs text-neutral-400 leading-relaxed">Une r&eacute;ponse existe d&eacute;j&agrave; pour cette demande. L'annulation d'affectation n'est plus disponible.</p>
                                            </div>
                                        </div>
                                        @endif

                                    </div>
                                </div>

                                @if(false)
                                <div class="mt-4 bg-white border border-neutral-200 rounded-xl p-4">
                                    <h4 class="text-xs font-medium text-neutral-500 uppercase tracking-wider border-b border-neutral-100 pb-2 mb-3">
                                        <svg class="icon-svg text-sky mr-1.5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="M4 6.5A2.5 2.5 0 0 1 6.5 4H10l2 2h5.5A2.5 2.5 0 0 1 20 8.5v9A2.5 2.5 0 0 1 17.5 20h-11A2.5 2.5 0 0 1 4 17.5v-11Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                            <path d="M8 11h8M8 15h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                        </svg>Historique des actions
                                    </h4>
                                    <div class="space-y-2.5">
                                        @forelse($historyEntries as $entry)
                                        <div class="rounded-lg border border-neutral-200 bg-neutral-50 px-3 py-3">
                                            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                                <div class="space-y-1">
                                                    <span class="inline-flex items-center gap-2 rounded-full border border-sky-100 bg-sky-50 px-2.5 py-1 text-[11px] font-medium text-sky">
                                                        <svg class="icon-svg text-[10px]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                            <path d="M12 7v5l3 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                            <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/>
                                                        </svg>
                                                        {{ $historyLabels[$entry->type_action] ?? ucfirst(str_replace('_', ' ', (string) $entry->type_action)) }}
                                                    </span>
                                                    <p class="text-sm font-medium text-navy">
                                                        {{ trim(((string) ($entry->acteur_prenom ?? '')).' '.((string) ($entry->acteur_nom ?? ''))) !== '' ? trim(((string) ($entry->acteur_prenom ?? '')).' '.((string) ($entry->acteur_nom ?? ''))) : html_entity_decode('Syst&egrave;me', ENT_QUOTES, 'UTF-8') }}
                                                    </p>
                                                    <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-neutral-400">
                                                        <span>{{ \Carbon\Carbon::parse($entry->date_action)->format('d/m/Y H:i') }}</span>
                                                        @if(!empty($entry->service_code))
                                                        <span>Service : {{ $entry->service_code }}</span>
                                                        @endif
                                                        @if(!empty($entry->ancien_statut) || !empty($entry->nouveau_statut))
                                                        <span>{{ $entry->ancien_statut ?: '-' }} -> {{ $entry->nouveau_statut ?: '-' }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                            @if(!empty($entry->commentaire))
                                            <p class="mt-2 text-xs leading-relaxed text-neutral-500">{{ $entry->commentaire }}</p>
                                            @endif
                                        </div>
                                        @empty
                                        <p class="text-sm text-neutral-400">Aucune action enregistr&eacute;e pour cette demande.</p>
                                        @endforelse
                                    </div>
                                </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center">
                                <div class="flex flex-col items-center gap-3 text-neutral-400">
                                    <div class="w-12 h-12 rounded-full bg-neutral-100 flex items-center justify-center">
                                        <svg class="icon-svg text-xl" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="M12 12a4.5 4.5 0 1 0-4.5-4.5A4.5 4.5 0 0 0 12 12Zm-5 8v-1c0-2.8 2.8-5 5-5s5 2.2 5 5v1H7Zm4-8 1 2 1-2h-2Zm1 3-1.5 5h3L12 15Z" fill="currentColor"/>
                                        </svg>
                                    </div>
                                    <p class="text-sm">Aucune demande affect&eacute;e.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @if($assigned->hasPages())
            <div class="px-5 py-4 border-t border-neutral-100 bg-neutral-50">
                {{ $assigned->links() }}
            </div>
        @endif
        </div>

        <div class="surface-card rounded-[26px] overflow-hidden">
            <div class="section-title-bar px-5 py-4 border-b border-white/70 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="icon-svg text-sky text-sm" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 7v5l3 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/>
                    </svg>
                    <h2 class="text-sm font-semibold text-navy">Historique des actions chef</h2>
                </div>
                <p class="text-xs text-neutral-400 hidden sm:block">Affectations, annulations et r&eacute;ponses du service</p>
            </div>
            <div class="px-5 py-4 space-y-3">
                @php
                    $actionLabels = [
                        'affectation_agent' => 'Affectation agent',
                        'annulation_affectation_agent' => 'Annulation affectation agent',
                        'reponse_directe_chef' => 'R&eacute;ponse directe chef',
                        'reponse_redigee' => html_entity_decode('R&eacute;ponse r&eacute;dig&eacute;e', ENT_QUOTES, 'UTF-8'),
                        'envoi_reponse' => html_entity_decode('Envoi r&eacute;ponse', ENT_QUOTES, 'UTF-8'),
                    ];
                @endphp
                @forelse($recentChefActions as $entry)
                <article class="rounded-xl border border-neutral-200 bg-neutral-50 px-4 py-3">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                        <div class="space-y-1.5">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-mono text-[11px] font-medium text-navy bg-navy-50 px-2 py-1 rounded">{{ $entry->numero_suivi }}</span>
                                <span class="inline-flex items-center gap-1.5 text-xs font-medium px-2.5 py-1 rounded-full {{ $entry->type_action === 'annulation_affectation_agent' ? 'bg-red-50 text-red-700 border border-red-200' : 'bg-navy-50 text-navy border border-navy-100' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $entry->type_action === 'annulation_affectation_agent' ? 'bg-red-500' : 'bg-navy' }}"></span>
                                    {{ $actionLabels[$entry->type_action] ?? ucfirst(str_replace('_', ' ', (string) $entry->type_action)) }}
                                </span>
                            </div>
                            <p class="text-sm font-medium text-navy">{{ $entry->objet }}</p>
                            <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-neutral-500">
                                <span>Usager : {{ trim(((string) ($entry->usager_prenom ?? '')).' '.((string) ($entry->usager_nom ?? ''))) !== '' ? trim(((string) ($entry->usager_prenom ?? '')).' '.((string) ($entry->usager_nom ?? ''))) : '-' }}</span>
                                <span>Acteur : {{ trim(((string) ($entry->acteur_prenom ?? '')).' '.((string) ($entry->acteur_nom ?? ''))) !== '' ? trim(((string) ($entry->acteur_prenom ?? '')).' '.((string) ($entry->acteur_nom ?? ''))) : html_entity_decode('Syst&egrave;me', ENT_QUOTES, 'UTF-8') }}</span>
                                @if(!empty($entry->service_code))
                                <span>Service : {{ $entry->service_code }}</span>
                                @endif
                            </div>
                            @if(!empty($entry->commentaire))
                            <p class="text-xs leading-relaxed text-neutral-500">{{ $entry->commentaire }}</p>
                            @endif
                        </div>
                        <div class="text-xs text-neutral-400 whitespace-nowrap">
                            {{ \Carbon\Carbon::parse($entry->date_action)->format('d/m/Y H:i') }}
                        </div>
                    </div>
                </article>
                @empty
                <div class="rounded-xl border border-dashed border-neutral-200 bg-neutral-50 px-4 py-6 text-center text-sm text-neutral-400">
                    Aucune action r&eacute;cente du chef de service.
                </div>
                @endforelse
            </div>
        </div>
        </div>

        {{-- Footer --}}
        <footer class="border-t border-neutral-200 pt-4 pb-2 flex items-center justify-between text-xs text-neutral-400">
            <span>&copy; {{ date('Y') }} Agence Nationale des Bourses du Gabon</span>
            <span class="font-medium">Constructeur d'avenir</span>
        </footer>

    </main>

<script>
(function () {
    const eyeSvg = `
        <svg class="icon-svg" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M2.8 12s3.2-5.5 9.2-5.5S21.2 12 21.2 12s-3.2 5.5-9.2 5.5S2.8 12 2.8 12Z" stroke="currentColor" stroke-width="2"/>
            <circle cx="12" cy="12" r="2.5" stroke="currentColor" stroke-width="2"/>
        </svg>`;
    const eyeOffSvg = `
        <svg class="icon-svg" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="m4 4 16 16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            <path d="M2.8 12s3.2-5.5 9.2-5.5c2.5 0 4.6.9 6.2 2.1M21.2 12s-3.2 5.5-9.2 5.5c-2.5 0-4.6-.9-6.2-2.1" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            <circle cx="12" cy="12" r="2.5" stroke="currentColor" stroke-width="2"/>
        </svg>`;
    const liveContent = document.getElementById('chef-live-content');
    const kpiContent = document.getElementById('chef-kpi-content');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    const animateCounters = (root = document) => {
        root.querySelectorAll('[data-countup]:not([data-countup-ready])').forEach((node) => {
            node.dataset.countupReady = '1';
            const target = Number(node.getAttribute('data-countup') || 0);
            const duration = 700;
            const startTime = performance.now();

            const tick = (now) => {
                const progress = Math.min((now - startTime) / duration, 1);
                const eased = 1 - Math.pow(1 - progress, 3);
                node.textContent = Math.round(target * eased).toLocaleString('fr-FR');
                if (progress < 1) {
                    requestAnimationFrame(tick);
                }
            };

            requestAnimationFrame(tick);
        });
    };

    const initDirectReplyRestrictions = (root = document) => {
    root.querySelectorAll('tr[id^="detail-a-"] form[action*="/reponse-directe"]').forEach((form) => {
        const card = form.closest('.bg-white');
        if (!card) return;

        card.className = 'bg-neutral-50 border border-neutral-200 rounded-xl p-4 flex items-start gap-3';
        card.innerHTML = `
            <svg class="icon-svg text-neutral-400 text-sm mt-0.5 flex-shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 10V8a5 5 0 0 1 10 0v2h1a2 2 0 0 1 2 2v8H4v-8a2 2 0 0 1 2-2h1Zm2 0h6V8a3 3 0 0 0-6 0v2Z" fill="currentColor"/></svg>
            <div>
                <p class="text-xs font-medium text-neutral-600 mb-0.5">R&eacute;ponse directe indisponible</p>
                <p class="text-xs text-neutral-400 leading-relaxed">D&egrave;s qu'un agent a &eacute;t&eacute; affect&eacute;, le chef de service ne peut plus r&eacute;pondre directement. Il doit d'abord annuler l'affectation si l'agent n'est plus disponible.</p>
            </div>
        `;
    });
    };

    const initFileFeedback = (root = document) => {
    root.querySelectorAll('input[data-file-feedback]:not([data-file-feedback-ready])').forEach((input) => {
        input.dataset.fileFeedbackReady = '1';
        input.addEventListener('change', () => {
            const label = input.closest('label');
            const feedback = label?.querySelector('.file-selection-feedback');
            if (!feedback) return;

            const files = Array.from(input.files || []);
            if (!files.length) {
                feedback.textContent = 'Aucun fichier sélectionné';
                return;
            }

            feedback.textContent = files.length === 1
                ? files[0].name
                : `${files.length} fichiers sélectionnés`;
        });
    });
    };

    const initViewToggles = (root = document) => {
    root.querySelectorAll('.view-toggle:not([data-toggle-ready])').forEach((btn) => {
        btn.dataset.toggleReady = '1';
        btn.addEventListener('click', () => {
            const row  = document.getElementById(btn.dataset.target);
            if (!row) return;
            const isOpen = row.style.display !== 'none';
            row.style.display = isOpen ? 'none' : '';
            btn.setAttribute('aria-expanded', String(!isOpen));
            const icon  = btn.querySelector('.toggle-icon');
            const spans = btn.querySelectorAll('span');
            const label = spans.length > 1 ? spans[1] : null;
            if (isOpen) {
                row.querySelectorAll('form').forEach((form) => {
                    delete form.dataset.refreshDirty;
                });
                icon.innerHTML = eyeSvg;
                if (label) label.textContent = 'Voir';
            } else {
                icon.innerHTML = eyeOffSvg;
                if (label) label.textContent = 'Masquer';
            }
        });
    });
    };

    const initFormRefreshLocks = (root = document) => {
        root.querySelectorAll('form:not([data-refresh-form-ready])').forEach((form) => {
            form.dataset.refreshFormReady = '1';
            form.addEventListener('input', () => {
                form.dataset.refreshDirty = '1';
            });
            form.addEventListener('change', () => {
                form.dataset.refreshDirty = '1';
            });
            form.addEventListener('submit', () => {
                if (liveContent && liveContent.contains(form)) {
                    liveContent.dataset.refreshLocked = '1';
                }
            });
        });
    };

    const initChefInteractions = (root = document) => {
        animateCounters(root);
        initDirectReplyRestrictions(root);
        initFileFeedback(root);
        initFormRefreshLocks(root);
        initViewToggles(root);
    };

    const hasFocusedControl = () => {
        const active = document.activeElement;
        if (!active || !liveContent || !liveContent.contains(active)) return false;

        return ['INPUT', 'TEXTAREA', 'SELECT', 'BUTTON'].includes(active.tagName) || active.isContentEditable;
    };

    const hasOpenDetail = () => {
        if (!liveContent) return false;

        return Array.from(liveContent.querySelectorAll('.detail-row')).some((row) => row.style.display !== 'none');
    };

    const hasDirtyForm = () => {
        if (!liveContent) return false;

        return Array.from(liveContent.querySelectorAll('form')).some((form) => form.dataset.refreshDirty === '1');
    };

    const shouldSkipRefresh = (isRefreshing) => {
        if (!liveContent || isRefreshing || document.hidden || liveContent.dataset.refreshLocked === '1') {
            return true;
        }

        return hasFocusedControl() || hasOpenDetail() || hasDirtyForm();
    };

    const initChefAutoRefresh = () => {
        if (!liveContent || liveContent.dataset.autoRefreshReady === '1') return;
        liveContent.dataset.autoRefreshReady = '1';

        let isRefreshing = false;
        const interval = Math.max(Number(liveContent.dataset.refreshInterval || 20000), 10000);

        const refreshContent = async () => {
            if (shouldSkipRefresh(isRefreshing)) return;
            isRefreshing = true;

            try {
                const url = new URL(liveContent.dataset.refreshUrl || window.location.href, window.location.origin);
                url.searchParams.set('_chef_refresh', Date.now().toString());

                const response = await fetch(url.toString(), {
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-Chef-Refresh': 'tables',
                    },
                    cache: 'no-store',
                });

                if (!response.ok) return;

                const html = await response.text();
                const nextDocument = new DOMParser().parseFromString(html, 'text/html');
                const nextKpiContent = nextDocument.getElementById('chef-kpi-content');
                const nextContent = nextDocument.getElementById('chef-live-content');

                if (nextKpiContent && kpiContent) {
                    kpiContent.innerHTML = nextKpiContent.innerHTML;
                    animateCounters(kpiContent);
                }

                if (!nextContent) return;

                liveContent.innerHTML = nextContent.innerHTML;
                initChefInteractions(liveContent);
            } catch (error) {
                console.warn('Rafraichissement chef de service interrompu.', error);
            } finally {
                isRefreshing = false;
            }
        };

        window.setInterval(refreshContent, interval);
        window.addEventListener('focus', refreshContent);
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) refreshContent();
        });
    };

    initChefInteractions(document);
    initChefAutoRefresh();
})();
</script>
</body>
</html>
