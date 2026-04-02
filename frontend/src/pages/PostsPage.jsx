import { useState, useEffect, useMemo } from 'react';
import { useSearchParams, useNavigate } from 'react-router-dom';
import { H1, Text, Button, Input } from '@/components/atoms';
import { PostGrid, FilterSidebar } from '@/components/organisms';
import { Container, Section } from '@/components';
import { usePosts, useIsLg } from '@/hooks';
import { Search, Filter, SlidersHorizontal, X, ChevronDown } from 'lucide-react';
import { debounce } from '@/utils';

/**
 * BlogListPage - Posts listing with filters, search, sorting, and pagination
 */
function PostsPage() {
  const [searchParams, setSearchParams] = useSearchParams();
  const navigate = useNavigate();
  const isDesktop = useIsLg();

  // State
  const [searchQuery, setSearchQuery] = useState(searchParams.get('q') || '');
  const [showFilters, setShowFilters] = useState(false);
  const [filters, setFilters] = useState({
    categories: searchParams.get('categories')?.split(',').filter(Boolean) || [],
    tags: searchParams.get('tags')?.split(',').filter(Boolean) || [],
  });

  // Get sort from URL or default to 'newest'
  const sortParam = searchParams.get('sort') || 'newest';
  const pageParam = parseInt(searchParams.get('page') || '1', 10);

  // Map sort param to API sort field
  const sortOptions = useMemo(() => [
    { value: 'newest', label: 'Newest', api: '-published_at' },
    { value: 'oldest', label: 'Oldest', api: 'published_at' },
    { value: 'popular', label: 'Most Popular', api: '-view_count' },
    { value: 'trending', label: 'Trending', api: '-trending_score' },
  ], []);

  const currentSort = sortOptions.find((s) => s.value === sortParam) || sortOptions[0];

  // Debounced search
  const debouncedSearch = useMemo(
    () => debounce((value) => {
      const params = new URLSearchParams(searchParams);
      if (value) {
        params.set('q', value);
      } else {
        params.delete('q');
      }
      params.set('page', '1');
      setSearchParams(params);
    }, 500),
    [searchParams, setSearchParams]
  );

  useEffect(() => {
    debouncedSearch(searchQuery);
  }, [searchQuery, debouncedSearch]);

  // Update filters in URL
  useEffect(() => {
    const params = new URLSearchParams(searchParams);
    if (filters.categories.length > 0) {
      params.set('categories', filters.categories.join(','));
    } else {
      params.delete('categories');
    }
    if (filters.tags.length > 0) {
      params.set('tags', filters.tags.join(','));
    } else {
      params.delete('tags');
    }
    params.set('page', '1');
    setSearchParams(params);
  }, [filters.categories, filters.tags]);

  // Fetch posts
  const { data: postsData, isLoading } = usePosts({
    page: pageParam,
    limit: 9,
    search: searchParams.get('q') || undefined,
    category: filters.categories[0] || undefined,
    sort: currentSort.api,
  });

  const posts = postsData?.data || [];
  const pagination = postsData?.meta || {};

  const handlePageChange = (newPage) => {
    const params = new URLSearchParams(searchParams);
    params.set('page', newPage.toString());
    setSearchParams(params);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  const handleSortChange = (value) => {
    const params = new URLSearchParams(searchParams);
    params.set('sort', value);
    params.set('page', '1');
    setSearchParams(params);
  };

  const handleClearFilters = () => {
    setFilters({ categories: [], tags: [] });
    setSearchQuery('');
    const params = new URLSearchParams();
    params.set('page', '1');
    setSearchParams(params);
  };

  const hasActiveFilters = filters.categories.length > 0 || filters.tags.length > 0 || searchQuery;

  return (
    <Section spacing="lg">
      <Container>
        {/* Header */}
        <div className="mb-8">
          <H1 className="mb-2">All Posts</H1>
          <Text color="muted">
            Browse through our collection of articles and stories.
            {hasActiveFilters && (
              <span className="ml-2 text-primary-600 dark:text-primary-400">
                ({pagination.total || 0} results)
              </span>
            )}
          </Text>
        </div>

        {/* Search and Filter Bar */}
        <div className="flex flex-col sm:flex-row gap-3 mb-8">
          {/* Search */}
          <div className="relative flex-1">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-secondary-400" />
            <Input
              type="search"
              placeholder="Search posts..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="pl-10"
              aria-label="Search posts"
            />
            {searchQuery && (
              <button
                onClick={() => setSearchQuery('')}
                className="absolute right-3 top-1/2 -translate-y-1/2 p-1 rounded-full hover:bg-secondary-100 dark:hover:bg-secondary-800"
                aria-label="Clear search"
              >
                <X className="w-4 h-4 text-secondary-400" />
              </button>
            )}
          </div>

          {/* Filter Toggle - Mobile */}
          <Button
            variant="outline"
            onClick={() => setShowFilters(true)}
            className="lg:hidden"
            aria-label="Open filters"
          >
            <SlidersHorizontal className="w-4 h-4 mr-2" />
            Filters
          </Button>

          {/* Sort Dropdown */}
          <div className="relative">
            <select
              value={sortParam}
              onChange={(e) => handleSortChange(e.target.value)}
              className="appearance-none w-full sm:w-auto px-4 py-2.5 pr-10 rounded-lg border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 text-secondary-900 dark:text-secondary-100 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-primary-500 cursor-pointer"
              aria-label="Sort posts"
            >
              {sortOptions.map((option) => (
                <option key={option.value} value={option.value}>
                  {option.label}
                </option>
              ))}
            </select>
            <ChevronDown className="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-secondary-400 pointer-events-none" />
          </div>
        </div>

        {/* Active Filters */}
        {hasActiveFilters && (
          <div className="flex flex-wrap items-center gap-2 mb-6 pb-4 border-b border-secondary-200 dark:border-secondary-700">
            <span className="text-sm text-secondary-500 dark:text-secondary-400">Filters:</span>
            {searchQuery && (
              <span className="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400 text-sm">
                Search: {searchQuery}
                <button onClick={() => setSearchQuery('')} aria-label="Clear search">
                  <X className="w-3 h-3" />
                </button>
              </span>
            )}
            {filters.categories.map((catId) => (
              <span
                key={catId}
                className="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400 text-sm"
              >
                Category {catId}
                <button onClick={() => setFilters(f => ({ ...f, categories: f.categories.filter(c => c !== catId) }))} aria-label="Remove category">
                  <X className="w-3 h-3" />
                </button>
              </span>
            ))}
            {filters.tags.map((tagId) => (
              <span
                key={tagId}
                className="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-secondary-100 dark:bg-secondary-800 text-secondary-700 dark:text-secondary-400 text-sm"
              >
                Tag {tagId}
                <button onClick={() => setFilters(f => ({ ...f, tags: f.tags.filter(t => t !== tagId) }))} aria-label="Remove tag">
                  <X className="w-3 h-3" />
                </button>
              </span>
            ))}
            <Button variant="ghost" size="sm" onClick={handleClearFilters} className="ml-auto">
              Clear all
            </Button>
          </div>
        )}

        <div className="flex gap-8">
          {/* Filter Sidebar - Desktop */}
          {isDesktop && (
            <div className="w-64 flex-shrink-0">
              <FilterSidebar
                filters={filters}
                onFilterChange={setFilters}
                onClearFilters={handleClearFilters}
              />
            </div>
          )}

          {/* Posts Grid */}
          <div className="flex-1 min-w-0">
            <PostGrid
              posts={posts}
              isLoading={isLoading}
              cols={isDesktop ? 2 : 1}
              emptyMessage={
                hasActiveFilters
                  ? 'No posts match your filters. Try adjusting your search or filters.'
                  : 'No posts available yet. Check back later!'
              }
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
        </div>
      </Container>

      {/* Filter Sidebar - Mobile */}
      {showFilters && (
        <FilterSidebar
          filters={filters}
          onFilterChange={setFilters}
          onClearFilters={handleClearFilters}
          isMobile
          onClose={() => setShowFilters(false)}
        />
      )}
    </Section>
  );
}

// Import cn for pagination
import { cn } from '@/utils';

export default PostsPage;
