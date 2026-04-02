import { cn } from '@/utils';
import { Skeleton } from '@/components/atoms';

/**
 * Post Card component for displaying blog posts
 * @param {Object} props - Component props
 * @param {Object} props.post - Post data
 * @param {Function} props.onClick - Click handler
 * @param {boolean} props.isLoading - Loading state
 */
function PostCard({ post, onClick, isLoading = false, className }) {
  if (isLoading) {
    return <Card.Skeleton showImage={true} showFooter={true} />;
  }

  const {
    title,
    excerpt,
    featured_image,
    author,
    category,
    published_at,
    reading_time,
  } = post;

  const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
    });
  };

  return (
    <div
      className={cn(
        'group cursor-pointer',
        'bg-white dark:bg-secondary-800 rounded-xl overflow-hidden',
        'border border-secondary-200 dark:border-secondary-700',
        'transition-all duration-300 hover:shadow-lg hover:-translate-y-1',
        className
      )}
      onClick={onClick}
    >
      {/* Featured Image */}
      {featured_image && (
        <div className="relative aspect-video overflow-hidden">
          <img
            src={featured_image}
            alt={title}
            className="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
          />
        </div>
      )}

      {/* Content */}
      <div className="p-5">
        {/* Category & Meta */}
        <div className="flex items-center gap-2 mb-3">
          {category && (
            <span className="px-2.5 py-0.5 text-xs font-medium rounded-full bg-primary-100 text-primary-800 dark:bg-primary-900 dark:text-primary-200">
              {category.name}
            </span>
          )}
          {reading_time && (
            <span className="text-xs text-secondary-500 dark:text-secondary-400">
              {reading_time} min read
            </span>
          )}
        </div>

        {/* Title */}
        <h3 className="text-lg font-semibold text-secondary-900 dark:text-secondary-100 mb-2 line-clamp-2 group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">
          {title}
        </h3>

        {/* Excerpt */}
        <p className="text-sm text-secondary-600 dark:text-secondary-400 mb-4 line-clamp-2">
          {excerpt}
        </p>

        {/* Author & Date */}
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-2">
            {author?.avatar ? (
              <img
                src={author.avatar}
                alt={author.name}
                className="w-6 h-6 rounded-full object-cover"
              />
            ) : (
              <div className="w-6 h-6 rounded-full bg-primary-500 flex items-center justify-center text-xs text-white font-medium">
                {author?.name?.charAt(0) || 'A'}
              </div>
            )}
            <span className="text-xs text-secondary-600 dark:text-secondary-400">
              {author?.name}
            </span>
          </div>
          {published_at && (
            <span className="text-xs text-secondary-500 dark:text-secondary-400">
              {formatDate(published_at)}
            </span>
          )}
        </div>
      </div>
    </div>
  );
}

// Import Card for skeleton
import Card from './Card';

export default PostCard;
