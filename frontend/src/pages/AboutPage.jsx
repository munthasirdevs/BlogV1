import { Link } from 'react-router-dom';
import { H1, H2, H3, Text, Button } from '@/components/atoms';
import { Container, Section } from '@/components';
import { ROUTES } from '@/constants';
import { 
  Users, 
  BookOpen, 
  Globe, 
  Heart, 
  Mail, 
  MapPin, 
  Calendar,
  Award,
  Zap,
  Shield,
  Coffee
} from 'lucide-react';

/**
 * AboutPage - Information about the blog platform
 */
function AboutPage() {
  const features = [
    {
      icon: BookOpen,
      title: 'Quality Content',
      description: 'Curated articles and tutorials from experienced writers and industry experts.',
      color: 'text-blue-600 dark:text-blue-400',
      bgColor: 'bg-blue-100 dark:bg-blue-900/30',
    },
    {
      icon: Users,
      title: 'Community Driven',
      description: 'Join a thriving community of readers and writers passionate about sharing knowledge.',
      color: 'text-green-600 dark:text-green-400',
      bgColor: 'bg-green-100 dark:bg-green-900/30',
    },
    {
      icon: Zap,
      title: 'Always Updated',
      description: 'Fresh content daily covering the latest trends, technologies, and best practices.',
      color: 'text-yellow-600 dark:text-yellow-400',
      bgColor: 'bg-yellow-100 dark:bg-yellow-900/30',
    },
    {
      icon: Shield,
      title: 'Trusted Source',
      description: 'Verified authors and fact-checked content you can rely on.',
      color: 'text-purple-600 dark:text-purple-400',
      bgColor: 'bg-purple-100 dark:bg-purple-900/30',
    },
  ];

  const stats = [
    { value: '10K+', label: 'Articles Published', icon: BookOpen },
    { value: '50K+', label: 'Monthly Readers', icon: Users },
    { value: '500+', label: 'Expert Writers', icon: Award },
    { value: '100+', label: 'Topics Covered', icon: Globe },
  ];

  const team = [
    {
      name: 'John Doe',
      role: 'Founder & CEO',
      avatar: null,
      bio: 'Passionate about creating platforms that empower writers and readers.',
    },
    {
      name: 'Jane Smith',
      role: 'Head of Content',
      avatar: null,
      bio: 'Ensuring quality and consistency across all published content.',
    },
    {
      name: 'Mike Johnson',
      role: 'Lead Developer',
      avatar: null,
      bio: 'Building the technology that powers our platform.',
    },
    {
      name: 'Sarah Williams',
      role: 'Community Manager',
      avatar: null,
      bio: 'Connecting with our community and fostering engagement.',
    },
  ];

  return (
    <Section spacing="xl">
      <Container>
        {/* Hero Section */}
        <div className="text-center max-w-3xl mx-auto mb-16">
          <H1 className="mb-6">About Blog Platform</H1>
          <Text size="xl" color="muted" className="mb-8">
            We're on a mission to make knowledge accessible to everyone. 
            Our platform connects writers with readers, fostering a community 
            of learning and growth.
          </Text>
          <div className="flex gap-4 justify-center">
            <Button onClick={() => (window.location.href = ROUTES.POSTS)}>
              Explore Posts
            </Button>
            <Button variant="outline" onClick={() => (window.location.href = ROUTES.CONTACT || '/contact')}>
              Contact Us
            </Button>
          </div>
        </div>

        {/* Stats */}
        <div className="grid grid-cols-2 md:grid-cols-4 gap-6 mb-16">
          {stats.map((stat) => (
            <div
              key={stat.label}
              className="text-center p-6 rounded-xl bg-secondary-50 dark:bg-secondary-800/50"
            >
              <stat.icon className="w-8 h-8 mx-auto mb-3 text-primary-600 dark:text-primary-400" />
              <p className="text-3xl font-bold text-secondary-900 dark:text-secondary-100 mb-1">
                {stat.value}
              </p>
              <p className="text-sm text-secondary-600 dark:text-secondary-400">{stat.label}</p>
            </div>
          ))}
        </div>

        {/* Features */}
        <div className="mb-16">
          <H2 className="text-center mb-12">Why Choose Us</H2>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            {features.map((feature) => (
              <div
                key={feature.title}
                className="p-6 rounded-xl border border-secondary-200 dark:border-secondary-700 hover:shadow-lg transition-all"
              >
                <div className={`w-12 h-12 rounded-lg ${feature.bgColor} flex items-center justify-center mb-4`}>
                  <feature.icon className={`w-6 h-6 ${feature.color}`} />
                </div>
                <H3 className="text-lg font-semibold mb-2">{feature.title}</H3>
                <Text color="muted" className="text-sm">
                  {feature.description}
                </Text>
              </div>
            ))}
          </div>
        </div>

        {/* Story Section */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center mb-16">
          <div>
            <H2 className="mb-6">Our Story</H2>
            <div className="space-y-4 text-secondary-700 dark:text-secondary-300">
              <p>
                Founded in 2024, Blog Platform started with a simple idea: make it easy for 
                anyone to share their knowledge and for everyone to learn something new every day.
              </p>
              <p>
                What began as a small blog has grown into a thriving community of writers and 
                readers from around the world. We believe in the power of words to inspire, 
                educate, and connect people.
              </p>
              <p>
                Our platform is built on the principles of quality, accessibility, and community. 
                We're committed to providing a space where diverse voices can be heard and where 
                learning never stops.
              </p>
            </div>
          </div>
          <div className="relative">
            <div className="aspect-square rounded-2xl bg-gradient-to-br from-primary-500 to-secondary-600 flex items-center justify-center">
              <BookOpen className="w-32 h-32 text-white/50" />
            </div>
            {/* Decorative elements */}
            <div className="absolute -top-4 -right-4 w-24 h-24 bg-primary-200 dark:bg-primary-800 rounded-xl -z-10" />
            <div className="absolute -bottom-4 -left-4 w-24 h-24 bg-secondary-200 dark:bg-secondary-800 rounded-xl -z-10" />
          </div>
        </div>

        {/* Team Section */}
        <div className="mb-16">
          <H2 className="text-center mb-4">Meet Our Team</H2>
          <Text color="muted" className="text-center max-w-2xl mx-auto mb-12">
            The passionate people behind Blog Platform working to bring you the best content experience.
          </Text>
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            {team.map((member) => (
              <div
                key={member.name}
                className="text-center p-6 rounded-xl bg-secondary-50 dark:bg-secondary-800/50"
              >
                <div className="w-20 h-20 rounded-full bg-gradient-to-br from-primary-500 to-secondary-600 flex items-center justify-center mx-auto mb-4">
                  <span className="text-2xl font-bold text-white">
                    {member.name.split(' ').map(n => n[0]).join('')}
                  </span>
                </div>
                <H3 className="font-semibold mb-1">{member.name}</H3>
                <p className="text-sm text-primary-600 dark:text-primary-400 mb-3">{member.role}</p>
                <Text color="muted" className="text-sm">
                  {member.bio}
                </Text>
              </div>
            ))}
          </div>
        </div>

        {/* Values Section */}
        <div className="bg-gradient-to-br from-secondary-900 to-secondary-800 dark:from-secondary-800 dark:to-secondary-900 rounded-2xl p-8 md:p-12 mb-16">
          <H2 className="text-white text-center mb-12">Our Values</H2>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div className="text-center">
              <Heart className="w-12 h-12 text-red-400 mx-auto mb-4" />
              <H3 className="text-white text-lg font-semibold mb-2">Passion</H3>
              <p className="text-secondary-300">
                We're passionate about content creation and helping others learn and grow.
              </p>
            </div>
            <div className="text-center">
              <Coffee className="w-12 h-12 text-amber-400 mx-auto mb-4" />
              <H3 className="text-white text-lg font-semibold mb-2">Community</H3>
              <p className="text-secondary-300">
                Building a supportive community where everyone can share and learn together.
              </p>
            </div>
            <div className="text-center">
              <Award className="w-12 h-12 text-green-400 mx-auto mb-4" />
              <H3 className="text-white text-lg font-semibold mb-2">Excellence</H3>
              <p className="text-secondary-300">
                Striving for excellence in everything we do, from content quality to user experience.
              </p>
            </div>
          </div>
        </div>

        {/* CTA Section */}
        <div className="text-center">
          <H2 className="mb-4">Want to Contribute?</H2>
          <Text color="muted" className="max-w-2xl mx-auto mb-8">
            We're always looking for talented writers to join our community. 
            Share your expertise and reach thousands of readers.
          </Text>
          <div className="flex gap-4 justify-center">
            <Button onClick={() => (window.location.href = ROUTES.REGISTER)}>
              Start Writing
            </Button>
            <Button variant="outline" onClick={() => (window.location.href = ROUTES.CONTACT || '/contact')}>
              <Mail className="w-4 h-4 mr-2" />
              Get in Touch
            </Button>
          </div>
        </div>
      </Container>
    </Section>
  );
}

export default AboutPage;
