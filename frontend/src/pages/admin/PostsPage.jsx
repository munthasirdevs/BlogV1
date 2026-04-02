import { H1, Text, Button } from '@/components/atoms';
import { Section } from '@/components';
import { FileText, Plus } from 'lucide-react';

/**
 * Admin posts management page component
 */
function PostsPage() {
  return (
    <Section spacing="md">
      <div className="flex items-center justify-between mb-8">
        <div>
          <H1>Posts</H1>
          <Text color="muted">Manage all blog posts</Text>
        </div>
        <Button>
          <Plus className="w-4 h-4 mr-2" />
          New Post
        </Button>
      </div>

      <div className="bg-white dark:bg-secondary-800 rounded-xl border border-secondary-200 dark:border-secondary-700 p-12 text-center">
        <FileText className="w-12 h-12 text-secondary-400 mx-auto mb-4" />
        <H2 className="text-lg font-semibold mb-2">No Posts Yet</H2>
        <Text color="muted" className="mb-4">
          Get started by creating your first blog post.
        </Text>
        <Button>
          <Plus className="w-4 h-4 mr-2" />
          Create Post
        </Button>
      </div>
    </Section>
  );
}

export default PostsPage;
