import { useParams, Link } from 'react-router-dom';
import { useState } from 'react';
import { H1, H2, Text, Button } from '@/components/atoms';
import { PostGrid } from '@/components/organisms';
import { Container, Section } from '@/components';
import { useIsMd } from '@/hooks';
import { ROUTES } from '@/constants';
import { ArrowLeft, Tag } from 'lucide-react';
import { tagService } from '@/services';
import { useQuery } from '@tanstack/react-query';
import { QUERY_KEYS } from '@/constants';
import { cn } from '@/utils';

/**
 * TagArchivePage - Tag page with posts
 */
function TagArchivePage() {
  const { slug } = useParams();
  const isDesktop = useIsMd();
  const [page, setPage] = useState(1);

  // Fetch tag with posts
  const { data, isLoading, error } = useQuery({
    queryKey: [QUERY_KEYS.TAGS.DETAIL, slug, 'posts', page],
    queryFn: async () => {
      const tag = await tagService.getBySlug(slug);
      const posts = await tagService.getPosts(tag.data.id, { page, limit: 9 });
      return {
        tag: tag.data,
        posts: posts.data,
        meta: posts.meta,
      };
    },
    enabled: !!slug,
    staleTime: 5 * 60 * 1000,
  });

  const tag = data?.tag;
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
            to={ROUTES.TAGS}
            className="inline-flex items-center text-sm text-secondary-600 dark:text-secondary-400 hover:text-primary-600 mb-4"
          >
            <ArrowLeft className="w-4 h-4 mr-1" />
            Back to Tags
          </Link>
          <div className="text-center py-12">
            <H2 className="mb-2">Tag not found</H2>
            <Text color="muted">The tag you're looking for doesn't exist or has been removed.</Text>
          </div>
        </Container>
      </Section>
    );
  }

  if (isLoading || !tag) {
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
          to={ROUTES.TAGS}
          className="inline-flex items-center text-sm text-secondary-600 dark:text-secondary-400 hover:text-primary-600 dark:hover:text-primary-400 mb-8 transition-colors"
        >
          <ArrowLeft className="w-4 h-4 mr-1" />
          Back to Tags
        </Link>

        {/* Tag Header */}
        <div className="mb-12">
          <div className="relative overflow-hidden rounded-2xl bg-gradient-to-br from-secondary-700 to-secondary-900 dark:from-secondary-600 dark:to-secondary-800 p-8 md:p-12">
            {/* Background decoration */}
            <div className="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full blur-3xl" />
            <div className="absolute bottom-0 left-0 w-64 h-64 bg-black/10 rounded-full blur-3xl" />

            <div className="relative z-10">
              <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/20 text-white text-sm font-medium mb-4">
                <Tag className="w-4 h-4" />
                Tag
              </div>
              <H1 className="text-3xl md:text-4xl font-bold text-white mb-4">#{tag.name}</H1>
              {tag.description && (
                <Text className="text-white/90 max-w-2xl">{tag.description}</Text>
              )}
              <div className="flex items-center gap-4 mt-6 text-white/80">
                <div className="flex items-center gap-2">
                  <Tag className="w-5 h-5" />
                  <span>{tag.post_count || posts.length} posts</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Posts */}
        <div>
          <div className="flex items-center justify-between mb-8">
            <H2 className="text-2xl font-bold">
              {pagination.total ? `${pagination.total} Articles tagged "${tag.name}"` : 'Articles'}
            </H2>
          </div>

          <PostGrid
            posts={posts}
            isLoading={isLoading}
            cols={isDesktop ? 3 : 1}
            emptyMessage={`No posts tagged with "${tag.name}" yet.`}
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

        {/* Related Tags */}
        {tag.related_tags && tag.related_tags.length > 0 && (
          <div className="mt-12 pt-12 border-t border-secondary-200 dark:border-secondary-700">
            <H2 className="text-xl font-bold mb-4">Related Tags</H2>
            <div className="flex flex-wrap gap-3">
              {tag.related_tags.map((relatedTag) => (
                <Link
                  key={relatedTag.id}
                  to={ROUTES.TAG_DETAIL(relatedTag.slug)}
                  className="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-secondary-100 dark:bg-secondary-800 text-secondary-700 dark:text-secondary-300 hover:bg-primary-100 dark:hover:bg-primary-900 hover:text-primary-700 dark:hover:text-primary-300 transition-all"
                >
                  <Tag className="w-4 h-4" />
                  #{relatedTag.name}
                </Link>
              ))}
            </div>
          </div>
        )}
      </Container>
    </Section>
  );
}

export default TagArchivePage;
