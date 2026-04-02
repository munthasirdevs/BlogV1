import { useParams, Link } from 'react-router-dom';
import { useState } from 'react';
import { H1, H2, Text, Button } from '@/components/atoms';
import { PostGrid } from '@/components/organisms';
import { Container, Section } from '@/components';
import { useCategoryBySlug, useIsMd } from '@/hooks';
import { ROUTES } from '@/constants';
import { ArrowLeft, BookOpen, FolderOpen } from 'lucide-react';
import { categoryService } from '@/services';
import { useQuery } from '@tanstack/react-query';
import { QUERY_KEYS } from '@/constants';

/**
 * CategoryArchivePage - Category page with posts
 */
function CategoryArchivePage() {
  const { slug } = useParams();
  const isDesktop = useIsMd();
  const [page, setPage] = useState(1);

  // Fetch category with posts
  const { data, isLoading, error } = useQuery({
    queryKey: [QUERY_KEYS.CATEGORIES.DETAIL, slug, 'posts', page],
    queryFn: async () => {
      const category = await categoryService.getBySlug(slug);
      const posts = await categoryService.getPosts(category.data.id, { page, limit: 9 });
      return {
        category: category.data,
        posts: posts.data,
        meta: posts.meta,
      };
    },
    enabled: !!slug,
    staleTime: 5 * 60 * 1000,
  });

  const category = data?.category;
  const posts = data?.posts || [];
  const pagination = data?.meta || {};

  const handlePageChange = (newPage) => {
    setPage(newPage);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  if (error) {
    return (
      <Section spacing="lg">
        <Container>
          <Link
            to={ROUTES.CATEGORIES}
            className="inline-flex items-center text-sm text-secondary-600 dark:text-secondary-400 hover:text-primary-600 mb-4"
          >
            <ArrowLeft className="w-4 h-4 mr-1" />
            Back to Categories
          </Link>
          <div className="text-center py-12">
            <H2 className="mb-2">Category not found</H2>
            <Text color="muted">The category you're looking for doesn't exist or has been removed.</Text>
          </div>
        </Container>
      </Section>
    );
  }

  if (isLoading || !category) {
    return (
      <Section spacing="lg">
        <Container>
          <div className="animate-pulse">
            <div className="h-32 bg-secondary-200 dark:bg-secondary-700 rounded-xl mb-8" />
            <div className="h-8 bg-secondary-200 dark:bg-secondary-700 rounded w-1/3 mb-4" />
            <div className="h-4 bg-secondary-200 dark:bg-secondary-700 rounded w-2/3" />
          </div>
        </Container>
      </Section>
    );
  }

  return (
    <Section spacing="lg">
      <Container>
        {/* Back Link */}
        <Link
          to={ROUTES.CATEGORIES}
          className="inline-flex items-center text-sm text-secondary-600 dark:text-secondary-400 hover:text-primary-600 dark:hover:text-primary-400 mb-8 transition-colors"
        >
          <ArrowLeft className="w-4 h-4 mr-1" />
          Back to Categories
        </Link>

        {/* Category Header */}
        <div className="mb-12">
          <div className="relative overflow-hidden rounded-2xl bg-gradient-to-br from-primary-500 to-secondary-600 p-8 md:p-12">
            {/* Background decoration */}
            <div className="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full blur-3xl" />
            <div className="absolute bottom-0 left-0 w-64 h-64 bg-black/10 rounded-full blur-3xl" />

            <div className="relative z-10">
              <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/20 text-white text-sm font-medium mb-4">
                <FolderOpen className="w-4 h-4" />
                Category
              </div>
              <H1 className="text-3xl md:text-4xl font-bold text-white mb-4">{category.name}</H1>
              {category.description && (
                <Text className="text-white/90 max-w-2xl">{category.description}</Text>
              )}
              <div className="flex items-center gap-4 mt-6 text-white/80">
                <div className="flex items-center gap-2">
                  <BookOpen className="w-5 h-5" />
                  <span>{category.post_count || posts.length} posts</span>
                </div>
                {category.parent_category && (
                  <Link
                    to={ROUTES.CATEGORY_DETAIL(category.parent_category.slug)}
                    className="flex items-center gap-2 hover:text-white transition-colors"
                  >
                    <ArrowLeft className="w-4 h-4 rotate-180" />
                    <span>{category.parent_category.name}</span>
                  </Link>
                )}
              </div>
            </div>
          </div>
        </div>

        {/* Child Categories */}
        {category.child_categories && category.child_categories.length > 0 && (
          <div className="mb-12">
            <H2 className="text-xl font-bold mb-4">Subcategories</H2>
            <div className="flex flex-wrap gap-3">
              {category.child_categories.map((child) => (
                <Link
                  key={child.id}
                  to={ROUTES.CATEGORY_DETAIL(child.slug)}
                  className="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-secondary-100 dark:bg-secondary-800 text-secondary-700 dark:text-secondary-300 hover:bg-primary-100 dark:hover:bg-primary-900 hover:text-primary-700 dark:hover:text-primary-300 transition-all"
                >
                  <FolderOpen className="w-4 h-4" />
                  {child.name}
                  {child.post_count !== undefined && (
                    <span className="text-xs text-secondary-500 dark:text-secondary-400">
                      ({child.post_count})
                    </span>
                  )}
                </Link>
              ))}
            </div>
          </div>
        )}

        {/* Posts */}
        <div>
          <div className="flex items-center justify-between mb-8">
            <H2 className="text-2xl font-bold">
              {pagination.total ? `${pagination.total} Articles` : 'Articles'}
            </H2>
          </div>

          <PostGrid
            posts={posts}
            isLoading={isLoading}
            cols={isDesktop ? 3 : 1}
            emptyMessage="No posts in this category yet."
          />

          {/* Pagination */}
          {pagination.last_page > 1 && (
            <div className="mt-12 flex items-center justify-center gap-2">
              <Button
                variant="outline"
                disabled={pagination.current_page === 1}
                onClick={() => handlePageChange(pagination.current_page - 1)}
                aria-label="Previous page"
              >
                Previous
              </Button>

              <div className="flex items-center gap-1">
                {Array.from({ length: Math.min(5, pagination.last_page) }, (_, i) => {
                  let pageNum;
                  if (pagination.last_page <= 5) {
                    pageNum = i + 1;
                  } else if (pagination.current_page <= 3) {
                    pageNum = i + 1;
                  } else if (pagination.current_page >= pagination.last_page - 2) {
                    pageNum = pagination.last_page - 4 + i;
                  } else {
                    pageNum = pagination.current_page - 2 + i;
                  }

                  return (
                    <button
                      key={pageNum}
                      onClick={() => handlePageChange(pageNum)}
                      className={cn(
                        'w-10 h-10 rounded-lg text-sm font-medium transition-all',
                        pagination.current_page === pageNum
                          ? 'bg-primary-600 text-white'
                          : 'bg-secondary-100 dark:bg-secondary-800 text-secondary-600 dark:text-secondary-400 hover:bg-secondary-200 dark:hover:bg-secondary-700'
                      )}
                      aria-label={`Page ${pageNum}`}
                      aria-current={pagination.current_page === pageNum ? 'page' : undefined}
                    >
                      {pageNum}
                    </button>
                  );
                })}
              </div>

              <Button
                variant="outline"
                disabled={pagination.current_page === pagination.last_page}
                onClick={() => handlePageChange(pagination.current_page + 1)}
                aria-label="Next page"
              >
                Next
              </Button>
            </div>
          )}
        </div>
      </Container>
    </Section>
  );
}

// Import cn for pagination
import { cn } from '@/utils';

export default CategoryArchivePage;
