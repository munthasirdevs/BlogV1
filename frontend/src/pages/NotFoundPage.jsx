import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { H1, H2, Text, Button, Input } from '@/components/atoms';
import { Container, Section } from '@/components';
import { ROUTES } from '@/constants';
import { Home, Search, BookOpen, FolderOpen, Tag, HelpCircle, ArrowRight } from 'lucide-react';

/**
 * Enhanced 404 Not Found page with helpful links and search
 */
function NotFoundPage() {
  const navigate = useNavigate();
  const [searchQuery, setSearchQuery] = useState('');

  const handleSearch = (e) => {
    e.preventDefault();
    if (searchQuery.trim()) {
      navigate(`${ROUTES.SEARCH}?q=${encodeURIComponent(searchQuery)}`);
    }
  };

  const quickLinks = [
    {
      icon: Home,
      title: 'Home',
      description: 'Return to our homepage',
      href: ROUTES.HOME,
      color: 'text-blue-600 dark:text-blue-400',
      bgColor: 'bg-blue-100 dark:bg-blue-900/30',
    },
    {
      icon: BookOpen,
      title: 'All Posts',
      description: 'Browse all articles',
      href: ROUTES.POSTS,
      color: 'text-green-600 dark:text-green-400',
      bgColor: 'bg-green-100 dark:bg-green-900/30',
    },
    {
      icon: FolderOpen,
      title: 'Categories',
      description: 'Explore by category',
      href: ROUTES.CATEGORIES,
      color: 'text-purple-600 dark:text-purple-400',
      bgColor: 'bg-purple-100 dark:bg-purple-900/30',
    },
    {
      icon: Tag,
      title: 'Tags',
      description: 'Browse by tags',
      href: ROUTES.TAGS,
      color: 'text-orange-600 dark:text-orange-400',
      bgColor: 'bg-orange-100 dark:bg-orange-900/30',
    },
  ];

  const popularPosts = [
    { title: 'Getting Started with React', slug: 'getting-started-with-react' },
    { title: 'Understanding TypeScript', slug: 'understanding-typescript' },
    { title: 'CSS Grid Layout Guide', slug: 'css-grid-layout-guide' },
    { title: 'JavaScript Best Practices', slug: 'javascript-best-practices' },
  ];

  return (
    <Section spacing="xl">
      <Container>
        <div className="max-w-3xl mx-auto">
          {/* 404 Hero */}
          <div className="text-center mb-12">
            {/* Illustration */}
            <div className="relative w-48 h-48 mx-auto mb-8">
              <div className="absolute inset-0 bg-gradient-to-br from-primary-500 to-secondary-600 rounded-full opacity-20 animate-pulse" />
              <div className="absolute inset-4 bg-gradient-to-br from-primary-500 to-secondary-600 rounded-full opacity-30" />
              <div className="absolute inset-0 flex items-center justify-center">
                <HelpCircle className="w-24 h-24 text-primary-600 dark:text-primary-400" />
              </div>
            </div>

            <div className="text-8xl font-bold text-secondary-200 dark:text-secondary-800 mb-4">
              404
            </div>
            <H1 className="mb-4">Oops! Page Not Found</H1>
            <Text size="lg" color="muted" className="mb-8">
              The page you're looking for doesn't exist, has been moved, 
              or is temporarily unavailable. Let's help you find your way.
            </Text>

            {/* Search Bar */}
            <form onSubmit={handleSearch} className="max-w-md mx-auto mb-8">
              <div className="relative">
                <Search className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-secondary-400" />
                <Input
                  type="search"
                  placeholder="Search for articles..."
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  className="pl-12 pr-4 py-3"
                  aria-label="Search the site"
                />
                <Button type="submit" className="absolute right-2 top-1/2 -translate-y-1/2">
                  Search
                </Button>
              </div>
            </form>

            {/* Home Button */}
            <Link to={ROUTES.HOME}>
              <Button size="lg">
                <Home className="w-4 h-4 mr-2" />
                Back to Home
              </Button>
            </Link>
          </div>

          {/* Quick Links */}
          <div className="mb-12">
            <H2 className="text-center mb-6">Quick Links</H2>
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
              {quickLinks.map((link) => (
                <Link
                  key={link.href}
                  to={link.href}
                  className="group p-6 rounded-xl border border-secondary-200 dark:border-secondary-700 hover:border-primary-300 dark:hover:border-primary-700 hover:shadow-lg transition-all"
                >
                  <div className={`w-12 h-12 rounded-lg ${link.bgColor} flex items-center justify-center mb-4`}>
                    <link.icon className={`w-6 h-6 ${link.color}`} />
                  </div>
                  <h3 className="font-semibold text-secondary-900 dark:text-secondary-100 mb-1 group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">
                    {link.title}
                  </h3>
                  <Text color="muted" className="text-sm">
                    {link.description}
                  </Text>
                </Link>
              ))}
            </div>
          </div>

          {/* Popular Posts */}
          <div className="mb-12">
            <H2 className="text-center mb-6">Popular Articles</H2>
            <div className="bg-secondary-50 dark:bg-secondary-800/50 rounded-xl p-6">
              <ul className="space-y-3">
                {popularPosts.map((post, index) => (
                  <li key={post.slug}>
                    <Link
                      to={ROUTES.POST_DETAIL(post.slug)}
                      className="flex items-center gap-3 group"
                    >
                      <span className="flex-shrink-0 w-8 h-8 rounded-full bg-secondary-200 dark:bg-secondary-700 flex items-center justify-center text-sm font-medium text-secondary-600 dark:text-secondary-400">
                        {index + 1}
                      </span>
                      <span className="text-secondary-700 dark:text-secondary-300 group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors flex-1">
                        {post.title}
                      </span>
                      <ArrowRight className="w-4 h-4 text-secondary-400 group-hover:text-primary-600 dark:group-hover:text-primary-400 opacity-0 group-hover:opacity-100 transition-all" />
                    </Link>
                  </li>
                ))}
              </ul>
            </div>
          </div>

          {/* Help Section */}
          <div className="text-center p-8 rounded-2xl bg-gradient-to-br from-primary-50 to-secondary-50 dark:from-primary-900/20 dark:to-secondary-900/20 border border-primary-100 dark:border-primary-800">
            <H2 className="mb-2">Still need help?</H2>
            <Text color="muted" className="mb-6">
              Our support team is here to assist you with any questions.
            </Text>
            <div className="flex flex-col sm:flex-row gap-4 justify-center">
              <Link to={ROUTES.CONTACT || '/contact'}>
                <Button variant="outline">
                  Contact Support
                </Button>
              </Link>
              <Link to="/help">
                <Button variant="ghost">
                  Visit Help Center
                </Button>
              </Link>
            </div>
          </div>
        </div>
      </Container>
    </Section>
  );
}

export default NotFoundPage;
