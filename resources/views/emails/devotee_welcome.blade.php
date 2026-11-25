<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('people.welcome_mail_title') }}</title>
</head>
<body style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8fafc; margin: 0; padding: 24px; color: #1e293b;">
    <div style="max-width: 580px; margin: 0 auto; background: #ffffff; border-radius: 14px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);">
        <!-- Temple Header Banner -->
        <div style="background: linear-gradient(135deg, #75141c, #991b1b); padding: 28px 24px; text-align: center; color: #ffffff;">
            <div style="font-size: 36px; font-weight: bold; line-height: 1; margin-bottom: 6px;">ॐ</div>
            <h2 style="margin: 0; font-size: 22px; font-weight: bold; letter-spacing: 0.5px;">{{ config('app.name') }}</h2>
            <p style="margin: 6px 0 0; font-size: 13px; opacity: 0.9; color: #fef2f2;">{{ __('people.welcome_mail_subtitle') }}</p>
        </div>

        <div style="padding: 32px 28px;">
            <h3 style="margin-top: 0; font-size: 19px; color: #0f172a; margin-bottom: 12px;">
                {{ __('people.welcome_mail_greeting', ['name' => $person->name_en]) }}
            </h3>
            <p style="font-size: 14px; line-height: 1.6; color: #475569; margin-bottom: 20px;">
                {{ __('people.welcome_mail_intro') }}
            </p>

            <!-- Credential Box -->
            <div style="background: #fffaf0; border: 1px solid #fde68a; border-radius: 10px; padding: 20px 24px; margin: 24px 0;">
                <h4 style="margin: 0 0 16px; font-size: 15px; color: #92400e; font-weight: bold; border-bottom: 1px solid #fef3c7; padding-bottom: 8px;">
                    🔑 {{ __('people.login_credentials_card_title') }}
                </h4>
                
                <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                    <tr>
                        <td style="padding: 6px 0; color: #64748b; width: 38%;"><strong>{{ __('people.person_code') }}:</strong></td>
                        <td style="padding: 6px 0; color: #0f172a; font-family: monospace; font-weight: bold;">{{ $person->person_code }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; color: #64748b;"><strong>{{ __('people.login_identifier') }}:</strong></td>
                        <td style="padding: 6px 0; color: #0f172a; font-family: monospace; font-weight: bold;">{{ $loginIdentifier }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; color: #64748b;"><strong>{{ __('people.temporary_password') }}:</strong></td>
                        <td style="padding: 6px 0;">
                            <span style="display: inline-block; background: #fee2e2; border: 1px solid #fca5a5; padding: 3px 10px; border-radius: 5px; color: #991b1b; font-family: monospace; font-size: 15px; font-weight: bold; letter-spacing: 1px;">
                                {{ $plainPassword }}
                            </span>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Login Button -->
            <div style="text-align: center; margin: 30px 0 24px;">
                <a href="{{ $loginUrl }}" target="_blank" style="display: inline-block; background: #75141c; color: #ffffff; padding: 13px 32px; border-radius: 30px; text-decoration: none; font-weight: bold; font-size: 15px; box-shadow: 0 3px 6px rgba(117, 20, 28, 0.25);">
                    {{ __('people.login_portal_btn') }} &rarr;
                </a>
            </div>

            <p style="font-size: 13px; color: #64748b; line-height: 1.5; margin-bottom: 16px;">
                {{ __('people.welcome_mail_change_pass_advice') }}
            </p>

            <div style="padding: 12px 16px; background: #f1f5f9; border-radius: 8px; font-size: 12px; color: #64748b; line-height: 1.5;">
                ℹ️ {{ __('people.welcome_mail_security_notice') }}
            </div>
        </div>

        <!-- Footer -->
        <div style="background: #f8fafc; padding: 18px 24px; text-align: center; font-size: 12px; color: #94a3b8; border-top: 1px solid #f1f5f9;">
            &copy; {{ date('Y') }} {{ config('app.name') }}. {{ __('people.all_rights_reserved') }}
        </div>
    </div>
</body>
</html>
