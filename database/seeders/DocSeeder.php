<?php

namespace Database\Seeders;

use App\Models\Doc;
use Illuminate\Database\Seeder;

class DocSeeder extends Seeder
{
    public function run(): void
    {
        $docs = [
            [
                'slug' => 'getting-started',
                'title' => 'Getting Started with SenderBehi',
                'content' => "Welcome to SenderBehi! This guide will help you get started with creating your first email campaign.\n\n## Creating an Account\n\nSign up for a free account to get started. No credit card required.\n\n## Setting Up Your First Campaign\n\n1. Navigate to the Campaigns page\n2. Click \"Create Campaign\"\n3. Choose a template or create from scratch\n4. Add your recipients\n5. Review and send\n\n## Understanding the Dashboard\n\nThe dashboard gives you an overview of your account activity, including sent campaigns, open rates, and click rates.",
                'category' => 'Getting Started',
                'sort_order' => 1,
            ],
            [
                'slug' => 'creating-templates',
                'title' => 'Creating Email Templates',
                'content' => "Templates are the foundation of your email campaigns. SenderBehi makes it easy to create beautiful, responsive emails.\n\n## Template Editor\n\nUse our drag-and-drop editor to create custom templates. You can also import HTML templates.\n\n## Variables\n\nUse variables like {{name}} to personalize your emails dynamically.\n\n## Best Practices\n\n- Keep your templates responsive\n- Use clear call-to-action buttons\n- Test before sending",
                'category' => 'Templates',
                'sort_order' => 2,
            ],
            [
                'slug' => 'scheduling-campaigns',
                'title' => 'Scheduling Campaigns',
                'content' => "Schedule your campaigns to send at the optimal time for your audience.\n\n## How to Schedule\n\n1. Create your campaign as usual\n2. Set a future scheduled_at date\n3. Click Send - the campaign will be scheduled\n4. SenderBehi will automatically send it at the scheduled time\n\n## Timezone Considerations\n\nAll times are in your account's configured timezone.\n\n## Editing a Scheduled Campaign\n\nYou can edit or cancel a scheduled campaign before it sends.",
                'category' => 'Campaigns',
                'sort_order' => 3,
            ],
            [
                'slug' => 'managing-recipients',
                'title' => 'Managing Recipients',
                'content' => "Learn how to manage your recipient lists effectively.\n\n## Adding Recipients\n\nAdd recipients individually or import them via CSV file.\n\n## CSV Import\n\n1. Navigate to your campaign\n2. Click Import CSV\n3. Map your columns to recipient fields\n4. Review and confirm\n\n## Best Practices\n\n- Always get permission before sending\n- Keep your lists clean\n- Respect unsubscribe requests",
                'category' => 'Campaigns',
                'sort_order' => 4,
            ],
            [
                'slug' => 'understanding-analytics',
                'title' => 'Understanding Analytics',
                'content' => "Track the performance of your email campaigns with SenderBehi's analytics.\n\n## Key Metrics\n\n- **Open Rate**: Percentage of recipients who opened your email\n- **Click Rate**: Percentage of recipients who clicked a link\n- **Bounce Rate**: Percentage of emails that couldn't be delivered\n- **Unsubscribe Rate**: Percentage of recipients who unsubscribed\n\n## Improving Your Results\n\nUse A/B testing to optimize subject lines and content. Monitor your analytics to understand what resonates with your audience.",
                'category' => 'Account',
                'sort_order' => 5,
            ],
        ];

        foreach ($docs as $doc) {
            Doc::create($doc);
        }
    }
}
