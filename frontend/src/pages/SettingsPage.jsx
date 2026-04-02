import { useState } from 'react';
import { H1, H2, Text, Input, Button, Textarea } from '@/components/atoms';
import { Container, Section } from '@/components';
import { useAuth } from '@/contexts/AuthContext';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { User, Mail, Lock, Eye, EyeOff } from 'lucide-react';

/**
 * Settings form validation schema
 */
const settingsSchema = z.object({
  name: z.string().min(2, 'Name must be at least 2 characters'),
  email: z.string().email('Please enter a valid email address'),
  bio: z.string().max(500, 'Bio must be less than 500 characters').optional(),
});

/**
 * Settings page component
 */
function SettingsPage() {
  const { user } = useAuth();
  const [showCurrentPassword, setShowCurrentPassword] = useState(false);
  const [showNewPassword, setShowNewPassword] = useState(false);

  const {
    register,
    handleSubmit,
    formState: { errors },
    watch,
  } = useForm({
    resolver: zodResolver(settingsSchema),
    defaultValues: {
      name: user?.name || '',
      email: user?.email || '',
      bio: user?.bio || '',
    },
  });

  const bio = watch('bio');

  const onSubmit = (data) => {
    console.log('Profile update:', data);
    // TODO: Implement profile update
  };

  return (
    <Section spacing="lg">
      <Container size="md">
        <H1 className="mb-8">Settings</H1>

        {/* Profile Settings */}
        <div className="mb-12">
          <H2 className="text-xl font-semibold mb-4">Profile Settings</H2>
          <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
            <Input
              label="Name"
              type="text"
              error={errors.name?.message}
              leftIcon={<User className="w-4 h-4" />}
              {...register('name')}
            />

            <Input
              label="Email"
              type="email"
              error={errors.email?.message}
              leftIcon={<Mail className="w-4 h-4" />}
              {...register('email')}
            />

            <Textarea
              label="Bio"
              rows={4}
              placeholder="Tell us about yourself..."
              error={errors.bio?.message}
              helperText={`${bio?.length || 0}/500 characters`}
              {...register('bio')}
            />

            <Button type="submit">Save Changes</Button>
          </form>
        </div>

        {/* Password Change */}
        <div>
          <H2 className="text-xl font-semibold mb-4">Change Password</H2>
          <form className="space-y-4">
            <Input
              label="Current Password"
              type={showCurrentPassword ? 'text' : 'password'}
              leftIcon={<Lock className="w-4 h-4" />}
              rightIcon={
                <button
                  type="button"
                  onClick={() => setShowCurrentPassword(!showCurrentPassword)}
                  className="hover:text-secondary-600"
                >
                  {showCurrentPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                </button>
              }
            />

            <Input
              label="New Password"
              type={showNewPassword ? 'text' : 'password'}
              leftIcon={<Lock className="w-4 h-4" />}
              rightIcon={
                <button
                  type="button"
                  onClick={() => setShowNewPassword(!showNewPassword)}
                  className="hover:text-secondary-600"
                >
                  {showNewPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                </button>
              }
            />

            <Input
              label="Confirm New Password"
              type="password"
              leftIcon={<Lock className="w-4 h-4" />}
            />

            <Button type="submit" variant="primary">
              Update Password
            </Button>
          </form>
        </div>
      </Container>
    </Section>
  );
}

export default SettingsPage;
