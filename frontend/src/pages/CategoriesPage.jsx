import { H1, Text, Badge } from '@/components/atoms';
import { Container, Section, Grid } from '@/components';
import { useQuery } from '@tanstack/react-query';
import { categoryService } from '@/services';
import { Link } from 'react-router-dom';
import { ROUTES } from '@/constants';
import { FolderOpen } from 'lucide-react';

/**
 * Categories page component
 */
function CategoriesPage() {
  const { data: categoriesData, isLoading } = useQuery({
    queryKey: ['categories'],
    queryFn: () => categoryService.getAll(),
  });

  const categories = categoriesData?.data || [];

  return (
    <Section spacing="lg">
      <Container>
        <div className="mb-8">
          <H1 className="mb-4">Categories</H1>
          <Text color="muted">Browse posts by category</Text>
        </div>

        <Grid cols={3} gap="lg">
          {isLoading
            ? Array(6)
                .fill(0)
                .map((_, i) => (
                  <div
                    key={i}
                    className="p-6 bg-white dark:bg-secondary-800 rounded-xl border border-secondary-200 dark:border-secondary-700 animate-pulse"
                  >
                    <div className="h-6 bg-secondary-200 dark:bg-secondary-700 rounded w-3/4 mb-2" />
                    <div className="h-4 bg-secondary-200 dark:bg-secondary-700 rounded w-1/2" />
                  </div>
                ))
            : categories.map((category) => (
                <Link
                  key={category.id}
                  to={ROUTES.CATEGORY_DETAIL(category.slug)}
                  className="p-6 bg-white dark:bg-secondary-800 rounded-xl border border-secondary-200 dark:border-secondary-700 hover:shadow-lg transition-all group"
                >
                  <div className="flex items-center gap-3 mb-3">
                    <div className="p-2 bg-primary-100 dark:bg-primary-900 rounded-lg group-hover:bg-primary-200 dark:group-hover:bg-primary-800 transition-colors">
                      <FolderOpen className="w-6 h-6 text-primary-600 dark:text-primary-400" />
                    </div>
                    <H1 className="text-lg font-semibold">{category.name}</H1>
                  </div>
                  {category.description && (
                    <Text color="muted" className="line-clamp-2">
                      {category.description}
                    </Text>
                  )}
                  <div className="mt-4">
                    <Badge variant="primary">{category.posts_count || 0} posts</Badge>
                  </div>
                </Link>
              ))}
        </Grid>

        {!isLoading && categories.length === 0 && (
          <div className="text-center py-12">
            <Text color="muted">No categories available yet.</Text>
          </div>
        )}
      </Container>
    </Section>
  );
}

export default CategoriesPage;
