import { H1, Text, Button } from '@/components/atoms';
import { Section } from '@/components';
import { Tags, Plus } from 'lucide-react';

/**
 * Admin categories management page component
 */
function CategoriesPage() {
  return (
    <Section spacing="md">
      <div className="flex items-center justify-between mb-8">
        <div>
          <H1>Categories</H1>
          <Text color="muted">Manage post categories</Text>
        </div>
        <Button>
          <Plus className="w-4 h-4 mr-2" />
          New Category
        </Button>
      </div>

      <div className="bg-white dark:bg-secondary-800 rounded-xl border border-secondary-200 dark:border-secondary-700 p-12 text-center">
        <Tags className="w-12 h-12 text-secondary-400 mx-auto mb-4" />
        <H2 className="text-lg font-semibold mb-2">No Categories Yet</H2>
        <Text color="muted" className="mb-4">
          Get started by creating your first category.
        </Text>
        <Button>
          <Plus className="w-4 h-4 mr-2" />
          Create Category
        </Button>
      </div>
    </Section>
  );
}

export default CategoriesPage;
