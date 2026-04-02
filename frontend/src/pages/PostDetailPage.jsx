import { useParams, Link, useNavigate } from 'react-router-dom';
import { useEffect, useState } from 'react';
import { H1, H2, H3, Text, Badge, Button, Avatar } from '@/components/atoms';
import { Alert, ReadingProgress, ShareButtons, TableOfContents } from '@/components/molecules';
import { AuthorCard, PostGrid as PostGridOrg } from '@/components/organisms';
import { Container, Section } from '@/components';
import { usePost, useRelatedPosts } from '@/hooks';
import { ROUTES } from '@/constants';
import { format } from 'date-fns';
import { Clock, Calendar, ArrowLeft, Share2, Bookmark, Eye, MessageSquare, ArrowRight } from 'lucide-react';
import { cn } from '@/utils';

/**
 * Enhanced BlogDetailPage with TOC, share buttons, related posts, author bio, and reading progress
 */
function PostDetailPage() {
  const { slug } = useParams();
  const navigate = useNavigate();
  const { data: postData, isLoading, error } = usePost(slug);
  const post = postData?.data;

  // Related posts
  const { data: relatedData } = useRelatedPosts(post?.id, 3);
  const relatedPosts = relatedData?.data || [];

  // Bookmark state
  const [isBookmarked, setIsBookmarked] = useState(false);

  useEffect(() => {
    // Check if post is bookmarked
    const bookmarks = JSON.parse(localStorage.getItem('bookmarks') || '[]');
    setIsBookmarked(bookmarks.includes(post?.id));
  }, [post?.id]);

  // Handle bookmark toggle
  const handleBookmark = () => {
    const bookmarks = JSON.parse(localStorage.getItem('bookmarks') || '[]');
    if (isBookmarked) {
      const newBookmarks = bookmarks.filter((id) => id !== post?.id);
      localStorage.setItem('bookmarks', JSON.stringify(newBookmarks));
      setIsBookmarked(false);
    } else {
      bookmarks.push(post?.id);
      localStorage.setItem('bookmarks', JSON.stringify(bookmarks));
      setIsBookmarked(true);
    }
  };

  // Get current URL for sharing
  const shareUrl = typeof window !== 'undefined' ? window.location.href : '';

  if (error) {
    return (
      <Section spacing="lg">
        <Container>
          <Alert variant="danger" title="Error loading post">
            The post you're looking for could not be found or has been removed.
          </Alert>
          <Link to={ROUTES.POSTS}>
            <Button variant="ghost" className="mt-4">
              <ArrowLeft className="w-4 h-4 mr-2" />
              Back to Posts
            </Button>
          </Link>
        </Container>
      </Section>
    );
  }

  if (isLoading || !post) {
    return (
      <Section spacing="lg">
        <Container>
          <div className="animate-pulse">
            <div className="h-64 md:h-96 bg-secondary-200 dark:bg-secondary-700 rounded-xl mb-8" />
            <div className="h-8 bg-secondary-200 dark:bg-secondary-700 rounded w-3/4 mb-4" />
            <div className="h-4 bg-secondary-200 dark:bg-secondary-700 rounded w-1/2 mb-8" />
            <div className="space-y-3">
              <div className="h-4 bg-secondary-200 dark:bg-secondary-700 rounded" />
              <div className="h-4 bg-secondary-200 dark:bg-secondary-700 rounded" />
              <div className="h-4 bg-secondary-200 dark:bg-secondary-700 rounded w-3/4" />
            </div>
          </div>
        </Container>
      </Section>
    );
  }

  return (
    <article>
      {/* Reading Progress Bar */}
      <ReadingProgress color="primary" height="3px" />

      {/* Featured Image */}
      {post.featured_image && (
        <div className="w-full h-64 md:h-[500px] bg-secondary-200 dark:bg-secondary-700 relative">
          <img
            src={post.featured_image}
            alt={post.title}
            className="w-full h-full object-cover"
          />
          {/* Gradient overlay */}
          <div className="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent" />
        </div>
      )}

      <Section spacing="lg">
        <Container size="lg">
          <div className="grid grid-cols-1 lg:grid-cols-3 gap-12">
            {/* Main Content */}
            <div className="lg:col-span-2">
              {/* Back Link */}
              <Link
                to={ROUTES.POSTS}
                className="inline-flex items-center text-sm text-secondary-600 dark:text-secondary-400 hover:text-primary-600 dark:hover:text-primary-400 mb-6 transition-colors"
              >
                <ArrowLeft className="w-4 h-4 mr-1" />
                Back to Posts
              </Link>

              {/* Header */}
              <header className="mb-8">
                {/* Categories & Tags */}
                <div className="flex flex-wrap items-center gap-2 mb-4">
                  {post.category && (
                    <Link to={ROUTES.CATEGORY_DETAIL(post.category.slug)}>
                      <Badge variant="primary" className="cursor-pointer hover:opacity-80">
                        {post.category.name}
                      </Badge>
                    </Link>
                  )}
                  {post.tags?.map((tag) => (
                    <Link key={tag.id} to={ROUTES.TAG_DETAIL(tag.slug)}>
                      <Badge variant="secondary" className="cursor-pointer hover:opacity-80">
                        {tag.name}
                      </Badge>
                    </Link>
                  ))}
                </div>

                {/* Title */}
                <H1 className="mb-4 text-3xl md:text-4xl lg:text-5xl font-bold">
                  {post.title}
                </H1>

                {/* Excerpt */}
                {post.excerpt && (
                  <Text size="lg" color="muted" className="mb-6">
                    {post.excerpt}
                  </Text>
                )}

                {/* Meta */}
                <div className="flex flex-wrap items-center gap-6 text-sm text-secondary-600 dark:text-secondary-400">
                  <div className="flex items-center gap-2">
                    <Avatar
                      name={post.author?.name}
                      src={post.author?.avatar}
                      size="sm"
                      className="w-8 h-8"
                    />
                    <span className="font-medium">{post.author?.name}</span>
                  </div>
                  <div className="flex items-center gap-1">
                    <Calendar className="w-4 h-4" />
                    {format(new Date(post.published_at), 'MMMM d, yyyy')}
                  </div>
                  <div className="flex items-center gap-1">
                    <Clock className="w-4 h-4" />
                    {post.reading_time} min read
                  </div>
                  {post.view_count !== undefined && (
                    <div className="flex items-center gap-1">
                      <Eye className="w-4 h-4" />
                      {post.view_count.toLocaleString()}
                    </div>
                  )}
                </div>
              </header>

              {/* Actions */}
              <div className="flex items-center justify-between gap-4 mb-8 pb-8 border-b border-secondary-200 dark:border-secondary-700">
                <ShareButtons
                  url={shareUrl}
                  title={post.title}
                  description={post.excerpt}
                  variant="outline"
                />
                <Button
                  variant={isBookmarked ? 'default' : 'outline'}
                  size="sm"
                  onClick={handleBookmark}
                  className={cn(isBookmarked && 'bg-primary-600 text-white hover:bg-primary-700')}
                >
                  <Bookmark className={cn('w-4 h-4 mr-2', isBookmarked && 'fill-current')} />
                  {isBookmarked ? 'Saved' : 'Save'}
                </Button>
              </div>

              {/* Table of Contents */}
              <TableOfContents contentSelector=".post-content" title="On this page" />

              {/* Content */}
              <div
                className="post-content prose prose-lg dark:prose-invert max-w-none
                  prose-headings:font-bold prose-headings:text-secondary-900 dark:prose-headings:text-secondary-100
                  prose-h2:text-2xl prose-h2:mt-10 prose-h2:mb-4
                  prose-h3:text-xl prose-h3:mt-8 prose-h3:mb-3
                  prose-p:text-secondary-700 dark:prose-p:text-secondary-300
                  prose-a:text-primary-600 dark:prose-a:text-primary-400 prose-a:no-underline hover:prose-a:underline
                  prose-blockquote:border-primary-500 prose-blockquote:bg-primary-50 dark:prose-blockquote:bg-primary-900/20
                  prose-blockquote:py-2 prose-blockquote:px-4 prose-blockquote:rounded-r-lg
                  prose-code:bg-secondary-100 dark:prose-code:bg-secondary-800
                  prose-code:px-1.5 prose-code:py-0.5 prose-code:rounded prose-code:text-sm
                  prose-pre:bg-secondary-900 prose-pre:text-secondary-100
                  prose-pre:rounded-xl prose-pre:p-4
                  prose-img:rounded-xl prose-img:border prose-img:border-secondary-200 dark:prose-img:border-secondary-700
                  prose-ul:my-4 prose-ol:my-4
                  prose-li:text-secondary-700 dark:prose-li:text-secondary-300"
                dangerouslySetInnerHTML={{ __html: post.content }}
              />

              {/* Tags */}
              {post.tags && post.tags.length > 0 && (
                <div className="mt-12 pt-8 border-t border-secondary-200 dark:border-secondary-700">
                  <div className="flex flex-wrap gap-2">
                    {post.tags.map((tag) => (
                      <Link key={tag.id} to={ROUTES.TAG_DETAIL(tag.slug)}>
                        <Badge variant="secondary" className="cursor-pointer hover:bg-secondary-200 dark:hover:bg-secondary-700">
                          #{tag.name}
                        </Badge>
                      </Link>
                    ))}
                  </div>
                </div>
              )}

              {/* Author Bio */}
              {post.author && (
                <div className="mt-12">
                  <AuthorCard author={post.author} showStats={false} />
                </div>
              )}
            </div>

            {/* Sidebar */}
            <aside className="lg:col-span-1">
              <div className="sticky top-24 space-y-8">
                {/* Share Card */}
                <div className="bg-secondary-50 dark:bg-secondary-800/50 rounded-xl p-6 border border-secondary-200 dark:border-secondary-700">
                  <H3 className="text-lg font-semibold mb-4">Share this article</H3>
                  <ShareButtons
                    url={shareUrl}
                    title={post.title}
                    description={post.excerpt}
                    variant="outline"
                    className="flex-col"
                  />
                </div>

                {/* Post Stats */}
                <div className="bg-secondary-50 dark:bg-secondary-800/50 rounded-xl p-6 border border-secondary-200 dark:border-secondary-700">
                  <H3 className="text-lg font-semibold mb-4">Article Stats</H3>
                  <div className="space-y-4">
                    <div className="flex items-center justify-between">
                      <span className="text-sm text-secondary-600 dark:text-secondary-400">Reading Time</span>
                      <span className="text-sm font-medium text-secondary-900 dark:text-secondary-100">{post.reading_time} min</span>
                    </div>
                    <div className="flex items-center justify-between">
                      <span className="text-sm text-secondary-600 dark:text-secondary-400">Published</span>
                      <span className="text-sm font-medium text-secondary-900 dark:text-secondary-100">
                        {format(new Date(post.published_at), 'MMM d, yyyy')}
                      </span>
                    </div>
                    {post.view_count !== undefined && (
                      <div className="flex items-center justify-between">
                        <span className="text-sm text-secondary-600 dark:text-secondary-400">Views</span>
                        <span className="text-sm font-medium text-secondary-900 dark:text-secondary-100">
                          {post.view_count.toLocaleString()}
                        </span>
                      </div>
                    )}
                    <div className="flex items-center justify-between">
                      <span className="text-sm text-secondary-600 dark:text-secondary-400">Category</span>
                      <Link
                        to={ROUTES.CATEGORY_DETAIL(post.category?.slug)}
                        className="text-sm font-medium text-primary-600 dark:text-primary-400 hover:underline"
                      >
                        {post.category?.name}
                      </Link>
                    </div>
                  </div>
                </div>
              </div>
            </aside>
          </div>

          {/* Related Posts */}
          {relatedPosts.length > 0 && (
            <div className="mt-16 pt-12 border-t border-secondary-200 dark:border-secondary-700">
              <div className="flex items-center justify-between mb-8">
                <H2 className="text-2xl font-bold">Related Articles</H2>
                <Link to={ROUTES.POSTS}>
                  <Button variant="ghost">
                    View All
                    <ArrowRight className="w-4 h-4 ml-1" />
                  </Button>
                </Link>
              </div>
              <PostGridOrg posts={relatedPosts} cols={3} />
            </div>
          )}
        </Container>
      </Section>
    </article>
  );
}

export default PostDetailPage;
