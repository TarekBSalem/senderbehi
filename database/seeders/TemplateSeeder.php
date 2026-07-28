<?php

namespace Database\Seeders;

use App\Models\Template;
use Illuminate\Database\Seeder;

class TemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Newsletter',
                'slug' => 'newsletter',
                'subject' => 'Your Newsletter from {{brand_name}}',
                'category' => 'newsletter',
                'thumbnail' => '/images/templates/newsletter.png',
                'body_html' => $this->getNewsletterHtml(),
            ],
            [
                'name' => 'Promotional Offer',
                'slug' => 'promotional-offer',
                'subject' => 'Special Offer Just for You!',
                'category' => 'promotional',
                'thumbnail' => '/images/templates/promo.png',
                'body_html' => $this->getPromoHtml(),
            ],
            [
                'name' => 'Product Announcement',
                'slug' => 'product-announcement',
                'subject' => 'Introducing Something Amazing',
                'category' => 'announcement',
                'thumbnail' => '/images/templates/announcement.png',
                'body_html' => $this->getAnnouncementHtml(),
            ],
            [
                'name' => 'Welcome Series',
                'slug' => 'welcome-series',
                'subject' => 'Welcome to {{brand_name}}!',
                'category' => 'welcome',
                'thumbnail' => '/images/templates/welcome.png',
                'body_html' => $this->getWelcomeHtml(),
            ],
            [
                'name' => 'Seasonal Campaign',
                'slug' => 'seasonal-campaign',
                'subject' => 'Summer Sale - Hot Deals Inside!',
                'category' => 'promotional',
                'thumbnail' => '/images/templates/seasonal.png',
                'body_html' => $this->getSeasonalHtml(),
            ],
            [
                'name' => 'Feedback Request',
                'slug' => 'feedback-request',
                'subject' => 'We\'d Love Your Feedback',
                'category' => 'engagement',
                'thumbnail' => '/images/templates/feedback.png',
                'body_html' => $this->getFeedbackHtml(),
            ],
            [
                'name' => 'Event Invitation',
                'slug' => 'event-invitation',
                'subject' => 'You\'re Invited to an Exclusive Event',
                'category' => 'announcement',
                'thumbnail' => '/images/templates/event.png',
                'body_html' => $this->getEventHtml(),
            ],
            [
                'name' => 'Re-engagement',
                'slug' => 're-engagement',
                'subject' => 'We Miss You!',
                'category' => 'engagement',
                'thumbnail' => '/images/templates/reengagement.png',
                'body_html' => $this->getReengagementHtml(),
            ],
        ];

        foreach ($templates as $template) {
            Template::updateOrCreate(
                ['slug' => $template['slug']],
                [
                    ...$template,
                    'is_active' => true,
                ]
            );
        }
    }

    private function getNewsletterHtml(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Newsletter</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f4f4;font-family:Arial,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;margin:0 auto;background-color:#ffffff;">
        <tr>
            <td style="background-color:#2563eb;padding:40px;text-align:center;">
                <h1 style="color:#ffffff;margin:0;font-size:28px;">Your Newsletter</h1>
            </td>
        </tr>
        <tr>
            <td style="padding:40px;">
                <p style="font-size:16px;line-height:1.6;color:#333333;">Hi {{name}},</p>
                <p style="font-size:16px;line-height:1.6;color:#333333;">Welcome to this month's newsletter. Here's what's new:</p>
                <h2 style="color:#2563eb;font-size:22px;margin-top:30px;">Featured Article</h2>
                <p style="font-size:16px;line-height:1.6;color:#555555;">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
                <h2 style="color:#2563eb;font-size:22px;margin-top:30px;">Latest Updates</h2>
                <p style="font-size:16px;line-height:1.6;color:#555555;">Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p>
                <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:30px;">
                    <tr>
                        <td style="text-align:center;">
                            <a href="#" style="display:inline-block;background-color:#2563eb;color:#ffffff;padding:15px 30px;text-decoration:none;border-radius:5px;font-weight:bold;">Read More</a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td style="background-color:#1f2937;padding:30px;text-align:center;">
                <p style="color:#9ca3af;font-size:12px;margin:0;">© 2026 MailForge. All rights reserved.</p>
                <p style="color:#9ca3af;font-size:12px;margin:10px 0 0 0;"><a href="#" style="color:#60a5fa;">Unsubscribe</a> | <a href="#" style="color:#60a5fa;">View in Browser</a></p>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    private function getPromoHtml(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Promotional Offer</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f4f4;font-family:Arial,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;margin:0 auto;background-color:#ffffff;">
        <tr>
            <td style="background:linear-gradient(135deg,#dc2626,#b91c1c);padding:50px 40px;text-align:center;">
                <h1 style="color:#ffffff;margin:0;font-size:36px;font-weight:bold;">🔥 SPECIAL OFFER</h1>
                <p style="color:#fecaca;font-size:20px;margin:15px 0 0 0;">Limited Time Only</p>
            </td>
        </tr>
        <tr>
            <td style="padding:40px;text-align:center;">
                <p style="font-size:18px;color:#333333;margin:0 0 20px 0;">Hi {{name}},</p>
                <div style="background-color:#fef2f2;border:2px dashed #dc2626;padding:30px;margin:20px 0;border-radius:10px;">
                    <p style="font-size:24px;font-weight:bold;color:#dc2626;margin:0;">50% OFF</p>
                    <p style="font-size:16px;color:#666666;margin:10px 0 0 0;">Your Next Purchase</p>
                </div>
                <p style="font-size:16px;line-height:1.6;color:#555555;">Use code <strong style="color:#dc2626;">SAVE50</strong> at checkout</p>
                <p style="font-size:14px;color:#999999;">Offer expires in 48 hours</p>
                <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:30px;">
                    <tr>
                        <td style="text-align:center;">
                            <a href="#" style="display:inline-block;background-color:#dc2626;color:#ffffff;padding:18px 40px;text-decoration:none;border-radius:5px;font-weight:bold;font-size:18px;">SHOP NOW</a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td style="background-color:#1f2937;padding:30px;text-align:center;">
                <p style="color:#9ca3af;font-size:12px;margin:0;">© 2026 MailForge. All rights reserved.</p>
                <p style="color:#9ca3af;font-size:12px;margin:10px 0 0 0;"><a href="#" style="color:#60a5fa;">Unsubscribe</a> | <a href="#" style="color:#60a5fa;">View in Browser</a></p>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    private function getAnnouncementHtml(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Announcement</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f4f4;font-family:Arial,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;margin:0 auto;background-color:#ffffff;">
        <tr>
            <td style="background-color:#7c3aed;padding:40px;text-align:center;">
                <p style="color:#c4b5fd;font-size:14px;margin:0;text-transform:uppercase;letter-spacing:2px;">Announcing</p>
                <h1 style="color:#ffffff;margin:15px 0 0 0;font-size:32px;">Something Amazing</h1>
            </td>
        </tr>
        <tr>
            <td style="padding:40px;">
                <p style="font-size:16px;line-height:1.6;color:#333333;">Hi {{name}},</p>
                <p style="font-size:16px;line-height:1.6;color:#555555;">We're thrilled to announce our latest innovation that will transform the way you work.</p>
                <div style="background-color:#f5f3ff;border-left:4px solid #7c3aed;padding:20px;margin:30px 0;">
                    <p style="font-size:18px;font-weight:bold;color:#7c3aed;margin:0;">✨ Key Features</p>
                    <ul style="color:#555555;margin:15px 0 0 0;padding-left:20px;">
                        <li style="margin:8px 0;">Feature one that solves a real problem</li>
                        <li style="margin:8px 0;">Feature two that saves you time</li>
                        <li style="margin:8px 0;">Feature three that boosts productivity</li>
                    </ul>
                </div>
                <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:30px;">
                    <tr>
                        <td style="text-align:center;">
                            <a href="#" style="display:inline-block;background-color:#7c3aed;color:#ffffff;padding:15px 30px;text-decoration:none;border-radius:5px;font-weight:bold;">Learn More</a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td style="background-color:#1f2937;padding:30px;text-align:center;">
                <p style="color:#9ca3af;font-size:12px;margin:0;">© 2026 MailForge. All rights reserved.</p>
                <p style="color:#9ca3af;font-size:12px;margin:10px 0 0 0;"><a href="#" style="color:#60a5fa;">Unsubscribe</a> | <a href="#" style="color:#60a5fa;">View in Browser</a></p>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    private function getWelcomeHtml(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f4f4;font-family:Arial,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;margin:0 auto;background-color:#ffffff;">
        <tr>
            <td style="background-color:#059669;padding:40px;text-align:center;">
                <h1 style="color:#ffffff;margin:0;font-size:32px;">Welcome Aboard! 🎉</h1>
            </td>
        </tr>
        <tr>
            <td style="padding:40px;">
                <p style="font-size:18px;color:#333333;margin:0 0 20px 0;">Hi {{name}},</p>
                <p style="font-size:16px;line-height:1.8;color:#555555;">Thank you for joining us! We're excited to have you on board.</p>
                <p style="font-size:16px;line-height:1.8;color:#555555;">Here's what you can expect:</p>
                <table width="100%" cellpadding="0" cellspacing="0" style="margin:20px 0;">
                    <tr>
                        <td style="padding:15px;border-bottom:1px solid #e5e7eb;">
                            <span style="font-size:24px;">📧</span>
                            <span style="font-size:16px;color:#333333;margin-left:15px;">Weekly tips and insights</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:15px;border-bottom:1px solid #e5e7eb;">
                            <span style="font-size:24px;">🎁</span>
                            <span style="font-size:16px;color:#333333;margin-left:15px;">Exclusive offers for subscribers</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:15px;">
                            <span style="font-size:24px;">🚀</span>
                            <span style="font-size:16px;color:#333333;margin-left:15px;">Early access to new features</span>
                        </td>
                    </tr>
                </table>
                <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:30px;">
                    <tr>
                        <td style="text-align:center;">
                            <a href="#" style="display:inline-block;background-color:#059669;color:#ffffff;padding:15px 30px;text-decoration:none;border-radius:5px;font-weight:bold;">Get Started</a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td style="background-color:#1f2937;padding:30px;text-align:center;">
                <p style="color:#9ca3af;font-size:12px;margin:0;">© 2026 MailForge. All rights reserved.</p>
                <p style="color:#9ca3af;font-size:12px;margin:10px 0 0 0;"><a href="#" style="color:#60a5fa;">Unsubscribe</a> | <a href="#" style="color:#60a5fa;">View in Browser</a></p>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    private function getSeasonalHtml(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seasonal Campaign</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f4f4;font-family:Arial,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;margin:0 auto;background-color:#ffffff;">
        <tr>
            <td style="background:linear-gradient(135deg,#f59e0b,#d97706);padding:50px 40px;text-align:center;">
                <h1 style="color:#ffffff;margin:0;font-size:36px;">☀️ Summer Sale</h1>
                <p style="color:#fef3c7;font-size:18px;margin:15px 0 0 0;">Hot deals for hot days!</p>
            </td>
        </tr>
        <tr>
            <td style="padding:40px;">
                <p style="font-size:16px;color:#333333;margin:0 0 20px 0;">Hi {{name}},</p>
                <p style="font-size:16px;line-height:1.6;color:#555555;">The sun is shining and so are our deals! Check out what's heating up this season.</p>
                <table width="100%" cellpadding="0" cellspacing="0" style="margin:30px 0;">
                    <tr>
                        <td width="48%" style="background-color:#fffbeb;padding:20px;border-radius:8px;text-align:center;">
                            <p style="font-size:24px;font-weight:bold;color:#d97706;margin:0;">30% OFF</p>
                            <p style="font-size:14px;color:#666666;margin:5px 0 0 0;">All Products</p>
                        </td>
                        <td width="4%"></td>
                        <td width="48%" style="background-color:#fff7ed;padding:20px;border-radius:8px;text-align:center;">
                            <p style="font-size:24px;font-weight:bold;color:#ea580c;margin:0;">FREE</p>
                            <p style="font-size:14px;color:#666666;margin:5px 0 0 0;">Shipping Over $50</p>
                        </td>
                    </tr>
                </table>
                <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:30px;">
                    <tr>
                        <td style="text-align:center;">
                            <a href="#" style="display:inline-block;background-color:#f59e0b;color:#ffffff;padding:15px 30px;text-decoration:none;border-radius:5px;font-weight:bold;">Shop the Sale</a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td style="background-color:#1f2937;padding:30px;text-align:center;">
                <p style="color:#9ca3af;font-size:12px;margin:0;">© 2026 MailForge. All rights reserved.</p>
                <p style="color:#9ca3af;font-size:12px;margin:10px 0 0 0;"><a href="#" style="color:#60a5fa;">Unsubscribe</a> | <a href="#" style="color:#60a5fa;">View in Browser</a></p>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    private function getFeedbackHtml(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback Request</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f4f4;font-family:Arial,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;margin:0 auto;background-color:#ffffff;">
        <tr>
            <td style="background-color:#0891b2;padding:40px;text-align:center;">
                <h1 style="color:#ffffff;margin:0;font-size:32px;">We'd Love Your Feedback</h1>
            </td>
        </tr>
        <tr>
            <td style="padding:40px;">
                <p style="font-size:16px;color:#333333;margin:0 0 20px 0;">Hi {{name}},</p>
                <p style="font-size:16px;line-height:1.8;color:#555555;">Your opinion matters to us! We're always looking for ways to improve and would love to hear from you.</p>
                <p style="font-size:16px;line-height:1.8;color:#555555;">This survey takes only 2 minutes and helps us serve you better.</p>
                <div style="background-color:#ecfeff;border:1px solid #a5f3fc;padding:30px;margin:30px 0;border-radius:8px;text-align:center;">
                    <p style="font-size:48px;margin:0;">📊</p>
                    <p style="font-size:18px;color:#0891b2;font-weight:bold;margin:15px 0 0 0;">2-Minute Survey</p>
                </div>
                <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:30px;">
                    <tr>
                        <td style="text-align:center;">
                            <a href="#" style="display:inline-block;background-color:#0891b2;color:#ffffff;padding:15px 30px;text-decoration:none;border-radius:5px;font-weight:bold;">Take the Survey</a>
                        </td>
                    </tr>
                </table>
                <p style="font-size:14px;color:#999999;margin-top:20px;text-align:center;">As a thank you, you'll get 10% off your next order!</p>
            </td>
        </tr>
        <tr>
            <td style="background-color:#1f2937;padding:30px;text-align:center;">
                <p style="color:#9ca3af;font-size:12px;margin:0;">© 2026 MailForge. All rights reserved.</p>
                <p style="color:#9ca3af;font-size:12px;margin:10px 0 0 0;"><a href="#" style="color:#60a5fa;">Unsubscribe</a> | <a href="#" style="color:#60a5fa;">View in Browser</a></p>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    private function getEventHtml(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Invitation</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f4f4;font-family:Arial,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;margin:0 auto;background-color:#ffffff;">
        <tr>
            <td style="background-color:#4f46e5;padding:50px 40px;text-align:center;">
                <p style="color:#c7d2fe;font-size:14px;margin:0;text-transform:uppercase;letter-spacing:2px;">You're Invited</p>
                <h1 style="color:#ffffff;margin:15px 0 0 0;font-size:32px;">Exclusive Event</h1>
            </td>
        </tr>
        <tr>
            <td style="padding:40px;">
                <p style="font-size:16px;color:#333333;margin:0 0 20px 0;">Hi {{name}},</p>
                <p style="font-size:16px;line-height:1.6;color:#555555;">You're invited to an exclusive event that you won't want to miss.</p>
                <div style="background-color:#eef2ff;padding:25px;margin:30px 0;border-radius:8px;border-left:4px solid #4f46e5;">
                    <table width="100%" cellpadding="0" cellspacing="0">
                        <tr>
                            <td style="padding:8px 0;color:#555555;">📅 <strong>Date:</strong> January 15, 2026</td>
                        </tr>
                        <tr>
                            <td style="padding:8px 0;color:#555555;">🕐 <strong>Time:</strong> 2:00 PM EST</td>
                        </tr>
                        <tr>
                            <td style="padding:8px 0;color:#555555;">📍 <strong>Location:</strong> Online (Zoom)</td>
                        </tr>
                    </table>
                </div>
                <p style="font-size:16px;line-height:1.6;color:#555555;">Space is limited, so reserve your spot today!</p>
                <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:30px;">
                    <tr>
                        <td style="text-align:center;">
                            <a href="#" style="display:inline-block;background-color:#4f46e5;color:#ffffff;padding:15px 30px;text-decoration:none;border-radius:5px;font-weight:bold;">RSVP Now</a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td style="background-color:#1f2937;padding:30px;text-align:center;">
                <p style="color:#9ca3af;font-size:12px;margin:0;">© 2026 MailForge. All rights reserved.</p>
                <p style="color:#9ca3af;font-size:12px;margin:10px 0 0 0;"><a href="#" style="color:#60a5fa;">Unsubscribe</a> | <a href="#" style="color:#60a5fa;">View in Browser</a></p>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    private function getReengagementHtml(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>We Miss You</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f4f4;font-family:Arial,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;margin:0 auto;background-color:#ffffff;">
        <tr>
            <td style="background-color:#ec4899;padding:50px 40px;text-align:center;">
                <h1 style="color:#ffffff;margin:0;font-size:32px;">We Miss You! 💝</h1>
            </td>
        </tr>
        <tr>
            <td style="padding:40px;">
                <p style="font-size:16px;color:#333333;margin:0 0 20px 0;">Hi {{name}},</p>
                <p style="font-size:16px;line-height:1.8;color:#555555;">It's been a while since we've seen you, and we wanted to reach out to see how you're doing.</p>
                <p style="font-size:16px;line-height:1.8;color:#555555;">We've been working on some exciting new features and think you'll love what's new.</p>
                <div style="background-color:#fdf2f8;padding:25px;margin:30px 0;border-radius:8px;text-align:center;">
                    <p style="font-size:20px;color:#ec4899;font-weight:bold;margin:0;">Come back and get 25% OFF</p>
                    <p style="font-size:14px;color:#666666;margin:10px 0 0 0;">Use code: <strong>WELCOMEBACK25</strong></p>
                </div>
                <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:30px;">
                    <tr>
                        <td style="text-align:center;">
                            <a href="#" style="display:inline-block;background-color:#ec4899;color:#ffffff;padding:15px 30px;text-decoration:none;border-radius:5px;font-weight:bold;">Welcome Back</a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td style="background-color:#1f2937;padding:30px;text-align:center;">
                <p style="color:#9ca3af;font-size:12px;margin:0;">© 2026 MailForge. All rights reserved.</p>
                <p style="color:#9ca3af;font-size:12px;margin:10px 0 0 0;"><a href="#" style="color:#60a5fa;">Unsubscribe</a> | <a href="#" style="color:#60a5fa;">View in Browser</a></p>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }
}
