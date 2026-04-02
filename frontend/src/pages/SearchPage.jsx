import { useState, useEffect } from 'react';
import { useSearchParams } from 'react-router-dom';
import { cn } from '@/utils';
import { Search, FileText, Tags, Users, Hash, Loader2, AlertCircle, Filter } from 'lucide-react';
import { searchService } from '@/services';
import { Button, PostCard, Breadcrumb, ScrollToTop } from '@/components';

/**
 * SearchPage component for displaying search results
 */
function SearchPage() {
  const [searchParams] = useSearchParams();
  const query = searchParams.get('q') || '';
  const type = searchParams.get('type') || 'all'; // all, posts, categories, tags, users

  const [results, setResults] = useState(null);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState(null);
  const [activeTab, setActiveTab] = useState(type);

  useEffect(() => {
    if (query) {
      performSearch(query, activeTab);
    }
  }, [query, activeTab]);

  const performSearch = async (searchQuery, searchType) => {
    if (!searchQuery.trim()) {
      setResults(null);
      return;
    }

    setIsLoading(true);
    setError(null);

    try {
      let response;
      switch (searchType) {
        case 'posts':
          response = await searchService.posts(searchQuery);
          break;
        case 'categories':
          response = await searchService.categories(searchQuery);
          break;
        case 'tags':
          response = await searchService.tags(searchQuery);
          break;
        case 'users':
          response = await searchService.users(searchQuery);
          break;
        default:
          response = await searchService.all(searchQuery);
      }
      setResults(response.data || response);
    } catch (err) {
      console.error('Search failed:', err);
      setError(err.response?.data?.message || 'Failed to perform search');
    } finally {
      setIsLoading(false);
    }
  };

  const tabs = [
    { id: 'all', label: 'All', count: results?.all?.total || 0 },
    { id: 'posts', label: 'Posts', count: results?.posts?.total || 0, icon: FileText },
    { id: 'categories', label: 'Categories', count: results?.categories?.total || 0, icon: Tags },
    { id: 'tags', label: 'Tags', count: results?.tags?.total || 0, icon: Hash },
    { id: 'users', label: 'Users', count: results?.users?.total || 0, icon: Users },
  ];

  return (
    <div className="min-h-screen bg-secondary-50 dark:bg-secondary-900">
      {/* Header */}
      <div className="bg-white dark:bg-secondary-800 border-b border-secondary-200 dark:border-secondary-700">
        <div className="container mx-auto px-4 py-8">
          <Breadcrumb className="mb-4" />
          <div className="flex items-center gap-3 mb-4">
            <Search className="w-8 h-8 text-primary-600" />
            <h1 className="text-3xl font-bold text-secondary-900 dark:text-secondary-100">
              Search Results
            </h1>
          </div>
          <p className="text-secondary-600 dark:text-secondary-400">
            {query ? (
              <>
                Found <span className="font-semibold text-primary-600">{results?.total || 0}</span>{' '}
                results for "<span className="font-semibold">{query}</span>"
              </>
            ) : (
              'Enter a search term to find results'
            )}
          </p>
        </div>
      </div>

      <div className="container mx-auto px-4 py-8">
        {/* Search Tabs */}
        {query && (
          <div className="flex items-center gap-2 mb-8 overflow-x-auto pb-2">
            {tabs.map((tab) => (
              <button
                key={tab.id}
                onClick={() => setActiveTab(tab.id)}
                className={cn(
                  'flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium whitespace-nowrap transition-colors',
                  activeTab === tab.id
                    ? 'bg-primary-600 text-white'
                    : 'bg-white dark:bg-secondary-800 text-secondary-600 dark:text-secondary-400 hover:bg-secondary-100 dark:hover:bg-secondary-700'
                )}
              >
                {tab.icon && <tab.icon className="w-4 h-4" />}
                {tab.label}
                {tab.count > 0 && (
                  <span
                    className={cn(
                      'px-2 py-0.5 text-xs rounded-full',
                      activeTab === tab.id
                        ? 'bg-white/20 text-white'
                        : 'bg-secondary-200 dark:bg-secondary-700 text-secondary-600 dark:text-secondary-400'
                    )}
                  >
                    {tab.count}
                  </span>
                )}
              </button>
            ))}
          </div>
        )}

        {/* Loading State */}
        {isLoading && (
          <div className="flex flex-col items-center justify-center py-16">
            <Loader2 className="w-12 h-12 animate-spin text-primary-600 mb-4" />
            <p className="text-secondary-600 dark:text-secondary-400">Searching...</p>
          </div>
        )}

        {/* Error State */}
        {error && (
          <div className="flex flex-col items-center justify-center py-16">
            <AlertCircle className="w-12 h-12 text-danger-600 mb-4" />
            <p className="text-lg font-medium text-secondary-900 dark:text-secondary-100 mb-2">
              Search failed
            </p>
            <p className="text-secondary-600 dark:text-secondary-400">{error}</p>
            <Button
              className="mt-4"
              onClick={() => query && performSearch(query, activeTab)}
            >
              Try Again
            </Button>
          </div>
        )}

        {/* No Results */}
        {!isLoading && !error && query && results && (
          ((activeTab === 'all' && results.total === 0) ||
            (activeTab !== 'all' && results[activeTab]?.total === 0)) && (
            <div className="flex flex-col items-center justify-center py-16">
              <Search className="w-16 h-16 text-secondary-300 dark:text-secondary-700 mb-4" />
              <p className="text-lg font-medium text-secondary-900 dark:text-secondary-100 mb-2">
                No results found
              </p>
              <p className="text-secondary-600 dark:text-secondary-400 text-center max-w-md">
                We couldn't find anything matching "{query}". Try different keywords or check your
                spelling.
              </p>
            </div>
          )
        )}

        {/* Results */}
        {!isLoading && !error && results && (
          <>
            {/* All Results */}
            {activeTab === 'all' && results.total > 0 && (
              <div className="space-y-12">
                {/* Posts */}
                {results.posts?.total > 0 && (
                  <section>
                    <h2 className="text-xl font-bold text-secondary-900 dark:text-secondary-100 mb-4 flex items-center gap-2">
                      <FileText className="w-5 h-5" />
                      Posts ({results.posts.total})
                    </h2>
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                      {results.posts.data?.map((post) => (
                        <PostCard key={post.id} post={post} />
                      ))}
                    </div>
                  </section>
                )}

                {/* Categories */}
                {results.categories?.total > 0 && (
                  <section>
                    <h2 className="text-xl font-bold text-secondary-900 dark:text-secondary-100 mb-4 flex items-center gap-2">
                      <Tags className="w-5 h-5" />
                      Categories ({results.categories.total})
                    </h2>
                    <div className="flex flex-wrap gap-3">
                      {results.categories.data?.map((category) => (
                        <a
                          key={category.id}
                          href={`/categories/${category.slug}`}
                          className="px-4 py-2 bg-white dark:bg-secondary-800 rounded-lg text-sm font-medium text-secondary-700 dark:text-secondary-300 hover:bg-primary-50 dark:hover:bg-primary-900/20 hover:text-primary-600 dark:hover:text-primary-400 transition-colors"
                        >
                          {category.name}
                        </a>
                      ))}
                    </div>
                  </section>
                )}

                {/* Tags */}
                {results.tags?.total > 0 && (
                  <section>
                    <h2 className="text-xl font-bold text-secondary-900 dark:text-secondary-100 mb-4 flex items-center gap-2">
                      <Hash className="w-5 h-5" />
                      Tags ({results.tags.total})
                    </h2>
                    <div className="flex flex-wrap gap-2">
                      {results.tags.data?.map((tag) => (
                        <a
                          key={tag.id}
                          href={`/tags/${tag.slug}`}
                          className="px-3 py-1.5 bg-secondary-100 dark:bg-secondary-800 rounded-full text-sm text-secondary-700 dark:text-secondary-300 hover:bg-primary-100 dark:hover:bg-primary-900/20 hover:text-primary-600 dark:hover:text-primary-400 transition-colors"
                        >
                          #{tag.name}
                        </a>
                      ))}
                    </div>
                  </section>
                )}

                {/* Users */}
                {results.users?.total > 0 && (
                  <section>
                    <h2 className="text-xl font-bold text-secondary-900 dark:text-secondary-100 mb-4 flex items-center gap-2">
                      <Users className="w-5 h-5" />
                      Users ({results.users.total})
                    </h2>
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                      {results.users.data?.map((user) => (
                        <a
                          key={user.id}
                          href={`/profile/${user.username}`}
                          className="flex items-center gap-3 p-4 bg-white dark:bg-secondary-800 rounded-lg hover:bg-secondary-50 dark:hover:bg-secondary-700 transition-colors"
                        >
                          {user.avatar ? (
                            <img
                              src={user.avatar}
                              alt={user.name}
                              className="w-12 h-12 rounded-full object-cover"
                            />
                          ) : (
                            <div className="w-12 h-12 rounded-full bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center text-white font-medium">
                              {user.name?.charAt(0)?.toUpperCase() || 'U'}
                            </div>
                          )}
                          <div>
                            <p className="font-medium text-secondary-900 dark:text-secondary-100">
                              {user.name}
                            </p>
                            <p className="text-sm text-secondary-500 dark:text-secondary-400">
                              @{user.username}
                            </p>
                          </div>
                        </a>
                      ))}
                    </div>
                  </section>
                )}
              </div>
            )}

            {/* Specific Tab Results */}
            {activeTab !== 'all' && results[activeTab]?.total > 0 && (
              <div className="space-y-6">
                {activeTab === 'posts' &&
                  results.posts.data?.map((post) => <PostCard key={post.id} post={post} />)}

                {activeTab === 'categories' && (
                  <div className="flex flex-wrap gap-3">
                    {results.categories.data?.map((category) => (
                      <a
                        key={category.id}
                        href={`/categories/${category.slug}`}
                        className="px-4 py-2 bg-white dark:bg-secondary-800 rounded-lg text-sm font-medium text-secondary-700 dark:text-secondary-300 hover:bg-primary-50 dark:hover:bg-primary-900/20 hover:text-primary-600 dark:hover:text-primary-400 transition-colors"
                      >
                        {category.name}
                      </a>
                    ))}
                  </div>
                )}

                {activeTab === 'tags' && (
                  <div className="flex flex-wrap gap-2">
                    {results.tags.data?.map((tag) => (
                      <a
                        key={tag.id}
                        href={`/tags/${tag.slug}`}
                        className="px-3 py-1.5 bg-secondary-100 dark:bg-secondary-800 rounded-full text-sm text-secondary-700 dark:text-secondary-300 hover:bg-primary-100 dark:hover:bg-primary-900/20 hover:text-primary-600 dark:hover:text-primary-400 transition-colors"
                      >
                        #{tag.name}
                      </a>
                    ))}
                  </div>
                )}

                {activeTab === 'users' && (
                  <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    {results.users.data?.map((user) => (
                      <a
                        key={user.id}
                        href={`/profile/${user.username}`}
                        className="flex items-center gap-3 p-4 bg-white dark:bg-secondary-800 rounded-lg hover:bg-secondary-50 dark:hover:bg-secondary-700 transition-colors"
                      >
                        {user.avatar ? (
                          <img
                            src={user.avatar}
                            alt={user.name}
                            className="w-12 h-12 rounded-full object-cover"
                          />
                        ) : (
                          <div className="w-12 h-12 rounded-full bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center text-white font-medium">
                            {user.name?.charAt(0)?.toUpperCase() || 'U'}
                          </div>
                        )}
                        <div>
                          <p className="font-medium text-secondary-900 dark:text-secondary-100">
                            {user.name}
                          </p>
                          <p className="text-sm text-secondary-500 dark:text-secondary-400">
                            @{user.username}
                          </p>
                        </div>
                      </a>
                    ))}
                  </div>
                )}
              </div>
            )}
          </>
        )}

        {/* No Query */}
        {!query && (
          <div className="flex flex-col items-center justify-center py-16">
            <Search className="w-16 h-16 text-secondary-300 dark:text-secondary-700 mb-4" />
            <p className="text-lg font-medium text-secondary-900 dark:text-secondary-100 mb-2">
              Start your search
            </p>
            <p className="text-secondary-600 dark:text-secondary-400">
              Enter a search term in the search bar above
            </p>
          </div>
        )}
      </div>

      <ScrollToTop />
    </div>
  );
}

export default SearchPage;
