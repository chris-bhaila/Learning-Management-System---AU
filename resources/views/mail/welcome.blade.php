<!DOCTYPE html>
<html>
<body style="font-family: 'Inter', Arial, sans-serif; color: #1E2A4A; margin: 0; padding: 24px; background-color: #F9FAFB;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center">
                <table role="presentation" width="480" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 20px; padding: 32px;">
                    <tr>
                        <td>
                            <h1 style="font-family: 'Plus Jakarta Sans', Arial, sans-serif; font-size: 22px; margin: 0 0 16px;">
                                Welcome to {{ $siteName }}, {{ $user->name }}!
                            </h1>
                            <p style="font-size: 15px; line-height: 1.6; margin: 0 0 12px;">
                                Thanks for joining {{ $siteName }}. Your account is ready to go — there's nothing else you need to do to start using it.
                            </p>
                            <p style="font-size: 15px; line-height: 1.6; margin: 0 0 12px;">
                                Next up: ask your teacher for a class token, then enter it from your dashboard to join their class and start enrolling in courses.
                            </p>
                            <p style="font-size: 15px; line-height: 1.6; margin: 24px 0 0;">
                                — The {{ $siteName }} Team
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
