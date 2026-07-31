<?php

namespace Database\Seeders;

use App\Models\BlogPost;
use Illuminate\Database\Seeder;

class BlogPostSeeder extends Seeder
{
    public function run(): void
    {
        $posts = [
            [
                'slug' => 'email-marketing-best-practices-2026',
                'title' => 'Email Marketing Best Practices for 2026',
                'content' => "Email marketing continues to evolve. Here are the key trends and best practices for 2026.\n\n## Personalization is Key\n\nIn 2026, personalization goes beyond using the recipient's name. Use behavioral data to send relevant content at the right time.\n\n## Mobile-First Design\n\nWith over 60% of emails opened on mobile devices, responsive design is no longer optional.\n\n## Deliverability Matters\n\nMaintain a clean sender reputation by following SPF, DKIM, and DMARC best practices.\n\n## AI-Powered Campaigns\n\nLeverage AI for subject line optimization, send time optimization, and content personalization.",
                'excerpt' => 'Discover the key trends and best practices that will define email marketing success in 2026.',
                'published_at' => now()->subDays(2),
                'is_published' => true,
            ],
            [
                'slug' => 'improving-email-deliverability',
                'title' => 'How to Improve Your Email Deliverability',
                'content' => "Email deliverability is crucial for the success of your campaigns. Here's how to ensure your emails reach the inbox.\n\n## Authentication is Essential\n\nSet up SPF, DKIM, and DMARC records for your sending domain. These protocols help ISPs verify that your emails are legitimate.\n\n## Maintain a Clean List\n\nRegularly remove inactive subscribers and bounced emails. A clean list improves engagement metrics.\n\n## Monitor Your Reputation\n\nKeep an eye on your sender score and address any issues promptly.\n\n## Best Practices\n\n- Use a consistent sending schedule\n- Avoid spam trigger words\n- Provide clear unsubscribe options\n- Test your emails before sending",
                'excerpt' => 'Learn how to optimize your email deliverability and ensure your campaigns reach the inbox.',
                'published_at' => now()->subDays(7),
                'is_published' => true,
            ],
            [
                'slug' => 'template-design-tips',
                'title' => 'Email Template Design Tips That Drive Conversions',
                'content' => "The design of your email templates plays a crucial role in conversion rates. Here are tips to create templates that drive results.\n\n## Keep It Simple\n\nClean, minimal designs often perform better than complex layouts. Focus on your message and call-to-action.\n\n## Visual Hierarchy\n\nGuide the reader's eye with clear visual hierarchy. Use headings, images, and buttons strategically.\n\n## Mobile Optimization\n\nTest your templates on multiple devices. Use responsive design techniques to ensure a great experience everywhere.\n\n## Call-to-Action\n\nMake your CTA clear and compelling. Use action-oriented language and make the button stand out.",
                'excerpt' => 'Discover email design techniques that boost engagement and drive conversions.',
                'published_at' => now()->subDays(14),
                'is_published' => true,
            ],
        ];

        foreach ($posts as $post) {
            BlogPost::create($post);
        }
    }
}
