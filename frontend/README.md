# Frontend Quick Start Guide

## Prerequisites

- Node.js 18+ installed
- Backend server running on `http://localhost:8000`

## Getting Started

### 1. Install Dependencies

```bash
cd frontend
npm install
```

### 2. Configure Environment

Copy `.env.example` to `.env`:

```bash
cp .env.example .env
```

Update the values if needed:

```env
VITE_API_BASE_URL=http://localhost:8000/api
VITE_APP_NAME=Blog Platform
```

### 3. Start Development Server

```bash
npm run dev
```

The app will be available at `http://localhost:3000`

---

## Available Scripts

| Command | Description |
|---------|-------------|
| `npm run dev` | Start development server |
| `npm run build` | Build for production |
| `npm run preview` | Preview production build |
| `npm run lint` | Run ESLint |
| `npm run lint:fix` | Fix ESLint issues |
| `npm run format` | Format code with Prettier |

---

## Project Structure

```
frontend/
├── src/
│   ├── components/    # Reusable UI components
│   ├── pages/         # Page components
│   ├── layouts/       # Layout components
│   ├── contexts/      # React contexts
│   ├── hooks/         # Custom hooks
│   ├── services/      # API services
│   ├── utils/         # Utility functions
│   ├── constants/     # App constants
│   └── routes/        # Route configuration
```

---

## Component Library

### Atoms (Basic Components)
- `Button` - Button with variants
- `Input` - Form input with validation
- `Textarea` - Multi-line text input
- `Badge` - Status labels
- `Avatar` - User avatar
- `Spinner` - Loading indicator
- `Skeleton` - Loading placeholder
- `Typography` - H1-H6, Text, Link

### Molecules (Composite Components)
- `Card` - Content card
- `Modal` - Dialog modal
- `Dropdown` - Dropdown menu
- `Alert` - Alert messages
- `Toast` - Notification toast
- `PostCard` - Blog post card

### Organisms (Complex Components)
- `Header` - Site header
- `Footer` - Site footer
- `Sidebar` - Admin sidebar
- `ThemeToggle` - Dark mode toggle

---

## Usage Examples

### Using Components

```jsx
import { Button, Input, H1, Text } from '@/components/atoms';
import { Card, Alert } from '@/components/molecules';
import { Container, Section } from '@/components';

function MyComponent() {
  return (
    <Section>
      <Container>
        <H1>Welcome</H1>
        <Text color="muted">This is a sample page</Text>
        
        <Card className="mt-4">
          <Card.Content>
            <Input label="Email" placeholder="Enter email" />
            <Button className="mt-4">Submit</Button>
          </Card.Content>
        </Card>
      </Container>
    </Section>
  );
}
```

### Using Hooks

```jsx
import { usePosts, usePost } from '@/hooks';
import { useAuth } from '@/contexts/AuthContext';
import { useTheme } from '@/contexts/ThemeContext';

function MyComponent() {
  const { data: posts } = usePosts({ limit: 10 });
  const { user, isAuthenticated } = useAuth();
  const { theme, setTheme } = useTheme();
  
  return <div>...</div>;
}
```

### Making API Calls

```jsx
import { postService, authService } from '@/services';

// Create a post
const post = await postService.create({
  title: 'My Post',
  content: 'Post content...',
  category_id: 1,
});

// Login
const result = await authService.login({
  email: 'user@example.com',
  password: 'password',
});
```

---

## Routing

Routes are defined in `src/routes/index.jsx`:

| Route | Component | Layout |
|-------|-----------|--------|
| `/` | HomePage | MainLayout |
| `/posts` | PostsPage | MainLayout |
| `/posts/:slug` | PostDetailPage | MainLayout |
| `/login` | LoginPage | AuthLayout |
| `/register` | RegisterPage | AuthLayout |
| `/admin/*` | Admin pages | DashboardLayout |

---

## State Management

### Server State (React Query)

```jsx
import { useQuery, useMutation } from '@tanstack/react-query';

// Query
const { data, isLoading, error } = useQuery({
  queryKey: ['posts'],
  queryFn: () => postService.getAll(),
});

// Mutation
const mutation = useMutation({
  mutationFn: (data) => postService.create(data),
  onSuccess: () => {
    queryClient.invalidateQueries(['posts']);
  },
});
```

### Auth State (Context)

```jsx
import { useAuth } from '@/contexts/AuthContext';

const { user, isAuthenticated, login, logout } = useAuth();
```

### Theme State (Context)

```jsx
import { useTheme } from '@/contexts/ThemeContext';

const { theme, setTheme, isDark } = useTheme();
```

---

## Styling

### Tailwind CSS

All components use Tailwind CSS for styling:

```jsx
<div className="flex items-center gap-4 p-4 bg-white dark:bg-secondary-800 rounded-lg">
  <span className="text-secondary-900 dark:text-secondary-100">
    Hello World
  </span>
</div>
```

### Class Name Utility

Use `cn()` for conditional classes:

```jsx
import { cn } from '@/utils';

<div className={cn(
  'base-class',
  isActive && 'active-class',
  className
)} />
```

---

## Dark Mode

The app supports dark mode out of the box:

1. Toggle via the theme button in the header
2. System preference is automatically detected
3. Preference is saved in localStorage

---

## Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `VITE_API_BASE_URL` | Backend API URL | `http://localhost:8000/api` |
| `VITE_APP_NAME` | Application name | `Blog Platform` |

---

## Troubleshooting

### API Connection Issues

1. Ensure backend is running on port 8000
2. Check `VITE_API_BASE_URL` in `.env`
3. Verify CORS is enabled in backend

### Build Errors

```bash
# Clear cache and reinstall
rm -rf node_modules package-lock.json
npm install
```

### ESLint/Prettier Issues

```bash
# Auto-fix issues
npm run lint:fix
npm run format
```

---

## Additional Resources

- [React Documentation](https://react.dev)
- [Vite Documentation](https://vitejs.dev)
- [Tailwind CSS](https://tailwindcss.com)
- [React Query](https://tanstack.com/query)
- [React Router](https://reactrouter.com)
