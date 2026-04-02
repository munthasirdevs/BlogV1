import { Link } from 'react-router-dom';
import { cn } from '@/utils';
import { Avatar, Button, Badge } from '@/components/atoms';
import { ROUTES } from '@/constants';
import { Github, Twitter, Linkedin, Globe, MapPin, Calendar, Users } from 'lucide-react';

/**
 * AuthorCard component - Displays author information
 * @param {Object} props - Component props
 * @param {Object} props.author - Author data
 * @param {boolean} props.showStats - Show author stats (default: true)
 * @param {boolean} props.showSocial - Show social links (default: true)
 * @param {string} props.variant - Card variant (default: 'default')
 * @param {string} props.className - Additional CSS classes
 */
function AuthorCard({ author, showStats = true, showSocial = true, variant = 'default', className }) {
  if (!author) return null;

  const {
    id,
    name,
    username,
    email,
    avatar,
    bio,
    location,
    website,
    created_at,
    posts_count,
    followers_count,
    following_count,
    social_links,
  } = author;

  const socialIcons = {
    github: Github,
    twitter: Twitter,
    linkedin: Linkedin,
    website: Globe,
  };

  const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'long',
    });
  };

  const cardVariants = {
    default: 'bg-white dark:bg-secondary-800 border-secondary-200 dark:border-secondary-700',
    elevated: 'bg-white dark:bg-secondary-800 shadow-lg border-transparent',
    minimal: 'bg-transparent border-transparent',
  };

  return (
    <div
      className={cn(
        'rounded-xl border p-6 transition-all',
        cardVariants[variant],
        className
      )}
    >
      {/* Avatar and Name */}
      <div className="flex flex-col items-center text-center mb-4">
        <Link to={ROUTES.PROFILE(username || name)}>
          <Avatar name={name} src={avatar} size="xl" className="mb-4 cursor-pointer" />
        </Link>
        <Link to={ROUTES.PROFILE(username || name)}>
          <h3 className="text-xl font-bold text-secondary-900 dark:text-secondary-100 hover:text-primary-600 dark:hover:text-primary-400 transition-colors">
            {name}
          </h3>
        </Link>
        {username && (
          <p className="text-sm text-secondary-500 dark:text-secondary-400">@{username}</p>
        )}
      </div>

      {/* Bio */}
      {bio && (
        <p className="text-sm text-secondary-600 dark:text-secondary-400 text-center mb-4">
          {bio}
        </p>
      )}

      {/* Meta info */}
      <div className="space-y-2 mb-4">
        {location && (
          <div className="flex items-center justify-center gap-2 text-sm text-secondary-500 dark:text-secondary-400">
            <MapPin className="w-4 h-4" />
            <span>{location}</span>
          </div>
        )}
        {created_at && (
          <div className="flex items-center justify-center gap-2 text-sm text-secondary-500 dark:text-secondary-400">
            <Calendar className="w-4 h-4" />
            <span>Joined {formatDate(created_at)}</span>
          </div>
        )}
      </div>

      {/* Stats */}
      {showStats && (
        <div className="flex items-center justify-center gap-6 py-4 border-t border-b border-secondary-200 dark:border-secondary-700 mb-4">
          <div className="text-center">
            <p className="text-lg font-bold text-secondary-900 dark:text-secondary-100">
              {posts_count || 0}
            </p>
            <p className="text-xs text-secondary-500 dark:text-secondary-400">Posts</p>
          </div>
          <div className="text-center">
            <p className="text-lg font-bold text-secondary-900 dark:text-secondary-100">
              {followers_count || 0}
            </p>
            <p className="text-xs text-secondary-500 dark:text-secondary-400">Followers</p>
          </div>
          <div className="text-center">
            <p className="text-lg font-bold text-secondary-900 dark:text-secondary-100">
              {following_count || 0}
            </p>
            <p className="text-xs text-secondary-500 dark:text-secondary-400">Following</p>
          </div>
        </div>
      )}

      {/* Social links */}
      {showSocial && social_links && Object.keys(social_links).length > 0 && (
        <div className="flex items-center justify-center gap-2 mb-4">
          {Object.entries(social_links).map(([platform, url]) => {
            const Icon = socialIcons[platform];
            if (!Icon || !url) return null;
            return (
              <a
                key={platform}
                href={url}
                target="_blank"
                rel="noopener noreferrer"
                className="p-2 rounded-lg bg-secondary-100 dark:bg-secondary-700 text-secondary-600 dark:text-secondary-400 hover:bg-primary-100 dark:hover:bg-primary-900 hover:text-primary-600 dark:hover:text-primary-400 transition-all"
                aria-label={`${platform} profile`}
              >
                <Icon className="w-4 h-4" />
              </a>
            );
          })}
          {website && (
            <a
              href={website}
              target="_blank"
              rel="noopener noreferrer"
              className="p-2 rounded-lg bg-secondary-100 dark:bg-secondary-700 text-secondary-600 dark:text-secondary-400 hover:bg-primary-100 dark:hover:bg-primary-900 hover:text-primary-600 dark:hover:text-primary-400 transition-all"
              aria-label="Website"
            >
              <Globe className="w-4 h-4" />
            </a>
          )}
        </div>
      )}

      {/* View Profile Button */}
      <Link to={ROUTES.PROFILE(username || name)} className="block">
        <Button variant="outline" className="w-full">
          View Profile
        </Button>
      </Link>
    </div>
  );
}

export default AuthorCard;
