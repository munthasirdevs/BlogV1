import { H1, H2, Text } from '@/components/atoms';
import { Card } from '@/components/molecules';
import { Grid, Section } from '@/components';
import { FileText, Users, Tags, Eye } from 'lucide-react';

/**
 * Admin dashboard page component
 */
function DashboardPage() {
  // Mock stats - will be replaced with real API data
  const stats = [
    { label: 'Total Posts', value: '0', icon: FileText, color: 'text-primary-600', bg: 'bg-primary-100 dark:bg-primary-900' },
    { label: 'Total Users', value: '0', icon: Users, color: 'text-success-600', bg: 'bg-success-100 dark:bg-success-900' },
    { label: 'Categories', value: '0', icon: Tags, color: 'text-warning-600', bg: 'bg-warning-100 dark:bg-warning-900' },
    { label: 'Total Views', value: '0', icon: Eye, color: 'text-info-600', bg: 'bg-info-100 dark:bg-info-900' },
  ];

  return (
    <Section spacing="md">
      <div className="mb-8">
        <H1>Dashboard</H1>
        <Text color="muted">Welcome to the admin dashboard</Text>
      </div>

      {/* Stats Grid */}
      <Grid cols={4} gap="md" className="mb-8">
        {stats.map((stat) => (
          <div
            key={stat.label}
            className="p-6 bg-white dark:bg-secondary-800 rounded-xl border border-secondary-200 dark:border-secondary-700"
          >
            <div className="flex items-center justify-between mb-4">
              <div className={`p-3 rounded-lg ${stat.bg}`}>
                <stat.icon className={`w-6 h-6 ${stat.color}`} />
              </div>
            </div>
            <div className="text-3xl font-bold text-secondary-900 dark:text-secondary-100 mb-1">
              {stat.value}
            </div>
            <Text size="sm" color="muted">
              {stat.label}
            </Text>
          </div>
        ))}
      </Grid>

      {/* Recent Activity */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div className="p-6 bg-white dark:bg-secondary-800 rounded-xl border border-secondary-200 dark:border-secondary-700">
          <H2 className="text-lg font-semibold mb-4">Recent Posts</H2>
          <Text color="muted">No recent posts to display.</Text>
        </div>
        <div className="p-6 bg-white dark:bg-secondary-800 rounded-xl border border-secondary-200 dark:border-secondary-700">
          <H2 className="text-lg font-semibold mb-4">Recent Users</H2>
          <Text color="muted">No recent users to display.</Text>
        </div>
      </div>
    </Section>
  );
}

export default DashboardPage;
