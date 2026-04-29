<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('mail/unsubscribe.success_title') }}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; padding: 48px 24px; background: #f5f5f5; }
        .card { max-width: 520px; margin: 0 auto; background: white; padding: 40px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
        h1 { margin: 0 0 16px; color: #16a34a; }
        p { color: #444; line-height: 1.6; }
    </style>
</head>
<body>
    <div class="card">
        <h1>{{ __('mail/unsubscribe.success_title') }}</h1>
        <p>{{ __('mail/unsubscribe.success_body') }}</p>
    </div>
</body>
</html>
