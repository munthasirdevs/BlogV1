import { useParams } from 'react-router-dom';
import { H1, Text, Avatar } from '@/components/atoms';
import { Container, Section } from '@/components';
import { useUserProfile } from '@/hooks';
import { Alert } from '@/components/molecules';
import { Calendar, MapPin, Link as LinkIcon } from 'lucide-react';

/**
 * User profile page component
 */
function ProfilePage() {
  const { username } = useParams();
  const { data: profileData, isLoading, error } = useUserProfile(username);
  const user = profileData?.data;

  if (error) {
    return (
      <Section spacing="lg">
        <Container>
          <Alert variant="danger" title="Profile not found">
            The user profile you're looking for could not be found.
          </Alert>
        </Container>
      </Section>
    );
  }

  if (isLoading || !user) {
    return (
      <Section spacing="lg">
        <Container>
          <div className="animate-pulse">
            <div className="w-24 h-24 bg-secondary-200 dark:bg-secondary-700 rounded-full mb-4" />
            <div className="h-8 bg-secondary-200 dark:bg-secondary-700 rounded w-48 mb-2" />
            <div className="h-4 bg-secondary-200 dark:bg-secondary-700 rounded w-64" />
          </div>
        </Container>
      </Section>
    );
  }

  return (
    <Section spacing="lg">
      <Container size="md">
        {/* Profile Header */}
        <div className="text-center mb-8">
          <Avatar name={user.name} src={user.avatar} size="xl" className="mb-4" />
          <H1 className="mb-2">{user.name}</H1>
          <Text color="muted">@{user.username}</Text>
        </div>

        {/* Bio */}
        {user.bio && (
          <div className="text-center mb-6">
            <Text>{user.bio}</Text>
          </div>
        )}

        {/* Meta Info */}
        <div className="flex justify-center gap-6 mb-8 text-sm text-secondary-600 dark:text-secondary-400">
          {user.location && (
            <div className="flex items-center gap-1">
              <MapPin className="w-4 h-4" />
              {user.location}
            </div>
          )}
          {user.website && (
            <a
              href={user.website}
              target="_blank"
              rel="noopener noreferrer"
              className="flex items-center gap-1 hover:text-primary-600"
            >
              <LinkIcon className="w-4 h-4" />
              Website
            </a>
          )}
          {user.created_at && (
            <div className="flex items-center gap-1">
              <Calendar className="w-4 h-4" />
              Joined {new Date(user.created_at).toLocaleDateString()}
            </div>
          )}
        </div>

        {/* Stats */}
        <div className="flex justify-center gap-8 py-6 border-t border-b border-secondary-200 dark:border-secondary-700">
          <div className="text-center">
            <div className="text-2xl font-bold text-secondary-900 dark:text-secondary-100">
              {user.posts_count || 0}
            </div>
            <Text size="sm" color="muted">
              Posts
            </Text>
          </div>
          <div className="text-center">
            <div className="text-2xl font-bold text-secondary-900 dark:text-secondary-100">
              {user.followers_count || 0}
            </div>
            <Text size="sm" color="muted">
              Followers
            </Text>
          </div>
          <div className="text-center">
            <div className="text-2xl font-bold text-secondary-900 dark:text-secondary-100">
              {user.following_count || 0}
            </div>
            <Text size="sm" color="muted">
              Following
            </Text>
          </div>
        </div>
      </Container>
    </Section>
  );
}

export default ProfilePage;
