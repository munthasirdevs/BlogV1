import { H1, Text, Badge } from '@/components/atoms';
import { Container, Section, Grid } from '@/components';
import { useQuery } from '@tanstack/react-query';
import { tagService } from '@/services';
import { Link } from 'react-router-dom';
import { ROUTES } from '@/constants';
import { Tag } from 'lucide-react';

/**
 * Tags page component
 */
function TagsPage() {
  const { data: tagsData, isLoading } = useQuery({
    queryKey: ['tags'],
    queryFn: () => tagService.getAll(),
  });

  const tags = tagsData?.data || [];

  return (
    <Section spacing="lg">
      <Container>
        <div className="mb-8">
          <H1 className="mb-4">Tags</H1>
          <Text color="muted">Browse posts by tags</Text>
        </div>

        <Grid cols={4} gap="md">
          {isLoading
            ? Array(12)
                .fill(0)
                .map((_, i) => (
                  <div
                    key={i}
                    className="p-4 bg-white dark:bg-secondary-800 rounded-lg border border-secondary-200 dark:border-secondary-700 animate-pulse"
                  >
                    <div className="h-4 bg-secondary-200 dark:bg-secondary-700 rounded w-3/4" />
                  </div>
                ))
            : tags.map((tag) => (
                <Link
                  key={tag.id}
                  to={ROUTES.TAG_DETAIL(tag.slug)}
                  className="p-4 bg-white dark:bg-secondary-800 rounded-lg border border-secondary-200 dark:border-secondary-700 hover:shadow-md hover:border-primary-300 dark:hover:border-primary-700 transition-all group"
                >
                  <div className="flex items-center gap-2">
                    <Tag className="w-4 h-4 text-secondary-400 group-hover:text-primary-500 transition-colors" />
                    <span className="font-medium">{tag.name}</span>
                  </div>
                  <div className="mt-2">
                    <Badge variant="secondary">{tag.posts_count || 0} posts</Badge>
                  </div>
                </Link>
              ))}
        </Grid>

        {!isLoading && tags.length === 0 && (
          <div className="text-center py-12">
            <Text color="muted">No tags available yet.</Text>
          </div>
        )}
      </Container>
    </Section>
  );
}

export default TagsPage;
