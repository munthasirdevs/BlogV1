import { Link } from 'react-router-dom';
import { cn } from '@/utils';
import { PostCard } from '@/components/molecules';
import { Skeleton } from '@/components/atoms';
import { ROUTES } from '@/constants';
import { Grid } from '@/components';

/**
 * PostGrid component - Grid of post cards with loading and empty states
 * @param {Object} props - Component props
 * @param {Array} props.posts - Array of posts to display
 * @param {boolean} props.isLoading - Loading state
 * @param {number} props.cols - Number of columns (default: 3)
 * @param {string} props.emptyMessage - Message when no posts
 * @param {string} props.className - Additional CSS classes
 */
function PostGrid({ posts = [], isLoading = false, cols = 3, emptyMessage = 'No posts found.', className }) {
  // Generate skeleton loaders
  const skeletonCount = cols === 1 ? 3 : cols === 2 ? 4 : 6;

  if (isLoading) {
    return (
      <Grid cols={cols} gap="lg" className={className}>
        {Array(skeletonCount)
          .fill(0)
          .map((_, i) => (
            <div key={i} className="h-full">
              <PostCard isLoading />
            </div>
          ))}
      </Grid>
    );
  }

  if (!posts || posts.length === 0) {
    return (
      <div className={cn('text-center py-12', className)}>
        <div className="w-16 h-16 mx-auto mb-4 rounded-full bg-secondary-100 dark:bg-secondary-800 flex items-center justify-center">
          <svg
            className="w-8 h-8 text-secondary-400"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"
            />
          </svg>
        </div>
        <p className="text-secondary-600 dark:text-secondary-400">{emptyMessage}</p>
      </div>
    );
  }

  return (
    <Grid cols={cols} gap="lg" className={className}>
      {posts.map((post) => (
        <Link key={post.id} to={ROUTES.POST_DETAIL(post.slug)} className="block h-full">
          <PostCard post={post} className="h-full" />
        </Link>
      ))}
    </Grid>
  );
}

export default PostGrid;
