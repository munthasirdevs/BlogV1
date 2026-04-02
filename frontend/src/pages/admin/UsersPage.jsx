import { H1, Text } from '@/components/atoms';
import { Section } from '@/components';
import { Users } from 'lucide-react';

/**
 * Admin users management page component
 */
function UsersPage() {
  return (
    <Section spacing="md">
      <div className="mb-8">
        <H1>Users</H1>
        <Text color="muted">Manage all registered users</Text>
      </div>

      <div className="bg-white dark:bg-secondary-800 rounded-xl border border-secondary-200 dark:border-secondary-700 p-12 text-center">
        <Users className="w-12 h-12 text-secondary-400 mx-auto mb-4" />
        <H2 className="text-lg font-semibold mb-2">No Users Yet</H2>
        <Text color="muted">
          Users will appear here once they register.
        </Text>
      </div>
    </Section>
  );
}

export default UsersPage;
