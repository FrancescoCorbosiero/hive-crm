<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ __('mail/unsubscribe.confirm_title') }}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; padding: 48px 24px; background: #f5f5f5; }
        .card { max-width: 520px; margin: 0 auto; background: white; padding: 40px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
        h1 { margin: 0 0 16px; color: #222; }
        p { color: #444; line-height: 1.6; }
        button { margin-top: 8px; padding: 12px 20px; border: 0; border-radius: 6px; background: #dc2626; color: white; font-size: 16px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="card">
        <h1>{{ __('mail/unsubscribe.confirm_title') }}</h1>
        <p>{{ __('mail/unsubscribe.confirm_body') }}</p>
        <form method="POST" action="{{ $action }}">
            <button type="submit">{{ __('mail/unsubscribe.confirm_button') }}</button>
        </form>
    </div>
</body>
</html>
