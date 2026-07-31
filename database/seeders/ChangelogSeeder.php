<?php

namespace Database\Seeders;

use App\Models\ChangelogEntry;
use Illuminate\Database\Seeder;

class ChangelogSeeder extends Seeder
{
    public function run(): void
    {
        $entries = [
            [
                'version' => '1.3.0',
                'title' => 'Scheduled Sending & A/B Testing',
                'content' => "- Added campaign scheduling - set future send times\n- Introduced A/B testing for subject lines\n- Improved analytics with detailed campaign reports\n- Performance optimizations across the platform",
                'released_at' => now()->subDays(5),
                'is_published' => true,
            ],
            [
                'version' => '1.2.0',
                'title' => 'Custom SMTP & Template Categories',
                'content' => "- Added per-user custom SMTP settings\n- Introduced template categories for better organization\n- Enhanced CSV import with column mapping\n- Fixed various UI bugs",
                'released_at' => now()->subDays(19),
                'is_published' => true,
            ],
            [
                'version' => '1.1.0',
                'title' => 'Admin Panel & Usage Quotas',
                'content' => "- New admin panel for user management\n- Usage tracking and monthly email quotas\n- Platform-wide analytics dashboard\n- Subscription plan management",
                'released_at' => now()->subDays(35),
                'is_published' => true,
            ],
            [
                'version' => '1.0.0',
                'title' => 'Initial Release',
                'content' => "- Campaign creation and management\n- Drag-and-drop template builder\n- CSV recipient import\n- Basic analytics (opens, clicks)\n- User authentication and account management",
                'released_at' => now()->subDays(60),
                'is_published' => true,
            ],
        ];

        foreach ($entries as $entry) {
            ChangelogEntry::create($entry);
        }
    }
}
