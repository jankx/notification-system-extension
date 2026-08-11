<?php
/**
 * Default email notification template.
 *
 * Variables available:
 *   $notification — NotificationDTO
 *   $user         — WP_User object
 *   $site_name    — Blog name
 *   $site_url     — Home URL
 */
?><!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html($notification->title); ?></title>
</head>
<body style="margin:0;padding:0;background-color:#f4f5f7;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f5f7;padding:40px 0;">
        <tr>
            <td align="center">
                <table width="560" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.06);">

                    <!-- Header -->
                    <tr>
                        <td style="background:linear-gradient(135deg,#10b981,#047857);padding:32px 40px;text-align:center;">
                            <h1 style="margin:0;color:#ffffff;font-size:20px;font-weight:700;">
                                <?php echo esc_html($site_name); ?>
                            </h1>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding:40px;">
                            <p style="margin:0 0 8px;font-size:14px;color:#6b7280;">
                                Xin chào <?php echo esc_html($user->display_name ?? ''); ?>,
                            </p>

                            <h2 style="margin:0 0 16px;font-size:22px;font-weight:700;color:#111827;">
                                <?php echo esc_html($notification->title); ?>
                            </h2>

                            <?php if (!empty($notification->message)): ?>
                                <p style="margin:0 0 24px;font-size:15px;color:#4b5563;line-height:1.6;">
                                    <?php echo nl2br(esc_html($notification->message)); ?>
                                </p>
                            <?php endif; ?>

                            <!-- Action button (if URL in data) -->
                            <?php if (!empty($notification->data['action_url'])): ?>
                                <p style="text-align:center;margin:32px 0;">
                                    <a href="<?php echo esc_url($notification->data['action_url']); ?>"
                                       style="display:inline-block;padding:12px 32px;background-color:#10b981;color:#ffffff;font-size:15px;font-weight:600;text-decoration:none;border-radius:8px;">
                                        Xem chi tiết
                                    </a>
                                </p>
                            <?php endif; ?>

                            <hr style="border:none;border-top:1px solid #e5e7eb;margin:32px 0;">

                            <p style="margin:0;font-size:13px;color:#9ca3af;">
                                Bạn nhận được email này vì có thông báo mới trên <?php echo esc_html($site_name); ?>.
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color:#f9fafb;padding:20px 40px;text-align:center;border-top:1px solid #e5e7eb;">
                            <p style="margin:0;font-size:12px;color:#9ca3af;">
                                &copy; <?php echo date('Y'); ?> <?php echo esc_html($site_name); ?>. All rights reserved.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
