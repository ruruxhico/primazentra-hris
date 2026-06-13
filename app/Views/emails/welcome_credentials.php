<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Welcome to PrimaZentra</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f0e8; font-family:Arial, sans-serif;">

  <table width="100%" cellpadding="0" cellspacing="0" style="padding: 40px 0;">
    <tr>
      <td align="center">

        <table width="560" cellpadding="0" cellspacing="0"
               style="background:#ffffff; border-radius:10px; padding:40px 36px;
                      box-shadow: 0 2px 16px rgba(0,0,0,0.08);">
          <tr>
            <td>
              <h1 style="color:#b8860b; margin-top:0; margin-bottom:4px; font-size:24px;">
                PrimaZentra
              </h1>
              <hr style="border:none; border-top:2px solid #f0e6cc; margin-bottom:24px;">

              <h2 style="color:#333; font-size:20px; margin-top:0;">
                Welcome, <?= esc($firstName) ?>!
              </h2>

              <p style="color:#555; line-height:1.7; font-size:15px;">
                Your PrimaZentra account has been created by an administrator.
                Use the temporary credentials below to log in to the
                <strong>PrimaZentra mobile app</strong>.
              </p>

              <!-- Credentials Box -->
              <div style="background:#fdf6e3; border:1px solid #f0e0b8; border-radius:8px; padding:20px; margin:24px 0;">
                <p style="margin:0 0 8px; font-size:13px; color:#999; text-transform:uppercase; letter-spacing:1px;">
                  Email
                </p>
                <p style="margin:0 0 16px; font-size:16px; color:#333; font-weight:bold;">
                  <?= esc($email) ?>
                </p>

                <p style="margin:0 0 8px; font-size:13px; color:#999; text-transform:uppercase; letter-spacing:1px;">
                  Temporary Password
                </p>
                <p style="margin:0; font-size:20px; color:#b8860b; font-weight:bold; font-family:monospace; letter-spacing:2px;">
                  <?= esc($tempPassword) ?>
                </p>
              </div>

              <p style="color:#c0392b; font-size:13px; background:#fde8e8; padding:10px 14px; border-radius:6px;">
                &#9888; For security, you will be asked to change this password on your first login.
              </p>

              <!-- App Download Section -->
              <hr style="border:none; border-top:1px solid #eee; margin:28px 0;">

              <h3 style="color:#333; font-size:16px; margin-bottom:12px;">
                Download the PrimaZentra Mobile App
              </h3>

              <div style="text-align:center; margin: 20px 0;">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=140x140&data=https://play.google.com/store/apps/details?id=com.primazentra"
                     alt="Download QR Code"
                     style="border:8px solid #fff; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.1);">
              </div>

              <div style="text-align:center; margin: 16px 0;">
                <a href="https://play.google.com/store/apps/details?id=com.primazentra"
                   style="display:inline-block; padding:12px 28px;
                          background-color:#b8860b; color:#ffffff;
                          border-radius:6px; text-decoration:none;
                          font-weight:bold; font-size:14px; margin:4px;">
                  Google Play
                </a>
              </div>

              <p style="color:#888; font-size:13px; text-align:center;">
                Scan the QR code or tap the button above to download the app,
                then log in using the credentials provided.
              </p>

              <hr style="border:none; border-top:1px solid #eee; margin-top:32px;">
              <p style="color:#bbb; font-size:12px; margin-bottom:0;">
                If you did not expect this email, please contact your administrator immediately.
              </p>
            </td>
          </tr>
        </table>

      </td>
    </tr>
  </table>

</body>
</html>
