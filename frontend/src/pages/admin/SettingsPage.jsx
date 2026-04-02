import { H1, Text, Button, Input, Switch } from '@/components/atoms';
import { Section } from '@/components';
import { Settings, Save } from 'lucide-react';

/**
 * Admin settings page component
 */
function SettingsPage() {
  return (
    <Section spacing="md">
      <div className="mb-8">
        <H1>Settings</H1>
        <Text color="muted">Configure your blog settings</Text>
      </div>

      <div className="space-y-6">
        {/* General Settings */}
        <div className="bg-white dark:bg-secondary-800 rounded-xl border border-secondary-200 dark:border-secondary-700 p-6">
          <H2 className="text-lg font-semibold mb-4">General Settings</H2>
          <div className="space-y-4">
            <Input label="Site Name" placeholder="My Blog" />
            <Input label="Site Description" placeholder="A awesome blog" />
            <Input label="Contact Email" type="email" placeholder="contact@example.com" />
          </div>
        </div>

        {/* SEO Settings */}
        <div className="bg-white dark:bg-secondary-800 rounded-xl border border-secondary-200 dark:border-secondary-700 p-6">
          <H2 className="text-lg font-semibold mb-4">SEO Settings</H2>
          <div className="space-y-4">
            <Input label="Meta Title" placeholder="Site meta title" />
            <Input label="Meta Description" placeholder="Site meta description" />
            <Input label="Google Analytics ID" placeholder="UA-XXXXX-Y" />
          </div>
        </div>

        {/* Save Button */}
        <Button>
          <Save className="w-4 h-4 mr-2" />
          Save Settings
        </Button>
      </div>
    </Section>
  );
}

import { H2 } from '@/components/atoms';

export default SettingsPage;
