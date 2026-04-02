import { Link } from 'react-router-dom';
import { H1, H2, H3, Text, Button } from '@/components/atoms';
import { PostCard, ReadingProgress } from '@/components/molecules';
import { Container, Section, Grid } from '@/components';
import { useFeaturedPosts, useTrendingPosts, useCategories } from '@/hooks';
import { ROUTES } from '@/constants';
import { ArrowRight, TrendingUp, BookOpen, Mail, Sparkles } from 'lucide-react';
import { useState } from 'react';
import { newsletterService } from '@/services';

/**
 * Enhanced Home page with hero, featured, trending, categories, and newsletter sections
 */
function HomePage() {
  const [email, setEmail] = useState('');
  const [newsletterStatus, setNewsletterStatus] = useState('idle');

  const { data: featuredData, isLoading: featuredLoading } = useFeaturedPosts({ limit: 6 });
  const { data: trendingData, isLoading: trendingLoading } = useTrendingPosts({ limit: 5 });
  const { data: categoriesData, isLoading: categoriesLoading } = useCategories({ limit: 6 });

  const featuredPosts = featuredData?.data || [];
  const trendingPosts = trendingData?.data || [];
  const categories = categoriesData?.data || [];

  const handleNewsletterSubscribe = async (e) => {
    e.preventDefault();
    if (!email) return;

    setNewsletterStatus('loading');
    try {
      await newsletterService.subscribe(email);
      setNewsletterStatus('success');
      setEmail('');
      setTimeout(() => setNewsletterStatus('idle'), 3000);
    } catch (error) {
      setNewsletterStatus('error');
      setTimeout(() => setNewsletterStatus('idle'), 3000);
    }
  };

  return (
    <>
      {/* Hero Section */}
      <Section spacing="xl" className="relative overflow-hidden bg-gradient-to-br from-primary-50 via-white to-secondary-50 dark:from-secondary-900 dark:via-secondary-900 dark:to-secondary-800">
        {/* Background decoration */}
        <div className="absolute inset-0 overflow-hidden pointer-events-none">
          <div className="absolute -top-40 -right-40 w-80 h-80 bg-primary-200 dark:bg-primary-800 rounded-full opacity-20 blur-3xl" />
          <div className="absolute -bottom-40 -left-40 w-80 h-80 bg-secondary-200 dark:bg-secondary-800 rounded-full opacity-20 blur-3xl" />
        </div>

        <Container className="relative">
          <div className="max-w-4xl mx-auto text-center py-16 md:py-24">
            {/* Badge */}
            <div className="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400 text-sm font-medium mb-6 animate-fade-in">
              <Sparkles className="w-4 h-4" />
              <span>Welcome to our community</span>
            </div>

            {/* Headline */}
            <H1 className="mb-6 animate-fade-in-up">
              Discover Amazing{' '}
              <span className="text-transparent bg-clip-text bg-gradient-to-r from-primary-600 to-secondary-600 dark:from-primary-400 dark:to-secondary-400">
                Stories & Ideas
              </span>
            </H1>

            {/* Subheading */}
            <Text size="xl" color="muted" className="mb-10 max-w-2xl mx-auto animate-fade-in-up animation-delay-200">
              Explore insightful articles, tutorials, and stories from our community of writers.
              Share your knowledge and connect with readers worldwide.
            </Text>

            {/* CTA Buttons */}
            <div className="flex flex-col sm:flex-row gap-4 justify-center animate-fade-in-up animation-delay-400">
              <Button size="lg" onClick={() => (window.location.href = ROUTES.POSTS)}>
                <BookOpen className="w-5 h-5 mr-2" />
                Explore Posts
                <ArrowRight className="w-4 h-4 ml-2" />
              </Button>
              <Button size="lg" variant="outline" onClick={() => (window.location.href = ROUTES.CATEGORIES)}>
                Browse Categories
              </Button>
            </div>

            {/* Stats */}
            <div className="grid grid-cols-3 gap-8 mt-16 pt-8 border-t border-secondary-200 dark:border-secondary-700 animate-fade-in animation-delay-600">
              <div>
                <p className="text-3xl md:text-4xl font-bold text-secondary-900 dark:text-secondary-100">10K+</p>
                <p className="text-sm text-secondary-500 dark:text-secondary-400 mt-1">Articles</p>
              </div>
              <div>
                <p className="text-3xl md:text-4xl font-bold text-secondary-900 dark:text-secondary-100">50K+</p>
                <p className="text-sm text-secondary-500 dark:text-secondary-400 mt-1">Readers</p>
              </div>
              <div>
                <p className="text-3xl md:text-4xl font-bold text-secondary-900 dark:text-secondary-100">500+</p>
                <p className="text-sm text-secondary-500 dark:text-secondary-400 mt-1">Writers</p>
              </div>
            </div>
          </div>
        </Container>
      </Section>

      {/* Featured Posts Section */}
      <Section spacing="xl">
        <Container>
          <div className="flex items-center justify-between mb-8">
            <div className="flex items-center gap-3">
              <div className="p-2 rounded-lg bg-primary-100 dark:bg-primary-900/30">
                <Sparkles className="w-5 h-5 text-primary-600 dark:text-primary-400" />
              </div>
              <H2>Featured Posts</H2>
            </div>
            <Link to={ROUTES.POSTS}>
              <Button variant="ghost">
                View All
                <ArrowRight className="w-4 h-4 ml-1" />
              </Button>
            </Link>
          </div>

          <Grid cols={3} gap="lg">
            {featuredLoading
              ? Array(6)
                  .fill(0)
                  .map((_, i) => <PostCard key={i} isLoading />)
              : featuredPosts.slice(0, 6).map((post) => (
                  <Link key={post.id} to={ROUTES.POST_DETAIL(post.slug)} className="block">
                    <PostCard post={post} />
                  </Link>
                ))}
          </Grid>

          {!featuredLoading && featuredPosts.length === 0 && (
            <div className="text-center py-12">
              <Text color="muted">No featured posts available yet.</Text>
            </div>
          )}
        </Container>
      </Section>

      {/* Trending Posts Section */}
      <Section spacing="xl" className="bg-secondary-50 dark:bg-secondary-800/50">
        <Container>
          <div className="flex items-center justify-between mb-8">
            <div className="flex items-center gap-3">
              <div className="p-2 rounded-lg bg-accent-100 dark:bg-accent-900/30">
                <TrendingUp className="w-5 h-5 text-accent-600 dark:text-accent-400" />
              </div>
              <H2>Trending Now</H2>
            </div>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            {trendingLoading
              ? Array(5)
                  .fill(0)
                  .map((_, i) => (
                    <div key={i} className="animate-pulse">
                      <div className="flex gap-4 p-4 bg-white dark:bg-secondary-800 rounded-xl">
                        <div className="w-20 h-20 bg-secondary-200 dark:bg-secondary-700 rounded-lg flex-shrink-0" />
                        <div className="flex-1 space-y-2">
                          <div className="h-4 bg-secondary-200 dark:bg-secondary-700 rounded w-3/4" />
                          <div className="h-3 bg-secondary-200 dark:bg-secondary-700 rounded w-1/2" />
                        </div>
                      </div>
                    </div>
                  ))
              : trendingPosts.slice(0, 5).map((post, index) => (
                  <Link
                    key={post.id}
                    to={ROUTES.POST_DETAIL(post.slug)}
                    className="group flex gap-4 p-4 bg-white dark:bg-secondary-800 rounded-xl hover:shadow-lg transition-all"
                  >
                    <div className="flex-shrink-0">
                      <span className="text-2xl font-bold text-secondary-300 dark:text-secondary-600">
                        {String(index + 1).padStart(2, '0')}
                      </span>
                    </div>
                    {post.featured_image && (
                      <img
                        src={post.featured_image}
                        alt={post.title}
                        className="w-20 h-20 object-cover rounded-lg flex-shrink-0"
                      />
                    )}
                    <div className="flex-1 min-w-0">
                      <h3 className="font-semibold text-secondary-900 dark:text-secondary-100 group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors line-clamp-2">
                        {post.title}
                      </h3>
                      <div className="flex items-center gap-2 mt-2 text-xs text-secondary-500 dark:text-secondary-400">
                        <span>{post.author?.name}</span>
                        {post.view_count !== undefined && (
                          <>
                            <span>•</span>
                            <span>{post.view_count.toLocaleString()} views</span>
                          </>
                        )}
                      </div>
                    </div>
                  </Link>
                ))}
          </div>
        </Container>
      </Section>

      {/* Categories Section */}
      <Section spacing="xl">
        <Container>
          <div className="flex items-center justify-between mb-8">
            <div className="flex items-center gap-3">
              <div className="p-2 rounded-lg bg-secondary-100 dark:bg-secondary-800">
                <BookOpen className="w-5 h-5 text-secondary-600 dark:text-secondary-400" />
              </div>
              <H2>Browse by Category</H2>
            </div>
            <Link to={ROUTES.CATEGORIES}>
              <Button variant="ghost">
                View All
                <ArrowRight className="w-4 h-4 ml-1" />
              </Button>
            </Link>
          </div>

          <Grid cols={3} gap="lg">
            {categoriesLoading
              ? Array(6)
                  .fill(0)
                  .map((_, i) => (
                    <div key={i} className="animate-pulse">
                      <div className="h-32 bg-secondary-200 dark:bg-secondary-700 rounded-xl" />
                    </div>
                  ))
              : categories.slice(0, 6).map((category) => (
                  <Link
                    key={category.id}
                    to={ROUTES.CATEGORY_DETAIL(category.slug)}
                    className="group relative overflow-hidden rounded-xl bg-gradient-to-br from-primary-500 to-secondary-600 p-6 text-white hover:shadow-lg transition-all"
                  >
                    <div className="relative z-10">
                      <H3 className="text-xl font-bold mb-2">{category.name}</H3>
                      {category.description && (
                        <p className="text-sm text-white/80 line-clamp-2">{category.description}</p>
                      )}
                      {category.post_count !== undefined && (
                        <p className="text-xs text-white/60 mt-3">{category.post_count} posts</p>
                      )}
                    </div>
                    <div className="absolute inset-0 bg-black/10 group-hover:bg-black/20 transition-colors" />
                  </Link>
                ))}
          </Grid>
        </Container>
      </Section>

      {/* Newsletter Section */}
      <Section spacing="xl" className="bg-gradient-to-r from-primary-600 to-secondary-600 dark:from-primary-700 dark:to-secondary-700">
        <Container>
          <div className="max-w-2xl mx-auto text-center text-white">
            <div className="inline-flex items-center justify-center w-12 h-12 rounded-full bg-white/20 mb-6">
              <Mail className="w-6 h-6" />
            </div>
            <H2 className="text-white mb-4">Subscribe to Our Newsletter</H2>
            <Text className="mb-8" style={{ color: 'rgba(255,255,255,0.9)' }}>
              Get the latest posts, updates, and exclusive content delivered straight to your inbox.
              No spam, unsubscribe anytime.
            </Text>

            <form onSubmit={handleNewsletterSubscribe} className="flex flex-col sm:flex-row gap-3">
              <input
                type="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                placeholder="Enter your email"
                className="flex-1 px-4 py-3 rounded-lg text-secondary-900 placeholder-secondary-500 focus:outline-none focus:ring-2 focus:ring-white/50"
                aria-label="Email address"
              />
              <Button
                type="submit"
                variant="secondary"
                size="lg"
                disabled={newsletterStatus === 'loading' || newsletterStatus === 'success'}
                className="whitespace-nowrap"
              >
                {newsletterStatus === 'loading' ? (
                  'Subscribing...'
                ) : newsletterStatus === 'success' ? (
                  'Subscribed!'
                ) : (
                  <>
                    Subscribe
                    <ArrowRight className="w-4 h-4 ml-2" />
                  </>
                )}
              </Button>
            </form>

            {newsletterStatus === 'error' && (
              <p className="text-sm text-red-200 mt-2">Failed to subscribe. Please try again.</p>
            )}
            {newsletterStatus === 'success' && (
              <p className="text-sm text-green-200 mt-2">Thank you for subscribing!</p>
            )}
          </div>
        </Container>
      </Section>

      {/* CTA Section */}
      <Section spacing="xl">
        <Container>
          <div className="relative overflow-hidden rounded-2xl bg-secondary-900 dark:bg-secondary-800 p-8 md:p-12">
            {/* Background decoration */}
            <div className="absolute top-0 right-0 w-64 h-64 bg-primary-500 rounded-full opacity-10 blur-3xl" />
            <div className="absolute bottom-0 left-0 w-64 h-64 bg-secondary-500 rounded-full opacity-10 blur-3xl" />

            <div className="relative z-10 text-center">
              <H2 className="text-white mb-4">Start Writing Today</H2>
              <Text className="mb-8 text-secondary-300 dark:text-secondary-400 max-w-xl mx-auto">
                Join our community of writers and share your stories with the world.
                Get started in minutes and reach thousands of readers.
              </Text>
              <div className="flex flex-col sm:flex-row gap-4 justify-center">
                <Button size="lg" variant="secondary" onClick={() => (window.location.href = ROUTES.REGISTER)}>
                  Create Account
                </Button>
                <Button
                  size="lg"
                  variant="outline"
                  className="border-white/30 text-white hover:bg-white/10"
                  onClick={() => (window.location.href = ROUTES.POSTS)}
                >
                  Learn More
                </Button>
              </div>
            </div>
          </div>
        </Container>
      </Section>
    </>
  );
}

export default HomePage;
