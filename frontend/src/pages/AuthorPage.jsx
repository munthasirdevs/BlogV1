import { useParams, Link } from 'react-router-dom';
import { H1, H2, H3, Text, Button, Avatar, Badge } from '@/components/atoms';
import { PostGrid } from '@/components/organisms';
import { Container, Section } from '@/components';
import { useAuthor, useIsMd } from '@/hooks';
import { ROUTES } from '@/constants';
import { format } from 'date-fns';
import { ArrowLeft, MapPin, Calendar, Users, Github, Twitter, Linkedin, Globe, Rss } from 'lucide-react';
import { cn } from '@/utils';

/**
 * AuthorPage - Author profile with bio, stats, and posts
 */
function AuthorPage() {
  const { username } = useParams();
  const isDesktop = useIsMd();
  const { data: authorData, isLoading, error } = useAuthor(username);
  const author = authorData?.data;

  // Mock author posts - in real app, use useAuthorPosts hook
  const authorPosts = authorData?.posts || [];

  const socialIcons = {
    github: Github,
    twitter: Twitter,
    linkedin: Linkedin,
    website: Globe,
  };

  if (error) {
    return (
      <Section spacing="lg">
        <Container>
          <Link
            to={ROUTES.POSTS}
            className="inline-flex items-center text-sm text-secondary-600 dark:text-secondary-400 hover:text-primary-600 mb-4"
          >
            <ArrowLeft className="w-4 h-4 mr-1" />
            Back to Posts
          </Link>
          <div className="text-center py-12">
            <H2 className="mb-2">Author not found</H2>
            <Text color="muted">The author you're looking for doesn't exist or has been removed.</Text>
          </div>
        </Container>
      </Section>
    );
  }

  if (isLoading || !author) {
    return (
      <Section spacing="lg">
        <Container>
          <div className="animate-pulse">
            <div className="max-w-4xl mx-auto">
              <div className="flex flex-col md:flex-row gap-8 items-center mb-12">
                <div className="w-32 h-32 bg-secondary-200 dark:bg-secondary-700 rounded-full" />
                <div className="flex-1 text-center md:text-left space-y-4">
                  <div className="h-8 bg-secondary-200 dark:bg-secondary-700 rounded w-3/4" />
                  <div className="h-4 bg-secondary-200 dark:bg-secondary-700 rounded w-1/2" />
                  <div className="h-4 bg-secondary-200 dark:bg-secondary-700 rounded w-full" />
                </div>
              </div>
            </div>
          </div>
        </Container>
      </Section>
    );
  }

  const {
    id,
    name,
    username: userUsername,
    email,
    avatar,
    bio,
    location,
    website,
    created_at,
    posts_count = 0,
    followers_count = 0,
    following_count = 0,
    social_links = {},
  } = author;

  return (
    <Section spacing="lg">
      <Container>
        {/* Back Link */}
        <Link
          to={ROUTES.POSTS}
          className="inline-flex items-center text-sm text-secondary-600 dark:text-secondary-400 hover:text-primary-600 dark:hover:text-primary-400 mb-8 transition-colors"
        >
          <ArrowLeft className="w-4 h-4 mr-1" />
          Back to Posts
        </Link>

        {/* Author Header */}
        <div className="max-w-4xl mx-auto mb-12">
          <div className="relative">
            {/* Cover Background */}
            <div className="h-32 md:h-48 rounded-t-2xl bg-gradient-to-r from-primary-500 to-secondary-600" />
            
            {/* Profile Card */}
            <div className="relative bg-white dark:bg-secondary-800 rounded-b-2xl border border-secondary-200 dark:border-secondary-700 border-t-0 p-6 md:p-8">
              <div className="flex flex-col md:flex-row gap-6 items-center md:items-end -mt-16 md:-mt-20">
                {/* Avatar */}
                <div className="relative">
                  <Avatar
                    name={name}
                    src={avatar}
                    size="xl"
                    className="w-32 h-32 border-4 border-white dark:border-secondary-800 shadow-lg"
                  />
                  <div className="absolute bottom-2 right-2 w-6 h-6 bg-green-500 border-4 border-white dark:border-secondary-800 rounded-full" />
                </div>

                {/* Info */}
                <div className="flex-1 text-center md:text-left">
                  <H1 className="text-2xl md:text-3xl font-bold mb-1">{name}</H1>
                  {userUsername && (
                    <p className="text-secondary-500 dark:text-secondary-400 mb-3">@{userUsername}</p>
                  )}
                  
                  {/* Meta */}
                  <div className="flex flex-wrap justify-center md:justify-start gap-4 text-sm text-secondary-600 dark:text-secondary-400 mb-4">
                    {location && (
                      <div className="flex items-center gap-1">
                        <MapPin className="w-4 h-4" />
                        <span>{location}</span>
                      </div>
                    )}
                    {created_at && (
                      <div className="flex items-center gap-1">
                        <Calendar className="w-4 h-4" />
                        <span>Joined {format(new Date(created_at), 'MMMM yyyy')}</span>
                      </div>
                    )}
                  </div>

                  {/* Social Links */}
                  {(Object.keys(social_links).length > 0 || website) && (
                    <div className="flex justify-center md:justify-start gap-2">
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
                </div>

                {/* Action Button */}
                <div className="flex gap-2">
                  <Button variant="outline" size="sm">
                    <Rss className="w-4 h-4 mr-2" />
                    Follow
                  </Button>
                </div>
              </div>

              {/* Bio */}
              {bio && (
                <div className="mt-6 pt-6 border-t border-secondary-200 dark:border-secondary-700">
                  <Text className="text-center md:text-left">{bio}</Text>
                </div>
              )}

              {/* Stats */}
              <div className="mt-6 pt-6 border-t border-secondary-200 dark:border-secondary-700">
                <div className="grid grid-cols-3 gap-4">
                  <div className="text-center">
                    <p className="text-2xl font-bold text-secondary-900 dark:text-secondary-100">{posts_count}</p>
                    <p className="text-sm text-secondary-500 dark:text-secondary-400">Posts</p>
                  </div>
                  <div className="text-center">
                    <p className="text-2xl font-bold text-secondary-900 dark:text-secondary-100">{followers_count}</p>
                    <p className="text-sm text-secondary-500 dark:text-secondary-400">Followers</p>
                  </div>
                  <div className="text-center">
                    <p className="text-2xl font-bold text-secondary-900 dark:text-secondary-100">{following_count}</p>
                    <p className="text-sm text-secondary-500 dark:text-secondary-400">Following</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Author's Posts */}
        <div>
          <div className="flex items-center justify-between mb-8">
            <H2 className="text-2xl font-bold">Articles by {name}</H2>
            <Link to={ROUTES.POSTS}>
              <Button variant="ghost">
                View All Posts
                <ArrowLeft className="w-4 h-4 ml-1 rotate-180" />
              </Button>
            </Link>
          </div>

          <PostGrid
            posts={authorPosts}
            isLoading={isLoading}
            cols={isDesktop ? 3 : 1}
            emptyMessage={`${name} hasn't published any posts yet.`}
          />
        </div>
      </Container>
    </Section>
  );
}

export default AuthorPage;
