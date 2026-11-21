<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('devotee.otp_email_title') }}</title>
</head>
<body style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8fafc; margin: 0; padding: 24px; color: #1e293b;">
    <div style="max-width: 540px; margin: 0 auto; background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
        <div style="background: linear-gradient(135deg, #75141c, #991b1b); padding: 24px; text-align: center; color: #ffffff;">
            <div style="font-size: 32px; font-weight: bold; margin-bottom: 4px;">ॐ</div>
            <h2 style="margin: 0; font-size: 20px; font-weight: bold;">{{ config('app.name') }}</h2>
            <p style="margin: 4px 0 0; font-size: 13px; opacity: 0.85;">{{ __('devotee.portal_title') }}</p>
        </div>

        <div style="padding: 28px 24px;">
            <h3 style="margin-top: 0; font-size: 18px; color: #0f172a;">{{ __('devotee.otp_greeting') }}</h3>
            <p style="font-size: 14px; line-height: 1.6; color: #475569;">
                {{ __('devotee.otp_request_notice', ['purpose' => __('devotee.purpose_'.$purpose)]) }}
            </p>

            <div style="text-align: center; margin: 28px 0;">
                <div style="display: inline-block; padding: 14px 32px; background: #fef2f2; border: 2px dashed #dc2626; border-radius: 8px; font-size: 30px; font-weight: bold; letter-spacing: 6px; color: #991b1b;">
                    {{ $otpCode }}
                </div>
            </div>

            <p style="font-size: 13px; color: #64748b; line-height: 1.5;">
                {{ __('devotee.otp_expiry_notice', ['minutes' => $expiryMinutes]) }}
            </p>

            <div style="margin-top: 24px; padding: 12px 16px; background: #f1f5f9; border-radius: 6px; font-size: 12px; color: #64748b;">
                {{ __('devotee.otp_security_warning') }}
            </div>
        </div>

        <div style="background: #f8fafc; padding: 16px 24px; text-align: center; font-size: 12px; color: #94a3b8; border-top: 1px solid #f1f5f9;">
            &copy; {{ date('Y') }} {{ config('app.name') }}. {{ __('devotee.all_rights_reserved') }}
        </div>
    </div>
</body>
</html>
