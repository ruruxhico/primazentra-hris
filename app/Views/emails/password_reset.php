<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset</title>
</head>
<body style="margin:0;padding:0;background:#f7f2e7;font-family:Arial,Helvetica,sans-serif;color:#3d3120;">
    <div style="max-width:640px;margin:0 auto;padding:32px 16px;">
        <div style="background:#fffaf0;border:1px solid #e3c98a;border-radius:18px;box-shadow:0 10px 24px rgba(140,112,28,.08);overflow:hidden;">
            <div style="padding:28px 28px 12px;">
                <h1 style="margin:0 0 16px;font-size:28px;line-height:1.2;color:#9a7918;">Reset your PrimaZentra password</h1>
                <p style="margin:0 0 20px;font-size:16px;line-height:1.6;">We received a password reset request for <strong><?= esc($email) ?></strong>.</p>
                <p style="margin:0 0 26px;font-size:16px;line-height:1.6;">Click the button below to open the PrimaZentra reset page.</p>
                <div style="text-align:center;margin:28px 0 30px;">
                    <a href="<?= esc($resetLink) ?>" style="display:inline-block;background:#9b771b;color:#fff9e8;text-decoration:none;font-weight:700;letter-spacing:.03em;padding:14px 26px;border-radius:999px;box-shadow:0 8px 16px rgba(141,109,25,.18);">Reset Password</a>
                </div>
                <p style="margin:0 0 10px;font-size:14px;line-height:1.6;color:#6f5d45;">If the button does not work, copy and paste this link into your browser:</p>
                <p style="margin:0 0 8px;font-size:14px;line-height:1.6;word-break:break-all;"><a href="<?= esc($resetLink) ?>" style="color:#3954df;"><?= esc($resetLink) ?></a></p>
                <p style="margin:24px 0 0;font-size:13px;line-height:1.6;color:#8a7d69;">If you did not request this change, you can ignore this email.</p>
            </div>
        </div>
    </div>
</body>
</html>
